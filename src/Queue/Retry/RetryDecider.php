<?php

declare (strict_types=1);
namespace OmniMail\Queue\Retry;

use OmniMail\Discovery\Attribute\Service;
use OmniMail\Queue\Stamp\DatabaseMessageStamp;
use OmniMailDeps\Symfony\Component\Messenger\Envelope;
use OmniMailDeps\Symfony\Component\Messenger\Exception\UnrecoverableMessageHandlingException;
use Throwable;
/**
 * Applies retry rules to failed queue messages.
 *
 * @since 0.1.0
 */
#[Service]
final readonly class RetryDecider
{
    public function __construct(private \OmniMail\Queue\Retry\RetryPolicy $retryPolicy)
    {
    }
    /**
     * @since 0.1.0
     */
    public function shouldRetry(Envelope $envelope, Throwable $throwable): bool
    {
        if ($throwable instanceof UnrecoverableMessageHandlingException) {
            return \false;
        }
        $stamp = $envelope->last(DatabaseMessageStamp::class);
        if (!$stamp instanceof DatabaseMessageStamp) {
            return \false;
        }
        return $this->retryPolicy->shouldRetry($stamp->attemptCount, $stamp->maxAttempts);
    }
    /**
     * @since 0.1.0
     */
    public function getDelaySeconds(Envelope $envelope, ?Throwable $throwable = null): int
    {
        if ($throwable instanceof \OmniMail\Queue\Retry\RetryDelayAwareExceptionInterface) {
            $retryAfterSeconds = $throwable->getRetryAfterSeconds();
            if ($retryAfterSeconds !== null && $retryAfterSeconds > 0) {
                return $retryAfterSeconds;
            }
        }
        $stamp = $envelope->last(DatabaseMessageStamp::class);
        if (!$stamp instanceof DatabaseMessageStamp) {
            return 0;
        }
        return $this->retryPolicy->getDelaySeconds($stamp->attemptCount);
    }
}
