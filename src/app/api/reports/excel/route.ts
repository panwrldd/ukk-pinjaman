import { NextRequest, NextResponse } from "next/server";
import * as XLSX from "xlsx";
import { getCurrentUser } from "@/lib/auth";
import { buildReportData } from "@/lib/services";
import { readDb } from "@/lib/storage";
import { todayJakarta } from "@/lib/types";

export const dynamic = "force-dynamic";

export async function GET(req: NextRequest) {
  const user = await getCurrentUser();
  if (!user || !["admin", "petugas"].includes(user.role)) {
    return NextResponse.json({ error: "Unauthorized" }, { status: 401 });
  }

  const sp = req.nextUrl.searchParams;
  const type = sp.get("type") || "loans";
  const filters = {
    date_from: sp.get("date_from") || undefined,
    date_to: sp.get("date_to") || undefined,
    status: sp.get("status") || undefined,
    category_id: sp.get("category_id") || undefined,
    condition: sp.get("condition") || undefined,
    role: sp.get("role") || undefined,
  };

  const db = await readDb();
  const report = buildReportData(db, type, filters, user.role);

  const sheetRows: Array<Array<string | number>> = [
    [report.institution.name],
    [report.institution.unit],
    [`${report.institution.address} | Telp: ${report.institution.phone}`],
    [],
    [report.meta.title.toUpperCase()],
    [
      `Periode: ${
        filters.date_from || filters.date_to
          ? `${filters.date_from || "Awal"} s/d ${filters.date_to || "Sekarang"}`
          : "Semua Periode Data Aktual"
      }`,
    ],
    [],
    report.headings,
    ...report.rows,
  ];

  const wb = XLSX.utils.book_new();
  const ws = XLSX.utils.aoa_to_sheet(sheetRows);
  XLSX.utils.book_append_sheet(wb, ws, "Laporan GearHub");

  const buf = XLSX.write(wb, { type: "buffer", bookType: "xlsx" });
  const filename = `gearhub-${report.type}-${todayJakarta()}.xlsx`;

  return new NextResponse(buf, {
    status: 200,
    headers: {
      "Content-Type":
        "application/vnd.openxmlformats-officedocument.spreadsheetml.sheet",
      "Content-Disposition": `attachment; filename="${filename}"`,
    },
  });
}
