<?php

declare (strict_types=1);
namespace OmniMail\Mail\Transport\Bridge\SendLayer\Transport;

use OmniMailDeps\Psr\EventDispatcher\EventDispatcherInterface;
use OmniMailDeps\Psr\Log\LoggerInterface;
use SensitiveParameter;
use OmniMailDeps\Symfony\Component\Mailer\Transport\Smtp\EsmtpTransport;
final class SendLayerSmtpTransport extends EsmtpTransport
{
    public function __construct(
        string $username,
        #[SensitiveParameter]
        string $password,
        ?EventDispatcherInterface $eventDispatcher = null,
        ?LoggerInterface $logger = null
    )
    {
        parent::__construct('smtp.sendlayer.net', 587, \false, $eventDispatcher, $logger);
        $this->setUsername($username);
        $this->setPassword($password);
    }
}
