<?php

declare (strict_types=1);
namespace OmniMail\Mail\Transport\Bridge\SendPulse\Transport;

use OmniMail\Discovery\Attribute\Service;
use OmniMail\Discovery\Attribute\TransportFactory;
use OmniMailDeps\Symfony\Component\Mailer\Exception\UnsupportedSchemeException;
use OmniMailDeps\Symfony\Component\Mailer\Transport\AbstractTransportFactory;
use OmniMailDeps\Symfony\Component\Mailer\Transport\Dsn;
use OmniMailDeps\Symfony\Component\Mailer\Transport\TransportInterface;
#[Service]
#[TransportFactory]
final class SendPulseTransportFactory extends AbstractTransportFactory
{
    public function create(Dsn $dsn): TransportInterface
    {
        $scheme = $dsn->getScheme();
        $user = $this->getUser($dsn);
        $password = $this->getPassword($dsn);
        $host = $dsn->getHost() === 'default' ? null : $dsn->getHost();
        $port = $dsn->getPort();
        return match ($scheme) {
            'sendpulse+api' => (new \OmniMail\Mail\Transport\Bridge\SendPulse\Transport\SendPulseApiTransport($user, $password, $this->client, $this->dispatcher, $this->logger))->setHost($host)->setPort($port),
            'sendpulse+smtp', 'sendpulse+smtps' => new \OmniMail\Mail\Transport\Bridge\SendPulse\Transport\SendPulseSmtpTransport($user, $password, $port, $this->dispatcher, $this->logger),
            // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
            default => throw new UnsupportedSchemeException($dsn, 'sendpulse', $this->getSupportedSchemes()),
        };
    }
    protected function getSupportedSchemes(): array
    {
        return ['sendpulse+api', 'sendpulse+smtp', 'sendpulse+smtps'];
    }
}
