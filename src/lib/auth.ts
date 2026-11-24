import { SignJWT, jwtVerify } from "jose";
import { cookies } from "next/headers";
import { redirect } from "next/navigation";
import { readDb } from "./storage";
import { Role, User, getRoleHome } from "./types";

export const SESSION_COOKIE = "gearhub_session";

function getSecretKey(): Uint8Array {
  const secret =
    process.env.AUTH_SECRET ||
    process.env.NETLIFY_SITE_ID ||
    "gearhub-ukk-rpl-paket-1-default-secret-key-2026";
  return new TextEncoder().encode(secret);
}

export async function signSessionToken(userId: number, remember = false): Promise<string> {
  return await new SignJWT({ uid: userId })
    .setProtectedHeader({ alg: "HS256" })
    .setIssuedAt()
    .setExpirationTime(remember ? "30d" : "7d")
    .sign(getSecretKey());
}

export async function verifySessionToken(token: string): Promise<number | null> {
  try {
    const { payload } = await jwtVerify(token, getSecretKey());
    if (typeof payload.uid === "number") {
      return payload.uid;
    }
    return null;
  } catch {
    return null;
  }
}

export async function setSessionCookie(userId: number, remember = false): Promise<void> {
  const token = await signSessionToken(userId, remember);
  const cookieStore = await cookies();
  cookieStore.set(SESSION_COOKIE, token, {
    httpOnly: true,
    sameSite: "lax",
    secure: process.env.NODE_ENV === "production",
    path: "/",
    maxAge: remember ? 60 * 60 * 24 * 30 : 60 * 60 * 24 * 7,
  });
}

export async function clearSessionCookie(): Promise<void> {
  const cookieStore = await cookies();
  cookieStore.delete(SESSION_COOKIE);
}

export async function getCurrentUser(): Promise<User | null> {
  const cookieStore = await cookies();
  const token = cookieStore.get(SESSION_COOKIE)?.value;
  if (!token) return null;

  const uid = await verifySessionToken(token);
  if (!uid) return null;

  const db = await readDb();
  const user = db.users.find((u) => u.id === uid) ?? null;
  if (!user || !user.is_active) {
    return null;
  }
  return user;
}

export async function requireUser(allowedRoles?: Role[]): Promise<User> {
  const user = await getCurrentUser();
  if (!user) {
    redirect("/login");
  }
  if (allowedRoles && !allowedRoles.includes(user.role)) {
    redirect(getRoleHome(user.role));
  }
  return user;
}
