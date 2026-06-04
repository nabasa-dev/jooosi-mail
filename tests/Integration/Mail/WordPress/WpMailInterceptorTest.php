<?php

declare(strict_types=1);

namespace OmniMail\Tests\Integration\Mail\WordPress;

use OmniMail\Mail\Delivery\DeliveryService;
use OmniMail\Infrastructure\Event\EventPublisherInterface;
use OmniMail\Mail\Logging\MailLifecycleLogger;
use OmniMail\Mail\Routing\RoutingPolicyResolver;
use OmniMail\Mail\WordPress\WpMailInterceptor;
use OmniMail\Mail\WordPress\WpMailPayloadNormalizer;
use OmniMail\Queue\Transport\DatabaseTransport;
use OmniMail\Queue\Trigger\ActionSchedulerTrigger;
use OmniMail\Queue\Trigger\TriggerCoordinator;
use OmniMail\Tests\Integration\Support\OmniMailIntegrationTestCase;
use RuntimeException;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;

/**
 * Covers the WordPress `wp_mail()` interception flow.
 *
 * @since 0.1.0
 */
final class WpMailInterceptorTest extends OmniMailIntegrationTestCase
{
    /**
     * @since 0.1.0
     */
    public function testDisabledInterceptorLeavesPreWpMailUnchanged(): void
    {
        $this->optionStore()->set('settings.mail.intercept.enabled', false);

        $result = apply_filters('pre_wp_mail', null, [
            'to' => 'recipient@example.test',
            'subject' => 'Disabled interception',
            'message' => 'Body',
        ]);

        self::assertNotSame(true, $result);
        self::assertSame(0, $this->countRows('mail_logs'));
        self::assertSame(0, $this->countRows('queue_messages'));
        self::assertCount(0, $this->actionSchedulerWakeups());
    }

    /**
     * @since 0.1.0
     */
    public function testWpMailSyncSendsThroughTheConfiguredNullConnection(): void
    {
        $connection = $this->createNullConnection();
        $this->optionStore()->set('settings.delivery.mode', 'sync');
        $this->optionStore()->set('settings.delivery.strategy', 'single');

        $result = wp_mail('recipient@example.test', 'Sync subject', 'Sync body');
        $mailLog = $this->latestRow('mail_logs');
        $attempts = $this->mailAttemptRepository()->listRecent(limit: 5, mailLogId: (int) ($mailLog['id'] ?? 0));
        $attempt = $attempts[0] ?? null;

        self::assertTrue($result);
        self::assertIsArray($mailLog);
        self::assertSame('Sync subject', $mailLog['subject']);
        self::assertSame('sent', $mailLog['status']);
        self::assertSame($connection->id, (int) $mailLog['final_connection_id']);
        self::assertIsArray($attempt);
        self::assertSame('sent', $attempt['status']);
        self::assertSame($connection->id, (int) $attempt['connection_id']);
        self::assertSame(0, $this->countRows('queue_messages'));
        self::assertCount(0, $this->actionSchedulerWakeups());
    }

    /**
     * @since 0.1.0
     */
    public function testWpMailAsyncQueuesTheMessage(): void
    {
        $this->createNullConnection();
        $this->optionStore()->set('settings.delivery.mode', 'async');
        $this->optionStore()->set('settings.delivery.strategy', 'single');

        $result = wp_mail('recipient@example.test', 'Async subject', 'Async body');
        $mailLog = $this->latestRow('mail_logs');
        $queueMessage = $this->latestRow('queue_messages');

        self::assertTrue($result);
        self::assertIsArray($mailLog);
        self::assertSame('Async subject', $mailLog['subject']);
        self::assertSame('queued', $mailLog['status']);
        self::assertIsArray($queueMessage);
        self::assertSame('pending', $queueMessage['status']);
        self::assertSame(DatabaseTransport::NAME, $queueMessage['queue_name']);
        self::assertSame(0, (int) $queueMessage['attempt_count']);
        self::assertSame(0, $this->countRows('mail_attempts'));
        self::assertCount(1, $this->actionSchedulerWakeups());
    }

    /**
     * @since 0.1.0
     */
    public function testWpMailAsyncCoalescesWakeupsAcrossBurstEnqueues(): void
    {
        $this->createNullConnection();
        $this->optionStore()->set('settings.delivery.mode', 'async');
        $this->optionStore()->set('settings.delivery.strategy', 'single');

        wp_mail('recipient@example.test', 'Burst subject 1', 'Burst body 1');
        wp_mail('recipient@example.test', 'Burst subject 2', 'Burst body 2');
        wp_mail('recipient@example.test', 'Burst subject 3', 'Burst body 3');

        $pendingActions = as_get_scheduled_actions([
            'hook' => ActionSchedulerTrigger::RUN_HOOK,
            'group' => ActionSchedulerTrigger::GROUP,
            'status' => 'pending',
        ], 'ids');

        self::assertSame(3, $this->countRows('queue_messages'));
        self::assertCount(1, $pendingActions);
        self::assertCount(1, $this->actionSchedulerWakeups());
    }

    /**
     * @since 0.1.0
     */
    public function testAsyncDispatchFailureRollsBackTheMailLog(): void
    {
        $this->createNullConnection();
        $this->optionStore()->set('settings.delivery.mode', 'async');
        $this->optionStore()->set('settings.delivery.strategy', 'single');

        $failingBus = new class implements MessageBusInterface {
            public function dispatch(object $message, array $stamps = []): Envelope
            {
                throw new RuntimeException('Simulated queue dispatch failure.');
            }
        };

        $interceptor = new WpMailInterceptor(
            $this->container()->get(WpMailPayloadNormalizer::class),
            $this->container()->get(RoutingPolicyResolver::class),
            $this->container()->get(MailLifecycleLogger::class),
            $this->container()->get(DeliveryService::class),
            $failingBus,
            $this->container()->get(TriggerCoordinator::class),
            $this->optionStore(),
            $this->db(),
            $this->container()->get(EventPublisherInterface::class),
        );

        $result = $interceptor->intercept(null, [
            'to' => 'recipient@example.test',
            'subject' => 'Rollback subject',
            'message' => 'Rollback body',
            'headers' => '',
            'attachments' => [],
        ]);

        self::assertFalse($result);
        self::assertSame(0, $this->countRows('mail_logs'));
        self::assertSame(0, $this->countRows('queue_messages'));
    }
}
