<?php

declare(strict_types=1);

namespace OmniMail\Queue\Transport;

use OmniMail\Discovery\Attribute\Service;
use Override;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Transport\TransportInterface;

/**
 * Messenger transport backed by the Omni Mail queue table.
 *
 * @since 0.1.0
 */
#[Service]
final readonly class DatabaseTransport implements TransportInterface
{
    public const string NAME = 'async';

    public function __construct(
        private DatabaseSender $sender,
        private DatabaseReceiver $receiver,
    ) {
    }

    /**
     * @since 0.1.0
     */
    #[Override]
    public function send(Envelope $envelope): Envelope
    {
        return $this->sender->send($envelope);
    }

    /**
     * @since 0.1.0
     */
    #[Override]
    public function get(): iterable
    {
        return $this->receiver->get();
    }

    /**
     * @since 0.1.0
     */
    #[Override]
    public function ack(Envelope $envelope): void
    {
        $this->receiver->ack($envelope);
    }

    /**
     * @since 0.1.0
     */
    #[Override]
    public function reject(Envelope $envelope): void
    {
        $this->receiver->reject($envelope);
    }
}
