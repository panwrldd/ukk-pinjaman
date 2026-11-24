import Link from "next/link";
import { AppShell } from "@/components/AppShell";
import { EmptyState } from "@/components/EmptyState";
import { StatusBadge } from "@/components/StatusBadge";
import { requireUser } from "@/lib/auth";
import { readDb } from "@/lib/storage";
import { formatShortDate, getLoanStatusLabel } from "@/lib/types";

export const dynamic = "force-dynamic";

export default async function AdminLoansPage({
  searchParams,
}: {
  searchParams: Promise<Record<string, string | undefined>>;
}) {
  const user = await requireUser(["admin"]);
  const sp = await searchParams;
  const db = await readDb();

  const status = sp.status || "";
  const q = (sp.q || "").trim().toLowerCase();

  const userById = new Map(db.users.map((u) => [u.id, u]));
  const eqById = new Map(db.equipments.map((e) => [e.id, e]));

  const loans = db.loans
    .filter((l) => {
      if (status && l.status !== status) return false;
      if (q) {
        const b = userById.get(l.user_id);
        const hay = `${l.code} ${l.purpose} ${b?.name ?? ""} ${
          b?.username ?? ""
        }`.toLowerCase();
        if (!hay.includes(q)) return false;
      }
      return true;
    })
    .sort((a, b) => b.id - a.id);

  return (
    <AppShell
      user={user}
      header="Manajemen Data Peminjaman"
      subheader="Pantau seluruh pengajuan, persetujuan, penyerahan fisik, dan status keterlambatan."
    >
      <div className="panel mb-6 p-4">
        <form
          method="GET"
          action="/admin/loans"
          className="flex flex-wrap items-end gap-3"
        >
          <div className="flex-1 min-w-[200px]">
            <label className="input-label">Cari Kode / Peminjam / Keperluan</label>
            <input
              type="text"
              name="q"
              defaultValue={sp.q || ""}
              placeholder="Contoh: LN- atau nama siswa..."
              className="input-field"
            />
          </div>
          <div className="w-56">
            <label className="input-label">Status Transaksi</label>
            <select name="status" defaultValue={status} className="input-field">
              <option value="">Semua Status</option>
              <option value="pending">Menunggu Persetujuan</option>
              <option value="approved">Disetujui (Siap Diambil)</option>
              <option value="borrowed">Sedang Dipinjam</option>
              <option value="overdue">Terlambat (Overdue)</option>
              <option value="returned">Selesai Dikembalikan</option>
              <option value="rejected">Ditolak</option>
              <option value="cancelled">Dibatalkan</option>
            </select>
          </div>
          <button type="submit" className="btn-primary">
            Filter
          </button>
          <Link href="/admin/loans" className="btn-secondary">
            Reset
          </Link>
        </form>
      </div>

      <div className="panel">
        <div className="table-container">
          {loans.length === 0 ? (
            <EmptyState
              title="Tidak Ada Transaksi Peminjaman"
              description="Belum ada transaksi yang sesuai dengan filter."
            />
          ) : (
            <table className="table-pro">
              <thead>
                <tr>
                  <th>Kode</th>
                  <th>Peminjam</th>
                  <th>Peralatan</th>
                  <th>Tgl Pinjam</th>
                  <th>Batas Kembali</th>
                  <th>Status</th>
                  <th className="text-right">Aksi</th>
                </tr>
              </thead>
              <tbody>
                {loans.map((loan) => {
                  const borrower = userById.get(loan.user_id);
                  const items = db.loan_details
                    .filter((d) => d.loan_id === loan.id)
                    .map(
                      (d) =>
                        `${eqById.get(d.equipment_id)?.name ?? "-"} (${d.qty})`
                    )
                    .join(", ");
                  return (
                    <tr key={loan.id}>
                      <td className="font-mono text-xs font-bold text-slate-900">
                        {loan.code}
                      </td>
                      <td>
                        <p className="font-semibold text-slate-900">
                          {borrower?.name ?? "-"}
                        </p>
                        <p className="text-xs text-slate-500">
                          {borrower?.address || "-"}
                        </p>
                      </td>
                      <td className="max-w-xs truncate text-xs" title={items}>
                        {items}
                      </td>
                      <td className="text-xs">{formatShortDate(loan.loan_date)}</td>
                      <td className="text-xs">{formatShortDate(loan.due_date)}</td>
                      <td>
                        <StatusBadge
                          variant={loan.status}
                          label={getLoanStatusLabel(loan.status)}
                        />
                      </td>
                      <td className="text-right">
                        <Link
                          href={`/admin/loans/${loan.id}`}
                          className="btn-secondary btn-xs"
                        >
                          Detail & Kelola
                        </Link>
                      </td>
                    </tr>
                  );
                })}
              </tbody>
            </table>
          )}
        </div>
      </div>
    </AppShell>
  );
}
