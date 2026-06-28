<?php

declare(strict_types=1);

namespace JooosiMail\Tests\Integration\Cli;

use JooosiMail\Tests\Integration\Support\JooosiMailIntegrationTestCase;

/**
 * Covers queue WP-CLI command behavior.
 *
 * @since 0.1.0
 */
final class QueueCommandTest extends JooosiMailIntegrationTestCase
{
    /**
     * @since 0.1.0
     */
    public function testStatusPrintsQueueCounts(): void
    {
        $this->queueAsyncMail('CLI status subject');

        $output = $this->captureCli(function (): void {
            $this->queueCommand()->status([], ['stale-after' => 300]);
        });

        self::assertStringContainsString('Ready Pending: 1', $output['stdout']);
        self::assertStringContainsString('Deferred Pending: 0', $output['stdout']);
        self::assertStringContainsString('Processing: 0', $output['stdout']);
        self::assertStringContainsString('Failed: 0', $output['stdout']);
        self::assertSame('', trim($output['stderr']));
    }

    /**
     * @since 0.1.0
     */
    public function testWorkProcessesQueuedMessagesAndReportsSuccess(): void
    {
        $this->queueAsyncMail('CLI work subject');

        $output = $this->captureCli(function (): void {
            $this->queueCommand()->work([], [
                'limit' => 5,
                'time-limit' => 20,
            ]);
        });

        self::assertStringContainsString('Success: Processed 1 queue message(s).', $output['stdout']);
        self::assertSame('sent', $this->latestRow('mail_logs')['status'] ?? null);
        self::assertSame('completed', $this->latestRow('queue_messages')['status'] ?? null);
    }

    /**
     * @since 0.1.0
     */
    public function testRetryMovesFailedMessagesBackToPending(): void
    {
        $this->queueAsyncMail('CLI retry subject');

        $queueMessage = $this->latestRow('queue_messages');

        self::assertIsArray($queueMessage);

        $this->db()->update($this->tableNameResolver()->resolve('queue_messages'), [
            'status' => 'failed',
            'attempt_count' => 3,
            'last_error' => 'Simulated queue failure.',
            'processed_at' => gmdate('Y-m-d H:i:s'),
            'updated_at' => gmdate('Y-m-d H:i:s'),
        ], [
            'id' => (int) $queueMessage['id'],
        ]);

        $output = $this->captureCli(function () use ($queueMessage): void {
            $this->queueCommand()->retry([], [
                'id' => (int) $queueMessage['id'],
            ]);
        });
        $retriedMessage = $this->latestRow('queue_messages');

        self::assertStringContainsString('Success: Retried 1 failed message(s).', $output['stdout']);
        self::assertIsArray($retriedMessage);
        self::assertSame('pending', $retriedMessage['status']);
        self::assertSame(0, (int) $retriedMessage['attempt_count']);
        self::assertNull($retriedMessage['last_error']);
        self::assertNull($retriedMessage['processed_at']);
    }

    /**
     * @since 0.1.0
     */
    public function testFailedListsFailedQueueMessages(): void
    {
        $this->queueAsyncMail('CLI failed subject');

        $queueMessage = $this->latestRow('queue_messages');

        self::assertIsArray($queueMessage);

        $this->db()->update($this->tableNameResolver()->resolve('queue_messages'), [
            'status' => 'failed',
            'last_error' => 'Simulated hard failure for failed listing.',
            'processed_at' => gmdate('Y-m-d H:i:s'),
            'updated_at' => gmdate('Y-m-d H:i:s'),
        ], [
            'id' => (int) $queueMessage['id'],
        ]);

        $output = $this->captureCli(function (): void {
            $this->queueCommand()->failed([], ['limit' => 5]);
        });

        self::assertStringContainsString('Simulated hard failure for failed listing.', $output['stdout']);
        self::assertStringContainsString('async', $output['stdout']);
    }

    /**
     * @since 0.1.0
     */
    public function testProcessingListsStaleClaims(): void
    {
        $this->queueAsyncMail('CLI processing subject');

        $queueMessage = $this->latestRow('queue_messages');

        self::assertIsArray($queueMessage);

        $this->db()->update($this->tableNameResolver()->resolve('queue_messages'), [
            'status' => 'processing',
            'claimed_at' => gmdate('Y-m-d H:i:s', time() - 600),
            'claimed_by' => 'cli-processing-test',
            'updated_at' => gmdate('Y-m-d H:i:s', time() - 600),
        ], [
            'id' => (int) $queueMessage['id'],
        ]);

        $output = $this->captureCli(function (): void {
            $this->queueCommand()->processing([], [
                'limit' => 5,
                'stale-after' => 300,
                'stale-only' => true,
            ]);
        });

        self::assertStringContainsString('yes', $output['stdout']);
        self::assertStringContainsString('async', $output['stdout']);
        self::assertSame('', trim($output['stderr']));
    }

    /**
     * @since 0.1.0
     */
    public function testReleaseStaleMovesProcessingMessagesBackToPending(): void
    {
        $this->queueAsyncMail('CLI release stale subject');

        $queueMessage = $this->latestRow('queue_messages');

        self::assertIsArray($queueMessage);

        $this->db()->update($this->tableNameResolver()->resolve('queue_messages'), [
            'status' => 'processing',
            'claimed_at' => gmdate('Y-m-d H:i:s', time() - 600),
            'claimed_by' => 'cli-release-test',
            'updated_at' => gmdate('Y-m-d H:i:s', time() - 600),
        ], [
            'id' => (int) $queueMessage['id'],
        ]);

        $output = $this->captureCli(function (): void {
            $this->queueCommand()->releaseStale([], ['older-than' => 300]);
        });
        $releasedMessage = $this->latestRow('queue_messages');

        self::assertStringContainsString('Success: Released 1 stale queue claim(s).', $output['stdout']);
        self::assertIsArray($releasedMessage);
        self::assertSame('pending', $releasedMessage['status']);
    }

    /**
     * @since 0.1.0
     */
    private function queueAsyncMail(string $subject): void
    {
        $this->createNullConnection();
        $this->optionStore()->set('settings.delivery.mode', 'async');
        $this->optionStore()->set('settings.delivery.strategy', 'single');

        wp_mail('recipient@example.test', $subject, 'CLI queue body');
    }
}
