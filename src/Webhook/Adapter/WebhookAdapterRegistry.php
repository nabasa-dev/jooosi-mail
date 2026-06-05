<?php

declare (strict_types=1);
namespace OmniMail\Webhook\Adapter;

use OmniMail\Discovery\Attribute\Service;
use OmniMail\Discovery\Runtime\DiscoveryManifest;
use OmniMail\Mail\Connection\Connection;
use OmniMailDeps\Psr\Container\ContainerInterface;
use ReflectionClass;
use RuntimeException;
/**
 * Resolves the best webhook adapter for a connection.
 *
 * @since 0.1.0
 */
#[Service]
final class WebhookAdapterRegistry
{
    /** @var list<WebhookAdapterInterface>|null */
    private ?array $adapters = null;
    public function __construct(private readonly DiscoveryManifest $manifest, private readonly ContainerInterface $container)
    {
    }
    /**
     * @since 0.1.0
     */
    public function resolve(Connection $connection): \OmniMail\Webhook\Adapter\WebhookAdapterInterface
    {
        return array_find($this->all(), static fn(\OmniMail\Webhook\Adapter\WebhookAdapterInterface $adapter): bool => $adapter->supports($connection)) ?? throw new RuntimeException(sprintf('No webhook adapter matched connection profile "%s".', $connection->profileKey));
    }
    /**
     * @return list<WebhookAdapterInterface>
     *
     * @since 0.1.0
     */
    private function all(): array
    {
        if (is_array($this->adapters)) {
            return $this->adapters;
        }
        $adapters = [];
        foreach ($this->manifest->services as $className) {
            if (!is_subclass_of($className, \OmniMail\Webhook\Adapter\WebhookAdapterInterface::class)) {
                continue;
            }
            $reflectionClass = new ReflectionClass($className);
            if ($reflectionClass->isAbstract()) {
                continue;
            }
            $service = $this->container->get($className);
            if ($service instanceof \OmniMail\Webhook\Adapter\WebhookAdapterInterface) {
                $adapters[] = $service;
            }
        }
        usort($adapters, static fn(\OmniMail\Webhook\Adapter\WebhookAdapterInterface $left, \OmniMail\Webhook\Adapter\WebhookAdapterInterface $right): int => $right->getPriority() <=> $left->getPriority());
        $this->adapters = $adapters;
        return $this->adapters;
    }
}
