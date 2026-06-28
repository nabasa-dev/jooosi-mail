import type { ComponentType, ReactNode } from "react"

import { JooosiMailLogo } from "@/components/jooosi-mail-logo"
import { buttonVariants } from "@/components/ui/button"
import {
  Card,
  CardContent,
  CardDescription,
  CardHeader,
  CardTitle,
} from "@/components/ui/card"
import { Separator } from "@/components/ui/separator"
import { getAdminRuntime } from "@/lib/admin-api"
import { cn } from "@/lib/utils"
import ActivityIcon from "~icons/lucide/activity"
import AwardIcon from "~icons/lucide/award"
import CircleCheckBigIcon from "~icons/lucide/circle-check-big"
import CodeIcon from "~icons/lucide/code"
import DatabaseIcon from "~icons/lucide/database"
import FacebookIcon from "~icons/lucide/facebook"
import FileTextIcon from "~icons/lucide/file-text"
import GithubIcon from "~icons/lucide/github"
import HeartIcon from "~icons/lucide/heart"
import KofiIcon from "~icons/simple-icons/kofi"
import PackageIcon from "~icons/lucide/package"
import StarIcon from "~icons/lucide/star"
import UsersIcon from "~icons/lucide/users"
import ZapIcon from "~icons/lucide/zap"

type Capability = {
  title: string
  description: string
  icon: ComponentType<{ className?: string }>
}

type Sponsor = {
  name: string
  description: string
  href: string
  iconSrc: string
}

type SponsorshipBenefit = {
  label: string
  icon: ComponentType<{ className?: string }>
}

const capabilities: Capability[] = [
  {
    title: "Multiple provider support",
    description: "Use SMTP or popular email services from one plugin, with one unified admin interface.",
    icon: DatabaseIcon,
  },
  {
    title: "Smarter delivery",
    description: "Queue busy sending periods and fall back to another connection when delivery needs recovery.",
    icon: ZapIcon,
  },
  {
    title: "Admin and CLI workflows",
    description: "Manage providers, delivery settings, logs, and operations from the dashboard or WP-CLI.",
    icon: CodeIcon,
  },
  {
    title: "Connection health",
    description: "Keep an eye on provider availability, delivery issues, and routing readiness from WordPress.",
    icon: ActivityIcon,
  },
]

const sponsors: Sponsor[] = [
  {
    name: "WindPress",
    description: "Tailwind CSS for WordPress",
    href: "https://windpress.jooo.si",
    iconSrc: new URL("../icons/windpress.svg", import.meta.url).href,
  },
  {
    name: "LiveCanvas",
    description: "Visual Site Builder for WordPress",
    href: "https://livecanvas.com",
    iconSrc: new URL("../icons/livecanvas.svg", import.meta.url).href,
  },
]

const sponsorshipBenefits: SponsorshipBenefit[] = [
  {
    label: "Your brand icon bundled in releases",
    icon: PackageIcon,
  },
  {
    label: "Logo featured in all plugin READMEs",
    icon: FileTextIcon,
  },
  {
    label: "Featured in all plugin admin pages",
    icon: AwardIcon,
  },
  {
    label: "Exposure to thousands of developers",
    icon: UsersIcon,
  },
]

const githubSponsorButtonClassName =
  "border-0 bg-[linear-gradient(135deg,#24292e_0%,#1a1e22_100%)] text-white shadow-[0_4px_20px_rgba(0,0,0,0.15)] hover:-translate-y-1 hover:bg-[linear-gradient(135deg,#1a1e22_0%,#0d1117_100%)] hover:text-white hover:shadow-[0_12px_32px_rgba(0,0,0,0.35)]"

const kofiSponsorButtonClassName =
  "border-0 bg-[linear-gradient(135deg,#ff5e5b_0%,#ff4757_100%)] text-white shadow-[0_4px_20px_rgba(0,0,0,0.15)] hover:-translate-y-1 hover:bg-[linear-gradient(135deg,#ee5a6f_0%,#ff3838_100%)] hover:text-white hover:shadow-[0_12px_32px_rgba(255,71,87,0.35)]"

