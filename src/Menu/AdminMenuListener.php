<?php

declare(strict_types=1);

namespace Kiora\SyliusMondialRelayPlugin\Menu;

use Sylius\Bundle\UiBundle\Menu\Event\MenuBuilderEvent;

/**
 * Adds Mondial Relay configuration to the Sylius admin menu.
 *
 * The configuration page allows administrators to set up API credentials
 * directly from the admin interface instead of using environment variables.
 */
final class AdminMenuListener
{
    public function addAdminMenuItems(MenuBuilderEvent $event): void
    {
        $menu = $event->getMenu();

        // Add Mondial Relay configuration under the existing Configuration menu
        $configurationMenu = $menu->getChild('configuration');

        if (null !== $configurationMenu) {
            $configurationMenu
                ->addChild('mondial_relay', [
                    'route' => 'kiora_sylius_mondial_relay_admin_configuration_index',
                ])
                ->setLabel('kiora_sylius_mondial_relay.ui.menu.mondial_relay')
                ->setLabelAttribute('icon', 'shipping fast')
            ;
        }
    }
}
