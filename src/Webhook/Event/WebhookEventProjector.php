<?php

declare (strict_types=1);
namespace OmniMail\Webhook\Event;

use OmniMail\Discovery\Attribute\Service;
use OmniMail\Infrastructure\Event\EventPublisherInterface;
/**
 * Projects normalized webhook events into WordPress hooks.
 *
 * @since 0.1.0
 */
#[Service]
final readonly class WebhookEventProjector
{
    public function __construct(private EventPublisherInterface $eventPublisher)
    {
    }
    /**
     * @since 0.1.0
     */
    public function project(\OmniMail\Webhook\Event\WebhookEvent $event): void
    {
        $this->eventPublisher->doAction('a!omni-mail/webhook:event', $event);
        $this->eventPublisher->doAction('a!omni-mail/webhook:event.' . $event->eventType, $event);
    }
}
