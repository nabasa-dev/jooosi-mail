<?php

declare(strict_types=1);

namespace OmniMail\Tests\Integration\Cli;

use OmniMail\Mail\Connection\Connection;
use OmniMail\Tests\Integration\Support\OmniMailIntegrationTestCase;
use OmniMail\Webhook\Event\WebhookEvent;

/**
 * Covers webhook WP-CLI command behavior.
 *
 * @since 0.1.0
 */
final class WebhookCommandTest extends OmniMailIntegrationTestCase
{
    /**
     * @since 0.1.0
     */
    public function testStatusReportsVerificationStateForConfiguredConnections(): void
    {
        $this->saveConnection(new Connection(
            id: null,
            profileKey: 'mailgun',
            name: 'CLI Mailgun Webhook',
            enabled: true,
            webhookEnabled: true,
            secrets: [
                'webhook_secret' => 'mailgun-secret',
            ],
        ));
        $this->saveConnection(new Connection(
            id: null,
            profileKey: 'sendgrid',
            name: 'CLI SendGrid Webhook',
            enabled: true,
            webhookEnabled: true,
        ));
        $this->saveConnection(new Connection(
            id: null,
            profileKey: 'ahasend',
            name: 'CLI AhaSend Webhook',
            enabled: true,
            webhookEnabled: true,
            secrets: [
                'webhook_secret' => 'ahasend-secret',
            ],
        ));
        $this->saveConnection(new Connection(
            id: null,
            profileKey: 'bird',
            name: 'CLI Bird Webhook',
            enabled: true,
            webhookEnabled: true,
        ));
        $this->saveConnection(new Connection(
            id: null,
            profileKey: 'brevo',
            name: 'CLI Brevo Webhook',
            enabled: true,
            webhookEnabled: true,
        ));
        $this->saveConnection(new Connection(
            id: null,
            profileKey: 'postmark',
            name: 'CLI Postmark Webhook',
            enabled: true,
            webhookEnabled: true,
        ));
        $this->saveConnection(new Connection(
            id: null,
            profileKey: 'mailtrap',
            name: 'CLI Mailtrap Webhook',
            enabled: true,
            webhookEnabled: true,
        ));
        $this->saveConnection(new Connection(
            id: null,
            profileKey: 'mailersend',
            name: 'CLI MailerSend Webhook',
            enabled: true,
            webhookEnabled: true,
            secrets: [
                'webhook_secret' => 'mailersend-secret',
            ],
        ));
        $this->saveConnection(new Connection(
            id: null,
            profileKey: 'mailjet',
            name: 'CLI Mailjet Webhook',
            enabled: true,
            webhookEnabled: true,
        ));
        $this->saveConnection(new Connection(
            id: null,
            profileKey: 'sendlayer',
            name: 'CLI SendLayer Webhook',
            enabled: true,
            webhookEnabled: true,
            secrets: [
                'webhook_secret' => 'sendlayer-secret',
            ],
        ));
        $this->saveConnection(new Connection(
            id: null,
            profileKey: 'smtp2go',
            name: 'CLI SMTP2GO Webhook',
            enabled: true,
            webhookEnabled: true,
        ));
        $this->saveConnection(new Connection(
            id: null,
            profileKey: 'sparkpost',
            name: 'CLI SparkPost Webhook',
            enabled: true,
            webhookEnabled: true,
        ));
        $this->saveConnection(new Connection(
            id: null,
            profileKey: 'mandrill',
            name: 'CLI Mandrill Webhook',
            enabled: true,
            webhookEnabled: true,
            secrets: [
                'webhook_secret' => 'mandrill-secret',
            ],
        ));
        $this->saveConnection(new Connection(
            id: null,
            profileKey: 'sweego',
            name: 'CLI Sweego Webhook',
            enabled: true,
            webhookEnabled: true,
            secrets: [
                'webhook_secret' => base64_encode('sweego-shared-secret'),
            ],
        ));
        $this->saveConnection(new Connection(
            id: null,
            profileKey: 'zeptomail',
            name: 'CLI ZeptoMail Webhook',
            enabled: true,
            webhookEnabled: true,
            secrets: [
                'webhook_secret' => 'zeptomail-secret',
            ],
        ));
        $this->saveConnection(new Connection(
            id: null,
            profileKey: 'null',
            name: 'CLI Disabled Webhook',
            enabled: true,
            webhookEnabled: false,
        ));

        $enabledOnly = $this->captureCli(function (): void {
            $this->webhookCommand()->status([], []);
        });
        $all = $this->captureCli(function (): void {
            $this->webhookCommand()->status([], ['all' => true]);
        });

        self::assertStringContainsString('CLI Mailgun Webhook', $enabledOnly['stdout']);
        self::assertStringContainsString('Mailgun', $enabledOnly['stdout']);
        self::assertStringContainsString('hmac-shared-secret', $enabledOnly['stdout']);
        self::assertStringContainsString('CLI SendGrid Webhook', $enabledOnly['stdout']);
        self::assertStringContainsString('unsigned-allowed', $enabledOnly['stdout']);
        self::assertStringContainsString('CLI AhaSend Webhook', $enabledOnly['stdout']);
        self::assertStringContainsString('CLI Bird Webhook', $enabledOnly['stdout']);
        self::assertStringContainsString('CLI Brevo Webhook', $enabledOnly['stdout']);
        self::assertStringContainsString('CLI Postmark Webhook', $enabledOnly['stdout']);
        self::assertStringContainsString('ip-allowlist', $enabledOnly['stdout']);
        self::assertStringContainsString('CLI Mailtrap Webhook', $enabledOnly['stdout']);
        self::assertStringContainsString('CLI MailerSend Webhook', $enabledOnly['stdout']);
        self::assertStringContainsString('CLI Mailjet Webhook', $enabledOnly['stdout']);
        self::assertStringContainsString('CLI SendLayer Webhook', $enabledOnly['stdout']);
        self::assertStringContainsString('CLI SMTP2GO Webhook', $enabledOnly['stdout']);
        self::assertStringContainsString('CLI SparkPost Webhook', $enabledOnly['stdout']);
        self::assertStringContainsString('CLI Mandrill Webhook', $enabledOnly['stdout']);
        self::assertStringContainsString('hmac-sha1-shared-secret', $enabledOnly['stdout']);
        self::assertStringContainsString('CLI Sweego Webhook', $enabledOnly['stdout']);
        self::assertStringContainsString('hmac-base64-shared-secret', $enabledOnly['stdout']);
        self::assertStringContainsString('CLI ZeptoMail Webhook', $enabledOnly['stdout']);
        self::assertStringNotContainsString('CLI Disabled Webhook', $enabledOnly['stdout']);

        self::assertStringContainsString('CLI Disabled Webhook', $all['stdout']);
        self::assertStringContainsString('disabled', $all['stdout']);
    }

