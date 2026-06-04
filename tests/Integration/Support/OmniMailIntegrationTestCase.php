<?php

declare(strict_types=1);

namespace OmniMail\Tests\Integration\Support;

use Doctrine\DBAL\Connection as DbalConnection;
use OmniMail\Bootstrap\Environment;
use OmniMail\Bootstrap\Paths;
use OmniMail\Bootstrap\Plugin;
use OmniMail\Cli\ConnectionCommand;
use OmniMail\Cli\MailCommand;
use OmniMail\Cli\MigrationCommand;
use OmniMail\Cli\QueueCommand;
use OmniMail\Cli\WebhookCommand;
use OmniMail\Database\Migration\MigrationManager;
use OmniMail\Infrastructure\Container\ContainerFactory;
use OmniMail\Infrastructure\Database\TableNameResolver;
use OmniMail\Infrastructure\WordPress\OptionStore;
use OmniMail\Mail\Connection\Connection;
use OmniMail\Mail\Connection\ConnectionManager;
use OmniMail\Mail\Connection\ConnectionRepository;
use OmniMail\Mail\Logging\MailAttemptRepository;
use OmniMail\Mail\Logging\MailLogRepository;
use OmniMail\Mail\Logging\MailLogRetentionScheduler;
use OmniMail\Mail\Routing\ConnectionCircuitBreaker;
use OmniMail\Mail\Routing\ConnectionRateLimiter;
use OmniMail\Mail\Routing\ConnectionResolver;
use OmniMail\Mail\Routing\DeliveryMode;
use OmniMail\Mail\Routing\DeliveryPlan;
use OmniMail\Mail\Routing\RoutingStrategy;
use OmniMail\Mail\ValueObject\MailAddress;
use OmniMail\Mail\ValueObject\MailRequest;
use OmniMail\Queue\Maintenance\QueueMaintenanceService;
use OmniMail\Queue\Query\QueueMessageQuery;
use OmniMail\Queue\Transport\DatabaseReceiver;
use OmniMail\Queue\Trigger\ActionSchedulerTrigger;
use OmniMail\Queue\Worker\QueueWorker;
use OmniMail\Queue\Worker\WorkerRunner;
use OmniMail\Webhook\Event\WebhookEventRepository;
use Psr\Container\ContainerInterface;
use RuntimeException;
use WP_CLI;
use WP_CLI\Loggers\Execution;
use WP_UnitTestCase;

/**
 * Shared WordPress-backed integration test utilities for Omni Mail.
 *
 * @since 0.1.0
 */
abstract class OmniMailIntegrationTestCase extends WP_UnitTestCase
{
    private ?ContainerInterface $container = null;

    /**
     * @var list<string>
     */
    private array $capturedActionSchedulerWakeups = [];

    /**
     * @since 0.1.0
     */
    public function set_up(): void
    {
        parent::set_up();

        $this->container = null;
        $this->capturedActionSchedulerWakeups = [];
        $this->stubActionSchedulerAsyncRunnerHttp();
        delete_option('omni_mail_config');
        $this->resetMigrationState();
        $this->activatePlugin();
        $this->resetQueueActions();
        delete_option('omni_mail_config');
    }

    /**
     * @since 0.1.0
     */
    public function tear_down(): void
    {
        delete_option('omni_mail_config');
        $this->unstubActionSchedulerAsyncRunnerHttp();
        $this->resetQueueActions();
        $this->resetMigrationState();
        $this->container = null;

        parent::tear_down();
    }

    /**
     * @since 0.1.0
     */
    protected function activatePlugin(): void
    {
        Plugin::boot(OMNI_MAIL_PLUGIN_FILE)->activate();
    }

    /**
     * @since 0.1.0
     */
    protected function container(): ContainerInterface
    {
        return $this->container ??= (new ContainerFactory(
            Paths::fromPluginFile(OMNI_MAIL_PLUGIN_FILE),
            Environment::fromWordPress(),
        ))->build();
    }

    /**
     * @since 0.1.0
     */
    protected function db(): DbalConnection
    {
        return $this->container()->get(DbalConnection::class);
    }

    /**
     * @since 0.1.0
     */
    protected function tableNameResolver(): TableNameResolver
    {
        return $this->container()->get(TableNameResolver::class);
    }

    /**
     * @since 0.1.0
     */
    protected function optionStore(): OptionStore
    {
        return $this->container()->get(OptionStore::class);
    }

    /**
     * @since 0.1.0
     */
    protected function connectionManager(): ConnectionManager
    {
        return $this->container()->get(ConnectionManager::class);
    }

