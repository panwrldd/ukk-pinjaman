import { hashPassword, verifyPassword } from "./seed";
import { mutateDb, readDb } from "./storage";
import {
  Category,
  Equipment,
  EquipmentCondition,
  EquipmentReturn,
  EquipmentStatus,
  GearHubDatabase,
  Loan,
  LoanStatus,
  Role,
  User,
  countLateDays,
  formatRupiah,
  formatShortDate,
  getConditionLabel,
  getEquipmentStatusLabel,
  getLoanStatusLabel,
  getQtyBorrowed,
  getReturnStatusLabel,
  getRoleLabel,
  todayJakarta,
} from "./types";

export function getSettingValue(db: GearHubDatabase, key: string, fallback: string): string {
  return db.settings.find((s) => s.key === key)?.value ?? fallback;
}

export function getFineRatePerDay(db: GearHubDatabase): number {
  const raw = Number(getSettingValue(db, "fine_rate_per_day", "5000"));
  return Number.isFinite(raw) && raw >= 0 ? raw : 5000;
}

/** Equivalent to MariaDB fn_calculate_fine(p_due_date, p_return_date) */
export function calculateFine(
  db: GearHubDatabase,
  dueDate: string,
  returnDate?: string | null
): { late_days: number; fine_amount: number; fine_rate: number } {
  const actualReturn = returnDate || todayJakarta();
  const late_days = countLateDays(dueDate, actualReturn);
  const fine_rate = getFineRatePerDay(db);
  return {
    late_days,
    fine_rate,
    fine_amount: late_days > 0 ? late_days * fine_rate : 0,
  };
}

function logActivity(
  db: GearHubDatabase,
  params: {
    userId: number | null;
    module: string;
    action: string;
    description: string;
    referenceType?: string | null;
    referenceId?: number | null;
    oldValues?: Record<string, unknown> | null;
    newValues?: Record<string, unknown> | null;
  }
) {
  db.counters.activity_logs += 1;
  db.activity_logs.push({
    id: db.counters.activity_logs,
    user_id: params.userId,
    module: params.module,
    action: params.action,
    reference_type: params.referenceType ?? null,
    reference_id: params.referenceId ?? null,
    description: params.description,
    old_values: params.oldValues ?? null,
    new_values: params.newValues ?? null,
    created_at: new Date().toISOString(),
  });
}

function generateLoanCode(db: GearHubDatabase): string {
  const prefix = `LN-${todayJakarta().replace(/-/g, "-")}`.replace(/-/g, "").replace(/^LN/, "LN-") + "-";
  let maxSeq = 0;
  for (const l of db.loans) {
    if (l.code.startsWith(prefix)) {
      const m = l.code.match(/-(\d{4})$/);
      if (m) maxSeq = Math.max(maxSeq, Number(m[1]));
    }
  }
  return `${prefix}${String(maxSeq + 1).padStart(4, "0")}`;
}

function generateReturnCode(db: GearHubDatabase): string {
  const ymd = todayJakarta().replace(/-/g, "");
  const prefix = `RTN-${ymd}-`;
  let maxSeq = 0;
  for (const r of db.returns) {
    if (r.code.startsWith(prefix)) {
      const m = r.code.match(/-(\d{4})$/);
      if (m) maxSeq = Math.max(maxSeq, Number(m[1]));
    }
  }
  return `${prefix}${String(maxSeq + 1).padStart(4, "0")}`;
}

export async function authenticateUser(
  identity: string,
  password: string
): Promise<{ ok: true; user: User } | { ok: false; error: string }> {
  const clean = identity.trim().toLowerCase();
  if (!clean || !password) {
    return { ok: false, error: "Username/email dan kata sandi wajib diisi." };
  }

  const db = await readDb();
  const found = db.users.find(
    (u) => u.username.toLowerCase() === clean || u.email.toLowerCase() === clean
  );

  if (!found || !verifyPassword(password, found.password_hash)) {
    return {
      ok: false,
      error: "Kredensial yang Anda masukkan tidak cocok dengan data sistem.",
    };
  }

  if (!found.is_active) {
    return {
      ok: false,
      error:
        "Akun Anda berstatus nonaktif. Silakan hubungi Administrator Laboratorium.",
    };
  }

  await mutateDb((draft) => {
    logActivity(draft, {
      userId: found.id,
      module: "auth",
      action: "user_login",
      description: `Pengguna ${found.name} (@${found.username}) berhasil masuk ke sistem.`,
      referenceType: "users",
      referenceId: found.id,
    });
  });

  return { ok: true, user: found };
}

/* ============================================================================
 * LOANS SERVICE (Mirrors LoanService.php + sp_approve_loan + trg_loans_status_audit)
 * ========================================================================== */
export async function createLoanAction(
  data: {
    loan_date: string;
    due_date: string;
    purpose: string;
    notes?: string | null;
    items: Array<{ equipment_id: number; qty: number }>;
  },
  userId: number
): Promise<Loan> {
  return mutateDb((db) => {
    const loanDate = data.loan_date.slice(0, 10);
    const dueDate = data.due_date.slice(0, 10);

    if (!loanDate || !dueDate) {
      throw new Error("Tanggal peminjaman dan batas kembali wajib diisi.");
    }
    if (dueDate < loanDate) {
      throw new Error(
        "Tanggal pengembalian tidak boleh lebih awal dari tanggal peminjaman."
      );
    }

    const maxLoanDays = Number(getSettingValue(db, "max_loan_days", "7"));
    const spanDays = countLateDays(loanDate, dueDate);
    if (maxLoanDays > 0 && spanDays > maxLoanDays) {
      throw new Error(`Durasi peminjaman maksimal adalah ${maxLoanDays} hari.`);
    }

    const maxActiveLoans = Number(getSettingValue(db, "max_active_loans", "3"));
    const activeCount = db.loans.filter(
      (l) =>
        l.user_id === userId &&
        ["pending", "approved", "borrowed", "overdue"].includes(l.status)
    ).length;
    if (maxActiveLoans > 0 && activeCount >= maxActiveLoans) {
      throw new Error(
        `Peminjam telah mencapai batas maksimal ${maxActiveLoans} transaksi peminjaman aktif.`
      );
    }

    if (!data.purpose?.trim()) {
      throw new Error("Keperluan peminjaman wajib diisi.");
    }

    if (!data.items || data.items.length === 0) {
      throw new Error("Pilih minimal satu peralatan untuk dipinjam.");
    }

    const normalized = new Map<number, number>();
    for (const item of data.items) {
      const eqId = Number(item.equipment_id);
      const qty = Number(item.qty);
      if (!Number.isInteger(qty) || qty <= 0) {
        throw new Error("Jumlah peminjaman setiap alat minimal 1 unit.");
      }
      normalized.set(eqId, (normalized.get(eqId) ?? 0) + qty);
    }

    const pendingLoanIds = new Set(
      db.loans
        .filter((l) => l.user_id === userId && l.status === "pending")
        .map((l) => l.id)
    );

    const validatedEquipments = new Map<number, Equipment>();
    for (const [eqId, qty] of normalized.entries()) {
      const hasPendingSame = db.loan_details.some(
        (ld) => ld.equipment_id === eqId && pendingLoanIds.has(ld.loan_id)
      );
      const eq = db.equipments.find((e) => e.id === eqId);
      if (!eq) {
        throw new Error("Data peralatan tidak ditemukan.");
      }
      if (hasPendingSame) {
        throw new Error(
          `Masih terdapat pengajuan berstatus menunggu untuk alat "${eq.name}".`
        );
      }
      const cat = db.categories.find((c) => c.id === eq.category_id);
      if (!cat || !cat.is_active) {
        throw new Error(`Kategori untuk alat "${eq.name}" sedang dinonaktifkan.`);
      }
      if (eq.status !== "tersedia") {
        throw new Error(`Peralatan "${eq.name}" sedang tidak tersedia untuk dipinjam.`);
      }
      if (eq.condition === "rusak_berat") {
        throw new Error(`Peralatan "${eq.name}" dalam kondisi rusak berat.`);
      }
      if (eq.qty_available < qty) {
        throw new Error(
          `Stok "${eq.name}" tidak mencukupi (tersedia: ${eq.qty_available}, diminta: ${qty}).`
        );
      }
      validatedEquipments.set(eqId, eq);
    }

    const nowIso = new Date().toISOString();
    db.counters.loans += 1;
    const loan: Loan = {
      id: db.counters.loans,
      code: generateLoanCode(db),
      user_id: userId,
      loan_date: loanDate,
      due_date: dueDate,
      purpose: data.purpose.trim(),
      notes: data.notes?.trim() || null,
      status: "pending",
      approved_by: null,
      approved_at: null,
      handed_over_by: null,
      handed_over_at: null,
      rejection_reason: null,
      created_at: nowIso,
      updated_at: nowIso,
    };
    db.loans.push(loan);

    for (const [eqId, qty] of normalized.entries()) {
      const eq = validatedEquipments.get(eqId)!;
      db.counters.loan_details += 1;
      db.loan_details.push({
        id: db.counters.loan_details,
        loan_id: loan.id,
        equipment_id: eqId,
        qty,
        condition_before: eq.condition === "rusak_ringan" ? "rusak_ringan" : "baik",
      });
    }

    logActivity(db, {
      userId,
      module: "loans",
      action: "loan_created",
      description: `Pengajuan peminjaman ${loan.code} dibuat dengan ${normalized.size} jenis alat.`,
      referenceType: "loans",
      referenceId: loan.id,
    });

    return loan;
  });
}

