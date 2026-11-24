import Link from "next/link";
import { AppShell } from "@/components/AppShell";
import { EmptyState } from "@/components/EmptyState";
import { StatusBadge } from "@/components/StatusBadge";
import { requireUser } from "@/lib/auth";
import { readDb } from "@/lib/storage";
import { formatDateTimeId } from "@/lib/types";

export const dynamic = "force-dynamic";

export default async function AdminActivityLogsPage({
  searchParams,
}: {
  searchParams: Promise<Record<string, string | undefined>>;
}) {
  const user = await requireUser(["admin"]);
  const sp = await searchParams;
  const db = await readDb();

  const mod = sp.module || "";
  const userById = new Map(db.users.map((u) => [u.id, u]));

  const logs = [...db.activity_logs]
    .filter((l) => !mod || l.module === mod)
    .sort((a, b) => b.id - a.id);

  return (
    <AppShell
      user={user}
      header="Log Aktivitas Pengguna & Audit Sistem"
      subheader="Rekam jejak seluruh aksi pengguna dan audit perubahan status transaksi otomatis."
    >
      <div className="mb-6 flex flex-wrap gap-2">
        {[
          { k: "", label: "Semua Modul" },
          { k: "loans", label: "Peminjaman" },
          { k: "returns", label: "Pengembalian" },
          { k: "equipments", label: "Inventaris Alat" },
          { k: "db_trigger", label: "Audit Trigger Otomatis" },
          { k: "settings", label: "Pengaturan" },
          { k: "auth", label: "Autentikasi" },
        ].map((item) => (
          <Link
            key={item.k}
            href={
              item.k
                ? `/admin/activity-logs?module=${item.k}`
                : "/admin/activity-logs"
            }
            className={`rounded-md border px-3 py-1.5 text-xs font-semibold ${
              mod === item.k
                ? "border-slate-900 bg-slate-900 text-white"
                : "border-slate-200 bg-white text-slate-700 hover:bg-slate-50"
            }`}
          >
            {item.label}
          </Link>
        ))}
      </div>

      <div className="panel">
        <div className="table-container">
          {logs.length === 0 ? (
            <EmptyState
              title="Belum Ada Log Aktivitas"
              description="Tidak ditemukan catatan log pada modul ini."
            />
          ) : (
            <table className="table-pro">
              <thead>
                <tr>
                  <th>Waktu</th>
                  <th>Pelaku</th>
                  <th>Modul</th>
                  <th>Aksi</th>
                  <th>Deskripsi & Perubahan</th>
                </tr>
              </thead>
              <tbody>
                {logs.map((log) => (
                  <tr key={log.id}>
                    <td className="whitespace-nowrap text-xs text-slate-500">
                      {formatDateTimeId(log.created_at)}
                    </td>
                    <td className="text-xs font-semibold text-slate-900">
                      {log.user_id
                        ? userById.get(log.user_id)?.name ?? "Pengguna"
                        : "Sistem"}
                    </td>
                    <td>
                      <StatusBadge
                        variant={
                          log.module === "db_trigger" ? "indigo" : "slate"
                        }
                        label={log.module}
                      />
                    </td>
                    <td className="font-mono text-xs">{log.action}</td>
                    <td className="text-xs text-slate-700">
                      <p>{log.description}</p>
                      {(log.old_values || log.new_values) && (
                        <p className="mt-1 font-mono text-[11px] text-slate-500">
                          {log.old_values
                            ? `Sebelum: ${JSON.stringify(log.old_values)} `
                            : ""}
                          {log.new_values
                            ? `→ Sesudah: ${JSON.stringify(log.new_values)}`
                            : ""}
                        </p>
                      )}
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          )}
        </div>
      </div>
    </AppShell>
  );
}
