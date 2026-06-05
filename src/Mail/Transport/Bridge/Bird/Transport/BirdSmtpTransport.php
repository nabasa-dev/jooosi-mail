<?php

declare (strict_types=1);
namespace OmniMail\Mail\Transport\Bridge\Bird\Transport;

use OmniMailDeps\Psr\EventDispatcher\EventDispatcherInterface;
use OmniMailDeps\Psr\Log\LoggerInterface;
use SensitiveParameter;
use OmniMailDeps\Symfony\Component\Mailer\Transport\Smtp\EsmtpTransport;
/**
 * Bird SMTP transport.
 *
 * @link https://docs.bird.com/api/email-api/smtp-api
 *
 * @since 0.1.0
 */
final class BirdSmtpTransport extends EsmtpTransport
{
    public function __construct(
        #[SensitiveParameter]
        string $accessKey,
        string $workspaceId,
        ?string $region = null,
        ?int $port = null,
        ?EventDispatcherInterface $eventDispatcher = null,
        ?LoggerInterface $logger = null
    )
    {
        $host = $region === 'us' ? 'smtp.email.us-west-2.api.bird.com' : 'smtp.email.eu-west-1.api.bird.com';
        $port ??= 587;
        parent::__construct($host, $port, \false, $eventDispatcher, $logger);
        $this->setUsername('SMTP_Injection:x-bird-workspace-id=' . $workspaceId);
        $this->setPassword('AccessKey ' . $accessKey);
    }
}
