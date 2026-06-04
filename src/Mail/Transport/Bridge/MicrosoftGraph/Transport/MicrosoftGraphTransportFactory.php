<?php

declare(strict_types=1);

namespace OmniMail\Mail\Transport\Bridge\MicrosoftGraph\Transport;

use OmniMail\Discovery\Attribute\Service;
use OmniMail\Discovery\Attribute\TransportFactory;
use Symfony\Component\Mailer\Bridge\MicrosoftGraph\Transport\MicrosoftGraphTransportFactory as SymfonyMicrosoftGraphTransportFactory;

/**
 * Registers the Microsoft Graph bridge with Omni Mail's transport discovery.
 *
 * @since 0.1.0
 */
#[Service]
#[TransportFactory]
final class MicrosoftGraphTransportFactory extends SymfonyMicrosoftGraphTransportFactory
{
}
