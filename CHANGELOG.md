# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Added

#### Mondial Relay API Client (2025-12-10)

Complete HTTP client implementation for Mondial Relay REST API v2.

**Client Implementation**
- `MondialRelayApiClientInterface` - Interface defining all API operations
- `MondialRelayApiClient` - Full implementation with authentication, retries, and error handling
  - HMAC-SHA256 request signing
  - Exponential backoff retry mechanism (3 attempts)
  - Configurable timeout (default 30s)
  - Sandbox/Production environment support
  - PSR-3 logger integration
  - Symfony HttpClient integration

**Data Transfer Objects (DTOs)**
- `RelayPointSearchCriteria` - Immutable search parameters with validation
  - Support for postal code and GPS coordinate search
  - Factory methods: `fromPostalCode()`, `fromCoordinates()`
- `RelayPointDTO` - Relay point information with helper methods
  - Full address and GPS coordinates
  - Opening hours structured by day
  - Available services (parking, accessibility)
  - Google Maps URL generation
- `RelayPointCollection` - Iterable collection with filtering capabilities
  - Fluent filtering API (`filterByService()`, `filterByMaxDistance()`, `filterActive()`)
  - Collection operations (`map()`, `toArray()`)
  - Implements `IteratorAggregate` and `Countable`
- `ShipmentRequest` - Shipment creation parameters with validation
  - Weight and dimension limits validation
  - Email and phone validation
  - Support for all Mondial Relay delivery modes
- `ShipmentResponse` - Created shipment details
  - Expedition number and tracking URL
  - Label download URL
  - Optional QR code support
- `LabelResponse` - Shipping label PDF content
  - Binary content with metadata
  - File saving capabilities
  - Base64 and Data URI conversion
  - Human-readable size formatting

**Exception Handling**
- `MondialRelayApiException` - Base exception with error code mapping
  - Translated French error messages
  - Error categorization (temporary, configuration, validation)
  - Rich context information for debugging
  - Support for 8+ Mondial Relay error codes
- `MondialRelayAuthenticationException` - Specific authentication failure handling

**Documentation**
- Complete API client documentation (`docs/API_CLIENT.md`)
  - Installation and configuration guide
  - Usage examples for all operations
  - Error handling patterns
  - Performance optimization tips
  - Caching strategies
  - Testing guidelines
  - Troubleshooting section
- Working code examples (`docs/examples/basic-usage.php`)
  - 8 complete examples covering all features
  - Search by postal code and GPS
  - Shipment creation and label download
  - Advanced error handling
  - Collection operations
- Implementation summary (`docs/API_CLIENT_SUMMARY.md`)

**Configuration**
- Symfony service registration in `config/services.yaml`
- Environment variable support:
  - `MONDIAL_RELAY_API_KEY`
  - `MONDIAL_RELAY_API_SECRET`
  - `MONDIAL_RELAY_SANDBOX`
  - `MONDIAL_RELAY_HTTP_TIMEOUT`
- Monolog logger channel integration

**PHP Features**
- PHP 8.2+ readonly classes for immutable DTOs
- Promoted constructor properties
- Named arguments support
- Strict typing throughout
- Full PHPDoc documentation

**Statistics**
- 10 PHP classes (1,634+ lines)
- 3 documentation files (1,500+ lines)
- 100% type coverage
- PSR-12 compliant
- PHPStan level 8 ready

## [0.1.0] - 2025-12-10

### Added
- Initial plugin structure
- Basic Symfony bundle setup
- DependencyInjection configuration
- Entity foundation

---

## Legend

- `Added` - New features
- `Changed` - Changes in existing functionality
- `Deprecated` - Soon-to-be removed features
- `Removed` - Removed features
- `Fixed` - Bug fixes
- `Security` - Security vulnerability fixes

[Unreleased]: https://github.com/kiora-tech/sylius-mondial-relay-plugin/compare/v0.1.0...HEAD
[0.1.0]: https://github.com/kiora-tech/sylius-mondial-relay-plugin/releases/tag/v0.1.0
