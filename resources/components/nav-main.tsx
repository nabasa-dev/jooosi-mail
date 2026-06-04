"use client"

import * as React from "react"

import {
  Collapsible,
  CollapsibleContent,
  CollapsibleTrigger,
} from "@/components/ui/collapsible"
import {
  SidebarGroup,
  SidebarGroupLabel,
  SidebarMenu,
  SidebarMenuAction,
  SidebarMenuButton,
  SidebarMenuItem,
  SidebarMenuSub,
  SidebarMenuSubButton,
  SidebarMenuSubItem,
} from "@/components/ui/sidebar"
import ArrowRight01Icon from "~icons/hugeicons/arrow-right-01"

export function NavMain({
  items,
}: {
  items: {
    title: string
    href: string
    icon: React.ReactNode
    isActive: boolean
    items?: {
      title: string
      href: string
      isActive: boolean
    }[]
  }[]
}) {
  return (
    <SidebarGroup>
      <SidebarGroupLabel>Navigation</SidebarGroupLabel>
      <SidebarMenu>
        {items.map((item) => (
          <Collapsible
            key={`${item.title}-${item.isActive}`}
            defaultOpen={item.isActive}
            render={<SidebarMenuItem />}
          >
            <SidebarMenuButton
              isActive={item.isActive}
              tooltip={item.title}
              render={<a href={item.href} />}
            >
              {item.icon}
              <span>{item.title}</span>
            </SidebarMenuButton>
            {item.items?.length ? (
              <>
                <CollapsibleTrigger
                  render={
                    <SidebarMenuAction className="aria-expanded:rotate-90" />
                  }
                >
                  <ArrowRight01Icon />
                  <span className="sr-only">Toggle</span>
                </CollapsibleTrigger>
                <CollapsibleContent>
                  <SidebarMenuSub>
                    {item.items.map((subItem) => (
                      <SidebarMenuSubItem key={subItem.title}>
                        <SidebarMenuSubButton
                          isActive={subItem.isActive}
                          render={<a href={subItem.href} />}
                        >
                          <span>{subItem.title}</span>
                        </SidebarMenuSubButton>
                      </SidebarMenuSubItem>
                    ))}
                  </SidebarMenuSub>
                </CollapsibleContent>
              </>
            ) : null}
          </Collapsible>
        ))}
      </SidebarMenu>
    </SidebarGroup>
  )
}
