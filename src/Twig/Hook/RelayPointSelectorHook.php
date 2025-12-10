<?php

declare(strict_types=1);

namespace Kiora\SyliusMondialRelayPlugin\Twig\Hook;

use Sylius\Component\Core\Model\OrderInterface;
use Sylius\Component\Core\Model\ShipmentInterface;
use Sylius\Component\Shipping\Model\ShippingMethodInterface;
use Sylius\TwigHooks\Hook\HookInterface;
use Sylius\TwigHooks\Hook\TemplateHook;

/**
 * Twig Hook to inject Mondial Relay point selector widget in checkout.
 *
 * This hook is triggered after the shipping method selection in Sylius 2 checkout.
 * It displays the relay point selector only when a Mondial Relay shipping method is selected.
 *
 * Configuration in services.yaml:
 * ```yaml
 * Kiora\SyliusMondialRelayPlugin\Twig\Hook\RelayPointSelectorHook:
 *     tags:
 *         - { name: 'sylius.twig_hooks.hook', hook: 'sylius_shop.checkout.complete.shipments.form' }
 * ```
 */
final class RelayPointSelectorHook
{
    private const MONDIAL_RELAY_SHIPPING_CODE_PREFIX = 'mondial_relay_';

    /**
     * Render the relay point selector widget if conditions are met.
     *
     * @param array{order?: OrderInterface, shipment?: ShipmentInterface} $context
     */
    public function __invoke(array $context = []): string
    {
        // Extract shipment from context
        $shipment = $context['shipment'] ?? null;

        if (!$shipment instanceof ShipmentInterface) {
            return '';
        }

        // Check if shipping method is Mondial Relay
        if (!$this->isMondialRelayShipment($shipment)) {
            return '';
        }

        // Get shipping address for initial search
        $order = $shipment->getOrder();
        if (!$order instanceof OrderInterface) {
            return '';
        }

        $shippingAddress = $order->getShippingAddress();
        if (!$shippingAddress) {
            return '';
        }

        // Build context for template
        $templateContext = [
            'shipment' => $shipment,
            'order' => $order,
            'shippingAddress' => $shippingAddress,
            'searchCriteria' => [
                'postalCode' => $shippingAddress->getPostcode(),
                'city' => $shippingAddress->getCity(),
                'countryCode' => $shippingAddress->getCountryCode(),
            ],
            'selectedRelayPoint' => $this->getSelectedRelayPoint($shipment),
        ];

        return $this->renderTemplate($templateContext);
    }

    /**
     * Check if shipment uses a Mondial Relay shipping method.
     */
    private function isMondialRelayShipment(ShipmentInterface $shipment): bool
    {
        $method = $shipment->getMethod();

        if (!$method instanceof ShippingMethodInterface) {
            return false;
        }

        $code = $method->getCode();

        // Check if shipping method code starts with mondial_relay_
        // or contains specific Mondial Relay indicators
        return $code !== null && (
            str_starts_with($code, self::MONDIAL_RELAY_SHIPPING_CODE_PREFIX) ||
            str_contains(strtolower($code), 'mondial') ||
            str_contains(strtolower($code), 'relay')
        );
    }

    /**
     * Get previously selected relay point from shipment details.
     *
     * @return array<string, mixed>|null
     */
    private function getSelectedRelayPoint(ShipmentInterface $shipment): ?array
    {
        $details = $shipment->getDetails();

        if (!is_array($details) || !isset($details['mondial_relay'])) {
            return null;
        }

        return $details['mondial_relay'];
    }

    /**
     * Render template with context.
     *
     * @param array<string, mixed> $context
     */
    private function renderTemplate(array $context): string
    {
        // In a real implementation, we would use Twig environment
        // For now, return a placeholder that will be replaced by actual template
        return sprintf(
            '<!-- Mondial Relay Selector Hook: shipment #%d -->',
            $context['shipment']->getId() ?? 0
        );
    }

    /**
     * Get template path for rendering.
     */
    public static function getTemplate(): string
    {
        return '@KioraSyliusMondialRelayPlugin/shop/checkout/_relay_point_selector.html.twig';
    }

    /**
     * Get hook configuration for Sylius Twig Hooks.
     *
     * @return array<string, mixed>
     */
    public static function getConfiguration(): array
    {
        return [
            'hookName' => 'sylius_shop.checkout.complete.shipments.form',
            'template' => self::getTemplate(),
            'priority' => 0,
            'enabled' => true,
        ];
    }
}
