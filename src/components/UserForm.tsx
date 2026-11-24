"use client";

import React, { useState, useTransition } from "react";
import Link from "next/link";
import { useRouter } from "next/navigation";
import { AlertTriangle, ArrowLeft, Save } from "lucide-react";
import { saveUserServerAction } from "@/lib/actions";
import { Role, User } from "@/lib/types";

export function UserForm({ initial }: { initial?: User }) {
  const router = useRouter();
  const [isPending, startTransition] = useTransition();
  const [error, setError] = useState<string | null>(null);

  const [name, setName] = useState(initial?.name ?? "");
  const [username, setUsername] = useState(initial?.username ?? "");
  const [email, setEmail] = useState(initial?.email ?? "");
  const [identityNumber, setIdentityNumber] = useState(
    initial?.identity_number ?? ""
  );
  const [password, setPassword] = useState("");
  const [role, setRole] = useState<Role>(initial?.role ?? "peminjam");
  const [phone, setPhone] = useState(initial?.phone ?? "");
  const [address, setAddress] = useState(initial?.address ?? "");
  const [isActive, setIsActive] = useState(initial?.is_active ?? true);

  function handleSubmit(e: React.FormEvent) {
    e.preventDefault();
    setError(null);
    startTransition(async () => {
      const res = await saveUserServerAction({
        id: initial?.id,
        name,
        username,
        email,
        identity_number: identityNumber,
        password: password || undefined,
        role,
        phone,
        address,
        is_active: isActive,
      });
      if (!res.ok) {
        setError(res.error || "Gagal menyimpan pengguna.");
        return;
      }
      router.push(res.redirectTo || "/admin/users");
      router.refresh();
    });
  }

  return (
    <div className="mx-auto max-w-2xl space-y-5">
      <Link href="/admin/users" className="btn-secondary btn-xs">
        <ArrowLeft className="h-3.5 w-3.5" />
        <span>Kembali ke Daftar Pengguna</span>
      </Link>

      {error && (
        <div className="flex items-center gap-2 rounded-lg border border-rose-200 bg-rose-50 p-3.5 text-xs text-rose-800">
          <AlertTriangle className="h-4 w-4 shrink-0 text-rose-600" />
          <span>{error}</span>
        </div>
      )}

      <form onSubmit={handleSubmit} className="panel p-6 space-y-4">
        <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
          <div>
            <label className="input-label">Nama Lengkap</label>
            <input
              type="text"
              required
              value={name}
              onChange={(e) => setName(e.target.value)}
              className="input-field"
            />
          </div>
          <div>
            <label className="input-label">Role Pengguna</label>
            <select
              value={role}
              onChange={(e) => setRole(e.target.value as Role)}
              className="input-field"
            >
              <option value="peminjam">Peminjam (Siswa / Guru)</option>
              <option value="petugas">Petugas Laboratorium</option>
              <option value="admin">Administrator</option>
            </select>
          </div>
        </div>

        <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
          <div>
            <label className="input-label">Username</label>
            <input
              type="text"
              required
              value={username}
              onChange={(e) => setUsername(e.target.value)}
              className="input-field font-mono"
            />
          </div>
          <div>
            <label className="input-label">Email Resmi</label>
            <input
              type="email"
              required
              value={email}
              onChange={(e) => setEmail(e.target.value)}
              className="input-field"
            />
          </div>
        </div>

        <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
          <div>
            <label className="input-label">NIS / NIP / Nomor Identitas</label>
            <input
              type="text"
              value={identityNumber}
              onChange={(e) => setIdentityNumber(e.target.value)}
              className="input-field font-mono"
            />
          </div>
          <div>
            <label className="input-label">Nomor Telepon / WhatsApp</label>
            <input
              type="text"
              value={phone}
              onChange={(e) => setPhone(e.target.value)}
              className="input-field"
            />
          </div>
        </div>

        <div>
          <label className="input-label">Kelas / Jabatan / Unit</label>
          <input
            type="text"
            value={address}
            onChange={(e) => setAddress(e.target.value)}
            placeholder="Contoh: Kelas XII RPL 1"
            className="input-field"
          />
        </div>

        <div>
          <label className="input-label">
            {initial
              ? "Kata Sandi Baru (Kosongkan jika tidak diubah)"
              : "Kata Sandi Akun"}
          </label>
          <input
            type="password"
            required={!initial}
            minLength={6}
            value={password}
            onChange={(e) => setPassword(e.target.value)}
            placeholder="Minimal 6 karakter"
            className="input-field"
          />
        </div>

        <label className="inline-flex items-center gap-2 text-xs font-medium text-slate-700 cursor-pointer">
          <input
            type="checkbox"
            checked={isActive}
            onChange={(e) => setIsActive(e.target.checked)}
            className="rounded border-slate-300"
          />
          <span>Akun Aktif (Dapat Masuk ke Sistem)</span>
        </label>

        <div className="flex justify-end gap-2 border-t border-slate-100 pt-4">
          <Link href="/admin/users" className="btn-secondary">
            Batal
          </Link>
          <button type="submit" disabled={isPending} className="btn-primary">
            <Save className="h-4 w-4" />
            <span>{isPending ? "Menyimpan..." : "Simpan Pengguna"}</span>
          </button>
        </div>
      </form>
    </div>
  );
}
