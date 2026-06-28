<?php

declare(strict_types=1);

namespace JooosiMail\Discovery\Runtime;

use Override;
use Psr\Container\ContainerInterface;
use ReflectionClass;
use ReflectionNamedType;
use RuntimeException;

/**
 * Minimal PSR container used while Tempest discovery instantiates discoveries.
 *
 * @since 0.1.0
 */
final class NullDiscoveryContainer implements ContainerInterface
{
    /**
     * @since 0.1.0
     */
    #[Override]
    public function get(string $id): object
    {
        if (! $this->has($id)) {
            // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
            throw new RuntimeException(sprintf('Discovery container cannot resolve "%s".', $id));
        }

        $reflectionClass = new ReflectionClass($id);

        if (! $reflectionClass->isInstantiable()) {
            // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
            throw new RuntimeException(sprintf('Discovery container cannot instantiate "%s".', $id));
        }

        $constructor = $reflectionClass->getConstructor();

        if ($constructor === null || $constructor->getNumberOfRequiredParameters() === 0) {
            return $reflectionClass->newInstance();
        }

        $arguments = [];

        foreach ($constructor->getParameters() as $parameter) {
            $type = $parameter->getType();

            if (! $type instanceof ReflectionNamedType || $type->isBuiltin()) {
                if ($parameter->isDefaultValueAvailable()) {
                    $arguments[] = $parameter->getDefaultValue();
                    continue;
                }

                // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
                throw new RuntimeException(sprintf(
                    'Discovery container cannot resolve parameter "$%s" for "%s".',
                    $parameter->getName(),
                    $id,
                ));
            }

            $arguments[] = $this->get($type->getName());
        }

        return $reflectionClass->newInstanceArgs($arguments);
    }

    /**
     * @since 0.1.0
     */
    #[Override]
    public function has(string $id): bool
    {
        return class_exists($id);
    }
}
