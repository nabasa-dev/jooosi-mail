<?php

declare(strict_types=1);

namespace OmniMail\Mail\Transport\Bridge\SparkPost\Transport;

use OmniMail\Discovery\Attribute\Service;
use OmniMail\Discovery\Attribute\TransportFactory;
use Symfony\Component\Mailer\Exception\UnsupportedSchemeException;
use Symfony\Component\Mailer\Transport\AbstractTransportFactory;
use Symfony\Component\Mailer\Transport\Dsn;
use Symfony\Component\Mailer\Transport\TransportInterface;

#[Service]
#[TransportFactory]
final class SparkPostTransportFactory extends AbstractTransportFactory
{
    public function create(Dsn $dsn): TransportInterface
    {
        $scheme = $dsn->getScheme();
        $apiKey = $this->getUser($dsn) ?? $this->getPassword($dsn);
        $host = $dsn->getHost() === 'default' ? null : $dsn->getHost();
        $port = $dsn->getPort();
        $region = $dsn->getOption('region');

        return match ($scheme) {
            'sparkpost+api' => (new SparkPostApiTransport($apiKey, $region, $this->client, $this->dispatcher, $this->logger))->setHost($host)->setPort($port),
            'sparkpost+smtp', 'sparkpost+smtps' => new SparkPostSmtpTransport($apiKey, $region, $port, $this->dispatcher, $this->logger),
            default => 
                // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
                throw new UnsupportedSchemeException($dsn, 'sparkpost', $this->getSupportedSchemes()),
        };
    }

    protected function getSupportedSchemes(): array
    {
        return ['sparkpost+api', 'sparkpost+smtp', 'sparkpost+smtps'];
    }
}
