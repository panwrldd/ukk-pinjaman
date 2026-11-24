"use client";

import React, { useState, useTransition } from "react";
import { useRouter } from "next/navigation";
import { AlertTriangle, CheckCircle2, Trash2, Wrench } from "lucide-react";
import {
  deleteEquipmentServerAction,
  restoreDamagedServerAction,
} from "@/lib/actions";

export function EquipmentDetailActions({
  equipmentId,
  equipmentCode,
  qtyDamaged,
}: {
  equipmentId: number;
  equipmentCode: string;
  qtyDamaged: number;
}) {
  const router = useRouter();
  const [isPending, startTransition] = useTransition();
  const [qtyRestore, setQtyRestore] = useState(Math.max(1, qtyDamaged));
  const [notes, setNotes] = useState("");
  const [msg, setMsg] = useState<{ type: "ok" | "err"; text: string } | null>(
    null
  );

  function handleRestore(e: React.FormEvent) {
    e.preventDefault();
    setMsg(null);
    startTransition(async () => {
      const res = await restoreDamagedServerAction(
        equipmentId,
        Number(qtyRestore),
        notes
      );
      if (!res.ok) {
        setMsg({ type: "err", text: res.error || "Gagal memulihkan unit." });
        return;
      }
      setMsg({ type: "ok", text: res.message || "Unit berhasil dipulihkan." });
      router.refresh();
    });
  }

  function handleDelete() {
    if (!confirm(`Hapus peralatan ${equipmentCode} dari inventaris?`)) return;
    setMsg(null);
    startTransition(async () => {
      const res = await deleteEquipmentServerAction(equipmentId);
      if (!res.ok) {
        setMsg({ type: "err", text: res.error || "Gagal menghapus peralatan." });
        return;
      }
      router.push(res.redirectTo || "/admin/equipments");
      router.refresh();
    });
  }

  return (
    <div className="space-y-4">
      {msg && (
        <div
          className={`flex items-center gap-2 rounded-lg border p-3 text-xs font-medium ${
            msg.type === "ok"
              ? "border-emerald-200 bg-emerald-50 text-emerald-800"
              : "border-rose-200 bg-rose-50 text-rose-800"
          }`}
        >
          {msg.type === "ok" ? (
            <CheckCircle2 className="h-4 w-4 shrink-0 text-emerald-600" />
          ) : (
            <AlertTriangle className="h-4 w-4 shrink-0 text-rose-600" />
          )}
          <span>{msg.text}</span>
        </div>
      )}

      {qtyDamaged > 0 && (
        <form
          onSubmit={handleRestore}
          className="panel border-amber-200 bg-amber-50/40 p-5 space-y-3"
        >
          <div className="flex items-center gap-2 text-sm font-semibold text-amber-900">
            <Wrench className="h-4 w-4 text-amber-700" />
            <span>
              Pemulihan Unit Selesai Servis ({qtyDamaged} unit dalam perbaikan)
            </span>
          </div>
          <div className="grid grid-cols-1 gap-3 sm:grid-cols-3">
            <div>
              <label className="input-label">Jumlah Dipulihkan</label>
              <input
                type="number"
                min={1}
                max={qtyDamaged}
                value={qtyRestore}
                onChange={(e) => setQtyRestore(Number(e.target.value))}
                className="input-field"
              />
            </div>
            <div className="sm:col-span-2">
              <label className="input-label">Catatan Servis (Opsional)</label>
              <input
                type="text"
                value={notes}
                onChange={(e) => setNotes(e.target.value)}
                placeholder="Contoh: Kipas pendingin sudah diganti dan lulus uji..."
                className="input-field"
              />
            </div>
          </div>
          <button type="submit" disabled={isPending} className="btn-emerald btn-xs">
            Pulihkan ke Stok Tersedia
          </button>
        </form>
      )}

      <div className="flex justify-end">
        <button
          type="button"
          disabled={isPending}
          onClick={handleDelete}
          className="btn-danger-outline btn-xs"
        >
          <Trash2 className="h-3.5 w-3.5" />
          <span>Hapus Peralatan</span>
        </button>
      </div>
    </div>
  );
}
