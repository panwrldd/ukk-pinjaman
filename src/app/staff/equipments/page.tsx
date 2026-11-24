import { AppShell } from "@/components/AppShell";
import { StatusBadge } from "@/components/StatusBadge";
import { requireUser } from "@/lib/auth";
import { readDb } from "@/lib/storage";
import {
  getConditionLabel,
  getEquipmentStatusLabel,
  getQtyBorrowed,
} from "@/lib/types";

export const dynamic = "force-dynamic";

export default async function StaffEquipmentsMonitorPage() {
  const user = await requireUser(["petugas"]);
  const db = await readDb();
  const catById = new Map(db.categories.map((c) => [c.id, c]));

  const equipments = [...db.equipments].sort((a, b) =>
    a.code.localeCompare(b.code)
  );

  return (
    <AppShell
      user={user}
      header="Pemantauan Kondisi & Stok Peralatan"
      subheader="Posisi ketersediaan unit real-time di lemari penyimpanan, sedang dipinjam, dan antrian maintenance."
    >
      <div className="panel">
        <div className="table-container">
          <table className="table-pro">
            <thead>
              <tr>
                <th>Kode</th>
                <th>Nama Peralatan & Lokasi</th>
                <th>Kategori</th>
                <th>Kondisi</th>
                <th>Tersedia / Total</th>
                <th>Dipinjam</th>
                <th>Rusak / Servis</th>
                <th>Status</th>
              </tr>
            </thead>
            <tbody>
              {equipments.map((eq) => (
                <tr key={eq.id}>
                  <td className="font-mono text-xs font-bold text-slate-900">
                    {eq.code}
                  </td>
                  <td>
                    <p className="font-semibold text-slate-900">{eq.name}</p>
                    <p className="text-xs text-slate-500">
                      {eq.location || "-"}
                    </p>
                  </td>
                  <td className="text-xs">
                    {catById.get(eq.category_id)?.name ?? "-"}
                  </td>
                  <td>
                    <StatusBadge
                      variant={eq.condition}
                      label={getConditionLabel(eq.condition)}
                    />
                  </td>
                  <td className="font-semibold">
                    {eq.qty_available} / {eq.qty_total} unit
                  </td>
                  <td>{getQtyBorrowed(eq)} unit</td>
                  <td>{eq.qty_damaged} unit</td>
                  <td>
                    <StatusBadge
                      variant={eq.status}
                      label={getEquipmentStatusLabel(eq.status)}
                    />
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      </div>
    </AppShell>
  );
}
