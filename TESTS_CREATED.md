# Tests PHPUnit créés pour Kiora Sylius Mondial Relay Plugin

## Vue d'ensemble

Suite complète de tests PHPUnit 10+ pour le bundle Sylius Mondial Relay, avec couverture de code des composants principaux.

**Date de création** : 10 décembre 2024
**Framework** : PHPUnit 10.5
**PHP** : 8.2+
**Standard** : PSR-4

## Fichiers de configuration

### 1. phpunit.xml.dist
```xml
- Bootstrap : tests/bootstrap.php
- Test suites : Unit, Integration
- Couverture : HTML, Clover, Text
- Source : src/ (sauf DependencyInjection)
- Strictness : Risky tests, warnings, output
```

### 2. tests/bootstrap.php
```php
- Autoloader Composer
- Configuration error_reporting
- Timezone UTC
- Vérification de l'environnement
```

## Tests unitaires créés

### API Client

#### tests/Unit/Api/Client/MondialRelayApiClientTest.php
**18 méthodes de test** | Couverture cible : 95%

| Test | Description | Assertions |
|------|-------------|-----------|
| `testConstructorThrowsExceptionWithEmptyApiKey()` | Validation API key vide | InvalidArgumentException |
| `testConstructorThrowsExceptionWithEmptyApiSecret()` | Validation API secret vide | InvalidArgumentException |
| `testFindRelayPointsSuccess()` | Recherche réussie de points relais | Collection valide, 1 résultat |
| `testFindRelayPointsWithInvalidCredentials()` | Erreur 401 credentials invalides | MondialRelayAuthenticationException |
| `testGetRelayPointSuccess()` | Récupération d'un point relais | DTO complet avec services |
| `testGetRelayPointNotFound()` | Point relais inexistant (404) | null retourné |
| `testCreateShipmentSuccess()` | Création d'expédition | ShipmentResponse avec numéro |
| `testRetryOnTemporaryError()` | Retry automatique (3 tentatives) | Succès après 2 échecs |
| `testRetryExhausted()` | Échec après max retries | MondialRelayApiException |
| `testGetLabelSuccess()` | Récupération étiquette PDF | LabelResponse avec contenu |
| `testApiErrorCodeInResponse()` | Code erreur Mondial Relay | Exception avec code 80 |
| `testSandboxModeUsesCorrectUrl()` | URL sandbox correcte | api-sandbox.mondialrelay.com |
| `testProductionModeUsesCorrectUrl()` | URL production correcte | api.mondialrelay.com |
| `testAuthenticationHeadersAreSent()` | Headers d'authentification | Authorization, X-MR-Signature, X-MR-Timestamp |

**Techniques utilisées** :
- MockHttpClient pour simuler les appels API
- MockResponse pour contrôler les réponses
- TransportException pour tester les retries
- Validation des signatures HMAC-SHA256

### API DTOs

#### tests/Unit/Api/DTO/RelayPointSearchCriteriaTest.php
**20 méthodes de test** | Couverture cible : 100%

Tests de validation :
- ✅ Création depuis code postal / coordonnées
- ✅ Latitude : -90 à 90 (boundaries testés)
- ✅ Longitude : -180 à 180 (boundaries testés)
- ✅ Rayon : 1 à 100 km
- ✅ Limite : 1 à 50 résultats
- ✅ Poids : valeurs positives
- ✅ Vérification readonly class

#### tests/Unit/Api/DTO/RelayPointCollectionTest.php
**19 méthodes de test** | Couverture cible : 100%

Tests de collection :
- ✅ Itération et comptage
- ✅ Filtrage par distance (filterByMaxDistance)
- ✅ Filtrage par service (filterByService)
- ✅ Filtrage actifs (filterActive)
- ✅ Recherche par ID (findById)
- ✅ Méthodes d'accès : first(), get(), all()
- ✅ Mapping et transformation (map, toArray)
- ✅ Création depuis API response
- ✅ Gestion collection vide
- ✅ Préservation totalCount après filtrage

