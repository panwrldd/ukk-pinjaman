export type Role = "admin" | "petugas" | "peminjam";
export type EquipmentCondition = "baik" | "rusak_ringan" | "rusak_berat";
export type EquipmentStatus = "tersedia" | "maintenance" | "tidak_tersedia";
export type LoanStatus =
  | "pending"
  | "approved"
  | "borrowed"
  | "overdue"
  | "returned"
  | "rejected"
  | "cancelled";
export type ReturnStatus = "pending_verification" | "verified";

export interface User {
  id: number;
  name: string;
  username: string;
  email: string;
  identity_number: string | null;
  password_hash: string;
  role: Role;
  phone: string | null;
  address: string | null;
  is_active: boolean;
  created_at: string;
  updated_at: string;
}

export interface Category {
  id: number;
  code: string;
  name: string;
  slug: string;
  description: string | null;
  is_active: boolean;
  created_at: string;
  updated_at: string;
}

export interface Equipment {
  id: number;
  category_id: number;
  code: string;
  name: string;
  description: string | null;
  photo: string | null;
  condition: EquipmentCondition;
  qty_total: number;
  qty_available: number;
  qty_damaged: number;
  location: string | null;
  status: EquipmentStatus;
  notes: string | null;
  created_at: string;
  updated_at: string;
}

export interface Loan {
  id: number;
  code: string;
  user_id: number;
  loan_date: string; // YYYY-MM-DD
  due_date: string; // YYYY-MM-DD
  purpose: string;
  notes: string | null;
  status: LoanStatus;
  approved_by: number | null;
  approved_at: string | null;
  handed_over_by: number | null;
  handed_over_at: string | null;
  rejection_reason: string | null;
  created_at: string;
  updated_at: string;
}

export interface LoanDetail {
  id: number;
  loan_id: number;
  equipment_id: number;
  qty: number;
  condition_before: EquipmentCondition;
}

export interface EquipmentReturn {
  id: number;
  code: string;
  loan_id: number;
  returned_by: number;
  return_date: string; // YYYY-MM-DD
  late_days: number;
  fine_per_day_snapshot: number;
  fine_amount: number;
  fine_paid: boolean;
  status: ReturnStatus;
  verified_by: number | null;
  verified_at: string | null;
  notes: string | null;
  created_at: string;
  updated_at: string;
}

export interface ReturnDetail {
  id: number;
  return_id: number;
  loan_detail_id: number;
  equipment_id: number;
  qty_returned: number;
  qty_damaged: number;
  condition_after: EquipmentCondition;
  damage_notes: string | null;
}

export interface ActivityLog {
  id: number;
  user_id: number | null;
  module: string;
  action: string;
  reference_type: string | null;
  reference_id: number | null;
  description: string;
  old_values: Record<string, unknown> | null;
  new_values: Record<string, unknown> | null;
  created_at: string;
}

export interface Setting {
  id: number;
  key: string;
  value: string;
  type: "integer" | "string";
  description: string;
  updated_by: number | null;
  updated_at: string;
}

export interface GearHubDatabase {
  version: number;
  counters: {
    users: number;
    categories: number;
    equipments: number;
    loans: number;
    loan_details: number;
    returns: number;
    return_details: number;
    activity_logs: number;
    settings: number;
  };
  users: User[];
  categories: Category[];
  equipments: Equipment[];
  loans: Loan[];
  loan_details: LoanDetail[];
  returns: EquipmentReturn[];
  return_details: ReturnDetail[];
  activity_logs: ActivityLog[];
  settings: Setting[];
}

export function todayJakarta(): string {
  const parts = new Intl.DateTimeFormat("en-CA", {
    timeZone: "Asia/Jakarta",
    year: "numeric",
    month: "2-digit",
    day: "2-digit",
  }).formatToParts(new Date());
  const y = parts.find((p) => p.type === "year")?.value ?? "2026";
  const m = parts.find((p) => p.type === "month")?.value ?? "01";
  const d = parts.find((p) => p.type === "day")?.value ?? "01";
  return `${y}-${m}-${d}`;
}

export function addDaysToDateStr(dateStr: string, days: number): string {
  const [y, m, d] = dateStr.split("-").map(Number);
  const dt = new Date(Date.UTC(y, m - 1, d));
  dt.setUTCDate(dt.getUTCDate() + days);
  return dt.toISOString().slice(0, 10);
}

/** Equivalent to MariaDB fn_count_late_days(p_due_date, p_return_date) */
export function countLateDays(dueDate: string, returnDate: string): number {
  if (!dueDate || !returnDate) return 0;
  const [dy, dm, dd] = dueDate.slice(0, 10).split("-").map(Number);
  const [ry, rm, rd] = returnDate.slice(0, 10).split("-").map(Number);
  const dueMs = Date.UTC(dy, dm - 1, dd);
  const retMs = Date.UTC(ry, rm - 1, rd);
  const diff = Math.floor((retMs - dueMs) / (1000 * 60 * 60 * 24));
  return diff > 0 ? diff : 0;
}

