import { AppShell } from "@/components/AppShell";
import { EquipmentForm } from "@/components/EquipmentForm";
import { requireUser } from "@/lib/auth";
import { readDb } from "@/lib/storage";

export const dynamic = "force-dynamic";

export default async function NewEquipmentPage() {
  const user = await requireUser(["admin"]);
  const db = await readDb();

  return (
    <AppShell
      user={user}
      header="Tambah Peralatan Baru"
      subheader="Daftarkan unit peralatan baru ke inventaris laboratorium."
    >
      <EquipmentForm categories={db.categories} />
    </AppShell>
  );
}
