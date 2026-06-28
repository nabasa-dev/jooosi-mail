<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace JooosiMailDeps\Symfony\Component\Mailer\Bridge\Mailtrap\Transport;

use JooosiMailDeps\Psr\EventDispatcher\EventDispatcherInterface;
use JooosiMailDeps\Psr\Log\LoggerInterface;
use JooosiMailDeps\Symfony\Component\Mailer\Envelope;
use JooosiMailDeps\Symfony\Component\Mailer\Exception\TransportException;
use JooosiMailDeps\Symfony\Component\Mailer\Header\MetadataHeader;
use JooosiMailDeps\Symfony\Component\Mailer\Header\TagHeader;
use JooosiMailDeps\Symfony\Component\Mailer\SentMessage;
use JooosiMailDeps\Symfony\Component\Mailer\Transport\Smtp\EsmtpTransport;
use JooosiMailDeps\Symfony\Component\Mime\Message;
use JooosiMailDeps\Symfony\Component\Mime\RawMessage;
/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class MailtrapSmtpTransport extends EsmtpTransport
{
    public function __construct(
        #[\SensitiveParameter]
        string $password,
        ?EventDispatcherInterface $dispatcher = null,
        ?LoggerInterface $logger = null
    )
    {
        parent::__construct('live.smtp.mailtrap.io', 587, \false, $dispatcher, $logger);
        $this->setUsername('api');
        $this->setPassword($password);
    }
    public function send(RawMessage $message, ?Envelope $envelope = null): ?SentMessage
    {
        if ($message instanceof Message) {
            $message = clone $message;
            $this->addMailtrapHeaders($message);
        }
        return parent::send($message, $envelope);
    }
    private function addMailtrapHeaders(Message $message): void
    {
        $headers = $message->getHeaders();
        $customVariables = [];
        foreach ($headers->all() as $name => $header) {
            if ($header instanceof TagHeader) {
                if ($headers->has('X-MT-Category')) {
                    throw new TransportException('Mailtrap only allows a single category per email.');
                }
                $headers->addTextHeader('X-MT-Category', $header->getValue());
                $headers->remove($name);
            }
            if ($header instanceof MetadataHeader) {
                $customVariables[$header->getKey()] = $header->getValue();
                $headers->remove($name);
            }
        }
        if ($customVariables) {
            $headers->addTextHeader('X-MT-Custom-Variables', json_encode($customVariables));
        }
    }
}
