import type { SVGProps } from "react"

import { cn } from "@/lib/utils"
import JooosiMailIcon from "~/jooosi-mail.svg?react"

type JooosiMailLogoProps = SVGProps<SVGSVGElement>

export function JooosiMailLogo({ className, style, ...props }: JooosiMailLogoProps) {
  return (
    <JooosiMailIcon
      {...props}
      aria-hidden="true"
      focusable="false"
      className={cn("inline-block shrink-0", className)}
      style={style}
    />
  )
}
