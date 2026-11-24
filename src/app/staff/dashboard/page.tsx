import { AppShell } from "@/components/AppShell";
import { requireUser } from "@/lib/auth";
import { readDb } from "@/lib/storage";
import { countLateDays, todayJakarta } from "@/lib/types";
import { StaffQueueTabs } from "./StaffQueueTabs";

export const dynamic = "force-dynamic";

export default async function StaffDashboardPage() {
  const user = await requireUser(["petugas"]);
  const db = await readDb();
  const today = todayJakarta();

  const userById = new Map(db.users.map((u) => [u.id, u]));
  const eqById = new Map(db.equipments.map((e) => [e.id, e]));
  const loanById = new Map(db.loans.map((l) => [l.id, l]));

  const mapLoan = (l: (typeof db.loans)[number]) => {
    const b = userById.get(l.user_id);
    const itemsSummary = db.loan_details
      .filter((d) => d.loan_id === l.id)
      .map((d) => `${eqById.get(d.equipment_id)?.name ?? "-"} (${d.qty} unit)`)
      .join(", ");
    return {
      id: l.id,
      code: l.code,
      borrower_name: b?.name ?? "-",
      borrower_phone: b?.phone ?? null,
      loan_date: l.loan_date,
      due_date: l.due_date,
      purpose: l.purpose,
      items_summary: itemsSummary,
      late_days: countLateDays(l.due_date, today),
    };
  };

  const pendingLoans = db.loans
    .filter((l) => l.status === "pending")
    .sort((a, b) => a.id - b.id)
    .map(mapLoan);

  const readyHandoverLoans = db.loans
    .filter((l) => l.status === "approved")
    .sort((a, b) => a.id - b.id)
    .map(mapLoan);

  const overdueLoans = db.loans
    .filter((l) => l.status === "overdue")
    .sort((a, b) => a.due_date.localeCompare(b.due_date))
    .map(mapLoan);

  const pendingReturns = db.returns
    .filter((r) => r.status === "pending_verification")
    .sort((a, b) => a.id - b.id)
    .map((r) => {
      const l = loanById.get(r.loan_id);
      return {
        id: r.id,
        code: r.code,
        loan_code: l?.code ?? "-",
        borrower_name: userById.get(r.returned_by)?.name ?? "-",
        due_date: l?.due_date ?? "",
        return_date: r.return_date,
      };
    });

  const currentlyBorrowed = db.loans.filter((l) =>
    ["borrowed", "overdue"].includes(l.status)
  ).length;
  const damagedUnits = db.equipments.reduce((s, e) => s + e.qty_damaged, 0);

  const recentActivities = [...db.activity_logs]
    .sort((a, b) => b.id - a.id)
    .slice(0, 8)
    .map((act) => ({
      id: act.id,
      actor_name: act.user_id
        ? userById.get(act.user_id)?.name ?? "Pengguna"
        : "Audit Sistem",
      description: act.description,
      created_at: act.created_at,
    }));

  return (
    <AppShell
      user={user}
      header="Meja Operasional Petugas Laboratorium"
      subheader="Antrian verifikasi pengajuan peminjaman, penyerahan fisik alat, dan pemeriksaan pengembalian."
    >
      <StaffQueueTabs
        metrics={{
          pending_approvals: pendingLoans.length,
          ready_for_handover: readyHandoverLoans.length,
          pending_returns: pendingReturns.length,
          currently_borrowed: currentlyBorrowed,
          overdue_count: overdueLoans.length,
          damaged_units: damagedUnits,
        }}
        pendingLoans={pendingLoans}
        readyHandoverLoans={readyHandoverLoans}
        pendingReturns={pendingReturns}
        overdueLoans={overdueLoans}
        recentActivities={recentActivities}
      />
    </AppShell>
  );
}
