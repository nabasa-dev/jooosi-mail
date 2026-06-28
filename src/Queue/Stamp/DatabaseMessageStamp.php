<?php

declare (strict_types=1);
namespace JooosiMail\Queue\Stamp;

use JooosiMailDeps\Symfony\Component\Messenger\Stamp\StampInterface;
/**
 * Transport metadata for a claimed queue message.
 *
 * @since 0.1.0
 */
final readonly class DatabaseMessageStamp implements StampInterface
{
    public function __construct(public int $messageId, public int $attemptCount, public int $maxAttempts, public string $queueName, public string $claimedBy)
    {
    }
}
