<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace OmniMailDeps\Symfony\Component\Messenger\EventListener;

use OmniMailDeps\Symfony\Component\EventDispatcher\EventSubscriberInterface;
use OmniMailDeps\Symfony\Component\Messenger\Event\WorkerMessageFailedEvent;
use OmniMailDeps\Symfony\Component\Messenger\Event\WorkerRunningEvent;
use OmniMailDeps\Symfony\Component\Messenger\Exception\HandlerFailedException;
use OmniMailDeps\Symfony\Component\Messenger\Exception\StopWorkerExceptionInterface;
/**
 * @author Grégoire Pineau <lyrixx@lyrixx.info>
 */
class StopWorkerOnCustomStopExceptionListener implements EventSubscriberInterface
{
    private bool $stop = \false;
    public function onMessageFailed(WorkerMessageFailedEvent $event): void
    {
        $th = $event->getThrowable();
        if ($th instanceof StopWorkerExceptionInterface) {
            $this->stop = \true;
        }
        if ($th instanceof HandlerFailedException) {
            foreach ($th->getWrappedExceptions() as $e) {
                if ($e instanceof StopWorkerExceptionInterface) {
                    $this->stop = \true;
                    break;
                }
            }
        }
    }
    public function onWorkerRunning(WorkerRunningEvent $event): void
    {
        if ($this->stop) {
            $event->getWorker()->stop();
        }
    }
    public static function getSubscribedEvents(): array
    {
        return [WorkerMessageFailedEvent::class => 'onMessageFailed', WorkerRunningEvent::class => 'onWorkerRunning'];
    }
}
