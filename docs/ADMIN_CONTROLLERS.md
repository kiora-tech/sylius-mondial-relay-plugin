# Admin Controllers and Forms Documentation

This document describes the admin interface components for the Kiora Sylius Mondial Relay Plugin.

## Overview

The plugin provides a complete admin interface for managing Mondial Relay configuration and operations within Sylius 2.0+ admin panel.

## Components

### Forms

#### 1. MondialRelayConfigurationType

**Location:** `src/Form/Type/MondialRelayConfigurationType.php`

**Purpose:** Global configuration form for Mondial Relay API credentials and default settings.

**Fields:**
- `api_key` (TextType, required): Mondial Relay API key
- `api_secret` (PasswordType, required): Mondial Relay API secret
- `brand_id` (TextType, required): Brand identifier (2-8 uppercase alphanumeric)
- `sandbox` (CheckboxType): Enable/disable sandbox mode
- `default_weight` (IntegerType): Default package weight in grams (1-150000g)
- `default_collection_mode` (ChoiceType): Default shipping mode (24R, REL, LD1, LDS, HOM)

**Validation:**
- All required fields validated with `NotBlank` constraint
- String length validation for API credentials (min 8 chars)
- Brand ID format validation (regex: `/^[A-Z0-9]{2,8}$/`)
- Weight range validation (1g - 150kg)

#### 2. MondialRelayShippingGatewayConfigurationType

**Location:** `src/Form/Type/MondialRelayShippingGatewayConfigurationType.php`

**Purpose:** Configuration form for individual shipping methods using Mondial Relay.

**Fields:**
- `collection_mode` (ChoiceType, required): Shipping mode for this method
- `max_weight` (IntegerType, required): Maximum package weight allowed
- `enabled_countries` (CountryType, multiple, required): Supported countries
- `allow_customer_selection` (ChoiceType, required): Enable/disable customer relay point selection
- `max_relay_points` (IntegerType, required): Maximum number of relay points to display (1-20)

**Supported Countries:**
- France (FR)
- Belgium (BE)
- Luxembourg (LU)
- Netherlands (NL)
- Spain (ES)
- Portugal (PT)
- Germany (DE)
- Austria (AT)
- Italy (IT)

### Controllers

#### 1. ConfigurationController

**Location:** `src/Controller/Admin/ConfigurationController.php`

**Routes:**
- `GET/POST /admin/mondial-relay/configuration` - Display and save configuration
- `POST /admin/mondial-relay/configuration/test-connection` - Test API credentials (AJAX)

**Actions:**

##### indexAction(Request $request): Response
Displays and processes the configuration form.

**Workflow:**
1. Load current configuration from JSON file
2. Create and handle form submission
3. Validate and save configuration
4. Display success/error flash messages
5. Redirect back to form

**Configuration Storage:**
Configuration is saved as JSON in `%kernel.project_dir%/config/mondial_relay.json`

##### testConnectionAction(Request $request): JsonResponse
Tests API connection with provided credentials (AJAX endpoint).

**Request Parameters:**
- `api_key` (string): API key to test
- `api_secret` (string): API secret to test
- `brand_id` (string): Brand ID to test
- `sandbox` (bool): Whether to use sandbox endpoint

**Response Format:**
```json
{
    "success": true,
    "message": "Connection successful! Your credentials are valid.",
    "data": {
        "message": "Connection successful",
        "sandbox": true
    }
}
```

Or on error:
```json
{
    "success": false,
    "message": "Connection failed: [error details]"
}
```

#### 2. ShipmentController

**Location:** `src/Controller/Admin/ShipmentController.php`

**Routes:**
- `POST/GET /admin/mondial-relay/shipments/{shipmentId}/generate-label` - Generate shipping label
- `GET /admin/mondial-relay/shipments/{shipmentId}/download-label` - Download label PDF
- `POST/GET /admin/mondial-relay/shipments/{shipmentId}/generate-qr-code` - Generate QR code

**Actions:**

##### generateLabelAction(Request $request, int $shipmentId): Response
Generates a Mondial Relay shipping label for the specified shipment.

**Workflow:**
1. Find shipment by ID (or 404)
2. Call label generator service
3. Display success/error flash message
4. Redirect to order detail page

