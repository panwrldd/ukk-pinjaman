"use client";

import React, { useState, useTransition } from "react";
import Link from "next/link";
import { useRouter } from "next/navigation";
import {
  AlertTriangle,
  ArrowLeft,
  CheckCircle2,
  ClipboardCheck,
  RotateCcw,
  Trash2,
  XCircle,
} from "lucide-react";
import {
  approveLoanServerAction,
  cancelLoanServerAction,
  deleteLoanServerAction,
  handOverLoanServerAction,
  rejectLoanServerAction,
  submitReturnServerAction,
} from "@/lib/actions";
import {
  EquipmentCondition,
  LoanStatus,
  Role,
  formatDateId,
  formatDateTimeId,
  formatRupiah,
  getConditionLabel,
  getLoanStatusLabel,
  getReturnStatusLabel,
} from "@/lib/types";
import { StatusBadge } from "./StatusBadge";

interface LoanDetailViewProps {
  viewerRole: Role;
  backHref: string;
  returnDetailHref?: string | null;
  loan: {
    id: number;
    code: string;
    loan_date: string;
    due_date: string;
    purpose: string;
    notes: string | null;
    status: LoanStatus;
    rejection_reason: string | null;
    approved_by_name: string | null;
    approved_at: string | null;
    handed_over_by_name: string | null;
    handed_over_at: string | null;
    borrower_name: string;
    borrower_username: string;
    borrower_identity: string | null;
    borrower_phone: string | null;
    borrower_address: string | null;
    current_late_days: number;
    estimated_fine: number;
    fine_rate: number;
  };
  items: Array<{
    id: number;
    equipment_code: string;
    equipment_name: string;
    location: string | null;
    qty: number;
    qty_available: number;
    condition_before: EquipmentCondition;
  }>;
  returnRecord: {
    id: number;
    code: string;
    return_date: string;
    late_days: number;
    fine_amount: number;
    fine_paid: boolean;
    status: "pending_verification" | "verified";
    notes: string | null;
  } | null;
}

