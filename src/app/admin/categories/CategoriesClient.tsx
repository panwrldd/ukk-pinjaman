"use client";

import React, { useState, useTransition } from "react";
import { useRouter } from "next/navigation";
import { AlertTriangle, CheckCircle2, Edit2, Plus, Trash2 } from "lucide-react";
import {
  deleteCategoryServerAction,
  saveCategoryServerAction,
} from "@/lib/actions";
import { StatusBadge } from "@/components/StatusBadge";

interface CategoryRow {
  id: number;
  code: string;
  name: string;
  description: string | null;
  is_active: boolean;
  equipment_count: number;
  unit_count: number;
}

export function CategoriesClient({ categories }: { categories: CategoryRow[] }) {
  const router = useRouter();
  const [isPending, startTransition] = useTransition();
  const [editingId, setEditingId] = useState<number | null>(null);
  const [code, setCode] = useState("");
  const [name, setName] = useState("");
  const [description, setDescription] = useState("");
  const [isActive, setIsActive] = useState(true);
  const [msg, setMsg] = useState<{ type: "ok" | "err"; text: string } | null>(
    null
  );

  function startEdit(cat: CategoryRow) {
    setEditingId(cat.id);
    setCode(cat.code);
    setName(cat.name);
    setDescription(cat.description ?? "");
    setIsActive(cat.is_active);
    setMsg(null);
  }

  function resetForm() {
    setEditingId(null);
    setCode("");
    setName("");
    setDescription("");
    setIsActive(true);
  }

  function handleSubmit(e: React.FormEvent) {
    e.preventDefault();
    setMsg(null);
    startTransition(async () => {
      const res = await saveCategoryServerAction({
        id: editingId ?? undefined,
        code,
        name,
        description,
        is_active: isActive,
      });
      if (!res.ok) {
        setMsg({ type: "err", text: res.error || "Gagal menyimpan kategori." });
        return;
      }
      setMsg({ type: "ok", text: res.message || "Berhasil disimpan." });
      resetForm();
      router.refresh();
    });
  }

  function handleDelete(cat: CategoryRow) {
    if (!confirm(`Hapus kategori ${cat.name}?`)) return;
    setMsg(null);
    startTransition(async () => {
      const res = await deleteCategoryServerAction(cat.id);
      if (!res.ok) {
        setMsg({ type: "err", text: res.error || "Gagal menghapus kategori." });
        return;
      }
      setMsg({ type: "ok", text: res.message || "Kategori dihapus." });
      router.refresh();
    });
  }

  return (
    <div className="grid grid-cols-1 gap-6 lg:grid-cols-12">
      <div className="lg:col-span-4">
        <form onSubmit={handleSubmit} className="panel p-5 space-y-4">
          <h2 className="text-sm font-semibold text-slate-900">
            {editingId ? "Edit Kategori Alat" : "Tambah Kategori Baru"}
          </h2>

          {msg && (
            <div
              className={`flex items-start gap-2 rounded-md border p-3 text-xs ${
                msg.type === "ok"
                  ? "border-emerald-200 bg-emerald-50 text-emerald-800"
                  : "border-rose-200 bg-rose-50 text-rose-800"
              }`}
            >
              {msg.type === "ok" ? (
                <CheckCircle2 className="mt-0.5 h-4 w-4 shrink-0 text-emerald-600" />
              ) : (
                <AlertTriangle className="mt-0.5 h-4 w-4 shrink-0 text-rose-600" />
              )}
              <span>{msg.text}</span>
            </div>
          )}

          <div>
            <label className="input-label">Kode Kategori</label>
            <input
              type="text"
              required
              value={code}
              onChange={(e) => setCode(e.target.value.toUpperCase())}
              placeholder="Contoh: KAT-LAB"
              className="input-field font-mono"
            />
          </div>

          <div>
            <label className="input-label">Nama Kategori</label>
            <input
              type="text"
              required
              value={name}
              onChange={(e) => setName(e.target.value)}
              placeholder="Contoh: Perangkat IoT & Sensor"
              className="input-field"
            />
          </div>

          <div>
            <label className="input-label">Deskripsi</label>
            <textarea
              rows={3}
              value={description}
              onChange={(e) => setDescription(e.target.value)}
              placeholder="Keterangan singkat klasifikasi..."
              className="input-field"
            />
          </div>

          <label className="inline-flex items-center gap-2 text-xs font-medium text-slate-700 cursor-pointer">
            <input
              type="checkbox"
              checked={isActive}
              onChange={(e) => setIsActive(e.target.checked)}
              className="rounded border-slate-300"
            />
            <span>Kategori Aktif (Dapat Dipinjam)</span>
          </label>

          <div className="flex items-center gap-2 pt-2">
            <button type="submit" disabled={isPending} className="btn-primary">
              <Plus className="h-3.5 w-3.5" />
              <span>{editingId ? "Simpan Perubahan" : "Tambah Kategori"}</span>
            </button>
            {editingId && (
              <button
                type="button"
                onClick={resetForm}
                className="btn-secondary"
              >
                Batal
              </button>
            )}
          </div>
        </form>
      </div>

      <div className="panel lg:col-span-8">
        <div className="panel-header">
          <h2 className="panel-title">Daftar Kategori Inventaris</h2>
        </div>
        <div className="table-container">
          <table className="table-pro">
            <thead>
              <tr>
                <th>Kode</th>
                <th>Nama & Deskripsi</th>
                <th>Jenis Alat</th>
                <th>Total Unit</th>
                <th>Status</th>
                <th className="text-right">Aksi</th>
              </tr>
            </thead>
            <tbody>
              {categories.map((cat) => (
                <tr key={cat.id}>
                  <td className="font-mono text-xs font-bold text-slate-900">
                    {cat.code}
                  </td>
                  <td>
                    <p className="font-semibold text-slate-900">{cat.name}</p>
                    <p className="text-xs text-slate-500">
                      {cat.description || "-"}
                    </p>
                  </td>
                  <td>{cat.equipment_count} jenis</td>
                  <td>{cat.unit_count} unit</td>
                  <td>
                    <StatusBadge
                      variant={cat.is_active ? "active" : "slate"}
                      label={cat.is_active ? "Aktif" : "Nonaktif"}
                    />
                  </td>
                  <td className="text-right">
                    <div className="inline-flex items-center gap-1.5">
                      <button
                        type="button"
                        onClick={() => startEdit(cat)}
                        className="btn-secondary btn-xs"
                      >
                        <Edit2 className="h-3 w-3" />
                        <span>Edit</span>
                      </button>
                      <button
                        type="button"
                        disabled={isPending}
                        onClick={() => handleDelete(cat)}
                        className="btn-danger-outline btn-xs"
                      >
                        <Trash2 className="h-3 w-3" />
                      </button>
                    </div>
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      </div>
    </div>
  );
}