    /**
     * @since 0.1.0
     */
    protected function connectionRepository(): ConnectionRepository
    {
        return $this->container()->get(ConnectionRepository::class);
    }

    /**
     * @since 0.1.0
     */
    protected function connectionResolver(): ConnectionResolver
    {
        return $this->container()->get(ConnectionResolver::class);
    }

    /**
     * @since 0.1.0
     */
    protected function mailLogRepository(): MailLogRepository
    {
        return $this->container()->get(MailLogRepository::class);
    }

    /**
     * @since 0.1.0
     */
    protected function mailAttemptRepository(): MailAttemptRepository
    {
        return $this->container()->get(MailAttemptRepository::class);
    }

    /**
     * @since 0.1.0
     */
    protected function queueWorker(): QueueWorker
    {
        return $this->container()->get(QueueWorker::class);
    }

    /**
     * @since 0.1.0
     */
    protected function workerRunner(): WorkerRunner
    {
        return $this->container()->get(WorkerRunner::class);
    }

    /**
     * @since 0.1.0
     */
    protected function actionSchedulerTrigger(): ActionSchedulerTrigger
    {
        return $this->container()->get(ActionSchedulerTrigger::class);
    }

    /**
     * @return list<string>
     *
     * @since 0.1.0
     */
    protected function actionSchedulerWakeups(): array
    {
        return $this->capturedActionSchedulerWakeups;
    }

    /**
     * @since 0.1.0
     */
    protected function databaseReceiver(): DatabaseReceiver
    {
        return $this->container()->get(DatabaseReceiver::class);
    }

    /**
     * @since 0.1.0
     */
    protected function queueMessageQuery(): QueueMessageQuery
    {
        return $this->container()->get(QueueMessageQuery::class);
    }

    /**
     * @since 0.1.0
     */
    protected function queueMaintenanceService(): QueueMaintenanceService
    {
        return $this->container()->get(QueueMaintenanceService::class);
    }

    /**
     * @since 0.1.0
     */
    protected function rateLimiter(): ConnectionRateLimiter
    {
        return $this->container()->get(ConnectionRateLimiter::class);
    }

    /**
     * @since 0.1.0
     */
    protected function circuitBreaker(): ConnectionCircuitBreaker
    {
        return $this->container()->get(ConnectionCircuitBreaker::class);
    }

    /**
     * @since 0.1.0
     */
    protected function webhookEventRepository(): WebhookEventRepository
    {
        return $this->container()->get(WebhookEventRepository::class);
    }

    /**
     * @since 0.1.0
     */
    protected function migrationManager(): MigrationManager
    {
        return $this->container()->get(MigrationManager::class);
    }

    /**
     * @since 0.1.0
     */
    protected function migrationCommand(): MigrationCommand
    {
        return $this->container()->get(MigrationCommand::class);
    }

    /**
     * @since 0.1.0
     */
    protected function connectionCommand(): ConnectionCommand
    {
        return $this->container()->get(ConnectionCommand::class);
    }

    /**
     * @since 0.1.0
     */
    protected function queueCommand(): QueueCommand
    {
        return $this->container()->get(QueueCommand::class);
    }

    /**
     * @since 0.1.0
     */
    protected function webhookCommand(): WebhookCommand
    {
        return $this->container()->get(WebhookCommand::class);
    }

    /**
     * @since 0.1.0
     */
    protected function mailCommand(): MailCommand
    {
        return $this->container()->get(MailCommand::class);
    }

    /**
     * @since 0.1.0
     */
    protected function createNullConnection(array $overrides = []): Connection
    {
        return $this->connectionManager()->create(array_replace([
            'profile' => 'null',
            'name' => 'Test Null Connection',
            'default' => true,
            'priority' => 10,
            'weight' => 1,
        ], $overrides));
    }

    /**
     * @since 0.1.0
     */
    protected function saveConnection(Connection $connection): Connection
    {
        $connectionId = $this->connectionRepository()->save($connection);

        return $this->connectionRepository()->find($connectionId)
            ?? throw new RuntimeException(sprintf('Connection %d could not be reloaded.', $connectionId));
    }

    /**
     * @since 0.1.0
     */
    protected function countRows(string $tableSuffix): int
    {
        return (int) $this->db()->fetchOne(sprintf(
            'SELECT COUNT(*) FROM %s',
            $this->tableNameResolver()->resolve($tableSuffix),
        ));
    }

    /**
     * @return array<string, mixed>|null
     *
     * @since 0.1.0
     */
    protected function latestRow(string $tableSuffix, string $orderBy = 'id'): ?array
    {
        $row = $this->db()->fetchAssociative(sprintf(
            'SELECT * FROM %s ORDER BY %s DESC LIMIT 1',
            $this->tableNameResolver()->resolve($tableSuffix),
            $orderBy,
        ));

        return is_array($row) ? $row : null;
    }

