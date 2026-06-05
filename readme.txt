=== Omni Mail ===
Contributors: suabahasa
Donate link: https://ko-fi.com/Q5Q75XSF7
Tags: email, smtp, mailer, transactional email, logs
Requires at least: 7.0
Tested up to: 7.0
Stable tag: 1.0.0
Requires PHP: 8.5
License: GPLv3 or later
License URI: https://www.gnu.org/licenses/gpl-3.0.html

A modern WordPress mail delivery plugin routing wp_mail() through Symfony Mailer, with queues, failover, webhooks, and observability.

== Description ==

Omni Mail is a modern email sending solution for WordPress sites. It routes WordPress email through configurable providers, records delivery activity, supports queue-based processing, and provides operational visibility for sent, queued, and failed messages.

### Features

* **Multiple provider support**: Use SMTP or popular email services from one plugin.
* **Queue-based delivery**: Queue busy sending periods and process messages with Action Scheduler.
* **Provider failover**: Fall back to another connection when a provider is unavailable.
* **Email logs**: Review sent, queued, failed, and webhook-related activity.
* **Webhook feedback**: Supported providers can report deliveries, bounces, complaints, opens, and clicks back to WordPress.
* **Admin and CLI workflows**: Manage Omni Mail from the WordPress dashboard or WP-CLI.

### Supported Providers

Omni Mail works with SMTP and many popular email providers. Available sending methods include:

* [AhaSend](https://ahasend.com) - API, SMTP. Webhooks supported.
* [Amazon SES](https://aws.amazon.com/ses/) - API, HTTPS, SMTP.
* [Azure Communication Services](https://azure.microsoft.com/en-us/products/communication-services) - API.
* [Bird](https://bird.com) - API, SMTP, SMTPS. Webhooks supported.
* [Brevo](https://www.brevo.com) - API, SMTP. Webhooks supported.
* [Cloudflare Email Service](https://developers.cloudflare.com/email-service/) - API.
* [Elastic Email](https://elasticemail.com) - API, SMTP, SMTPS.
* [Emailit](https://emailit.com) - API, SMTP, SMTPS.
* [Gmail](https://workspace.google.com/products/gmail/) - API, SMTP.
* [Infobip](https://www.infobip.com) - API, SMTP.
* [MailerSend](https://www.mailersend.com) - API, SMTP. Webhooks supported.
* [Mailgun](https://www.mailgun.com) - API, HTTPS, SMTP. Webhooks supported.
* [Mailjet](https://www.mailjet.com) - API, SMTP. Webhooks supported.
* [Mailomat](https://mailomat.swiss) - API, SMTP. Webhooks supported.
* [MailPace](https://mailpace.com) - API, SMTP.
* [Mailtrap](https://mailtrap.io) - SMTP, API, Sandbox. Webhooks supported.
* [Mandrill](https://mailchimp.com/developer/transactional/) - API, HTTPS, SMTP. Webhooks supported.
* [Microsoft Graph](https://developer.microsoft.com/en-us/graph) - API.
* [Native PHP](https://www.php.net/manual/en/function.mail.php) - Native PHP mail.
* [Null](https://symfony.com/doc/current/mailer.html#disabling-delivery) - Null transport.
* [Pepipost](https://netcorecloud.com/email/) - API, SMTP, SMTPS.
* [Postal](https://docs.postalserver.io) - API.
* [Postmark](https://postmarkapp.com) - API, SMTP. Webhooks supported.
* [Resend](https://resend.com) - API, SMTP. Webhooks supported.
* [Scaleway](https://www.scaleway.com/en/transactional-email-tem/) - API, SMTP.
* [SendGrid](https://sendgrid.com) - API, SMTP. Webhooks supported.
* [SendLayer](https://sendlayer.com) - API, SMTP, SMTPS. Webhooks supported.
* [Sendmail](https://www.proofpoint.com/us/products/email-protection/open-source-email-solution) - Sendmail.
* [SendPulse](https://sendpulse.com) - API, SMTP, SMTPS.
* [SMTP](https://en.wikipedia.org/wiki/Simple_Mail_Transfer_Protocol) - SMTP, SMTPS.
* [SMTP2GO](https://www.smtp2go.com) - API, SMTP, SMTPS. Webhooks supported.
* [SMTP.com](https://www.smtp.com) - API, SMTP, SMTPS.
* [SparkPost](https://www.sparkpost.com) - API, SMTP, SMTPS. Webhooks supported.
* [Sweego](https://www.sweego.io) - API, SMTP. Webhooks supported.
* [toSend](https://tosend.com) - API. Webhooks supported.
* [ZeptoMail](https://www.zoho.com/zeptomail/) - API, SMTP, SMTPS. Webhooks supported.
* [Zoho Mail](https://www.zoho.com/mail/) - SMTP, SMTPS.

Visit [the GitHub repository](https://github.com/nabasa-dev/omni-mail) for documentation, development notes, and issue tracking.

== Installation ==

1. Upload the plugin files to `/wp-content/plugins/omni-mail`, or install the plugin through the WordPress plugins screen.
2. Activate Omni Mail through the Plugins screen in WordPress.
3. Open Omni Mail in the WordPress admin and configure a sending connection.

== Frequently Asked Questions ==

= Which email providers are supported? =

Omni Mail supports SMTP and many popular transactional email providers through Symfony Mailer transports.

= Does Omni Mail replace wp_mail()? =

Yes. Omni Mail intercepts WordPress `wp_mail()` calls and routes them through the configured delivery workflow.

= Does Omni Mail support WP-CLI? =

Yes. Omni Mail includes WP-CLI commands for operational tasks such as managing connections, migrations, queue processing, and diagnostics.

== Changelog ==
