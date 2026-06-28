<?php

declare(strict_types=1);

namespace JooosiMail\Tests\Integration\Mail\Routing;

use JooosiMail\Mail\Routing\DeliveryMode;
use JooosiMail\Mail\Routing\DeliveryPlan;
use JooosiMail\Mail\Routing\RoutingStrategy;
use JooosiMail\Tests\Integration\Support\JooosiMailIntegrationTestCase;
use RuntimeException;

/**
 * Covers connection resolution across all routing strategies.
 *
 * @since 0.1.0
 */
final class ConnectionResolverTest extends JooosiMailIntegrationTestCase
{
    /**
     * @since 0.1.0
     */
    public function testDefaultConnectionIsPreferredForSingleRouting(): void
    {
        $defaultConnection = $this->createNullConnection([
            'name' => 'Default route',
            'default' => true,
            'priority' => 20,
        ]);
        $backupConnection = $this->createNullConnection([
            'name' => 'Backup route',
            'default' => false,
            'priority' => 1,
        ]);

        $resolved = $this->connectionResolver()->resolve($this->defaultSinglePlan());

        self::assertCount(1, $resolved);
        self::assertSame($defaultConnection->id, $resolved[0]->id);
        self::assertNotSame($backupConnection->id, $resolved[0]->id);
    }

    /**
     * @since 0.1.0
     */
    public function testPreferredConnectionOverridesTheDefaultConnection(): void
    {
        $defaultConnection = $this->createNullConnection([
            'name' => 'Default route',
            'default' => true,
        ]);
        $preferredConnection = $this->createNullConnection([
            'name' => 'Preferred route',
            'default' => false,
        ]);

        $resolved = $this->connectionResolver()->resolve($this->defaultSinglePlan($preferredConnection->id));

        self::assertCount(1, $resolved);
        self::assertSame($preferredConnection->id, $resolved[0]->id);
        self::assertNotSame($defaultConnection->id, $resolved[0]->id);
    }

    /**
     * @since 0.1.0
     */
    public function testBlacklistedConnectionsAreSkippedDuringResolution(): void
    {
        $primaryConnection = $this->createNullConnection([
            'name' => 'Primary route',
            'default' => true,
            'circuit_threshold' => 1,
            'circuit_window' => 300,
            'circuit_cooldown' => 300,
        ]);
        $backupConnection = $this->createNullConnection([
            'name' => 'Backup route',
            'default' => false,
        ]);

        $this->circuitBreaker()->recordFailure($primaryConnection, new RuntimeException('Simulated routing failure.'));

        $resolved = $this->connectionResolver()->resolve($this->defaultSinglePlan());

        self::assertCount(1, $resolved);
        self::assertSame($backupConnection->id, $resolved[0]->id);
        self::assertNotSame($primaryConnection->id, $resolved[0]->id);
    }

    /**
     * @since 0.1.0
     */
    public function testWeightedRandomSkipsUnhealthyConnectionsForPrimarySelection(): void
    {
        $unhealthyConnection = $this->createNullConnection([
            'name' => 'Unhealthy weighted route',
            'default' => true,
            'weight' => 100,
        ]);
        $healthyConnection = $this->createNullConnection([
            'name' => 'Healthy weighted route',
            'default' => false,
            'weight' => 1,
        ]);

        $this->recordFailedAttempts($unhealthyConnection->id ?? 0, 20);

        $resolved = $this->connectionResolver()->resolve($this->plan(RoutingStrategy::WeightedRandom));

        self::assertCount(2, $resolved);
        self::assertSame($healthyConnection->id, $resolved[0]->id);
        self::assertSame($unhealthyConnection->id, $resolved[1]->id);
    }

    /**
     * @since 0.1.0
     */
    public function testWeightedRandomFavorsHeavierHealthyConnections(): void
    {
        $heavyConnection = $this->createNullConnection([
            'name' => 'Heavy weighted route',
            'default' => false,
            'weight' => 100,
        ]);
        $lightConnection = $this->createNullConnection([
            'name' => 'Light weighted route',
            'default' => false,
            'weight' => 1,
        ]);

        $heavyPrimaryCount = 0;
        $lightPrimaryCount = 0;

        for ($iteration = 0; $iteration < 60; $iteration++) {
            $resolved = $this->connectionResolver()->resolve($this->plan(RoutingStrategy::WeightedRandom));

            if (($resolved[0]->id ?? null) === $heavyConnection->id) {
                $heavyPrimaryCount++;

                continue;
            }

            if (($resolved[0]->id ?? null) === $lightConnection->id) {
                $lightPrimaryCount++;
            }
        }

        self::assertGreaterThan(50, $heavyPrimaryCount);
        self::assertLessThan(10, $lightPrimaryCount);
    }

