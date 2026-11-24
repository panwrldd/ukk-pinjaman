import Link from "next/link";
import { notFound } from "next/navigation";
import { ArrowLeft, Edit } from "lucide-react";
import { AppShell } from "@/components/AppShell";
import { StatusBadge } from "@/components/StatusBadge";
import { requireUser } from "@/lib/auth";
import { readDb } from "@/lib/storage";
import {
  formatShortDate,
  getConditionLabel,
  getEquipmentStatusLabel,
  getLoanStatusLabel,
  getQtyBorrowed,
} from "@/lib/types";
import { EquipmentDetailActions } from "./EquipmentDetailActions";

export const dynamic = "force-dynamic";

export default async function AdminEquipmentShowPage({
  params,
}: {
  params: Promise<{ id: string }>;
}) {
  const user = await requireUser(["admin"]);
  const { id } = await params;
  const db = await readDb();

  const eq = db.equipments.find((e) => e.id === Number(id));
  if (!eq) notFound();

  const category = db.categories.find((c) => c.id === eq.category_id);
  const userById = new Map(db.users.map((u) => [u.id, u]));

  const relatedDetails = db.loan_details.filter((d) => d.equipment_id === eq.id);
  const relatedLoanIds = new Set(relatedDetails.map((d) => d.loan_id));
  const relatedLoans = db.loans
    .filter((l) => relatedLoanIds.has(l.id))
    .sort((a, b) => b.id - a.id);

  return (
    <AppShell
      user={user}
      header={`Detail Peralatan — ${eq.code}`}
      subheader={eq.name}
    >
      <div className="space-y-6">
        <div className="flex items-center justify-between">
          <Link href="/admin/equipments" className="btn-secondary btn-xs">
            <ArrowLeft className="h-3.5 w-3.5" />
            <span>Kembali ke Daftar Alat</span>
          </Link>
          <Link
            href={`/admin/equipments/${eq.id}/edit`}
            className="btn-primary btn-xs"
          >
            <Edit className="h-3.5 w-3.5" />
            <span>Edit Data Alat</span>
          </Link>
        </div>

        <div className="panel p-6">
          <div className="flex flex-wrap items-start justify-between gap-6">
            <div className="flex-1 space-y-2">
              <div className="flex flex-wrap items-center gap-2">
                <span className="font-mono text-sm font-bold text-slate-600">
                  {eq.code}
                </span>
                <StatusBadge
                  variant={eq.status}
                  label={getEquipmentStatusLabel(eq.status)}
                />
                <StatusBadge
                  variant={eq.condition}
                  label={`Kondisi: ${getConditionLabel(eq.condition)}`}
                />
              </div>
              <h2 className="text-lg font-bold text-slate-900">{eq.name}</h2>
              <p className="text-xs text-slate-600">
                Kategori: <strong>{category?.name ?? "-"}</strong> · Lokasi:{" "}
                <strong>{eq.location || "-"}</strong>
              </p>
              {eq.description && (
                <p className="pt-2 text-sm text-slate-700">{eq.description}</p>
              )}
              {eq.notes && (
                <p className="rounded-md bg-slate-50 p-3 text-xs text-slate-600">
                  <strong>Catatan SOP:</strong> {eq.notes}
                </p>
              )}
            </div>

            {eq.photo && (
              <img
                src={eq.photo}
                alt={eq.name}
                className="h-36 w-36 rounded-lg border border-slate-200 object-cover"
              />
            )}
          </div>

          <div className="mt-6 grid grid-cols-2 gap-4 border-t border-slate-100 pt-5 sm:grid-cols-4">
            <div className="rounded-md border border-slate-200 p-3">
              <span className="text-[11px] font-semibold uppercase text-slate-500">
                Total Inventaris
              </span>
              <p className="mt-1 text-xl font-bold text-slate-900">
                {eq.qty_total} unit
              </p>
            </div>
            <div className="rounded-md border border-emerald-200 bg-emerald-50/40 p-3">
              <span className="text-[11px] font-semibold uppercase text-emerald-700">
                Siap Dipinjam
              </span>
              <p className="mt-1 text-xl font-bold text-emerald-800">
                {eq.qty_available} unit
              </p>
            </div>
            <div className="rounded-md border border-blue-200 bg-blue-50/40 p-3">
              <span className="text-[11px] font-semibold uppercase text-blue-700">
                Sedang Dipinjam
              </span>
              <p className="mt-1 text-xl font-bold text-blue-800">
                {getQtyBorrowed(eq)} unit
              </p>
            </div>
            <div className="rounded-md border border-rose-200 bg-rose-50/40 p-3">
              <span className="text-[11px] font-semibold uppercase text-rose-700">
                Rusak / Maintenance
              </span>
              <p className="mt-1 text-xl font-bold text-rose-800">
                {eq.qty_damaged} unit
              </p>
            </div>
          </div>
        </div>

        <EquipmentDetailActions
          equipmentId={eq.id}
          equipmentCode={eq.code}
          qtyDamaged={eq.qty_damaged}
        />

        <div className="panel">
          <div className="panel-header">
            <h3 className="panel-title">Riwayat Peminjaman Alat Ini</h3>
          </div>
          <div className="table-container">
            <table className="table-pro">
              <thead>
                <tr>
                  <th>Kode Pinjam</th>
                  <th>Peminjam</th>
                  <th>Tgl Pinjam</th>
                  <th>Batas Kembali</th>
                  <th>Jumlah</th>
                  <th>Status</th>
                </tr>
              </thead>
              <tbody>
                {relatedLoans.length === 0 ? (
                  <tr>
                    <td colSpan={6} className="py-6 text-center text-xs text-slate-500">
                      Belum ada riwayat peminjaman untuk alat ini.
                    </td>
                  </tr>
                ) : (
                  relatedLoans.map((loan) => {
                    const ld = relatedDetails.find((d) => d.loan_id === loan.id);
                    return (
                      <tr key={loan.id}>
                        <td>
                          <Link
                            href={`/admin/loans/${loan.id}`}
                            className="font-mono text-xs font-bold text-blue-800 hover:underline"
                          >
                            {loan.code}
                          </Link>
                        </td>
                        <td>{userById.get(loan.user_id)?.name ?? "-"}</td>
                        <td>{formatShortDate(loan.loan_date)}</td>
                        <td>{formatShortDate(loan.due_date)}</td>
                        <td>{ld?.qty ?? 0} unit</td>
                        <td>
                          <StatusBadge
                            variant={loan.status}
                            label={getLoanStatusLabel(loan.status)}
                          />
                        </td>
                      </tr>
                    );
                  })
                )}
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </AppShell>
  );
}
