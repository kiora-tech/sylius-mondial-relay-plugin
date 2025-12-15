# Kiora Sylius Mondial Relay Plugin

[![PHP Version](https://img.shields.io/badge/php-%5E8.2-blue)](https://php.net)
[![Sylius Version](https://img.shields.io/badge/sylius-%5E2.0-green)](https://sylius.com)
[![License](https://img.shields.io/badge/license-MIT-brightgreen)](LICENSE)

A comprehensive Mondial Relay integration plugin for Sylius 2.x, providing pickup point shipping functionality for French and European e-commerce stores.

## Features

- 🗺️ **Pickup Point Selection**: Interactive widget for customers to choose relay points during checkout
- 📦 **Dual API Support**:
  - **REST API v2 (Connect)**: For shipment creation, label generation with Basic Auth
  - **SOAP API v1**: For relay point search (WSI4_PointRelais_Recherche)
- 🎫 **Label & QR Code Generation**: Automatic shipping labels with QR codes for drop-off
- 📊 **Admin Interface**:
  - Configuration page for API credentials
  - Relay point details in order view
  - QR code generation from admin panel
- 🔌 **Sylius 2 Twig Hooks**: Native integration with Sylius 2 hook system
- ⚡ **Performance**: Built-in caching for API responses
- 🧪 **Sandbox Mode**: Test integration without affecting production
- 🔒 **Type Safety**: PHP 8.2+ with readonly classes and strict typing
- 📝 **Well Documented**: Complete API documentation and usage examples

## Requirements

- PHP 8.2 or higher
- Sylius 2.0 or higher
- Symfony 7.0 or higher
- MySQL 8.0+ or PostgreSQL 13+
- A valid Mondial Relay merchant account

## Installation

### 1. Install via Composer

```bash
composer require kiora/sylius-mondial-relay-plugin
```

### 2. Register the Bundle

The bundle is automatically registered via Symfony Flex. If not, add it manually to `config/bundles.php`:

```php
<?php

return [
    // ...
    Kiora\SyliusMondialRelayPlugin\KioraSyliusMondialRelayPlugin::class => ['all' => true],
];
```

### 3. Configure the Plugin

Create `config/packages/kiora_sylius_mondial_relay.yaml`:

```yaml
kiora_sylius_mondial_relay:
    # Get these credentials from your Mondial Relay merchant account
    api_key: '%env(MONDIAL_RELAY_API_KEY)%'
    api_secret: '%env(MONDIAL_RELAY_API_SECRET)%'
    brand_id: '%env(MONDIAL_RELAY_BRAND_ID)%'

    # Enable sandbox mode for testing (uses test API endpoints)
    sandbox: '%env(bool:MONDIAL_RELAY_SANDBOX)%'

    # Optional: Cache configuration
    cache:
        enabled: true
        ttl: 3600  # Cache pickup point searches for 1 hour

    # Optional: HTTP client settings
    http:
        timeout: 10
        max_retries: 3

    # Optional: Default values
    default_country: 'FR'      # ISO 3166-1 alpha-2 country code
    default_weight: 1000       # Default package weight in grams
```

### 4. Set Environment Variables

Add to your `.env` or `.env.local`:

```env
# REST API v2 (Connect) - For shipment creation and labels
MONDIAL_RELAY_API_KEY=your_api_key_here
MONDIAL_RELAY_API_SECRET=your_api_secret_here
MONDIAL_RELAY_SANDBOX=true

# SOAP API v1 - For relay point search
MONDIAL_RELAY_ENSEIGNE=your_enseigne_code
MONDIAL_RELAY_PRIVATE_KEY=your_private_key_for_signature
```

> **Note**: You need credentials for both APIs. The REST API (Connect) handles shipments and labels, while the SOAP API is required for relay point search as this feature is not available in the REST API.

### 5. Import Routes

Create `config/routes/kiora_mondial_relay.yaml`:

```yaml
# Admin routes (configuration, shipment actions)
kiora_mondial_relay_admin:
    resource: '@KioraSyliusMondialRelayPlugin/config/routes/admin.yaml'

# Shop routes (relay point search/select during checkout)
kiora_mondial_relay_shop:
    resource: '@KioraSyliusMondialRelayPlugin/config/routes/shop.yaml'
```

### 6. Extend Shipment Entity (optional)

To store pickup point selections, extend Sylius Shipment entity with the provided trait:

```php
<?php
// src/Entity/Shipping/Shipment.php

namespace App\Entity\Shipping;

use Doctrine\ORM\Mapping as ORM;
use Kiora\SyliusMondialRelayPlugin\Entity\MondialRelayShipmentInterface;
use Kiora\SyliusMondialRelayPlugin\Entity\MondialRelayShipmentTrait;
use Sylius\Component\Core\Model\Shipment as BaseShipment;

#[ORM\Entity]
#[ORM\Table(name: 'sylius_shipment')]
class Shipment extends BaseShipment implements MondialRelayShipmentInterface
{
    use MondialRelayShipmentTrait;
}
```

Then run migrations:

```bash
bin/console doctrine:migrations:diff
bin/console doctrine:migrations:migrate
```

### 7. Clear Cache

```bash
bin/console cache:clear
```

## Configuration Reference

### Full Configuration Options

```yaml
kiora_sylius_mondial_relay:
    # Required: API credentials
    api_key: string                    # Your Mondial Relay API key
    api_secret: string                 # Your API secret for request signing
    brand_id: string                   # Your brand identifier

    # Optional: Environment
    sandbox: false                     # Enable test mode

    # Optional: Cache settings
    cache:
        enabled: true                  # Enable API response caching
        ttl: 3600                      # Cache lifetime in seconds

    # Optional: HTTP client
    http:
        timeout: 10                    # Request timeout in seconds
        max_retries: 3                 # Max retry attempts for failed requests

    # Optional: Defaults
    default_country: 'FR'              # Default country for searches
    default_weight: 1000               # Default weight in grams
```

## Usage

### Quick Start with API Client

The plugin provides a complete HTTP client for interacting with the Mondial Relay API:

```php
<?php

use Kiora\SyliusMondialRelayPlugin\Api\Client\MondialRelayApiClientInterface;
use Kiora\SyliusMondialRelayPlugin\Api\DTO\RelayPointSearchCriteria;

class MyService
{
    public function __construct(
        private readonly MondialRelayApiClientInterface $apiClient
    ) {}

    public function searchRelayPoints(string $postalCode): void
    {
        // Search by postal code
        $criteria = RelayPointSearchCriteria::fromPostalCode(
            postalCode: $postalCode,
            countryCode: 'FR',
            radius: 10,
            limit: 20
        );

        $collection = $this->apiClient->findRelayPoints($criteria);

        foreach ($collection as $relayPoint) {
            echo sprintf(
                "%s - %s (%s km)\n",
                $relayPoint->name,
                $relayPoint->getFullAddress(),
                $relayPoint->getDistanceKm()
            );
        }
    }
}
```

**See complete documentation**: [API Client Documentation](docs/API_CLIENT.md)

**See working examples**: [docs/examples/basic-usage.php](docs/examples/basic-usage.php)

### Testing the Integration

Use the provided Makefile commands for development:

```bash
# Install dependencies
make install

# Run tests
make test

# Run static analysis
make phpstan

# Fix code style
make cs-fix

# Run all quality checks
make check
```

## Development

### Project Structure

```
kiora-sylius-mondial-relay-plugin/
├── config/
│   ├── routes/
│   │   ├── admin.yaml                 # Admin routes (configuration, shipments)
│   │   └── shop.yaml                  # Shop routes (relay point search/select)
│   ├── services.yaml                  # Service definitions
│   └── twig_hooks.yaml                # Sylius 2 Twig Hooks configuration
├── src/
│   ├── Api/
│   │   ├── Client/
│   │   │   ├── MondialRelayApiClient.php      # REST API v2 client
│   │   │   ├── MondialRelayApiClientInterface.php
│   │   │   └── MondialRelaySoapClient.php     # SOAP API v1 client
│   │   ├── DTO/
│   │   │   ├── RelayPointSearchCriteria.php   # Search parameters
│   │   │   ├── RelayPointDTO.php              # Relay point data
│   │   │   ├── RelayPointCollection.php       # Iterable collection
│   │   │   └── ...
│   │   └── Exception/
│   │       └── MondialRelayApiException.php   # API exceptions
│   ├── Controller/
│   │   ├── Admin/
│   │   │   ├── ConfigurationController.php    # API configuration page
│   │   │   └── ShipmentController.php         # Label/QR code generation
│   │   └── Shop/
│   │       └── RelayPointController.php       # Relay point search/select
│   ├── Entity/
│   │   ├── MondialRelayPickupPoint.php        # Pickup point entity
│   │   └── MondialRelayShipmentTrait.php      # Shipment extension trait
│   ├── Service/
│   │   ├── MondialRelayLabelGenerator.php     # Shipping labels
│   │   └── MondialRelayQrCodeGenerator.php    # QR codes for drop-off
│   ├── DependencyInjection/
│   │   ├── Configuration.php
│   │   └── KioraSyliusMondialRelayExtension.php
│   └── KioraSyliusMondialRelayPlugin.php
├── templates/
│   ├── admin/
│   │   ├── configuration/             # Admin configuration page templates
│   │   └── order/show/                # Order detail Mondial Relay info
│   └── shop/
│       └── checkout/                  # Relay point selector widget
├── CHANGELOG.md
├── composer.json
└── README.md
```

### Running Tests

```bash
composer test
```

### Static Analysis

```bash
composer phpstan
```

### Code Style

```bash
composer cs-fix
```

## Troubleshooting

### API Authentication Errors

If you receive authentication errors:
1. Verify your credentials in the Mondial Relay merchant portal
2. Ensure you're using the correct environment (sandbox vs production)
3. Check that the API secret is properly configured for request signing

### Cache Issues

Clear the plugin cache:

```bash
bin/console cache:pool:clear kiora_sylius_mondial_relay.cache
```

### Network Timeouts

Increase the HTTP timeout in your configuration:

```yaml
kiora_sylius_mondial_relay:
    http:
        timeout: 30
```

## Documentation

### Plugin Documentation
- **[API Client Guide](docs/API_CLIENT.md)** - Complete HTTP client documentation
- **[API Client Summary](docs/API_CLIENT_SUMMARY.md)** - Implementation details and statistics
- **[Code Examples](docs/examples/basic-usage.php)** - 8 working examples for common use cases
- **[Changelog](CHANGELOG.md)** - Version history and changes

### External Documentation
- [Mondial Relay API Documentation](https://www.mondialrelay.com/documentation/)
- [Sylius Documentation](https://docs.sylius.com)
- [Symfony HttpClient](https://symfony.com/doc/current/http_client.html)

## Support

For bugs, feature requests, or questions:
- GitHub Issues: [Create an issue](https://github.com/kiora/sylius-mondial-relay-plugin/issues)
- Email: support@kiora.tech

## Contributing

Contributions are welcome! Please:
1. Fork the repository
2. Create a feature branch
3. Commit your changes with clear messages
4. Add tests for new functionality
5. Submit a pull request

## License

This plugin is licensed under the MIT License. See [LICENSE](LICENSE) for details.

## Credits

Developed by [Kiora](https://kiora.tech)

Built for Sylius, the modern e-commerce framework for PHP.

---

**Note**: This plugin requires a valid Mondial Relay merchant account. Visit [Mondial Relay Professional](https://www.mondialrelay.fr/pro/) to register.