#### tests/Unit/Api/DTO/RelayPointDTOTest.php
**17 méthodes de test** | Couverture cible : 100%

Tests de DTO :
- ✅ Getters de propriétés readonly
- ✅ getFullAddress() : formatage adresse
- ✅ getDistanceKm() : conversion m→km avec arrondi
- ✅ getGoogleMapsUrl() : génération URL
- ✅ hasService() : vérification services
- ✅ getOpeningHoursForDay() : horaires par jour
- ✅ isOpenOnDay() : vérification ouverture
- ✅ fromApiResponse() : parsing API
- ✅ toArray() : sérialisation
- ✅ Support coordonnées négatives (hémisphère Sud/Ouest)
- ✅ Données minimales et complètes

### Entités

#### tests/Unit/Entity/MondialRelayPickupPointTest.php
**15 méthodes de test** | Couverture cible : 95%

Tests d'entité Doctrine :
- ✅ Initialisation automatique createdAt
- ✅ Getters/setters pour tous les champs
- ✅ Fluent interface (chaining)
- ✅ openingHours en JSON
- ✅ __toString() pour affichage
- ✅ Coordonnées en DECIMAL(10,7)
- ✅ distanceMeters nullable
- ✅ Gestion valeurs nulles
- ✅ Données complètes avec horaires
- ✅ Coordonnées négatives
- ✅ Précision haute pour GPS

### Validateurs

#### tests/Unit/Validator/ValidCoordinatesValidatorTest.php
**23 méthodes de test** | Couverture cible : 100%

Tests de validation GPS :
- ✅ Exception si mauvais type de constraint
- ✅ Valeurs null et vides ignorées
- ✅ Validation latitude : float, int, string
- ✅ Validation longitude : float, int, string
- ✅ Erreurs latitude < -90 ou > 90
- ✅ Erreurs longitude < -180 ou > 180
- ✅ Boundaries exactes acceptées
- ✅ Format invalide (non numérique, array)
- ✅ Coordonnées négatives valides
- ✅ Zéro accepté
- ✅ Haute précision (7 décimales)
- ✅ Messages d'erreur personnalisés
- ✅ Edge cases (90.0001, 180.0001)

**Mocking utilisé** :
- ExecutionContextInterface
- ConstraintViolationBuilderInterface
- Vérification des appels de méthodes

### Formulaires

#### tests/Unit/Form/Type/MondialRelayConfigurationTypeTest.php
**20 méthodes de test** | Couverture cible : 95%

Tests de formulaire Symfony :
- ✅ Soumission valide complète
- ✅ api_key : min 8 caractères, NotBlank
- ✅ api_secret : min 8 caractères, NotBlank
- ✅ brand_id : regex ^[A-Z0-9]{2,8}$
- ✅ default_weight : Range 1-150000
- ✅ sandbox : CheckboxType avec défaut true
- ✅ default_collection_mode : ChoiceType (24R, REL, LD1, LDS, HOM)
- ✅ Validation erreurs individuelles
- ✅ Formats brand_id valides/invalides
- ✅ Tous les modes de collecte testés
- ✅ Vue formulaire (children)
- ✅ Block prefix personnalisé
- ✅ Boundaries poids (1, 150000)

**Extension utilisée** :
- ValidatorExtension avec AttributeMapping

## Statistiques

| Catégorie | Fichiers | Méthodes | Lignes de code |
|-----------|----------|----------|----------------|
| Tests API Client | 1 | 18 | ~450 |
| Tests DTOs | 3 | 56 | ~900 |
| Tests Entity | 1 | 15 | ~350 |
| Tests Validator | 1 | 23 | ~400 |
| Tests Form | 1 | 20 | ~500 |
| **TOTAL** | **7** | **132** | **~2600** |

## Couverture attendue

