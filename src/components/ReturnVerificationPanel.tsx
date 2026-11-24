"use client";

import React, { useState, useTransition } from "react";
import Link from "next/link";
import { useRouter } from "next/navigation";
import { CheckCircle2, AlertTriangle, ArrowLeft } from "lucide-react";
import {
  markFinePaidServerAction,
  verifyReturnServerAction,
} from "@/lib/actions";
import {
  EquipmentCondition,
  formatDateId,
  formatDateTimeId,
  formatRupiah,
  getConditionLabel,
  getReturnStatusLabel,
} from "@/lib/types";
import { StatusBadge } from "./StatusBadge";

interface ReturnItemRow {
  loan_detail_id: number;
  equipment_id: number;
  equipment_code: string;
  equipment_name: string;
  qty: number;
  condition_before: EquipmentCondition;
  verified_condition_after?: EquipmentCondition;
  verified_qty_damaged?: number;
  verified_damage_notes?: string | null;
}

interface ReturnVerificationPanelProps {
  backHref: string;
  loanHref: string;
  returnData: {
    id: number;
    code: string;
    return_date: string;
    late_days: number;
    fine_rate: number;
    fine_amount: number;
    fine_paid: boolean;
    status: "pending_verification" | "verified";
    notes: string | null;
    verified_at: string | null;
    verified_by_name: string | null;
    returned_by_name: string;
    loan_id: number;
    loan_code: string;
    loan_date: string;
    due_date: string;
    borrower_name: string;
    borrower_identity: string | null;
  };
  items: ReturnItemRow[];
}

