"use client";

import React, { useState, useTransition } from "react";
import Link from "next/link";
import { useRouter } from "next/navigation";
import { AlertTriangle, Plus, Send, Trash2 } from "lucide-react";
import { createLoanServerAction } from "@/lib/actions";

interface AvailableEquipmentOption {
  id: number;
  code: string;
  name: string;
  category_name: string;
  qty_available: number;
}

export function CreateLoanForm({
  equipments,
  defaultEquipmentId,
  defaultLoanDate,
  defaultDueDate,
  maxLoanDays,
  fineRateFormatted,
}: {
  equipments: AvailableEquipmentOption[];
  defaultEquipmentId?: number;
  defaultLoanDate: string;
  defaultDueDate: string;
  maxLoanDays: number;
  fineRateFormatted: string;
}) {
  const router = useRouter();
  const [isPending, startTransition] = useTransition();
  const [error, setError] = useState<string | null>(null);

  const initialEq =
    equipments.find((e) => e.id === defaultEquipmentId)?.id ??
    equipments[0]?.id ??
    0;

  const [loanDate, setLoanDate] = useState(defaultLoanDate);
  const [dueDate, setDueDate] = useState(defaultDueDate);
  const [purpose, setPurpose] = useState("");
  const [notes, setNotes] = useState("");
  const [items, setItems] = useState<Array<{ equipment_id: number; qty: number }>>(
    initialEq ? [{ equipment_id: initialEq, qty: 1 }] : []
  );

  function addItemRow() {
    if (equipments.length === 0) return;
    const used = new Set(items.map((i) => i.equipment_id));
    const next = equipments.find((e) => !used.has(e.id)) ?? equipments[0];
    setItems((prev) => [...prev, { equipment_id: next.id, qty: 1 }]);
  }

  function removeItemRow(idx: number) {
    setItems((prev) => prev.filter((_, i) => i !== idx));
  }

  function updateItemRow(
    idx: number,
    patch: Partial<{ equipment_id: number; qty: number }>
  ) {
    setItems((prev) =>
      prev.map((row, i) => (i === idx ? { ...row, ...patch } : row))
    );
  }

  function handleSubmit(e: React.FormEvent) {
    e.preventDefault();
    setError(null);
    startTransition(async () => {
      const res = await createLoanServerAction({
        loan_date: loanDate,
        due_date: dueDate,
        purpose,
        notes,
        items,
      });
      if (!res.ok) {
        setError(res.error || "Gagal mengirim pengajuan peminjaman.");
        return;
      }
      router.push(res.redirectTo || "/borrower/loans");
      router.refresh();
    });
  }

  return (
    <div className="mx-auto max-w-3xl space-y-5">
      {error && (
        <div className="flex items-center gap-2.5 rounded-lg border border-rose-200 bg-rose-50 p-3.5 text-xs text-rose-800">
          <AlertTriangle className="h-4 w-4 shrink-0 text-rose-600" />
          <span>{error}</span>
        </div>
      )}

      <form onSubmit={handleSubmit} className="panel p-6 space-y-5">
        <div className="rounded-md border border-slate-200 bg-slate-50 p-3.5 text-xs text-slate-600">
          Durasi peminjaman maksimal <strong>{maxLoanDays} hari</strong>.
          Keterlambatan pengembalian dikenakan denda{" "}
          <strong>{fineRateFormatted}/hari</strong>.
        </div>

        <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
          <div>
            <label className="input-label">Tanggal Mulai Pinjam</label>
            <input
              type="date"
              required
              value={loanDate}
              onChange={(e) => setLoanDate(e.target.value)}
              className="input-field"
            />
          </div>
          <div>
            <label className="input-label">Tanggal Rencana Kembali</label>
            <input
              type="date"
              required
              min={loanDate}
              value={dueDate}
              onChange={(e) => setDueDate(e.target.value)}
              className="input-field"
            />
          </div>
        </div>

        <div>
          <label className="input-label">
            Keperluan Kegiatan / Mata Pelajaran
          </label>
          <input
            type="text"
            required
            value={purpose}
            onChange={(e) => setPurpose(e.target.value)}
            placeholder="Contoh: Praktikum Produksi Video Pendek Kelas XII DKV 2"
            className="input-field"
          />
        </div>

        <div className="space-y-3">
          <div className="flex items-center justify-between">
            <label className="input-label mb-0">
              Daftar Peralatan yang Dipinjam
            </label>
            <button
              type="button"
              onClick={addItemRow}
              className="btn-secondary btn-xs"
            >
              <Plus className="h-3.5 w-3.5" />
              <span>Tambah Baris Alat</span>
            </button>
          </div>

          {items.map((row, idx) => {
            const selectedEq = equipments.find((e) => e.id === row.equipment_id);
            const maxQty = selectedEq?.qty_available ?? 1;
            return (
              <div
                key={idx}
                className="flex flex-wrap items-center gap-3 rounded-md border border-slate-200 p-3"
              >
                <div className="flex-1 min-w-[220px]">
                  <select
                    value={row.equipment_id}
                    onChange={(e) =>
                      updateItemRow(idx, {
                        equipment_id: Number(e.target.value),
                        qty: 1,
                      })
                    }
                    className="input-field"
                  >
                    {equipments.map((eq) => (
                      <option key={eq.id} value={eq.id}>
                        [{eq.code}] {eq.name} (Tersedia: {eq.qty_available})
                      </option>
                    ))}
                  </select>
                </div>

                <div className="w-28">
                  <input
                    type="number"
                    min={1}
                    max={maxQty}
                    required
                    value={row.qty}
                    onChange={(e) =>
                      updateItemRow(idx, { qty: Number(e.target.value) })
                    }
                    className="input-field"
                  />
                </div>

                {items.length > 1 && (
                  <button
                    type="button"
                    onClick={() => removeItemRow(idx)}
                    className="btn-danger-outline btn-xs"
                  >
                    <Trash2 className="h-3.5 w-3.5" />
                  </button>
                )}
              </div>
            );
          })}
        </div>

        <div>
          <label className="input-label">Catatan Tambahan (Opsional)</label>
          <textarea
            rows={2}
            value={notes}
            onChange={(e) => setNotes(e.target.value)}
            placeholder="Jam pengambilan atau keterangan tambahan..."
            className="input-field"
          />
        </div>

        <div className="flex justify-end gap-2 border-t border-slate-100 pt-4">
          <Link href="/borrower/catalog" className="btn-secondary">
            Batal
          </Link>
          <button
            type="submit"
            disabled={isPending || items.length === 0}
            className="btn-primary"
          >
            <Send className="h-4 w-4" />
            <span>
              {isPending ? "Mengirim Pengajuan..." : "Kirim Pengajuan Peminjaman"}
            </span>
          </button>
        </div>
      </form>
    </div>
  );
}
