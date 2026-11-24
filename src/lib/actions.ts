"use server";

import { revalidatePath } from "next/cache";
import {
  clearSessionCookie,
  getCurrentUser,
  setSessionCookie,
} from "./auth";
import {
  approveLoanAction,
  authenticateUser,
  cancelLoanAction,
  createEquipmentAction,
  createLoanAction,
  deleteCategoryAction,
  deleteEquipmentAction,
  deleteLoanAction,
  deleteUserAction,
  handOverLoanAction,
  markFinePaidAction,
  rejectLoanAction,
  restoreDamagedUnitsAction,
  saveCategoryAction,
  saveUserAction,
  submitReturnAction,
  toggleUserStatusAction,
  updateEquipmentAction,
  updateSettingsAction,
  verifyReturnAction,
} from "./services";
import { resetDbToSeed } from "./storage";
import {
  EquipmentCondition,
  EquipmentStatus,
  Role,
  getRoleHome,
} from "./types";

export interface ActionResult {
  ok: boolean;
  message?: string;
  error?: string;
  redirectTo?: string;
}

function errMsg(e: unknown): string {
  return e instanceof Error ? e.message : "Terjadi kesalahan pada sistem.";
}

export async function loginServerAction(
  identity: string,
  password: string,
  remember: boolean
): Promise<ActionResult> {
  try {
    const res = await authenticateUser(identity, password);
    if (!res.ok) {
      return { ok: false, error: res.error };
    }
    await setSessionCookie(res.user.id, remember);
    revalidatePath("/", "layout");
    return {
      ok: true,
      message: `Selamat datang kembali, ${res.user.name}.`,
      redirectTo: getRoleHome(res.user.role),
    };
  } catch (e) {
    return { ok: false, error: errMsg(e) };
  }
}

export async function logoutServerAction(): Promise<ActionResult> {
  await clearSessionCookie();
  revalidatePath("/", "layout");
  return { ok: true, redirectTo: "/login" };
}

export async function createLoanServerAction(payload: {
  user_id?: number;
  loan_date: string;
  due_date: string;
  purpose: string;
  notes?: string;
  items: Array<{ equipment_id: number; qty: number }>;
}): Promise<ActionResult> {
  try {
    const actor = await getCurrentUser();
    if (!actor) return { ok: false, error: "Sesi berakhir, silakan login ulang." };

    const targetUserId =
      actor.role === "admin" && payload.user_id ? Number(payload.user_id) : actor.id;
    const loan = await createLoanAction(payload, targetUserId);
    revalidatePath("/", "layout");
    return {
      ok: true,
      message: `Pengajuan peminjaman ${loan.code} berhasil dibuat.`,
      redirectTo:
        actor.role === "admin"
          ? `/admin/loans/${loan.id}`
          : `/borrower/loans/${loan.id}`,
    };
  } catch (e) {
    return { ok: false, error: errMsg(e) };
  }
}

export async function approveLoanServerAction(
  loanId: number,
  notes?: string
): Promise<ActionResult> {
  try {
    const actor = await getCurrentUser();
    if (!actor || !["admin", "petugas"].includes(actor.role)) {
      return { ok: false, error: "Akses ditolak." };
    }
    const loan = await approveLoanAction(loanId, actor.id, notes);
    revalidatePath("/", "layout");
    return {
      ok: true,
      message: `Peminjaman ${loan.code} disetujui dan stok direservasi.`,
    };
  } catch (e) {
    return { ok: false, error: errMsg(e) };
  }
}

export async function rejectLoanServerAction(
  loanId: number,
  reason: string
): Promise<ActionResult> {
  try {
    const actor = await getCurrentUser();
    if (!actor || !["admin", "petugas"].includes(actor.role)) {
      return { ok: false, error: "Akses ditolak." };
    }
    const loan = await rejectLoanAction(loanId, actor.id, reason);
    revalidatePath("/", "layout");
    return { ok: true, message: `Peminjaman ${loan.code} telah ditolak.` };
  } catch (e) {
    return { ok: false, error: errMsg(e) };
  }
}

export async function handOverLoanServerAction(
  loanId: number,
  notes?: string
): Promise<ActionResult> {
  try {
    const actor = await getCurrentUser();
    if (!actor || !["admin", "petugas"].includes(actor.role)) {
      return { ok: false, error: "Akses ditolak." };
    }
    const loan = await handOverLoanAction(loanId, actor.id, notes);
    revalidatePath("/", "layout");
    return {
      ok: true,
      message: `Penyerahan fisik alat untuk ${loan.code} berhasil dicatat.`,
    };
  } catch (e) {
    return { ok: false, error: errMsg(e) };
  }
}

