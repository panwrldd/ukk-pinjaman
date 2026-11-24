import { redirect } from "next/navigation";
import { FileText, Layers, Package, ShieldCheck } from "lucide-react";
import { getCurrentUser } from "@/lib/auth";
import { getRoleHome } from "@/lib/types";
import { LoginForm } from "./LoginForm";

export const dynamic = "force-dynamic";

export default async function LoginPage() {
  const user = await getCurrentUser();
  if (user) {
    redirect(getRoleHome(user.role));
  }

  return (
    <div className="flex min-h-screen bg-slate-100 text-slate-900">
      {/* Left Institutional Panel */}
      <div className="hidden w-1/2 flex-col justify-between bg-slate-900 p-12 text-white lg:flex">
        <div>
          <div className="flex items-center gap-3">
            <div className="flex h-10 w-10 items-center justify-center rounded-lg bg-blue-700 text-white">
              <Layers className="h-5 w-5" />
            </div>
            <div>
              <span className="block text-lg font-bold tracking-tight">
                GearHub
              </span>
              <span className="block text-xs text-slate-400">
                Inventory & Laboratory Loan Management
              </span>
            </div>
          </div>

          <div className="mt-14 max-w-md">
            <h1 className="text-2xl font-bold leading-snug text-white">
              Tata Kelola Inventaris Multimedia & Laboratorium Secara Terukur
            </h1>
            <p className="mt-3 text-sm leading-relaxed text-slate-300">
              Platform terpusat untuk pencatatan ketersediaan alat, pengajuan
              peminjaman berjenjang, pemeriksaan kondisi fisik pengembalian,
              perhitungan denda otomatis, serta pelaporan audit laboratorium.
            </p>
          </div>

          <div className="mt-10 max-w-md space-y-5">
            <div className="flex items-start gap-3.5">
              <div className="mt-0.5 flex h-8 w-8 shrink-0 items-center justify-center rounded-md border border-slate-700 bg-slate-800 text-blue-400">
                <Package className="h-4 w-4" />
              </div>
              <div>
                <h3 className="text-xs font-semibold uppercase tracking-wider text-white">
                  Akurasi Stok Real-Time
                </h3>
                <p className="mt-1 text-xs leading-relaxed text-slate-400">
                  Pemisahan otomatis antara stok tersedia, sedang dipinjam, dan
                  unit rusak berat dalam perbaikan.
                </p>
              </div>
            </div>

            <div className="flex items-start gap-3.5">
              <div className="mt-0.5 flex h-8 w-8 shrink-0 items-center justify-center rounded-md border border-slate-700 bg-slate-800 text-blue-400">
                <ShieldCheck className="h-4 w-4" />
              </div>
              <div>
                <h3 className="text-xs font-semibold uppercase tracking-wider text-white">
                  Verifikasi Berjenjang & Audit Sistem
                </h3>
                <p className="mt-1 text-xs leading-relaxed text-slate-400">
                  Dilengkapi validasi integritas stok, perhitungan denda harian
                  otomatis, dan rekam jejak audit setiap transaksi.
                </p>
              </div>
            </div>

            <div className="flex items-start gap-3.5">
              <div className="mt-0.5 flex h-8 w-8 shrink-0 items-center justify-center rounded-md border border-slate-700 bg-slate-800 text-blue-400">
                <FileText className="h-4 w-4" />
              </div>
              <div>
                <h3 className="text-xs font-semibold uppercase tracking-wider text-white">
                  Pelaporan Resmi Siap Cetak
                </h3>
                <p className="mt-1 text-xs leading-relaxed text-slate-400">
                  Ekspor laporan inventaris, peminjaman, keterlambatan, dan
                  denda ke format cetak PDF dan Excel (.xlsx).
                </p>
              </div>
            </div>
          </div>
        </div>

        <div className="border-t border-slate-800 pt-6 text-xs text-slate-400">
          Unit Pelaksana Teknis Laboratorium RPL & Multimedia
        </div>
      </div>

      {/* Right Login Form Panel */}
      <div className="flex flex-1 flex-col justify-center bg-white px-6 py-12 sm:px-12 lg:px-16">
        <LoginForm />
      </div>
    </div>
  );
}
