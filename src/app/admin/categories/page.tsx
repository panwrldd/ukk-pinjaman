import { AppShell } from "@/components/AppShell";
import { requireUser } from "@/lib/auth";
import { readDb } from "@/lib/storage";
import { CategoriesClient } from "./CategoriesClient";

export const dynamic = "force-dynamic";

export default async function AdminCategoriesPage() {
  const user = await requireUser(["admin"]);
  const db = await readDb();

  const rows = db.categories
    .map((cat) => {
      const eqs = db.equipments.filter((e) => e.category_id === cat.id);
      return {
        id: cat.id,
        code: cat.code,
        name: cat.name,
        description: cat.description,
        is_active: cat.is_active,
        equipment_count: eqs.length,
        unit_count: eqs.reduce((s, e) => s + e.qty_total, 0),
      };
    })
    .sort((a, b) => a.code.localeCompare(b.code));

  return (
    <AppShell
      user={user}
      header="Manajemen Kategori Alat"
      subheader="Klasifikasi peralatan multimedia, studio, dan laboratorium jaringan."
    >
      <CategoriesClient categories={rows} />
    </AppShell>
  );
}
