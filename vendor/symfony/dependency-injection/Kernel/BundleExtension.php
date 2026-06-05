<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace OmniMailDeps\Symfony\Component\DependencyInjection\Kernel;

use OmniMailDeps\Symfony\Component\Config\Definition\Configuration;
use OmniMailDeps\Symfony\Component\Config\Definition\ConfigurationInterface;
use OmniMailDeps\Symfony\Component\DependencyInjection\ContainerBuilder;
use OmniMailDeps\Symfony\Component\DependencyInjection\Extension\ConfigurableExtensionInterface;
use OmniMailDeps\Symfony\Component\DependencyInjection\Extension\Extension;
use OmniMailDeps\Symfony\Component\DependencyInjection\Extension\ExtensionTrait;
use OmniMailDeps\Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use OmniMailDeps\Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
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