export function ReturnVerificationPanel({
  backHref,
  loanHref,
  returnData,
  items,
}: ReturnVerificationPanelProps) {
  const router = useRouter();
  const [isPending, startTransition] = useTransition();
  const [feedback, setFeedback] = useState<{
    type: "ok" | "err";
    text: string;
  } | null>(null);

  const [rows, setRows] = useState(
    items.map((it) => ({
      loan_detail_id: it.loan_detail_id,
      condition_after: ("baik" as EquipmentCondition),
      qty_damaged: 1,
      damage_notes: "",
    }))
  );
  const [finePaid, setFinePaid] = useState(returnData.fine_amount <= 0);
  const [staffNotes, setStaffNotes] = useState(returnData.notes ?? "");

  function updateRow(
    idx: number,
    patch: Partial<{
      condition_after: EquipmentCondition;
      qty_damaged: number;
      damage_notes: string;
    }>
  ) {
    setRows((prev) =>
      prev.map((r, i) => (i === idx ? { ...r, ...patch } : r))
    );
  }

  function handleVerify(e: React.FormEvent) {
    e.preventDefault();
    setFeedback(null);
    startTransition(async () => {
      const res = await verifyReturnServerAction({
        returnId: returnData.id,
        details: rows.map((r, idx) => ({
          loan_detail_id: r.loan_detail_id,
          condition_after: r.condition_after,
          qty_damaged:
            r.condition_after === "rusak_berat"
              ? Math.min(items[idx].qty, Math.max(1, Number(r.qty_damaged)))
              : 0,
          damage_notes: r.damage_notes || null,
        })),
        finePaid,
        notes: staffNotes,
      });
      if (!res.ok) {
        setFeedback({ type: "err", text: res.error || "Gagal memverifikasi." });
      } else {
        setFeedback({
          type: "ok",
          text: res.message || "Pengembalian berhasil diverifikasi.",
        });
        router.refresh();
      }
    });
  }

  function handleMarkPaid() {
    setFeedback(null);
    startTransition(async () => {
      const res = await markFinePaidServerAction(returnData.id);
      if (!res.ok) {
        setFeedback({ type: "err", text: res.error || "Gagal memperbarui." });
      } else {
        setFeedback({
          type: "ok",
          text: res.message || "Denda berhasil ditandai lunas.",
        });
        router.refresh();
      }
    });
  }

  return (
    <div className="space-y-6">
      <div className="flex flex-wrap items-center justify-between gap-3">
        <Link href={backHref} className="btn-secondary btn-xs">
          <ArrowLeft className="h-3.5 w-3.5" />
          <span>Kembali ke Daftar Pengembalian</span>
        </Link>
        <Link href={loanHref} className="btn-secondary btn-xs">
          Lihat Transaksi Pinjaman ({returnData.loan_code})
        </Link>
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

      {/* Summary Header Card */}
      <div className="panel p-5">
        <div className="flex flex-wrap items-start justify-between gap-4 border-b border-slate-100 pb-4">
          <div>
            <div className="flex items-center gap-2.5">
              <span className="font-mono text-base font-bold text-slate-900">
                {returnData.code}
              </span>
              <StatusBadge
                variant={returnData.status}
                label={getReturnStatusLabel(returnData.status)}
              />
            </div>
            <p className="mt-1 text-xs text-slate-500">
              Peminjam: <strong className="text-slate-800">{returnData.borrower_name}</strong>{" "}
              {returnData.borrower_identity ? `(${returnData.borrower_identity})` : ""} · Ref Pinjaman:{" "}
              <span className="font-mono font-semibold">{returnData.loan_code}</span>
            </p>
          </div>

          <div className="rounded-lg border border-slate-200 bg-slate-50 px-4 py-2.5 text-right">
            <span className="block text-[10px] font-semibold uppercase tracking-wider text-slate-500">
              Denda Keterlambatan ({returnData.late_days} hari × {formatRupiah(returnData.fine_rate)})
            </span>
            <span
              className={`text-lg font-bold ${
                returnData.fine_amount > 0 ? "text-rose-700" : "text-emerald-700"
              }`}
            >
              {formatRupiah(returnData.fine_amount)}
            </span>
            {returnData.fine_amount > 0 && (
              <div className="mt-1">
                <StatusBadge
                  variant={returnData.fine_paid ? "paid" : "unpaid"}
                  label={returnData.fine_paid ? "Denda Lunas" : "Belum Dibayar"}
                />
              </div>
            )}
          </div>
        </div>

        <div className="mt-4 grid grid-cols-2 gap-4 text-xs sm:grid-cols-4">
          <div>
            <span className="text-slate-500">Tgl Pinjam</span>
            <p className="mt-0.5 font-semibold text-slate-900">
              {formatDateId(returnData.loan_date)}
            </p>
          </div>
          <div>
            <span className="text-slate-500">Batas Jatuh Tempo</span>
            <p className="mt-0.5 font-semibold text-slate-900">
              {formatDateId(returnData.due_date)}
            </p>
          </div>
          <div>
            <span className="text-slate-500">Tanggal Dikembalikan</span>
            <p className="mt-0.5 font-semibold text-slate-900">
              {formatDateId(returnData.return_date)}
            </p>
          </div>
          <div>
            <span className="text-slate-500">Pemeriksa Fisik</span>
            <p className="mt-0.5 font-semibold text-slate-900">
              {returnData.verified_by_name
                ? `${returnData.verified_by_name} (${formatDateTimeId(returnData.verified_at)})`
                : "Menunggu Pemeriksaan"}
            </p>
          </div>
        </div>

        {returnData.notes && (
          <div className="mt-4 rounded-md bg-slate-50 p-3 text-xs text-slate-700">
            <strong>Catatan Pengembalian:</strong> {returnData.notes}
          </div>
        )}
      </div>

      {/* Inspection Form or Verified Table */}
      {returnData.status === "pending_verification" ? (
        <form onSubmit={handleVerify} className="panel">
          <div className="panel-header">
            <div>
              <h2 className="panel-title">
                Formulir Pemeriksaan Fisik Alat & Verifikasi Pengembalian
              </h2>
              <p className="text-xs text-slate-500">
                Periksa kondisi setiap peralatan. Unit berstatus Rusak Berat otomatis dipisahkan ke stok Maintenance.
              </p>
            </div>
          </div>

          <div className="divide-y divide-slate-200 p-5 space-y-4">
            {items.map((item, idx) => {
              const row = rows[idx];
              return (
                <div
                  key={item.loan_detail_id}
                  className="pt-4 first:pt-0 grid grid-cols-1 gap-4 lg:grid-cols-12"
                >
                  <div className="lg:col-span-5">
                    <span className="font-mono text-[11px] font-bold text-slate-500">
                      {item.equipment_code}
                    </span>
                    <p className="text-sm font-semibold text-slate-900">
                      {item.equipment_name}
                    </p>
                    <p className="mt-0.5 text-xs text-slate-500">
                      Jumlah Dipinjam: <strong>{item.qty} unit</strong> · Kondisi Awal:{" "}
                      <strong>{getConditionLabel(item.condition_before)}</strong>
                    </p>
                  </div>

                  <div className="lg:col-span-3">
                    <label className="input-label">Kondisi Saat Kembali</label>
                    <select
                      value={row.condition_after}
                      onChange={(e) =>
                        updateRow(idx, {
                          condition_after: e.target.value as EquipmentCondition,
                        })
                      }
                      className="input-field"
                    >
                      <option value="baik">Baik (Siap Dipinjam Kembali)</option>
                      <option value="rusak_ringan">
                        Rusak Ringan (Masih Layak Pakai)
                      </option>
                      <option value="rusak_berat">
                        Rusak Berat (Masuk Maintenance)
                      </option>
                    </select>
                  </div>

                  <div className="lg:col-span-4 space-y-2">
                    {row.condition_after === "rusak_berat" && (
                      <div>
                        <label className="input-label">
                          Jumlah Unit Rusak Berat (Maks {item.qty})
                        </label>
                        <input
                          type="number"
                          min={1}
                          max={item.qty}
                          value={row.qty_damaged}
                          onChange={(e) =>
                            updateRow(idx, {
                              qty_damaged: Number(e.target.value),
                            })
                          }
                          className="input-field"
                        />
                      </div>
                    )}
                    <div>
                      <label className="input-label">
                        Catatan Pemeriksaan / Kerusakan (Opsional)
                      </label>
                      <input
                        type="text"
                        placeholder="Contoh: Lensa bersih / Tutup patah..."
                        value={row.damage_notes}
                        onChange={(e) =>
                          updateRow(idx, { damage_notes: e.target.value })
                        }
                        className="input-field"
                      />
                    </div>
                  </div>
                </div>
              );
            })}
          </div>

          <div className="border-t border-slate-200 bg-slate-50 p-5 space-y-4">
            <div>
              <label className="input-label">Catatan Verifikasi Petugas</label>
              <textarea
                rows={2}
                value={staffNotes}
                onChange={(e) => setStaffNotes(e.target.value)}
                placeholder="Catatan kelengkapan pengembalian atau pembayaran denda..."
                className="input-field"
              />
            </div>

            {returnData.fine_amount > 0 && (
              <label className="inline-flex items-center gap-2.5 rounded-md border border-amber-300 bg-amber-50 px-3.5 py-2.5 text-xs font-medium text-amber-900 cursor-pointer">
                <input
                  type="checkbox"
                  checked={finePaid}
                  onChange={(e) => setFinePaid(e.target.checked)}
                  className="h-4 w-4 rounded border-amber-400"
                />
                <span>
                  Tandai denda keterlambatan sebesar{" "}
                  <strong>{formatRupiah(returnData.fine_amount)}</strong> sudah dibayar tunai (Lunas)
                </span>
              </label>
            )}

            <div className="flex justify-end">
              <button
                type="submit"
                disabled={isPending}
                className="btn-emerald"
              >
                {isPending
                  ? "Memproses Verifikasi..."
                  : "Selesaikan Verifikasi & Pulihkan Stok"}
              </button>
            </div>
          </div>
        </form>
      ) : (
        <div className="panel">
          <div className="panel-header">
            <h2 className="panel-title">Hasil Pemeriksaan Fisik Peralatan</h2>
            {returnData.fine_amount > 0 && !returnData.fine_paid && (
              <button
                type="button"
                disabled={isPending}
                onClick={handleMarkPaid}
                className="btn-emerald btn-xs"
              >
                {isPending ? "Memproses..." : "Tandai Denda Lunas"}
              </button>
            )}
          </div>
          <div className="table-container">
            <table className="table-pro">
              <thead>
                <tr>
                  <th>Kode Alat</th>
                  <th>Nama Peralatan</th>
                  <th>Dikembalikan</th>
                  <th>Kondisi Awal</th>
                  <th>Kondisi Akhir</th>
                  <th>Unit Rusak Berat</th>
                  <th>Catatan Fisik</th>
                </tr>
              </thead>
              <tbody>
                {items.map((item) => (
                  <tr key={item.loan_detail_id}>
                    <td className="font-mono text-xs font-semibold">
                      {item.equipment_code}
                    </td>
                    <td className="font-medium text-slate-900">
                      {item.equipment_name}
                    </td>
                    <td>{item.qty} unit</td>
                    <td>
                      <StatusBadge
                        variant={item.condition_before}
                        label={getConditionLabel(item.condition_before)}
                      />
                    </td>
                    <td>
                      <StatusBadge
                        variant={item.verified_condition_after ?? "baik"}
                        label={getConditionLabel(
                          item.verified_condition_after ?? "baik"
                        )}
                      />
                    </td>
                    <td>{item.verified_qty_damaged ?? 0} unit</td>
                    <td className="text-xs text-slate-600">
                      {item.verified_damage_notes || "-"}
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        </div>
      )}
    </div>
  );
}
