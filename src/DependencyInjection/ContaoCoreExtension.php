<?php

/**
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2015 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\Yaml\Yaml;

/**
 * Adds the bundle services to the container.
 *
 * @author Leo Feyer <https://github.com/leofeyer>
 * @author Yanick Witschi <https://github.com/toflar>
 */
class ContaoCoreExtension extends Extension implements PrependExtensionInterface
{
    /**
     * @var array
     */
    private $configFiles = [
        'doctrine.yml'
    ];

    /**
     * {@inheritdoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $loader = new YamlFileLoader(
            $container,
            new FileLocator(__DIR__ . '/../Resources/config')
        );

        $loader->load('services.yml');
    }

    /**
     * {@inheritdoc}
     */
    public function prepend(ContainerBuilder $container)
    {
        foreach ($this->configFiles as $file) {
            $parsedConfig = Yaml::parse(
                file_get_contents(
                    __DIR__ . '/../Resources/config/' . $file
                )
            );

            $this->prependConfig($parsedConfig, $container, $file);
        }
    }

    /**
     * Adds the configuration to the container.
     *
     * @param mixed            $parsedConfig The parsed configuration
     * @param ContainerBuilder $container    The container object
     * @param string           $file         The file name
     */
    private function prependConfig($parsedConfig, ContainerBuilder $container, $file)
    {
        if (!is_array($parsedConfig)) {
            throw new \LogicException("Error parsing $file");
        }

        foreach ($parsedConfig as $bundleName => $bundleConfig) {
            $container->prependExtensionConfig($bundleName, $bundleConfig);
        }
    }
}