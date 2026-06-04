import * as React from "react"

import { AppSidebar } from "@/components/app-sidebar"
import { SiteHeader } from "@/components/site-header"
import { SidebarInset, SidebarProvider } from "@/components/ui/sidebar"

type AdminLayoutProps = {
  currentPath: string
  title: string
  children: React.ReactNode
}

export function AdminLayout({
  currentPath,
  title,
  children,
}: AdminLayoutProps) {
  return (
    <SidebarProvider
      style={
        {
          "--sidebar-width": "calc(var(--spacing) * 72)",
          "--header-height": "calc(var(--spacing) * 12)",
        } as React.CSSProperties
      }
    >
      <AppSidebar currentPath={currentPath} variant="inset" />
      <SidebarInset>
        <SiteHeader title={title} />
        {children}
      </SidebarInset>
    </SidebarProvider>
  )
}

export default AdminLayout