export async function approveLoanAction(
  loanId: number,
  staffId: number,
  notes?: string | null
): Promise<Loan> {
  return mutateDb((db) => {
    const loan = db.loans.find((l) => l.id === loanId);
    if (!loan) throw new Error("Data pengajuan peminjaman tidak ditemukan.");
    if (loan.status !== "pending") {
      throw new Error("Hanya pengajuan berstatus menunggu yang dapat disetujui.");
    }

    const details = db.loan_details.filter((d) => d.loan_id === loan.id);
    // Validate all items first (atomic transaction semantics)
    for (const d of details) {
      const eq = db.equipments.find((e) => e.id === d.equipment_id);
      if (!eq || eq.status !== "tersedia") {
        throw new Error(`Alat tidak tersedia untuk dipinjam: ${eq?.name ?? d.equipment_id}`);
      }
      if (eq.qty_available < d.qty) {
        throw new Error(`Stok tidak mencukupi untuk alat: ${eq.name}`);
      }
    }

    const nowIso = new Date().toISOString();
    for (const d of details) {
      const eq = db.equipments.find((e) => e.id === d.equipment_id)!;
      const newAvail = eq.qty_available - d.qty;
      eq.qty_available = newAvail;
      eq.status =
        newAvail > 0
          ? "tersedia"
          : eq.qty_damaged > 0
          ? "maintenance"
          : "tidak_tersedia";
      eq.updated_at = nowIso;
    }

    const oldStatus = loan.status;
    loan.status = "approved";
    loan.approved_by = staffId;
    loan.approved_at = nowIso;
    if (notes && notes.trim()) {
      loan.notes = notes.trim();
    }
    loan.updated_at = nowIso;

    // Trigger audit equivalent (trg_loans_status_audit)
    logActivity(db, {
      userId: staffId,
      module: "db_trigger",
      action: "loan_status_changed",
      description: `Status transaksi peminjaman ${loan.code} berubah dari "${oldStatus}" menjadi "approved"`,
      referenceType: "loans",
      referenceId: loan.id,
      oldValues: { status: oldStatus },
      newValues: { status: "approved" },
    });

    logActivity(db, {
      userId: staffId,
      module: "loans",
      action: "loan_approved",
      description: `Pengajuan peminjaman ${loan.code} disetujui dan stok direservasi.`,
      referenceType: "loans",
      referenceId: loan.id,
    });

    return loan;
  });
}

export async function rejectLoanAction(
  loanId: number,
  staffId: number,
  reason: string
): Promise<Loan> {
  return mutateDb((db) => {
    const cleanReason = reason.trim();
    if (!cleanReason) {
      throw new Error("Alasan penolakan wajib diisi.");
    }
    const loan = db.loans.find((l) => l.id === loanId);
    if (!loan) throw new Error("Data peminjaman tidak ditemukan.");
    if (loan.status !== "pending") {
      throw new Error("Hanya pengajuan berstatus menunggu yang dapat ditolak.");
    }

    const nowIso = new Date().toISOString();
    const oldStatus = loan.status;
    loan.status = "rejected";
    loan.approved_by = staffId;
    loan.approved_at = nowIso;
    loan.rejection_reason = cleanReason;
    loan.updated_at = nowIso;

    logActivity(db, {
      userId: staffId,
      module: "db_trigger",
      action: "loan_status_changed",
      description: `Status transaksi peminjaman ${loan.code} berubah dari "${oldStatus}" menjadi "rejected"`,
      referenceType: "loans",
      referenceId: loan.id,
      oldValues: { status: oldStatus },
      newValues: { status: "rejected" },
    });

    logActivity(db, {
      userId: staffId,
      module: "loans",
      action: "loan_rejected",
      description: `Pengajuan peminjaman ${loan.code} ditolak. Alasan: ${cleanReason}`,
      referenceType: "loans",
      referenceId: loan.id,
    });

    return loan;
  });
}

export async function handOverLoanAction(
  loanId: number,
  staffId: number,
  notes?: string | null
): Promise<Loan> {
  return mutateDb((db) => {
    const loan = db.loans.find((l) => l.id === loanId);
    if (!loan) throw new Error("Data peminjaman tidak ditemukan.");
    if (loan.status !== "approved") {
      throw new Error(
        "Hanya peminjaman berstatus disetujui yang dapat diserahkan alatnya."
      );
    }

    const nowIso = new Date().toISOString();
    const oldStatus = loan.status;
    const today = todayJakarta();
    loan.status = loan.due_date < today ? "overdue" : "borrowed";
    loan.handed_over_by = staffId;
    loan.handed_over_at = nowIso;
    if (notes && notes.trim()) {
      loan.notes = notes.trim();
    }
    loan.updated_at = nowIso;

    logActivity(db, {
      userId: staffId,
      module: "db_trigger",
      action: "loan_status_changed",
      description: `Status transaksi peminjaman ${loan.code} berubah dari "${oldStatus}" menjadi "${loan.status}"`,
      referenceType: "loans",
      referenceId: loan.id,
      oldValues: { status: oldStatus },
      newValues: { status: loan.status },
    });

    logActivity(db, {
      userId: staffId,
      module: "loans",
      action: "loan_handed_over",
      description: `Peralatan untuk peminjaman ${loan.code} telah diserahkan kepada peminjam.`,
      referenceType: "loans",
      referenceId: loan.id,
    });

    return loan;
  });
}

