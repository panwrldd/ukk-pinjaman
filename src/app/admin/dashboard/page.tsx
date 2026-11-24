import Link from "next/link";
import {
  Activity,
  AlertTriangle,
  Banknote,
  ClipboardList,
  Clock,
  Database,
  Package,
  Plus,
  Printer,
  Tags,
  UserPlus,
  Users,
} from "lucide-react";
import { AppShell } from "@/components/AppShell";
import { EmptyState } from "@/components/EmptyState";
import { StatusBadge } from "@/components/StatusBadge";
import { requireUser } from "@/lib/auth";
import { getFineRatePerDay } from "@/lib/services";
import { readDb } from "@/lib/storage";
import {
  countLateDays,
  formatNumber,
  formatRelativeTime,
  formatRupiah,
  formatShortDate,
  getMonthShortLabel,
  todayJakarta,
} from "@/lib/types";

export const dynamic = "force-dynamic";

export default async function AdminDashboardPage() {
  const user = await requireUser(["admin"]);
  const db = await readDb();
  const today = todayJakarta();

  const totalUnits = db.equipments.reduce((s, e) => s + e.qty_total, 0);
  const availableUnits = db.equipments.reduce((s, e) => s + e.qty_available, 0);
  const damagedUnits = db.equipments.reduce((s, e) => s + e.qty_damaged, 0);
  const borrowedUnits = Math.max(0, totalUnits - availableUnits - damagedUnits);

  const safeTotal = Math.max(1, totalUnits);
  const pctAvailable = Number(((availableUnits / safeTotal) * 100).toFixed(1));
  const pctBorrowed = Number(((borrowedUnits / safeTotal) * 100).toFixed(1));
  const pctDamaged = Math.max(
    0,
    Number((100 - pctAvailable - pctBorrowed).toFixed(1))
  );

  const fineRate = getFineRatePerDay(db);
  const finesCollected = db.returns
    .filter((r) => r.fine_paid)
    .reduce((s, r) => s + r.fine_amount, 0);
  const finesUnpaid = db.returns
    .filter((r) => !r.fine_paid)
    .reduce((s, r) => s + r.fine_amount, 0);

  const activeLoansCount = db.loans.filter((l) =>
    ["approved", "borrowed", "overdue"].includes(l.status)
  ).length;
  const pendingLoansCount = db.loans.filter((l) => l.status === "pending").length;
  const pendingReturnsCount = db.returns.filter(
    (r) => r.status === "pending_verification"
  ).length;
  const overdueLoans = db.loans
    .filter((l) => l.status === "overdue")
    .sort((a, b) => a.due_date.localeCompare(b.due_date));

  const maintenanceEquipments = db.equipments.filter((e) => e.qty_damaged > 0);

  // 6-Month Loan Trend
  const [curY, curM] = today.split("-").map(Number);
  const monthlyTrend: Array<{ label: string; count: number }> = [];
  for (let offset = 5; offset >= 0; offset--) {
    const dt = new Date(Date.UTC(curY, curM - 1 - offset, 1));
    const ym = `${dt.getUTCFullYear()}-${String(dt.getUTCMonth() + 1).padStart(
      2,
      "0"
    )}`;
    const count = db.loans.filter((l) => l.loan_date.startsWith(ym)).length;
    monthlyTrend.push({
      label: getMonthShortLabel(dt.getUTCFullYear(), dt.getUTCMonth()),
      count,
    });
  }
  const maxMonthCount = Math.max(1, ...monthlyTrend.map((m) => m.count));

  // Category Utilization
  const eqCatMap = new Map(db.equipments.map((e) => [e.id, e.category_id]));
  const catStats = db.categories.map((cat) => {
    const borrowedQty = db.loan_details
      .filter((ld) => eqCatMap.get(ld.equipment_id) === cat.id)
      .reduce((s, ld) => s + ld.qty, 0);
    return { id: cat.id, name: cat.name, borrowed_qty: borrowedQty };
  });
  const maxCategoryQty = Math.max(1, ...catStats.map((c) => c.borrowed_qty));

  const userById = new Map(db.users.map((u) => [u.id, u]));
  const recentLogs = [...db.activity_logs]
    .sort((a, b) => b.id - a.id)
    .slice(0, 8);

  return (
    <AppShell
      user={user}
      header="Pusat Kendali Inventaris & Tata Kelola"
      subheader="Ringkasan kesehatan inventaris laboratorium, tren peminjaman, pengawasan keterlambatan, dan jejak audit sistem."
    >
      {/* Top Quick Action Bar */}
      <div className="mb-6 flex flex-wrap items-center justify-between gap-3 rounded-lg border border-slate-200 bg-white px-5 py-3.5 shadow-2xs">
        <div className="flex flex-wrap items-center gap-4 text-xs text-slate-600">
          <span className="inline-flex items-center gap-1.5 font-medium text-slate-900">
            <Banknote className="h-4 w-4 text-blue-800" />
            Tarif Denda Aktif:{" "}
            <strong className="font-semibold">{formatRupiah(fineRate)}/hari</strong>
          </span>
          <span className="hidden text-slate-300 sm:inline">|</span>
          <span>
            Denda Lunas:{" "}
            <strong className="text-emerald-700">
              {formatRupiah(finesCollected)}
            </strong>
          </span>
          {finesUnpaid > 0 && (
            <span>
              Belum Dibayar:{" "}
              <strong className="text-rose-700">{formatRupiah(finesUnpaid)}</strong>
            </span>
          )}
        </div>
        <div className="flex flex-wrap items-center gap-2">
          <Link href="/admin/equipments/new" className="btn-primary btn-xs">
            <Plus className="h-3.5 w-3.5" />
            <span>Tambah Alat</span>
          </Link>
          <Link href="/admin/users/new" className="btn-secondary btn-xs">
            <UserPlus className="h-3.5 w-3.5" />
            <span>Tambah Pengguna</span>
          </Link>
          <Link href="/reports" className="btn-secondary btn-xs">
            <Printer className="h-3.5 w-3.5" />
            <span>Cetak Laporan</span>
          </Link>
        </div>
      </div>

      {/* Operational Governance Metrics */}
      <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6">
        <div className="panel p-4">
          <div className="flex items-center justify-between">
            <span className="text-[11px] font-semibold uppercase tracking-wider text-slate-500">
              Pengguna
            </span>
            <Users className="h-4 w-4 text-slate-400" />
          </div>
          <p className="mt-2 text-2xl font-bold text-slate-900">
            {formatNumber(db.users.length)}
          </p>
          <p className="mt-1 text-xs text-slate-500">
            {db.users.filter((u) => u.role === "peminjam" && u.is_active).length}{" "}
            peminjam aktif
          </p>
        </div>

        <div className="panel p-4">
          <div className="flex items-center justify-between">
            <span className="text-[11px] font-semibold uppercase tracking-wider text-slate-500">
              Jenis Alat
            </span>
            <Package className="h-4 w-4 text-slate-400" />
          </div>
          <p className="mt-2 text-2xl font-bold text-slate-900">
            {formatNumber(db.equipments.length)}
          </p>
          <p className="mt-1 text-xs text-slate-500">
            {availableUnits}/{totalUnits} unit tersedia
          </p>
        </div>

        <div className="panel p-4">
          <div className="flex items-center justify-between">
            <span className="text-[11px] font-semibold uppercase tracking-wider text-slate-500">
              Kategori
            </span>
            <Tags className="h-4 w-4 text-slate-400" />
          </div>
          <p className="mt-2 text-2xl font-bold text-slate-900">
            {formatNumber(db.categories.length)}
          </p>
          <p className="mt-1 text-xs text-slate-500">Klasifikasi inventaris</p>
        </div>

        <div className="panel p-4">
          <div className="flex items-center justify-between">
            <span className="text-[11px] font-semibold uppercase tracking-wider text-slate-500">
              Pinjaman Aktif
            </span>
            <ClipboardList className="h-4 w-4 text-indigo-600" />
          </div>
          <p className="mt-2 text-2xl font-bold text-slate-900">
            {formatNumber(activeLoansCount)}
          </p>
          <p className="mt-1 text-xs text-slate-500">
            {borrowedUnits} unit sedang keluar
          </p>
        </div>

        <Link
          href="/admin/loans?status=pending"
          className="panel p-4 transition hover:border-amber-300"
        >
          <div className="flex items-center justify-between">
            <span className="text-[11px] font-semibold uppercase tracking-wider text-amber-700">
              Menunggu
            </span>
            <Clock className="h-4 w-4 text-amber-600" />
          </div>
          <p className="mt-2 text-2xl font-bold text-amber-800">
            {formatNumber(pendingLoansCount)}
          </p>
          <p className="mt-1 text-xs text-amber-700/80">
            {pendingReturnsCount} kembali perlu cek
          </p>
        </Link>

        <Link
          href="/admin/loans?status=overdue"
          className={`panel p-4 transition hover:border-rose-300 ${
            overdueLoans.length > 0 ? "border-rose-200 bg-rose-50/30" : ""
          }`}
        >
          <div className="flex items-center justify-between">
            <span className="text-[11px] font-semibold uppercase tracking-wider text-rose-700">
              Terlambat
            </span>
            <AlertTriangle className="h-4 w-4 text-rose-600" />
          </div>
          <p className="mt-2 text-2xl font-bold text-rose-700">
            {formatNumber(overdueLoans.length)}
          </p>
          <p className="mt-1 text-xs text-rose-600">
            {damagedUnits} unit maintenance
          </p>
        </Link>
      </div>

      {/* Inventory Composition Progress Bar */}
      <div className="panel mt-6 p-5">
        <div className="flex flex-wrap items-center justify-between gap-2">
          <div>
            <h2 className="text-sm font-semibold text-slate-900">
              Komposisi Posisi Fisik Unit Inventaris ({totalUnits} Total Unit)
            </h2>
            <p className="text-xs text-slate-500">
              Proporsi unit siap pakai di ruang penyimpanan, sedang dipinjam, dan dalam perbaikan.
            </p>
          </div>
          <div className="flex flex-wrap items-center gap-4 text-xs">
            <span className="inline-flex items-center gap-1.5">
              <span className="h-2.5 w-2.5 rounded-xs bg-emerald-600" /> Tersedia (
              {availableUnits})
            </span>
            <span className="inline-flex items-center gap-1.5">
              <span className="h-2.5 w-2.5 rounded-xs bg-blue-700" /> Dipinjam (
              {borrowedUnits})
            </span>
            <span className="inline-flex items-center gap-1.5">
              <span className="h-2.5 w-2.5 rounded-xs bg-rose-600" /> Rusak /
              Maintenance ({damagedUnits})
            </span>
          </div>
        </div>
        <div className="mt-3 flex h-3 w-full overflow-hidden rounded-full bg-slate-100">
          <div
            className="bg-emerald-600 transition-all"
            style={{ width: `${pctAvailable}%` }}
          />
          <div
            className="bg-blue-700 transition-all"
            style={{ width: `${pctBorrowed}%` }}
          />
          <div
            className="bg-rose-600 transition-all"
            style={{ width: `${pctDamaged}%` }}
          />
        </div>
      </div>

      {/* Charts Grid */}
      <div className="mt-6 grid grid-cols-1 gap-6 lg:grid-cols-12">
        <div className="panel lg:col-span-7">
          <div className="panel-header">
            <div>
              <h2 className="panel-title">
                Grafik Tren Transaksi Peminjaman (6 Bulan Terakhir)
              </h2>
              <p className="text-xs text-slate-500">
                Dihitung langsung berdasarkan tanggal peminjaman pada data transaksi.
              </p>
            </div>
          </div>
          <div className="p-5">
            <div className="grid h-52 grid-cols-6 items-end gap-3 border-b border-slate-200 pb-3 pt-6">
              {monthlyTrend.map((point) => {
                const barHeight =
                  point.count > 0
                    ? Math.max(12, Math.round((point.count / maxMonthCount) * 100))
                    : 4;
                return (
                  <div
                    key={point.label}
                    className="flex h-full flex-col items-center justify-end gap-2"
                  >
                    <span className="text-xs font-bold text-slate-700">
                      {point.count}
                    </span>
                    <div className="flex h-36 w-full max-w-[44px] items-end rounded-t bg-slate-100">
                      <div
                        className={`w-full rounded-t transition-all ${
                          point.count > 0
                            ? "bg-slate-900 hover:bg-blue-800"
                            : "bg-slate-200"
                        }`}
                        style={{ height: `${barHeight}%` }}
                        title={`${point.label}: ${point.count} transaksi`}
                      />
                    </div>
                  </div>
                );
              })}
            </div>
            <div className="mt-2.5 grid grid-cols-6 gap-3 text-center">
              {monthlyTrend.map((point) => (
                <span
                  key={point.label}
                  className="text-[11px] font-medium text-slate-500"
                >
                  {point.label}
                </span>
              ))}
            </div>
          </div>
        </div>

        <div className="panel lg:col-span-5">
          <div className="panel-header">
            <div>
              <h2 className="panel-title">
                Frekuensi Unit Dipinjam per Kategori
              </h2>
              <p className="text-xs text-slate-500">
                Akumulasi kuantitas unit pada rincian peminjaman.
              </p>
            </div>
          </div>
          <div className="space-y-4 p-5">
            {catStats.map((cat) => {
              const pctCat =
                cat.borrowed_qty > 0
                  ? Math.round((cat.borrowed_qty / maxCategoryQty) * 100)
                  : 0;
              return (
                <div key={cat.id}>
                  <div className="flex items-center justify-between text-xs">
                    <span className="font-medium text-slate-800">{cat.name}</span>
                    <span className="font-semibold text-slate-900">
                      {cat.borrowed_qty} unit
                    </span>
                  </div>
                  <div className="mt-1.5 h-2 w-full overflow-hidden rounded-full bg-slate-100">
                    <div
                      className="h-full rounded-full bg-blue-800"
                      style={{ width: `${pctCat}%` }}
                    />
                  </div>
                </div>
              );
            })}
          </div>
        </div>
      </div>

      {/* Bottom Governance & Audit Columns */}
      <div className="mt-6 grid grid-cols-1 gap-6 lg:grid-cols-12">
        <div className="space-y-6 lg:col-span-6">
          <div className="panel">
            <div className="panel-header">
              <h2 className="panel-title">
                Pinjaman Melewati Jatuh Tempo (Overdue)
              </h2>
              <Link
                href="/admin/loans?status=overdue"
                className="text-xs font-semibold text-blue-800 hover:underline"
              >
                Lihat Semua
              </Link>
            </div>
            {overdueLoans.length === 0 ? (
              <EmptyState
                title="Tidak Ada Keterlambatan Aktif"
                description="Seluruh peminjaman aktif berada dalam batas jadwal pengembalian."
              />
            ) : (
              <div className="divide-y divide-slate-100">
                {overdueLoans.map((loan) => {
                  const lateDays = countLateDays(loan.due_date, today);
                  return (
                    <div
                      key={loan.id}
                      className="flex items-center justify-between gap-4 px-5 py-3.5"
                    >
                      <div className="min-w-0">
                        <div className="flex items-center gap-2">
                          <Link
                            href={`/admin/loans/${loan.id}`}
                            className="font-mono text-xs font-bold text-slate-900 hover:text-blue-800"
                          >
                            {loan.code}
                          </Link>
                          <StatusBadge
                            variant="overdue"
                            label={`${lateDays} hari terlambat`}
                          />
                        </div>
                        <p className="mt-0.5 truncate text-xs text-slate-600">
                          {userById.get(loan.user_id)?.name ?? "-"} · Jatuh tempo{" "}
                          {formatShortDate(loan.due_date)}
                        </p>
                      </div>
                      <Link
                        href={`/admin/loans/${loan.id}`}
                        className="btn-secondary btn-xs shrink-0"
                      >
                        Periksa
                      </Link>
                    </div>
                  );
                })}
              </div>
            )}
          </div>

          <div className="panel">
            <div className="panel-header">
              <h2 className="panel-title">
                Unit Rusak Berat / Dalam Perbaikan
              </h2>
              <Link
                href="/admin/equipments"
                className="text-xs font-semibold text-blue-800 hover:underline"
              >
                Kelola Inventaris
              </Link>
            </div>
            {maintenanceEquipments.length === 0 ? (
              <EmptyState
                title="Tidak Ada Unit Rusak"
                description="Seluruh unit peralatan siap digunakan dan tidak ada antrian servis."
              />
            ) : (
              <div className="divide-y divide-slate-100">
                {maintenanceEquipments.map((eq) => (
                  <div
                    key={eq.id}
                    className="flex items-center justify-between gap-4 px-5 py-3.5"
                  >
                    <div className="min-w-0">
                      <p className="truncate text-xs font-semibold text-slate-900">
                        {eq.name}
                      </p>
                      <p className="mt-0.5 text-xs text-slate-500">
                        {eq.code} · Tersedia: {eq.qty_available}/{eq.qty_total}{" "}
                        unit
                      </p>
                    </div>
                    <div className="flex shrink-0 items-center gap-2">
                      <StatusBadge
                        variant="rusak_berat"
                        label={`${eq.qty_damaged} unit rusak`}
                      />
                      <Link
                        href={`/admin/equipments/${eq.id}`}
                        className="btn-secondary btn-xs"
                      >
                        Pulihkan
                      </Link>
                    </div>
                  </div>
                ))}
              </div>
            )}
          </div>
        </div>

        <div className="panel lg:col-span-6">
          <div className="panel-header">
            <div>
              <h2 className="panel-title">Aktivitas Terbaru & Audit Sistem</h2>
              <p className="text-xs text-slate-500">
                Mencakup log aktivitas pengguna dan audit perubahan status otomatis.
              </p>
            </div>
            <Link
              href="/admin/activity-logs"
              className="text-xs font-semibold text-blue-800 hover:underline"
            >
              Log Lengkap
            </Link>
          </div>
          {recentLogs.length === 0 ? (
            <EmptyState
              title="Belum Ada Aktivitas"
              description="Aktivitas pengguna dan perubahan status transaksi akan tercatat di sini."
            />
          ) : (
            <div className="divide-y divide-slate-100">
              {recentLogs.map((log) => {
                const actorName = log.user_id
                  ? userById.get(log.user_id)?.name ?? "Pengguna"
                  : "Sistem Audit";
                const isTrigger = log.module === "db_trigger";
                return (
                  <div
                    key={log.id}
                    className="flex items-start gap-3 px-5 py-3.5"
                  >
                    <div
                      className={`mt-0.5 flex h-7 w-7 shrink-0 items-center justify-center rounded-md ${
                        isTrigger
                          ? "bg-indigo-50 text-indigo-700"
                          : "bg-slate-100 text-slate-600"
                      }`}
                    >
                      {isTrigger ? (
                        <Database className="h-3.5 w-3.5" />
                      ) : (
                        <Activity className="h-3.5 w-3.5" />
                      )}
                    </div>
                    <div className="min-w-0 flex-1">
                      <div className="flex flex-wrap items-center justify-between gap-1">
                        <span className="text-xs font-semibold text-slate-900">
                          {actorName}
                          {isTrigger && (
                            <span className="ml-1.5 rounded bg-indigo-50 px-1.5 py-0.5 font-mono text-[10px] font-semibold text-indigo-700">
                              AUDIT TRIGGER
                            </span>
                          )}
                        </span>
                        <span className="text-[11px] text-slate-400">
                          {formatRelativeTime(log.created_at)}
                        </span>
                      </div>
                      <p className="mt-0.5 text-xs leading-relaxed text-slate-600">
                        {log.description}
                      </p>
                    </div>
                  </div>
                );
              })}
            </div>
          )}
        </div>
      </div>
    </AppShell>
  );
}
