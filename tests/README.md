# Tests PHPUnit - Kiora Sylius Mondial Relay Plugin

Ce répertoire contient la suite complète de tests PHPUnit pour le plugin Sylius Mondial Relay.

## Structure des tests

```
tests/
├── bootstrap.php              # Bootstrap pour initialiser l'environnement de test
├── Unit/                      # Tests unitaires
│   ├── Api/
│   │   ├── Client/
│   │   │   └── MondialRelayApiClientTest.php
│   │   └── DTO/
│   │       ├── RelayPointDTOTest.php
│   │       ├── RelayPointSearchCriteriaTest.php
│   │       └── RelayPointCollectionTest.php
│   ├── Entity/
│   │   └── MondialRelayPickupPointTest.php
│   ├── Validator/
│   │   └── ValidCoordinatesValidatorTest.php
│   └── Form/
│       └── Type/
│           └── MondialRelayConfigurationTypeTest.php
└── Integration/               # Tests d'intégration (à venir)
```

## Prérequis

- PHP 8.2 ou supérieur
- Extension PHP intl activée
- Composer installé
- Dépendances installées via `composer install`

## Installation

```bash
# Installer les dépendances
composer install

# Si vous avez des problèmes de plateforme (ex: PHP 8.1 au lieu de 8.2)
composer install --ignore-platform-reqs
```

## Exécution des tests

### Tous les tests

```bash
# Exécuter tous les tests
vendor/bin/phpunit

# Avec verbosité
vendor/bin/phpunit --verbose

# Avec couleurs
vendor/bin/phpunit --colors=always
```

### Tests par suite

```bash
# Tests unitaires uniquement
vendor/bin/phpunit --testsuite Unit

# Tests d'intégration uniquement
vendor/bin/phpunit --testsuite Integration
```

### Tests spécifiques

```bash
# Un fichier de test spécifique
vendor/bin/phpunit tests/Unit/Api/Client/MondialRelayApiClientTest.php

# Une méthode de test spécifique
vendor/bin/phpunit --filter testFindRelayPointsSuccess
```

## Couverture de code

```bash
# Générer la couverture HTML
vendor/bin/phpunit --coverage-html var/coverage/html

# Générer la couverture Clover (pour CI)
vendor/bin/phpunit --coverage-clover var/coverage/clover.xml

# Afficher la couverture en ligne de commande
vendor/bin/phpunit --coverage-text
```

La couverture HTML sera disponible dans `var/coverage/html/index.html`.

## Description des tests

### Unit/Api/Client/MondialRelayApiClientTest.php

Tests du client API REST Mondial Relay v2 :
- ✅ Construction avec validation des credentials
- ✅ Recherche de points relais (succès et erreurs)
- ✅ Récupération d'un point relais par ID
- ✅ Création d'expédition
- ✅ Récupération d'étiquettes PDF
- ✅ Gestion des erreurs d'authentification (401/403)
- ✅ Retry automatique sur erreurs temporaires
- ✅ Gestion du mode sandbox vs production
- ✅ Validation des headers d'authentification (signature HMAC)

### Unit/Api/DTO/RelayPointSearchCriteriaTest.php

Tests des critères de recherche de points relais :
- ✅ Création depuis code postal
- ✅ Création depuis coordonnées GPS
- ✅ Validation des coordonnées GPS (latitude -90/90, longitude -180/180)
- ✅ Validation du rayon de recherche (1-100 km)
- ✅ Validation de la limite de résultats (1-50)
- ✅ Validation du poids (valeurs positives)
- ✅ Méthodes `hasCoordinates()` et `hasPostalCode()`
- ✅ Immutabilité (readonly class)

### Unit/Api/DTO/RelayPointCollectionTest.php

Tests de la collection de points relais :
- ✅ Création et itération
- ✅ Filtrage par distance maximale
- ✅ Filtrage par service disponible
- ✅ Filtrage des points actifs
- ✅ Recherche par ID
- ✅ Mapping et transformation
- ✅ Conversion depuis/vers API response
- ✅ Collection vide
- ✅ Méthodes `first()`, `get()`, `all()`, `count()`, `isEmpty()`

### Unit/Api/DTO/RelayPointDTOTest.php

