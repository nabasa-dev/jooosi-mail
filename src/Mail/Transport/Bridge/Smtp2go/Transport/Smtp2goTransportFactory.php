<?php

declare (strict_types=1);
namespace OmniMail\Mail\Transport\Bridge\Smtp2go\Transport;

use OmniMail\Discovery\Attribute\Service;
use OmniMail\Discovery\Attribute\TransportFactory;
use OmniMailDeps\Symfony\Component\Mailer\Exception\UnsupportedSchemeException;
use OmniMailDeps\Symfony\Component\Mailer\Transport\AbstractTransportFactory;
use OmniMailDeps\Symfony\Component\Mailer\Transport\Dsn;
use OmniMailDeps\Symfony\Component\Mailer\Transport\TransportInterface;
#[Service]
#[TransportFactory]
final class Smtp2goTransportFactory extends AbstractTransportFactory
{
    public function create(Dsn $dsn): TransportInterface
    {
        $scheme = $dsn->getScheme();
        $user = $this->getUser($dsn);
        $port = $dsn->getPort();
        $region = $dsn->getOption('region');
        return match ($scheme) {
            'smtp2go+api' => new \OmniMail\Mail\Transport\Bridge\Smtp2go\Transport\Smtp2goApiTransport($user, $region, $this->client, $this->dispatcher, $this->logger),
            'smtp2go+smtp', 'smtp2go+smtps' => new \OmniMail\Mail\Transport\Bridge\Smtp2go\Transport\Smtp2goSmtpTransport($user, $this->getPassword($dsn), $region, $port, $this->dispatcher, $this->logger),
            // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
            default => throw new UnsupportedSchemeException($dsn, 'smtp2go', $this->getSupportedSchemes()),
        };
    }
    protected function getSupportedSchemes(): array
    {
        return ['smtp2go+api', 'smtp2go+smtp', 'smtp2go+smtps'];
    }
}
