<?php

declare(strict_types=1);

namespace OmniMail\Mail\Delivery;

use OmniMail\Discovery\Attribute\Service;
use OmniMail\Infrastructure\Event\EventPublisherInterface;
use OmniMail\Mail\Connection\Connection;
use OmniMail\Mail\Connection\ConnectionDsnResolver;
use OmniMail\Mail\Logging\MailAttemptRepository;
use OmniMail\Mail\Logging\MailLogRepository;
use OmniMail\Mail\Logging\MailLogRetentionService;
use OmniMail\Mail\Routing\ConnectionCircuitBreaker;
use OmniMail\Mail\Routing\ConnectionRateLimiter;
use OmniMail\Mail\Routing\ConnectionResolver;
use OmniMail\Mail\Routing\ConnectionStatusReporter;
use OmniMail\Mail\Routing\DeliveryPlan;
use OmniMail\Mail\Routing\RoutingPolicyResolver;
use OmniMail\Mail\Sender\SenderPolicyResolver;
use OmniMail\Mail\Transport\TransportRegistry;
use OmniMail\Mail\ValueObject\DeliveryResult;
use OmniMail\Mail\ValueObject\MailRequest;
use Throwable;

/**
 * Executes delivery attempts for a logged email.
 *
 * @since 0.1.0
 */