export async function cancelLoanAction(
  loanId: number,
  actorId: number,
  isStaffOrAdmin: boolean
): Promise<Loan> {
  return mutateDb((db) => {
    const loan = db.loans.find((l) => l.id === loanId);
    if (!loan) throw new Error("Data peminjaman tidak ditemukan.");

    if (!isStaffOrAdmin && loan.user_id !== actorId) {
      throw new Error("Anda tidak berhak membatalkan pengajuan milik pengguna lain.");
    }

    const nowIso = new Date().toISOString();
    const oldStatus = loan.status;

    if (loan.status === "pending") {
      loan.status = "cancelled";
      loan.updated_at = nowIso;
    } else if (isStaffOrAdmin && loan.status === "approved") {
      const details = db.loan_details.filter((d) => d.loan_id === loan.id);
      for (const d of details) {
        const eq = db.equipments.find((e) => e.id === d.equipment_id);
        if (eq) {
          const newAvail = eq.qty_available + d.qty;
          eq.qty_available = newAvail;
          eq.status =
            newAvail > 0
              ? "tersedia"
              : eq.qty_damaged > 0
              ? "maintenance"
              : "tidak_tersedia";
          eq.updated_at = nowIso;
        }
      }
      loan.status = "cancelled";
      loan.updated_at = nowIso;
    } else {
      throw new Error("Peminjaman dengan status saat ini tidak dapat dibatalkan.");
    }

    logActivity(db, {
      userId: actorId,
      module: "db_trigger",
      action: "loan_status_changed",
      description: `Status transaksi peminjaman ${loan.code} berubah dari "${oldStatus}" menjadi "cancelled"`,
      referenceType: "loans",
      referenceId: loan.id,
      oldValues: { status: oldStatus },
      newValues: { status: "cancelled" },
    });

    logActivity(db, {
      userId: actorId,
      module: "loans",
      action: "loan_cancelled",
      description: `Peminjaman ${loan.code} dibatalkan.`,
      referenceType: "loans",
      referenceId: loan.id,
    });

    return loan;
  });
}

export async function deleteLoanAction(loanId: number, adminId: number): Promise<void> {
  return mutateDb((db) => {
    const idx = db.loans.findIndex((l) => l.id === loanId);
    if (idx === -1) throw new Error("Data peminjaman tidak ditemukan.");
    const loan = db.loans[idx];
    if (["approved", "borrowed", "overdue"].includes(loan.status)) {
      throw new Error(
        "Peminjaman yang sedang aktif/direservasi tidak dapat dihapus langsung. Batalkan atau selesaikan pengembalian terlebih dahulu."
      );
    }

    const retIds = new Set(
      db.returns.filter((r) => r.loan_id === loan.id).map((r) => r.id)
    );
    db.return_details = db.return_details.filter((rd) => !retIds.has(rd.return_id));
    db.returns = db.returns.filter((r) => r.loan_id !== loan.id);
    db.loan_details = db.loan_details.filter((ld) => ld.loan_id !== loan.id);
    db.loans.splice(idx, 1);

    logActivity(db, {
      userId: adminId,
      module: "loans",
      action: "loan_deleted",
      description: `Data transaksi peminjaman ${loan.code} dihapus oleh Administrator.`,
      referenceType: "loans",
      referenceId: loanId,
    });
  });
}

/* ============================================================================
 * RETURNS SERVICE (Mirrors ReturnService.php + sp_process_return)
 * ========================================================================== */
export async function submitReturnAction(
  loanId: number,
  actorId: number,
  notes?: string | null,
  returnDate?: string | null
): Promise<EquipmentReturn> {
  return mutateDb((db) => {
    const loan = db.loans.find((l) => l.id === loanId);
    if (!loan) throw new Error("Data peminjaman tidak ditemukan.");
    if (!["borrowed", "overdue"].includes(loan.status)) {
      throw new Error(
        "Hanya peminjaman aktif yang sedang dipinjam yang dapat diajukan pengembaliannya."
      );
    }

    const existing = db.returns.find((r) => r.loan_id === loan.id);
    if (existing) {
      throw new Error(
        "Pengajuan pengembalian untuk transaksi ini sudah pernah dibuat."
      );
    }

    const actualReturnDate = returnDate?.slice(0, 10) || todayJakarta();
    const est = calculateFine(db, loan.due_date, actualReturnDate);
    const nowIso = new Date().toISOString();

    db.counters.returns += 1;
    const ret: EquipmentReturn = {
      id: db.counters.returns,
      code: generateReturnCode(db),
      loan_id: loan.id,
      returned_by: actorId,
      return_date: actualReturnDate,
      late_days: est.late_days,
      fine_per_day_snapshot: est.fine_rate,
      fine_amount: est.fine_amount,
      fine_paid: est.fine_amount <= 0,
      status: "pending_verification",
      verified_by: null,
      verified_at: null,
      notes: notes?.trim() || null,
      created_at: nowIso,
      updated_at: nowIso,
    };
    db.returns.push(ret);

    logActivity(db, {
      userId: actorId,
      module: "returns",
      action: "return_submitted",
      description: `Pengajuan pengembalian ${ret.code} untuk pinjaman ${loan.code} dikirim.`,
      referenceType: "returns",
      referenceId: ret.id,
    });

    return ret;
  });
}

