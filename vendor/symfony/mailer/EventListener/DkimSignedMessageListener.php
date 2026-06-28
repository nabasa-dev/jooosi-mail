<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace JooosiMailDeps\Symfony\Component\Mailer\EventListener;

use JooosiMailDeps\Symfony\Component\EventDispatcher\EventSubscriberInterface;
use JooosiMailDeps\Symfony\Component\Mailer\Event\MessageEvent;
use JooosiMailDeps\Symfony\Component\Mime\Crypto\DkimSigner;
use JooosiMailDeps\Symfony\Component\Mime\Message;
/**
 * Signs messages using DKIM.
 *
 * @author Elías Fernández
 */
class DkimSignedMessageListener implements EventSubscriberInterface
{
    public function __construct(private DkimSigner $signer)
    {
    }
    public function onMessage(MessageEvent $event): void
    {
        $message = $event->getMessage();
        if (!$message instanceof Message) {
            return;
        }
        $event->setMessage($this->signer->sign($message));
    }
    public static function getSubscribedEvents(): array
    {
        return [MessageEvent::class => ['onMessage', -128]];
    }
}
