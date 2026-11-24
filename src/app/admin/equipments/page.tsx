import Link from "next/link";
import { Plus } from "lucide-react";
import { AppShell } from "@/components/AppShell";
import { EmptyState } from "@/components/EmptyState";
import { StatusBadge } from "@/components/StatusBadge";
import { requireUser } from "@/lib/auth";
import { readDb } from "@/lib/storage";
import {
  getConditionLabel,
  getEquipmentStatusLabel,
  getQtyBorrowed,
} from "@/lib/types";

export const dynamic = "force-dynamic";

export default async function AdminEquipmentsPage({
  searchParams,
}: {
  searchParams: Promise<Record<string, string | undefined>>;
}) {
  const user = await requireUser(["admin"]);
  const sp = await searchParams;
  const db = await readDb();

  const q = (sp.q || "").trim().toLowerCase();
  const categoryId = sp.category_id ? Number(sp.category_id) : 0;
  const status = sp.status || "";

  const catById = new Map(db.categories.map((c) => [c.id, c]));

  const filtered = db.equipments
    .filter((eq) => {
      if (q) {
        const hay = `${eq.code} ${eq.name} ${eq.location ?? ""}`.toLowerCase();
        if (!hay.includes(q)) return false;
      }
      if (categoryId && eq.category_id !== categoryId) return false;
      if (status && eq.status !== status) return false;
      return true;
    })
    .sort((a, b) => a.code.localeCompare(b.code));

  return (
    <AppShell
      user={user}
      header="Manajemen Inventaris Peralatan"
      subheader="Kelola data master peralatan, stok tersedia, lokasi penyimpanan, dan pemulihan unit maintenance."
    >
      <div className="panel mb-6 p-4">
        <form
          method="GET"
          action="/admin/equipments"
          className="flex flex-wrap items-end justify-between gap-3"
        >
          <div className="grid flex-1 grid-cols-1 gap-3 sm:grid-cols-3">
            <div>
              <label className="input-label">Cari Kode / Nama / Lokasi</label>
              <input
                type="text"
                name="q"
                defaultValue={sp.q || ""}
                placeholder="Ketik nama atau kode alat..."
                className="input-field"
              />
            </div>
            <div>
              <label className="input-label">Kategori</label>
              <select
                name="category_id"
                defaultValue={sp.category_id || ""}
                className="input-field"
              >
                <option value="">Semua Kategori</option>
                {db.categories.map((c) => (
                  <option key={c.id} value={c.id}>
                    {c.name}
                  </option>
                ))}
              </select>
            </div>
            <div>
              <label className="input-label">Status Ketersediaan</label>
              <select
                name="status"
                defaultValue={status}
                className="input-field"
              >
                <option value="">Semua Status</option>
                <option value="tersedia">Tersedia</option>
                <option value="maintenance">Maintenance / Servis</option>
                <option value="tidak_tersedia">Tidak Tersedia</option>
              </select>
            </div>
          </div>
          <div className="flex items-center gap-2">
            <button type="submit" className="btn-secondary">
              Filter
            </button>
            <Link href="/admin/equipments/new" className="btn-primary">
              <Plus className="h-4 w-4" />
              <span>Tambah Peralatan</span>
            </Link>
          </div>
        </form>
      </div>

      <div className="panel">
        <div className="table-container">
          {filtered.length === 0 ? (
            <EmptyState
              title="Peralatan Tidak Ditemukan"
              description="Tidak ada data peralatan yang cocok dengan filter pencarian."
            />
          ) : (
            <table className="table-pro">
              <thead>
                <tr>
                  <th>Kode</th>
                  <th>Nama Peralatan</th>
                  <th>Kategori</th>
                  <th>Kondisi</th>
                  <th>Stok (Tersedia / Total)</th>
                  <th>Dipinjam</th>
                  <th>Rusak</th>
                  <th>Status</th>
                  <th className="text-right">Aksi</th>
                </tr>
              </thead>
              <tbody>
                {filtered.map((eq) => (
                  <tr key={eq.id}>
                    <td className="font-mono text-xs font-bold text-slate-900">
                      {eq.code}
                    </td>
                    <td>
                      <Link
                        href={`/admin/equipments/${eq.id}`}
                        className="font-semibold text-slate-900 hover:text-blue-800"
                      >
                        {eq.name}
                      </Link>
                      <p className="text-xs text-slate-500">
                        {eq.location || "-"}
                      </p>
                    </td>
                    <td className="text-xs">
                      {catById.get(eq.category_id)?.name ?? "-"}
                    </td>
                    <td>
                      <StatusBadge
                        variant={eq.condition}
                        label={getConditionLabel(eq.condition)}
                      />
                    </td>
                    <td className="font-semibold">
                      {eq.qty_available} / {eq.qty_total} unit
                    </td>
                    <td>{getQtyBorrowed(eq)} unit</td>
                    <td>
                      {eq.qty_damaged > 0 ? (
                        <span className="font-semibold text-rose-700">
                          {eq.qty_damaged} unit
                        </span>
                      ) : (
                        "0"
                      )}
                    </td>
                    <td>
                      <StatusBadge
                        variant={eq.status}
                        label={getEquipmentStatusLabel(eq.status)}
                      />
                    </td>
                    <td className="text-right">
                      <div className="inline-flex items-center gap-1.5">
                        <Link
                          href={`/admin/equipments/${eq.id}`}
                          className="btn-secondary btn-xs"
                        >
                          Detail
                        </Link>
                        <Link
                          href={`/admin/equipments/${eq.id}/edit`}
                          className="btn-secondary btn-xs"
                        >
                          Edit
                        </Link>
                      </div>
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
