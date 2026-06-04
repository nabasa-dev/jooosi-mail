"use client"

import * as React from "react"
import type { AdminMailLogFilterOption } from "@/lib/admin-api"
import type { MailLogDateRangeFilter } from "@/components/mail-log-table-types"
import { MailLogDateRangePicker } from "@/components/mail-log-date-range-picker"
import { MailLogTableFacetedFilter } from "@/components/mail-log-table-faceted-filter"
import { Button } from "@/components/ui/button"
import { Input } from "@/components/ui/input"

type MailLogTableToolbarProps = {
  searchValue: string
  onSearchChange: (value: string) => void
  searchPlaceholder?: string
  statusOptions: AdminMailLogFilterOption[]
  selectedStatuses: string[]
  onStatusesChange: (values: string[]) => void
  connectionOptions: AdminMailLogFilterOption[]
  selectedConnectionIds: string[]
  onConnectionIdsChange: (values: string[]) => void
  dateRange?: MailLogDateRangeFilter
  onDateRangeChange: (value?: MailLogDateRangeFilter) => void
  onReset: () => void
  viewOptions?: React.ReactNode
}

export function MailLogTableToolbar({
  searchValue,
  onSearchChange,
  searchPlaceholder = "Search email logs...",
  statusOptions,
  selectedStatuses,
  onStatusesChange,
  connectionOptions,
  selectedConnectionIds,
  onConnectionIdsChange,
  dateRange,
  onDateRangeChange,
  onReset,
  viewOptions,
}: MailLogTableToolbarProps) {
  const isFiltered =
    searchValue.trim() !== "" ||
    selectedStatuses.length > 0 ||
    selectedConnectionIds.length > 0 ||
    dateRange !== undefined

  const showStatusFilter = statusOptions.length > 0
  const showConnectionFilter = connectionOptions.length > 0

  return (
    <div className="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
      <div className="flex flex-1 flex-wrap items-center gap-2">
        <Input
          placeholder={searchPlaceholder}
          value={searchValue}
          onChange={(event) => onSearchChange(event.target.value)}
          className="h-8 w-full md:w-[170px] lg:w-[220px]"
        />

        {showStatusFilter ? (
          <MailLogTableFacetedFilter
            title="Status"
            options={statusOptions}
            selectedValues={selectedStatuses}
            onChange={onStatusesChange}
          />
        ) : null}

        {showConnectionFilter ? (
          <MailLogTableFacetedFilter
            title="Connection"
            options={connectionOptions}
            selectedValues={selectedConnectionIds}
            onChange={onConnectionIdsChange}
          />
        ) : null}

        <MailLogDateRangePicker value={dateRange} onChange={onDateRangeChange} />

        {isFiltered ? (
          <Button variant="ghost" size="sm" onClick={onReset}>
            Reset
          </Button>
        ) : null}
      </div>

      <div className="flex items-center gap-2">
        {viewOptions}
      </div>
    </div>
  )
}

export default MailLogTableToolbar
