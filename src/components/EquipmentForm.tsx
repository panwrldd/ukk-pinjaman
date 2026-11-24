"use client";

import React, { useState, useTransition } from "react";
import Link from "next/link";
import { useRouter } from "next/navigation";
import { AlertTriangle, ArrowLeft, Save } from "lucide-react";
import { saveEquipmentServerAction } from "@/lib/actions";
import {
  Category,
  Equipment,
  EquipmentCondition,
  EquipmentStatus,
} from "@/lib/types";

interface EquipmentFormProps {
  categories: Category[];
  initial?: Equipment;
}

export function EquipmentForm({ categories, initial }: EquipmentFormProps) {
  const router = useRouter();
  const [isPending, startTransition] = useTransition();
  const [error, setError] = useState<string | null>(null);

  const [categoryId, setCategoryId] = useState<number>(
    initial?.category_id ?? categories[0]?.id ?? 1
  );
  const [code, setCode] = useState(initial?.code ?? "");
  const [name, setName] = useState(initial?.name ?? "");
  const [description, setDescription] = useState(initial?.description ?? "");
  const [condition, setCondition] = useState<EquipmentCondition>(
    initial?.condition ?? "baik"
  );
  const [qtyTotal, setQtyTotal] = useState<number>(initial?.qty_total ?? 1);
  const [qtyDamaged, setQtyDamaged] = useState<number>(
    initial?.qty_damaged ?? 0
  );
  const [location, setLocation] = useState(initial?.location ?? "");
  const [status, setStatus] = useState<EquipmentStatus>(
    initial?.status ?? "tersedia"
  );
  const [notes, setNotes] = useState(initial?.notes ?? "");
  const [photo, setPhoto] = useState<string | null>(initial?.photo ?? null);
  const [removePhoto, setRemovePhoto] = useState(false);

  function handleFileChange(e: React.ChangeEvent<HTMLInputElement>) {
    const file = e.target.files?.[0];
    if (!file) return;
    if (file.size > 1.5 * 1024 * 1024) {
      setError("Ukuran foto maksimal 1,5 MB.");
      return;
    }
    const reader = new FileReader();
    reader.onload = () => {
      if (typeof reader.result === "string") {
        setPhoto(reader.result);
        setRemovePhoto(false);
      }
    };
    reader.readAsDataURL(file);
  }

  function handleSubmit(e: React.FormEvent) {
    e.preventDefault();
    setError(null);
    startTransition(async () => {
      const res = await saveEquipmentServerAction({
        id: initial?.id,
        category_id: Number(categoryId),
        code,
        name,
        description,
        photo,
        remove_photo: removePhoto,
        condition,
        qty_total: Number(qtyTotal),
        qty_damaged: Number(qtyDamaged),
        location,
        status,
        notes,
      });
      if (!res.ok) {
        setError(res.error || "Gagal menyimpan peralatan.");
        return;
      }
      router.push(res.redirectTo || "/admin/equipments");
      router.refresh();
    });
  }

  return (
    <div className="mx-auto max-w-3xl space-y-5">
      <Link href="/admin/equipments" className="btn-secondary btn-xs">
        <ArrowLeft className="h-3.5 w-3.5" />
        <span>Kembali ke Inventaris</span>
      </Link>

      {error && (
        <div className="flex items-center gap-2.5 rounded-lg border border-rose-200 bg-rose-50 p-3.5 text-xs text-rose-800">
          <AlertTriangle className="h-4 w-4 shrink-0 text-rose-600" />
          <span>{error}</span>
        </div>
      )}

      <form onSubmit={handleSubmit} className="panel p-6 space-y-4">
        <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
          <div>
            <label className="input-label">Kode Peralatan</label>
            <input
              type="text"
              required
              value={code}
              onChange={(e) => setCode(e.target.value.toUpperCase())}
              placeholder="Contoh: EQP-KAM-004"
              className="input-field font-mono"
            />
          </div>

          <div>
            <label className="input-label">Kategori Alat</label>
            <select
              value={categoryId}
              onChange={(e) => setCategoryId(Number(e.target.value))}
              className="input-field"
            >
              {categories.map((c) => (
                <option key={c.id} value={c.id}>
                  {c.code} — {c.name}
                </option>
              ))}
            </select>
          </div>
        </div>

        <div>
          <label className="input-label">Nama Lengkap Peralatan</label>
          <input
            type="text"
            required
            value={name}
            onChange={(e) => setName(e.target.value)}
            placeholder="Contoh: Kamera Mirrorless Sony Alpha A6400 Kit"
            className="input-field"
          />
        </div>

        <div>
          <label className="input-label">Deskripsi & Kelengkapan Unit</label>
          <textarea
            rows={3}
            value={description}
            onChange={(e) => setDescription(e.target.value)}
            placeholder="Rincian spesifikasi dan kelengkapan bawaan..."
            className="input-field"
          />
        </div>

        <div className="grid grid-cols-1 gap-4 sm:grid-cols-4">
          <div>
            <label className="input-label">Total Stok Unit</label>
            <input
              type="number"
              min={1}
              required
              value={qtyTotal}
              onChange={(e) => setQtyTotal(Number(e.target.value))}
              className="input-field"
            />
          </div>

          <div>
            <label className="input-label">Unit Rusak / Servis</label>
            <input
              type="number"
              min={0}
              max={qtyTotal}
              value={qtyDamaged}
              onChange={(e) => setQtyDamaged(Number(e.target.value))}
              className="input-field"
            />
          </div>

          <div>
            <label className="input-label">Kondisi Dominan</label>
            <select
              value={condition}
              onChange={(e) =>
                setCondition(e.target.value as EquipmentCondition)
              }
              className="input-field"
            >
              <option value="baik">Baik</option>
              <option value="rusak_ringan">Rusak Ringan</option>
              <option value="rusak_berat">Rusak Berat</option>
            </select>
          </div>

          <div>
            <label className="input-label">Status Katalog</label>
            <select
              value={status}
              onChange={(e) => setStatus(e.target.value as EquipmentStatus)}
              className="input-field"
            >
              <option value="tersedia">Tersedia</option>
              <option value="maintenance">Maintenance</option>
              <option value="tidak_tersedia">Tidak Tersedia</option>
            </select>
          </div>
        </div>

        <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
          <div>
            <label className="input-label">Lokasi Penyimpanan</label>
            <input
              type="text"
              value={location}
              onChange={(e) => setLocation(e.target.value)}
              placeholder="Contoh: Lemari Dry Cabinet A-01 (Studio MM)"
              className="input-field"
            />
          </div>
          <div>
            <label className="input-label">Foto Peralatan (Opsional)</label>
            <input
              type="file"
              accept="image/*"
              onChange={handleFileChange}
              className="input-field text-xs"
            />
            {photo && !removePhoto && (
              <div className="mt-2 flex items-center gap-3">
                <img
                  src={photo}
                  alt="Preview"
                  className="h-12 w-12 rounded border border-slate-200 object-cover"
                />
                <button
                  type="button"
                  onClick={() => {
                    setPhoto(null);
                    setRemovePhoto(true);
                  }}
                  className="text-xs font-semibold text-rose-600 hover:underline"
                >
                  Hapus Foto
                </button>
              </div>
            )}
          </div>
        </div>

        <div>
          <label className="input-label">Catatan Penggunaan / SOP Alat</label>
          <input
            type="text"
            value={notes}
            onChange={(e) => setNotes(e.target.value)}
            placeholder="Catatan penting saat peminjaman & pengembalian..."
            className="input-field"
          />
        </div>

        <div className="flex justify-end gap-2 border-t border-slate-100 pt-4">
          <Link href="/admin/equipments" className="btn-secondary">
            Batal
          </Link>
          <button type="submit" disabled={isPending} className="btn-primary">
            <Save className="h-4 w-4" />
            <span>{isPending ? "Menyimpan..." : "Simpan Peralatan"}</span>
          </button>
        </div>
      </form>
    </div>
  );
}
