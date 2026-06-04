import type { AdminDashboardData } from "@/lib/admin-api"

import { Badge } from "@/components/ui/badge"
import {
  Card,
  CardAction,
  CardDescription,
  CardFooter,
  CardHeader,
  CardTitle,
} from "@/components/ui/card"
import { formatAdminDateTime, formatAdminNumber, titleCase } from "@/lib/admin-format"
import ChartDownIcon from "~icons/hugeicons/chart-down"
import ChartUpIcon from "~icons/hugeicons/chart-up"

type SectionCardsProps = {
  summary: AdminDashboardData["summary"]
}

export function SectionCards({ summary }: SectionCardsProps) {
  return (
    <div className="grid grid-cols-1 gap-4 px-4 *:data-[slot=card]:bg-linear-to-t *:data-[slot=card]:from-primary/5 *:data-[slot=card]:to-card *:data-[slot=card]:shadow-xs lg:px-6 @xl/main:grid-cols-2 @5xl/main:grid-cols-4 dark:*:data-[slot=card]:bg-card">
      <Card className="@container/card">
        <CardHeader>
          <CardDescription>Total Emails</CardDescription>
          <CardTitle className="text-2xl font-semibold tabular-nums @[250px]/card:text-3xl">
            {formatAdminNumber(summary.mailTotal)}
          </CardTitle>
          <CardAction>
            <Badge variant="outline">
              <ChartUpIcon />
              {formatAdminNumber(summary.mailSent)} sent
            </Badge>
          </CardAction>
        </CardHeader>
        <CardFooter className="flex-col items-start gap-1.5 text-sm">
          <div className="line-clamp-1 flex gap-2 font-medium">
            {formatAdminNumber(summary.mailFailed)} failed messages
            <ChartDownIcon />
          </div>
          <div className="text-muted-foreground">
            {formatAdminNumber(summary.mailQueued)} queued and {formatAdminNumber(summary.mailProcessing)} processing
          </div>
        </CardFooter>
      </Card>
      <Card className="@container/card">
        <CardHeader>
          <CardDescription>Total Connections</CardDescription>
          <CardTitle className="text-2xl font-semibold tabular-nums @[250px]/card:text-3xl">
            {formatAdminNumber(summary.connectionsTotal)}
          </CardTitle>
          <CardAction>
            <Badge variant="outline">
              <ChartUpIcon />
              {formatAdminNumber(summary.activeConnections)} active
            </Badge>
          </CardAction>
        </CardHeader>
        <CardFooter className="flex-col items-start gap-1.5 text-sm">
          <div className="line-clamp-1 flex gap-2 font-medium">
            {formatAdminNumber(summary.availableConnections)} available now
            <ChartUpIcon />
          </div>
          <div className="text-muted-foreground">
            {summary.nextAvailableAt
              ? `Next route opens ${formatAdminDateTime(summary.nextAvailableAt)}`
              : "All active routes are immediately available"}
          </div>
        </CardFooter>
      </Card>
      <Card className="@container/card">
        <CardHeader>
          <CardDescription>Ready Queue</CardDescription>
          <CardTitle className="text-2xl font-semibold tabular-nums @[250px]/card:text-3xl">
            {formatAdminNumber(summary.queuePendingReady)}
          </CardTitle>
          <CardAction>
            <Badge variant="outline">
              <ChartDownIcon />
              {formatAdminNumber(summary.queuePendingDeferred)} deferred
            </Badge>
          </CardAction>
        </CardHeader>
        <CardFooter className="flex-col items-start gap-1.5 text-sm">
          <div className="line-clamp-1 flex gap-2 font-medium">
            {formatAdminNumber(summary.queueProcessing)} processing
            <ChartUpIcon />
          </div>
          <div className="text-muted-foreground">
            {formatAdminNumber(summary.queueFailed)} failed and {formatAdminNumber(summary.queueStaleProcessing)} stale workers
          </div>
        </CardFooter>
      </Card>
      <Card className="@container/card">
        <CardHeader>
          <CardDescription>Total Webhooks</CardDescription>
          <CardTitle className="text-2xl font-semibold tabular-nums @[250px]/card:text-3xl">
            {formatAdminNumber(summary.webhookEvents)}
          </CardTitle>
          <CardAction>
            <Badge variant="outline">
              <ChartUpIcon />
              {titleCase(summary.deliveryMode)}
            </Badge>
          </CardAction>
        </CardHeader>
        <CardFooter className="flex-col items-start gap-1.5 text-sm">
          <div className="line-clamp-1 flex gap-2 font-medium">
            {titleCase(summary.routingStrategy)} routing
            <ChartUpIcon />
          </div>
          <div className="text-muted-foreground">
            {summary.interceptEnabled ? "wp_mail interception is enabled" : "wp_mail interception is disabled"}
          </div>
        </CardFooter>
      </Card>
    </div>
  )
}
