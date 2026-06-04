<?php

declare(strict_types=1);

namespace OmniMail\Bootstrap;

use OmniMail\Database\Migration\MigrationRunner;
use OmniMail\Infrastructure\WordPress\CommandRegistrar;
use OmniMail\Infrastructure\WordPress\HookRegistrar;
use OmniMail\Infrastructure\WordPress\RestRouteRegistrar;
use OmniMail\Mail\Logging\MailLogRetentionScheduler;
use OmniMail\Queue\Trigger\ActionSchedulerTrigger;

/**
 * Registers runtime integrations with WordPress.
 *
 * @since 0.1.0
 */
final readonly class LifecycleManager
{
    public function __construct(
        private HookRegistrar $hookRegistrar,
        private RestRouteRegistrar $restRouteRegistrar,
        private CommandRegistrar $commandRegistrar,
        private MigrationRunner $migrationRunner,
        private ActionSchedulerTrigger $actionSchedulerTrigger,
        private MailLogRetentionScheduler $mailLogRetentionScheduler,
    ) {
    }

    /**
     * Register all runtime bridges.
     *
     * @since 0.1.0
     */
    public function boot(): void
    {
        $this->hookRegistrar->register();
        $this->restRouteRegistrar->register();
        $this->commandRegistrar->register();
    }

    /**
     * Run activation tasks.
     *
     * @since 0.1.0
     */
    public function activate(): void
    {
        $this->migrationRunner->run();
        $this->actionSchedulerTrigger->scheduleRecurring();
        $this->mailLogRetentionScheduler->scheduleRecurring();
    }

    /**
     * Run deactivation tasks.
     *
     * @since 0.1.0
     */
    public function deactivate(): void
    {
        $this->actionSchedulerTrigger->unscheduleAll();
        $this->mailLogRetentionScheduler->unscheduleAll();
    }
}