export async function verifyReturnAction(
  returnId: number,
  staffId: number,
  details: Array<{
    loan_detail_id: number;
    condition_after: EquipmentCondition;
    qty_damaged?: number;
    damage_notes?: string | null;
  }>,
  finePaid: boolean,
  notes?: string | null
): Promise<EquipmentReturn> {
  return mutateDb((db) => {
    const ret = db.returns.find((r) => r.id === returnId);
    if (!ret) throw new Error("Data pengembalian tidak ditemukan.");
    if (ret.status !== "pending_verification") {
      throw new Error("Data pengembalian ini sudah selesai diverifikasi.");
    }

    const loan = db.loans.find((l) => l.id === ret.loan_id);
    if (!loan || !["borrowed", "overdue"].includes(loan.status)) {
      throw new Error("Status peminjaman tidak valid untuk diverifikasi.");
    }

    const loanDetails = db.loan_details.filter((ld) => ld.loan_id === loan.id);
    if (details.length !== loanDetails.length) {
      throw new Error("Seluruh item peralatan yang dipinjam wajib diperiksa kondisinya.");
    }

    // Clear existing draft details
    db.return_details = db.return_details.filter((rd) => rd.return_id !== ret.id);

    const nowIso = new Date().toISOString();

    for (const item of details) {
      const ld = loanDetails.find((x) => x.id === Number(item.loan_detail_id));
      if (!ld) {
        throw new Error("Item detail peminjaman tidak sesuai dengan transaksi ini.");
      }

      const cond = item.condition_after;
      if (!["baik", "rusak_ringan", "rusak_berat"].includes(cond)) {
        throw new Error("Kondisi pengembalian tidak valid.");
      }

      let qtyDamaged =
        cond === "rusak_berat"
          ? Number(item.qty_damaged ?? ld.qty)
          : 0;
      if (cond === "rusak_berat" && qtyDamaged <= 0) {
        qtyDamaged = ld.qty;
      }
      if (qtyDamaged < 0 || qtyDamaged > ld.qty) {
        throw new Error("Jumlah unit rusak berat tidak valid.");
      }

      const eq = db.equipments.find((e) => e.id === ld.equipment_id);
      if (!eq) throw new Error("Data peralatan tidak ditemukan.");

      const goodQty = ld.qty - qtyDamaged;
      const newAvail = eq.qty_available + goodQty;
      const newDamaged = eq.qty_damaged + qtyDamaged;

      if (newAvail + newDamaged > eq.qty_total) {
        throw new Error(
          "Integritas stok gagal: stok tersedia ditambah stok rusak melebihi total stok."
        );
      }

      let newCond: EquipmentCondition = eq.condition;
      if (newAvail === 0 && newDamaged > 0) {
        newCond = "rusak_berat";
      } else if (cond === "rusak_ringan" && goodQty > 0) {
        newCond = "rusak_ringan";
      }

      eq.qty_available = newAvail;
      eq.qty_damaged = newDamaged;
      eq.condition = newCond;
      eq.status =
        newAvail > 0
          ? "tersedia"
          : newDamaged > 0
          ? "maintenance"
          : "tidak_tersedia";
      eq.updated_at = nowIso;

      db.counters.return_details += 1;
      db.return_details.push({
        id: db.counters.return_details,
        return_id: ret.id,
        loan_detail_id: ld.id,
        equipment_id: ld.equipment_id,
        qty_returned: ld.qty,
        qty_damaged: qtyDamaged,
        condition_after: cond,
        damage_notes: item.damage_notes?.trim() || null,
      });
    }

    const calc = calculateFine(db, loan.due_date, ret.return_date);
    ret.status = "verified";
    ret.verified_by = staffId;
    ret.verified_at = nowIso;
    ret.late_days = calc.late_days;
    ret.fine_per_day_snapshot = calc.fine_rate;
    ret.fine_amount = calc.fine_amount;
    ret.fine_paid = calc.fine_amount <= 0 ? true : Boolean(finePaid);
    if (notes && notes.trim()) {
      ret.notes = notes.trim();
    }
    ret.updated_at = nowIso;

    const oldLoanStatus = loan.status;
    loan.status = "returned";
    loan.updated_at = nowIso;

    logActivity(db, {
      userId: staffId,
      module: "db_trigger",
      action: "loan_status_changed",
      description: `Status transaksi peminjaman ${loan.code} berubah dari "${oldLoanStatus}" menjadi "returned"`,
      referenceType: "loans",
      referenceId: loan.id,
      oldValues: { status: oldLoanStatus },
      newValues: { status: "returned" },
    });

    logActivity(db, {
      userId: staffId,
      module: "returns",
      action: "return_verified",
      description: `Pengembalian ${ret.code} diverifikasi. Keterlambatan: ${ret.late_days} hari, Denda: ${formatRupiah(ret.fine_amount)}.`,
      referenceType: "returns",
      referenceId: ret.id,
    });

    return ret;
  });
}

export async function markFinePaidAction(
  returnId: number,
  staffId: number
): Promise<EquipmentReturn> {
  return mutateDb((db) => {
    const ret = db.returns.find((r) => r.id === returnId);
    if (!ret) throw new Error("Data pengembalian tidak ditemukan.");
    ret.fine_paid = true;
    ret.updated_at = new Date().toISOString();

    logActivity(db, {
      userId: staffId,
      module: "returns",
      action: "fine_marked_paid",
      description: `Pembayaran denda ${formatRupiah(ret.fine_amount)} untuk pengembalian ${ret.code} ditandai lunas.`,
      referenceType: "returns",
      referenceId: ret.id,
    });

    return ret;
  });
}

/* ============================================================================
 * EQUIPMENTS, CATEGORIES, USERS, SETTINGS SERVICES
 * ========================================================================== */
export async function createEquipmentAction(
  data: {
    category_id: number;
    code: string;
    name: string;
    description?: string | null;
    photo?: string | null;
    condition: EquipmentCondition;
    qty_total: number;
    qty_damaged?: number;
    location?: string | null;
    status: EquipmentStatus;
    notes?: string | null;
  },
  actorId: number
): Promise<Equipment> {
  return mutateDb((db) => {
    const code = data.code.trim().toUpperCase();
    if (!code || !data.name.trim()) {
      throw new Error("Kode alat dan nama peralatan wajib diisi.");
    }
    if (db.equipments.some((e) => e.code.toUpperCase() === code)) {
      throw new Error(`Kode alat "${code}" sudah digunakan.`);
    }

    const qtyTotal = Number(data.qty_total);
    const qtyDamaged = Number(data.qty_damaged ?? 0);
    if (qtyTotal < 0 || qtyDamaged < 0 || qtyDamaged > qtyTotal) {
      throw new Error(
        "Jumlah stok tersedia dan rusak tidak boleh melebihi total stok."
      );
    }
    const qtyAvailable = qtyTotal - qtyDamaged;
    let status = data.status;
    if (qtyAvailable === 0 && status === "tersedia") {
      status = qtyDamaged > 0 ? "maintenance" : "tidak_tersedia";
    }

    const nowIso = new Date().toISOString();
    db.counters.equipments += 1;
    const eq: Equipment = {
      id: db.counters.equipments,
      category_id: Number(data.category_id),
      code,
      name: data.name.trim(),
      description: data.description?.trim() || null,
      photo: data.photo || null,
      condition: data.condition,
      qty_total: qtyTotal,
      qty_available: qtyAvailable,
      qty_damaged: qtyDamaged,
      location: data.location?.trim() || null,
      status,
      notes: data.notes?.trim() || null,
      created_at: nowIso,
      updated_at: nowIso,
    };
    db.equipments.push(eq);

    logActivity(db, {
      userId: actorId,
      module: "equipments",
      action: "equipment_created",
      description: `Menambahkan peralatan baru: ${eq.name} (${eq.code}) dengan stok ${eq.qty_total} unit.`,
      referenceType: "equipments",
      referenceId: eq.id,
      newValues: {
        code: eq.code,
        name: eq.name,
        qty_total: eq.qty_total,
        qty_available: eq.qty_available,
        condition: eq.condition,
        status: eq.status,
      },
    });

    return eq;
  });
}

export async function updateEquipmentAction(
  equipmentId: number,
  data: {
    category_id: number;
    code: string;
    name: string;
    description?: string | null;
    photo?: string | null;
    remove_photo?: boolean;
    condition: EquipmentCondition;
    qty_total: number;
    qty_damaged: number;
    location?: string | null;
    status: EquipmentStatus;
    notes?: string | null;
  },
  actorId: number
): Promise<Equipment> {
  return mutateDb((db) => {
    const eq = db.equipments.find((e) => e.id === equipmentId);
    if (!eq) throw new Error("Data peralatan tidak ditemukan.");

    const code = data.code.trim().toUpperCase();
    if (db.equipments.some((e) => e.id !== eq.id && e.code.toUpperCase() === code)) {
      throw new Error(`Kode alat "${code}" sudah digunakan pada alat lain.`);
    }

    const oldSnapshot = {
      code: eq.code,
      name: eq.name,
      qty_total: eq.qty_total,
      qty_available: eq.qty_available,
      qty_damaged: eq.qty_damaged,
      condition: eq.condition,
      status: eq.status,
    };

    const currentlyBorrowed = getQtyBorrowed(eq);
    const newTotal = Number(data.qty_total);
    const newDamaged = Number(data.qty_damaged ?? eq.qty_damaged);

    if (newTotal < currentlyBorrowed + newDamaged) {
      throw new Error(
        `Total stok tidak dapat dikurangi di bawah jumlah unit sedang dipinjam (${currentlyBorrowed}) ditambah unit rusak (${newDamaged}).`
      );
    }

    const newAvailable = newTotal - currentlyBorrowed - newDamaged;
    let status = data.status;
    if (newAvailable === 0 && status === "tersedia") {
      status = newDamaged > 0 ? "maintenance" : "tidak_tersedia";
    }

    eq.category_id = Number(data.category_id);
    eq.code = code;
    eq.name = data.name.trim();
    eq.description = data.description?.trim() || null;
    if (data.remove_photo) {
      eq.photo = null;
    } else if (data.photo) {
      eq.photo = data.photo;
    }
    eq.condition = data.condition;
    eq.qty_total = newTotal;
    eq.qty_available = newAvailable;
    eq.qty_damaged = newDamaged;
    eq.location = data.location?.trim() || null;
    eq.status = status;
    eq.notes = data.notes?.trim() || null;
    eq.updated_at = new Date().toISOString();

    logActivity(db, {
      userId: actorId,
      module: "equipments",
      action: "equipment_updated",
      description: `Memperbarui data peralatan: ${eq.name} (${eq.code}).`,
      referenceType: "equipments",
      referenceId: eq.id,
      oldValues: oldSnapshot,
      newValues: {
        code: eq.code,
        name: eq.name,
        qty_total: eq.qty_total,
        qty_available: eq.qty_available,
        qty_damaged: eq.qty_damaged,
        condition: eq.condition,
        status: eq.status,
      },
    });

    return eq;
  });
}

