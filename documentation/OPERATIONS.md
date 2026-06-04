# Omni Mail Operations

This guide covers how Omni Mail is configured and operated today. The project is still in its initial phase, so admin workflows, provider setup guidance, and operator playbooks are still being hardened.

## Operating Model

Omni Mail is currently administered through both the WordPress admin UI and WP-CLI. The plugin intercepts `wp_mail()`, stores normalized mail records and delivery-attempt history according to the configured email log retention policy, then delivers synchronously or through its database-backed queue.

The built-in profile catalog currently includes core transports (`smtp`, `sendmail`, `native`, `null`) plus provider profiles for `ahasend`, `azure`, `bird`, `brevo`, `cloudflare`, `elasticemail`, `emailit`, `gmail`, `infobip`, `mailersend`, `mailgun`, `mailjet`, `mailomat`, `mailpace`, `mailtrap`, `mandrill`, `microsoftgraph`, `pepipost`, `postal`, `postmark`, `resend`, `scaleway`, `sendgrid`, `sendlayer`, `sendpulse`, `ses`, `smtp2go`, `smtpcom`, `sparkpost`, `sweego`, `tosend`, `zeptomail`, and `zohomail`.

Use `wp omni-mail connection:profiles` as the authoritative source for supported schemes, field names, and webhook support.

## Admin UI

Open the Omni Mail admin app from the WordPress dashboard or directly at:

```text
wp-admin/admin.php?page=omni-mail
```

Current admin screens include:

- Dashboard for delivery mode, routing strategy, connection health, queue counts, mail counts, webhook counts, sending trends, recent attempts, recent webhooks, and failed messages
- Connections for profile discovery, connection CRUD, enabled/default state, sender overrides, rate limits, circuit-breaker settings, webhook settings, webhook URL visibility, and masked secret state
- Email Logs for message lifecycle rows, delivery-attempt detail, filtering, and test email sending
- Queue Logs for queued, deferred, processing, stale, and failed work
- Webhook Logs for normalized provider callback records
- Settings for interception, sender policy, return-path policy, logging retention, delivery mode, routing strategy, rate limits, circuit breaker defaults, and queue retry policy

The UI is functional but still early. Keep WP-CLI available for incident response, automation, and workflows that are not yet covered by polished admin screens.

## Activation and Runtime

On activation, Omni Mail:

- runs database migrations,
- creates the core mail and queue tables,
- creates routing-state tables for circuit breakers and rate limits,
- schedules recurring Action Scheduler queue processing.

At runtime, Omni Mail:

- boots a compiled Symfony container,
- validates the compiled container against cache metadata and a source hash,
- discovers services, commands, hooks, controllers, profiles, and handlers,
- registers WordPress hooks, REST routes, and WP-CLI commands,
- intercepts `wp_mail()` when Omni Mail is enabled.

### Container Cache

Use WP-CLI to inspect or clear the compiled container cache:

```bash
wp omni-mail container:status
wp omni-mail container:clear
```

In production, Omni Mail only reuses the compiled container when both the cache file and metadata file are present and their source hash matches the current plugin sources. If the cache becomes stale or corrupted, Omni Mail reruns discovery and rebuilds the container automatically.

## Connection Management

### Inspect Profiles

```bash
wp omni-mail connection:profiles
```

Use this to see the registered profile keys, supported schemes, webhook support, and expected fields.

### Create a Connection

```bash
wp omni-mail connection:create --profile=smtp --name="Primary SMTP" --host=smtp.example.com --port=587 --username=user --password=secret
```

Omni Mail stores canonical profile fields and builds the transport configuration lazily when delivery starts.

### Inspect and Update Connections

```bash
wp omni-mail connection:list
wp omni-mail connection:update 3 --weight=5 --priority=10 --rate-limit-hour=500
wp omni-mail connection:status
```

### Set the Default Route or Change Availability

```bash
wp omni-mail connection:set-default 3
wp omni-mail connection:enable 3
wp omni-mail connection:disable 3
wp omni-mail connection:delete 3
```

`connection:delete` asks for confirmation before removing the connection.

### Focused CLI Checks

```bash
wp omni-mail migration:status
wp omni-mail queue:status
wp omni-mail queue:work --limit=10
```

Use these focused commands to verify schema state and queue processing without relying on a single broad smoke command.

Useful migration commands:

```bash
wp omni-mail migration:list
wp omni-mail migration:run --dry-run
wp omni-mail migration:rollback --dry-run
```

## Admin Settings

The Settings screen stores plugin-wide defaults in the `omni_mail_config` option.

Current settings include:

- `wp_mail()` interception enablement
- global From email and From name policy
- return-path policy: provider default, match From Email, or custom address
- email log retention and terminal-log cleanup behavior
- delivery mode: `sync` or `async`
- routing strategy: `single`, `weighted_random`, `round_robin`, or `failover`
- global rate-limit defaults for minute, hour, and day windows
- circuit-breaker threshold, failure window, and cooldown defaults
- queue retry count, initial delay, multiplier, and maximum delay

## Routing Model

Omni Mail separates routing into four concerns:

- delivery mode: sync or async,
- strategy: `single`, `weighted_random`, `round_robin`, or `failover`,
- connection ranking: preferred connection, default connection, health, priority, and weight,
- availability gating: circuit breaker and rate limits.

### Preferred and Default Connections

- A request can prefer a connection through `MailRequest` metadata or the `X-Omni-Mail-Connection-Id` header.
- A default connection remains a preference, not a hard lock.
- If the preferred or default route is unavailable, Omni Mail can still fail over to other active connections.

### Health, Circuit Breaker, and Rate Limits

