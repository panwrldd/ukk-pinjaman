import fs from "fs/promises";
import os from "os";
import path from "path";
import { getStore } from "@netlify/blobs";
import { createInitialSeedDatabase } from "./seed";
import { GearHubDatabase, todayJakarta } from "./types";

const STORE_NAME = "gearhub-ukk-db";
const STATE_KEY = "state-v1";

declare global {
  // eslint-disable-next-line no-var
  var __gearhubMemoryDb: GearHubDatabase | undefined;
}

function getLocalFilePath(): string {
  if (process.env.NETLIFY || process.env.AWS_LAMBDA_FUNCTION_NAME) {
    return path.join(os.tmpdir(), "gearhub-ukk-db.json");
  }
  return path.join(process.cwd(), ".data", "gearhub-ukk-db.json");
}

function syncOverdueInMemory(db: GearHubDatabase): boolean {
  const today = todayJakarta();
  const nowIso = new Date().toISOString();
  let changed = false;

  for (const loan of db.loans) {
    if (loan.status === "borrowed" && loan.due_date < today) {
      const oldStatus = loan.status;
      loan.status = "overdue";
      loan.updated_at = nowIso;
      db.counters.activity_logs += 1;
      db.activity_logs.push({
        id: db.counters.activity_logs,
        user_id: loan.handed_over_by ?? loan.approved_by ?? loan.user_id,
        module: "db_trigger",
        action: "loan_status_changed",
        reference_type: "loans",
        reference_id: loan.id,
        description: `Status transaksi peminjaman ${loan.code} berubah dari "${oldStatus}" menjadi "overdue"`,
        old_values: { status: oldStatus },
        new_values: { status: "overdue" },
        created_at: nowIso,
      });
      changed = true;
    }
  }

  return changed;
}

async function tryGetNetlifyStore() {
  try {
    const store = getStore({ name: STORE_NAME, consistency: "strong" });
    // Probe whether Netlify Blobs environment is active
    await store.getMetadata(STATE_KEY);
    return store;
  } catch {
    return null;
  }
}

export async function readDb(): Promise<GearHubDatabase> {
  const blobStore = await tryGetNetlifyStore();

  if (blobStore) {
    const existing = (await blobStore.get(STATE_KEY, {
      type: "json",
    })) as GearHubDatabase | null;

    if (existing && existing.version === 1) {
      if (syncOverdueInMemory(existing)) {
        await blobStore.setJSON(STATE_KEY, existing);
      }
      return existing;
    }

    const seeded = createInitialSeedDatabase();
    await blobStore.setJSON(STATE_KEY, seeded);
    return seeded;
  }

  // Fallback: local file or tmpdir
  const filePath = getLocalFilePath();
  try {
    const raw = await fs.readFile(filePath, "utf8");
    const parsed = JSON.parse(raw) as GearHubDatabase;
    if (parsed && parsed.version === 1) {
      if (syncOverdueInMemory(parsed)) {
        await writeLocalFile(filePath, parsed);
      }
      global.__gearhubMemoryDb = parsed;
      return parsed;
    }
  } catch {
    // Initialize below
  }

  if (global.__gearhubMemoryDb) {
    syncOverdueInMemory(global.__gearhubMemoryDb);
    return global.__gearhubMemoryDb;
  }

  const seeded = createInitialSeedDatabase();
  global.__gearhubMemoryDb = seeded;
  await writeLocalFile(filePath, seeded);
  return seeded;
}

async function writeLocalFile(filePath: string, db: GearHubDatabase): Promise<void> {
  try {
    await fs.mkdir(path.dirname(filePath), { recursive: true });
    await fs.writeFile(filePath, JSON.stringify(db, null, 2), "utf8");
  } catch {
    // Ignore read-only filesystem errors; memory fallback stays active
  }
}

export async function writeDb(db: GearHubDatabase): Promise<void> {
  global.__gearhubMemoryDb = db;
  const blobStore = await tryGetNetlifyStore();
  if (blobStore) {
    await blobStore.setJSON(STATE_KEY, db);
    return;
  }
  await writeLocalFile(getLocalFilePath(), db);
}

export async function mutateDb<T>(
  mutator: (db: GearHubDatabase) => T | Promise<T>
): Promise<T> {
  const db = await readDb();
  const clone = structuredClone(db);
  const result = await mutator(clone);
  await writeDb(clone);
  return result;
}

export async function resetDbToSeed(): Promise<GearHubDatabase> {
  const seeded = createInitialSeedDatabase();
  await writeDb(seeded);
  return seeded;
}
