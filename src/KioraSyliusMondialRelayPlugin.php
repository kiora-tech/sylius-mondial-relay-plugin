<?php

declare(strict_types=1);

namespace Kiora\SyliusMondialRelayPlugin;

use Symfony\Component\HttpKernel\Bundle\AbstractBundle;

/**
 * Mondial Relay pickup point shipping integration for Sylius 2.
 *
 * This plugin provides:
 * - Mondial Relay API v2 integration
 * - Pickup point selection widget
 * - Shipping calculator for relay points
 * - QR code generation for shipments
 * - Admin interface for managing shipments
 *
 * @author Kiora <https://kiora.tech>
 */
final class KioraSyliusMondialRelayPlugin extends AbstractBundle
{
    public function getPath(): string
    {
        return \dirname(__DIR__);
    }
}