- Health scores are built from recent delivery attempts and recent negative webhook events.
- The circuit breaker temporarily blacklists repeatedly failing connections.
- Rate limits are tracked per connection across minute, hour, and day windows.
- If all active connections are temporarily unavailable, queued delivery is deferred and retried when the next route becomes available.

### Routing Strategies

- `weighted_random` is the default routing strategy.
- `round_robin` uses smooth weighted round robin primary selection.
- Smooth weighted round robin persists its shared state in a dedicated Omni Mail table.
- Only reasonably healthy connections are eligible as weighted-random or round-robin primaries.
- Remaining candidates are still ordered for failover.

Queued messages re-read the current routing defaults when they are actually delivered, so routing strategy changes take effect for pending queue work.

## Queue Operations

Async mail is stored in the Omni Mail queue table and processed by a small WordPress-friendly worker.

Queue entry points:

- Action Scheduler async wakeups,
- recurring Action Scheduler runs,
- direct WP-CLI worker execution.

Scheduled HTTP processing is single-worker by design: one Action Scheduler run holds the runner lease, and any remaining ready work is continued by at most one pending wakeup.
Immediate queue wakeups can also nudge Action Scheduler's internal async runner so due actions do not have to wait for a later site visit.

### Inspect and Work the Queue

```bash
wp omni-mail queue:status
wp omni-mail queue:work --limit=25 --time-limit=20
wp omni-mail queue:processing --limit=20
wp omni-mail queue:release-stale --older-than=300
wp omni-mail queue:failed --limit=20
wp omni-mail queue:retry
wp omni-mail queue:retry --id=42
```

Retry timing is configurable through Omni Mail options. Temporary routing exhaustion can return a retry-after value so the worker reschedules the job at a better time instead of only using exponential backoff.

`queue:status` now separates ready pending, deferred pending, active processing, stale processing claims, and failed messages. `queue:work` automatically releases stale claims older than five minutes before it claims more work.

## Webhooks

Webhook ingestion is available through the REST API.

Endpoint shape:

```text
/wp-json/omni-mail/v1/webhook/{connection_id}
```

Current webhook adapters:

- AhaSend
- Bird
- Brevo
- MailerSend
- Mailgun
- Mailjet
- Mailomat
- Mailtrap
- Mandrill
- Postmark
- Resend
- SendGrid
- SendLayer
- SMTP2GO
- SparkPost
- Sweego
- toSend
- ZeptoMail
- Generic fallback parser

Verification posture depends on the adapter and whether a connection has the required secret material configured. Omni Mail currently uses a mix of:

- shared-secret or HMAC validation,
- provider public-key validation,
- IP allowlists,
- unsigned-but-accepted callbacks for providers that do not expose a stronger verification path,
- hard-fail `unsupported` states when a provider requires a secret that has not been configured.

Useful webhook commands:

```bash
wp omni-mail webhook:status
wp omni-mail webhook:status --all=true
wp omni-mail webhook:events --limit=20
wp omni-mail webhook:events --connection-id=3
```

Webhook requests now return `404` when the target connection exists but webhook ingestion is disabled for that connection. Use `webhook:status` to confirm whether a connection is currently accepting callbacks and whether the active adapter is protected by a shared secret or currently unsupported.

Webhook events are persisted and projected back into WordPress hooks so they can influence routing health.

## Mail Attempt Visibility

Use WP-CLI to inspect recent delivery attempts:

```bash
wp omni-mail mail:attempts --limit=20
wp omni-mail mail:attempts --mail-log-id=42
wp omni-mail mail:attempts --connection-id=3 --status=failed
```

This gives operators a direct view into per-connection outcomes, provider message ids, and the latest error text without querying the database manually.

## Email Log Retention

Email logging is enabled by default and terminal logs are kept forever by default.

Administrators can disable retained email logs or set a retention duration from the plugin settings screen. Omni Mail still keeps the internal mail-log row while a message is `pending`, `queued`, or `processing`, because async delivery reconstructs the message from that durable payload. When logging is disabled, terminal `sent` and `failed` mail logs and their delivery-attempt rows are deleted after delivery completes.

Retention cleanup runs through Action Scheduler and only deletes terminal mail logs. Queue records and webhook events remain as operational records; webhook events are detached from deleted mail-log ids.

## Observability

Current operational visibility comes from the admin UI, WP-CLI, and database-backed records.

Admin visibility includes:

- dashboard summary cards and sending chart,
- connection health and availability details,
- email logs and attempt detail,
- queue logs,
- webhook logs,
- test email sending.

Useful commands:

- `wp omni-mail connection:status`
- `wp omni-mail connection:list`
- `wp omni-mail container:status`
- `wp omni-mail mail:attempts --limit=20`
- `wp omni-mail queue:status`
- `wp omni-mail queue:processing`
- `wp omni-mail queue:release-stale`
- `wp omni-mail queue:failed`
- `wp omni-mail container:clear`
- `wp omni-mail webhook:status`
- `wp omni-mail webhook:events --limit=20`
- `wp omni-mail mail:test --to=you@example.com`
- `wp omni-mail mail:test --to=you@example.com --connection-id=3`

Persisted records include:

- connections,
- queue messages,
- mail logs,
- mail attempts,
- webhook events,
- circuit-breaker state,
- rate-limit state,
- weighted round robin state.

## Current Operational Limits

- Admin UI is implemented but still early; polish, accessibility review, provider setup guidance, and UI-oriented coverage are still ongoing
- Provider-specific setup and incident-response guides are still incomplete
- No provider-side quota synchronization yet
- Some webhook adapters still rely on unsigned callbacks or provider-side allowlists instead of shared secrets
