import * as React from "react"
import { format, subDays } from "date-fns"

import { ChartAreaInteractive, type DashboardSendingStatsRange } from "@/components/chart-area-interactive"
import { SectionCards } from "@/components/section-cards"
import { Button } from "@/components/ui/button"
import {
  Card,
  CardContent,
  CardDescription,
  CardHeader,
  CardTitle,
} from "@/components/ui/card"
import {
  Item,
  ItemContent,
  ItemMedia,
  ItemTitle,
} from "@/components/ui/item"
import { useAdminQuery } from "@/hooks/use-admin-query"
import { getDashboard, type AdminDashboardQuery } from "@/lib/admin-api"
import Cancel01Icon from "~icons/hugeicons/cancel-01"
import CheckmarkCircle01Icon from "~icons/hugeicons/checkmark-circle-01"
import RefreshIcon from "~icons/tabler/refresh"

function resolveSendingStatsQuery(range: DashboardSendingStatsRange): AdminDashboardQuery {
  if (range.preset === "custom") {
    return {
      fromDate: range.from,
      toDate: range.to,
    }
  }

  const today = new Date()
  const days = range.preset === "7d" ? 7 : range.preset === "30d" ? 30 : 90

  return {
    fromDate: format(subDays(today, days - 1), "yyyy-MM-dd"),
    toDate: format(today, "yyyy-MM-dd"),
  }
}

function getDashboardQueryKey(query: AdminDashboardQuery): string {
  return `${query.fromDate ?? ""}:${query.toDate ?? ""}`
}

export default function DashboardPage() {
  const [sendingStatsRange, setSendingStatsRange] = React.useState<DashboardSendingStatsRange>({ preset: "90d" })
  const dashboardQuery = React.useMemo(
    () => resolveSendingStatsQuery(sendingStatsRange),
    [sendingStatsRange],
  )
  const dashboardQueryKey = getDashboardQueryKey(dashboardQuery)
  const loadDashboard = React.useCallback(() => getDashboard(dashboardQuery), [dashboardQuery])
  const { data, loading, refreshing, error, refresh } = useAdminQuery(loadDashboard, {
    pollMs: 15000,
  })
  const hasLoadedDashboard = React.useRef(false)

  React.useEffect(() => {
    if (data !== null) {
      hasLoadedDashboard.current = true
    }
  }, [data])

  React.useEffect(() => {
    if (hasLoadedDashboard.current) {
      void refresh()
    }
  }, [dashboardQueryKey, refresh])

  if (loading && !data) {
    return (
      <div className="flex flex-1 flex-col px-4 py-4 lg:px-6 lg:py-6">
        <Card>
          <CardHeader>
            <CardTitle>Loading dashboard</CardTitle>
            <CardDescription>Collecting routing, queue, and delivery metrics.</CardDescription>
          </CardHeader>
        </Card>
      </div>
    )
  }

  if (!data) {
    return (
      <div className="flex flex-1 flex-col px-4 py-4 lg:px-6 lg:py-6">
        <Card>
          <CardHeader>
            <CardTitle>Dashboard unavailable</CardTitle>
            <CardDescription>{error ?? "The dashboard could not be loaded."}</CardDescription>
          </CardHeader>
          <CardContent>
            <Button onClick={() => void refresh()}>Try again</Button>
          </CardContent>
        </Card>
      </div>
    )
  }

  return (
    <div className="@container/main flex flex-1 flex-col gap-4 py-4 md:gap-6 md:py-6">
      <div className="flex flex-col gap-3 px-4 lg:flex-row lg:items-center lg:justify-between lg:px-6">
        <div className="flex flex-col gap-1">
          <h2 className="text-xl font-semibold tracking-tight">Operations overview</h2>
          <p className="text-sm text-muted-foreground">
            Watch email totals, routing capacity, queue readiness, and sending trends from one screen.
          </p>
        </div>
        <div className="flex flex-col items-stretch gap-2 lg:flex-row lg:items-center">
          <Item variant={data.summary.interceptEnabled ? "muted" : "outline"} size="sm" className="lg:w-64">
            <ItemMedia variant="icon">
              {data.summary.interceptEnabled ? (
                <CheckmarkCircle01Icon className="text-primary" />
              ) : (
                <Cancel01Icon className="text-destructive" />
              )}
            </ItemMedia>
            <ItemContent>
              <ItemTitle>
                {data.summary.interceptEnabled ? "wp_mail interception is on." : "wp_mail interception is off."}
              </ItemTitle>
            </ItemContent>
          </Item>
          <Button variant="outline" onClick={() => void refresh()}>
            <RefreshIcon data-icon="inline-start" className={refreshing ? "animate-spin" : undefined} />
            {refreshing ? "Refreshing..." : "Refresh"}
          </Button>
        </div>
      </div>

      {error ? (
        <div className="px-4 lg:px-6">
          <Card>
            <CardContent className="py-4 text-sm text-destructive">{error}</CardContent>
          </Card>
        </div>
      ) : null}

      <SectionCards summary={data.summary} />

      <div className="px-4 lg:px-6">
        <ChartAreaInteractive
          data={data.sendingStats}
          range={sendingStatsRange}
          onRangeChange={setSendingStatsRange}
        />
      </div>
    </div>
  )
}