| Composant | Couverture cible |
|-----------|------------------|
| Api/Client | 95% |
| Api/DTO | 100% |
| Entity | 95% |
| Validator | 100% |
| Form/Type | 95% |
| **Global** | **~95%** |

## Commandes utiles

```bash
# Exécuter tous les tests
vendor/bin/phpunit

# Tests unitaires seulement
vendor/bin/phpunit --testsuite Unit

# Avec couverture HTML
vendor/bin/phpunit --coverage-html var/coverage/html

# Test spécifique
vendor/bin/phpunit tests/Unit/Api/Client/MondialRelayApiClientTest.php

# Avec filtre
vendor/bin/phpunit --filter testFindRelayPointsSuccess

# Mode verbose
vendor/bin/phpunit --verbose --colors=always
```

## Dépendances de test

Définies dans `composer.json` :

```json
{
  "require-dev": {
    "phpunit/phpunit": "^10.5",
    "symfony/browser-kit": "^7.0",
    "symfony/http-client": "^7.0"
  }
}
```

## Patterns et bonnes pratiques utilisés

### 1. Arrange-Act-Assert (AAA)
```php
// Arrange : Préparer les données
$dto = new RelayPointDTO(...);

// Act : Exécuter l'action
$result = $dto->getFullAddress();

// Assert : Vérifier le résultat
$this->assertEquals('...', $result);
```

### 2. Data Providers (potentiel)
Pour tester plusieurs cas similaires :
```php
/**
 * @dataProvider invalidCoordinatesProvider
 */
public function testInvalidCoordinates($latitude, $longitude): void
{
    // ...
}
```

### 3. Mocking intelligent
- HttpClient mocké pour éviter les appels réseau réels
- Context et ViolationBuilder mockés pour les validateurs
- Callbacks dans MockHttpClient pour tester retry

### 4. Edge cases systématiques
- Valeurs nulles
- Valeurs vides
- Boundaries (min/max)
- Formats invalides
- Coordonnées négatives

### 5. Tests d'immutabilité
Vérification que les classes readonly sont bien readonly :
```php
$reflection = new \ReflectionClass($dto);
$this->assertTrue($reflection->isReadOnly());
```

## Intégration avec CI/CD

Les tests sont prêts pour être intégrés dans GitHub Actions, GitLab CI, etc. :

```yaml
# .github/workflows/tests.yml
name: Tests
on: [push, pull_request]

jobs:
  phpunit:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v3

      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: '8.2'
          extensions: intl

      - name: Install dependencies
        run: composer install --prefer-dist

      - name: Run tests
        run: vendor/bin/phpunit --coverage-clover coverage.xml

      - name: Upload coverage
        uses: codecov/codecov-action@v3
```

## Prochaines étapes

### Tests d'intégration à créer :
1. **DatabaseTest** : Persistance entités Doctrine
2. **ApiIntegrationTest** : Vrais appels API (sandbox)
3. **FormIntegrationTest** : Intégration complète formulaire
4. **ValidatorIntegrationTest** : Validation entités complètes
5. **ControllerTest** : Tests fonctionnels des contrôleurs

### Tests fonctionnels potentiels :
1. Sélection point relais dans checkout
2. Génération étiquette complète
3. Webhook callbacks
4. Configuration admin

## Maintenance

Pour maintenir les tests à jour :

1. **Nouveaux features** : Ajouter tests AVANT le code (TDD)
2. **Bug fixes** : Ajouter test de régression
3. **Refactoring** : Vérifier que tous les tests passent
4. **Coverage** : Maintenir > 90% de couverture

## Documentation

- Configuration : `phpunit.xml.dist`
- Bootstrap : `tests/bootstrap.php`
- Guide : `tests/README.md`
- Ce fichier : `TESTS_CREATED.md`

## Auteur

Tests créés par Claude Code (Anthropic) pour le projet Kiora Sylius Mondial Relay Plugin.

---

**Total : 7 fichiers de test | 132 méthodes | ~2600 lignes de code de test | Couverture cible : 95%**
