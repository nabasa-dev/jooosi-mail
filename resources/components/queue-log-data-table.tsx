"use client"

import * as React from "react"
import {
  flexRender,
  getCoreRowModel,
  useReactTable,
  type ColumnDef,
  type PaginationState,
  type RowSelectionState,
  type SortingState,
  type VisibilityState,
} from "@tanstack/react-table"

import { buildAdminHashHref } from "@/admin/routes"
import { MailLogTablePagination } from "@/components/mail-log-table-pagination"
import { MailLogTableToolbar } from "@/components/mail-log-table-toolbar"
import { QueueLogTableViewOptions } from "@/components/queue-log-table-view-options"
import type { MailLogDateRangeFilter } from "@/components/mail-log-table-types"
import type { AdminMailLogFilterOption, AdminQueueLogQuery, AdminQueueMessage } from "@/lib/admin-api"
import { getQueueLogs } from "@/lib/admin-api"
import { formatAdminDateTime, titleCase } from "@/lib/admin-format"
import { getLogStatusVariant } from "@/lib/admin-log-helpers"
import { Badge } from "@/components/ui/badge"
import { Button } from "@/components/ui/button"
import { Checkbox } from "@/components/ui/checkbox"
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from "@/components/ui/table"
import { Tooltip, TooltipContent, TooltipTrigger } from "@/components/ui/tooltip"
import ArrowDown01Icon from "~icons/hugeicons/arrow-down-01"
import ArrowUp01Icon from "~icons/hugeicons/arrow-up-01"
import MailIcon from "~icons/tabler/mail"
import UnfoldMoreIcon from "~icons/hugeicons/unfold-more"

type QueueLogSortId = "id" | "status" | "priority" | "attempts" | "dateTime"

type QueueLogDataTableProps = {
  refreshToken?: number
}

const LOG_TABLE_POLL_MS = 15_000

const QUEUE_CLAIM_STALE_AFTER_SECONDS = 300

function getQueueMessageDateTime(message: AdminQueueMessage): string | null {
  return message.updatedAt ?? message.processedAt ?? message.claimedAt ?? message.availableAt ?? message.createdAt
}

function parseDateTime(value: string | null | undefined): Date | null {
  if (!value) {
    return null
  }

  const parsedValue = new Date(value)

  return Number.isNaN(parsedValue.getTime()) ? null : parsedValue
}

function formatDurationLabel(totalSeconds: number): string {
  if (totalSeconds < 60) {
    return `${totalSeconds}s`
  }

  const totalMinutes = Math.floor(totalSeconds / 60)

  if (totalMinutes < 60) {
    return `${totalMinutes}m`
  }

  const totalHours = Math.floor(totalMinutes / 60)

  if (totalHours < 24) {
    const remainingMinutes = totalMinutes % 60

    return remainingMinutes === 0 ? `${totalHours}h` : `${totalHours}h ${remainingMinutes}m`
  }

  const totalDays = Math.floor(totalHours / 24)
  const remainingHours = totalHours % 24

  return remainingHours === 0 ? `${totalDays}d` : `${totalDays}d ${remainingHours}h`
}

function getClaimAgeLabel(claimedAt: string | null | undefined, now: number): string | null {
  const claimedAtDate = parseDateTime(claimedAt)

  if (claimedAtDate === null) {
    return null
  }

  return formatDurationLabel(Math.max(0, Math.floor((now - claimedAtDate.getTime()) / 1000)))
}

function isStaleClaim(message: AdminQueueMessage, now: number): boolean {
  if (message.status !== "processing") {
    return false
  }

  const claimedAtDate = parseDateTime(message.claimedAt)

  if (claimedAtDate === null) {
    return false
  }

  return now - claimedAtDate.getTime() >= QUEUE_CLAIM_STALE_AFTER_SECONDS * 1000
}

function formatClaimedTooltipValue(message: AdminQueueMessage, now: number): string {
  const claimedAtLabel = formatAdminDateTime(message.claimedAt)
  const claimAgeLabel = getClaimAgeLabel(message.claimedAt, now)

  if (!claimAgeLabel) {
    return claimedAtLabel
  }

  return `${claimedAtLabel} (Age ${claimAgeLabel})`
}

