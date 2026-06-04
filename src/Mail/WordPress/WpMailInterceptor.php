<?php

declare(strict_types=1);

namespace OmniMail\Mail\WordPress;

use Doctrine\DBAL\Connection as DbalConnection;
use OmniMail\Discovery\Attribute\Hook;
use OmniMail\Discovery\Attribute\Service;
use OmniMail\Infrastructure\Event\EventPublisherInterface;
use OmniMail\Infrastructure\WordPress\OptionStore;
use OmniMail\Mail\Delivery\DeliveryService;
use OmniMail\Mail\Logging\MailLifecycleLogger;
use OmniMail\Mail\Routing\DeliveryMode;
use OmniMail\Mail\Routing\RoutingPolicyResolver;
use OmniMail\Queue\Message\SendEmailMessage;
use OmniMail\Queue\Stamp\QueuePriorityStamp;
use OmniMail\Queue\Transport\DatabaseTransport;
use OmniMail\Queue\Trigger\TriggerCoordinator;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\DelayStamp;
use Symfony\Component\Messenger\Stamp\TransportNamesStamp;
use Throwable;

/**
 * Intercepts `wp_mail()` and hands off to Omni Mail.
 *
 * @since 0.1.0
 */
#[Service]
final class WpMailInterceptor
{
    private bool $intercepting = false;

    public function __construct(
        private readonly WpMailPayloadNormalizer $payloadNormalizer,
        private readonly RoutingPolicyResolver $routingPolicyResolver,
        private readonly MailLifecycleLogger $mailLifecycleLogger,
        private readonly DeliveryService $deliveryService,
        private readonly MessageBusInterface $messageBus,
        private readonly TriggerCoordinator $triggerCoordinator,
        private readonly OptionStore $optionStore,
        private readonly DbalConnection $connection,
        private readonly EventPublisherInterface $eventPublisher,
    ) {
    }

    /**
     * Replace native sending with Omni Mail.
     *
     * @param array<string, mixed> $args
     *
     * @since 0.1.0
     */
    #[Hook(name: 'pre_wp_mail', kind: 'filter', priority: 9999, acceptedArgs: 2)]
    public function intercept(?bool $preempt, array $args): ?bool
    {
        if ($this->intercepting || ! $this->isEnabled()) {
            return $preempt;
        }

        $this->intercepting = true;

        try {
            $mailRequest = $this->payloadNormalizer->normalize($args);
            $deliveryPlan = $this->routingPolicyResolver->resolve($mailRequest);

            if ($deliveryPlan->mode === DeliveryMode::Sync) {
                $mailLogId = $this->mailLifecycleLogger->create($mailRequest, $deliveryPlan);
                $result = $this->deliveryService->deliver($mailLogId);

                return $result->successful;
            }

            $stamps = [
                new TransportNamesStamp([DatabaseTransport::NAME]),
                new QueuePriorityStamp($deliveryPlan->priority),
            ];

            if ($deliveryPlan->delaySeconds > 0) {
                $stamps[] = new DelayStamp($deliveryPlan->delaySeconds * 1000);
            }

            $this->connection->beginTransaction();

            try {
                $mailLogId = $this->mailLifecycleLogger->create($mailRequest, $deliveryPlan);
                $this->messageBus->dispatch(new SendEmailMessage($mailLogId), $stamps);
                $this->connection->commit();
            } catch (Throwable $throwable) {
                $this->connection->rollBack();

                throw $throwable;
            }

            try {
                $this->triggerCoordinator->trigger();
            } catch (Throwable $throwable) {
                $this->eventPublisher->doAction('a!omni-mail/queue:trigger.failed', $throwable, $mailLogId);
            }

            $this->eventPublisher->doAction('a!omni-mail/mail:queued', $mailLogId, DatabaseTransport::NAME);

            return true;
        } catch (Throwable $throwable) {
            $this->eventPublisher->doAction('a!omni-mail/mail:intercept.failed', $throwable, $args);

            return false;
        } finally {
            $this->intercepting = false;
        }
    }

    /**
     * @since 0.1.0
     */
    private function isEnabled(): bool
    {
        $enabled = (bool) $this->optionStore->get('settings.mail.intercept.enabled', true);

        return (bool) $this->eventPublisher->applyFilters('f!omni-mail/mail:intercept.enabled', $enabled);
    }
}
