import { notFound } from "next/navigation";
import { AppShell } from "@/components/AppShell";
import { LoanDetailView } from "@/components/LoanDetailView";
import { requireUser } from "@/lib/auth";
import { calculateFine } from "@/lib/services";
import { readDb } from "@/lib/storage";

export const dynamic = "force-dynamic";

export default async function BorrowerLoanShowPage({
  params,
}: {
  params: Promise<{ id: string }>;
}) {
  const user = await requireUser(["peminjam"]);
  const { id } = await params;
  const db = await readDb();

  const loan = db.loans.find((l) => l.id === Number(id));
  if (!loan || loan.user_id !== user.id) notFound();

  const userById = new Map(db.users.map((u) => [u.id, u]));
  const eqById = new Map(db.equipments.map((e) => [e.id, e]));
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
      header={`Rincian Peminjaman Saya — ${loan.code}`}
      subheader="Pantau status persetujuan petugas atau ajukan pengembalian peralatan."
    >
      <LoanDetailView
        viewerRole="peminjam"
        backHref="/borrower/loans"
        returnDetailHref={null}
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
          borrower_name: user.name,
          borrower_username: user.username,
          borrower_identity: user.identity_number,
          borrower_phone: user.phone,
          borrower_address: user.address,
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
