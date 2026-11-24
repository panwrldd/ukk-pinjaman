import { AppShell } from "@/components/AppShell";
import { requireUser } from "@/lib/auth";
import { readDb } from "@/lib/storage";
import { SettingsForm } from "./SettingsForm";

export const dynamic = "force-dynamic";

export default async function AdminSettingsPage() {
  const user = await requireUser(["admin"]);
  const db = await readDb();

  return (
    <AppShell
      user={user}
      header="Pengaturan Sistem & Tarif Denda"
      subheader="Konfigurasi denda keterlambatan harian, batas peminjaman, dan identitas kop laporan resmi."
    >
      <SettingsForm settings={db.settings} />
    </AppShell>
  );
}
