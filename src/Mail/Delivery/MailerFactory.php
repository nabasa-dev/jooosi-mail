<?php

declare (strict_types=1);
namespace OmniMail\Mail\Delivery;

use OmniMail\Discovery\Attribute\Service;
use OmniMail\Mail\Connection\Connection;
use OmniMail\Mail\Connection\ConnectionDsnResolver;
use OmniMail\Mail\Transport\TransportRegistry;
use OmniMailDeps\Symfony\Component\Mailer\Mailer;
/**
 * Creates mailer instances for a connection.
 *
 * @since 0.1.0
 */
#[Service]
final readonly class MailerFactory
{
    public function __construct(private ConnectionDsnResolver $connectionDsnResolver, private TransportRegistry $transportRegistry)
    {
    }
    /**
     * @since 0.1.0
     */
    public function create(Connection $connection): Mailer
    {
        return new Mailer($this->transportRegistry->create($this->connectionDsnResolver->resolve($connection)));
    }
}
