import { notFound } from "next/navigation";
import { AppShell } from "@/components/AppShell";
import { LoanDetailView } from "@/components/LoanDetailView";
import { requireUser } from "@/lib/auth";
import { calculateFine } from "@/lib/services";
import { readDb } from "@/lib/storage";

export const dynamic = "force-dynamic";

export default async function StaffLoanShowPage({
  params,
}: {
  params: Promise<{ id: string }>;
}) {
  const user = await requireUser(["petugas"]);
  const { id } = await params;
  const db = await readDb();

  const loan = db.loans.find((l) => l.id === Number(id));
  if (!loan) notFound();

  const userById = new Map(db.users.map((u) => [u.id, u]));
  const eqById = new Map(db.equipments.map((e) => [e.id, e]));
  const borrower = userById.get(loan.user_id);
  const ret = db.returns.find((r) => r.loan_id === loan.id) ?? null;
  const finePreview = calculateFine(db, loan.due_date);

  const items = db.loan_details
    .filter((d) => d.loan_id === loan.id)
    .map((d) => {
      const eq = eqById.get(d.equipment_id);
      return {
        id: d.id,
        equipment_code: eq?.code ?? "-",
        equipment_name: eq?.name ?? "-",
        location: eq?.location ?? null,
        qty: d.qty,
        qty_available: eq?.qty_available ?? 0,
        condition_before: d.condition_before,
      };
    });

  return (
    <AppShell
      user={user}
      header={`Verifikasi Peminjaman — ${loan.code}`}
      subheader="Setujui, tolak dengan alasan jelas, atau catat penyerahan fisik alat."
    >
      <LoanDetailView
        viewerRole="petugas"
        backHref="/staff/loans"
        returnDetailHref={ret ? `/staff/returns/${ret.id}` : null}
        loan={{
          id: loan.id,
          code: loan.code,
          loan_date: loan.loan_date,
          due_date: loan.due_date,
          purpose: loan.purpose,
          notes: loan.notes,
          status: loan.status,
          rejection_reason: loan.rejection_reason,
          approved_by_name: loan.approved_by
            ? userById.get(loan.approved_by)?.name ?? null
            : null,
          approved_at: loan.approved_at,
          handed_over_by_name: loan.handed_over_by
            ? userById.get(loan.handed_over_by)?.name ?? null
            : null,
          handed_over_at: loan.handed_over_at,
          borrower_name: borrower?.name ?? "-",
          borrower_username: borrower?.username ?? "-",
          borrower_identity: borrower?.identity_number ?? null,
          borrower_phone: borrower?.phone ?? null,
          borrower_address: borrower?.address ?? null,
          current_late_days: finePreview.late_days,
          estimated_fine: finePreview.fine_amount,
          fine_rate: finePreview.fine_rate,
        }}
        items={items}
        returnRecord={ret}
      />
    </AppShell>
  );
}
