<?php

declare (strict_types=1);
namespace OmniMail\Mail\Transport\Bridge\SmtpCom\Transport;

use OmniMail\Discovery\Attribute\Service;
use OmniMail\Discovery\Attribute\TransportFactory;
use OmniMailDeps\Symfony\Component\Mailer\Exception\UnsupportedSchemeException;
use OmniMailDeps\Symfony\Component\Mailer\Transport\AbstractTransportFactory;
use OmniMailDeps\Symfony\Component\Mailer\Transport\Dsn;
use OmniMailDeps\Symfony\Component\Mailer\Transport\TransportInterface;
#[Service]
#[TransportFactory]
final class SmtpComTransportFactory extends AbstractTransportFactory
{
    public function create(Dsn $dsn): TransportInterface
    {
        $scheme = $dsn->getScheme();
        $apiKey = $this->getUser($dsn);
        $host = $dsn->getHost() === 'default' ? null : $dsn->getHost();
        $port = $dsn->getPort();
        return match ($scheme) {
            'smtpcom+api' => (new \OmniMail\Mail\Transport\Bridge\SmtpCom\Transport\SmtpComApiTransport($apiKey, $dsn->getOption('channel'), $this->client, $this->dispatcher, $this->logger))->setHost($host)->setPort($port),
            'smtpcom+smtp', 'smtpcom+smtps' => new \OmniMail\Mail\Transport\Bridge\SmtpCom\Transport\SmtpComSmtpTransport($this->getUser($dsn), $this->getPassword($dsn), $port, $this->dispatcher, $this->logger),
            default => throw new UnsupportedSchemeException($dsn, 'smtpcom', $this->getSupportedSchemes()),
        };
    }
    protected function getSupportedSchemes(): array
    {
        return ['smtpcom+api', 'smtpcom+smtp', 'smtpcom+smtps'];
    }
}
