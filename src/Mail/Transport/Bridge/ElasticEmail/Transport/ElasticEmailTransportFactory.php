<?php

declare(strict_types=1);

namespace OmniMail\Mail\Transport\Bridge\ElasticEmail\Transport;

use OmniMail\Discovery\Attribute\Service;
use OmniMail\Discovery\Attribute\TransportFactory;
use Symfony\Component\Mailer\Exception\UnsupportedSchemeException;
use Symfony\Component\Mailer\Transport\AbstractTransportFactory;
use Symfony\Component\Mailer\Transport\Dsn;
use Symfony\Component\Mailer\Transport\TransportInterface;

#[Service]
#[TransportFactory]
final class ElasticEmailTransportFactory extends AbstractTransportFactory
{
    public function create(Dsn $dsn): TransportInterface
    {
        $scheme = $dsn->getScheme();
        $user = $this->getUser($dsn);
        $host = $dsn->getHost() === 'default' ? null : $dsn->getHost();
        $port = $dsn->getPort();

        return match ($scheme) {
            'elasticemail+api' => (new ElasticEmailApiTransport($user, $this->client, $this->dispatcher, $this->logger))->setHost($host)->setPort($port),
            'elasticemail+smtp', 'elasticemail+smtps' => new ElasticEmailSmtpTransport($user, $this->getPassword($dsn), $port, $this->dispatcher, $this->logger),
            default => throw new UnsupportedSchemeException($dsn, 'elasticemail', $this->getSupportedSchemes()),
        };
    }

    protected function getSupportedSchemes(): array
    {
        return ['elasticemail+api', 'elasticemail+smtp', 'elasticemail+smtps'];
    }
}
