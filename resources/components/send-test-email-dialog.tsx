"use client"

import * as React from "react"
import { toast } from "sonner"

import { Button } from "@/components/ui/button"
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from "@/components/ui/dialog"
import {
  Field,
  FieldDescription,
  FieldError,
  FieldGroup,
  FieldLabel,
} from "@/components/ui/field"
import { Input } from "@/components/ui/input"
import {
  Select,
  SelectContent,
  SelectGroup,
  SelectItem,
  SelectTrigger,
} from "@/components/ui/select"
import { Separator } from "@/components/ui/separator"
import { getConnections, sendTestEmail, type AdminConnectionSummary } from "@/lib/admin-api"
import Loader2Icon from "~icons/tabler/loader-2"
import MailIcon from "~icons/tabler/mail"

const DEFAULT_SUBJECT = "Omni Mail test"
const DEFAULT_CONNECTION_VALUE = "default"

type SendTestEmailDialogProps = {
  open: boolean
  onOpenChange: (open: boolean) => void
  onSent?: () => void
}

function validateRecipient(value: string): string | null {
  const recipient = value.trim()

  if (recipient === "") {
    return "Enter a recipient email address."
  }

  if (/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(recipient) === false) {
    return "Enter a valid recipient email address."
  }

  return null
}

export function SendTestEmailDialog({
  open,
  onOpenChange,
  onSent,
}: SendTestEmailDialogProps) {
  const recipientId = React.useId()
  const subjectId = React.useId()
  const connectionId = React.useId()
  const [recipient, setRecipient] = React.useState("")
  const [subject, setSubject] = React.useState(DEFAULT_SUBJECT)
  const [selectedConnectionId, setSelectedConnectionId] = React.useState(DEFAULT_CONNECTION_VALUE)
  const [connections, setConnections] = React.useState<AdminConnectionSummary[]>([])
  const [connectionsLoading, setConnectionsLoading] = React.useState(false)
  const [submitted, setSubmitted] = React.useState(false)
  const [sending, setSending] = React.useState(false)
  const [error, setError] = React.useState<string | null>(null)
  const recipientError = submitted ? validateRecipient(recipient) : null
  const selectedConnectionLabel = React.useMemo(() => {
    if (selectedConnectionId === DEFAULT_CONNECTION_VALUE) {
      return "Use current default"
    }

    const selectedConnection = connections.find((connection) => `${connection.id}` === selectedConnectionId)

    if (!selectedConnection) {
      return "Select connection"
    }

    return selectedConnection.default ? `${selectedConnection.name} (default)` : selectedConnection.name
  }, [connections, selectedConnectionId])

  const resetForm = React.useCallback(() => {
    setRecipient("")
    setSubject(DEFAULT_SUBJECT)
    setSelectedConnectionId(DEFAULT_CONNECTION_VALUE)
    setSubmitted(false)
    setError(null)
  }, [])

  React.useEffect(() => {
    let active = true

    if (!open) {
      return () => {
        active = false
      }
    }

    setConnectionsLoading(true)

    void getConnections()
      .then((response) => {
        if (!active) {
          return
        }

        setConnections(response.connections.filter((connection) => connection.enabled))
      })
      .catch(() => {
        if (active) {
          setConnections([])
        }
      })
      .finally(() => {
        if (active) {
          setConnectionsLoading(false)
        }
      })

    return () => {
      active = false
    }
  }, [open])

  const handleOpenChange = React.useCallback(
    (nextOpen: boolean) => {
      if (sending) {
        return
      }

      if (!nextOpen) {
        resetForm()
      }

      onOpenChange(nextOpen)
    },
    [onOpenChange, resetForm, sending],
  )

  const handleSubmit = async (event: React.FormEvent<HTMLFormElement>) => {
    event.preventDefault()
    setSubmitted(true)
    setError(null)

    const nextRecipientError = validateRecipient(recipient)

    if (nextRecipientError) {
      return
    }

    setSending(true)

    try {
      const response = await sendTestEmail({
        to: recipient.trim(),
        subject: subject.trim() || DEFAULT_SUBJECT,
        connectionId: selectedConnectionId === DEFAULT_CONNECTION_VALUE ? undefined : Number(selectedConnectionId),
      })

      toast.success(response.message)
      onSent?.()
      resetForm()
      onOpenChange(false)
    } catch (caughtError) {
      const errorMessage = caughtError instanceof Error ? caughtError.message : "The test email could not be sent."

      setError(errorMessage)
      toast.error(errorMessage)
    } finally {
      setSending(false)
    }
  }

  return (
    <Dialog open={open} onOpenChange={handleOpenChange}>
      <DialogContent className="overflow-hidden p-0 sm:max-w-xl" showCloseButton={!sending}>
        <form onSubmit={handleSubmit} className="flex flex-col">
          <DialogHeader className="border-b px-6 py-5 pr-14">
            <DialogTitle>Send test email</DialogTitle>
            <DialogDescription>
              Send a generated text and HTML diagnostic email through the current delivery configuration.
            </DialogDescription>
          </DialogHeader>

          <div className="flex flex-col gap-4 px-6 py-5">
            <section className="flex flex-col gap-3">
              <FieldGroup className="gap-4">
                <Field
                  className="gap-3 rounded-lg border bg-muted/20 p-4 shadow-xs"
                  data-invalid={recipientError !== null || undefined}
                >
                  <FieldLabel htmlFor={recipientId} className="gap-1">
                    {"Recipient"}
                    <span aria-hidden="true" className="text-destructive">
                      *
                    </span>
                  </FieldLabel>
                  <Input
                    id={recipientId}
                    type="email"
                    value={recipient}
                    placeholder="you@example.com"
                    autoComplete="email"
                    disabled={sending}
                    autoFocus
                    required
                    aria-required="true"
                    aria-invalid={recipientError !== null || undefined}
                    onChange={(event) => setRecipient(event.target.value)}
                  />
                  <FieldDescription>The address that should receive the test message.</FieldDescription>
                  <FieldError>{recipientError}</FieldError>
                </Field>

                <Field className="gap-3 rounded-lg border bg-muted/20 p-4 shadow-xs">
                  <FieldLabel htmlFor={subjectId}>Subject</FieldLabel>
                  <Input
                    id={subjectId}
                    value={subject}
                    disabled={sending}
                    onChange={(event) => setSubject(event.target.value)}
                  />
                </Field>
              </FieldGroup>
            </section>

            <Separator />

            <section className="flex flex-col gap-3">
              <Field className="gap-3 rounded-lg border bg-muted/20 p-4 shadow-xs">
                <FieldLabel htmlFor={connectionId}>Connection</FieldLabel>
                <Select
                  value={selectedConnectionId}
                  onValueChange={(value) => setSelectedConnectionId(value ?? DEFAULT_CONNECTION_VALUE)}
                >
                  <SelectTrigger id={connectionId} className="w-full" disabled={sending || connectionsLoading}>
                    <span data-slot="select-value" className="flex flex-1 items-center text-left">
                      {selectedConnectionLabel}
                    </span>
                  </SelectTrigger>
                  <SelectContent>
                    <SelectGroup>
                      <SelectItem value={DEFAULT_CONNECTION_VALUE}>Use current default</SelectItem>
                      {connections.map((connection) => (
                        <SelectItem key={connection.id} value={`${connection.id}`}>
                          {connection.default ? `${connection.name} (default)` : connection.name}
                        </SelectItem>
                      ))}
                    </SelectGroup>
                  </SelectContent>
                </Select>
                <FieldDescription>
                  Leave unchanged to use the current default routing configuration.
                </FieldDescription>
              </Field>
            </section>

            {error ? (
              <div
                role="alert"
                className="rounded-md border border-destructive/20 bg-destructive/5 px-3 py-2 text-sm text-destructive"
              >
                {error}
              </div>
            ) : null}
          </div>

          <DialogFooter className="border-t bg-muted/20 px-6 py-4 sm:flex-row sm:justify-end">
            <Button
              type="button"
              variant="outline"
              disabled={sending}
              className="w-full sm:w-auto"
              onClick={() => handleOpenChange(false)}
            >
              Cancel
            </Button>
            <Button type="submit" disabled={sending} className="w-full sm:w-auto">
              {sending ? (
                <Loader2Icon data-icon="inline-start" className="animate-spin" />
              ) : (
                <MailIcon data-icon="inline-start" />
              )}
              {sending ? "Sending..." : "Send test"}
            </Button>
          </DialogFooter>
        </form>
      </DialogContent>
    </Dialog>
  )
}