##### downloadLabelAction(int $shipmentId): Response
Downloads the generated label PDF.

**Workflow:**
1. Find shipment by ID (or 404)
2. Get label path from generator service
3. Return BinaryFileResponse with PDF
4. Filename: `mondial-relay-label-{shipmentId}.pdf`

**Error Handling:**
- If label doesn't exist, show flash message and redirect
- If file read fails, show error and redirect

##### generateQrCodeAction(Request $request, int $shipmentId): Response
Generates a QR code for the shipment tracking number.

**Supports two modes:**
1. **Regular request**: Generates QR code and redirects with flash message
2. **AJAX request**: Returns JSON response

**AJAX Response Format:**
```json
{
    "success": true,
    "message": "QR code generated successfully",
    "data": {
        "qr_code_url": "/media/mondial_relay/qrcodes/123.png",
        "tracking_number": "1234567890"
    }
}
```

### Menu Integration

#### AdminMenuListener

**Location:** `src/Menu/AdminMenuListener.php`

**Events:**
- `sylius.menu.admin.main` - Adds Mondial Relay to main admin menu
- `sylius.menu.admin.order.show` - Adds Mondial Relay actions to order detail page

**Main Menu Items:**
```
Mondial Relay
├── Configuration
├── Dashboard (placeholder)
└── Shipments
```

**Order Show Menu Items:**
- Generate Label (visible if no tracking number)
- Download Label (visible if tracking number exists)
- Generate QR Code (AJAX action)

### Templates

#### 1. Configuration Page

**Location:** `templates/admin/configuration/index.html.twig`

**Features:**
- Configuration form with sections for API credentials and default settings
- Test Connection button with AJAX functionality
- Documentation links sidebar
- Help section

**JavaScript:**
- AJAX test connection with loading modal
- Success/error message display
- Form validation

#### 2. Dashboard Page

**Location:** `templates/admin/dashboard/index.html.twig`

**Status:** Placeholder for future development

**Planned Features:**
- Recent shipments list
- Statistics and charts
- Bulk operations
- Error monitoring

#### 3. Order Detail Block

**Location:** `templates/admin/order/_mondial_relay_block.html.twig`

**Purpose:** Display Mondial Relay information in order detail page.

**Displayed Information:**
- Relay point ID and name
- Relay point address
- Tracking number
- QR code (if generated)

**Actions:**
- Generate/Download label button
- Generate QR code button (AJAX)

**JavaScript:**
- AJAX QR code generation
- Confirmation dialogs
- Auto-reload after successful generation

## Services

### MondialRelayApiV2Service

**Interface:** None (concrete class)

**Purpose:** Handle Mondial Relay API v2 operations and connection testing.

**Methods:**
- `testConnection(string $apiKey, string $apiSecret, string $brandId, bool $sandbox): array`

### MondialRelayLabelGenerator

**Interface:** `MondialRelayLabelGeneratorInterface`

**Purpose:** Generate and manage shipping labels.

**Methods:**
- `generateLabel(ShipmentInterface $shipment): array`
- `getLabelPath(ShipmentInterface $shipment): ?string`
- `hasLabel(ShipmentInterface $shipment): bool`
- `deleteLabel(ShipmentInterface $shipment): bool`

**Storage:** `%kernel.project_dir%/var/mondial_relay/labels/{shipmentId}.pdf`

**Status:** Partial implementation (API integration pending)

### MondialRelayQrCodeGenerator

**Interface:** `MondialRelayQrCodeGeneratorInterface`

**Purpose:** Generate and manage QR codes for tracking numbers.

**Methods:**
- `generateQrCode(ShipmentInterface $shipment): array`
- `getQrCodeUrl(ShipmentInterface $shipment): ?string`
- `hasQrCode(ShipmentInterface $shipment): bool`
- `deleteQrCode(ShipmentInterface $shipment): bool`

**Storage:** `%kernel.project_dir%/public/media/mondial_relay/qrcodes/{shipmentId}.png`

**Dependencies:** `endroid/qr-code` library

## Translations

### Available Locales
- English (`messages.en.yaml`)
- French (`messages.fr.yaml`)

### Translation Keys Prefix
All translations use the prefix `kiora_sylius_mondial_relay`