    /**
     * @since 0.1.0
     */
    public function testEventsListsPersistedWebhookEvents(): void
    {
        $connection = $this->saveConnection(new Connection(
            id: null,
            profileKey: 'mailgun',
            name: 'CLI Webhook Events Connection',
            enabled: true,
            webhookEnabled: true,
            secrets: [
                'webhook_secret' => 'mailgun-secret',
            ],
        ));
        $mailLogId = $this->createMailLog($this->defaultSinglePlan(), $this->createMailRequest('CLI webhook event mail'));

        $this->webhookEventRepository()->save(new WebhookEvent(
            connectionId: $connection->id,
            mailLogId: $mailLogId,
            eventType: 'delivered',
            transportMessageId: 'provider-123',
            providerEventId: 'event-123',
            payload: ['status' => 'delivered'],
            occurredAt: '2026-03-30 00:00:00',
        ));

        $events = $this->captureCli(function () use ($connection, $mailLogId): void {
            $this->webhookCommand()->events([], [
                'connection-id' => $connection->id,
                'mail-log-id' => $mailLogId,
            ]);
        });

        self::assertStringContainsString(sprintf('#%d %s', $connection->id, $connection->name), $events['stdout']);
        self::assertStringContainsString((string) $mailLogId, $events['stdout']);
        self::assertStringContainsString('delivered', $events['stdout']);
        self::assertStringContainsString('provider-123', $events['stdout']);
        self::assertStringContainsString('event-123', $events['stdout']);
    }
}
