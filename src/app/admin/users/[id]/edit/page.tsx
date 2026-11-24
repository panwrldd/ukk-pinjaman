import { notFound } from "next/navigation";
import { AppShell } from "@/components/AppShell";
import { UserForm } from "@/components/UserForm";
import { requireUser } from "@/lib/auth";
import { readDb } from "@/lib/storage";

export const dynamic = "force-dynamic";

export default async function EditUserPage({
  params,
}: {
  params: Promise<{ id: string }>;
}) {
  const user = await requireUser(["admin"]);
  const { id } = await params;
  const db = await readDb();
  const target = db.users.find((u) => u.id === Number(id));
  if (!target) notFound();

  return (
    <AppShell
      user={user}
      header={`Edit Pengguna — @${target.username}`}
      subheader={target.name}
    >
      <UserForm initial={target} />
    </AppShell>
  );
}
