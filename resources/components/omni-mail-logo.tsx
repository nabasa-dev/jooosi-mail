import type { SVGProps } from "react"

import { cn } from "@/lib/utils"
import OmniMailIcon from "~/omni-mail.svg?react"

type OmniMailLogoProps = SVGProps<SVGSVGElement>

export function OmniMailLogo({ className, style, ...props }: OmniMailLogoProps) {
  return (
    <OmniMailIcon
      {...props}
      aria-hidden="true"
      focusable="false"
      className={cn("inline-block shrink-0", className)}
      style={style}
    />
  )
}