export function formatRupiah(amount: number): string {
  return "Rp" + Math.round(amount).toLocaleString("id-ID");
}

export function formatNumber(num: number): string {
  return Math.round(num).toLocaleString("id-ID");
}

const MONTHS_ID = [
  "Januari",
  "Februari",
  "Maret",
  "April",
  "Mei",
  "Juni",
  "Juli",
  "Agustus",
  "September",
  "Oktober",
  "November",
  "Desember",
];

const MONTHS_SHORT_ID = [
  "Jan",
  "Feb",
  "Mar",
  "Apr",
  "Mei",
  "Jun",
  "Jul",
  "Agu",
  "Sep",
  "Okt",
  "Nov",
  "Des",
];

export function formatDateId(dateStr?: string | null): string {
  if (!dateStr) return "-";
  const clean = dateStr.slice(0, 10);
  const [y, m, d] = clean.split("-").map(Number);
  if (!y || !m || !d) return dateStr;
  return `${String(d).padStart(2, "0")} ${MONTHS_ID[m - 1] ?? ""} ${y}`;
}

export function formatShortDate(dateStr?: string | null): string {
  if (!dateStr) return "-";
  const clean = dateStr.slice(0, 10);
  const [y, m, d] = clean.split("-");
  if (!y || !m || !d) return dateStr;
  return `${d}/${m}/${y}`;
}

export function formatDateTimeId(iso?: string | null): string {
  if (!iso) return "-";
  const dt = new Date(iso);
  if (Number.isNaN(dt.getTime())) return iso;
  const parts = new Intl.DateTimeFormat("id-ID", {
    timeZone: "Asia/Jakarta",
    day: "2-digit",
    month: "short",
    year: "numeric",
    hour: "2-digit",
    minute: "2-digit",
  }).format(dt);
  return `${parts} WIB`;
}

export function formatRelativeTime(iso?: string | null): string {
  if (!iso) return "-";
  const dt = new Date(iso);
  const diffSec = Math.max(0, Math.floor((Date.now() - dt.getTime()) / 1000));
  if (diffSec < 60) return "Baru saja";
  if (diffSec < 3600) return `${Math.floor(diffSec / 60)} menit lalu`;
  if (diffSec < 86400) return `${Math.floor(diffSec / 3600)} jam lalu`;
  const days = Math.floor(diffSec / 86400);
  if (days < 30) return `${days} hari lalu`;
  return formatShortDate(iso);
}

export function getMonthShortLabel(year: number, monthZeroBased: number): string {
  return `${MONTHS_SHORT_ID[monthZeroBased]} ${String(year).slice(-2)}`;
}

export function getInitials(name: string): string {
  const parts = name
    .replace(/,.*/, "")
    .trim()
    .split(/\s+/)
    .filter(Boolean);
  if (parts.length === 0) return "GH";
  if (parts.length === 1) return parts[0].slice(0, 2).toUpperCase();
  return (parts[0][0] + parts[1][0]).toUpperCase();
}

export function getRoleLabel(role: Role): string {
  switch (role) {
    case "admin":
      return "Administrator";
    case "petugas":
      return "Petugas Lab";
    case "peminjam":
      return "Peminjam";
  }
}

export function getRoleHome(role: Role): string {
  switch (role) {
    case "admin":
      return "/admin/dashboard";
    case "petugas":
      return "/staff/dashboard";
    case "peminjam":
      return "/borrower/dashboard";
  }
}

export function getLoanStatusLabel(status: LoanStatus): string {
  switch (status) {
    case "pending":
      return "Menunggu Persetujuan";
    case "approved":
      return "Disetujui (Siap Diambil)";
    case "borrowed":
      return "Sedang Dipinjam";
    case "overdue":
      return "Terlambat";
    case "returned":
      return "Selesai Dikembalikan";
    case "rejected":
      return "Ditolak";
    case "cancelled":
      return "Dibatalkan";
  }
}

export function getConditionLabel(cond: EquipmentCondition): string {
  switch (cond) {
    case "baik":
      return "Baik";
    case "rusak_ringan":
      return "Rusak Ringan";
    case "rusak_berat":
      return "Rusak Berat";
  }
}

export function getEquipmentStatusLabel(status: EquipmentStatus): string {
  switch (status) {
    case "tersedia":
      return "Tersedia";
    case "maintenance":
      return "Maintenance / Servis";
    case "tidak_tersedia":
      return "Tidak Tersedia";
  }
}

export function getReturnStatusLabel(status: ReturnStatus): string {
  switch (status) {
    case "pending_verification":
      return "Menunggu Verifikasi";
    case "verified":
      return "Terverifikasi";
  }
}

export function getQtyBorrowed(eq: Pick<Equipment, "qty_total" | "qty_available" | "qty_damaged">): number {
  return Math.max(0, eq.qty_total - eq.qty_available - eq.qty_damaged);
}
