"use client";

import React, { useTransition } from "react";
import Link from "next/link";
import { useRouter } from "next/navigation";
import {
  deleteUserServerAction,
  toggleUserStatusServerAction,
} from "@/lib/actions";

export function UserRowActions({
  targetId,
  currentUserId,
  isActive,
  username,
}: {
  targetId: number;
  currentUserId: number;
  isActive: boolean;
  username: string;
}) {
  const router = useRouter();
  const [isPending, startTransition] = useTransition();
  const isSelf = targetId === currentUserId;

  function handleToggle() {
    startTransition(async () => {
      const res = await toggleUserStatusServerAction(targetId);
      if (!res.ok) alert(res.error);
      router.refresh();
    });
  }

  function handleDelete() {
    if (!confirm(`Hapus akun pengguna @${username}?`)) return;
    startTransition(async () => {
      const res = await deleteUserServerAction(targetId);
      if (!res.ok) alert(res.error);
      router.refresh();
    });
  }

  return (
    <div className="inline-flex items-center gap-1.5">
      <Link href={`/admin/users/${targetId}/edit`} className="btn-secondary btn-xs">
        Edit
      </Link>
      {!isSelf && (
        <>
          <button
            type="button"
            disabled={isPending}
            onClick={handleToggle}
            className="btn-secondary btn-xs"
          >
            {isActive ? "Nonaktifkan" : "Aktifkan"}
          </button>
          <button
            type="button"
            disabled={isPending}
            onClick={handleDelete}
            className="btn-danger-outline btn-xs"
          >
            Hapus
          </button>
        </>
      )}
    </div>
  );
}
