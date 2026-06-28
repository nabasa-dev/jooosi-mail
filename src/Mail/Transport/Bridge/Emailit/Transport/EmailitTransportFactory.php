<?php

declare(strict_types=1);

namespace JooosiMail\Mail\Transport\Bridge\Emailit\Transport;

use JooosiMail\Discovery\Attribute\Service;
use JooosiMail\Discovery\Attribute\TransportFactory;
use Symfony\Component\Mailer\Exception\UnsupportedSchemeException;
use Symfony\Component\Mailer\Transport\AbstractTransportFactory;
use Symfony\Component\Mailer\Transport\Dsn;
use Symfony\Component\Mailer\Transport\TransportInterface;

#[Service]
#[TransportFactory]
final class EmailitTransportFactory extends AbstractTransportFactory
{
    public function create(Dsn $dsn): TransportInterface
    {
        $scheme = $dsn->getScheme();
        $apiKey = $this->getUser($dsn);
        $host = $dsn->getHost() === 'default' ? null : $dsn->getHost();
        $port = $dsn->getPort();

        return match ($scheme) {
            'emailit+api' => (new EmailitApiTransport($apiKey, $this->client, $this->dispatcher, $this->logger))->setHost($host)->setPort($port),
            'emailit+smtp', 'emailit+smtps' => new EmailitSmtpTransport($this->getPassword($dsn), $this->dispatcher, $this->logger),
            // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
            default => 
                throw new UnsupportedSchemeException($dsn, 'emailit', $this->getSupportedSchemes()),
        };
    }

    protected function getSupportedSchemes(): array
    {
        return ['emailit+api', 'emailit+smtp', 'emailit+smtps'];
    }
}
