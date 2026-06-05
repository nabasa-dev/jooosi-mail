<?php

declare (strict_types=1);
namespace OmniMail\Mail\Transport\Bridge\SendPulse\Transport;

use OmniMailDeps\Psr\EventDispatcher\EventDispatcherInterface;
use OmniMailDeps\Psr\Log\LoggerInterface;
use SensitiveParameter;
use OmniMailDeps\Symfony\Component\Mailer\Transport\Smtp\EsmtpTransport;
final class SendPulseSmtpTransport extends EsmtpTransport
{
    public function __construct(
        string $username,
        #[SensitiveParameter]
        string $password,
        ?int $port = null,
        ?EventDispatcherInterface $eventDispatcher = null,
        ?LoggerInterface $logger = null
    )
    {
        $port ??= 587;
        parent::__construct('smtp-pulse.com', $port, $port === 465, $eventDispatcher, $logger);
        $this->setUsername($username);
        $this->setPassword($password);
    }
}
