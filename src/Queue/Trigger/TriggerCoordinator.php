<?php

declare (strict_types=1);
namespace OmniMail\Queue\Trigger;

use OmniMail\Discovery\Attribute\Service;
/**
 * Coordinates Action Scheduler queue wakeups.
 *
 * @since 0.1.0
 */
#[Service]
final readonly class TriggerCoordinator
{
    public function __construct(private \OmniMail\Queue\Trigger\ActionSchedulerTrigger $actionSchedulerTrigger)
    {
    }
    /**
     * @since 0.1.0
     */
    public function trigger(): void
    {
        $this->actionSchedulerTrigger->trigger();
    }
}
