<?php

declare(strict_types=1);

namespace OmniMail\Webhook\Adapter;

use OmniMail\Discovery\Attribute\Service;
use OmniMail\Mail\Connection\Connection;
use Override;

/**
 * Normalizes Bird webhook batches.
 *
 * @link https://docs.bird.com/api/email-api/webhooks
 *
 * @since 0.1.0
 */
#[Service]
final class BirdWebhookAdapter extends SparkPostWebhookAdapter
{
    #[Override]
    public function getPriority(): int
    {
        return 331;
    }

    #[Override]
    public function supports(Connection $connection): bool
    {
        return $connection->profileKey === 'bird';
    }
}
