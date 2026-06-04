import * as React from "react"

import { AdminLayout } from "@/admin/layout"
import {
  DEFAULT_ADMIN_PATH,
  buildAdminHashHref,
  parseAdminHashLocation,
  resolveAdminRoute,
} from "@/admin/routes"
import { TooltipProvider } from "@/components/ui/tooltip"

function subscribeToHashChange(callback: () => void): () => void {
  if (typeof window === "undefined") {
    return () => undefined
  }

  window.addEventListener("hashchange", callback)

  return () => window.removeEventListener("hashchange", callback)
}

function getHashSnapshot(): string {
  if (typeof window === "undefined") {
    return DEFAULT_ADMIN_PATH
  }

  return resolveAdminRoute(parseAdminHashLocation(window.location.hash).path).path
}

export function AdminApp() {
  const currentPath = React.useSyncExternalStore(
    subscribeToHashChange,
    getHashSnapshot,
    () => DEFAULT_ADMIN_PATH,
  )
  const route = resolveAdminRoute(currentPath)
  const CurrentPage = route.component

  React.useEffect(() => {
    if (typeof window === "undefined") {
      return
    }

    const { path, searchParams } = parseAdminHashLocation(window.location.hash)

    if (path === route.path) {
      return
    }

    const nextUrl = new URL(window.location.href)
    nextUrl.hash = buildAdminHashHref(route.path, searchParams)
    window.history.replaceState(window.history.state, "", nextUrl)
  }, [route.path])

  return (
    <TooltipProvider>
      <main>
        <AdminLayout
          currentPath={route.path}
          title={route.title}
        >
          <CurrentPage />
        </AdminLayout>
      </main>
    </TooltipProvider>
  )
}

export default AdminApp