function QueueDateValue({ message, now }: { message: AdminQueueMessage; now: number }) {
  const primaryDate = getQueueMessageDateTime(message)
  const stale = isStaleClaim(message, now)

  if (!primaryDate) {
    return "-"
  }

  return (
    <div className="flex flex-col gap-1">
      <Tooltip>
        <TooltipTrigger render={<span className="cursor-help underline decoration-dotted underline-offset-2" />}>
          {formatAdminDateTime(primaryDate)}
        </TooltipTrigger>
        <TooltipContent className="max-w-sm">
          <div className="flex flex-col gap-1.5">
            <div className="grid grid-cols-[auto_1fr] gap-2">
              <span className="opacity-70">Available</span>
              <span>{formatAdminDateTime(message.availableAt)}</span>
            </div>
            <div className="grid grid-cols-[auto_1fr] gap-2">
              <span className="opacity-70">Claimed</span>
              <span>{formatClaimedTooltipValue(message, now)}</span>
            </div>
            <div className="grid grid-cols-[auto_1fr] gap-2">
              <span className="opacity-70">Updated</span>
              <span>{formatAdminDateTime(primaryDate)}</span>
            </div>
          </div>
        </TooltipContent>
      </Tooltip>
      {stale ? (
        <div className="flex items-center gap-2 text-xs text-muted-foreground">
          <Badge variant="destructive">Stale</Badge>
        </div>
      ) : null}
    </div>
  )
}

function SortableHeader({
  column,
  title,
}: {
  column: {
    getIsSorted: () => false | "asc" | "desc"
    toggleSorting: (desc?: boolean) => void
  }
  title: string
}) {
  const direction = column.getIsSorted()

  return (
    <Button
      variant="ghost"
      size="sm"
      className="-ml-3 h-8"
      onClick={() => column.toggleSorting(column.getIsSorted() === "asc")}
    >
      {title}
      {direction === "asc" ? <ArrowUp01Icon data-icon="inline-end" /> : null}
      {direction === "desc" ? <ArrowDown01Icon data-icon="inline-end" /> : null}
      {direction === false ? <UnfoldMoreIcon data-icon="inline-end" /> : null}
    </Button>
  )
}

function resolveSortBy(sorting: SortingState): QueueLogSortId {
  const sortId = sorting[0]?.id

  if (sortId === "id" || sortId === "status" || sortId === "priority" || sortId === "attempts") {
    return sortId
  }

  return "dateTime"
}

