export function getLogStatusVariant(
  status: string,
): "default" | "secondary" | "destructive" | "outline" {
  switch (status) {
    case "sent":
    case "delivered":
    case "completed":
    case "available":
      return "default"
    case "queued":
    case "pending":
    case "processing":
      return "secondary"
    case "failed":
    case "bounced":
    case "blocked":
      return "destructive"
    default:
      return "outline"
  }
}

export function getWebhookEventVariant(
  eventType: string,
): "default" | "secondary" | "destructive" | "outline" {
  switch (eventType) {
    case "queued":
    case "pending":
    case "processing":
      return "secondary"
    case "failed":
    case "bounced":
    case "blocked":
      return "destructive"
    default:
      return "outline"
  }
}