export async function restoreDamagedUnitsAction(
  equipmentId: number,
  qtyToRestore: number,
  actorId: number,
  notes?: string | null
): Promise<Equipment> {
  return mutateDb((db) => {
    const eq = db.equipments.find((e) => e.id === equipmentId);
    if (!eq) throw new Error("Data peralatan tidak ditemukan.");
    if (qtyToRestore <= 0 || qtyToRestore > eq.qty_damaged) {
      throw new Error("Jumlah unit yang dipulihkan melebihi jumlah unit dalam perbaikan.");
    }

    eq.qty_damaged -= qtyToRestore;
    eq.qty_available += qtyToRestore;
    if (eq.qty_damaged === 0) {
      eq.condition = "baik";
    }
    eq.status = eq.qty_available > 0 ? "tersedia" : "tidak_tersedia";
    if (notes && notes.trim()) {
      eq.notes = notes.trim();
    }
    eq.updated_at = new Date().toISOString();

    logActivity(db, {
      userId: actorId,
      module: "equipments",
      action: "equipment_restored",
      description: `Memulihkan ${qtyToRestore} unit ${eq.name} (${eq.code}) dari status rusak/maintenance ke stok tersedia.`,
      referenceType: "equipments",
      referenceId: eq.id,
    });

    return eq;
  });
}

export async function deleteEquipmentAction(
  equipmentId: number,
  actorId: number
): Promise<void> {
  return mutateDb((db) => {
    const idx = db.equipments.findIndex((e) => e.id === equipmentId);
    if (idx === -1) throw new Error("Data peralatan tidak ditemukan.");
    const eq = db.equipments[idx];

    if (db.loan_details.some((ld) => ld.equipment_id === eq.id)) {
      throw new Error(
        "Peralatan ini tidak dapat dihapus karena sudah tercatat dalam riwayat transaksi peminjaman. Ubah statusnya menjadi Tidak Tersedia."
      );
    }

    db.equipments.splice(idx, 1);
    logActivity(db, {
      userId: actorId,
      module: "equipments",
      action: "equipment_deleted",
      description: `Menghapus peralatan: ${eq.name} (${eq.code}).`,
      referenceType: "equipments",
      referenceId: equipmentId,
    });
  });
}

export async function saveCategoryAction(
  data: {
    id?: number;
    code: string;
    name: string;
    description?: string | null;
    is_active: boolean;
  },
  actorId: number
): Promise<Category> {
  return mutateDb((db) => {
    const code = data.code.trim().toUpperCase();
    const name = data.name.trim();
    if (!code || !name) {
      throw new Error("Kode dan nama kategori wajib diisi.");
    }
    const slug = name
      .toLowerCase()
      .replace(/[^a-z0-9]+/g, "-")
      .replace(/^-|-$/g, "");
    const nowIso = new Date().toISOString();

    if (data.id) {
      const cat = db.categories.find((c) => c.id === data.id);
      if (!cat) throw new Error("Data kategori tidak ditemukan.");
      if (db.categories.some((c) => c.id !== cat.id && c.code === code)) {
        throw new Error(`Kode kategori "${code}" sudah digunakan.`);
      }
      cat.code = code;
      cat.name = name;
      cat.slug = slug;
      cat.description = data.description?.trim() || null;
      cat.is_active = data.is_active;
      cat.updated_at = nowIso;

      logActivity(db, {
        userId: actorId,
        module: "categories",
        action: "category_updated",
        description: `Memperbarui kategori alat: ${cat.name} (${cat.code}).`,
        referenceType: "categories",
        referenceId: cat.id,
      });
      return cat;
    }

    if (db.categories.some((c) => c.code === code)) {
      throw new Error(`Kode kategori "${code}" sudah digunakan.`);
    }

    db.counters.categories += 1;
    const cat: Category = {
      id: db.counters.categories,
      code,
      name,
      slug,
      description: data.description?.trim() || null,
      is_active: data.is_active,
      created_at: nowIso,
      updated_at: nowIso,
    };
    db.categories.push(cat);

    logActivity(db, {
      userId: actorId,
      module: "categories",
      action: "category_created",
      description: `Menambahkan kategori alat baru: ${cat.name} (${cat.code}).`,
      referenceType: "categories",
      referenceId: cat.id,
    });

    return cat;
  });
}

export async function deleteCategoryAction(
  categoryId: number,
  actorId: number
): Promise<void> {
  return mutateDb((db) => {
    const idx = db.categories.findIndex((c) => c.id === categoryId);
    if (idx === -1) throw new Error("Kategori tidak ditemukan.");
    if (db.equipments.some((e) => e.category_id === categoryId)) {
      throw new Error(
        "Kategori tidak dapat dihapus karena masih memiliki peralatan terdaftar."
      );
    }
    const cat = db.categories[idx];
    db.categories.splice(idx, 1);
    logActivity(db, {
      userId: actorId,
      module: "categories",
      action: "category_deleted",
      description: `Menghapus kategori alat: ${cat.name} (${cat.code}).`,
      referenceType: "categories",
      referenceId: categoryId,
    });
  });
}

