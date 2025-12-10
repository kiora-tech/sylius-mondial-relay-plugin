<?php

declare(strict_types=1);

namespace Kiora\SyliusMondialRelayPlugin\Menu;

use Sylius\Bundle\UiBundle\Menu\Event\MenuBuilderEvent;

final class AdminMenuListener
{
    public function addAdminMenuItems(MenuBuilderEvent $event): void
    {
        $menu = $event->getMenu();

        // Add main Mondial Relay menu item
        $mondialRelayMenu = $menu
            ->addChild('mondial_relay')
            ->setLabel('kiora_sylius_mondial_relay.ui.menu.main')
            ->setLabelAttribute('icon', 'shipping fast')
        ;

        // Add Configuration submenu item
        $mondialRelayMenu
            ->addChild('configuration', [
                'route' => 'kiora_sylius_mondial_relay_admin_configuration_index',
            ])
            ->setLabel('kiora_sylius_mondial_relay.ui.menu.configuration')
            ->setLabelAttribute('icon', 'cog')
        ;

        // Add Dashboard submenu item (placeholder for future development)
        $mondialRelayMenu
            ->addChild('dashboard', [
                'route' => 'kiora_sylius_mondial_relay_admin_dashboard_index',
            ])
            ->setLabel('kiora_sylius_mondial_relay.ui.menu.dashboard')
            ->setLabelAttribute('icon', 'chart bar')
        ;

        // Add Shipments submenu item
        $mondialRelayMenu
            ->addChild('shipments', [
                'route' => 'sylius_admin_shipment_index',
                'routeParameters' => [
                    'criteria' => [
                        'method' => [
                            'code' => 'mondial_relay',
                        ],
                    ],
                ],
            ])
            ->setLabel('kiora_sylius_mondial_relay.ui.menu.shipments')
            ->setLabelAttribute('icon', 'shipping')
        ;
    }

    public function addOrderShowMenuItems(MenuBuilderEvent $event): void
    {
        $menu = $event->getMenu();
        $order = $event->getOrder();

        if (null === $order) {
            return;
        }

        // Check if order has Mondial Relay shipment
        foreach ($order->getShipments() as $shipment) {
            $method = $shipment->getMethod();
            if (null === $method) {
                continue;
            }

            // Check if shipping method is Mondial Relay
            // This assumes the shipping gateway code is 'mondial_relay'
            $gateway = $method->getGateway();
            if (null !== $gateway && str_contains(strtolower($gateway->getGatewayName()), 'mondial')) {
                // Add Mondial Relay actions to order show menu
                $menu
                    ->addChild('generate_mondial_relay_label', [
                        'route' => 'kiora_sylius_mondial_relay_admin_shipment_generate_label',
                        'routeParameters' => [
                            'shipmentId' => $shipment->getId(),
                        ],
                    ])
                    ->setLabel('kiora_sylius_mondial_relay.ui.generate_label')
                    ->setLabelAttribute('icon', 'print')
                ;

                $menu
                    ->addChild('generate_mondial_relay_qr_code', [
                        'route' => 'kiora_sylius_mondial_relay_admin_shipment_generate_qr_code',
                        'routeParameters' => [
                            'shipmentId' => $shipment->getId(),
                        ],
                    ])
                    ->setLabel('kiora_sylius_mondial_relay.ui.generate_qr_code')
                    ->setLabelAttribute('icon', 'qrcode')
                    ->setLabelAttribute('class', 'ajax-action')
                ;

                // Only show download label if label exists
                if ($shipment->getTracking() !== null) {
                    $menu
                        ->addChild('download_mondial_relay_label', [
                            'route' => 'kiora_sylius_mondial_relay_admin_shipment_download_label',
                            'routeParameters' => [
                                'shipmentId' => $shipment->getId(),
                            ],
                        ])
                        ->setLabel('kiora_sylius_mondial_relay.ui.download_label')
                        ->setLabelAttribute('icon', 'download')
                    ;
                }

                break; // Only process first Mondial Relay shipment
            }
        }
    }
}
