import Link from "next/link";
import { AlertTriangle, ArrowRight, Plus } from "lucide-react";
import { AppShell } from "@/components/AppShell";
import { EmptyState } from "@/components/EmptyState";
import { StatusBadge } from "@/components/StatusBadge";
import { requireUser } from "@/lib/auth";
import { getFineRatePerDay } from "@/lib/services";
import { readDb } from "@/lib/storage";
import {
  countLateDays,
  formatDateId,
  formatRupiah,
  getLoanStatusLabel,
  todayJakarta,
} from "@/lib/types";

export const dynamic = "force-dynamic";

export default async function BorrowerDashboardPage() {
  const user = await requireUser(["peminjam"]);
  const db = await readDb();
  const today = todayJakarta();
  const fineRate = getFineRatePerDay(db);

  const eqById = new Map(db.equipments.map((e) => [e.id, e]));
  const catById = new Map(db.categories.map((c) => [c.id, c]));

  const myLoans = db.loans
    .filter((l) => l.user_id === user.id)
    .sort((a, b) => b.id - a.id);

  const overdueCount = myLoans.filter((l) => l.status === "overdue").length;
  const activeBorrowed = myLoans.filter((l) =>
    ["borrowed", "overdue"].includes(l.status)
  ).length;
  const approvedReady = myLoans.filter((l) => l.status === "approved").length;
  const pendingRequests = myLoans.filter((l) => l.status === "pending").length;
  const completedLoans = myLoans.filter((l) => l.status === "returned").length;

  const activeSchedule = myLoans.filter((l) =>
    ["pending", "approved", "borrowed", "overdue"].includes(l.status)
  );
  const recentHistory = myLoans.slice(0, 5);

  const featuredEquipments = db.equipments
    .filter((e) => e.status === "tersedia" && e.qty_available > 0)
    .slice(0, 6);

  return (
    <AppShell
      user={user}
      header="Portal Peminjaman Peralatan Saya"
      subheader="Pantau jadwal pengembalian aktif, status pengajuan, dan telusuri peralatan laboratorium yang siap dipinjam."
    >
      {overdueCount > 0 && (
        <div className="mb-6 flex flex-wrap items-center justify-between gap-3 rounded-lg border border-rose-300 bg-rose-50 px-5 py-4 text-xs text-rose-900">
          <div className="flex items-start gap-3">
            <AlertTriangle className="mt-0.5 h-5 w-5 shrink-0 text-rose-600" />
            <div>
              <p className="font-bold">
                Perhatian: Anda Memiliki {overdueCount} Peminjaman yang Melewati
                Batas Waktu!
              </p>
              <p className="mt-0.5 text-rose-800">
                Segera ajukan pengembalian dan serahkan alat ke loket
                laboratorium. Tarif denda keterlambatan berlaku{" "}
                {formatRupiah(fineRate)}/hari.
              </p>
            </div>
          </div>
          <Link
            href="/borrower/loans?status=overdue"
            className="btn-danger btn-xs"
          >
            Lihat Pinjaman Terlambat
          </Link>
        </div>
      )}

      {/* Personal Status Summary */}
      <div className="grid grid-cols-2 gap-4 lg:grid-cols-4">
        <div className="panel p-4">
          <span className="text-[11px] font-semibold uppercase tracking-wider text-indigo-700">
            Sedang Saya Pinjam
          </span>
          <p className="mt-1.5 text-2xl font-bold text-slate-900">
            {activeBorrowed}
          </p>
          <p className="mt-0.5 text-xs text-slate-500">
            Wajib dijaga kondisinya
          </p>
        </div>
        <div className="panel p-4">
          <span className="text-[11px] font-semibold uppercase tracking-wider text-blue-700">
            Disetujui (Siap Diambil)
          </span>
          <p className="mt-1.5 text-2xl font-bold text-slate-900">
            {approvedReady}
          </p>
          <p className="mt-0.5 text-xs text-slate-500">
            Ambil di loket petugas
          </p>
        </div>
        <div className="panel p-4">
          <span className="text-[11px] font-semibold uppercase tracking-wider text-amber-700">
            Menunggu Verifikasi
          </span>
          <p className="mt-1.5 text-2xl font-bold text-slate-900">
            {pendingRequests}
          </p>
          <p className="mt-0.5 text-xs text-slate-500">
            Sedang diperiksa petugas
          </p>
        </div>
        <div className="panel p-4">
          <span className="text-[11px] font-semibold uppercase tracking-wider text-emerald-700">
            Selesai Dikembalikan
          </span>
          <p className="mt-1.5 text-2xl font-bold text-slate-900">
            {completedLoans}
          </p>
          <p className="mt-0.5 text-xs text-slate-500">
            Riwayat peminjaman tuntas
          </p>
        </div>
      </div>

      <div className="mt-6 grid grid-cols-1 gap-6 lg:grid-cols-12">
        <div className="panel lg:col-span-7">
          <div className="panel-header">
            <div>
              <h2 className="panel-title">
                Jadwal Pinjaman Aktif & Batas Pengembalian
              </h2>
              <p className="text-xs text-slate-500">
                Pastikan pengembalian dilakukan sebelum atau tepat pada tanggal
                jatuh tempo.
              </p>
            </div>
            <Link href="/borrower/loans/create" className="btn-primary btn-xs">
              <Plus className="h-3.5 w-3.5" />
              <span>Pinjam Alat</span>
            </Link>
          </div>

          {activeSchedule.length === 0 ? (
            <EmptyState
              title="Tidak Ada Pinjaman Aktif"
              description="Anda sedang tidak meminjam peralatan saat ini. Silakan telusuri katalog untuk mengajukan peminjaman."
            >
              <Link href="/borrower/catalog" className="btn-primary btn-xs">
                Jelajahi Katalog Alat
              </Link>
            </EmptyState>
          ) : (
            <div className="divide-y divide-slate-100">
              {activeSchedule.map((loan) => {
                const items = db.loan_details
                  .filter((d) => d.loan_id === loan.id)
                  .map(
                    (d) =>
                      `${eqById.get(d.equipment_id)?.name ?? "-"} (${
                        d.qty
                      } unit)`
                  )
                  .join(", ");
                const ret = db.returns.find((r) => r.loan_id === loan.id);
                const lateDays =
                  ["borrowed", "overdue"].includes(loan.status) &&
                  loan.due_date < today
                    ? countLateDays(loan.due_date, today)
                    : 0;

                return (
                  <div
                    key={loan.id}
                    className="flex flex-wrap items-center justify-between gap-4 p-4"
                  >
                    <div className="min-w-0 flex-1">
                      <div className="flex flex-wrap items-center gap-2">
                        <Link
                          href={`/borrower/loans/${loan.id}`}
                          className="font-mono text-xs font-bold text-blue-800 hover:underline"
                        >
                          {loan.code}
                        </Link>
                        <StatusBadge
                          variant={loan.status}
                          label={getLoanStatusLabel(loan.status)}
                        />
                      </div>
                      <p className="mt-1 text-xs font-semibold text-slate-900">
                        {items}
                      </p>
                      <p className="mt-0.5 text-xs text-slate-500">
                        Batas Pengembalian:{" "}
                        <strong
                          className={
                            lateDays > 0 ? "text-rose-700" : "text-slate-800"
                          }
                        >
                          {formatDateId(loan.due_date)}
                        </strong>{" "}
                        {lateDays > 0 && (
                          <span className="font-semibold text-rose-600">
                            ({lateDays} hari terlambat)
                          </span>
                        )}
                      </p>
                    </div>
                    <div className="flex items-center gap-2">
                      {["borrowed", "overdue"].includes(loan.status) && !ret ? (
                        <Link
                          href={`/borrower/loans/${loan.id}`}
                          className="btn-primary btn-xs"
                        >
                          Ajukan Kembali
                        </Link>
                      ) : ret ? (
                        <StatusBadge
                          variant="amber"
                          label="Menunggu Cek Petugas"
                        />
                      ) : (
                        <Link
                          href={`/borrower/loans/${loan.id}`}
                          className="btn-secondary btn-xs"
                        >
                          Detail
                        </Link>
                      )}
                    </div>
                  </div>
                );
              })}
            </div>
          )}
        </div>

        <div className="panel lg:col-span-5">
          <div className="panel-header">
            <h2 className="panel-title">Riwayat Pengajuan Terakhir</h2>
            <Link
              href="/borrower/loans"
              className="text-xs font-semibold text-blue-800 hover:underline"
            >
              Semua Riwayat
            </Link>
          </div>
          {recentHistory.length === 0 ? (
            <EmptyState
              title="Belum Ada Riwayat"
              description="Pengajuan peminjaman Anda akan tercatat di sini."
            />
          ) : (
            <div className="divide-y divide-slate-100">
              {recentHistory.map((hist) => (
                <Link
                  key={hist.id}
                  href={`/borrower/loans/${hist.id}`}
                  className="flex items-center justify-between gap-3 px-4 py-3 transition hover:bg-slate-50"
                >
                  <div className="min-w-0">
                    <span className="font-mono text-xs font-bold text-slate-800">
                      {hist.code}
                    </span>
                    <p className="truncate text-xs text-slate-600">
                      {hist.purpose}
                    </p>
                  </div>
                  <StatusBadge
                    variant={hist.status}
                    label={getLoanStatusLabel(hist.status)}
                  />
                </Link>
              ))}
            </div>
          )}
        </div>
      </div>

      {/* Featured Equipment Showcase */}
      <div className="mt-6">
        <div className="mb-3 flex items-center justify-between">
          <div>
            <h2 className="text-sm font-bold text-slate-900">
              Peralatan Siap Dipinjam di Laboratorium
            </h2>
            <p className="text-xs text-slate-500">
              Stok tersedia diperbarui secara real-time berdasarkan transaksi
              aktif.
            </p>
          </div>
          <Link
            href="/borrower/catalog"
            className="inline-flex items-center gap-1 text-xs font-semibold text-blue-800 hover:underline"
          >
            <span>Buka Katalog Lengkap</span>
            <ArrowRight className="h-3.5 w-3.5" />
          </Link>
        </div>

        <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
          {featuredEquipments.map((eq) => (
            <div
              key={eq.id}
              className="panel flex flex-col justify-between p-4"
            >
              <div>
                <div className="flex items-start justify-between gap-2">
                  <span className="font-mono text-[11px] font-bold text-slate-500">
                    {eq.code}
                  </span>
                  <StatusBadge
                    variant="tersedia"
                    label={`${eq.qty_available} unit tersedia`}
                  />
                </div>
                <Link
                  href={`/borrower/catalog/${eq.id}`}
                  className="mt-1.5 block text-sm font-bold text-slate-900 hover:text-blue-800"
                >
                  {eq.name}
                </Link>
                <p className="mt-0.5 text-xs text-slate-500">
                  {catById.get(eq.category_id)?.name ?? "-"} ·{" "}
                  {eq.location || "Lab Terpadu"}
                </p>
              </div>
              <div className="mt-4 flex items-center justify-between border-t border-slate-100 pt-3">
                <Link
                  href={`/borrower/catalog/${eq.id}`}
                  className="text-xs font-semibold text-slate-600 hover:text-slate-900"
                >
                  Lihat Spesifikasi
                </Link>
                <Link
                  href={`/borrower/loans/create?equipment_id=${eq.id}`}
                  className="btn-primary btn-xs"
                >
                  Pinjam
                </Link>
              </div>
            </div>
          ))}
        </div>
      </div>
    </AppShell>
  );
}
