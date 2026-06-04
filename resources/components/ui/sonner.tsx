import { useTheme } from "next-themes"
import { Toaster as Sonner, type ToasterProps } from "sonner"
import Alert02Icon from "~icons/hugeicons/alert-02"
import CheckmarkCircle02Icon from "~icons/hugeicons/checkmark-circle-02"
import InformationCircleIcon from "~icons/hugeicons/information-circle"
import Loading03Icon from "~icons/hugeicons/loading-03"
import MultiplicationSignCircleIcon from "~icons/hugeicons/multiplication-sign-circle"

const Toaster = ({ ...props }: ToasterProps) => {
  const { theme = "system" } = useTheme()

  return (
    <Sonner
      theme={theme as ToasterProps["theme"]}
      className="toaster group"
      icons={{
        success: (
          <CheckmarkCircle02Icon className="size-4" />
        ),
        info: (
          <InformationCircleIcon className="size-4" />
        ),
        warning: (
          <Alert02Icon className="size-4" />
        ),
        error: (
          <MultiplicationSignCircleIcon className="size-4" />
        ),
        loading: (
          <Loading03Icon className="size-4 animate-spin" />
        ),
      }}
      style={
        {
          "--normal-bg": "var(--popover)",
          "--normal-text": "var(--popover-foreground)",
          "--normal-border": "var(--border)",
          "--border-radius": "var(--radius)",
        } as React.CSSProperties
      }
      toastOptions={{
        classNames: {
          toast: "cn-toast",
        },
      }}
      {...props}
    />
  )
}

export { Toaster }
