import Link from "next/link";
import { AppShell } from "@/components/AppShell";
import { EmptyState } from "@/components/EmptyState";
import { StatusBadge } from "@/components/StatusBadge";
import { requireUser } from "@/lib/auth";
import { readDb } from "@/lib/storage";
import { getConditionLabel } from "@/lib/types";

export const dynamic = "force-dynamic";

export default async function BorrowerCatalogPage({
  searchParams,
}: {
  searchParams: Promise<Record<string, string | undefined>>;
}) {
  const user = await requireUser(["peminjam"]);
  const sp = await searchParams;
  const db = await readDb();

  const q = (sp.q || "").trim().toLowerCase();
  const categoryId = sp.category_id ? Number(sp.category_id) : 0;

  const activeCategories = db.categories.filter((c) => c.is_active);
  const activeCatIds = new Set(activeCategories.map((c) => c.id));
  const catById = new Map(activeCategories.map((c) => [c.id, c]));

  const equipments = db.equipments
    .filter((eq) => {
      if (!activeCatIds.has(eq.category_id)) return false;
      if (categoryId && eq.category_id !== categoryId) return false;
      if (q) {
        const hay = `${eq.code} ${eq.name} ${eq.description ?? ""}`.toLowerCase();
        if (!hay.includes(q)) return false;
      }
      return true;
    })
    .sort((a, b) => a.code.localeCompare(b.code));

  return (
    <AppShell
      user={user}
      header="Katalog Peralatan Laboratorium"
      subheader="Telusuri ketersediaan peralatan multimedia, audio, proyektor, dan jaringan secara real-time."
    >
      <div className="panel mb-6 p-4">
        <form
          method="GET"
          action="/borrower/catalog"
          className="flex flex-wrap items-end gap-3"
        >
          <div className="flex-1 min-w-[200px]">
            <label className="input-label">Cari Nama atau Kode Alat</label>
            <input
              type="text"
              name="q"
              defaultValue={sp.q || ""}
              placeholder="Contoh: Canon, Proyektor, MikroTik..."
              className="input-field"
            />
          </div>
          <div className="w-60">
            <label className="input-label">Kategori</label>
            <select
              name="category_id"
              defaultValue={sp.category_id || ""}
              className="input-field"
            >
              <option value="">Semua Kategori Aktif</option>
              {activeCategories.map((c) => (
                <option key={c.id} value={c.id}>
                  {c.name}
                </option>
              ))}
            </select>
          </div>
          <button type="submit" className="btn-primary">
            Cari Alat
          </button>
          <Link href="/borrower/catalog" className="btn-secondary">
            Reset
          </Link>
        </form>
      </div>

      {equipments.length === 0 ? (
        <div className="panel">
          <EmptyState
            title="Peralatan Tidak Ditemukan"
            description="Coba ubah kata kunci pencarian atau pilih kategori lain."
          />
        </div>
      ) : (
        <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
          {equipments.map((eq) => {
            const canBorrow =
              eq.status === "tersedia" &&
              eq.qty_available > 0 &&
              eq.condition !== "rusak_berat";
            return (
              <div
                key={eq.id}
                className="panel flex flex-col justify-between p-5"
              >
                <div>
                  <div className="flex items-center justify-between gap-2">
                    <span className="font-mono text-xs font-bold text-slate-500">
                      {eq.code}
                    </span>
                    <StatusBadge
                      variant={canBorrow ? "tersedia" : "slate"}
                      label={
                        canBorrow
                          ? `${eq.qty_available} dari ${eq.qty_total} tersedia`
                          : "Stok Kosong / Servis"
                      }
                    />
                  </div>
                  <Link
                    href={`/borrower/catalog/${eq.id}`}
                    className="mt-2 block text-sm font-bold text-slate-900 hover:text-blue-800"
                  >
                    {eq.name}
                  </Link>
                  <p className="mt-1 text-xs text-slate-500">
                    {catById.get(eq.category_id)?.name ?? "-"} · Kondisi:{" "}
                    {getConditionLabel(eq.condition)}
                  </p>
                  {eq.description && (
                    <p className="mt-2 line-clamp-2 text-xs text-slate-600">
                      {eq.description}
                    </p>
                  )}
                </div>

                <div className="mt-4 flex items-center justify-between border-t border-slate-100 pt-3">
                  <Link
                    href={`/borrower/catalog/${eq.id}`}
                    className="text-xs font-semibold text-slate-600 hover:text-slate-900"
                  >
                    Detail Alat
                  </Link>
                  {canBorrow ? (
                    <Link
                      href={`/borrower/loans/create?equipment_id=${eq.id}`}
                      className="btn-primary btn-xs"
                    >
                      Pinjam Alat Ini
                    </Link>
                  ) : (
                    <span className="text-xs text-slate-400">
                      Tidak Dapat Dipinjam
                    </span>
                  )}
                </div>
              </div>
            );
          })}
        </div>
      )}
    </AppShell>
  );
}
