<?php

declare (strict_types=1);
namespace OmniMail\Webhook\Adapter;

use OmniMail\Discovery\Attribute\Service;
use OmniMail\Mail\Connection\Connection;
use Override;
/**
 * Generic fallback webhook adapter.
 *
 * @since 0.1.0
 */
#[Service]
final class GenericWebhookAdapter extends \OmniMail\Webhook\Adapter\AbstractWebhookAdapter
{
    #[Override]
    public function getPriority(): int
    {
        return -1000;
    }
    #[Override]
    public function supports(Connection $connection): bool
    {
        return \true;
    }
}
