<?php

declare(strict_types=1);

namespace OmniMail\Mail\Transport\Bridge\SendLayer\Transport;

use OmniMail\Discovery\Attribute\Service;
use OmniMail\Discovery\Attribute\TransportFactory;
use Symfony\Component\Mailer\Exception\UnsupportedSchemeException;
use Symfony\Component\Mailer\Transport\AbstractTransportFactory;
use Symfony\Component\Mailer\Transport\Dsn;
use Symfony\Component\Mailer\Transport\TransportInterface;

#[Service]
#[TransportFactory]
final class SendLayerTransportFactory extends AbstractTransportFactory
{
    public function create(Dsn $dsn): TransportInterface
    {
        $scheme = $dsn->getScheme();
        $user = $this->getUser($dsn);
        $host = $dsn->getHost() === 'default' ? null : $dsn->getHost();
        $port = $dsn->getPort();

        return match ($scheme) {
            'sendlayer+api' => (new SendLayerApiTransport($user, $this->client, $this->dispatcher, $this->logger))->setHost($host)->setPort($port),
            'sendlayer+smtp', 'sendlayer+smtps' => new SendLayerSmtpTransport($this->getUser($dsn), $this->getPassword($dsn), $this->dispatcher, $this->logger),
            default => 
                // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
                throw new UnsupportedSchemeException($dsn, 'sendlayer', $this->getSupportedSchemes()),
        };
    }

    protected function getSupportedSchemes(): array
    {
        return ['sendlayer+api', 'sendlayer+smtp', 'sendlayer+smtps'];
    }
}