export function QueueLogDataTable({ refreshToken = 0 }: QueueLogDataTableProps) {
  const [columnVisibility, setColumnVisibility] = React.useState<VisibilityState>({})
  const [rowSelection, setRowSelection] = React.useState<RowSelectionState>({})
  const [sorting, setSorting] = React.useState<SortingState>([
    {
      id: "dateTime",
      desc: true,
    },
  ])
  const [pagination, setPagination] = React.useState<PaginationState>({
    pageIndex: 0,
    pageSize: 25,
  })
  const [searchValue, setSearchValue] = React.useState("")
  const deferredSearchValue = React.useDeferredValue(searchValue)
  const [selectedStatuses, setSelectedStatuses] = React.useState<string[]>([])
  const [dateRange, setDateRange] = React.useState<MailLogDateRangeFilter | undefined>(undefined)
  const [rows, setRows] = React.useState<AdminQueueMessage[]>([])
  const [statusOptions, setStatusOptions] = React.useState<AdminMailLogFilterOption[]>([])
  const [totalRows, setTotalRows] = React.useState(0)
  const [pageCount, setPageCount] = React.useState(1)
  const [loading, setLoading] = React.useState(true)
  const [error, setError] = React.useState<string | null>(null)
  const [now, setNow] = React.useState(() => Date.now())

  const openRelatedMailLog = React.useCallback((mailLogId: number) => {
    if (typeof window === "undefined") {
      return
    }

    window.location.hash = buildAdminHashHref("/logs/mail", {
      id: mailLogId,
    }).slice(1)
  }, [])

  const sortBy = resolveSortBy(sorting)
  const sortDirection = sorting[0]?.desc ? "desc" : "asc"

  React.useEffect(() => {
    const intervalId = window.setInterval(() => {
      setNow(Date.now())
    }, 60_000)

    return () => {
      window.clearInterval(intervalId)
    }
  }, [])

  React.useEffect(() => {
    setPagination((currentPagination) =>
      currentPagination.pageIndex === 0
        ? currentPagination
        : {
            ...currentPagination,
            pageIndex: 0,
          },
    )
  }, [deferredSearchValue, selectedStatuses, dateRange?.from, dateRange?.to, sortBy, sortDirection, pagination.pageSize])

  const query = React.useMemo<AdminQueueLogQuery>(
    () => ({
      search: deferredSearchValue.trim() || undefined,
      statuses: selectedStatuses.length > 0 ? selectedStatuses : undefined,
      fromDate: dateRange?.from,
      toDate: dateRange?.to,
      page: pagination.pageIndex + 1,
      perPage: pagination.pageSize,
      sortBy,
      sortDirection,
    }),
    [dateRange?.from, dateRange?.to, deferredSearchValue, pagination.pageIndex, pagination.pageSize, selectedStatuses, sortBy, sortDirection],
  )

  React.useEffect(() => {
    let active = true
    let requestId = 0

    const loadQueueLogs = (showLoading: boolean) => {
      const currentRequestId = requestId + 1

      requestId = currentRequestId

      if (showLoading) {
        setLoading(true)
      }

      setError(null)

      void getQueueLogs(query)
        .then((response) => {
          if (!active || currentRequestId !== requestId) {
            return
          }

          setRows(response.items)
          setStatusOptions(response.filters.statuses)
          setTotalRows(response.pagination.total)
          setPageCount(response.pagination.totalPages)
          setPagination((currentPagination) => {
            const nextPageIndex = Math.max(0, response.pagination.page - 1)

            if (
              currentPagination.pageIndex === nextPageIndex &&
              currentPagination.pageSize === response.pagination.perPage
            ) {
              return currentPagination
            }

            return {
              pageIndex: nextPageIndex,
              pageSize: response.pagination.perPage,
            }
          })
        })
        .catch((caughtError) => {
          if (!active || currentRequestId !== requestId) {
            return
          }

          setError(caughtError instanceof Error ? caughtError.message : "The queue logs could not be loaded.")
        })
        .finally(() => {
          if (active && currentRequestId === requestId) {
            setLoading(false)
          }
        })
    }

    loadQueueLogs(true)

    const intervalId = window.setInterval(() => {
      loadQueueLogs(false)
    }, LOG_TABLE_POLL_MS)

    return () => {
      active = false
      window.clearInterval(intervalId)
    }
  }, [query, refreshToken])

  const columns = React.useMemo<ColumnDef<AdminQueueMessage>[]>(
    () => [
      {
        id: "select",
        header: ({ table }) => (
          <Checkbox
            checked={table.getIsAllPageRowsSelected()}
            indeterminate={table.getIsSomePageRowsSelected() && !table.getIsAllPageRowsSelected()}
            onCheckedChange={(value) => table.toggleAllPageRowsSelected(!!value)}
            aria-label="Select all queue messages"
          />
        ),
        cell: ({ row }) => (
          <Checkbox
            checked={row.getIsSelected()}
            onCheckedChange={(value) => row.toggleSelected(!!value)}
            aria-label={`Select queue message ${row.original.id}`}
          />
        ),
        enableSorting: false,
        enableHiding: false,
      },
      {
        accessorFn: (row) => `#${row.id}`,
        id: "id",
        header: ({ column }) => <SortableHeader column={column} title="ID" />,
        cell: ({ row }) => <span className="font-medium">#{row.original.id}</span>,
        enableHiding: false,
      },
      {
        accessorFn: (row) => row.mailLogId ?? -1,
        id: "mailLogId",
        header: () => <span>Mail</span>,
        cell: ({ row }) =>
          typeof row.original.mailLogId === "number" ? (
            <Button
              variant="secondary"
              size="sm"
              onClick={() => openRelatedMailLog(row.original.mailLogId as number)}
            >
              <MailIcon data-icon="inline-start" />
              #{row.original.mailLogId}
            </Button>
          ) : (
            "-"
          ),
        enableSorting: false,
      },
      {
        accessorKey: "status",
        id: "status",
        header: ({ column }) => <SortableHeader column={column} title="Status" />,
        cell: ({ row }) => {
          const statusLabel = titleCase(row.original.status)
          const statusVariant = getLogStatusVariant(row.original.status)

          if (!row.original.lastError) {
            return <Badge variant={statusVariant}>{statusLabel}</Badge>
          }

          return (
            <Tooltip>
              <TooltipTrigger render={<Badge variant={statusVariant} />}>{statusLabel}</TooltipTrigger>
              <TooltipContent className="max-w-sm whitespace-pre-wrap break-words">
                {row.original.lastError}
              </TooltipContent>
            </Tooltip>
          )
        },
      },
      {
        accessorKey: "priority",
        id: "priority",
        header: ({ column }) => <SortableHeader column={column} title="Priority" />,
        cell: ({ row }) => row.original.priority,
      },
      {
        accessorFn: (row) => row.attemptCount,
        id: "attempts",
        header: ({ column }) => <SortableHeader column={column} title="Attempts" />,
        cell: ({ row }) => (
          <span>
            {row.original.attemptCount} / {row.original.maxAttempts}
          </span>
        ),
      },
      {
        accessorFn: (row) => getQueueMessageDateTime(row) ?? "",
        id: "dateTime",
        header: ({ column }) => <SortableHeader column={column} title="Date" />,
        cell: ({ row }) => <QueueDateValue message={row.original} now={now} />,
      },
    ],
    [now, openRelatedMailLog],
  )

  const table = useReactTable({
    data: rows,
    columns,
    state: {
      sorting,
      pagination,
      columnVisibility,
      rowSelection,
    },
    pageCount,
    getRowId: (row) => row.id.toString(),
    enableRowSelection: true,
    manualPagination: true,
    manualSorting: true,
    onRowSelectionChange: setRowSelection,
    onSortingChange: setSorting,
    onColumnVisibilityChange: setColumnVisibility,
    onPaginationChange: setPagination,
    getCoreRowModel: getCoreRowModel(),
  })

  return (
    <div className="flex flex-col gap-4">
      <MailLogTableToolbar
        searchValue={searchValue}
        onSearchChange={setSearchValue}
        searchPlaceholder="Search queue logs..."
        statusOptions={statusOptions}
        selectedStatuses={selectedStatuses}
        onStatusesChange={setSelectedStatuses}
        connectionOptions={[]}
        selectedConnectionIds={[]}
        onConnectionIdsChange={() => undefined}
        dateRange={dateRange}
        onDateRangeChange={setDateRange}
        onReset={() => {
          setSearchValue("")
          setSelectedStatuses([])
          setDateRange(undefined)
        }}
        viewOptions={<QueueLogTableViewOptions table={table} />}
      />

      {error ? <div className="text-sm text-destructive">{error}</div> : null}

      <div className="overflow-hidden rounded-md border">
        <Table>
          <TableHeader>
            {table.getHeaderGroups().map((headerGroup) => (
              <TableRow key={headerGroup.id}>
                {headerGroup.headers.map((header) => (
                  <TableHead key={header.id} colSpan={header.colSpan}>
                    {header.isPlaceholder ? null : flexRender(header.column.columnDef.header, header.getContext())}
                  </TableHead>
                ))}
              </TableRow>
            ))}
          </TableHeader>
          <TableBody>
            {table.getRowModel().rows.length > 0 ? (
              table.getRowModel().rows.map((row) => (
                <TableRow key={row.id}>
                  {row.getVisibleCells().map((cell) => (
                    <TableCell key={cell.id}>{flexRender(cell.column.columnDef.cell, cell.getContext())}</TableCell>
                  ))}
                </TableRow>
              ))
            ) : (
              <TableRow>
                <TableCell colSpan={columns.length} className="h-24 text-center text-muted-foreground">
                  {loading ? "Loading queue logs..." : "No queue messages match the current search and filters."}
                </TableCell>
              </TableRow>
            )}
          </TableBody>
        </Table>
      </div>

      <MailLogTablePagination table={table} totalRows={totalRows} />
    </div>
  )
}

export default QueueLogDataTable
