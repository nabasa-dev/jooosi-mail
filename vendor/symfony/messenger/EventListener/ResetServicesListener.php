<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace JooosiMailDeps\Symfony\Component\Messenger\EventListener;

use JooosiMailDeps\Symfony\Component\DependencyInjection\Attribute\Autowire;
use JooosiMailDeps\Symfony\Component\DependencyInjection\ServicesResetterInterface;
use JooosiMailDeps\Symfony\Component\EventDispatcher\EventSubscriberInterface;
use JooosiMailDeps\Symfony\Component\Messenger\Event\WorkerRunningEvent;
use JooosiMailDeps\Symfony\Component\Messenger\Event\WorkerStoppedEvent;
use JooosiMailDeps\Symfony\Contracts\Service\ResetInterface;
/**
 * @author Grégoire Pineau <lyrixx@lyrixx.info>
 */
class ResetServicesListener implements EventSubscriberInterface
{
    private int $interval = 1;
    private int $count = 0;
    public function __construct(
        #[Autowire(service: ServicesResetterInterface::class)]
        private ResetInterface $servicesResetter
    )
    {
    }
    public function setInterval(int $interval): void
    {
        $this->interval = $interval;
    }
    public function resetServices(WorkerRunningEvent $event): void
    {
        if (!$event->isWorkerIdle() && 0 === ++$this->count % $this->interval) {
            $this->servicesResetter->reset();
        }
    }
    public function resetServicesAtStop(WorkerStoppedEvent $event): void
    {
        $this->servicesResetter->reset();
    }
    public static function getSubscribedEvents(): array
    {
        return [WorkerRunningEvent::class => ['resetServices', -1024], WorkerStoppedEvent::class => ['resetServicesAtStop', -1024]];
    }
}
