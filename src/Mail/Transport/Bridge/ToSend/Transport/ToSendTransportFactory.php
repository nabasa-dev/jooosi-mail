<?php

declare(strict_types=1);

namespace JooosiMail\Mail\Transport\Bridge\ToSend\Transport;

use JooosiMail\Discovery\Attribute\Service;
use JooosiMail\Discovery\Attribute\TransportFactory;
use Symfony\Component\Mailer\Exception\UnsupportedSchemeException;
use Symfony\Component\Mailer\Transport\AbstractTransportFactory;
use Symfony\Component\Mailer\Transport\Dsn;
use Symfony\Component\Mailer\Transport\TransportInterface;

/**
 * toSend custom transport factory.
 *
 * @since 0.1.0
 */
#[Service]
#[TransportFactory]
final class ToSendTransportFactory extends AbstractTransportFactory
{
    public function create(Dsn $dsn): TransportInterface
    {
        $host = $dsn->getHost() === 'default' ? null : $dsn->getHost();
        $port = $dsn->getPort();

        return match ($dsn->getScheme()) {
            'tosend+api' => (new ToSendApiTransport($this->getUser($dsn), $this->client, $this->dispatcher, $this->logger))
                ->setHost($host)
                ->setPort($port),
            // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
            default => 
                throw new UnsupportedSchemeException($dsn, 'tosend', $this->getSupportedSchemes()),
        };
    }

    /**
     * @return list<string>
     *
     * @since 0.1.0
     */
    protected function getSupportedSchemes(): array
    {
        return ['tosend+api'];
    }
}
