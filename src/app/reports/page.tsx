import Link from "next/link";
import { Download, FileSpreadsheet, Printer } from "lucide-react";
import { AppShell } from "@/components/AppShell";
import { EmptyState } from "@/components/EmptyState";
import { requireUser } from "@/lib/auth";
import { REPORT_CATALOG, buildReportData } from "@/lib/services";
import { readDb } from "@/lib/storage";

export const dynamic = "force-dynamic";

export default async function ReportsPage({
  searchParams,
}: {
  searchParams: Promise<Record<string, string | undefined>>;
}) {
  const user = await requireUser(["admin", "petugas"]);
  const sp = await searchParams;
  const db = await readDb();

  const availableTypes = Object.entries(REPORT_CATALOG).filter(([, meta]) =>
    meta.roles.includes(user.role)
  );

  const activeType =
    sp.type && REPORT_CATALOG[sp.type]?.roles.includes(user.role)
      ? sp.type
      : "loans";

  const filters = {
    date_from: sp.date_from || "",
    date_to: sp.date_to || "",
    status: sp.status || "",
    category_id: sp.category_id || "",
    condition: sp.condition || "",
    role: sp.role || "",
  };

  const report = buildReportData(db, activeType, filters, user.role);

  const qs = new URLSearchParams();
  qs.set("type", activeType);
  if (filters.date_from) qs.set("date_from", filters.date_from);
  if (filters.date_to) qs.set("date_to", filters.date_to);
  if (filters.status) qs.set("status", filters.status);
  if (filters.category_id) qs.set("category_id", filters.category_id);
  if (filters.condition) qs.set("condition", filters.condition);
  if (filters.role) qs.set("role", filters.role);
  const queryString = qs.toString();

  return (
    <AppShell
      user={user}
      header="Pusat Laporan & Dokumentasi Audit"
      subheader="Rekapitulasi inventaris, transaksi peminjaman, pengembalian, keterlambatan, dan denda siap cetak PDF & Excel."
    >
      {/* Report Type Selector Tabs */}
      <div className="mb-6 flex flex-wrap gap-2">
        {availableTypes.map(([key, meta]) => (
          <Link
            key={key}
            href={`/reports?type=${key}`}
            className={`rounded-md border px-3 py-2 text-xs font-semibold transition ${
              activeType === key
                ? "border-slate-900 bg-slate-900 text-white"
                : "border-slate-200 bg-white text-slate-700 hover:bg-slate-50"
            }`}
          >
            {meta.title}
          </Link>
        ))}
      </div>

      {/* Filter & Export Action Bar */}
      <div className="panel mb-6 p-4">
        <form method="GET" action="/reports" className="space-y-4">
          <input type="hidden" name="type" value={activeType} />
          <div className="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-5">
            {["loans", "returns", "overdue", "fines"].includes(activeType) && (
              <>
                <div>
                  <label className="input-label">Dari Tanggal</label>
                  <input
                    type="date"
                    name="date_from"
                    defaultValue={filters.date_from}
                    className="input-field"
                  />
                </div>
                <div>
                  <label className="input-label">Sampai Tanggal</label>
                  <input
                    type="date"
                    name="date_to"
                    defaultValue={filters.date_to}
                    className="input-field"
                  />
                </div>
              </>
            )}

            {["loans", "returns", "overdue", "equipments"].includes(
              activeType
            ) && (
              <div>
                <label className="input-label">Kategori Alat</label>
                <select
                  name="category_id"
                  defaultValue={filters.category_id}
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
            )}

            {activeType === "loans" && (
              <div>
                <label className="input-label">Status Peminjaman</label>
                <select
                  name="status"
                  defaultValue={filters.status}
                  className="input-field"
                >
                  <option value="">Semua Status</option>
                  <option value="pending">Menunggu Persetujuan</option>
                  <option value="approved">Disetujui</option>
                  <option value="borrowed">Sedang Dipinjam</option>
                  <option value="overdue">Terlambat</option>
                  <option value="returned">Selesai Dikembalikan</option>
                  <option value="rejected">Ditolak</option>
                </select>
              </div>
            )}

            {activeType === "fines" && (
              <div>
                <label className="input-label">Status Pembayaran</label>
                <select
                  name="status"
                  defaultValue={filters.status}
                  className="input-field"
                >
                  <option value="">Semua Status</option>
                  <option value="paid">Lunas</option>
                  <option value="unpaid">Belum Dibayar</option>
                </select>
              </div>
            )}

            {activeType === "equipments" && (
              <div>
                <label className="input-label">Kondisi Fisik</label>
                <select
                  name="condition"
                  defaultValue={filters.condition}
                  className="input-field"
                >
                  <option value="">Semua Kondisi</option>
                  <option value="baik">Baik</option>
                  <option value="rusak_ringan">Rusak Ringan</option>
                  <option value="rusak_berat">Rusak Berat</option>
                </select>
              </div>
            )}

            {activeType === "users" && (
              <div>
                <label className="input-label">Role Pengguna</label>
                <select
                  name="role"
                  defaultValue={filters.role}
                  className="input-field"
                >
                  <option value="">Semua Role</option>
                  <option value="admin">Administrator</option>
                  <option value="petugas">Petugas Lab</option>
                  <option value="peminjam">Peminjam</option>
                </select>
              </div>
            )}

            <div className="flex items-end gap-2">
              <button type="submit" className="btn-primary">
                Terapkan Filter
              </button>
              <Link
                href={`/reports?type=${activeType}`}
                className="btn-secondary"
              >
                Reset
              </Link>
            </div>
          </div>
        </form>

        <div className="mt-4 flex flex-wrap items-center justify-between gap-3 border-t border-slate-100 pt-4">
          <div>
            <h2 className="text-sm font-bold text-slate-900">
              {report.meta.title}
            </h2>
            <p className="text-xs text-slate-500">{report.meta.subtitle}</p>
          </div>
          <div className="flex flex-wrap items-center gap-2">
            <Link
              href={`/reports/print?${queryString}`}
              target="_blank"
              className="btn-secondary btn-xs"
            >
              <Printer className="h-3.5 w-3.5" />
              <span>Cetak Laporan</span>
            </Link>
            <Link
              href={`/reports/print?${queryString}&autoprint=1`}
              target="_blank"
              className="btn-blue btn-xs"
            >
              <Download className="h-3.5 w-3.5" />
              <span>Cetak / Simpan PDF</span>
            </Link>
            <a
              href={`/api/reports/excel?${queryString}`}
              className="btn-emerald btn-xs"
            >
              <FileSpreadsheet className="h-3.5 w-3.5" />
              <span>Ekspor Excel (.xlsx)</span>
            </a>
          </div>
        </div>
      </div>

      {/* Summary Cards */}
      <div className="mb-6 grid grid-cols-2 gap-4 lg:grid-cols-4">
        {report.summary.map((item) => (
          <div key={item.label} className="panel p-4">
            <span className="text-[11px] font-semibold uppercase tracking-wider text-slate-500">
              {item.label}
            </span>
            <p className="mt-1.5 text-xl font-bold text-slate-900">
              {item.value}
            </p>
          </div>
        ))}
      </div>

      {/* Tabular Data Preview */}
      <div className="panel">
        <div className="table-container">
          {report.rows.length === 0 ? (
            <EmptyState
              title="Tidak Ada Data Laporan"
              description="Tidak ditemukan baris data yang sesuai dengan kriteria filter saat ini."
            />
          ) : (
            <table className="table-pro">
              <thead>
                <tr>
                  {report.headings.map((h) => (
                    <th key={h}>{h}</th>
                  ))}
                </tr>
              </thead>
              <tbody>
                {report.rows.map((row, rIdx) => (
                  <tr key={rIdx}>
                    {row.map((cell, cIdx) => (
                      <td key={cIdx} className="text-xs">
                        {cell}
                      </td>
                    ))}
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
