<?php

declare (strict_types=1);
namespace OmniMail\Webhook\Event;

/**
 * Normalized webhook event payload.
 *
 * @since 0.1.0
 */
final readonly class WebhookEvent
{
    /**
     * @param array<string, mixed> $payload
     */
    public function __construct(public ?int $connectionId, public ?int $mailLogId, public string $eventType, public ?string $transportMessageId, public ?string $providerEventId, public array $payload, public ?string $occurredAt)
    {
    }
}
