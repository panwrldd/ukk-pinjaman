"use client";

import React, { useState, useTransition } from "react";
import { useRouter } from "next/navigation";
import { AlertTriangle, CheckCircle2, RotateCcw, Save } from "lucide-react";
import {
  resetDemoDataServerAction,
  saveSettingsServerAction,
} from "@/lib/actions";
import { Setting } from "@/lib/types";

export function SettingsForm({ settings }: { settings: Setting[] }) {
  const router = useRouter();
  const [isPending, startTransition] = useTransition();
  const [values, setValues] = useState<Record<string, string>>(() =>
    Object.fromEntries(settings.map((s) => [s.key, s.value]))
  );
  const [msg, setMsg] = useState<{ type: "ok" | "err"; text: string } | null>(
    null
  );

  function handleSave(e: React.FormEvent) {
    e.preventDefault();
    setMsg(null);
    startTransition(async () => {
      const res = await saveSettingsServerAction(values);
      if (!res.ok) {
        setMsg({ type: "err", text: res.error || "Gagal menyimpan pengaturan." });
        return;
      }
      setMsg({ type: "ok", text: res.message || "Pengaturan disimpan." });
      router.refresh();
    });
  }

  function handleResetDemo() {
    if (
      !confirm(
        "Reset seluruh data kembali ke kondisi awal Seeder UKK? Transaksi tambahan baru akan dikembalikan ke data awal."
      )
    )
      return;
    setMsg(null);
    startTransition(async () => {
      const res = await resetDemoDataServerAction();
      if (!res.ok) {
        setMsg({ type: "err", text: res.error || "Gagal mereset data." });
        return;
      }
      setMsg({ type: "ok", text: res.message || "Data berhasil di-reset." });
      router.refresh();
    });
  }

  return (
    <div className="mx-auto max-w-3xl space-y-6">
      {msg && (
        <div
          className={`flex items-center gap-2 rounded-lg border p-3.5 text-xs font-medium ${
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

      <form onSubmit={handleSave} className="panel p-6 space-y-5">
        <div className="border-b border-slate-100 pb-3">
          <h2 className="text-sm font-semibold text-slate-900">
            Parameter Tarif Denda & Kebijakan Peminjaman
          </h2>
          <p className="text-xs text-slate-500">
            Perubahan nilai otomatis dicatat pada log audit sistem.
          </p>
        </div>

        <div className="space-y-4">
          {settings.map((s) => (
            <div key={s.key}>
              <label className="input-label">
                {s.description}{" "}
                <span className="font-mono text-[11px] font-normal text-slate-400">
                  ({s.key})
                </span>
              </label>
              <input
                type={s.type === "integer" ? "number" : "text"}
                min={s.type === "integer" ? 0 : undefined}
                required
                value={values[s.key] ?? ""}
                onChange={(e) =>
                  setValues((prev) => ({ ...prev, [s.key]: e.target.value }))
                }
                className="input-field"
              />
            </div>
          ))}
        </div>

        <div className="flex justify-end border-t border-slate-100 pt-4">
          <button type="submit" disabled={isPending} className="btn-primary">
            <Save className="h-4 w-4" />
            <span>{isPending ? "Menyimpan..." : "Simpan Pengaturan"}</span>
          </button>
        </div>
      </form>

      <div className="panel border-slate-200 p-5 flex flex-wrap items-center justify-between gap-4">
        <div>
          <h3 className="text-sm font-semibold text-slate-900">
            Reset Data Demo Pengujian UKK
          </h3>
          <p className="text-xs text-slate-500">
            Kembalikan seluruh inventaris, akun, dan contoh transaksi ke data awal seeder untuk keperluan simulasi sidang UKK.
          </p>
        </div>
        <button
          type="button"
          disabled={isPending}
          onClick={handleResetDemo}
          className="btn-danger-outline btn-xs"
        >
          <RotateCcw className="h-3.5 w-3.5" />
          <span>Reset ke Data Awal Seeder</span>
        </button>
      </div>
    </div>
  );
}
