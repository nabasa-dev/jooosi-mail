<?php

declare (strict_types=1);
namespace OmniMail\Mail\ValueObject;

/**
 * Delivery outcome for a mail send attempt.
 *
 * @since 0.1.0
 */
final readonly class DeliveryResult
{
    public function __construct(public bool $successful, public ?int $connectionId = null, public ?string $transportMessageId = null, public ?string $debug = null, public ?string $error = null, public bool $temporaryFailure = \false, public ?int $retryAfterSeconds = null)
    {
    }
}
