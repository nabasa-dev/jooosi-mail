import type { AdminConnectionSummary } from "@/lib/admin-api"

export type ConnectionOperationalStatus = "available" | "blocked" | "disabled"

export function getConnectionOperationalStatus(
  connection: Pick<AdminConnectionSummary, "enabled" | "available">,
): ConnectionOperationalStatus {
  if (!connection.enabled) {
    return "disabled"
  }

  return connection.available ? "available" : "blocked"
}

export function getConnectionOperationalLabel(
  connection: Pick<AdminConnectionSummary, "enabled" | "available">,
): string {
  const status = getConnectionOperationalStatus(connection)

  if (status === "available") {
    return "Available"
  }

  if (status === "blocked") {
    return "Blocked"
  }

  return "Disabled"
}

export function getConnectionStatusVariant(
  status: ConnectionOperationalStatus,
): "default" | "secondary" | "destructive" | "outline" {
  switch (status) {
    case "available":
      return "default"
    case "disabled":
      return "outline"
    default:
      return "destructive"
  }
}

export function supportsConnectionWebhooks(
  connection: Pick<AdminConnectionSummary, "profile">,
): boolean {
  return connection.profile.supportsWebhooks
}

export function hasEnabledConnectionWebhooks(
  connection: Pick<AdminConnectionSummary, "profile" | "webhookEnabled">,
): boolean {
  return supportsConnectionWebhooks(connection) && connection.webhookEnabled
}
