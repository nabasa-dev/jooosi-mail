import { Area, AreaChart, CartesianGrid, XAxis } from "recharts"

import type { AdminDashboardSendingStat } from "@/lib/admin-api"

import { DateRangePicker, type DateRangeFilter } from "@/components/date-range-picker"
import {
  Card,
  CardAction,
  CardContent,
  CardDescription,
  CardHeader,
  CardTitle,
} from "@/components/ui/card"
import {
  ChartContainer,
  ChartTooltip,
  ChartTooltipContent,
  type ChartConfig,
} from "@/components/ui/chart"
import {
  Select,
  SelectContent,
  SelectGroup,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from "@/components/ui/select"
import {
  ToggleGroup,
  ToggleGroupItem,
} from "@/components/ui/toggle-group"

export const description = "An interactive sending stats chart"

type ChartAreaInteractiveProps = {
  data: AdminDashboardSendingStat[]
  range: DashboardSendingStatsRange
  onRangeChange: (range: DashboardSendingStatsRange) => void
}

const chartConfig = {
  total: {
    label: "Total emails",
  },
  sent: {
    label: "Sent",
    color: "var(--primary)",
  },
  failed: {
    label: "Failed",
    color: "var(--destructive)",
  },
} satisfies ChartConfig

const timeRangeLabels = {
  "90d": "Last 3 months",
  "30d": "Last 30 days",
  "7d": "Last 7 days",
  custom: "Custom range",
} as const

export type DashboardSendingStatsRange = DateRangeFilter & {
  preset: keyof typeof timeRangeLabels
}

function parseChartDate(value: string): Date {
  return new Date(`${value}T00:00:00`)
}

function formatChartDate(value: string): string {
  return parseChartDate(value).toLocaleDateString("en-US", {
    month: "short",
    day: "numeric",
  })
}

function formatSelectedRangeLabel(range: DashboardSendingStatsRange): string {
  if (range.preset !== "custom") {
    return timeRangeLabels[range.preset]
  }

  if (range.from && range.to) {
    return `${formatChartDate(range.from)} - ${formatChartDate(range.to)}`
  }

  if (range.from) {
    return `Since ${formatChartDate(range.from)}`
  }

  if (range.to) {
    return `Until ${formatChartDate(range.to)}`
  }

  return timeRangeLabels.custom
}

export function ChartAreaInteractive({ data, range, onRangeChange }: ChartAreaInteractiveProps) {
  const selectedRangeLabel = formatSelectedRangeLabel(range)

  function updatePreset(value?: string): void {
    const preset = (value ?? "90d") as DashboardSendingStatsRange["preset"]

    if (preset === "custom") {
      onRangeChange({ preset: "custom", from: range.from, to: range.to })
      return
    }

    onRangeChange({ preset })
  }

  return (
    <Card className="@container/card">
      <CardHeader>
        <CardTitle>Sending Stats</CardTitle>
        <CardDescription>
          <span className="hidden @[540px]/card:block">
            Sent and failed email volume for {selectedRangeLabel.toLowerCase()}
          </span>
          <span className="@[540px]/card:hidden">{selectedRangeLabel}</span>
        </CardDescription>
        <CardAction className="flex flex-col items-end gap-2 @[767px]/card:flex-row">
          <ToggleGroup
            multiple={false}
            value={range.preset ? [range.preset] : []}
            onValueChange={(value) => updatePreset(value[0])}
            variant="outline"
            className="hidden *:data-[slot=toggle-group-item]:px-4! @[767px]/card:flex"
          >
            <ToggleGroupItem value="90d">Last 3 months</ToggleGroupItem>
            <ToggleGroupItem value="30d">Last 30 days</ToggleGroupItem>
            <ToggleGroupItem value="7d">Last 7 days</ToggleGroupItem>
            <ToggleGroupItem value="custom">Custom</ToggleGroupItem>
          </ToggleGroup>
          <Select
            value={range.preset}
            onValueChange={(value) => {
              if (value !== null) {
                updatePreset(value)
              }
            }}
          >
            <SelectTrigger
              className="flex w-40 **:data-[slot=select-value]:block **:data-[slot=select-value]:truncate @[767px]/card:hidden"
              size="sm"
              aria-label="Select date range"
            >
              <SelectValue placeholder="Last 3 months" />
            </SelectTrigger>
            <SelectContent className="rounded-xl">
              <SelectGroup>
                <SelectItem value="90d" className="rounded-lg">
                  Last 3 months
                </SelectItem>
                <SelectItem value="30d" className="rounded-lg">
                  Last 30 days
                </SelectItem>
                <SelectItem value="7d" className="rounded-lg">
                  Last 7 days
                </SelectItem>
                <SelectItem value="custom" className="rounded-lg">
                  Custom range
                </SelectItem>
              </SelectGroup>
            </SelectContent>
          </Select>
          {range.preset === "custom" ? (
            <DateRangePicker
              id="dashboard-sending-date-range"
              value={{ from: range.from, to: range.to }}
              onChange={(value) => onRangeChange({ preset: "custom", from: value?.from, to: value?.to })}
              className="w-40 md:w-60"
              placeholder="Pick dates"
            />
          ) : null}
        </CardAction>
      </CardHeader>
      <CardContent className="px-2 pt-4 sm:px-6 sm:pt-6">
        <ChartContainer
          config={chartConfig}
          className="aspect-auto h-[250px] w-full"
        >
          <AreaChart data={data}>
            <defs>
              <linearGradient id="fillSent" x1="0" y1="0" x2="0" y2="1">
                <stop
                  offset="5%"
                  stopColor="var(--color-sent)"
                  stopOpacity={1.0}
                />
                <stop
                  offset="95%"
                  stopColor="var(--color-sent)"
                  stopOpacity={0.1}
                />
              </linearGradient>
              <linearGradient id="fillFailed" x1="0" y1="0" x2="0" y2="1">
                <stop
                  offset="5%"
                  stopColor="var(--color-failed)"
                  stopOpacity={0.8}
                />
                <stop
                  offset="95%"
                  stopColor="var(--color-failed)"
                  stopOpacity={0.1}
                />
              </linearGradient>
            </defs>
            <CartesianGrid vertical={false} />
            <XAxis
              dataKey="date"
              tickLine={false}
              axisLine={false}
              tickMargin={8}
              minTickGap={32}
              tickFormatter={(value) => formatChartDate(String(value))}
            />
            <ChartTooltip
              cursor={false}
              content={
                <ChartTooltipContent
                  labelFormatter={(value) => formatChartDate(String(value))}
                  indicator="dot"
                />
              }
            />
            <Area
              dataKey="failed"
              type="natural"
              fill="url(#fillFailed)"
              stroke="var(--color-failed)"
              stackId="a"
            />
            <Area
              dataKey="sent"
              type="natural"
              fill="url(#fillSent)"
              stroke="var(--color-sent)"
              stackId="a"
            />
          </AreaChart>
        </ChartContainer>
      </CardContent>
    </Card>
  )
}