### Categories
- `ui.*` - User interface labels and messages
- `form.*` - Form field labels and help texts
- `collection_mode.*` - Shipping mode descriptions
- `validation.*` - Validation error messages

## Configuration

### Service Parameters

```yaml
# config/services.yaml
parameters:
    kiora_sylius_mondial_relay.http_timeout: 30
    kiora_sylius_mondial_relay.cache_ttl: 3600

services:
    # Configuration path
    Kiora\SyliusMondialRelayPlugin\Controller\Admin\ConfigurationController:
        arguments:
            $configFilePath: '%kernel.project_dir%/config/mondial_relay.json'

    # Label storage
    Kiora\SyliusMondialRelayPlugin\Service\MondialRelayLabelGenerator:
        arguments:
            $labelsDirectory: '%kernel.project_dir%/var/mondial_relay/labels'

    # QR code storage
    Kiora\SyliusMondialRelayPlugin\Service\MondialRelayQrCodeGenerator:
        arguments:
            $qrCodesDirectory: '%kernel.project_dir%/public/media/mondial_relay/qrcodes'
            $qrCodesPublicPath: '/media/mondial_relay/qrcodes'
```

### Required Directories

The following directories need to be created with write permissions:

```bash
mkdir -p var/mondial_relay/labels
mkdir -p public/media/mondial_relay/qrcodes
mkdir -p config
chmod 755 var/mondial_relay/labels
chmod 755 public/media/mondial_relay/qrcodes
```

## Security

### Permissions

All admin routes require:
- `_sylius.section: admin`
- `_sylius.permission: true`

This ensures only authenticated admin users can access Mondial Relay configuration and operations.

### AJAX Requests

The following endpoints accept AJAX requests:
- `/admin/mondial-relay/configuration/test-connection` (POST)
- `/admin/mondial-relay/shipments/{id}/generate-qr-code` (POST/GET)

AJAX validation:
```php
if (!$request->isXmlHttpRequest()) {
    throw new BadRequestHttpException('This endpoint only accepts AJAX requests');
}
```

## Extending

### Adding New Fields to Configuration

1. Add field to `MondialRelayConfigurationType`
2. Add validation constraints
3. Add translation keys
4. Update configuration template
5. Update service that uses the configuration

### Adding New Actions to Shipments

1. Add method to `ShipmentController`
2. Define route in `config/routes/admin.yaml`
3. Add menu item in `AdminMenuListener`
4. Add translation keys
5. Update order detail template block

### Customizing Templates

Override templates in your application:

```
# Your application
templates/
  bundles/
    KioraSyliusMondialRelayPlugin/
      admin/
        configuration/
          index.html.twig  # Override configuration page
        order/
          _mondial_relay_block.html.twig  # Override order block
```

## TODO

### High Priority
- [ ] Complete `MondialRelayLabelGenerator::generateLabel()` implementation
- [ ] Integrate with Mondial Relay API for label generation
- [ ] Add shipment tracking integration
- [ ] Implement relay point assignment in admin

### Medium Priority
- [ ] Create dashboard with statistics
- [ ] Add bulk label generation
- [ ] Implement label regeneration
- [ ] Add webhook handling for tracking updates

### Low Priority
- [ ] Add export functionality for shipments
- [ ] Create reports for shipping costs
- [ ] Add email notifications for label generation
- [ ] Implement label printing queue

## Testing

### Manual Testing Checklist

**Configuration:**
- [ ] Access configuration page
- [ ] Save valid configuration
- [ ] Test API connection with valid credentials
- [ ] Test API connection with invalid credentials
- [ ] Verify form validation errors display correctly

**Shipments:**
- [ ] Generate label for shipment (when implemented)
- [ ] Download generated label
- [ ] Generate QR code for shipment
- [ ] Verify QR code displays in order detail
- [ ] Test AJAX QR code generation

**Menu:**
- [ ] Verify Mondial Relay menu appears in admin
- [ ] Verify submenu items are accessible
- [ ] Verify order show menu items appear for Mondial Relay shipments

## Support

For issues or questions:
- Check the main plugin README
- Review API documentation: https://www.mondialrelay.fr/media/624438/documentation-api-v2.pdf
- Contact Mondial Relay support: https://connect.mondialrelay.com/
