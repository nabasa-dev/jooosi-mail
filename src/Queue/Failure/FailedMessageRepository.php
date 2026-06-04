<?php

declare(strict_types=1);

namespace OmniMail\Queue\Failure;

use OmniMail\Discovery\Attribute\Service;
use OmniMail\Queue\Query\QueueMessageQuery;

/**
 * Read model for failed queue messages.
 *
 * @since 0.1.0
 */
#[Service]
final readonly class FailedMessageRepository
{
    public function __construct(
        private QueueMessageQuery $queueMessageQuery,
    ) {
    }

    /**
     * @return list<array<string, mixed>>
     *
     * @since 0.1.0
     */
    public function list(int $limit = 50): array
    {
        return $this->queueMessageQuery->listFailed($limit);
    }

    /**
     * @since 0.1.0
     */
    public function count(): int
    {
        return $this->queueMessageQuery->countFailed();
    }
}
