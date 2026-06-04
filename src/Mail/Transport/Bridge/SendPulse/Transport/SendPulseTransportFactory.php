<?php

declare(strict_types=1);

namespace OmniMail\Mail\Transport\Bridge\SendPulse\Transport;

use OmniMail\Discovery\Attribute\Service;
use OmniMail\Discovery\Attribute\TransportFactory;
use Symfony\Component\Mailer\Exception\UnsupportedSchemeException;
use Symfony\Component\Mailer\Transport\AbstractTransportFactory;
use Symfony\Component\Mailer\Transport\Dsn;
use Symfony\Component\Mailer\Transport\TransportInterface;

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
            'sendpulse+api' => (new SendPulseApiTransport($user, $password, $this->client, $this->dispatcher, $this->logger))->setHost($host)->setPort($port),
            'sendpulse+smtp', 'sendpulse+smtps' => new SendPulseSmtpTransport($user, $password, $port, $this->dispatcher, $this->logger),
            default => throw new UnsupportedSchemeException($dsn, 'sendpulse', $this->getSupportedSchemes()),
        };
    }

    protected function getSupportedSchemes(): array
    {
        return ['sendpulse+api', 'sendpulse+smtp', 'sendpulse+smtps'];
    }
}
