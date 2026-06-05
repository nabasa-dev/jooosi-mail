<?php

declare (strict_types=1);
namespace OmniMail\Queue\Failure;

use OmniMail\Discovery\Attribute\Service;
use OmniMail\Queue\Maintenance\QueueMaintenanceService;
/**
 * Retry orchestration for failed queue rows.
 *
 * @since 0.1.0
 */
#[Service]
final readonly class FailedMessageService
{
    public function __construct(private QueueMaintenanceService $queueMaintenanceService)
    {
    }
    /**
     * @since 0.1.0
     */
    public function retry(?int $messageId = null): int
    {
        return $this->queueMaintenanceService->retryFailed($messageId);
    }
}
