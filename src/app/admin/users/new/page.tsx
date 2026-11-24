import { AppShell } from "@/components/AppShell";
import { UserForm } from "@/components/UserForm";
import { requireUser } from "@/lib/auth";

export const dynamic = "force-dynamic";

export default async function NewUserPage() {
  const user = await requireUser(["admin"]);
  return (
    <AppShell
      user={user}
      header="Tambah Pengguna Baru"
      subheader="Buat akun baru untuk Administrator, Petugas Lab, atau Peminjam."
    >
      <UserForm />
    </AppShell>
  );
}
