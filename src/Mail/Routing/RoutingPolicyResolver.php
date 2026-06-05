<?php

declare (strict_types=1);
namespace OmniMail\Mail\Routing;

use OmniMail\Discovery\Attribute\Service;
use OmniMail\Infrastructure\WordPress\OptionStore;
use OmniMail\Mail\ValueObject\MailRequest;
/**
 * Resolves sync/async, priority, and strategy defaults.
 *
 * @since 0.1.0
 */
#[Service]
final readonly class RoutingPolicyResolver
{
    public function __construct(private OptionStore $optionStore)
    {
    }
    /**
     * @since 0.1.0
     */
    public function resolve(MailRequest $mailRequest): \OmniMail\Mail\Routing\DeliveryPlan
    {
        $priority = $this->resolvePriority($mailRequest);
        $delaySeconds = $this->resolveDelay($mailRequest);
        $preferredConnectionId = $this->resolvePreferredConnectionId($mailRequest);
        return new \OmniMail\Mail\Routing\DeliveryPlan(mode: \OmniMail\Mail\Routing\DeliveryMode::fromMixed($this->optionStore->get('settings.delivery.mode', \OmniMail\Mail\Routing\DeliveryMode::Async->value)), strategy: \OmniMail\Mail\Routing\RoutingStrategy::fromMixed($this->optionStore->get('settings.delivery.strategy', \OmniMail\Mail\Routing\RoutingStrategy::WeightedRandom->value)), priority: $priority, delaySeconds: $delaySeconds, preferredConnectionId: $preferredConnectionId);
    }
    /**
     * @since 0.1.0
     */
    private function resolvePreferredConnectionId(MailRequest $mailRequest): ?int
    {
        $preferredConnectionId = $mailRequest->metadata['preferred_connection_id'] ?? $mailRequest->headers['X-Omni-Mail-Connection-Id'] ?? null;
        if (!is_scalar($preferredConnectionId)) {
            return null;
        }
        $preferredConnectionId = (int) $preferredConnectionId;
        return $preferredConnectionId > 0 ? $preferredConnectionId : null;
    }
    private function resolvePriority(MailRequest $mailRequest): int
    {
        $priority = strtolower((string) ($mailRequest->headers['X-Priority'] ?? 'normal'));
        return match ($priority) {
            'high' => 1,
            'low' => 20,
            default => 10,
        };
    }
    private function resolveDelay(MailRequest $mailRequest): int
    {
        $schedule = $mailRequest->headers['X-Schedule-Time'] ?? null;
        if (!is_string($schedule) || $schedule === '') {
            return 0;
        }
        $timestamp = strtotime($schedule);
        if ($timestamp === \false) {
            return 0;
        }
        return max(0, $timestamp - time());
    }
}
