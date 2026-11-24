"use client";

import React, { useState, useTransition } from "react";
import Link from "next/link";
import { useRouter } from "next/navigation";
import {
  AlertTriangle,
  ClipboardCheck,
  Clock,
  RotateCcw,
} from "lucide-react";
import {
  approveLoanServerAction,
  handOverLoanServerAction,
} from "@/lib/actions";
import { EmptyState } from "@/components/EmptyState";
import { StatusBadge } from "@/components/StatusBadge";
import { formatRelativeTime, formatShortDate } from "@/lib/types";

interface QueueLoanItem {
  id: number;
  code: string;
  borrower_name: string;
  borrower_phone: string | null;
  loan_date: string;
  due_date: string;
  purpose: string;
  items_summary: string;
  late_days: number;
}

interface QueueReturnItem {
  id: number;
  code: string;
  loan_code: string;
  borrower_name: string;
  due_date: string;
  return_date: string;
}

export function StaffQueueTabs({
  metrics,
  pendingLoans,
  readyHandoverLoans,
  pendingReturns,
  overdueLoans,
  recentActivities,
}: {
  metrics: {
    pending_approvals: number;
    ready_for_handover: number;
    pending_returns: number;
    currently_borrowed: number;
    overdue_count: number;
    damaged_units: number;
  };
  pendingLoans: QueueLoanItem[];
  readyHandoverLoans: QueueLoanItem[];
  pendingReturns: QueueReturnItem[];
  overdueLoans: QueueLoanItem[];
  recentActivities: Array<{
    id: number;
    actor_name: string;
    description: string;
    created_at: string;
  }>;
}) {
  const router = useRouter();
  const [isPending, startTransition] = useTransition();
  const [tab, setTab] = useState<"pending" | "handover" | "returns" | "overdue">(
    metrics.pending_approvals > 0
      ? "pending"
      : metrics.pending_returns > 0
      ? "returns"
      : "handover"
  );

  function quickApprove(loanId: number) {
    startTransition(async () => {
      const res = await approveLoanServerAction(loanId);
      if (!res.ok) alert(res.error);
      router.refresh();
    });
  }

  function quickHandover(loanId: number) {
    startTransition(async () => {
      const res = await handOverLoanServerAction(loanId);
      if (!res.ok) alert(res.error);
      router.refresh();
    });
  }

  const cardClass = (active: boolean) =>
    `panel p-4 text-left transition hover:border-slate-400 cursor-pointer ${
      active ? "ring-2 ring-slate-900 border-slate-900" : ""
    }`;

  return (
    <div>
      <div className="grid grid-cols-2 gap-4 lg:grid-cols-4">
        <button
          type="button"
          onClick={() => setTab("pending")}
          className={cardClass(tab === "pending")}
        >
          <div className="flex items-center justify-between">
            <span className="text-xs font-semibold text-amber-800">
              Menunggu Persetujuan
            </span>
            <Clock className="h-4 w-4 text-amber-600" />
          </div>
          <p className="mt-2 text-2xl font-bold text-slate-900">
            {metrics.pending_approvals}
          </p>
          <p className="mt-0.5 text-[11px] text-slate-500">
            Pengajuan baru perlu dicek
          </p>
        </button>

        <button
          type="button"
          onClick={() => setTab("handover")}
          className={cardClass(tab === "handover")}
        >
          <div className="flex items-center justify-between">
            <span className="text-xs font-semibold text-blue-800">
              Siap Diserahkan
            </span>
            <ClipboardCheck className="h-4 w-4 text-blue-700" />
          </div>
          <p className="mt-2 text-2xl font-bold text-slate-900">
            {metrics.ready_for_handover}
          </p>
          <p className="mt-0.5 text-[11px] text-slate-500">
            Stok sudah direservasi
          </p>
        </button>

        <button
          type="button"
          onClick={() => setTab("returns")}
          className={cardClass(tab === "returns")}
        >
          <div className="flex items-center justify-between">
            <span className="text-xs font-semibold text-emerald-800">
              Verifikasi Kembali
            </span>
            <RotateCcw className="h-4 w-4 text-emerald-700" />
          </div>
          <p className="mt-2 text-2xl font-bold text-slate-900">
            {metrics.pending_returns}
          </p>
          <p className="mt-0.5 text-[11px] text-slate-500">
            Cek kondisi fisik & denda
          </p>
        </button>

        <button
          type="button"
          onClick={() => setTab("overdue")}
          className={cardClass(tab === "overdue")}
        >
          <div className="flex items-center justify-between">
            <span className="text-xs font-semibold text-rose-700">
              Sedang Dipinjam / Telat
            </span>
            <AlertTriangle className="h-4 w-4 text-rose-600" />
          </div>
          <p className="mt-2 text-2xl font-bold text-slate-900">
            {metrics.currently_borrowed}{" "}
            <span className="text-sm font-semibold text-rose-600">
              ({metrics.overdue_count} telat)
            </span>
          </p>
          <p className="mt-0.5 text-[11px] text-slate-500">
            {metrics.damaged_units} unit dalam servis
          </p>
        </button>
      </div>

      <div className="mt-6 grid grid-cols-1 gap-6 lg:grid-cols-12">
        <div className="panel lg:col-span-8">
          {tab === "pending" && (
            <div>
              <div className="panel-header">
                <h2 className="panel-title">
                  Antrian Pengajuan Menunggu Persetujuan
                </h2>
                <Link
                  href="/staff/loans?status=pending"
                  className="text-xs font-semibold text-blue-800 hover:underline"
                >
                  Buka Semua
                </Link>
              </div>
              {pendingLoans.length === 0 ? (
                <EmptyState
                  title="Antrian Persetujuan Bersih"
                  description="Tidak ada pengajuan peminjaman baru yang menunggu verifikasi saat ini."
                />
              ) : (
                <div className="divide-y divide-slate-100">
                  {pendingLoans.map((loan) => (
                    <div
                      key={loan.id}
                      className="flex flex-wrap items-center justify-between gap-4 p-4"
                    >
                      <div className="min-w-0 flex-1">
                        <div className="flex items-center gap-2">
                          <Link
                            href={`/staff/loans/${loan.id}`}
                            className="font-mono text-xs font-bold text-blue-800 hover:underline"
                          >
                            {loan.code}
                          </Link>
                          <span className="text-xs font-semibold text-slate-900">
                            {loan.borrower_name}
                          </span>
                        </div>
                        <p className="mt-1 text-xs text-slate-700">
                          {loan.items_summary}
                        </p>
                        <p className="mt-0.5 text-[11px] text-slate-500">
                          Jadwal: {formatShortDate(loan.loan_date)} s/d{" "}
                          {formatShortDate(loan.due_date)} · {loan.purpose}
                        </p>
                      </div>
                      <div className="flex items-center gap-2">
                        <button
                          type="button"
                          disabled={isPending}
                          onClick={() => quickApprove(loan.id)}
                          className="btn-emerald btn-xs"
                        >
                          Setujui
                        </button>
                        <Link
                          href={`/staff/loans/${loan.id}`}
                          className="btn-secondary btn-xs"
                        >
                          Detail / Tolak
                        </Link>
                      </div>
                    </div>
                  ))}
                </div>
              )}
            </div>
          )}

          {tab === "handover" && (
            <div>
              <div className="panel-header">
                <h2 className="panel-title">
                  Siap Diserahkan Secara Fisik kepada Peminjam
                </h2>
                <Link
                  href="/staff/loans?status=approved"
                  className="text-xs font-semibold text-blue-800 hover:underline"
                >
                  Buka Semua
                </Link>
              </div>
              {readyHandoverLoans.length === 0 ? (
                <EmptyState
                  title="Tidak Ada Antrian Penyerahan"
                  description="Belum ada peminjaman berstatus disetujui yang menunggu pengambilan fisik."
                />
              ) : (
                <div className="divide-y divide-slate-100">
                  {readyHandoverLoans.map((loan) => (
                    <div
                      key={loan.id}
                      className="flex flex-wrap items-center justify-between gap-4 p-4"
                    >
                      <div className="min-w-0 flex-1">
                        <div className="flex items-center gap-2">
                          <Link
                            href={`/staff/loans/${loan.id}`}
                            className="font-mono text-xs font-bold text-blue-800 hover:underline"
                          >
                            {loan.code}
                          </Link>
                          <span className="text-xs font-semibold text-slate-900">
                            {loan.borrower_name}
                          </span>
                        </div>
                        <p className="mt-1 text-xs text-slate-700">
                          {loan.items_summary}
                        </p>
                      </div>
                      <button
                        type="button"
                        disabled={isPending}
                        onClick={() => quickHandover(loan.id)}
                        className="btn-blue btn-xs"
                      >
                        Catat Penyerahan Alat
                      </button>
                    </div>
                  ))}
                </div>
              )}
            </div>
          )}

          {tab === "returns" && (
            <div>
              <div className="panel-header">
                <h2 className="panel-title">
                  Pengembalian Menunggu Pemeriksaan Kondisi
                </h2>
                <Link
                  href="/staff/returns?status=pending_verification"
                  className="text-xs font-semibold text-blue-800 hover:underline"
                >
                  Buka Semua
                </Link>
              </div>
              {pendingReturns.length === 0 ? (
                <EmptyState
                  title="Tidak Ada Pengembalian Menunggu"
                  description="Seluruh pengajuan pengembalian telah selesai diperiksa."
                />
              ) : (
                <div className="divide-y divide-slate-100">
                  {pendingReturns.map((ret) => (
                    <div
                      key={ret.id}
                      className="flex flex-wrap items-center justify-between gap-4 p-4"
                    >
                      <div className="min-w-0 flex-1">
                        <div className="flex items-center gap-2">
                          <span className="font-mono text-xs font-bold text-slate-900">
                            {ret.code}
                          </span>
                          <span className="text-xs text-slate-500">
                            ({ret.loan_code})
                          </span>
                        </div>
                        <p className="mt-0.5 text-xs font-semibold text-slate-800">
                          {ret.borrower_name}
                        </p>
                        <p className="text-[11px] text-slate-500">
                          Jatuh tempo: {formatShortDate(ret.due_date)} ·
                          Dikembalikan: {formatShortDate(ret.return_date)}
                        </p>
                      </div>
                      <Link
                        href={`/staff/returns/${ret.id}`}
                        className="btn-primary btn-xs"
                      >
                        Periksa Fisik & Verifikasi
                      </Link>
                    </div>
                  ))}
                </div>
              )}
            </div>
          )}

          {tab === "overdue" && (
            <div>
              <div className="panel-header">
                <h2 className="panel-title">
                  Peminjaman Melewati Batas Waktu (Overdue)
                </h2>
                <Link
                  href="/staff/loans?status=overdue"
                  className="text-xs font-semibold text-blue-800 hover:underline"
                >
                  Buka Semua
                </Link>
              </div>
              {overdueLoans.length === 0 ? (
                <EmptyState
                  title="Tidak Ada Pinjaman Terlambat"
                  description="Seluruh peminjaman aktif masih berada dalam tenggat waktu."
                />
              ) : (
                <div className="divide-y divide-slate-100">
                  {overdueLoans.map((loan) => (
                    <div
                      key={loan.id}
                      className="flex flex-wrap items-center justify-between gap-4 p-4"
                    >
                      <div>
                        <div className="flex items-center gap-2">
                          <Link
                            href={`/staff/loans/${loan.id}`}
                            className="font-mono text-xs font-bold text-rose-700 hover:underline"
                          >
                            {loan.code}
                          </Link>
                          <StatusBadge
                            variant="overdue"
                            label={`${loan.late_days} hari terlambat`}
                          />
                        </div>
                        <p className="mt-0.5 text-xs font-semibold text-slate-900">
                          {loan.borrower_name} · {loan.borrower_phone || "-"}
                        </p>
                      </div>
                      <Link
                        href={`/staff/loans/${loan.id}`}
                        className="btn-secondary btn-xs"
                      >
                        Tindak Lanjuti
                      </Link>
                    </div>
                  ))}
                </div>
              )}
            </div>
          )}
        </div>

        <div className="panel lg:col-span-4">
          <div className="panel-header">
            <h2 className="panel-title">Aktivitas Operasional Terbaru</h2>
          </div>
          {recentActivities.length === 0 ? (
            <EmptyState
              title="Belum Ada Aktivitas"
              description="Log transaksi terbaru akan muncul di sini."
            />
          ) : (
            <div className="divide-y divide-slate-100">
              {recentActivities.map((act) => (
                <div key={act.id} className="px-4 py-3 text-xs">
                  <div className="flex items-center justify-between">
                    <span className="font-semibold text-slate-900">
                      {act.actor_name}
                    </span>
                    <span className="text-[11px] text-slate-400">
                      {formatRelativeTime(act.created_at)}
                    </span>
                  </div>
                  <p className="mt-0.5 leading-relaxed text-slate-600">
                    {act.description}
                  </p>
                </div>
              ))}
            </div>
          )}
        </div>
      </div>
    </div>
  );
}
