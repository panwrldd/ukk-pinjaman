import Link from "next/link";
import { AppShell } from "@/components/AppShell";
import { EmptyState } from "@/components/EmptyState";
import { StatusBadge } from "@/components/StatusBadge";
import { requireUser } from "@/lib/auth";
import { readDb } from "@/lib/storage";
import { formatShortDate, getLoanStatusLabel } from "@/lib/types";

export const dynamic = "force-dynamic";

export default async function StaffLoansPage({
  searchParams,
}: {
  searchParams: Promise<Record<string, string | undefined>>;
}) {
  const user = await requireUser(["petugas"]);
  const sp = await searchParams;
  const db = await readDb();

  const status = sp.status || "";
  const userById = new Map(db.users.map((u) => [u.id, u]));
  const eqById = new Map(db.equipments.map((e) => [e.id, e]));

  const loans = db.loans
    .filter((l) => !status || l.status === status)
    .sort((a, b) => b.id - a.id);

  return (
    <AppShell
      user={user}
      header="Persetujuan & Penyerahan Peminjaman"
      subheader="Verifikasi ketersediaan stok, setujui atau tolak pengajuan, dan catat penyerahan alat."
    >
      <div className="mb-6 flex flex-wrap gap-2">
        {[
          { k: "", label: "Semua" },
          { k: "pending", label: "Menunggu Persetujuan" },
          { k: "approved", label: "Siap Diserahkan" },
          { k: "borrowed", label: "Sedang Dipinjam" },
          { k: "overdue", label: "Terlambat" },
          { k: "returned", label: "Selesai" },
        ].map((t) => (
          <Link
            key={t.k}
            href={t.k ? `/staff/loans?status=${t.k}` : "/staff/loans"}
            className={`rounded-md border px-3 py-1.5 text-xs font-semibold ${
              status === t.k
                ? "border-slate-900 bg-slate-900 text-white"
                : "border-slate-200 bg-white text-slate-700 hover:bg-slate-50"
            }`}
          >
            {t.label}
          </Link>
        ))}
      </div>

      <div className="panel">
        <div className="table-container">
          {loans.length === 0 ? (
            <EmptyState
              title="Tidak Ada Data Peminjaman"
              description="Tidak ditemukan transaksi peminjaman pada kategori status ini."
            />
          ) : (
            <table className="table-pro">
              <thead>
                <tr>
                  <th>Kode</th>
                  <th>Peminjam</th>
                  <th>Daftar Alat</th>
                  <th>Pinjam</th>
                  <th>Jatuh Tempo</th>
                  <th>Status</th>
                  <th className="text-right">Aksi</th>
                </tr>
              </thead>
              <tbody>
                {loans.map((loan) => {
                  const b = userById.get(loan.user_id);
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
                          {b?.name ?? "-"}
                        </p>
                        <p className="text-xs text-slate-500">
                          {b?.address || "-"}
                        </p>
                      </td>
                      <td className="max-w-xs truncate text-xs" title={items}>
                        {items}
                      </td>
                      <td className="text-xs">
                        {formatShortDate(loan.loan_date)}
                      </td>
                      <td className="text-xs">
                        {formatShortDate(loan.due_date)}
                      </td>
                      <td>
                        <StatusBadge
                          variant={loan.status}
                          label={getLoanStatusLabel(loan.status)}
                        />
                      </td>
                      <td className="text-right">
                        <Link
                          href={`/staff/loans/${loan.id}`}
                          className="btn-primary btn-xs"
                        >
                          Periksa
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
