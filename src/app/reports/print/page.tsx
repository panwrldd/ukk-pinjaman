import { requireUser } from "@/lib/auth";
import { buildReportData } from "@/lib/services";
import { readDb } from "@/lib/storage";
import {
  formatDateId,
  formatDateTimeId,
  getRoleLabel,
  todayJakarta,
} from "@/lib/types";
import { PrintToolbar } from "./PrintToolbar";

export const dynamic = "force-dynamic";

export default async function ReportPrintPage({
  searchParams,
}: {
  searchParams: Promise<Record<string, string | undefined>>;
}) {
  const user = await requireUser(["admin", "petugas"]);
  const sp = await searchParams;
  const db = await readDb();

  const filters = {
    date_from: sp.date_from || "",
    date_to: sp.date_to || "",
    status: sp.status || "",
    category_id: sp.category_id || "",
    condition: sp.condition || "",
    role: sp.role || "",
  };

  const report = buildReportData(db, sp.type || "loans", filters, user.role);

  return (
    <div className="min-h-screen bg-white p-6 text-slate-900">
      <style>{`
        @page { size: A4 landscape; margin: 12mm; }
        table.print-table { width: 100%; border-collapse: collapse; font-size: 11px; margin-top: 12px; }
        table.print-table th, table.print-table td { border: 1px solid #334155; padding: 6px 8px; text-align: left; vertical-align: top; }
        table.print-table th { background-color: #f1f5f9; font-weight: 700; text-transform: uppercase; font-size: 10px; }
      `}</style>

      <PrintToolbar autoPrint={sp.autoprint === "1"} />

      {/* Official Institution Letterhead */}
      <div className="border-b-2 border-slate-900 pb-3 text-center">
        <h1 className="text-lg font-bold uppercase tracking-wide text-slate-900">
          {report.institution.name}
        </h1>
        <p className="text-xs font-semibold uppercase text-slate-700">
          {report.institution.unit}
        </p>
        <p className="mt-0.5 text-[11px] text-slate-600">
          {report.institution.address} · Telp: {report.institution.phone}
        </p>
      </div>

      <div className="mt-4 flex items-start justify-between text-xs">
        <div>
          <h2 className="text-base font-bold uppercase text-slate-900">
            {report.meta.title}
          </h2>
          <p className="text-slate-600">
            Periode:{" "}
            {filters.date_from || filters.date_to
              ? `${filters.date_from || "Awal"} s/d ${filters.date_to || "Sekarang"}`
              : "Semua Periode Data Aktual"}
          </p>
        </div>
        <div className="text-right text-[11px] text-slate-600">
          <p>
            Tanggal Cetak:{" "}
            <strong>{formatDateTimeId(new Date().toISOString())}</strong>
          </p>
          <p>
            Dicetak Oleh:{" "}
            <strong>
              {user.name} ({getRoleLabel(user.role)})
            </strong>
          </p>
        </div>
      </div>

      {/* Summary Row */}
      <div className="mt-3 grid grid-cols-4 gap-3">
        {report.summary.map((stat) => (
          <div
            key={stat.label}
            className="rounded border border-slate-300 px-3 py-2"
          >
            <span className="block text-[10px] font-semibold uppercase text-slate-500">
              {stat.label}
            </span>
            <span className="text-sm font-bold text-slate-900">
              {stat.value}
            </span>
          </div>
        ))}
      </div>

      {/* Data Table */}
      <table className="print-table">
        <thead>
          <tr>
            {report.headings.map((h) => (
              <th key={h}>{h}</th>
            ))}
          </tr>
        </thead>
        <tbody>
          {report.rows.length === 0 ? (
            <tr>
              <td
                colSpan={report.headings.length}
                style={{ textAlign: "center", padding: "16px" }}
              >
                Tidak ada data laporan pada kriteria ini.
              </td>
            </tr>
          ) : (
            report.rows.map((row, idx) => (
              <tr key={idx}>
                {row.map((cell, cIdx) => (
                  <td key={cIdx}>{cell}</td>
                ))}
              </tr>
            ))
          )}
        </tbody>
      </table>

      {/* Signature Footer */}
      <div className="mt-8 flex justify-end text-xs">
        <div className="w-64 text-center">
          <p>{formatDateId(todayJakarta())}</p>
          <p className="font-semibold">{getRoleLabel(user.role)}</p>
          <div className="h-16" />
          <p className="font-bold underline">{user.name}</p>
          <p className="text-[11px] text-slate-600">
            {user.identity_number
              ? `NIP/ID: ${user.identity_number}`
              : `@${user.username}`}
          </p>
        </div>
      </div>
    </div>
  );
}