export function LoanDetailView({
  viewerRole,
  backHref,
  returnDetailHref,
  loan,
  items,
  returnRecord,
}: LoanDetailViewProps) {
  const router = useRouter();
  const [isPending, startTransition] = useTransition();
  const [feedback, setFeedback] = useState<{
    type: "ok" | "err";
    text: string;
  } | null>(null);

  const [staffNotes, setStaffNotes] = useState(loan.notes ?? "");
  const [rejectReason, setRejectReason] = useState("");
  const [showRejectBox, setShowRejectBox] = useState(false);
  const [returnNotes, setReturnNotes] = useState("");

  const isStaffOrAdmin = viewerRole === "admin" || viewerRole === "petugas";

  function runAction(fn: () => Promise<{ ok: boolean; message?: string; error?: string; redirectTo?: string }>) {
    setFeedback(null);
    startTransition(async () => {
      const res = await fn();
      if (!res.ok) {
        setFeedback({ type: "err", text: res.error || "Gagal memproses aksi." });
        return;
      }
      if (res.redirectTo) {
        router.push(res.redirectTo);
        router.refresh();
        return;
      }
      setFeedback({ type: "ok", text: res.message || "Berhasil diperbarui." });
      setShowRejectBox(false);
      router.refresh();
    });
  }

  return (
    <div className="space-y-6">
      <div className="flex flex-wrap items-center justify-between gap-3">
        <Link href={backHref} className="btn-secondary btn-xs">
          <ArrowLeft className="h-3.5 w-3.5" />
          <span>Kembali ke Daftar Peminjaman</span>
        </Link>

        <div className="flex flex-wrap items-center gap-2">
          {returnRecord && returnDetailHref && (
            <Link href={returnDetailHref} className="btn-blue btn-xs">
              <RotateCcw className="h-3.5 w-3.5" />
              <span>Buka Data Pengembalian ({returnRecord.code})</span>
            </Link>
          )}

          {viewerRole === "admin" &&
            !["approved", "borrowed", "overdue"].includes(loan.status) && (
              <button
                type="button"
                disabled={isPending}
                onClick={() => {
                  if (confirm(`Hapus permanen data peminjaman ${loan.code}?`)) {
                    runAction(() => deleteLoanServerAction(loan.id));
                  }
                }}
                className="btn-danger-outline btn-xs"
              >
                <Trash2 className="h-3.5 w-3.5" />
                <span>Hapus Transaksi</span>
              </button>
            )}
        </div>
      </div>

      {feedback && (
        <div
          className={`flex items-center gap-2.5 rounded-lg border p-3.5 text-xs font-medium ${
            feedback.type === "ok"
              ? "border-emerald-200 bg-emerald-50 text-emerald-800"
              : "border-rose-200 bg-rose-50 text-rose-800"
          }`}
        >
          {feedback.type === "ok" ? (
            <CheckCircle2 className="h-4 w-4 shrink-0 text-emerald-600" />
          ) : (
            <AlertTriangle className="h-4 w-4 shrink-0 text-rose-600" />
          )}
          <span>{feedback.text}</span>
        </div>
      )}

      {/* Loan Overview Card */}
      <div className="panel p-5">
        <div className="flex flex-wrap items-start justify-between gap-4 border-b border-slate-100 pb-4">
          <div>
            <div className="flex flex-wrap items-center gap-2.5">
              <span className="font-mono text-base font-bold text-slate-900">
                {loan.code}
              </span>
              <StatusBadge
                variant={loan.status}
                label={getLoanStatusLabel(loan.status)}
              />
              {loan.current_late_days > 0 &&
                ["borrowed", "overdue"].includes(loan.status) && (
                  <StatusBadge
                    variant="overdue"
                    label={`${loan.current_late_days} hari terlambat (Est. ${formatRupiah(
                      loan.estimated_fine
                    )})`}
                  />
                )}
            </div>
            <p className="mt-1.5 text-sm font-medium text-slate-800">
              {loan.purpose}
            </p>
          </div>

          <div className="text-right text-xs text-slate-600">
            <p>
              Tanggal Pinjam: <strong>{formatDateId(loan.loan_date)}</strong>
            </p>
            <p className="mt-0.5">
              Batas Kembali:{" "}
              <strong
                className={
                  loan.current_late_days > 0 ? "text-rose-700" : "text-slate-900"
                }
              >
                {formatDateId(loan.due_date)}
              </strong>
            </p>
          </div>
        </div>

        <div className="mt-4 grid grid-cols-1 gap-4 text-xs sm:grid-cols-3">
          <div>
            <span className="font-semibold uppercase tracking-wider text-slate-400">
              Data Peminjam
            </span>
            <p className="mt-1 font-semibold text-slate-900">
              {loan.borrower_name} (@{loan.borrower_username})
            </p>
            <p className="text-slate-500">
              {loan.borrower_identity || "-"} · {loan.borrower_phone || "-"}
            </p>
            <p className="text-slate-500">{loan.borrower_address || "-"}</p>
          </div>

          <div>
            <span className="font-semibold uppercase tracking-wider text-slate-400">
              Verifikasi Persetujuan
            </span>
            <p className="mt-1 font-semibold text-slate-900">
              {loan.approved_by_name || "Belum diverifikasi"}
            </p>
            <p className="text-slate-500">
              {loan.approved_at ? formatDateTimeId(loan.approved_at) : "-"}
            </p>
          </div>

          <div>
            <span className="font-semibold uppercase tracking-wider text-slate-400">
              Penyerahan Fisik Alat
            </span>
            <p className="mt-1 font-semibold text-slate-900">
              {loan.handed_over_by_name || "Belum diserahkan"}
            </p>
            <p className="text-slate-500">
              {loan.handed_over_at ? formatDateTimeId(loan.handed_over_at) : "-"}
            </p>
          </div>
        </div>

        {loan.notes && (
          <div className="mt-4 rounded-md border border-slate-200 bg-slate-50 p-3 text-xs text-slate-700">
            <strong>Catatan Petugas / Peminjam:</strong> {loan.notes}
          </div>
        )}

        {loan.rejection_reason && (
          <div className="mt-4 rounded-md border border-rose-200 bg-rose-50 p-3 text-xs text-rose-800">
            <strong>Alasan Penolakan:</strong> {loan.rejection_reason}
          </div>
        )}
      </div>

      {/* Borrowed Equipment Items Table */}
      <div className="panel">
        <div className="panel-header">
          <h2 className="panel-title">Daftar Peralatan yang Dipinjam</h2>
        </div>
        <div className="table-container">
          <table className="table-pro">
            <thead>
              <tr>
                <th>Kode Alat</th>
                <th>Nama Peralatan</th>
                <th>Lokasi</th>
                <th>Kondisi Awal</th>
                <th>Jumlah Pinjam</th>
                {isStaffOrAdmin && <th>Stok Tersedia Saat Ini</th>}
              </tr>
            </thead>
            <tbody>
              {items.map((item) => (
                <tr key={item.id}>
                  <td className="font-mono text-xs font-semibold">
                    {item.equipment_code}
                  </td>
                  <td className="font-medium text-slate-900">
                    {item.equipment_name}
                  </td>
                  <td className="text-xs text-slate-500">
                    {item.location || "-"}
                  </td>
                  <td>
                    <StatusBadge
                      variant={item.condition_before}
                      label={getConditionLabel(item.condition_before)}
                    />
                  </td>
                  <td className="font-semibold text-slate-900">
                    {item.qty} unit
                  </td>
                  {isStaffOrAdmin && (
                    <td className="text-xs text-slate-600">
                      {item.qty_available} unit
                    </td>
                  )}
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      </div>

      {/* Staff/Admin Operational Controls */}
      {isStaffOrAdmin && loan.status === "pending" && (
        <div className="panel p-5 space-y-4">
          <h2 className="text-sm font-semibold text-slate-900">
            Tindakan Verifikasi Pengajuan (Petugas / Admin)
          </h2>
          <div>
            <label className="input-label">
              Catatan Persetujuan / Instruksi Pengambilan (Opsional)
            </label>
            <input
              type="text"
              value={staffNotes}
              onChange={(e) => setStaffNotes(e.target.value)}
              placeholder="Contoh: Silakan ambil alat di Loket Studio Multimedia..."
              className="input-field"
            />
          </div>
          <div className="flex flex-wrap items-center gap-2.5">
            <button
              type="button"
              disabled={isPending}
              onClick={() =>
                runAction(() => approveLoanServerAction(loan.id, staffNotes))
              }
              className="btn-emerald"
            >
              <ClipboardCheck className="h-4 w-4" />
              <span>Setujui & Reservasi Stok</span>
            </button>
            <button
              type="button"
              disabled={isPending}
              onClick={() => setShowRejectBox((v) => !v)}
              className="btn-danger"
            >
              <XCircle className="h-4 w-4" />
              <span>Tolak Pengajuan</span>
            </button>
          </div>

          {showRejectBox && (
            <div className="rounded-lg border border-rose-200 bg-rose-50/50 p-4 space-y-3">
              <label className="input-label text-rose-900">
                Alasan Penolakan (Wajib Diisi)
              </label>
              <textarea
                rows={2}
                required
                value={rejectReason}
                onChange={(e) => setRejectReason(e.target.value)}
                placeholder="Tuliskan alasan penolakan sesuai SOP laboratorium..."
                className="input-field"
              />
              <button
                type="button"
                disabled={isPending || !rejectReason.trim()}
                onClick={() =>
                  runAction(() => rejectLoanServerAction(loan.id, rejectReason))
                }
                className="btn-danger btn-xs"
              >
                Konfirmasi Penolakan
              </button>
            </div>
          )}
        </div>
      )}

      {isStaffOrAdmin && loan.status === "approved" && (
        <div className="panel p-5 space-y-4">
          <h2 className="text-sm font-semibold text-slate-900">
            Konfirmasi Penyerahan Fisik Alat di Loket
          </h2>
          <p className="text-xs text-slate-600">
            Stok alat telah direservasi. Klik tombol di bawah saat peminjam mengambil fisik peralatan.
          </p>
          <div className="flex flex-wrap items-center gap-2.5">
            <button
              type="button"
              disabled={isPending}
              onClick={() =>
                runAction(() => handOverLoanServerAction(loan.id, staffNotes))
              }
              className="btn-blue"
            >
              <ClipboardCheck className="h-4 w-4" />
              <span>Catat Penyerahan Fisik Alat</span>
            </button>
            <button
              type="button"
              disabled={isPending}
              onClick={() => runAction(() => cancelLoanServerAction(loan.id))}
              className="btn-danger-outline"
            >
              Batalkan & Kembalikan Stok Reservasi
            </button>
          </div>
        </div>
      )}

      {isStaffOrAdmin &&
        ["borrowed", "overdue"].includes(loan.status) &&
        !returnRecord && (
          <div className="panel p-5 space-y-3">
            <h2 className="text-sm font-semibold text-slate-900">
              Inisiasi Pengembalian Langsung di Loket Petugas
            </h2>
            <p className="text-xs text-slate-600">
              Jika peminjam menyerahkan alat langsung di meja petugas tanpa mengajukan lewat aplikasi, buat dokumen pemeriksaan kembali di sini.
            </p>
            <button
              type="button"
              disabled={isPending}
              onClick={() =>
                runAction(() =>
                  submitReturnServerAction(
                    loan.id,
                    "Diserahkan langsung melalui loket petugas."
                  )
                )
              }
              className="btn-primary"
            >
              <RotateCcw className="h-4 w-4" />
              <span>Proses Pengembalian & Periksa Kondisi</span>
            </button>
          </div>
        )}

      {/* Borrower Actions */}
      {viewerRole === "peminjam" && loan.status === "pending" && (
        <div className="panel p-5 flex flex-wrap items-center justify-between gap-3">
          <div>
            <h2 className="text-sm font-semibold text-slate-900">
              Pengajuan Masih Menunggu Verifikasi
            </h2>
            <p className="text-xs text-slate-500">
              Anda dapat membatalkan pengajuan ini selama belum disetujui petugas.
            </p>
          </div>
          <button
            type="button"
            disabled={isPending}
            onClick={() => runAction(() => cancelLoanServerAction(loan.id))}
            className="btn-danger-outline"
          >
            Batalkan Pengajuan Saya
          </button>
        </div>
      )}

      {viewerRole === "peminjam" &&
        ["borrowed", "overdue"].includes(loan.status) &&
        !returnRecord && (
          <div className="panel p-5 space-y-4">
            <div>
              <h2 className="text-sm font-semibold text-slate-900">
                Ajukan Pengembalian Peralatan
              </h2>
              <p className="text-xs text-slate-500">
                Setelah mengirim pengajuan kembali, serahkan fisik peralatan ke loket petugas untuk diperiksa kondisinya.
              </p>
            </div>

            {loan.current_late_days > 0 && (
              <div className="rounded-md border border-rose-200 bg-rose-50 p-3 text-xs text-rose-800">
                Pinjaman ini terlambat <strong>{loan.current_late_days} hari</strong>. Estimasi denda keterlambatan:{" "}
                <strong>{formatRupiah(loan.estimated_fine)}</strong> ({formatRupiah(loan.fine_rate)}/hari).
              </div>
            )}

            <div>
              <label className="input-label">
                Catatan Pengembalian (Opsional)
              </label>
              <textarea
                rows={2}
                value={returnNotes}
                onChange={(e) => setReturnNotes(e.target.value)}
                placeholder="Contoh: Seluruh kelengkapan kabel dan baterai lengkap..."
                className="input-field"
              />
            </div>

            <button
              type="button"
              disabled={isPending}
              onClick={() =>
                runAction(() => submitReturnServerAction(loan.id, returnNotes))
              }
              className="btn-primary"
            >
              <RotateCcw className="h-4 w-4" />
              <span>Kirim Pengajuan Pengembalian</span>
            </button>
          </div>
        )}

      {/* Return Summary Box if already submitted */}
      {returnRecord && (
        <div className="panel p-5">
          <div className="flex flex-wrap items-center justify-between gap-2">
            <div>
              <span className="text-xs font-semibold uppercase tracking-wider text-slate-400">
                Data Pengembalian Terkait
              </span>
              <div className="mt-1 flex items-center gap-2">
                <span className="font-mono text-sm font-bold text-slate-900">
                  {returnRecord.code}
                </span>
                <StatusBadge
                  variant={returnRecord.status}
                  label={getReturnStatusLabel(returnRecord.status)}
                />
              </div>
            </div>
            <div className="text-right text-xs">
              <p>Tanggal Kembali: <strong>{formatDateId(returnRecord.return_date)}</strong></p>
              <p>
                Denda:{" "}
                <strong className={returnRecord.fine_amount > 0 ? "text-rose-700" : "text-emerald-700"}>
                  {formatRupiah(returnRecord.fine_amount)}
                </strong>{" "}
                ({returnRecord.fine_paid ? "Lunas" : "Belum Dibayar"})
              </p>
            </div>
          </div>
        </div>
      )}
    </div>
  );
}
