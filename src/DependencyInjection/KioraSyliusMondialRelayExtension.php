<?php

declare(strict_types=1);

namespace Kiora\SyliusMondialRelayPlugin\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;

/**
 * Loads and manages the plugin configuration.
 *
 * This extension:
 * - Loads service definitions from config/services.yaml
 * - Processes configuration from Configuration class
 * - Sets up Mondial Relay API parameters
 * - Configures caching and HTTP client settings
 * - Prepends Twig Hooks configuration for admin pages
 */
final class KioraSyliusMondialRelayExtension extends Extension implements PrependExtensionInterface
{
    /**
     * Prepend Twig Hooks configuration for Sylius admin pages.
     */
    public function prepend(ContainerBuilder $container): void
    {
        $loader = new YamlFileLoader($container, new FileLocator(__DIR__ . '/../../config'));
        $loader->load('twig_hooks.yaml');
    }

    /**
     * @param array<string, mixed> $configs
     */
    public function load(array $configs, ContainerBuilder $container): void
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        // Set configuration parameters for service injection
        $container->setParameter('kiora_sylius_mondial_relay.api_key', $config['api_key']);
        $container->setParameter('kiora_sylius_mondial_relay.api_secret', $config['api_secret']);
        $container->setParameter('kiora_sylius_mondial_relay.brand_id', $config['brand_id']);
        $container->setParameter('kiora_sylius_mondial_relay.sandbox', $config['sandbox']);
        $container->setParameter('kiora_sylius_mondial_relay.cache_ttl', $config['cache']['ttl']);
        $container->setParameter('kiora_sylius_mondial_relay.cache_enabled', $config['cache']['enabled']);
        $container->setParameter('kiora_sylius_mondial_relay.http_timeout', $config['http']['timeout']);
        $container->setParameter('kiora_sylius_mondial_relay.http_max_retries', $config['http']['max_retries']);
        $container->setParameter('kiora_sylius_mondial_relay.default_country', $config['default_country']);
        $container->setParameter('kiora_sylius_mondial_relay.default_weight', $config['default_weight']);

        // Load service definitions
        $loader = new YamlFileLoader($container, new FileLocator(__DIR__ . '/../../config'));
        $loader->load('services.yaml');
    }

    public function getAlias(): string
    {
        return 'kiora_sylius_mondial_relay';
    }
}
