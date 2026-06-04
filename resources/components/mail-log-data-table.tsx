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
import { toast } from "sonner"

import { MailLogDetailsDialog } from "@/components/mail-log-details-dialog"
import { MailLogTablePagination } from "@/components/mail-log-table-pagination"
import { MailLogTableToolbar } from "@/components/mail-log-table-toolbar"
import { normalizeMailLogRows, type MailLogDateRangeFilter, type MailLogTableRow } from "@/components/mail-log-table-types"
import { MailLogTableViewOptions } from "@/components/mail-log-table-view-options"
import type { AdminMailLogFilterOption, AdminMailLogQuery } from "@/lib/admin-api"
import { getMailLog, getMailLogs } from "@/lib/admin-api"
import { buildAdminHashHref, parseAdminHashLocation } from "@/admin/routes"
import { formatAdminDateTime, titleCase } from "@/lib/admin-format"
import { getLogStatusVariant } from "@/lib/admin-log-helpers"
import { Badge } from "@/components/ui/badge"
import { Button } from "@/components/ui/button"
import { Checkbox } from "@/components/ui/checkbox"
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuTrigger,
} from "@/components/ui/dropdown-menu"
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from "@/components/ui/table"
import AlertCircleIcon from "~icons/tabler/alert-circle"
import ArrowDown01Icon from "~icons/hugeicons/arrow-down-01"
import ArrowUp01Icon from "~icons/hugeicons/arrow-up-01"
import CircleCheckIcon from "~icons/tabler/circle-check"
import ClockIcon from "~icons/tabler/clock"
import DatabaseIcon from "~icons/tabler/database"
import Loader2Icon from "~icons/tabler/loader-2"
import MoreVerticalCircle01Icon from "~icons/hugeicons/more-vertical-circle-01"
import UnfoldMoreIcon from "~icons/hugeicons/unfold-more"

type MailLogSortId = "id" | "subject" | "status" | "dateTime" | "connection"

type MailLogDataTableProps = {
  refreshToken?: number
}

const LOG_TABLE_POLL_MS = 15_000

function getMailStatusIcon(status: string) {
  switch (status) {
    case "sent":
      return CircleCheckIcon
    case "processing":
      return Loader2Icon
    case "pending":
    case "queued":
      return ClockIcon
    case "failed":
      return AlertCircleIcon
    default:
      return ClockIcon
  }
}

function subscribeToHashChange(callback: () => void): () => void {
  if (typeof window === "undefined") {
    return () => undefined
  }

  window.addEventListener("hashchange", callback)

  return () => window.removeEventListener("hashchange", callback)
}

