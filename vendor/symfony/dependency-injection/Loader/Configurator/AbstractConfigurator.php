<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace OmniMailDeps\Symfony\Component\DependencyInjection\Loader\Configurator;

use OmniMailDeps\Symfony\Component\Config\Loader\ParamConfigurator;
use OmniMailDeps\Symfony\Component\DependencyInjection\Alias;
use OmniMailDeps\Symfony\Component\DependencyInjection\Argument\AbstractArgument;
use OmniMailDeps\Symfony\Component\DependencyInjection\Argument\ArgumentInterface;
use OmniMailDeps\Symfony\Component\DependencyInjection\Argument\ServiceClosureArgument;
use OmniMailDeps\Symfony\Component\DependencyInjection\Definition;
use OmniMailDeps\Symfony\Component\DependencyInjection\Exception\InvalidArgumentException;
use OmniMailDeps\Symfony\Component\DependencyInjection\Parameter;
use OmniMailDeps\Symfony\Component\DependencyInjection\Reference;
use OmniMailDeps\Symfony\Component\ExpressionLanguage\Expression;
abstract class AbstractConfigurator
{
    public const FACTORY = 'unknown';
    /**
     * @var \Closure(mixed, bool):mixed|null
     */
    public static ?\Closure $valuePreProcessor = null;
    /** @internal */
    protected Definition|Alias|null $definition = null;
    public function __call(string $method, array $args): mixed
    {
        if (method_exists($this, 'set' . $method)) {
            return $this->{'set' . $method}(...$args);
        }
        throw new \BadMethodCallException(\sprintf('Call to undefined method "%s::%s()".', static::class, $method));
    }
    public function __serialize(): array
    {
        throw new \BadMethodCallException('Cannot serialize ' . __CLASS__);
    }
    public function __unserialize(array $data): void
    {
        throw new \BadMethodCallException('Cannot unserialize ' . __CLASS__);
    }
    /**
     * Checks that a value is valid, optionally replacing Definition and Reference configurators by their configure value.
     *
     * @param bool $allowServices whether Definition and Reference are allowed; by default, only scalars, arrays and enum are
     *
     * @return mixed the value, optionally cast to a Definition/Reference
     */
    public static function processValue(mixed $value, bool $allowServices = \false): mixed
    {
        if (\is_array($value)) {
            foreach ($value as $k => $v) {
                $value[$k] = static::processValue($v, $allowServices);
            }
            return self::$valuePreProcessor ? (self::$valuePreProcessor)($value, $allowServices) : $value;
        }
        if (self::$valuePreProcessor) {
            $value = (self::$valuePreProcessor)($value, $allowServices);
        }
        if ($value instanceof ParamConfigurator) {
            return (string) $value;
        }
        if (\is_scalar($value ?? '') || $value instanceof \UnitEnum) {
            return $value;
        }
        if ($value instanceof \Closure) {
            return self::processClosure($value);
        }
        if (!$allowServices) {
            throw new InvalidArgumentException(\sprintf('Cannot use values of type "%s" in service configuration files.', get_debug_type($value)));
        }
        if ($value instanceof ReferenceConfigurator) {
            $reference = new Reference($value->id, $value->invalidBehavior);
            return $value instanceof ClosureReferenceConfigurator ? new ServiceClosureArgument($reference) : $reference;
        }
        if ($value instanceof InlineServiceConfigurator) {
            $def = $value->definition;
            $value->definition = null;
            return $def;
        }
        if ($value instanceof self) {
            throw new InvalidArgumentException(\sprintf('"%s()" can be used only at the root of service configuration files.', $value::FACTORY));
        }
        switch (\true) {
            case $value instanceof ArgumentInterface:
            case $value instanceof Definition:
            case $value instanceof Expression:
            case $value instanceof Parameter:
            case $value instanceof AbstractArgument:
            case $value instanceof Reference:
                return $value;
        }
        throw new InvalidArgumentException(\sprintf('Cannot use values of type "%s" in service configuration files.', get_debug_type($value)));
    }
    /**
     * Converts a named closure to dumpable callable.
     *
     * @throws InvalidArgumentException if the closure is anonymous or references a non-static method
     */
    private static function processClosure(\Closure $closure): callable
    {
        $function = new \ReflectionFunction($closure);
        if ($function->isAnonymous()) {
            throw new InvalidArgumentException('Anonymous closure not supported. The closure must be created from a static method or a global function.');
        }
        // Convert global_function(...) closure into 'global_function'
        if (!$class = $function->getClosureCalledClass()) {
            return $function->name;
        }
        // Convert Class::method(...) closure into ['Class', 'method']
        if ($function->isStatic()) {
            return [$class->name, $function->name];
        }
        throw new InvalidArgumentException(\sprintf('The method "%s::%s(...)" is not static.', $class->name, $function->name));
    }
}
