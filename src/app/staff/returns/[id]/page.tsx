import { notFound } from "next/navigation";
import { AppShell } from "@/components/AppShell";
import { ReturnVerificationPanel } from "@/components/ReturnVerificationPanel";
import { requireUser } from "@/lib/auth";
import { getFineRatePerDay } from "@/lib/services";
import { readDb } from "@/lib/storage";

export const dynamic = "force-dynamic";

export default async function StaffReturnShowPage({
  params,
}: {
  params: Promise<{ id: string }>;
}) {
  const user = await requireUser(["petugas"]);
  const { id } = await params;
  const db = await readDb();

  const ret = db.returns.find((r) => r.id === Number(id));
  if (!ret) notFound();
  const loan = db.loans.find((l) => l.id === ret.loan_id);
  if (!loan) notFound();

  const userById = new Map(db.users.map((u) => [u.id, u]));
  const eqById = new Map(db.equipments.map((e) => [e.id, e]));
  const borrower = userById.get(loan.user_id);

  const rds = db.return_details.filter((d) => d.return_id === ret.id);
  const items = db.loan_details
    .filter((d) => d.loan_id === loan.id)
    .map((ld) => {
      const eq = eqById.get(ld.equipment_id);
      const verified = rds.find((x) => x.loan_detail_id === ld.id);
      return {
        loan_detail_id: ld.id,
        equipment_id: ld.equipment_id,
        equipment_code: eq?.code ?? "-",
        equipment_name: eq?.name ?? "-",
        qty: ld.qty,
        condition_before: ld.condition_before,
        verified_condition_after: verified?.condition_after,
        verified_qty_damaged: verified?.qty_damaged,
        verified_damage_notes: verified?.damage_notes,
      };
    });

  return (
    <AppShell
      user={user}
      header={`Pemeriksaan Pengembalian — ${ret.code}`}
      subheader="Periksa kondisi fisik peralatan dan selesaikan verifikasi pengembalian."
    >
      <ReturnVerificationPanel
        backHref="/staff/returns"
        loanHref={`/staff/loans/${loan.id}`}
        returnData={{
          id: ret.id,
          code: ret.code,
          return_date: ret.return_date,
          late_days: ret.late_days,
          fine_rate:
            ret.fine_per_day_snapshot > 0
              ? ret.fine_per_day_snapshot
              : getFineRatePerDay(db),
          fine_amount: ret.fine_amount,
          fine_paid: ret.fine_paid,
          status: ret.status,
          notes: ret.notes,
          verified_at: ret.verified_at,
          verified_by_name: ret.verified_by
            ? userById.get(ret.verified_by)?.name ?? null
            : null,
          returned_by_name: userById.get(ret.returned_by)?.name ?? "-",
          loan_id: loan.id,
          loan_code: loan.code,
          loan_date: loan.loan_date,
          due_date: loan.due_date,
          borrower_name: borrower?.name ?? "-",
          borrower_identity: borrower?.identity_number ?? null,
        }}
        items={items}
      />
    </AppShell>
  );
}
