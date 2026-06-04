<?php

declare(strict_types=1);

namespace OmniMail\Tests\Integration\Cli;

use OmniMail\Tests\Integration\Support\OmniMailIntegrationTestCase;

/**
 * Covers migration WP-CLI command behavior.
 *
 * @since 0.1.0
 */
final class MigrationCommandTest extends OmniMailIntegrationTestCase
{
    /**
     * @since 0.1.0
     */
    public function testStatusRendersExecutedMigrationSummaryAsJson(): void
    {
        $output = $this->captureCli(function (): void {
            $this->migrationCommand()->status([], ['format' => 'json']);
        });
        $items = json_decode(trim($output['stdout']), true, flags: JSON_THROW_ON_ERROR);
        $summary = [];

        foreach ($items as $item) {
            $summary[(string) $item['metric']] = (string) $item['value'];
        }

        self::assertSame('', trim($output['stderr']));
        self::assertSame($summary['latest_version'], $summary['current_version']);
        self::assertSame('0', $summary['pending_migrations']);
        self::assertSame($summary['available_migrations'], $summary['executed_migrations']);
    }

    /**
     * @since 0.1.0
     */
    public function testRunExecutesPendingMigrationsAndRendersJsonRows(): void
    {
        $this->migrationManager()->reset();

        self::assertSame(0, $this->countRows('migrations'));

        $output = $this->captureCli(function (): void {
            $this->migrationCommand()->run([], [
                'yes' => true,
                'format' => 'json',
            ]);
        });
        $items = json_decode(trim($output['stdout']), true, flags: JSON_THROW_ON_ERROR);

        self::assertSame('', trim($output['stderr']));
        self::assertNotEmpty($items);
        self::assertSame(count($items), $this->countRows('migrations'));

        foreach ($items as $item) {
            self::assertSame('executed', $item['status']);
        }
    }

    /**
     * @since 0.1.0
     */
    public function testRollbackStepsRollsBackTheLatestMigrationOnly(): void
    {
        $output = $this->captureCli(function (): void {
            $this->migrationCommand()->rollback([], [
                'steps' => 1,
                'yes' => true,
                'format' => 'json',
            ]);
        });
        $items = json_decode(trim($output['stdout']), true, flags: JSON_THROW_ON_ERROR);
        $status = $this->migrationManager()->status();

        self::assertSame('', trim($output['stderr']));
        self::assertCount(1, $items);
        self::assertSame('202603300001', $items[0]['version']);
        self::assertSame('rolled_back', $items[0]['status']);
        self::assertSame(2, $this->countRows('migrations'));
        self::assertSame(2, $status['executed']);
        self::assertSame(1, $status['pending']);
        self::assertFalse($this->db()->createSchemaManager()->tablesExist([
            $this->tableNameResolver()->resolve('weighted_round_robin_states'),
        ]));
        self::assertTrue($this->db()->createSchemaManager()->tablesExist([
            $this->tableNameResolver()->resolve('connections'),
            $this->tableNameResolver()->resolve('connection_rate_limits'),
        ]));
    }

    /**
     * @since 0.1.0
     */
    public function testRollbackResetDropsAllManagedTables(): void
    {
        $output = $this->captureCli(function (): void {
            $this->migrationCommand()->rollback([], [
                'reset' => true,
                'yes' => true,
                'format' => 'json',
            ]);
        });
        $items = json_decode(trim($output['stdout']), true, flags: JSON_THROW_ON_ERROR);
        $status = $this->migrationManager()->status();

        self::assertSame('', trim($output['stderr']));
        self::assertCount(3, $items);
        self::assertSame('202603300001', $items[0]['version']);
        self::assertSame('202603220001', $items[1]['version']);
        self::assertSame('202603190001', $items[2]['version']);
        self::assertSame(0, $this->countRows('migrations'));
        self::assertSame(0, $status['executed']);
        self::assertSame(3, $status['pending']);
        self::assertFalse($this->db()->createSchemaManager()->tablesExist([
            $this->tableNameResolver()->resolve('connections'),
            $this->tableNameResolver()->resolve('queue_messages'),
            $this->tableNameResolver()->resolve('mail_logs'),
            $this->tableNameResolver()->resolve('mail_attempts'),
            $this->tableNameResolver()->resolve('webhook_events'),
            $this->tableNameResolver()->resolve('connection_circuit_breakers'),
            $this->tableNameResolver()->resolve('connection_rate_limits'),
            $this->tableNameResolver()->resolve('weighted_round_robin_states'),
        ]));
    }
}
