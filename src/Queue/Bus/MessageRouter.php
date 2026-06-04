<?php

declare(strict_types=1);

namespace OmniMail\Queue\Bus;

use OmniMail\Discovery\Attribute\Service;
use OmniMail\Queue\Message\SendEmailMessage;
use OmniMail\Queue\Transport\DatabaseTransport;
use Override;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Stamp\TransportNamesStamp;
use Symfony\Component\Messenger\Transport\Sender\SenderInterface;
use Symfony\Component\Messenger\Transport\Sender\SendersLocatorInterface;

/**
 * Routes async messages to the Omni Mail database transport.
 *
 * @since 0.1.0
 */
#[Service]
final readonly class MessageRouter implements SendersLocatorInterface
{
    public function __construct(
        private DatabaseTransport $databaseTransport,
    ) {
    }

    /**
     * @return iterable<string, SenderInterface>
     *
     * @since 0.1.0
     */
    #[Override]
    public function getSenders(Envelope $envelope): iterable
    {
        $message = $envelope->getMessage();
        $transportNamesStamp = $envelope->last(TransportNamesStamp::class);
        $transportNames = $transportNamesStamp?->getTransportNames() ?? [];

        if ($message instanceof SendEmailMessage || in_array(DatabaseTransport::NAME, $transportNames, true)) {
            yield DatabaseTransport::NAME => $this->databaseTransport;
        }
    }
}