export async function saveUserAction(
  data: {
    id?: number;
    name: string;
    username: string;
    email: string;
    identity_number?: string | null;
    password?: string | null;
    role: Role;
    phone?: string | null;
    address?: string | null;
    is_active: boolean;
  },
  actorId: number
): Promise<User> {
  return mutateDb((db) => {
    const username = data.username.trim().toLowerCase();
    const email = data.email.trim().toLowerCase();
    const name = data.name.trim();

    if (!name || !username || !email) {
      throw new Error("Nama lengkap, username, dan email wajib diisi.");
    }

    const dupUser = db.users.find(
      (u) =>
        u.id !== data.id &&
        (u.username.toLowerCase() === username || u.email.toLowerCase() === email)
    );
    if (dupUser) {
      throw new Error("Username atau email sudah terdaftar pada pengguna lain.");
    }

    const nowIso = new Date().toISOString();

    if (data.id) {
      const user = db.users.find((u) => u.id === data.id);
      if (!user) throw new Error("Data pengguna tidak ditemukan.");
      if (user.id === actorId && !data.is_active) {
        throw new Error("Anda tidak dapat menonaktifkan akun Anda sendiri.");
      }

      user.name = name;
      user.username = username;
      user.email = email;
      user.identity_number = data.identity_number?.trim() || null;
      user.role = data.role;
      user.phone = data.phone?.trim() || null;
      user.address = data.address?.trim() || null;
      user.is_active = data.is_active;
      if (data.password && data.password.trim()) {
        if (data.password.trim().length < 6) {
          throw new Error("Kata sandi minimal 6 karakter.");
        }
        user.password_hash = hashPassword(data.password.trim());
      }
      user.updated_at = nowIso;

      logActivity(db, {
        userId: actorId,
        module: "users",
        action: "user_updated",
        description: `Memperbarui akun pengguna: ${user.name} (@${user.username}).`,
        referenceType: "users",
        referenceId: user.id,
      });

      return user;
    }

    if (!data.password || data.password.trim().length < 6) {
      throw new Error("Kata sandi wajib diisi minimal 6 karakter.");
    }

    db.counters.users += 1;
    const newUser: User = {
      id: db.counters.users,
      name,
      username,
      email,
      identity_number: data.identity_number?.trim() || null,
      password_hash: hashPassword(data.password.trim()),
      role: data.role,
      phone: data.phone?.trim() || null,
      address: data.address?.trim() || null,
      is_active: data.is_active,
      created_at: nowIso,
      updated_at: nowIso,
    };
    db.users.push(newUser);

    logActivity(db, {
      userId: actorId,
      module: "users",
      action: "user_created",
      description: `Menambahkan akun pengguna baru: ${newUser.name} (@${newUser.username}) sebagai ${getRoleLabel(newUser.role)}.`,
      referenceType: "users",
      referenceId: newUser.id,
    });

    return newUser;
  });
}

export async function toggleUserStatusAction(
  targetUserId: number,
  actorId: number
): Promise<User> {
  return mutateDb((db) => {
    if (targetUserId === actorId) {
      throw new Error("Anda tidak dapat menonaktifkan akun Anda sendiri.");
    }
    const user = db.users.find((u) => u.id === targetUserId);
    if (!user) throw new Error("Data pengguna tidak ditemukan.");
    user.is_active = !user.is_active;
    user.updated_at = new Date().toISOString();

    logActivity(db, {
      userId: actorId,
      module: "users",
      action: "user_status_toggled",
      description: `Status akun ${user.name} (@${user.username}) diubah menjadi ${user.is_active ? "Aktif" : "Nonaktif"}.`,
      referenceType: "users",
      referenceId: user.id,
    });

    return user;
  });
}

export async function deleteUserAction(
  targetUserId: number,
  actorId: number
): Promise<void> {
  return mutateDb((db) => {
    if (targetUserId === actorId) {
      throw new Error("Anda tidak dapat menghapus akun Anda sendiri.");
    }
    const idx = db.users.findIndex((u) => u.id === targetUserId);
    if (idx === -1) throw new Error("Pengguna tidak ditemukan.");
    const user = db.users[idx];

    const hasHistory =
      db.loans.some(
        (l) =>
          l.user_id === user.id ||
          l.approved_by === user.id ||
          l.handed_over_by === user.id
      ) ||
      db.returns.some((r) => r.returned_by === user.id || r.verified_by === user.id);

    if (hasHistory) {
      throw new Error(
        "Pengguna ini memiliki riwayat transaksi sehingga tidak dapat dihapus. Silakan nonaktifkan status akunnya."
      );
    }

    db.users.splice(idx, 1);
    logActivity(db, {
      userId: actorId,
      module: "users",
      action: "user_deleted",
      description: `Menghapus akun pengguna: ${user.name} (@${user.username}).`,
      referenceType: "users",
      referenceId: targetUserId,
    });
  });
}

export async function updateSettingsAction(
  updates: Record<string, string>,
  actorId: number
): Promise<void> {
  return mutateDb((db) => {
    const nowIso = new Date().toISOString();
    let changedCount = 0;

    for (const setting of db.settings) {
      if (Object.prototype.hasOwnProperty.call(updates, setting.key)) {
        const nextVal = String(updates[setting.key] ?? "").trim();
        if (nextVal !== setting.value) {
          const oldVal = setting.value;
          setting.value = nextVal;
          setting.updated_by = actorId;
          setting.updated_at = nowIso;
          changedCount += 1;

          // Equivalent to trg_settings_value_audit
          logActivity(db, {
            userId: actorId,
            module: "db_trigger",
            action: "setting_updated",
            description: `Konfigurasi "${setting.key}" diperbarui dari "${oldVal}" menjadi "${nextVal}"`,
            referenceType: "settings",
            referenceId: setting.id,
            oldValues: { key: setting.key, value: oldVal },
            newValues: { key: setting.key, value: nextVal },
          });
        }
      }
    }

    if (changedCount > 0) {
      logActivity(db, {
        userId: actorId,
        module: "settings",
        action: "settings_saved",
        description: `Memperbarui ${changedCount} parameter pengaturan sistem & tarif denda.`,
      });
    }
  });
}

/* ============================================================================
 * REPORTS BUILDER (Mirrors ReportService.php — all 7 official UKK reports)
 * ========================================================================== */
export interface ReportDefinition {
  title: string;
  subtitle: string;
  roles: Role[];
}

export const REPORT_CATALOG: Record<string, ReportDefinition> = {
  loans: {
    title: "Laporan Peminjaman Alat",
    subtitle:
      "Rekapitulasi seluruh pengajuan dan transaksi peminjaman peralatan laboratorium.",
    roles: ["admin", "petugas"],
  },
  returns: {
    title: "Laporan Pengembalian Alat",
    subtitle:
      "Data verifikasi pengembalian, pemeriksaan kondisi fisik alat, dan status penyelesaian.",
    roles: ["admin", "petugas"],
  },
  overdue: {
    title: "Laporan Keterlambatan",
    subtitle:
      "Daftar peminjaman yang melewati batas waktu pengembalian (aktif maupun selesai terlambat).",
    roles: ["admin", "petugas"],
  },
  fines: {
    title: "Laporan Denda Keterlambatan",
    subtitle:
      "Rincian perhitungan denda keterlambatan beserta status pembayaran tunai/lunas.",
    roles: ["admin", "petugas"],
  },
  equipments: {
    title: "Laporan Inventaris Data Alat",
    subtitle:
      "Posisi stok total, stok tersedia, unit sedang dipinjam, dan unit rusak/maintenance.",
    roles: ["admin", "petugas"],
  },
  categories: {
    title: "Laporan Data Kategori Alat",
    subtitle:
      "Daftar klasifikasi inventaris peralatan beserta ringkasan jumlah jenis dan unit alat.",
    roles: ["admin", "petugas"],
  },
  users: {
    title: "Laporan Data Pengguna Sistem",
    subtitle:
      "Daftar akun Administrator, Petugas Laboratorium, dan Peminjam terdaftar.",
    roles: ["admin"],
  },
};

export interface ReportFilters {
  date_from?: string;
  date_to?: string;
  status?: string;
  category_id?: string;
  condition?: string;
  role?: string;
}

export interface BuiltReport {
  type: string;
  meta: ReportDefinition;
  summary: Array<{ label: string; value: string }>;
  headings: string[];
  rows: Array<Array<string | number>>;
  institution: {
    name: string;
    unit: string;
    address: string;
    phone: string;
  };
}