    /**
     * @since 0.1.0
     */
    protected function createMailLog(DeliveryPlan $deliveryPlan, ?MailRequest $mailRequest = null): int
    {
        return $this->mailLogRepository()->create($mailRequest ?? $this->createMailRequest(), $deliveryPlan);
    }

    /**
     * @since 0.1.0
     */
    protected function createMailRequest(string $subject = 'Integration test mail'): MailRequest
    {
        return new MailRequest(
            from: [new MailAddress('sender@omni-mail.test', 'Omni Mail Test Sender')],
            to: [new MailAddress('recipient@omni-mail.test', 'Omni Mail Test Recipient')],
            cc: [],
            bcc: [],
            replyTo: [],
            subject: $subject,
            textBody: 'Integration test body.',
            htmlBody: null,
            attachments: [],
            headers: [],
            source: 'integration_test',
            metadata: [],
        );
    }

    /**
     * @since 0.1.0
     */
    protected function defaultSinglePlan(?int $preferredConnectionId = null): DeliveryPlan
    {
        return new DeliveryPlan(
            mode: DeliveryMode::Async,
            strategy: RoutingStrategy::Single,
            priority: 10,
            delaySeconds: 0,
            preferredConnectionId: $preferredConnectionId,
        );
    }

    /**
     * @param callable(): void $callback
     *
     * @return array{stdout: string, stderr: string}
     *
     * @since 0.1.0
     */
    protected function captureCli(callable $callback): array
    {
        require_once dirname(OMNI_MAIL_PLUGIN_FILE) . '/vendor/wp-cli/wp-cli/php/utils.php';

        $previousLogger = WP_CLI::get_logger();
        $logger = new Execution(false);

        WP_CLI::set_logger($logger);
        $logger->ob_start();

        try {
            $callback();
        } finally {
            $logger->ob_end();
            WP_CLI::set_logger($previousLogger);
        }

        return [
            'stdout' => $logger->stdout,
            'stderr' => $logger->stderr,
        ];
    }

    /**
     * @since 0.1.0
     */
    private function resetMigrationState(): void
    {
        $result = $this->migrationManager()->reset();

        if (isset($result['failed'])) {
            throw new RuntimeException((string) ($result['message'] ?? 'Unable to reset Omni Mail migration state.'));
        }
    }

    /**
     * @since 0.1.0
     */
    private function resetQueueActions(): void
    {
        if (function_exists('as_unschedule_all_actions')) {
            as_unschedule_all_actions(ActionSchedulerTrigger::RUN_HOOK, [], ActionSchedulerTrigger::GROUP);
            as_unschedule_all_actions(ActionSchedulerTrigger::RECURRING_HOOK, [], ActionSchedulerTrigger::GROUP);
            as_unschedule_all_actions(MailLogRetentionScheduler::RUN_HOOK, [], ActionSchedulerTrigger::GROUP);
        }

        delete_option(ActionSchedulerTrigger::SCHEDULE_LOCK_OPTION);
        delete_option(WorkerRunner::RUNNER_LEASE_OPTION);
    }

    /**
     * @since 0.1.0
     */
    private function stubActionSchedulerAsyncRunnerHttp(): void
    {
        add_filter('pre_http_request', [$this, 'preemptActionSchedulerAsyncRunnerHttp'], 10, 3);
    }

    /**
     * @since 0.1.0
     */
    private function unstubActionSchedulerAsyncRunnerHttp(): void
    {
        remove_filter('pre_http_request', [$this, 'preemptActionSchedulerAsyncRunnerHttp'], 10);
    }

    /**
     * @param array<string, mixed> $parsedArgs
     *
     * @return mixed
     *
     * @since 0.1.0
     */
    public function preemptActionSchedulerAsyncRunnerHttp(mixed $preempt, array $parsedArgs, string $url): mixed
    {
        $query = wp_parse_url($url, PHP_URL_QUERY);

        if (! is_string($query) || $query === '') {
            return $preempt;
        }

        $queryArgs = [];
        wp_parse_str($query, $queryArgs);

        if (($queryArgs['action'] ?? null) !== ActionSchedulerTrigger::ASYNC_RUNNER_ACTION) {
            return $preempt;
        }

        $this->capturedActionSchedulerWakeups[] = $url;

        return [
            'headers' => [],
            'body' => '',
            'response' => [
                'code' => 202,
                'message' => 'Accepted',
            ],
            'cookies' => [],
            'filename' => null,
        ];
    }
}