#[Service]
final readonly class DeliveryService
{
    public function __construct(
        private MailLogRepository $mailLogRepository,
        private MailAttemptRepository $mailAttemptRepository,
        private ConnectionResolver $connectionResolver,
        private ConnectionStatusReporter $connectionStatusReporter,
        private ConnectionRateLimiter $connectionRateLimiter,
        private ConnectionCircuitBreaker $connectionCircuitBreaker,
        private RoutingPolicyResolver $routingPolicyResolver,
        private ConnectionDsnResolver $connectionDsnResolver,
        private TransportRegistry $transportRegistry,
        private EmailFactory $emailFactory,
        private SenderPolicyResolver $senderPolicyResolver,
        private EventPublisherInterface $eventPublisher,
        private MailLogRetentionService $mailLogRetentionService,
    ) {
    }

    /**
     * @since 0.1.0
     */
    public function deliver(int $mailLogId, bool $finalizeFailures = true): DeliveryResult
    {
        $mailLog = $this->mailLogRepository->find($mailLogId);

        if (! is_array($mailLog)) {
            return new DeliveryResult(successful: false, error: 'Mail log not found.');
        }

        if (($mailLog['status'] ?? null) === 'sent') {
            return new DeliveryResult(successful: true);
        }

        $sentAttempt = $this->mailAttemptRepository->findLatestSent($mailLogId);

        if (is_array($sentAttempt)) {
            return $this->reconcileSentAttempt($mailLogId, $sentAttempt);
        }

        $mailRequest = MailRequest::fromArray(json_decode((string) ($mailLog['payload_json'] ?? '{}'), true) ?: []);
        $deliveryPlan = $this->routingPolicyResolver->resolve($mailRequest);
        $connections = $this->connectionResolver->resolve($deliveryPlan);

        if ($connections === []) {
            return $this->handleNoAvailableConnections($mailLogId, $finalizeFailures);
        }

        $this->mailLogRepository->markProcessing($mailLogId);
        $reservedAnyConnection = false;

        foreach ($connections as $connection) {
            if ($connection->id === null) {
                continue;
            }

            if (! $this->connectionRateLimiter->reserve($connection)) {
                continue;
            }

            $reservedAnyConnection = true;

            try {
                $transport = $this->transportRegistry->create($this->connectionDsnResolver->resolve($connection));
                $deliveryMailRequest = $this->senderPolicyResolver->apply(
                    $this->filterDeliveryMailRequest($mailRequest, $mailLogId, $connection, $deliveryPlan),
                    $connection,
                );

                if ($deliveryMailRequest !== $mailRequest) {
                    $this->mailLogRepository->updatePayload($mailLogId, $deliveryMailRequest);
                }

                $sentMessage = $transport->send(
                    $this->emailFactory->create($deliveryMailRequest),
                    $this->emailFactory->createEnvelope($deliveryMailRequest),
                );
            } catch (Throwable $throwable) {
                $this->recordConnectionFailure($mailLogId, $connection->id, $connection, $throwable);

                continue;
            }

            $transportMessageId = $sentMessage->getMessageId();
            $debug = $sentMessage->getDebug();
            $normalizedDebug = is_string($debug) ? $debug : (wp_json_encode($debug) ?: null);

            $this->recordAcceptedDelivery(
                mailLogId: $mailLogId,
                connectionId: $connection->id,
                transportMessageId: $transportMessageId,
                debug: $normalizedDebug,
            );

            $this->recordConnectionSuccess($connection);
            $this->dispatchMailSentAction($mailLogId, $connection->id, $transportMessageId);
            $this->cleanupTerminalLog($mailLogId);

            return new DeliveryResult(
                successful: true,
                connectionId: $connection->id,
                transportMessageId: $transportMessageId,
                debug: $normalizedDebug,
            );
        }

        if (! $reservedAnyConnection) {
            return $this->handleNoAvailableConnections($mailLogId, $finalizeFailures);
        }

        $error = 'No connection could deliver this message.';

        if ($finalizeFailures) {
            $this->mailLogRepository->markFailed($mailLogId, $error);
            $this->eventPublisher->doAction('a!omni-mail/mail:failed', $mailLogId, $error);
            $this->cleanupTerminalLog($mailLogId);
        } else {
            $this->mailLogRepository->markDeferred($mailLogId, $error);
        }

        return new DeliveryResult(successful: false, error: $error);
    }

    /**
     * @since 0.1.0
     */
    private function filterDeliveryMailRequest(
        MailRequest $mailRequest,
        int $mailLogId,
        Connection $connection,
        DeliveryPlan $deliveryPlan,
    ): MailRequest {
        $filteredMailRequest = $this->eventPublisher->applyFilters(
            'f!omni-mail/mail:delivery.request',
            $mailRequest,
            $mailLogId,
            $connection,
            $deliveryPlan,
        );

        return $filteredMailRequest instanceof MailRequest ? $filteredMailRequest : $mailRequest;
    }

    /**
     * @since 0.1.0
     */
    private function handleNoAvailableConnections(int $mailLogId, bool $finalizeFailures): DeliveryResult
    {
        $summary = $this->connectionStatusReporter->summarizeActiveConnections();

        if (($summary['active_connections'] ?? 0) <= 0) {
            $error = 'No active connections are configured.';

            if ($finalizeFailures) {
                $this->mailLogRepository->markFailed($mailLogId, $error);
                $this->eventPublisher->doAction('a!omni-mail/mail:failed', $mailLogId, $error);
                $this->cleanupTerminalLog($mailLogId);
            } else {
                $this->mailLogRepository->markDeferred($mailLogId, $error);
            }

            return new DeliveryResult(successful: false, error: $error);
        }

        $retryAfterSeconds = isset($summary['next_available_in_seconds']) ? (int) $summary['next_available_in_seconds'] : null;

        if ($retryAfterSeconds !== null && $retryAfterSeconds > 0) {
            $error = sprintf('All active connections are temporarily unavailable. Retry in %d second(s).', $retryAfterSeconds);
            $this->mailLogRepository->markDeferred($mailLogId, $error);
            $this->eventPublisher->doAction('a!omni-mail/mail:deferred', $mailLogId, $retryAfterSeconds);

            return new DeliveryResult(
                successful: false,
                error: $error,
                temporaryFailure: true,
                retryAfterSeconds: $retryAfterSeconds,
            );
        }

        $error = 'No available connections passed routing checks.';

        if ($finalizeFailures) {
            $this->mailLogRepository->markFailed($mailLogId, $error);
            $this->eventPublisher->doAction('a!omni-mail/mail:failed', $mailLogId, $error);
            $this->cleanupTerminalLog($mailLogId);
        } else {
            $this->mailLogRepository->markDeferred($mailLogId, $error);
        }

        return new DeliveryResult(successful: false, error: $error);
    }

    /**
     * @since 0.1.0
     */
    private function reconcileSentAttempt(int $mailLogId, array $sentAttempt): DeliveryResult
    {
        $connectionId = (int) ($sentAttempt['connection_id'] ?? 0);
        $transportMessageId = isset($sentAttempt['transport_message_id']) ? (string) $sentAttempt['transport_message_id'] : null;

        if ($connectionId > 0) {
            try {
                $this->mailLogRepository->markSent($mailLogId, $connectionId, $transportMessageId);
                $this->cleanupTerminalLog($mailLogId);
            } catch (Throwable $throwable) {
                $this->eventPublisher->doAction('a!omni-mail/mail:sent.reconcile-failed', $mailLogId, $connectionId, $throwable);
            }
        }

        return new DeliveryResult(
            successful: true,
            connectionId: $connectionId > 0 ? $connectionId : null,
            transportMessageId: $transportMessageId,
        );
    }

    /**
     * @since 0.1.0
     */
    private function recordAcceptedDelivery(int $mailLogId, int $connectionId, ?string $transportMessageId, ?string $debug): void
    {
        $recordedAttempt = false;
        $markedSent = false;
        $persistenceErrors = [];

        try {
            $this->mailAttemptRepository->record(
                mailLogId: $mailLogId,
                connectionId: $connectionId,
                status: 'sent',
                debug: $debug,
                transportMessageId: $transportMessageId,
            );
            $recordedAttempt = true;
        } catch (Throwable $throwable) {
            $persistenceErrors[] = $throwable;
        }

        try {
            $this->mailLogRepository->markSent($mailLogId, $connectionId, $transportMessageId);
            $markedSent = true;
        } catch (Throwable $throwable) {
            $persistenceErrors[] = $throwable;
        }

        if (! $recordedAttempt && ! $markedSent) {
            $this->eventPublisher->doAction('a!omni-mail/mail:sent.persistence-failed', $mailLogId, $connectionId, $transportMessageId, $persistenceErrors);
        }
    }

    /**
     * @since 0.1.0
     */
    private function recordConnectionSuccess(Connection $connection): void
    {
        try {
            $this->connectionCircuitBreaker->recordSuccess($connection);
        } catch (Throwable $throwable) {
            $this->eventPublisher->doAction('a!omni-mail/routing:success-record.failed', $connection, $throwable);
        }
    }

    /**
     * @since 0.1.0
     */
    private function dispatchMailSentAction(int $mailLogId, int $connectionId, ?string $transportMessageId): void
    {
        try {
            $this->eventPublisher->doAction('a!omni-mail/mail:sent', $mailLogId, $connectionId, $transportMessageId);
        } catch (Throwable) {
        }
    }

    /**
     * @since 0.1.0
     */
    private function cleanupTerminalLog(int $mailLogId): void
    {
        try {
            $this->mailLogRetentionService->cleanupTerminalLog($mailLogId);
        } catch (Throwable $throwable) {
            $this->eventPublisher->doAction('a!omni-mail/mail:retention-cleanup.failed', $mailLogId, $throwable);
        }
    }

    /**
     * @since 0.1.0
     */
    private function recordConnectionFailure(int $mailLogId, int $connectionId, Connection $connection, Throwable $throwable): void
    {
        try {
            $this->mailAttemptRepository->record(
                mailLogId: $mailLogId,
                connectionId: $connectionId,
                status: 'failed',
                error: $throwable->getMessage(),
                debug: method_exists($throwable, 'getDebug') ? (string) call_user_func([$throwable, 'getDebug']) : null,
            );
        } catch (Throwable $loggingThrowable) {
            $this->eventPublisher->doAction('a!omni-mail/mail:failed.connection-log-failed', $mailLogId, $connectionId, $throwable, $loggingThrowable);
        }

        try {
            $this->connectionCircuitBreaker->recordFailure($connection, $throwable);
        } catch (Throwable $circuitBreakerThrowable) {
            $this->eventPublisher->doAction('a!omni-mail/routing:failure-record.failed', $connection, $throwable, $circuitBreakerThrowable);
        }

        $this->eventPublisher->doAction('a!omni-mail/mail:failed.connection', $mailLogId, $connectionId, $throwable);
    }
}