const primarySponsorButtonClassName =
  "h-12 min-w-52 flex-1 justify-center gap-2.5 rounded-[10px] px-7 text-[15px] font-bold sm:max-w-70"

function AboutMetaCard({
  title,
  icon: Icon,
  children,
}: {
  title: string
  icon: ComponentType<{ className?: string }>
  children: ReactNode
}) {
  return (
    <div className="flex items-start gap-4 rounded-xl border-2 bg-muted/50 p-5">
      <div className="flex size-10 shrink-0 items-center justify-center rounded-lg bg-background text-primary shadow-xs">
        <Icon className="size-5" />
      </div>
      <div className="flex min-w-0 flex-col gap-1">
        <h3 className="text-xs font-semibold uppercase tracking-wide text-muted-foreground/80">{title}</h3>
        <div className="text-sm leading-relaxed text-muted-foreground">{children}</div>
      </div>
    </div>
  )
}

export default function AboutPage() {
  const { pluginVersion } = getAdminRuntime()

  return (
    <div className="@container/main flex flex-1 flex-col gap-4 py-4 md:gap-6 md:py-6">
      <div className="px-4 lg:px-6">
        <Card className="border-2 bg-gradient-to-br from-muted/60 to-background shadow-none ring-0">
          <CardHeader className="px-8 py-12">
            <div className="mx-auto flex max-w-3xl flex-col items-center gap-6 text-center">
              <div className="flex size-20 items-center justify-center rounded-[20px] bg-gradient-to-br from-primary to-primary/70 text-primary-foreground shadow-[0_8px_32px_color-mix(in_srgb,var(--primary)_30%,transparent)]">
                <JooosiMailLogo className="size-13" />
              </div>
              <div className="flex flex-col gap-2">
                <CardTitle className="text-4xl font-bold tracking-tight">Jooosi Mail</CardTitle>
                <p className="text-lg font-semibold text-primary">Enterprise-grade email delivery for WordPress</p>
                <CardDescription className="mx-auto max-w-2xl text-[15px] leading-7">
                  A modern WordPress mail delivery plugin that routes <code>wp_mail()</code> through Symfony Mailer, durable queues, provider failover, webhooks, and operational observability.
                </CardDescription>
              </div>
            </div>
          </CardHeader>
          <CardContent className="flex flex-col gap-6">
            <div className="grid gap-5 md:grid-cols-2 xl:grid-cols-4">
              {capabilities.map((capability) => {
                const Icon = capability.icon

                return (
                  <Card
                    key={capability.title}
                    size="sm"
                    className="group/feature border-2 p-2 shadow-none ring-0 transition-all hover:-translate-y-1 hover:border-primary hover:shadow-lg"
                  >
                    <CardHeader className="gap-4">
                      <div className="flex size-14 items-center justify-center rounded-xl bg-muted text-primary transition-all group-hover/feature:scale-110 group-hover/feature:bg-primary group-hover/feature:text-primary-foreground">
                        <Icon className="size-8" />
                      </div>
                      <div className="flex flex-col gap-1">
                        <CardTitle className="font-bold group-data-[size=sm]/card:text-base">
                          {capability.title}
                        </CardTitle>
                        <CardDescription className="leading-relaxed">{capability.description}</CardDescription>
                      </div>
                    </CardHeader>
                  </Card>
                )
              })}
            </div>
            <Separator />
            <div className="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
              <AboutMetaCard title="GitHub Repository" icon={GithubIcon}>
                <a
                  className="font-medium text-primary underline-offset-4 hover:underline"
                  href="https://github.com/nabasa-dev/jooosi-mail"
                  target="_blank"
                  rel="noreferrer"
                >
                  nabasa-dev/jooosi-mail
                </a>
              </AboutMetaCard>
              <AboutMetaCard title="Current Version" icon={CircleCheckBigIcon}>
                <span className="font-medium text-card-foreground">{pluginVersion}</span>
              </AboutMetaCard>
              <AboutMetaCard title="Open Source" icon={StarIcon}>
                <a
                  className="font-medium text-primary underline-offset-4 hover:underline"
                  href="https://www.gnu.org/licenses/gpl-3.0.html"
                  target="_blank"
                  rel="noreferrer"
                >
                  GPL-3.0-or-later
                </a>
              </AboutMetaCard>
              <AboutMetaCard title="Facebook Community" icon={FacebookIcon}>
                <a
                  className="font-medium text-primary underline-offset-4 hover:underline"
                  href="https://wind.press/go/facebook"
                  target="_blank"
                  rel="noreferrer"
                >
                  Join our community
                </a>
              </AboutMetaCard>
            </div>
          </CardContent>
        </Card>
      </div>

      <div className="px-4 lg:px-6">
        <Card>
          <CardHeader>
            <div className="mx-auto flex max-w-3xl flex-col items-center gap-4 text-center">
              <div className="flex size-16 items-center justify-center rounded-2xl bg-muted text-primary">
                <HeartIcon className="size-8" />
              </div>
              <div className="flex flex-col gap-2">
                <CardTitle className="text-2xl">Love This Plugin?</CardTitle>
                <CardDescription className="text-base">
                  Jooosi Mail is 100% free and open source, crafted with passion for the WordPress community. Your
                  sponsorship helps maintain and improve all our free WordPress plugins, not just Jooosi Mail.
                  Supporting one plugin means supporting all our open-source efforts.
                </CardDescription>
              </div>
            </div>
          </CardHeader>
          <CardContent className="flex flex-col gap-8">
            <div className="mx-auto grid w-full max-w-2xl gap-0 sm:grid-cols-2">
              <a
                className={cn(
                  buttonVariants({ variant: "default", size: "lg" }),
                  primarySponsorButtonClassName,
                  githubSponsorButtonClassName,
                )}
                href="https://github.com/sponsors/suasgn"
                target="_blank"
                rel="noreferrer"
              >
                <GithubIcon data-icon="inline-start" className="size-5" />
                GitHub Sponsors
              </a>
              <a
                className={cn(
                  buttonVariants({ variant: "default", size: "lg" }),
                  primarySponsorButtonClassName,
                  kofiSponsorButtonClassName,
                )}
                href="https://ko-fi.com/Q5Q75XSF7"
                target="_blank"
                rel="noreferrer"
              >
                <KofiIcon data-icon="inline-start" className="size-5" />
                Support via Ko-fi
              </a>
            </div>

            <Separator />

            <div className="flex flex-col gap-4">
              <div className="flex flex-col items-center gap-1 text-center">
                <h3 className="text-base font-medium">Proudly Sponsored By</h3>
                <p className="text-sm text-muted-foreground">
                  Sponsors are shown together because every contribution supports the same open-source work.
                </p>
              </div>
              <div className="grid gap-3 md:grid-cols-2">
                {sponsors.map((sponsor) => (
                  <a
                    key={sponsor.name}
                    className="group flex items-center gap-4 rounded-xl border p-4 transition-colors hover:bg-muted/50"
                    href={sponsor.href}
                    target="_blank"
                    rel="noreferrer"
                  >
                    <span className="flex size-14 shrink-0 items-center justify-center rounded-lg bg-muted text-lg font-semibold text-muted-foreground">
                      <img src={sponsor.iconSrc} alt="" className="size-10 object-contain" />
                    </span>
                    <span className="flex min-w-0 flex-1 flex-col gap-1">
                      <span className="flex items-center justify-between gap-3 text-sm font-medium">
                        {sponsor.name}
                      </span>
                      <span className="text-sm text-muted-foreground">{sponsor.description}</span>
                    </span>
                  </a>
                ))}
              </div>
            </div>

            <div className="flex flex-col gap-4 rounded-xl border p-4">
              <h3 className="text-base font-medium">Sponsorship Benefits</h3>
              <ul className="grid gap-3 sm:grid-cols-2">
                {sponsorshipBenefits.map((benefit) => {
                  const Icon = benefit.icon

                  return (
                    <li key={benefit.label} className="flex items-center gap-3 text-sm text-muted-foreground">
                      <span className="flex size-8 shrink-0 items-center justify-center rounded-lg bg-muted text-primary">
                        <Icon className="size-4" />
                      </span>
                      <span>{benefit.label}</span>
                    </li>
                  )
                })}
              </ul>
            </div>
          </CardContent>
        </Card>
      </div>

    </div>
  )
}
