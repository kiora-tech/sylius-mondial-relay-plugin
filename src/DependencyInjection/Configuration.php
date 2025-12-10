<?php

declare(strict_types=1);

namespace Kiora\SyliusMondialRelayPlugin\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * Defines the configuration tree for the Mondial Relay plugin.
 *
 * Configuration structure:
 * kiora_sylius_mondial_relay:
 *     api_key: string (required)
 *     api_secret: string (required)
 *     brand_id: string (required)
 *     sandbox: bool (default: false)
 *     cache:
 *         enabled: bool (default: true)
 *         ttl: int (default: 3600)
 *     http:
 *         timeout: int (default: 10)
 *         max_retries: int (default: 3)
 *     default_country: string (default: 'FR')
 *     default_weight: int (default: 1000) # in grams
 */
final class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('kiora_sylius_mondial_relay');
        $rootNode = $treeBuilder->getRootNode();

        $rootNode
            ->children()
                ->scalarNode('api_key')
                    ->isRequired()
                    ->cannotBeEmpty()
                    ->info('Mondial Relay API key (v2 REST API)')
                    ->example('BDTEST13')
                ->end()
                ->scalarNode('api_secret')
                    ->isRequired()
                    ->cannotBeEmpty()
                    ->info('Mondial Relay API secret for signature generation')
                    ->example('PrivateK')
                ->end()
                ->scalarNode('brand_id')
                    ->isRequired()
                    ->cannotBeEmpty()
                    ->info('Your Mondial Relay brand identifier')
                    ->example('BDTEST13')
                ->end()
                ->booleanNode('sandbox')
                    ->defaultFalse()
                    ->info('Enable sandbox/test mode')
                ->end()
                ->arrayNode('cache')
                    ->addDefaultsIfNotSet()
                    ->info('Cache configuration for API responses')
                    ->children()
                        ->booleanNode('enabled')
                            ->defaultTrue()
                            ->info('Enable caching of pickup point searches')
                        ->end()
                        ->integerNode('ttl')
                            ->defaultValue(3600)
                            ->min(60)
                            ->info('Cache TTL in seconds (default: 1 hour)')
                        ->end()
                    ->end()
                ->end()
                ->arrayNode('http')
                    ->addDefaultsIfNotSet()
                    ->info('HTTP client configuration')
                    ->children()
                        ->integerNode('timeout')
                            ->defaultValue(10)
                            ->min(1)
                            ->max(60)
                            ->info('HTTP request timeout in seconds')
                        ->end()
                        ->integerNode('max_retries')
                            ->defaultValue(3)
                            ->min(0)
                            ->max(10)
                            ->info('Maximum number of retry attempts for failed requests')
                        ->end()
                    ->end()
                ->end()
                ->scalarNode('default_country')
                    ->defaultValue('FR')
                    ->info('Default country code for pickup point searches (ISO 3166-1 alpha-2)')
                    ->validate()
                        ->ifTrue(fn ($value) => !preg_match('/^[A-Z]{2}$/', $value))
                        ->thenInvalid('Country code must be a 2-letter uppercase ISO code')
                    ->end()
                ->end()
                ->integerNode('default_weight')
                    ->defaultValue(1000)
                    ->min(1)
                    ->info('Default package weight in grams if order weight cannot be determined')
                ->end()
            ->end()
        ;

        return $treeBuilder;
    }
}
