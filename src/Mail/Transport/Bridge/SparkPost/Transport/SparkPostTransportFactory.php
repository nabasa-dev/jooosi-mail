<?php

declare (strict_types=1);
namespace OmniMail\Mail\Transport\Bridge\SparkPost\Transport;

use OmniMail\Discovery\Attribute\Service;
use OmniMail\Discovery\Attribute\TransportFactory;
use OmniMailDeps\Symfony\Component\Mailer\Exception\UnsupportedSchemeException;
use OmniMailDeps\Symfony\Component\Mailer\Transport\AbstractTransportFactory;
use OmniMailDeps\Symfony\Component\Mailer\Transport\Dsn;
use OmniMailDeps\Symfony\Component\Mailer\Transport\TransportInterface;
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
            'sparkpost+api' => (new \OmniMail\Mail\Transport\Bridge\SparkPost\Transport\SparkPostApiTransport($apiKey, $region, $this->client, $this->dispatcher, $this->logger))->setHost($host)->setPort($port),
            'sparkpost+smtp', 'sparkpost+smtps' => new \OmniMail\Mail\Transport\Bridge\SparkPost\Transport\SparkPostSmtpTransport($apiKey, $region, $port, $this->dispatcher, $this->logger),
            // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
            default => throw new UnsupportedSchemeException($dsn, 'sparkpost', $this->getSupportedSchemes()),
        };
    }
    protected function getSupportedSchemes(): array
    {
        return ['sparkpost+api', 'sparkpost+smtp', 'sparkpost+smtps'];
    }
}
