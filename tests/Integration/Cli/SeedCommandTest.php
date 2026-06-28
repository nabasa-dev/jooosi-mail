<?php

declare(strict_types=1);

namespace JooosiMail\Tests\Integration\Cli;

use Doctrine\DBAL\ArrayParameterType;
use JooosiMail\Cli\SeedCommand;
use JooosiMail\Queue\Message\SendEmailMessage;
use JooosiMail\Tests\Integration\Support\JooosiMailIntegrationTestCase;

/**
 * Covers seed WP-CLI command behavior.
 *
 * @since 0.1.0
 */
final class SeedCommandTest extends JooosiMailIntegrationTestCase
{
    /**
     * @since 0.1.0
     */
    public function testDemoSeedsQueueMessagesThatCanBeDecodedByTheDatabaseReceiver(): void
    {
        $output = $this->captureCli(function (): void {
            $this->container()->get(SeedCommand::class)->demo([], ['count' => 3]);
        });
        $envelopes = $this->databaseReceiver()->receive(10);

        self::assertStringContainsString('Success: Seeded 3 mail logs, 3 webhook events, and 3 queue messages using 3 seed connection(s); created 3 connection row(s).', $output['stdout']);
        self::assertSame('', trim($output['stderr']));
        self::assertSame(3, $this->countRows('connections'));
        self::assertCount(3, $envelopes);

        foreach ($envelopes as $envelope) {
            self::assertInstanceOf(SendEmailMessage::class, $envelope->getMessage());
        }
    }

    /**
     * @since 0.1.0
     */
    public function testDemoCreatesDedicatedSeedConnectionsInsteadOfReusingExistingConnections(): void
    {
        $this->createNullConnection(['name' => 'Existing Connection']);

        $output = $this->captureCli(function (): void {
            $this->container()->get(SeedCommand::class)->demo([], ['count' => 2]);
        });
        $seedConnectionCount = (int) $this->db()->fetchOne(sprintf(
            'SELECT COUNT(*) FROM %s WHERE name IN (:names)',
            $this->tableNameResolver()->resolve('connections'),
        ), [
            'names' => [
                'Seed Primary Route',
                'Seed Backup Route',
                'Seed Bulk Route',
            ],
        ], [
            'names' => ArrayParameterType::STRING,
        ]);

        self::assertStringContainsString('using 3 seed connection(s); created 3 connection row(s).', $output['stdout']);
        self::assertSame(4, $this->countRows('connections'));
        self::assertSame(3, $seedConnectionCount);
    }
}
