"use client";

import React, { useState, useTransition } from "react";
import Link from "next/link";
import { usePathname, useRouter } from "next/navigation";
import {
  Activity,
  Calendar,
  ClipboardCheck,
  ClipboardList,
  FileText,
  Layers,
  LayoutDashboard,
  LogOut,
  Menu,
  Package,
  Plus,
  RotateCcw,
  Settings,
  Tags,
  Users,
  X,
} from "lucide-react";
import { logoutServerAction } from "@/lib/actions";
import {
  Role,
  formatDateId,
  getInitials,
  getRoleHome,
  getRoleLabel,
  todayJakarta,
} from "@/lib/types";
import { StatusBadge } from "./StatusBadge";

export interface NavUser {
  id: number;
  name: string;
  username: string;
  role: Role;
}

interface AppShellProps {
  user: NavUser;
  header: string;
  subheader?: string;
  children: React.ReactNode;
}

export function AppShell({ user, header, subheader, children }: AppShellProps) {
  const pathname = usePathname();
  const router = useRouter();
  const [sidebarOpen, setSidebarOpen] = useState(false);
  const [isPending, startTransition] = useTransition();

  const isActive = (prefix: string, exact = false) => {
    if (exact) return pathname === prefix;
    return pathname === prefix || pathname.startsWith(`${prefix}/`);
  };

  const navClass = (active: boolean) =>
    `flex items-center gap-2.5 rounded-md px-3 py-2 text-xs font-medium transition ${
      active
        ? "bg-blue-800 text-white"
        : "text-slate-300 hover:bg-slate-800 hover:text-white"
    }`;

  function handleLogout() {
    startTransition(async () => {
      const res = await logoutServerAction();
      router.push(res.redirectTo || "/login");
      router.refresh();
    });
  }

  return (
    <div className="min-h-screen bg-slate-50 text-slate-900">
      {/* Mobile Backdrop */}
      {sidebarOpen && (
        <div
          onClick={() => setSidebarOpen(false)}
          className="no-print fixed inset-0 z-40 bg-slate-900/50 lg:hidden"
        />
      )}

      {/* Sidebar Navigation */}
      <aside
        className={`no-print fixed inset-y-0 left-0 z-50 flex w-64 flex-col bg-slate-900 text-slate-300 transition-transform duration-200 ease-out ${
          sidebarOpen ? "translate-x-0" : "-translate-x-full lg:translate-x-0"
        }`}
      >
        <div className="flex h-16 shrink-0 items-center justify-between border-b border-slate-800 px-5">
          <Link href={getRoleHome(user.role)} className="flex items-center gap-2.5">
            <div className="flex h-8 w-8 items-center justify-center rounded-md bg-blue-700 text-white">
              <Layers className="h-4 w-4" />
            </div>
            <div>
              <span className="block text-sm font-bold tracking-tight text-white">
                GearHub
              </span>
              <span className="block text-[10px] font-medium uppercase tracking-wider text-slate-400">
                Lab & Multimedia
              </span>
            </div>
          </Link>
          <button
            type="button"
            onClick={() => setSidebarOpen(false)}
            className="rounded p-1 text-slate-400 hover:text-white lg:hidden"
          >
            <X className="h-5 w-5" />
          </button>
        </div>

        <nav className="flex-1 space-y-5 overflow-y-auto px-3 py-5">
          {user.role === "admin" && (
            <>
              <div>
                <p className="px-3 text-[10px] font-bold uppercase tracking-widest text-slate-500">
                  Ikhtisar
                </p>
                <div className="mt-2 space-y-1">
                  <Link
                    href="/admin/dashboard"
                    className={navClass(isActive("/admin/dashboard", true))}
                  >
                    <LayoutDashboard className="h-4 w-4 shrink-0" />
                    <span>Dashboard Admin</span>
                  </Link>
                </div>
              </div>

              <div>
                <p className="px-3 text-[10px] font-bold uppercase tracking-widest text-slate-500">
                  Data Master
                </p>
                <div className="mt-2 space-y-1">
                  <Link
                    href="/admin/equipments"
                    className={navClass(isActive("/admin/equipments"))}
                  >
                    <Package className="h-4 w-4 shrink-0" />
                    <span>Inventaris Alat</span>
                  </Link>
                  <Link
                    href="/admin/categories"
                    className={navClass(isActive("/admin/categories"))}
                  >
                    <Tags className="h-4 w-4 shrink-0" />
                    <span>Kategori Alat</span>
                  </Link>
                  <Link
                    href="/admin/users"
                    className={navClass(isActive("/admin/users"))}
                  >
                    <Users className="h-4 w-4 shrink-0" />
                    <span>Manajemen Pengguna</span>
                  </Link>
                </div>
              </div>

              <div>
                <p className="px-3 text-[10px] font-bold uppercase tracking-widest text-slate-500">
                  Transaksi & Tata Kelola
                </p>
                <div className="mt-2 space-y-1">
                  <Link
                    href="/admin/loans"
                    className={navClass(isActive("/admin/loans"))}
                  >
                    <ClipboardList className="h-4 w-4 shrink-0" />
                    <span>Data Peminjaman</span>
                  </Link>
                  <Link
                    href="/admin/returns"
                    className={navClass(isActive("/admin/returns"))}
                  >
                    <RotateCcw className="h-4 w-4 shrink-0" />
                    <span>Data Pengembalian</span>
                  </Link>
                  <Link
                    href="/reports"
                    className={navClass(isActive("/reports"))}
                  >
                    <FileText className="h-4 w-4 shrink-0" />
                    <span>Pusat Laporan</span>
                  </Link>
                  <Link
                    href="/admin/activity-logs"
                    className={navClass(isActive("/admin/activity-logs"))}
                  >
                    <Activity className="h-4 w-4 shrink-0" />
                    <span>Log Aktivitas</span>
                  </Link>
                  <Link
                    href="/admin/settings"
                    className={navClass(isActive("/admin/settings"))}
                  >
                    <Settings className="h-4 w-4 shrink-0" />
                    <span>Pengaturan & Denda</span>
                  </Link>
                </div>
              </div>
            </>
          )}

          {user.role === "petugas" && (
            <>
              <div>
                <p className="px-3 text-[10px] font-bold uppercase tracking-widest text-slate-500">
                  Meja Operasional
                </p>
                <div className="mt-2 space-y-1">
                  <Link
                    href="/staff/dashboard"
                    className={navClass(isActive("/staff/dashboard", true))}
                  >
                    <LayoutDashboard className="h-4 w-4 shrink-0" />
                    <span>Dashboard Petugas</span>
                  </Link>
                  <Link
                    href="/staff/loans"
                    className={navClass(isActive("/staff/loans"))}
                  >
                    <ClipboardCheck className="h-4 w-4 shrink-0" />
                    <span>Persetujuan & Penyerahan</span>
                  </Link>
                  <Link
                    href="/staff/returns"
                    className={navClass(isActive("/staff/returns"))}
                  >
                    <RotateCcw className="h-4 w-4 shrink-0" />
                    <span>Verifikasi Pengembalian</span>
                  </Link>
                </div>
              </div>

              <div>
                <p className="px-3 text-[10px] font-bold uppercase tracking-widest text-slate-500">
                  Inventaris & Laporan
                </p>
                <div className="mt-2 space-y-1">
                  <Link
                    href="/staff/equipments"
                    className={navClass(isActive("/staff/equipments"))}
                  >
                    <Package className="h-4 w-4 shrink-0" />
                    <span>Kondisi & Stok Alat</span>
                  </Link>
                  <Link
                    href="/reports"
                    className={navClass(isActive("/reports"))}
                  >
                    <FileText className="h-4 w-4 shrink-0" />
                    <span>Laporan & Cetak</span>
                  </Link>
                </div>
              </div>
            </>
          )}

          {user.role === "peminjam" && (
            <div>
              <p className="px-3 text-[10px] font-bold uppercase tracking-widest text-slate-500">
                Menu Peminjam
              </p>
              <div className="mt-2 space-y-1">
                <Link
                  href="/borrower/dashboard"
                  className={navClass(isActive("/borrower/dashboard", true))}
                >
                  <LayoutDashboard className="h-4 w-4 shrink-0" />
                  <span>Ringkasan Saya</span>
                </Link>
                <Link
                  href="/borrower/catalog"
                  className={navClass(isActive("/borrower/catalog"))}
                >
                  <Package className="h-4 w-4 shrink-0" />
                  <span>Katalog Peralatan</span>
                </Link>
                <Link
                  href="/borrower/loans/create"
                  className={navClass(isActive("/borrower/loans/create", true))}
                >
                  <Plus className="h-4 w-4 shrink-0" />
                  <span>Ajukan Peminjaman</span>
                </Link>
                <Link
                  href="/borrower/loans"
                  className={navClass(
                    pathname === "/borrower/loans" ||
                      (pathname.startsWith("/borrower/loans/") &&
                        pathname !== "/borrower/loans/create")
                  )}
                >
                  <ClipboardList className="h-4 w-4 shrink-0" />
                  <span>Pinjaman & Riwayat</span>
                </Link>
              </div>
            </div>
          )}
        </nav>

        <div className="border-t border-slate-800 p-4">
          <div className="flex items-center gap-3">
            <div className="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-slate-800 text-xs font-bold text-white ring-1 ring-slate-700">
              {getInitials(user.name)}
            </div>
            <div className="min-w-0 flex-1">
              <p className="truncate text-xs font-semibold text-white">
                {user.name}
              </p>
              <p className="truncate text-[11px] text-slate-400">
                {getRoleLabel(user.role)} · @{user.username}
              </p>
            </div>
          </div>
          <button
            type="button"
            disabled={isPending}
            onClick={handleLogout}
            className="mt-3 flex w-full items-center justify-center gap-2 rounded-md border border-slate-700 bg-slate-800/80 px-3 py-1.5 text-xs font-medium text-slate-200 transition hover:border-rose-600 hover:bg-rose-600 hover:text-white cursor-pointer"
          >
            <LogOut className="h-3.5 w-3.5" />
            <span>{isPending ? "Keluar..." : "Keluar Sistem"}</span>
          </button>
        </div>
      </aside>

      {/* Main Content Column */}
      <div className="flex min-h-screen flex-col lg:pl-64">
        <header className="no-print sticky top-0 z-30 flex h-16 shrink-0 items-center justify-between border-b border-slate-200 bg-white/95 px-4 backdrop-blur sm:px-6 lg:px-8">
          <div className="flex items-center gap-3">
            <button
              type="button"
              onClick={() => setSidebarOpen(true)}
              className="rounded-md border border-slate-200 p-2 text-slate-600 hover:bg-slate-100 lg:hidden"
            >
              <Menu className="h-4 w-4" />
            </button>
            <div>
              <h1 className="text-sm font-bold text-slate-900 sm:text-base">
                {header}
              </h1>
              {subheader && (
                <p className="hidden text-xs text-slate-500 sm:block">
                  {subheader}
                </p>
              )}
            </div>
          </div>

          <div className="flex items-center gap-3">
            <div className="hidden items-center gap-1.5 rounded-md border border-slate-200 bg-slate-50 px-2.5 py-1 text-xs text-slate-600 md:flex">
              <Calendar className="h-3.5 w-3.5 text-slate-400" />
              <span>{formatDateId(todayJakarta())}</span>
            </div>
            <StatusBadge
              variant={user.role}
              label={getRoleLabel(user.role)}
            />
          </div>
        </header>

        <main className="flex-1 px-4 py-6 sm:px-6 lg:px-8">{children}</main>

        <footer className="no-print border-t border-slate-200 bg-white px-4 py-3 text-xs text-slate-500 sm:px-6 lg:px-8">
          <div className="flex flex-wrap items-center justify-between gap-2">
            <span>
              &copy; {new Date().getFullYear()} GearHub — Sistem Manajemen
              Peminjaman Peralatan Multimedia & Laboratorium
            </span>
            <span className="font-mono text-[11px] text-slate-400">
              UKK RPL · Paket 1 (Next.js Edition)
            </span>
          </div>
        </footer>
      </div>
    </div>
  );
}
