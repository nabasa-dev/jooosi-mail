<?php

declare (strict_types=1);
namespace OmniMail\Webhook\Event;

use OmniMail\Discovery\Attribute\Hook;
use OmniMail\Discovery\Attribute\Service;
use OmniMail\Infrastructure\Event\EventPublisherInterface;
use OmniMail\Mail\Connection\ConnectionRepository;
use OmniMail\Mail\Routing\ConnectionCircuitBreaker;
use RuntimeException;
/**
 * Feeds webhook delivery feedback back into routing health decisions.
 *
 * @since 0.1.0
 */
#[Service]
final readonly class WebhookRoutingHealthListener
{
    public function __construct(private ConnectionRepository $connectionRepository, private ConnectionCircuitBreaker $connectionCircuitBreaker, private EventPublisherInterface $eventPublisher)
    {
    }
    /**
     * @since 0.1.0
     */
    #[Hook(name: 'a!omni-mail/webhook:event', kind: 'action', acceptedArgs: 1)]
    public function handle(\OmniMail\Webhook\Event\WebhookEvent $event): void
    {
        if ($event->connectionId === null) {
            return;
        }
        $connection = $this->connectionRepository->find($event->connectionId);
        if ($connection === null) {
            return;
        }
        $eventType = strtolower($event->eventType);
        if (!$this->shouldAffectCircuitBreaker($eventType)) {
            return;
        }
        $message = sprintf('Webhook routing feedback: %s', $eventType);
        $this->connectionCircuitBreaker->recordFailure($connection, new RuntimeException($message));
        $this->eventPublisher->doAction('a!omni-mail/routing:webhook-feedback.recorded', $connection, $event);
    }
    /**
     * @since 0.1.0
     */
    private function shouldAffectCircuitBreaker(string $eventType): bool
    {
        return in_array($eventType, ['provider_unavailable', 'outage', 'temporarily_unavailable', 'connection_error', 'api_error', 'throttled', 'rate_limited', 'rejected', 'blocked', 'dropped', 'failed'], \true);
    }
}