function getMailLogIdFromHash(): number | null {
  if (typeof window === "undefined") {
    return null
  }

  const mailLogId = parseAdminHashLocation(window.location.hash).searchParams.get("id")

  if (!mailLogId || /^\d+$/.test(mailLogId) === false) {
    return null
  }

  return Number(mailLogId)
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

function resolveSortBy(sorting: SortingState): MailLogSortId {
  const sortId = sorting[0]?.id

  if (sortId === "id" || sortId === "subject" || sortId === "status" || sortId === "connection") {
    return sortId
  }

  return "dateTime"
}

export function MailLogDataTable({ refreshToken = 0 }: MailLogDataTableProps) {
  const [selectedLog, setSelectedLog] = React.useState<MailLogTableRow | null>(null)
  const [rowSelection, setRowSelection] = React.useState<RowSelectionState>({})
  const [columnVisibility, setColumnVisibility] = React.useState<VisibilityState>({})
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
  const [selectedConnectionIds, setSelectedConnectionIds] = React.useState<string[]>([])
  const [dateRange, setDateRange] = React.useState<MailLogDateRangeFilter | undefined>(undefined)
  const [rows, setRows] = React.useState<MailLogTableRow[]>([])
  const [statusOptions, setStatusOptions] = React.useState<AdminMailLogFilterOption[]>([])
  const [connectionOptions, setConnectionOptions] = React.useState<AdminMailLogFilterOption[]>([])
  const [totalRows, setTotalRows] = React.useState(0)
  const [pageCount, setPageCount] = React.useState(1)
  const [loading, setLoading] = React.useState(true)
  const [error, setError] = React.useState<string | null>(null)
  const targetedMailLogId = React.useSyncExternalStore(
    subscribeToHashChange,
    getMailLogIdFromHash,
    () => null,
  )

  const sortBy = resolveSortBy(sorting)
  const sortDirection = sorting[0]?.desc ? "desc" : "asc"

  const openConnection = React.useCallback((connectionId: number) => {
    if (typeof window === "undefined") {
      return
    }

    window.location.hash = buildAdminHashHref("/connections", {
      id: connectionId,
    }).slice(1)
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
  }, [
    deferredSearchValue,
    selectedStatuses,
    selectedConnectionIds,
    dateRange?.from,
    dateRange?.to,
    sortBy,
    sortDirection,
    pagination.pageSize,
  ])

  const query = React.useMemo<AdminMailLogQuery>(
    () => ({
      search: deferredSearchValue.trim() || undefined,
      statuses: selectedStatuses.length > 0 ? selectedStatuses : undefined,
      connectionIds: selectedConnectionIds.length > 0 ? selectedConnectionIds : undefined,
      fromDate: dateRange?.from,
      toDate: dateRange?.to,
      page: pagination.pageIndex + 1,
      perPage: pagination.pageSize,
      sortBy,
      sortDirection,
    }),
    [
      dateRange?.from,
      dateRange?.to,
      deferredSearchValue,
      pagination.pageIndex,
      pagination.pageSize,
      selectedConnectionIds,
      selectedStatuses,
      sortBy,
      sortDirection,
    ],
  )

  React.useEffect(() => {
    let active = true
    let requestId = 0

    const loadMailLogs = (showLoading: boolean) => {
      const currentRequestId = requestId + 1

      requestId = currentRequestId

      if (showLoading) {
        setLoading(true)
      }

      setError(null)

      void getMailLogs(query)
        .then((response) => {
          if (!active || currentRequestId !== requestId) {
            return
          }

          setRows(normalizeMailLogRows(response.items))
          setStatusOptions(response.filters.statuses)
          setConnectionOptions(response.filters.connections)
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

          setError(caughtError instanceof Error ? caughtError.message : "The email logs could not be loaded.")
        })
        .finally(() => {
          if (active && currentRequestId === requestId) {
            setLoading(false)
          }
        })
    }

    loadMailLogs(true)

    const intervalId = window.setInterval(() => {
      loadMailLogs(false)
    }, LOG_TABLE_POLL_MS)

    return () => {
      active = false
      window.clearInterval(intervalId)
    }
  }, [query, refreshToken])

  const columns = React.useMemo<ColumnDef<MailLogTableRow>[]>(
    () => [
      {
        id: "select",
        header: ({ table }) => (
          <Checkbox
            checked={table.getIsAllPageRowsSelected()}
            indeterminate={table.getIsSomePageRowsSelected() && !table.getIsAllPageRowsSelected()}
            onCheckedChange={(value) => table.toggleAllPageRowsSelected(!!value)}
            aria-label="Select all mail logs"
          />
        ),
        cell: ({ row }) => (
          <Checkbox
            checked={row.getIsSelected()}
            onCheckedChange={(value) => row.toggleSelected(!!value)}
            aria-label={`Select mail log ${row.original.id}`}
          />
        ),
        enableSorting: false,
        enableHiding: false,
      },
      {
        accessorFn: (row) => `#${row.id}`,
        id: "id",
        header: ({ column }) => <SortableHeader column={column} title="ID" />,
        cell: ({ row }) => (
          <Button
            variant="link"
            className="h-auto justify-start px-0 text-left"
            onClick={() => openMailLogDetails(row.original)}
          >
            #{row.original.id}
          </Button>
        ),
      },
      {
        accessorKey: "subject",
        id: "subject",
        header: ({ column }) => <SortableHeader column={column} title="Subject" />,
        cell: ({ row }) => (
          <div className="flex min-w-0 flex-col gap-1">
            <Button
              variant="link"
              className="h-auto justify-start px-0 text-left"
              onClick={() => openMailLogDetails(row.original)}
            >
              {row.original.subject || "(No subject)"}
            </Button>
            {row.original.lastError ? (
              <span className="max-w-80 truncate text-xs text-muted-foreground">
                {row.original.lastError}
              </span>
            ) : null}
          </div>
        ),
      },
      {
        accessorKey: "toSummary",
        id: "to",
        header: ({ column }) => <SortableHeader column={column} title="To" />,
        cell: ({ row }) => <span className="block max-w-64 truncate">{row.original.toSummary}</span>,
        enableSorting: false,
      },
      {
        accessorKey: "dateTime",
        id: "dateTime",
        header: ({ column }) => <SortableHeader column={column} title="Date time" />,
        cell: ({ row }) => formatAdminDateTime(row.original.dateTime),
      },
      {
        accessorFn: (row) => row.connectionLabel,
        id: "connection",
        header: ({ column }) => <SortableHeader column={column} title="Connection" />,
        cell: ({ row }) => (
          <Button
            variant="secondary"
            size="sm"
            disabled={row.original.finalConnectionId === null}
            onClick={() => {
              if (row.original.finalConnectionId === null) {
                return
              }

              openConnection(row.original.finalConnectionId)
            }}
          >
            <DatabaseIcon data-icon="inline-start" />
            {row.original.connectionLabel}
          </Button>
        ),
      },
      {
        accessorKey: "status",
        id: "status",
        header: ({ column }) => <SortableHeader column={column} title="Status" />,
        cell: ({ row }) => {
          const StatusIcon = getMailStatusIcon(row.original.status)

          return (
            <Badge variant={getLogStatusVariant(row.original.status)}>
              <StatusIcon data-icon="inline-start" />
              {titleCase(row.original.status)}
            </Badge>
          )
        },
      },
      {
        id: "actions",
        enableSorting: false,
        enableHiding: false,
        cell: ({ row }) => (
          <DropdownMenu>
            <DropdownMenuTrigger
              render={<Button variant="ghost" size="icon-sm" className="data-open:bg-muted" />}
            >
              <MoreVerticalCircle01Icon />
              <span className="sr-only">Open mail log actions</span>
            </DropdownMenuTrigger>
            <DropdownMenuContent align="end" className="w-44">
              <DropdownMenuItem onClick={() => openMailLogDetails(row.original)}>
                View details
              </DropdownMenuItem>
              <DropdownMenuItem
                disabled={!row.original.transportMessageId}
                onClick={() => {
                  const transportMessageId = row.original.transportMessageId

                  if (!transportMessageId) {
                    return
                  }

                  void navigator.clipboard.writeText(transportMessageId)
                  toast.success("Transport message id copied.")
                }}
              >
                Copy transport id
              </DropdownMenuItem>
            </DropdownMenuContent>
          </DropdownMenu>
        ),
      },
    ],
    [openConnection],
  )

  const table = useReactTable({
    data: rows,
    columns,
    state: {
      sorting,
      columnVisibility,
      rowSelection,
      pagination,
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

  const syncMailLogHash = React.useCallback((mailLogId: number | null) => {
    if (typeof window === "undefined") {
      return
    }

    const { searchParams } = parseAdminHashLocation(window.location.hash)

    if (mailLogId === null) {
      searchParams.delete("id")
    } else {
      searchParams.set("id", `${mailLogId}`)
    }

    const nextUrl = new URL(window.location.href)
    nextUrl.hash = buildAdminHashHref("/logs/mail", searchParams)
    window.history.replaceState(window.history.state, "", nextUrl)
  }, [])

  const openMailLogDetails = React.useCallback(
    (mailLog: MailLogTableRow) => {
      setSelectedLog(mailLog)
      syncMailLogHash(mailLog.id)
    },
    [syncMailLogHash],
  )

  React.useEffect(() => {
    if (targetedMailLogId === null) {
      return
    }

    if (selectedLog?.id === targetedMailLogId) {
      return
    }

    const currentRow = rows.find((row) => row.id === targetedMailLogId)

    if (currentRow) {
      setSelectedLog(currentRow)
      return
    }

    let active = true

    void getMailLog(targetedMailLogId)
      .then((response) => {
        if (!active || !response.item) {
          return
        }

        const [mailLog] = normalizeMailLogRows([response.item])

        if (mailLog) {
          setSelectedLog(mailLog)
        }
      })
      .catch(() => undefined)

    return () => {
      active = false
    }
  }, [rows, selectedLog?.id, targetedMailLogId])

  return (
    <>
      <div className="flex flex-col gap-4">
        <MailLogTableToolbar
          searchValue={searchValue}
          onSearchChange={setSearchValue}
          statusOptions={statusOptions}
          selectedStatuses={selectedStatuses}
          onStatusesChange={setSelectedStatuses}
          connectionOptions={connectionOptions}
          selectedConnectionIds={selectedConnectionIds}
          onConnectionIdsChange={setSelectedConnectionIds}
          dateRange={dateRange}
          onDateRangeChange={setDateRange}
          onReset={() => {
            setSearchValue("")
            setSelectedStatuses([])
            setSelectedConnectionIds([])
            setDateRange(undefined)
          }}
          viewOptions={<MailLogTableViewOptions table={table} />}
        />

        {error ? <div className="text-sm text-destructive">{error}</div> : null}

        <div className="overflow-hidden rounded-md border">
          <Table>
            <TableHeader>
              {table.getHeaderGroups().map((headerGroup) => (
                <TableRow key={headerGroup.id}>
                  {headerGroup.headers.map((header) => (
                    <TableHead key={header.id} colSpan={header.colSpan}>
                      {header.isPlaceholder
                        ? null
                        : flexRender(header.column.columnDef.header, header.getContext())}
                    </TableHead>
                  ))}
                </TableRow>
              ))}
            </TableHeader>
            <TableBody>
              {table.getRowModel().rows.length > 0 ? (
                table.getRowModel().rows.map((row) => (
                  <TableRow key={row.id} data-state={row.getIsSelected() && "selected"}>
                    {row.getVisibleCells().map((cell) => (
                      <TableCell key={cell.id}>
                        {flexRender(cell.column.columnDef.cell, cell.getContext())}
                      </TableCell>
                    ))}
                  </TableRow>
                ))
              ) : (
                <TableRow>
                  <TableCell colSpan={columns.length} className="h-24 text-center text-muted-foreground">
                    {loading ? "Loading email logs..." : "No email logs match the current search and filters."}
                  </TableCell>
                </TableRow>
              )}
            </TableBody>
          </Table>
        </div>

        <MailLogTablePagination table={table} totalRows={totalRows} />
      </div>

      <MailLogDetailsDialog
        log={selectedLog}
        open={selectedLog !== null}
        onOpenChange={(open) => {
          if (!open) {
            setSelectedLog(null)
            syncMailLogHash(null)
          }
        }}
      />
    </>
  )
}

export default MailLogDataTable
