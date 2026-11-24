"use client";

import React, { useState, useTransition } from "react";
import { useRouter } from "next/navigation";
import { AlertTriangle, Eye, EyeOff, Info, Layers, LogIn } from "lucide-react";
import { loginServerAction } from "@/lib/actions";

export function LoginForm() {
  const router = useRouter();
  const [identity, setIdentity] = useState("");
  const [password, setPassword] = useState("");
  const [remember, setRemember] = useState(true);
  const [showPassword, setShowPassword] = useState(false);
  const [openDemo, setOpenDemo] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [isPending, startTransition] = useTransition();

  function handleSubmit(e: React.FormEvent) {
    e.preventDefault();
    setError(null);
    startTransition(async () => {
      const res = await loginServerAction(identity, password, remember);
      if (!res.ok) {
        setError(res.error || "Gagal masuk ke sistem.");
        return;
      }
      router.push(res.redirectTo || "/");
      router.refresh();
    });
  }

  return (
    <div className="mx-auto w-full max-w-sm">
      <div className="mb-8 flex items-center gap-2.5 lg:hidden">
        <div className="flex h-9 w-9 items-center justify-center rounded-lg bg-slate-900 text-white">
          <Layers className="h-5 w-5" />
        </div>
        <div>
          <span className="block text-base font-bold text-slate-900">
            GearHub
          </span>
          <span className="block text-xs text-slate-500">
            Sistem Peminjaman Alat Lab
          </span>
        </div>
      </div>

      <div>
        <h2 className="text-xl font-bold tracking-tight text-slate-900">
          Masuk ke Akun Anda
        </h2>
        <p className="mt-1.5 text-xs text-slate-500">
          Gunakan username atau email resmi yang terdaftar di sistem laboratorium.
        </p>
      </div>

      {error && (
        <div className="mt-5 flex items-start gap-2.5 rounded-md border border-rose-200 bg-rose-50 p-3 text-xs text-rose-800">
          <AlertTriangle className="mt-0.5 h-4 w-4 shrink-0 text-rose-600" />
          <span>{error}</span>
        </div>
      )}

      <form onSubmit={handleSubmit} className="mt-6 space-y-4">
        <div>
          <label htmlFor="identity" className="input-label">
            Username atau Email
          </label>
          <input
            id="identity"
            type="text"
            required
            autoFocus
            value={identity}
            onChange={(e) => setIdentity(e.target.value)}
            placeholder="Contoh: admin atau budi.santoso@siswa..."
            className="input-field"
          />
        </div>

        <div>
          <label htmlFor="password" className="input-label">
            Kata Sandi
          </label>
          <div className="relative">
            <input
              id="password"
              type={showPassword ? "text" : "password"}
              required
              value={password}
              onChange={(e) => setPassword(e.target.value)}
              placeholder="Masukkan kata sandi"
              className="input-field pr-10"
            />
            <button
              type="button"
              onClick={() => setShowPassword((v) => !v)}
              className="absolute inset-y-0 right-0 flex items-center px-3 text-slate-400 hover:text-slate-600"
              tabIndex={-1}
            >
              {showPassword ? (
                <EyeOff className="h-4 w-4" />
              ) : (
                <Eye className="h-4 w-4" />
              )}
            </button>
          </div>
        </div>

        <div className="flex items-center justify-between pt-1">
          <label className="inline-flex cursor-pointer items-center gap-2 text-xs text-slate-600">
            <input
              type="checkbox"
              checked={remember}
              onChange={(e) => setRemember(e.target.checked)}
              className="rounded border-slate-300 text-slate-900"
            />
            <span>Ingat sesi pada perangkat ini</span>
          </label>
        </div>

        <button
          type="submit"
          disabled={isPending}
          className="btn-primary w-full py-2.5 text-sm"
        >
          <LogIn className="h-4 w-4" />
          <span>
            {isPending ? "Memverifikasi Kredensial..." : "Masuk ke Sistem"}
          </span>
        </button>
      </form>

      {/* Quick Credential Selector for UKK Demo */}
      <div className="mt-8 rounded-lg border border-slate-200 bg-slate-50 p-3.5">
        <button
          type="button"
          onClick={() => setOpenDemo((v) => !v)}
          className="flex w-full items-center justify-between text-left text-xs font-semibold text-slate-700 cursor-pointer"
        >
          <span className="flex items-center gap-1.5">
            <Info className="h-3.5 w-3.5 text-blue-700" />
            <span>Akun Demo Pengujian Role (UKK)</span>
          </span>
          <span className="text-[11px] font-normal text-slate-500">
            {openDemo ? "Sembunyikan" : "Tampilkan"}
          </span>
        </button>

        {openDemo && (
          <>
            <div className="mt-3 grid grid-cols-3 gap-2">
              <button
                type="button"
                onClick={() => {
                  setIdentity("admin");
                  setPassword("password123");
                }}
                className="rounded border border-slate-200 bg-white px-2.5 py-2 text-left transition hover:border-blue-600 hover:bg-blue-50/40 cursor-pointer"
              >
                <span className="block text-xs font-bold text-slate-900">
                  Admin
                </span>
                <span className="block font-mono text-[10px] text-slate-500">
                  admin
                </span>
              </button>
              <button
                type="button"
                onClick={() => {
                  setIdentity("petugas");
                  setPassword("password123");
                }}
                className="rounded border border-slate-200 bg-white px-2.5 py-2 text-left transition hover:border-blue-600 hover:bg-blue-50/40 cursor-pointer"
              >
                <span className="block text-xs font-bold text-slate-900">
                  Petugas
                </span>
                <span className="block font-mono text-[10px] text-slate-500">
                  petugas
                </span>
              </button>
              <button
                type="button"
                onClick={() => {
                  setIdentity("peminjam");
                  setPassword("password123");
                }}
                className="rounded border border-slate-200 bg-white px-2.5 py-2 text-left transition hover:border-blue-600 hover:bg-blue-50/40 cursor-pointer"
              >
                <span className="block text-xs font-bold text-slate-900">
                  Peminjam
                </span>
                <span className="block font-mono text-[10px] text-slate-500">
                  peminjam
                </span>
              </button>
            </div>
            <p className="mt-2 text-[11px] text-slate-500">
              Klik salah satu role di atas untuk mengisi otomatis kredensial uji (
              <code className="font-mono">password123</code>).
            </p>
          </>
        )}
      </div>
    </div>
  );
}