export async function cancelLoanServerAction(loanId: number): Promise<ActionResult> {
  try {
    const actor = await getCurrentUser();
    if (!actor) return { ok: false, error: "Sesi berakhir." };
    const isStaffOrAdmin = actor.role === "admin" || actor.role === "petugas";
    const loan = await cancelLoanAction(loanId, actor.id, isStaffOrAdmin);
    revalidatePath("/", "layout");
    return { ok: true, message: `Peminjaman ${loan.code} berhasil dibatalkan.` };
  } catch (e) {
    return { ok: false, error: errMsg(e) };
  }
}

export async function deleteLoanServerAction(loanId: number): Promise<ActionResult> {
  try {
    const actor = await getCurrentUser();
    if (!actor || actor.role !== "admin") {
      return { ok: false, error: "Hanya Administrator yang dapat menghapus transaksi." };
    }
    await deleteLoanAction(loanId, actor.id);
    revalidatePath("/", "layout");
    return {
      ok: true,
      message: "Data transaksi peminjaman berhasil dihapus.",
      redirectTo: "/admin/loans",
    };
  } catch (e) {
    return { ok: false, error: errMsg(e) };
  }
}

export async function submitReturnServerAction(
  loanId: number,
  notes?: string,
  returnDate?: string
): Promise<ActionResult> {
  try {
    const actor = await getCurrentUser();
    if (!actor) return { ok: false, error: "Sesi berakhir." };
    const ret = await submitReturnAction(loanId, actor.id, notes, returnDate);
    revalidatePath("/", "layout");
    const redirectTo =
      actor.role === "admin"
        ? `/admin/returns/${ret.id}`
        : actor.role === "petugas"
        ? `/staff/returns/${ret.id}`
        : undefined;
    return {
      ok: true,
      message: `Pengajuan pengembalian ${ret.code} berhasil dicatat.`,
      redirectTo,
    };
  } catch (e) {
    return { ok: false, error: errMsg(e) };
  }
}

export async function verifyReturnServerAction(payload: {
  returnId: number;
  details: Array<{
    loan_detail_id: number;
    condition_after: EquipmentCondition;
    qty_damaged?: number;
    damage_notes?: string | null;
  }>;
  finePaid: boolean;
  notes?: string;
}): Promise<ActionResult> {
  try {
    const actor = await getCurrentUser();
    if (!actor || !["admin", "petugas"].includes(actor.role)) {
      return { ok: false, error: "Akses ditolak." };
    }
    const ret = await verifyReturnAction(
      payload.returnId,
      actor.id,
      payload.details,
      payload.finePaid,
      payload.notes
    );
    revalidatePath("/", "layout");
    return {
      ok: true,
      message: `Pengembalian ${ret.code} selesai diverifikasi dan stok telah diperbarui.`,
    };
  } catch (e) {
    return { ok: false, error: errMsg(e) };
  }
}

export async function markFinePaidServerAction(
  returnId: number
): Promise<ActionResult> {
  try {
    const actor = await getCurrentUser();
    if (!actor || !["admin", "petugas"].includes(actor.role)) {
      return { ok: false, error: "Akses ditolak." };
    }
    const ret = await markFinePaidAction(returnId, actor.id);
    revalidatePath("/", "layout");
    return {
      ok: true,
      message: `Denda pengembalian ${ret.code} berhasil ditandai lunas.`,
    };
  } catch (e) {
    return { ok: false, error: errMsg(e) };
  }
}

export async function saveEquipmentServerAction(payload: {
  id?: number;
  category_id: number;
  code: string;
  name: string;
  description?: string;
  photo?: string | null;
  remove_photo?: boolean;
  condition: EquipmentCondition;
  qty_total: number;
  qty_damaged: number;
  location?: string;
  status: EquipmentStatus;
  notes?: string;
}): Promise<ActionResult> {
  try {
    const actor = await getCurrentUser();
    if (!actor || actor.role !== "admin") {
      return { ok: false, error: "Akses ditolak." };
    }
    if (payload.id) {
      const eq = await updateEquipmentAction(payload.id, payload, actor.id);
      revalidatePath("/", "layout");
      return {
        ok: true,
        message: `Data peralatan ${eq.code} berhasil diperbarui.`,
        redirectTo: `/admin/equipments/${eq.id}`,
      };
    } else {
      const eq = await createEquipmentAction(payload, actor.id);
      revalidatePath("/", "layout");
      return {
        ok: true,
        message: `Peralatan ${eq.code} berhasil ditambahkan.`,
        redirectTo: `/admin/equipments/${eq.id}`,
      };
    }
  } catch (e) {
    return { ok: false, error: errMsg(e) };
  }
}

export async function restoreDamagedServerAction(
  equipmentId: number,
  qtyToRestore: number,
  notes?: string
): Promise<ActionResult> {
  try {
    const actor = await getCurrentUser();
    if (!actor || actor.role !== "admin") {
      return { ok: false, error: "Akses ditolak." };
    }
    const eq = await restoreDamagedUnitsAction(
      equipmentId,
      qtyToRestore,
      actor.id,
      notes
    );
    revalidatePath("/", "layout");
    return {
      ok: true,
      message: `Unit perbaikan pada ${eq.code} berhasil dipulihkan ke stok tersedia.`,
    };
  } catch (e) {
    return { ok: false, error: errMsg(e) };
  }
}

