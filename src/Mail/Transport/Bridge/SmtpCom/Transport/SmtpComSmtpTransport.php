<?php

declare(strict_types=1);

namespace OmniMail\Mail\Transport\Bridge\SmtpCom\Transport;

use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Log\LoggerInterface;
use SensitiveParameter;
use Symfony\Component\Mailer\Transport\Smtp\EsmtpTransport;

final class SmtpComSmtpTransport extends EsmtpTransport
{
    public function __construct(
        string $username,
        #[SensitiveParameter] string $password,
        ?int $port = null,
        ?EventDispatcherInterface $eventDispatcher = null,
        ?LoggerInterface $logger = null,
    ) {
        $port ??= 2525;
        parent::__construct('send.smtp.com', $port, $port === 465, $eventDispatcher, $logger);
        $this->setUsername($username);
        $this->setPassword($password);
    }
}
