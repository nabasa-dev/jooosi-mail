import type { AdminWebhookLog } from "@/lib/admin-api"

export type WebhookLogTableRow = AdminWebhookLog & {
  dateTime: string | null
  connectionLabel: string
  mailLogLabel: string
}

function resolveWebhookDateTime(log: AdminWebhookLog): string | null {
  return log.occurredAt ?? log.createdAt
}

export function normalizeWebhookLogRows(data: AdminWebhookLog[]): WebhookLogTableRow[] {
  return data.map((log) => ({
    ...log,
    dateTime: resolveWebhookDateTime(log),
    connectionLabel: log.connectionName ?? "Unassigned",
    mailLogLabel: log.mailLogId !== null ? `#${log.mailLogId}` : "-",
  }))
}
