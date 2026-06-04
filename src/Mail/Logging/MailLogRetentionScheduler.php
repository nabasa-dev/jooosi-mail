<?php

declare(strict_types=1);

namespace OmniMail\Mail\Logging;

use OmniMail\Discovery\Attribute\Hook;
use OmniMail\Discovery\Attribute\Service;
use OmniMail\Queue\Trigger\ActionSchedulerTrigger;

/**
 * Schedules recurring email log retention cleanup.
 *
 * @since 0.1.0
 */
#[Service]
final readonly class MailLogRetentionScheduler
{
    private const int HOUR_IN_SECONDS = 3600;

    private const int DAY_IN_SECONDS = 86400;

    public const string RUN_HOOK = 'omni_mail_mail_logs_prune';

    public function __construct(
        private MailLogRetentionService $retentionService,
    ) {
    }

    /**
     * @since 0.1.0
     */
    #[Hook(name: 'init', kind: 'action', priority: 20, acceptedArgs: 0)]
    public function ensureRecurringScheduled(): void
    {
        $this->scheduleRecurring();
    }

    /**
     * @since 0.1.0
     */
    public function scheduleRecurring(): void
    {
        if (! function_exists('as_schedule_recurring_action') || ! function_exists('as_next_scheduled_action')) {
            return;
        }

        if (as_next_scheduled_action(self::RUN_HOOK, [], ActionSchedulerTrigger::GROUP) !== false) {
            return;
        }

        as_schedule_recurring_action(
            time() + self::HOUR_IN_SECONDS,
            self::DAY_IN_SECONDS,
            self::RUN_HOOK,
            [],
            ActionSchedulerTrigger::GROUP,
            true,
        );
    }

    /**
     * @since 0.1.0
     */
    #[Hook(name: self::RUN_HOOK, kind: 'action', acceptedArgs: 0)]
    public function prune(): void
    {
        $this->retentionService->pruneExpired();
    }

    /**
     * @since 0.1.0
     */
    public function unscheduleAll(): void
    {
        if (! function_exists('as_unschedule_all_actions')) {
            return;
        }

        as_unschedule_all_actions(self::RUN_HOOK, [], ActionSchedulerTrigger::GROUP);
    }
}
