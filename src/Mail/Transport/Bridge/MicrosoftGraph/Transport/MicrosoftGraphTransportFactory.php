<?php

declare(strict_types=1);

namespace JooosiMail\Mail\Transport\Bridge\MicrosoftGraph\Transport;

use JooosiMail\Discovery\Attribute\Service;
use JooosiMail\Discovery\Attribute\TransportFactory;
use Symfony\Component\Mailer\Bridge\MicrosoftGraph\Transport\MicrosoftGraphTransportFactory as SymfonyMicrosoftGraphTransportFactory;

/**
 * Registers the Microsoft Graph bridge with Jooosi Mail's transport discovery.
 *
 * @since 0.1.0
 */
#[Service]
#[TransportFactory]
final class MicrosoftGraphTransportFactory extends SymfonyMicrosoftGraphTransportFactory
{
}
