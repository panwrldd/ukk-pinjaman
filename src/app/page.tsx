import { redirect } from "next/navigation";
import { getCurrentUser } from "@/lib/auth";
import { getRoleHome } from "@/lib/types";

export const dynamic = "force-dynamic";

export default async function HomePage() {
  const user = await getCurrentUser();
  if (!user) {
    redirect("/login");
  }
  redirect(getRoleHome(user.role));
}