export async function deleteEquipmentServerAction(
  equipmentId: number
): Promise<ActionResult> {
  try {
    const actor = await getCurrentUser();
    if (!actor || actor.role !== "admin") {
      return { ok: false, error: "Akses ditolak." };
    }
    await deleteEquipmentAction(equipmentId, actor.id);
    revalidatePath("/", "layout");
    return {
      ok: true,
      message: "Peralatan berhasil dihapus dari inventaris.",
      redirectTo: "/admin/equipments",
    };
  } catch (e) {
    return { ok: false, error: errMsg(e) };
  }
}

export async function saveCategoryServerAction(payload: {
  id?: number;
  code: string;
  name: string;
  description?: string;
  is_active: boolean;
}): Promise<ActionResult> {
  try {
    const actor = await getCurrentUser();
    if (!actor || actor.role !== "admin") {
      return { ok: false, error: "Akses ditolak." };
    }
    await saveCategoryAction(payload, actor.id);
    revalidatePath("/", "layout");
    return {
      ok: true,
      message: payload.id
        ? "Kategori berhasil diperbarui."
        : "Kategori baru berhasil ditambahkan.",
    };
  } catch (e) {
    return { ok: false, error: errMsg(e) };
  }
}

export async function deleteCategoryServerAction(
  categoryId: number
): Promise<ActionResult> {
  try {
    const actor = await getCurrentUser();
    if (!actor || actor.role !== "admin") {
      return { ok: false, error: "Akses ditolak." };
    }
    await deleteCategoryAction(categoryId, actor.id);
    revalidatePath("/", "layout");
    return { ok: true, message: "Kategori berhasil dihapus." };
  } catch (e) {
    return { ok: false, error: errMsg(e) };
  }
}

export async function saveUserServerAction(payload: {
  id?: number;
  name: string;
  username: string;
  email: string;
  identity_number?: string;
  password?: string;
  role: Role;
  phone?: string;
  address?: string;
  is_active: boolean;
}): Promise<ActionResult> {
  try {
    const actor = await getCurrentUser();
    if (!actor || actor.role !== "admin") {
      return { ok: false, error: "Akses ditolak." };
    }
    await saveUserAction(payload, actor.id);
    revalidatePath("/", "layout");
    return {
      ok: true,
      message: payload.id
        ? "Data pengguna berhasil diperbarui."
        : "Pengguna baru berhasil ditambahkan.",
      redirectTo: "/admin/users",
    };
  } catch (e) {
    return { ok: false, error: errMsg(e) };
  }
}

export async function toggleUserStatusServerAction(
  targetUserId: number
): Promise<ActionResult> {
  try {
    const actor = await getCurrentUser();
    if (!actor || actor.role !== "admin") {
      return { ok: false, error: "Akses ditolak." };
    }
    const u = await toggleUserStatusAction(targetUserId, actor.id);
    revalidatePath("/", "layout");
    return {
      ok: true,
      message: `Status akun @${u.username} diubah menjadi ${u.is_active ? "Aktif" : "Nonaktif"}.`,
    };
  } catch (e) {
    return { ok: false, error: errMsg(e) };
  }
}

export async function deleteUserServerAction(
  targetUserId: number
): Promise<ActionResult> {
  try {
    const actor = await getCurrentUser();
    if (!actor || actor.role !== "admin") {
      return { ok: false, error: "Akses ditolak." };
    }
    await deleteUserAction(targetUserId, actor.id);
    revalidatePath("/", "layout");
    return { ok: true, message: "Akun pengguna berhasil dihapus." };
  } catch (e) {
    return { ok: false, error: errMsg(e) };
  }
}

export async function saveSettingsServerAction(
  updates: Record<string, string>
): Promise<ActionResult> {
  try {
    const actor = await getCurrentUser();
    if (!actor || actor.role !== "admin") {
      return { ok: false, error: "Akses ditolak." };
    }
    await updateSettingsAction(updates, actor.id);
    revalidatePath("/", "layout");
    return {
      ok: true,
      message: "Pengaturan sistem dan tarif denda berhasil disimpan.",
    };
  } catch (e) {
    return { ok: false, error: errMsg(e) };
  }
}

export async function resetDemoDataServerAction(): Promise<ActionResult> {
  try {
    const actor = await getCurrentUser();
    if (!actor || actor.role !== "admin") {
      return { ok: false, error: "Akses ditolak." };
    }
    await resetDbToSeed();
    revalidatePath("/", "layout");
    return {
      ok: true,
      message: "Seluruh data sistem berhasil di-reset ke data awal seeder UKK.",
    };
  } catch (e) {
    return { ok: false, error: errMsg(e) };
  }
}
