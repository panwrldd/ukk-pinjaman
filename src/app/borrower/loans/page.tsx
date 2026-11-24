import Link from "next/link";
import { Plus } from "lucide-react";
import { AppShell } from "@/components/AppShell";
import { EmptyState } from "@/components/EmptyState";
import { StatusBadge } from "@/components/StatusBadge";
import { requireUser } from "@/lib/auth";
import { readDb } from "@/lib/storage";
import { formatShortDate, getLoanStatusLabel } from "@/lib/types";

export const dynamic = "force-dynamic";

export default async function BorrowerLoansIndexPage({
  searchParams,
}: {
  searchParams: Promise<Record<string, string | undefined>>;
}) {
  const user = await requireUser(["peminjam"]);
  const sp = await searchParams;
  const db = await readDb();

  const status = sp.status || "";
  const eqById = new Map(db.equipments.map((e) => [e.id, e]));

  const loans = db.loans
    .filter((l) => l.user_id === user.id && (!status || l.status === status))
    .sort((a, b) => b.id - a.id);

  return (
    <AppShell
      user={user}
      header="Pinjaman & Riwayat Pengajuan Saya"
      subheader="Lacak status verifikasi, jadwal pengembalian, dan riwayat peminjaman peralatan Anda."
    >
      <div className="mb-6 flex flex-wrap items-center justify-between gap-3">
        <div className="flex flex-wrap gap-2">
          {[
            { k: "", label: "Semua" },
            { k: "pending", label: "Menunggu" },
            { k: "approved", label: "Disetujui" },
            { k: "borrowed", label: "Sedang Dipinjam" },
            { k: "overdue", label: "Terlambat" },
            { k: "returned", label: "Selesai" },
          ].map((t) => (
            <Link
              key={t.k}
              href={t.k ? `/borrower/loans?status=${t.k}` : "/borrower/loans"}
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

        <Link href="/borrower/loans/create" className="btn-primary btn-xs">
          <Plus className="h-3.5 w-3.5" />
          <span>Ajukan Peminjaman Baru</span>
        </Link>
      </div>

      <div className="panel">
        <div className="table-container">
          {loans.length === 0 ? (
            <EmptyState
              title="Belum Ada Data Peminjaman"
              description="Anda belum memiliki transaksi pada kategori status ini."
            />
          ) : (
            <table className="table-pro">
              <thead>
                <tr>
                  <th>Kode</th>
                  <th>Keperluan & Daftar Alat</th>
                  <th>Tgl Pinjam</th>
                  <th>Batas Kembali</th>
                  <th>Status</th>
                  <th className="text-right">Aksi</th>
                </tr>
              </thead>
              <tbody>
                {loans.map((loan) => {
                  const items = db.loan_details
                    .filter((d) => d.loan_id === loan.id)
                    .map(
                      (d) =>
                        `${eqById.get(d.equipment_id)?.name ?? "-"} (${
                          d.qty
                        } unit)`
                    )
                    .join(", ");
                  return (
                    <tr key={loan.id}>
                      <td className="font-mono text-xs font-bold text-slate-900">
                        {loan.code}
                      </td>
                      <td>
                        <p className="font-semibold text-slate-900">
                          {loan.purpose}
                        </p>
                        <p className="text-xs text-slate-500">{items}</p>
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
                          href={`/borrower/loans/${loan.id}`}
                          className="btn-secondary btn-xs"
                        >
                          Detail
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
