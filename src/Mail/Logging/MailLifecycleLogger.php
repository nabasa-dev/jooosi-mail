<?php

declare (strict_types=1);
namespace OmniMail\Mail\Logging;

use OmniMail\Discovery\Attribute\Service;
use OmniMail\Mail\Routing\DeliveryPlan;
use OmniMail\Mail\ValueObject\MailRequest;
/**
 * High-level logging facade for mail lifecycle entries.
 *
 * @since 0.1.0
 */
#[Service]
final readonly class MailLifecycleLogger
{
    public function __construct(private \OmniMail\Mail\Logging\MailLogRepository $mailLogRepository)
    {
    }
    /**
     * @since 0.1.0
     */
    public function create(MailRequest $mailRequest, DeliveryPlan $deliveryPlan): int
    {
        return $this->mailLogRepository->create($mailRequest, $deliveryPlan);
    }
}
