# Roadmap

This roadmap tracks the current direction of the project. Jooosi Mail is still in its initial phase, so priorities can move as backend contracts, admin workflows, provider coverage, and operational documentation mature.

## Recently Landed

- Expanded the built-in provider catalog across core transports, Symfony bridge transports, and custom integrations including Gmail, Mailomat, Microsoft Graph, SendLayer, SMTP2GO, SparkPost, ToSend, ZeptoMail, and Zoho Mail.
- Hardened webhook verification and parsing for AhaSend, Brevo, Mailgun, Mailjet, Mailomat, MailerSend, Mailtrap, Mandrill, Postmark, Resend, SendGrid, SendLayer, SMTP2GO, SparkPost, Sweego, ToSend, and ZeptoMail.
- Added an initial admin app for dashboard metrics, connection management, settings, email logs, queue logs, webhook logs, and test email sending.
- Expanded the WordPress-backed PHP integration suite across connection, transport, CLI, and webhook flows.

## Current Focus

- Harden the initial admin UI with stronger validation, clearer empty/error states, accessibility review, provider setup guidance, and UI-oriented coverage.
- Publish operator and developer documentation for supported providers, webhook setup requirements, and incident-response workflows.
- Refine admin, CLI, and REST troubleshooting flows for queue, routing, delivery, and webhook incidents.
- Close the biggest gaps between the shipped transport catalog and the smaller set of providers with first-class webhook ingestion and verification coverage.

## Next

- Expand monitoring and operator guides around delivery attempts, queue recovery, and webhook events.
- Improve admin UX for provider-specific configuration, secret handling, scheme selection, and webhook diagnostics.
- Add end-to-end integration coverage for admin flows and upgrade or migration paths.

## Later

- Add template rendering, analytics, and richer routing policies.
- Add provider-specific and domain-specific balancing controls.
- Expand developer extension guides.
- Explore lower-priority or non-production transports only when there is clear product demand.
