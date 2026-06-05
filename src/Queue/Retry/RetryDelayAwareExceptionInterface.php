<?php

declare (strict_types=1);
namespace OmniMail\Queue\Retry;

/**
 * Exposes a preferred retry delay for queued failures.
 *
 * @since 0.1.0
 */
interface RetryDelayAwareExceptionInterface
{
    public function getRetryAfterSeconds(): ?int;
}
