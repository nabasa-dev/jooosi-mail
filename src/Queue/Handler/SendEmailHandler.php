<?php

declare (strict_types=1);
namespace OmniMail\Queue\Handler;

use OmniMail\Discovery\Attribute\MessageHandler;
use OmniMail\Discovery\Attribute\Service;
use OmniMail\Mail\Delivery\DeliveryService;
use OmniMail\Queue\Message\SendEmailMessage;
use OmniMail\Queue\Retry\RetryAfterException;
use OmniMail\Queue\Transport\DatabaseTransport;
use RuntimeException;
/**
 * Delivers queued email logs.
 *
 * @since 0.1.0
 */
#[Service]
#[MessageHandler(SendEmailMessage::class, DatabaseTransport::NAME)]
final readonly class SendEmailHandler
{
    public function __construct(private DeliveryService $deliveryService)
    {
    }
    /**
     * @since 0.1.0
     */
    public function __invoke(SendEmailMessage $message): void
    {
        $result = $this->deliveryService->deliver($message->mailLogId, finalizeFailures: \false);
        if (!$result->successful) {
            if ($result->temporaryFailure) {
                throw new RetryAfterException($result->error ?? 'Queued mail delivery is temporarily unavailable.', $result->retryAfterSeconds);
            }
            throw new RuntimeException($result->error ?? 'Queued mail delivery failed.');
        }
    }
}
