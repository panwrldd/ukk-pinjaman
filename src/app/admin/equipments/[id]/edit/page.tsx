import { notFound } from "next/navigation";
import { AppShell } from "@/components/AppShell";
import { EquipmentForm } from "@/components/EquipmentForm";
import { requireUser } from "@/lib/auth";
import { readDb } from "@/lib/storage";

export const dynamic = "force-dynamic";

export default async function EditEquipmentPage({
  params,
}: {
  params: Promise<{ id: string }>;
}) {
  const user = await requireUser(["admin"]);
  const { id } = await params;
  const db = await readDb();
  const eq = db.equipments.find((e) => e.id === Number(id));
  if (!eq) notFound();

  return (
    <AppShell
      user={user}
      header={`Edit Peralatan — ${eq.code}`}
      subheader={eq.name}
    >
      <EquipmentForm categories={db.categories} initial={eq} />
    </AppShell>
  );
}
