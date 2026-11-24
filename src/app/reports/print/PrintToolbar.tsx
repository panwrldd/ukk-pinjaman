"use client";

import React, { useEffect } from "react";

export function PrintToolbar({ autoPrint }: { autoPrint?: boolean }) {
  useEffect(() => {
    if (autoPrint) {
      const t = setTimeout(() => window.print(), 350);
      return () => clearTimeout(t);
    }
  }, [autoPrint]);

  return (
    <div className="no-print mb-6 flex flex-wrap items-center justify-between gap-3 rounded-lg border border-slate-200 bg-slate-50 px-4 py-3">
      <span className="text-xs font-medium text-slate-600">
        Pratinjau Cetak Resmi — Tekan <strong>Cetak / Simpan PDF</strong> untuk
        mencetak ke printer atau menyimpan sebagai dokumen PDF.
      </span>
      <div className="flex items-center gap-2">
        <button
          type="button"
          onClick={() => window.print()}
          className="btn-primary btn-xs"
        >
          Cetak / Simpan PDF
        </button>
        <button
          type="button"
          onClick={() => window.close()}
          className="btn-secondary btn-xs"
        >
          Tutup Tab
        </button>
      </div>
    </div>
  );
}
