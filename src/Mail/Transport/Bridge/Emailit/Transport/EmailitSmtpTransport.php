<?php

declare (strict_types=1);
namespace OmniMail\Mail\Transport\Bridge\Emailit\Transport;

use OmniMailDeps\Psr\EventDispatcher\EventDispatcherInterface;
use OmniMailDeps\Psr\Log\LoggerInterface;
use SensitiveParameter;
use OmniMailDeps\Symfony\Component\Mailer\Transport\Smtp\EsmtpTransport;
/**
 * Emailit SMTP transport.
 *
 * @since 0.1.0
 */
final class EmailitSmtpTransport extends EsmtpTransport
{
    public function __construct(
        #[SensitiveParameter]
        string $smtpCredential,
        ?EventDispatcherInterface $eventDispatcher = null,
        ?LoggerInterface $logger = null
    )
    {
        parent::__construct('smtp.emailit.com', 587, \false, $eventDispatcher, $logger);
        $this->setUsername('emailit');
        $this->setPassword($smtpCredential);
    }
}
