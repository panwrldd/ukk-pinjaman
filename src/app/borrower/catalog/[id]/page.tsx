import Link from "next/link";
import { notFound } from "next/navigation";
import { ArrowLeft, Plus } from "lucide-react";
import { AppShell } from "@/components/AppShell";
import { StatusBadge } from "@/components/StatusBadge";
import { requireUser } from "@/lib/auth";
import { readDb } from "@/lib/storage";
import { getConditionLabel, getEquipmentStatusLabel } from "@/lib/types";

export const dynamic = "force-dynamic";

export default async function BorrowerCatalogShowPage({
  params,
}: {
  params: Promise<{ id: string }>;
}) {
  const user = await requireUser(["peminjam"]);
  const { id } = await params;
  const db = await readDb();

  const eq = db.equipments.find((e) => e.id === Number(id));
  if (!eq) notFound();

  const category = db.categories.find((c) => c.id === eq.category_id);
  const canBorrow =
    eq.status === "tersedia" &&
    eq.qty_available > 0 &&
    eq.condition !== "rusak_berat" &&
    Boolean(category?.is_active);

  return (
    <AppShell
      user={user}
      header={`Spesifikasi Peralatan — ${eq.code}`}
      subheader={eq.name}
    >
      <div className="mx-auto max-w-3xl space-y-6">
        <Link href="/borrower/catalog" className="btn-secondary btn-xs">
          <ArrowLeft className="h-3.5 w-3.5" />
          <span>Kembali ke Katalog</span>
        </Link>

        <div className="panel p-6 space-y-4">
          <div className="flex flex-wrap items-center justify-between gap-2">
            <span className="font-mono text-sm font-bold text-slate-600">
              {eq.code}
            </span>
            <div className="flex items-center gap-2">
              <StatusBadge
                variant={eq.condition}
                label={`Kondisi: ${getConditionLabel(eq.condition)}`}
              />
              <StatusBadge
                variant={eq.status}
                label={getEquipmentStatusLabel(eq.status)}
              />
            </div>
          </div>

          <h2 className="text-xl font-bold text-slate-900">{eq.name}</h2>
          <p className="text-xs text-slate-500">
            Kategori: <strong>{category?.name ?? "-"}</strong> · Lokasi
            Pengambilan: <strong>{eq.location || "Loket Laboratorium"}</strong>
          </p>

          {eq.photo && (
            <img
              src={eq.photo}
              alt={eq.name}
              className="max-h-64 rounded-lg border border-slate-200 object-cover"
            />
          )}

          {eq.description && (
            <div className="pt-2">
              <h3 className="text-xs font-semibold uppercase tracking-wider text-slate-500">
                Deskripsi & Kelengkapan
              </h3>
              <p className="mt-1 text-sm leading-relaxed text-slate-700">
                {eq.description}
              </p>
            </div>
          )}

          {eq.notes && (
            <div className="rounded-md border border-blue-200 bg-blue-50/50 p-3 text-xs text-blue-900">
              <strong>Ketentuan Peminjaman Alat:</strong> {eq.notes}
            </div>
          )}

          <div className="flex flex-wrap items-center justify-between gap-4 border-t border-slate-100 pt-4">
            <div>
              <span className="text-xs text-slate-500">Ketersediaan Stok:</span>
              <p className="text-lg font-bold text-slate-900">
                {eq.qty_available} unit tersedia{" "}
                <span className="text-xs font-normal text-slate-500">
                  (dari {eq.qty_total} total unit)
                </span>
              </p>
            </div>

            {canBorrow && (
              <Link
                href={`/borrower/loans/create?equipment_id=${eq.id}`}
                className="btn-primary"
              >
                <Plus className="h-4 w-4" />
                <span>Ajukan Peminjaman Alat Ini</span>
              </Link>
            )}
          </div>
        </div>
      </div>
    </AppShell>
  );
}
