<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace JooosiMailDeps\Symfony\Component\DependencyInjection\Kernel;

use JooosiMailDeps\Symfony\Component\Config\Definition\Configuration;
use JooosiMailDeps\Symfony\Component\Config\Definition\ConfigurationInterface;
use JooosiMailDeps\Symfony\Component\DependencyInjection\ContainerBuilder;
use JooosiMailDeps\Symfony\Component\DependencyInjection\Extension\ConfigurableExtensionInterface;
use JooosiMailDeps\Symfony\Component\DependencyInjection\Extension\Extension;
use JooosiMailDeps\Symfony\Component\DependencyInjection\Extension\ExtensionTrait;
use JooosiMailDeps\Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use JooosiMailDeps\Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
/**
 * @author Yonel Ceruto <yonelceruto@gmail.com>
 *
 * @internal
 */
class BundleExtension extends Extension implements PrependExtensionInterface
{
    use ExtensionTrait;
    public function __construct(private ConfigurableExtensionInterface $subject, private string $alias)
    {
    }
    public function getConfiguration(array $config, ContainerBuilder $container): ?ConfigurationInterface
    {
        return new Configuration($this->subject, $container, $this->getAlias());
    }
    public function getAlias(): string
    {
        return $this->alias;
    }
    public function prepend(ContainerBuilder $container): void
    {
        $callback = function (ContainerConfigurator $configurator) use ($container) {
            $this->subject->prependExtension($configurator, $container);
        };
        $this->executeConfiguratorCallback($container, $callback, $this->subject, \true);
    }
    public function load(array $configs, ContainerBuilder $container): void
    {
        $config = $this->processConfiguration($this->getConfiguration([], $container), $configs);
        $callback = function (ContainerConfigurator $configurator) use ($config, $container) {
            $this->subject->loadExtension($config, $configurator, $container);
        };
        $this->executeConfiguratorCallback($container, $callback, $this->subject);
    }
}
