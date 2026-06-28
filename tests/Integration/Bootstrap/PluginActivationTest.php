<?php

declare(strict_types=1);

namespace JooosiMail\Tests\Integration\Bootstrap;

use Doctrine\DBAL\Connection;
use JooosiMail\Bootstrap\Environment;
use JooosiMail\Bootstrap\Paths;
use JooosiMail\Bootstrap\Plugin;
use JooosiMail\Database\Migration\MigrationManager;
use JooosiMail\Infrastructure\Container\ContainerFactory;
use JooosiMail\Infrastructure\Database\TableNameResolver;
use JooosiMail\Queue\Trigger\ActionSchedulerTrigger;
use JooosiMail\Queue\Worker\WorkerRunner;
use Psr\Container\ContainerInterface;
use WP_UnitTestCase;

/**
 * Covers plugin activation and migration lifecycle behavior.
 *
 * @since 0.1.0
 */
final class PluginActivationTest extends WP_UnitTestCase
{
    /**
     * @since 0.1.0
     */
    public function set_up(): void
    {
        parent::set_up();

        $this->resetMigrationState();
        $this->resetActionSchedulerState();
    }

    /**
     * @since 0.1.0
     */
    public function tear_down(): void
    {
        $this->resetActionSchedulerState();
        $this->resetMigrationState();

        parent::tear_down();
    }

    /**
     * @since 0.1.0
     */
    public function testBootLoadsActionSchedulerProceduralApi(): void
    {
        self::assertTrue(function_exists('as_enqueue_async_action'));
        self::assertTrue(function_exists('as_schedule_recurring_action'));
        self::assertTrue(function_exists('as_next_scheduled_action'));
    }

    /**
     * @since 0.1.0
     */
    public function testActivateRunsAllPendingMigrations(): void
    {
        $before = $this->migrationManager()->status();

        self::assertSame(0, $before['executed']);
        self::assertSame($before['total'], $before['pending']);
        self::assertSame('0', $before['current_version']);

        Plugin::boot(JOOOSI_MAIL_PLUGIN_FILE)->activate();

        $after = $this->migrationManager()->status();

        self::assertSame($after['total'], $after['executed']);
        self::assertSame(0, $after['pending']);
        self::assertSame($after['latest_version'], $after['current_version']);
        self::assertSame('up_to_date', $after['next_version']);
        self::assertSame($after['total'], $this->migrationExecutionCount());

        foreach ($this->expectedTableNames() as $tableName) {
            self::assertTrue($this->tableExists($tableName), sprintf('Expected table "%s" to exist after activation.', $tableName));
        }
    }

    /**
     * @since 0.1.0
     */
    public function testActivateIsIdempotentAfterMigrationsHaveRun(): void
    {
        Plugin::boot(JOOOSI_MAIL_PLUGIN_FILE)->activate();

        $afterFirstActivation = $this->migrationManager()->status();
        $executionsAfterFirstActivation = $this->migrationExecutionCount();

        Plugin::boot(JOOOSI_MAIL_PLUGIN_FILE)->activate();

        $afterSecondActivation = $this->migrationManager()->status();

        self::assertSame($afterFirstActivation['total'], $afterSecondActivation['executed']);
        self::assertSame(0, $afterSecondActivation['pending']);
        self::assertSame($executionsAfterFirstActivation, $this->migrationExecutionCount());
        self::assertSame($afterSecondActivation['total'], $this->migrationExecutionCount());
    }

    /**
     * @since 0.1.0
     */
    public function testActivateSchedulesTheRecurringQueueRunner(): void
    {
        Plugin::boot(JOOOSI_MAIL_PLUGIN_FILE)->activate();

        self::assertNotFalse(as_next_scheduled_action(ActionSchedulerTrigger::RECURRING_HOOK, [], ActionSchedulerTrigger::GROUP));
    }

    /**
     * @since 0.1.0
     */
    private function resetMigrationState(): void
    {
        $result = $this->migrationManager()->reset();

        self::assertArrayNotHasKey('failed', $result);
    }

    /**
     * @since 0.1.0
     */
    private function resetActionSchedulerState(): void
    {
        if (function_exists('as_unschedule_all_actions')) {
            as_unschedule_all_actions(ActionSchedulerTrigger::RUN_HOOK, [], ActionSchedulerTrigger::GROUP);
            as_unschedule_all_actions(ActionSchedulerTrigger::RECURRING_HOOK, [], ActionSchedulerTrigger::GROUP);
        }

        delete_option(ActionSchedulerTrigger::SCHEDULE_LOCK_OPTION);
        delete_option(WorkerRunner::RUNNER_LEASE_OPTION);
    }

    /**
     * @since 0.1.0
     */
    private function migrationManager(): MigrationManager
    {
        return $this->container()->get(MigrationManager::class);
    }

    /**
     * @since 0.1.0
     */
    private function connection(): Connection
    {
        return $this->container()->get(Connection::class);
    }

    /**
     * @since 0.1.0
     */
    private function tableNameResolver(): TableNameResolver
    {
        return $this->container()->get(TableNameResolver::class);
    }

    /**
     * @return list<string>
     *
     * @since 0.1.0
     */
    private function expectedTableNames(): array
    {
        $tableNameResolver = $this->tableNameResolver();

        return [
            $tableNameResolver->resolve('migrations'),
            $tableNameResolver->resolve('connections'),
            $tableNameResolver->resolve('queue_messages'),
            $tableNameResolver->resolve('mail_logs'),
            $tableNameResolver->resolve('mail_attempts'),
            $tableNameResolver->resolve('webhook_events'),
            $tableNameResolver->resolve('connection_circuit_breakers'),
            $tableNameResolver->resolve('connection_rate_limits'),
            $tableNameResolver->resolve('weighted_round_robin_states'),
        ];
    }

    /**
     * @since 0.1.0
     */
    private function migrationExecutionCount(): int
    {
        return (int) $this->connection()->fetchOne(sprintf(
            'SELECT COUNT(*) FROM %s',
            $this->tableNameResolver()->resolve('migrations'),
        ));
    }

    /**
     * @since 0.1.0
     */
    private function tableExists(string $tableName): bool
    {
        return $this->connection()->createSchemaManager()->tablesExist([$tableName]);
    }

    /**
     * @since 0.1.0
     */
    private function container(): ContainerInterface
    {
        return (new ContainerFactory(
            Paths::fromPluginFile(JOOOSI_MAIL_PLUGIN_FILE),
            Environment::fromWordPress(),
        ))->build();
    }
}
