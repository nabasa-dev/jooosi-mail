<?php

declare(strict_types=1);

namespace OmniMail\Mail\Transport\Bridge\Pepipost\Transport;

use OmniMail\Discovery\Attribute\Service;
use OmniMail\Discovery\Attribute\TransportFactory;
use Symfony\Component\Mailer\Exception\UnsupportedSchemeException;
use Symfony\Component\Mailer\Transport\AbstractTransportFactory;
use Symfony\Component\Mailer\Transport\Dsn;
use Symfony\Component\Mailer\Transport\TransportInterface;

#[Service]
#[TransportFactory]
final class PepipostTransportFactory extends AbstractTransportFactory
{
    public function create(Dsn $dsn): TransportInterface
    {
        $scheme = $dsn->getScheme();
        $user = $this->getUser($dsn);
        $host = $dsn->getHost() === 'default' ? null : $dsn->getHost();
        $port = $dsn->getPort();
        $region = $dsn->getOption('region');

        return match ($scheme) {
            'pepipost+api' => (new PepipostApiTransport($user, $region, $this->client, $this->dispatcher, $this->logger))->setHost($host)->setPort($port),
            'pepipost+smtp', 'pepipost+smtps' => new PepipostSmtpTransport($user, $this->getPassword($dsn), $port, $this->dispatcher, $this->logger),
            default => 
                // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
                throw new UnsupportedSchemeException($dsn, 'pepipost', $this->getSupportedSchemes()),
        };
    }

    protected function getSupportedSchemes(): array
    {
        return ['pepipost+api', 'pepipost+smtp', 'pepipost+smtps'];
    }
}
