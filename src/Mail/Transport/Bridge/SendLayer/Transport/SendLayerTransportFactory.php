<?php

declare (strict_types=1);
namespace OmniMail\Mail\Transport\Bridge\SendLayer\Transport;

use OmniMail\Discovery\Attribute\Service;
use OmniMail\Discovery\Attribute\TransportFactory;
use OmniMailDeps\Symfony\Component\Mailer\Exception\UnsupportedSchemeException;
use OmniMailDeps\Symfony\Component\Mailer\Transport\AbstractTransportFactory;
use OmniMailDeps\Symfony\Component\Mailer\Transport\Dsn;
use OmniMailDeps\Symfony\Component\Mailer\Transport\TransportInterface;
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
            'sendlayer+api' => (new \OmniMail\Mail\Transport\Bridge\SendLayer\Transport\SendLayerApiTransport($user, $this->client, $this->dispatcher, $this->logger))->setHost($host)->setPort($port),
            'sendlayer+smtp', 'sendlayer+smtps' => new \OmniMail\Mail\Transport\Bridge\SendLayer\Transport\SendLayerSmtpTransport($this->getUser($dsn), $this->getPassword($dsn), $this->dispatcher, $this->logger),
            default => throw new UnsupportedSchemeException($dsn, 'sendlayer', $this->getSupportedSchemes()),
        };
    }
    protected function getSupportedSchemes(): array
    {
        return ['sendlayer+api', 'sendlayer+smtp', 'sendlayer+smtps'];
    }
}
