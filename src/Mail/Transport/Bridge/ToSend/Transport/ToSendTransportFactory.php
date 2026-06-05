<?php

declare (strict_types=1);
namespace OmniMail\Mail\Transport\Bridge\ToSend\Transport;

use OmniMail\Discovery\Attribute\Service;
use OmniMail\Discovery\Attribute\TransportFactory;
use OmniMailDeps\Symfony\Component\Mailer\Exception\UnsupportedSchemeException;
use OmniMailDeps\Symfony\Component\Mailer\Transport\AbstractTransportFactory;
use OmniMailDeps\Symfony\Component\Mailer\Transport\Dsn;
use OmniMailDeps\Symfony\Component\Mailer\Transport\TransportInterface;
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
            'tosend+api' => (new \OmniMail\Mail\Transport\Bridge\ToSend\Transport\ToSendApiTransport($this->getUser($dsn), $this->client, $this->dispatcher, $this->logger))->setHost($host)->setPort($port),
            default => throw new UnsupportedSchemeException($dsn, 'tosend', $this->getSupportedSchemes()),
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
