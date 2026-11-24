import React from "react";

interface StatusBadgeProps {
  variant?: string;
  label: string;
  className?: string;
}

export function StatusBadge({
  variant = "slate",
  label,
  className = "",
}: StatusBadgeProps) {
  let colorClasses = "bg-slate-100 text-slate-700 ring-slate-500/20";

  switch (variant) {
    case "emerald":
    case "green":
    case "tersedia":
    case "baik":
    case "returned":
    case "verified":
    case "active":
    case "paid":
      colorClasses = "bg-emerald-50 text-emerald-800 ring-emerald-600/20";
      break;
    case "amber":
    case "yellow":
    case "pending":
    case "pending_verification":
    case "rusak_ringan":
    case "maintenance":
      colorClasses = "bg-amber-50 text-amber-800 ring-amber-600/20";
      break;
    case "blue":
    case "approved":
    case "petugas":
      colorClasses = "bg-blue-50 text-blue-800 ring-blue-600/20";
      break;
    case "indigo":
    case "borrowed":
    case "admin":
      colorClasses = "bg-indigo-50 text-indigo-800 ring-indigo-600/20";
      break;
    case "red":
    case "rose":
    case "overdue":
    case "rejected":
    case "rusak_berat":
    case "unpaid":
      colorClasses = "bg-rose-50 text-rose-800 ring-rose-600/20";
      break;
  }

  return (
    <span
      className={`inline-flex items-center gap-1 rounded-md px-2 py-0.5 text-xs font-semibold ring-1 ring-inset ${colorClasses} ${className}`}
    >
      {label}
    </span>
  );
}