export function buildReportData(
  db: GearHubDatabase,
  type: string,
  filters: ReportFilters,
  actorRole: Role
): BuiltReport {
  const safeType =
    REPORT_CATALOG[type] && REPORT_CATALOG[type].roles.includes(actorRole)
      ? type
      : "loans";
  const meta = REPORT_CATALOG[safeType];

  const institution = {
    name: getSettingValue(
      db,
      "institution_name",
      "SMK Negeri 1 Rekayasa & Multimedia"
    ),
    unit: getSettingValue(
      db,
      "institution_unit",
      "Unit Pelaksana Teknis Laboratorium RPL & Multimedia"
    ),
    address: getSettingValue(
      db,
      "institution_address",
      "Jl. Pendidikan Teknologi No. 45, Gedung Laboratorium Terpadu Lt. 2"
    ),
    phone: getSettingValue(db, "institution_phone", "(021) 5589-2410"),
  };

  const userById = new Map(db.users.map((u) => [u.id, u]));
  const eqById = new Map(db.equipments.map((e) => [e.id, e]));
  const catById = new Map(db.categories.map((c) => [c.id, c]));

  if (safeType === "equipments") {
    const list = db.equipments
      .filter((eq) => {
        if (filters.category_id && eq.category_id !== Number(filters.category_id))
          return false;
        if (filters.status && eq.status !== filters.status) return false;
        if (filters.condition && eq.condition !== filters.condition) return false;
        return true;
      })
      .sort((a, b) => a.code.localeCompare(b.code));

    const totalUnits = list.reduce((s, e) => s + e.qty_total, 0);
    const availUnits = list.reduce((s, e) => s + e.qty_available, 0);
    const dmgUnits = list.reduce((s, e) => s + e.qty_damaged, 0);

    return {
      type: safeType,
      meta,
      institution,
      summary: [
        { label: "Total Jenis Alat", value: String(list.length) },
        { label: "Total Unit Inventaris", value: `${totalUnits} Unit` },
        { label: "Unit Siap Dipinjam", value: `${availUnits} Unit` },
        { label: "Unit Rusak / Maintenance", value: `${dmgUnits} Unit` },
      ],
      headings: [
        "No",
        "Kode Alat",
        "Nama Peralatan",
        "Kategori",
        "Lokasi Penyimpanan",
        "Kondisi",
        "Total Stok",
        "Tersedia",
        "Dipinjam",
        "Rusak / Maintenance",
        "Status",
      ],
      rows: list.map((eq, i) => [
        i + 1,
        eq.code,
        eq.name,
        catById.get(eq.category_id)?.name ?? "-",
        eq.location ?? "-",
        getConditionLabel(eq.condition),
        eq.qty_total,
        eq.qty_available,
        getQtyBorrowed(eq),
        eq.qty_damaged,
        getEquipmentStatusLabel(eq.status),
      ]),
    };
  }

  if (safeType === "categories") {
    const list = db.categories
      .filter((c) => {
        if (filters.status === "active") return c.is_active;
        if (filters.status === "inactive") return !c.is_active;
        return true;
      })
      .sort((a, b) => a.code.localeCompare(b.code));

    let totalEqTypes = 0;
    let totalUnits = 0;

    const rows = list.map((cat, i) => {
      const eqs = db.equipments.filter((e) => e.category_id === cat.id);
      const units = eqs.reduce((s, e) => s + e.qty_total, 0);
      const avail = eqs.reduce((s, e) => s + e.qty_available, 0);
      totalEqTypes += eqs.length;
      totalUnits += units;
      return [
        i + 1,
        cat.code,
        cat.name,
        cat.description ?? "-",
        eqs.length,
        units,
        avail,
        cat.is_active ? "Aktif" : "Nonaktif",
      ];
    });

    return {
      type: safeType,
      meta,
      institution,
      summary: [
        { label: "Total Kategori", value: String(list.length) },
        {
          label: "Kategori Aktif",
          value: String(list.filter((c) => c.is_active).length),
        },
        { label: "Total Jenis Alat", value: String(totalEqTypes) },
        { label: "Total Unit Terdaftar", value: String(totalUnits) },
      ],
      headings: [
        "No",
        "Kode Kategori",
        "Nama Kategori",
        "Deskripsi",
        "Jumlah Jenis Alat",
        "Total Unit",
        "Unit Tersedia",
        "Status Kategori",
      ],
      rows,
    };
  }

  if (safeType === "users") {
    const list = db.users
      .filter((u) => {
        if (filters.role && u.role !== filters.role) return false;
        if (filters.status === "active") return u.is_active;
        if (filters.status === "inactive") return !u.is_active;
        return true;
      })
      .sort((a, b) => a.role.localeCompare(b.role) || a.name.localeCompare(b.name));

    return {
      type: safeType,
      meta,
      institution,
      summary: [
        { label: "Total Pengguna", value: String(list.length) },
        {
          label: "Administrator",
          value: String(list.filter((u) => u.role === "admin").length),
        },
        {
          label: "Petugas Lab",
          value: String(list.filter((u) => u.role === "petugas").length),
        },
        {
          label: "Peminjam",
          value: String(list.filter((u) => u.role === "peminjam").length),
        },
      ],
      headings: [
        "No",
        "Nama Lengkap",
        "Username",
        "Email",
        "No. Identitas (NIS/NIP)",
        "Role",
        "No. Telepon",
        "Total Pinjaman",
        "Status Akun",
      ],
      rows: list.map((u, i) => [
        i + 1,
        u.name,
        u.username,
        u.email,
        u.identity_number ?? "-",
        getRoleLabel(u.role),
        u.phone ?? "-",
        db.loans.filter((l) => l.user_id === u.id).length,
        u.is_active ? "Aktif" : "Nonaktif",
      ]),
    };
  }

  if (safeType === "returns") {
    const list = db.returns
      .filter((r) => {
        if (filters.date_from && r.return_date < filters.date_from) return false;
        if (filters.date_to && r.return_date > filters.date_to) return false;
        if (filters.status && r.status !== filters.status) return false;
        if (filters.category_id) {
          const catId = Number(filters.category_id);
          const lds = db.loan_details.filter((d) => d.loan_id === r.loan_id);
          if (!lds.some((d) => eqById.get(d.equipment_id)?.category_id === catId))
            return false;
        }
        return true;
      })
      .sort((a, b) => b.return_date.localeCompare(a.return_date) || b.id - a.id);

    const totalFines = list.reduce((s, r) => s + r.fine_amount, 0);

    return {
      type: safeType,
      meta,
      institution,
      summary: [
        { label: "Total Pengembalian", value: String(list.length) },
        {
          label: "Terverifikasi",
          value: String(list.filter((r) => r.status === "verified").length),
        },
        {
          label: "Menunggu Verifikasi",
          value: String(
            list.filter((r) => r.status === "pending_verification").length
          ),
        },
        { label: "Total Denda Tercatat", value: formatRupiah(totalFines) },
      ],
      headings: [
        "No",
        "Kode Pengembalian",
        "Kode Pinjam",
        "Peminjam",
        "Tgl Jatuh Tempo",
        "Tgl Kembali",
        "Terlambat (Hari)",
        "Kondisi Kembali",
        "Nominal Denda",
        "Petugas Pemeriksa",
        "Status",
      ],
      rows: list.map((ret, i) => {
        const loan = db.loans.find((l) => l.id === ret.loan_id);
        const rds = db.return_details.filter((d) => d.return_id === ret.id);
        const condText =
          rds.length > 0
            ? rds
                .map(
                  (d) =>
                    `${eqById.get(d.equipment_id)?.code ?? "-"}: ${getConditionLabel(d.condition_after)}`
                )
                .join("; ")
            : "Belum diperiksa";
        return [
          i + 1,
          ret.code,
          loan?.code ?? "-",
          userById.get(ret.returned_by)?.name ?? "-",
          formatShortDate(loan?.due_date),
          formatShortDate(ret.return_date),
          `${ret.late_days} hari`,
          condText,
          formatRupiah(ret.fine_amount),
          ret.verified_by ? userById.get(ret.verified_by)?.name ?? "-" : "-",
          getReturnStatusLabel(ret.status),
        ];
      }),
    };
  }

  if (safeType === "overdue") {
    const today = todayJakarta();
    const fineRate = getFineRatePerDay(db);
    const list = db.loans
      .filter((l) => {
        const ret = db.returns.find((r) => r.loan_id === l.id);
        const isOverdueCase =
          l.status === "overdue" ||
          (l.status === "borrowed" && l.due_date < today) ||
          (ret && ret.late_days > 0);
        if (!isOverdueCase) return false;
        if (filters.date_from && l.due_date < filters.date_from) return false;
        if (filters.date_to && l.due_date > filters.date_to) return false;
        if (filters.category_id) {
          const catId = Number(filters.category_id);
          const lds = db.loan_details.filter((d) => d.loan_id === l.id);
          if (!lds.some((d) => eqById.get(d.equipment_id)?.category_id === catId))
            return false;
        }
        return true;
      })
      .sort((a, b) => b.due_date.localeCompare(a.due_date));

    let totalFineOrEst = 0;
    const rows = list.map((loan, i) => {
      const ret = db.returns.find((r) => r.loan_id === loan.id);
      const lateDays = ret ? ret.late_days : countLateDays(loan.due_date, today);
      const fineVal = ret ? ret.fine_amount : lateDays * fineRate;
      totalFineOrEst += fineVal;
      const borrower = userById.get(loan.user_id);
      const items = db.loan_details
        .filter((d) => d.loan_id === loan.id)
        .map((d) => `${eqById.get(d.equipment_id)?.name ?? "-"} (${d.qty})`)
        .join("; ");

      return [
        i + 1,
        loan.code,
        borrower?.name ?? "-",
        borrower?.phone ?? "-",
        items,
        formatShortDate(loan.due_date),
        `${lateDays} hari`,
        formatRupiah(fineVal),
        getLoanStatusLabel(loan.status),
      ];
    });

    return {
      type: safeType,
      meta,
      institution,
      summary: [
        { label: "Total Kasus Terlambat", value: String(list.length) },
        {
          label: "Belum Dikembalikan (Overdue Aktif)",
          value: String(
            list.filter((l) => ["borrowed", "overdue"].includes(l.status)).length
          ),
        },
        {
          label: "Sudah Dikembalikan Terlambat",
          value: String(list.filter((l) => l.status === "returned").length),
        },
        { label: "Total Denda / Estimasi", value: formatRupiah(totalFineOrEst) },
      ],
      headings: [
        "No",
        "Kode Peminjaman",
        "Peminjam",
        "Telepon",
        "Daftar Alat",
        "Jatuh Tempo",
        "Keterlambatan",
        "Denda (Aktual / Estimasi)",
        "Status Saat Ini",
      ],
      rows,
    };
  }

  if (safeType === "fines") {
    const fineRate = getFineRatePerDay(db);
    const list = db.returns
      .filter((r) => {
        if (r.fine_amount <= 0) return false;
        if (filters.date_from && r.return_date < filters.date_from) return false;
        if (filters.date_to && r.return_date > filters.date_to) return false;
        if (filters.status === "paid" && !r.fine_paid) return false;
        if (filters.status === "unpaid" && r.fine_paid) return false;
        return true;
      })
      .sort((a, b) => b.return_date.localeCompare(a.return_date));

    const paidSum = list
      .filter((r) => r.fine_paid)
      .reduce((s, r) => s + r.fine_amount, 0);
    const unpaidSum = list
      .filter((r) => !r.fine_paid)
      .reduce((s, r) => s + r.fine_amount, 0);

    return {
      type: safeType,
      meta,
      institution,
      summary: [
        { label: "Transaksi Berdenda", value: String(list.length) },
        { label: "Total Seluruh Denda", value: formatRupiah(paidSum + unpaidSum) },
        { label: "Denda Sudah Dibayar", value: formatRupiah(paidSum) },
        { label: "Denda Belum Dibayar", value: formatRupiah(unpaidSum) },
      ],
      headings: [
        "No",
        "Kode Pengembalian",
        "Kode Pinjam",
        "Nama Peminjam",
        "Tgl Kembali",
        "Hari Terlambat",
        "Tarif / Hari",
        "Total Denda",
        "Status Pembayaran",
        "Diverifikasi Oleh",
      ],
      rows: list.map((ret, i) => {
        const loan = db.loans.find((l) => l.id === ret.loan_id);
        const borrower = loan ? userById.get(loan.user_id) : null;
        const rate =
          ret.fine_per_day_snapshot > 0 ? ret.fine_per_day_snapshot : fineRate;
        return [
          i + 1,
          ret.code,
          loan?.code ?? "-",
          borrower?.name ?? "-",
          formatShortDate(ret.return_date),
          `${ret.late_days} hari`,
          formatRupiah(rate),
          formatRupiah(ret.fine_amount),
          ret.fine_paid ? "Lunas" : "Belum Dibayar",
          ret.verified_by ? userById.get(ret.verified_by)?.name ?? "-" : "-",
        ];
      }),
    };
  }

  // Default: 'loans'
  const list = db.loans
    .filter((l) => {
      if (filters.date_from && l.loan_date < filters.date_from) return false;
      if (filters.date_to && l.loan_date > filters.date_to) return false;
      if (filters.status && l.status !== filters.status) return false;
      if (filters.category_id) {
        const catId = Number(filters.category_id);
        const lds = db.loan_details.filter((d) => d.loan_id === l.id);
        if (!lds.some((d) => eqById.get(d.equipment_id)?.category_id === catId))
          return false;
      }
      return true;
    })
    .sort((a, b) => b.loan_date.localeCompare(a.loan_date) || b.id - a.id);

  let totalUnits = 0;
  const rows = list.map((loan, i) => {
    const borrower = userById.get(loan.user_id);
    const lds = db.loan_details.filter((d) => d.loan_id === loan.id);
    totalUnits += lds.reduce((s, d) => s + d.qty, 0);
    const itemsText = lds
      .map((d) => `${eqById.get(d.equipment_id)?.name ?? "-"} (${d.qty} unit)`)
      .join("; ");
    return [
      i + 1,
      loan.code,
      borrower?.name ?? "-",
      borrower?.identity_number ?? "-",
      itemsText,
      formatShortDate(loan.loan_date),
      formatShortDate(loan.due_date),
      loan.approved_by ? userById.get(loan.approved_by)?.name ?? "-" : "-",
      getLoanStatusLabel(loan.status),
    ];
  });

  return {
    type: "loans",
    meta,
    institution,
    summary: [
      { label: "Total Transaksi", value: String(list.length) },
      { label: "Total Unit Dipinjam", value: `${totalUnits} Unit` },
      {
        label: "Sedang Aktif / Dipinjam",
        value: String(
          list.filter((l) =>
            ["approved", "borrowed", "overdue"].includes(l.status)
          ).length
        ),
      },
      {
        label: "Selesai Dikembalikan",
        value: String(list.filter((l) => l.status === "returned").length),
      },
    ],
    headings: [
      "No",
      "Kode Peminjaman",
      "Nama Peminjam",
      "Identitas (NIS/NIP)",
      "Daftar Alat & Jumlah",
      "Tgl Pinjam",
      "Batas Kembali",
      "Petugas Verifikasi",
      "Status",
    ],
    rows,
  };
}
