import Link from "next/link";
import { AppShell } from "@/components/AppShell";
import { EmptyState } from "@/components/EmptyState";
import { StatusBadge } from "@/components/StatusBadge";
import { requireUser } from "@/lib/auth";
import { readDb } from "@/lib/storage";
import {
  formatRupiah,
  formatShortDate,
  getReturnStatusLabel,
} from "@/lib/types";

export const dynamic = "force-dynamic";

export default async function AdminReturnsPage({
  searchParams,
}: {
  searchParams: Promise<Record<string, string | undefined>>;
}) {
  const user = await requireUser(["admin"]);
  const sp = await searchParams;
  const db = await readDb();

  const status = sp.status || "";
  const userById = new Map(db.users.map((u) => [u.id, u]));
  const loanById = new Map(db.loans.map((l) => [l.id, l]));

  const returns = db.returns
    .filter((r) => !status || r.status === status)
    .sort((a, b) => b.id - a.id);

  return (
    <AppShell
      user={user}
      header="Manajemen Pengembalian & Denda"
      subheader="Verifikasi pengembalian peralatan, kondisi fisik kembali, dan pelunasan denda keterlambatan."
    >
      <div className="mb-6 flex flex-wrap gap-2">
        {[
          { k: "", label: "Semua Pengembalian" },
          { k: "pending_verification", label: "Menunggu Verifikasi" },
          { k: "verified", label: "Sudah Diverifikasi" },
        ].map((tab) => (
          <Link
            key={tab.k}
            href={tab.k ? `/admin/returns?status=${tab.k}` : "/admin/returns"}
            className={`rounded-md border px-3 py-1.5 text-xs font-semibold ${
              status === tab.k
                ? "border-slate-900 bg-slate-900 text-white"
                : "border-slate-200 bg-white text-slate-700 hover:bg-slate-50"
            }`}
          >
            {tab.label}
          </Link>
        ))}
      </div>

      <div className="panel">
        <div className="table-container">
          {returns.length === 0 ? (
            <EmptyState
              title="Tidak Ada Data Pengembalian"
              description="Belum ada transaksi pengembalian pada filter ini."
            />
          ) : (
            <table className="table-pro">
              <thead>
                <tr>
                  <th>Kode Kembali</th>
                  <th>Kode Pinjam</th>
                  <th>Peminjam</th>
                  <th>Tgl Kembali</th>
                  <th>Terlambat</th>
                  <th>Denda</th>
                  <th>Status</th>
                  <th className="text-right">Aksi</th>
                </tr>
              </thead>
              <tbody>
                {returns.map((ret) => {
                  const loan = loanById.get(ret.loan_id);
                  return (
                    <tr key={ret.id}>
                      <td className="font-mono text-xs font-bold text-slate-900">
                        {ret.code}
                      </td>
                      <td className="font-mono text-xs">{loan?.code ?? "-"}</td>
                      <td className="font-medium text-slate-900">
                        {userById.get(ret.returned_by)?.name ?? "-"}
                      </td>
                      <td className="text-xs">
                        {formatShortDate(ret.return_date)}
                      </td>
                      <td className="text-xs">{ret.late_days} hari</td>
                      <td className="text-xs font-semibold">
                        {formatRupiah(ret.fine_amount)}{" "}
                        {ret.fine_amount > 0 && (
                          <span
                            className={
                              ret.fine_paid
                                ? "text-emerald-700"
                                : "text-rose-600"
                            }
                          >
                            ({ret.fine_paid ? "Lunas" : "Belum"})
                          </span>
                        )}
                      </td>
                      <td>
                        <StatusBadge
                          variant={ret.status}
                          label={getReturnStatusLabel(ret.status)}
                        />
                      </td>
                      <td className="text-right">
                        <Link
                          href={`/admin/returns/${ret.id}`}
                          className="btn-primary btn-xs"
                        >
                          {ret.status === "pending_verification"
                            ? "Verifikasi"
                            : "Detail"}
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