Tests du DTO de point relais :
- ✅ Construction et getters
- ✅ Formatage d'adresse complète
- ✅ Conversion distance mètres → kilomètres (arrondi 2 décimales)
- ✅ Génération URL Google Maps
- ✅ Vérification de disponibilité de services
- ✅ Gestion des horaires d'ouverture par jour
- ✅ Conversion depuis API response
- ✅ Conversion vers array
- ✅ Support des coordonnées négatives
- ✅ Immutabilité (readonly class)

### Unit/Entity/MondialRelayPickupPointTest.php

Tests de l'entité Doctrine :
- ✅ Initialisation automatique de createdAt
- ✅ Getters et setters fluides
- ✅ Gestion des horaires d'ouverture (JSON)
- ✅ Méthode `__toString()` pour affichage
- ✅ Stockage des coordonnées en decimal (précision 7)
- ✅ Distance nullable
- ✅ Support des coordonnées négatives

### Unit/Validator/ValidCoordinatesValidatorTest.php

Tests du validateur de coordonnées GPS :
- ✅ Validation de latitude (-90 à 90)
- ✅ Validation de longitude (-180 à 180)
- ✅ Acceptation de valeurs float, int, et string numériques
- ✅ Rejet de valeurs non numériques
- ✅ Valeurs nulles/vides autorisées
- ✅ Messages d'erreur personnalisés
- ✅ Gestion des cas limites (boundaries)
- ✅ Intégration avec le système de validation Symfony

### Unit/Form/Type/MondialRelayConfigurationTypeTest.php

Tests du formulaire de configuration Mondial Relay :
- ✅ Soumission avec données valides
- ✅ Validation API key (min 8 caractères)
- ✅ Validation API secret (min 8 caractères)
- ✅ Validation brand ID (regex: ^[A-Z0-9]{2,8}$)
- ✅ Validation poids par défaut (1-150000 grammes)
- ✅ Choix mode de collecte (24R, REL, LD1, LDS, HOM)
- ✅ Checkbox sandbox avec valeur par défaut
- ✅ Block prefix personnalisé
- ✅ Intégration ValidatorExtension

## Bonnes pratiques

### 1. Tests isolés
Chaque test doit être indépendant et ne pas dépendre de l'état d'autres tests.

### 2. Mocking
Utilisez des mocks pour les dépendances externes (API HTTP, base de données, etc.).

### 3. Cas limites
Testez toujours les cas limites :
- Valeurs nulles
- Valeurs vides
- Valeurs minimales/maximales
- Formats invalides

### 4. Nommage des tests
Utilisez des noms descriptifs : `testMethodName[Scenario][ExpectedResult]()`

Exemples :
- `testFindRelayPointsSuccess()`
- `testValidationThrowsExceptionForInvalidLatitude()`
- `testGetDistanceKmReturnsNullWhenDistanceIsNull()`

### 5. Assertions claires
Une assertion par test si possible, avec des messages explicites.

## Intégration Continue

Les tests peuvent être intégrés dans un pipeline CI/CD :

```yaml
# Exemple GitHub Actions
- name: Run tests
  run: vendor/bin/phpunit --coverage-clover coverage.xml

- name: Upload coverage
  uses: codecov/codecov-action@v3
  with:
    files: ./coverage.xml
```

## Dépannage

### Erreur "Class not found"
```bash
composer dump-autoload
```

### Erreur de plateforme PHP
```bash
composer install --ignore-platform-reqs
```

### Extension intl manquante
```bash
# Ubuntu/Debian
sudo apt-get install php8.2-intl

# macOS
brew install php@8.2
```

## Métriques de couverture

Objectifs de couverture :
- ✅ **Ligne** : > 85%
- ✅ **Méthodes** : > 90%
- ✅ **Classes** : > 95%

Les fichiers exclus de la couverture :
- `src/DependencyInjection/`
- `src/KioraSyliusMondialRelayPlugin.php`

## Contribuer

Pour ajouter de nouveaux tests :

1. Créer le fichier de test dans le bon répertoire
2. Étendre `PHPUnit\Framework\TestCase`
3. Suivre les conventions de nommage
4. Documenter les cas de test complexes
5. Vérifier la couverture de code

## Ressources

- [Documentation PHPUnit 10](https://phpunit.de/documentation.html)
- [Symfony Testing](https://symfony.com/doc/current/testing.html)
- [Best Practices PHPUnit](https://phpunit.de/manual/current/en/writing-tests-for-phpunit.html)
