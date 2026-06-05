<?php

declare (strict_types=1);
namespace OmniMail\Bootstrap;

use OmniMail\Infrastructure\Container\ContainerFactory;
use OmniMailDeps\Psr\Container\ContainerInterface;
/**
 * Boots the compiled Symfony container and runtime lifecycle.
 *
 * @since 0.1.0
 */
final class Kernel
{
    private ?ContainerInterface $container = null;
    public function __construct(private readonly \OmniMail\Bootstrap\Paths $paths, private readonly \OmniMail\Bootstrap\Environment $environment)
    {
    }
    /**
     * Boot Omni Mail for the current request.
     *
     * @since 0.1.0
     */
    public function boot(): void
    {
        if ($this->container instanceof ContainerInterface) {
            return;
        }
        $this->container = (new ContainerFactory($this->paths, $this->environment))->build();
        $this->container->get(\OmniMail\Bootstrap\LifecycleManager::class)->boot();
    }
    /**
     * Run activation lifecycle work.
     *
     * @since 0.1.0
     */
    public function activate(): void
    {
        $this->boot();
        $this->container?->get(\OmniMail\Bootstrap\LifecycleManager::class)->activate();
    }
    /**
     * Run deactivation lifecycle work.
     *
     * @since 0.1.0
     */
    public function deactivate(): void
    {
        $this->boot();
        $this->container?->get(\OmniMail\Bootstrap\LifecycleManager::class)->deactivate();
    }
}
