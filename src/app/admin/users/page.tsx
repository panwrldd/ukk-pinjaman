import Link from "next/link";
import { UserPlus } from "lucide-react";
import { AppShell } from "@/components/AppShell";
import { StatusBadge } from "@/components/StatusBadge";
import { requireUser } from "@/lib/auth";
import { readDb } from "@/lib/storage";
import { getRoleLabel } from "@/lib/types";
import { UserRowActions } from "./UserRowActions";

export const dynamic = "force-dynamic";

export default async function AdminUsersPage({
  searchParams,
}: {
  searchParams: Promise<Record<string, string | undefined>>;
}) {
  const user = await requireUser(["admin"]);
  const sp = await searchParams;
  const db = await readDb();

  const q = (sp.q || "").trim().toLowerCase();
  const role = sp.role || "";

  const users = db.users
    .filter((u) => {
      if (role && u.role !== role) return false;
      if (q) {
        const hay = `${u.name} ${u.username} ${u.email} ${
          u.identity_number ?? ""
        }`.toLowerCase();
        if (!hay.includes(q)) return false;
      }
      return true;
    })
    .sort((a, b) => a.role.localeCompare(b.role) || a.name.localeCompare(b.name));

  return (
    <AppShell
      user={user}
      header="Manajemen Akun Pengguna"
      subheader="Kelola hak akses Administrator, Petugas Laboratorium, dan Peminjam (Siswa/Guru)."
    >
      <div className="panel mb-6 p-4">
        <form
          method="GET"
          action="/admin/users"
          className="flex flex-wrap items-end justify-between gap-3"
        >
          <div className="grid flex-1 grid-cols-1 gap-3 sm:grid-cols-2">
            <div>
              <label className="input-label">Cari Nama / Username / NIS / NIP</label>
              <input
                type="text"
                name="q"
                defaultValue={sp.q || ""}
                placeholder="Ketik kata kunci..."
                className="input-field"
              />
            </div>
            <div>
              <label className="input-label">Filter Role</label>
              <select name="role" defaultValue={role} className="input-field">
                <option value="">Semua Role</option>
                <option value="admin">Administrator</option>
                <option value="petugas">Petugas Lab</option>
                <option value="peminjam">Peminjam</option>
              </select>
            </div>
          </div>
          <div className="flex items-center gap-2">
            <button type="submit" className="btn-secondary">
              Filter
            </button>
            <Link href="/admin/users/new" className="btn-primary">
              <UserPlus className="h-4 w-4" />
              <span>Tambah Pengguna</span>
            </Link>
          </div>
        </form>
      </div>

      <div className="panel">
        <div className="table-container">
          <table className="table-pro">
            <thead>
              <tr>
                <th>Nama & Unit/Kelas</th>
                <th>Username & Email</th>
                <th>NIS / NIP</th>
                <th>Role</th>
                <th>Status</th>
                <th className="text-right">Aksi</th>
              </tr>
            </thead>
            <tbody>
              {users.map((u) => (
                <tr key={u.id}>
                  <td>
                    <p className="font-semibold text-slate-900">{u.name}</p>
                    <p className="text-xs text-slate-500">{u.address || "-"}</p>
                  </td>
                  <td className="text-xs">
                    <span className="font-mono font-semibold text-slate-800">
                      @{u.username}
                    </span>
                    <p className="text-slate-500">{u.email}</p>
                  </td>
                  <td className="font-mono text-xs">
                    {u.identity_number || "-"}
                  </td>
                  <td>
                    <StatusBadge
                      variant={u.role}
                      label={getRoleLabel(u.role)}
                    />
                  </td>
                  <td>
                    <StatusBadge
                      variant={u.is_active ? "active" : "slate"}
                      label={u.is_active ? "Aktif" : "Nonaktif"}
                    />
                  </td>
                  <td className="text-right">
                    <UserRowActions
                      targetId={u.id}
                      currentUserId={user.id}
                      isActive={u.is_active}
                      username={u.username}
                    />
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      </div>
    </AppShell>
  );
}
