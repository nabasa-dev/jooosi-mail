"use client"

import * as React from "react"

import { DEFAULT_ADMIN_PATH, getAdminNavItems, toAdminHashHref } from "@/admin/routes"
import { NavMain } from "@/components/nav-main"
import { JooosiMailLogo } from "@/components/jooosi-mail-logo"
import {
  Sidebar,
  SidebarContent,
  SidebarHeader,
  SidebarMenu,
  SidebarMenuButton,
  SidebarMenuItem,
} from "@/components/ui/sidebar"

type AppSidebarProps = React.ComponentProps<typeof Sidebar> & {
  currentPath: string
}

export function AppSidebar({ currentPath, ...props }: AppSidebarProps) {
  const items = getAdminNavItems(currentPath)

  return (
    <Sidebar variant="inset" {...props}>
      <SidebarHeader>
        <SidebarMenu>
          <SidebarMenuItem>
            <SidebarMenuButton
              size="lg"
              render={<a href={toAdminHashHref(DEFAULT_ADMIN_PATH)} />}
            >
              <div className="flex aspect-square size-8 items-center justify-center rounded-lg bg-sidebar-primary text-sidebar-primary-foreground">
                <JooosiMailLogo className="size-4" />
              </div>
              <div className="grid flex-1 text-left text-sm leading-tight">
                <span className="truncate font-medium">Jooosi Mail</span>
                <span className="truncate text-xs">Email Delivery</span>
              </div>
            </SidebarMenuButton>
          </SidebarMenuItem>
        </SidebarMenu>
      </SidebarHeader>
      <SidebarContent>
        <NavMain items={items} />
      </SidebarContent>
    </Sidebar>
  )
}