    /**
     * @since 0.1.0
     */
    public function testRoundRobinPersistsSmoothWeightedSequenceAcrossResolutions(): void
    {
        $primaryConnection = $this->createNullConnection([
            'name' => 'Round robin primary',
            'default' => false,
            'weight' => 3,
        ]);
        $secondaryConnection = $this->createNullConnection([
            'name' => 'Round robin secondary',
            'default' => false,
            'weight' => 1,
        ]);

        $sequence = [];

        for ($iteration = 0; $iteration < 4; $iteration++) {
            $resolved = $this->connectionResolver()->resolve($this->plan(RoutingStrategy::RoundRobin));
            $sequence[] = $resolved[0]->id;
        }

        $stateRow = $this->db()->fetchAssociative(sprintf(
            'SELECT weights_json FROM %s WHERE scope_key = :scope_key LIMIT 1',
            $this->tableNameResolver()->resolve('weighted_round_robin_states'),
        ), [
            'scope_key' => 'global',
        ]);
        $state = json_decode((string) ($stateRow['weights_json'] ?? '{}'), true);

        self::assertSame([
            $primaryConnection->id,
            $primaryConnection->id,
            $secondaryConnection->id,
            $primaryConnection->id,
        ], $sequence);
        self::assertIsArray($state);
        self::assertSame(0, (int) ($state[(string) $primaryConnection->id] ?? 0));
        self::assertSame(0, (int) ($state[(string) $secondaryConnection->id] ?? 0));
    }

    /**
     * @since 0.1.0
     */
    public function testFailoverOrdersPreferredHealthyThenFallbackCandidates(): void
    {
        $degradedDefaultConnection = $this->createNullConnection([
            'name' => 'Degraded default route',
            'default' => true,
        ]);
        $healthyFallbackConnection = $this->createNullConnection([
            'name' => 'Healthy fallback route',
            'default' => false,
        ]);
        $preferredConnection = $this->createNullConnection([
            'name' => 'Preferred failover route',
            'default' => false,
        ]);

        $this->recordFailedAttempts($degradedDefaultConnection->id ?? 0, 20);

        $resolved = $this->connectionResolver()->resolve($this->plan(
            RoutingStrategy::Failover,
            $preferredConnection->id,
        ));

        self::assertCount(3, $resolved);
        self::assertSame($preferredConnection->id, $resolved[0]->id);
        self::assertSame($healthyFallbackConnection->id, $resolved[1]->id);
        self::assertSame($degradedDefaultConnection->id, $resolved[2]->id);
    }

    /**
     * @since 0.1.0
     */
    public function testRateLimitReservationConsumesCapacityBeforeSend(): void
    {
        $connection = $this->createNullConnection([
            'rate_limit_minute' => 1,
        ]);

        self::assertTrue($this->rateLimiter()->reserve($connection));
        self::assertFalse($this->rateLimiter()->reserve($connection));

        $availability = $this->rateLimiter()->getAvailability($connection);

        self::assertTrue($availability['blocked']);
    }

    /**
     * @since 0.1.0
     */
    private function plan(RoutingStrategy $strategy, ?int $preferredConnectionId = null): DeliveryPlan
    {
        return new DeliveryPlan(
            mode: DeliveryMode::Async,
            strategy: $strategy,
            priority: 10,
            delaySeconds: 0,
            preferredConnectionId: $preferredConnectionId,
        );
    }

    /**
     * @since 0.1.0
     */
    private function recordFailedAttempts(int $connectionId, int $count): void
    {
        if ($connectionId <= 0) {
            return;
        }

        $mailLogId = $this->createMailLog($this->plan(RoutingStrategy::Single));

        for ($attempt = 0; $attempt < $count; $attempt++) {
            $this->mailAttemptRepository()->record(
                $mailLogId,
                $connectionId,
                'failed',
                'Simulated routing failure.',
            );
        }
    }
}
