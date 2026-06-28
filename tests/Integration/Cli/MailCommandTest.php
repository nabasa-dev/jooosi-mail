<?php

declare(strict_types=1);

namespace JooosiMail\Tests\Integration\Cli;

use JooosiMail\Admin\Mail\TestEmailDeliveryTemplateListener;
use JooosiMail\Tests\Integration\Support\JooosiMailIntegrationTestCase;

/**
 * Covers mail WP-CLI command behavior.
 *
 * @since 0.1.0
 */
final class MailCommandTest extends JooosiMailIntegrationTestCase
{
    /**
     * @since 0.1.0
     */
    public function testTestCommandSendsDashboardDiagnosticMailAndReportsSuccess(): void
    {
        $this->createNullConnection([
            'name' => 'CLI Default Mail Command Connection',
        ]);
        $targetConnection = $this->createNullConnection([
            'name' => 'CLI Target Mail Command Connection',
            'default' => false,
            'priority' => 20,
        ]);
        $this->optionStore()->set('settings.delivery.mode', 'sync');
        $this->optionStore()->set('settings.delivery.strategy', 'single');

        $output = $this->captureCli(function () use ($targetConnection): void {
            $this->mailCommand()->test([], [
                'to' => 'recipient@example.test',
                'subject' => 'CLI Mail Subject',
                'connection-id' => $targetConnection->id,
            ]);
        });
        $mailLog = $this->latestRow('mail_logs');
        $attempt = $this->mailAttemptRepository()->listRecent(limit: 1)[0] ?? null;
        $payload = json_decode((string) ($mailLog['payload_json'] ?? '{}'), true);
        $plan = json_decode((string) ($mailLog['plan_json'] ?? '{}'), true);

        self::assertStringContainsString('Success: The test email was queued or sent successfully.', $output['stdout']);
        self::assertIsArray($mailLog);
        self::assertIsArray($payload);
        self::assertIsArray($plan);
        self::assertSame('admin_test_email', $mailLog['source']);
        self::assertSame('CLI Mail Subject', $mailLog['subject']);
        self::assertSame('sent', $mailLog['status']);
        self::assertSame('admin_test_email', $payload['source']);
        self::assertSame(true, $payload['metadata'][TestEmailDeliveryTemplateListener::METADATA_KEY] ?? null);
        self::assertStringContainsString('Delivery diagnostic: passed', (string) ($payload['textBody'] ?? ''));
        self::assertStringContainsString('Jooosi Mail rendered both HTML', (string) ($payload['htmlBody'] ?? ''));
        self::assertSame('text/html; charset=UTF-8', $payload['headers']['Content-Type'] ?? null);
        self::assertSame((string) $targetConnection->id, $payload['headers']['X-Jooosi-Mail-Connection-Id'] ?? null);
        self::assertSame($targetConnection->id, (int) ($plan['preferredConnectionId'] ?? 0));
        self::assertIsArray($attempt);
        self::assertSame('sent', $attempt['status']);
        self::assertSame($targetConnection->id, (int) $attempt['connection_id']);
    }

    /**
     * @since 0.1.0
     */
    public function testAttemptsListsRecentDeliveryAttempts(): void
    {
        $connection = $this->createNullConnection([
            'name' => 'CLI Attempt Connection',
        ]);
        $this->optionStore()->set('settings.delivery.mode', 'sync');
        $this->optionStore()->set('settings.delivery.strategy', 'single');

        wp_mail('recipient@example.test', 'CLI Attempts Subject', 'CLI attempts body');

        $mailLog = $this->latestRow('mail_logs');
        $output = $this->captureCli(function () use ($mailLog, $connection): void {
            $this->mailCommand()->attempts([], [
                'mail-log-id' => (int) ($mailLog['id'] ?? 0),
                'connection-id' => $connection->id,
                'status' => 'sent',
            ]);
        });

        self::assertIsArray($mailLog);
        self::assertStringContainsString((string) $mailLog['id'], $output['stdout']);
        self::assertStringContainsString(sprintf('#%d %s', $connection->id, $connection->name), $output['stdout']);
        self::assertStringContainsString('sent', $output['stdout']);
        self::assertSame('', trim($output['stderr']));
    }
}
