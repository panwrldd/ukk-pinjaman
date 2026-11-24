import { AppShell } from "@/components/AppShell";
import { requireUser } from "@/lib/auth";
import { getFineRatePerDay, getSettingValue } from "@/lib/services";
import { readDb } from "@/lib/storage";
import { addDaysToDateStr, formatRupiah, todayJakarta } from "@/lib/types";
import { CreateLoanForm } from "./CreateLoanForm";

export const dynamic = "force-dynamic";

export default async function BorrowerCreateLoanPage({
  searchParams,
}: {
  searchParams: Promise<Record<string, string | undefined>>;
}) {
  const user = await requireUser(["peminjam"]);
  const sp = await searchParams;
  const db = await readDb();

  const activeCatMap = new Map(
    db.categories.filter((c) => c.is_active).map((c) => [c.id, c.name])
  );

  const availableEquipments = db.equipments
    .filter(
      (e) =>
        e.status === "tersedia" &&
        e.qty_available > 0 &&
        e.condition !== "rusak_berat" &&
        activeCatMap.has(e.category_id)
    )
    .sort((a, b) => a.code.localeCompare(b.code))
    .map((e) => ({
      id: e.id,
      code: e.code,
      name: e.name,
      category_name: activeCatMap.get(e.category_id) ?? "-",
      qty_available: e.qty_available,
    }));

  const today = todayJakarta();
  const maxLoanDays = Number(getSettingValue(db, "max_loan_days", "7"));
  const fineRate = getFineRatePerDay(db);

  return (
    <AppShell
      user={user}
      header="Formulir Pengajuan Peminjaman Alat"
      subheader="Pilih peralatan yang tersedia, tentukan jadwal peminjaman, dan tuliskan keperluan kegiatan."
    >
      <CreateLoanForm
        equipments={availableEquipments}
        defaultEquipmentId={
          sp.equipment_id ? Number(sp.equipment_id) : undefined
        }
        defaultLoanDate={today}
        defaultDueDate={addDaysToDateStr(today, 2)}
        maxLoanDays={maxLoanDays}
        fineRateFormatted={formatRupiah(fineRate)}
      />
    </AppShell>
  );
}
