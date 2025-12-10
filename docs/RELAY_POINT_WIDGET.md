# Relay Point Selector Widget - Documentation

## Vue d'ensemble

Le widget de sélection de point relais Mondial Relay permet aux clients de choisir leur point de retrait préféré pendant le processus de checkout Sylius 2.

### Caractéristiques principales

- **Recherche intelligente** : Recherche automatique basée sur l'adresse de livraison
- **Vue double** : Liste et carte interactive (Leaflet.js)
- **Responsive** : Design mobile-first optimisé pour tous les écrans
- **Accessible** : Conforme WCAG 2.1 AA
- **Performant** : AJAX asynchrone, pas de rechargement de page
- **Multilingue** : Support français complet

## Architecture

### Stack technique

- **Backend** : PHP 8.2+, Symfony 7.2
- **Frontend** : Stimulus.js (Hotwired), Leaflet.js
- **CSS** : Bootstrap-compatible, mobile-first
- **API** : Mondial Relay REST API v2

### Composants principaux

```
kiora-sylius-mondial-relay-plugin/
├── src/
│   ├── Controller/Shop/
│   │   └── RelayPointController.php       # API AJAX endpoints
│   └── Twig/Hook/
│       └── RelayPointSelectorHook.php     # Sylius 2 Twig Hook
├── assets/
│   ├── controllers/
│   │   ├── relay-point-selector_controller.js  # Logique principale
│   │   └── relay-point-map_controller.js       # Carte Leaflet
│   └── styles/
│       └── relay-point-widget.css         # Styles du widget
├── templates/shop/checkout/
│   └── _relay_point_selector.html.twig    # Template principal
├── config/routes/
│   └── shop.yaml                          # Routes AJAX
└── translations/
    └── messages.fr.yaml                   # Traductions
```

## Installation

### 1. Prérequis

Le plugin doit être installé via Composer dans votre projet Sylius 2 :

```bash
composer require kiora/sylius-mondial-relay-plugin
```

### 2. Configuration des routes

Les routes shop sont automatiquement chargées via le plugin. Vérifiez dans `config/routes/`:

```yaml
# config/routes/kiora_sylius_mondial_relay_shop.yaml
kiora_sylius_mondial_relay_shop:
    resource: "@KioraSyliusMondialRelayPlugin/config/routes/shop.yaml"
    prefix: /shop
```

### 3. Importation des assets

#### Option A : Webpack Encore (recommandé)

Ajoutez dans votre `assets/app.js` :

```javascript
// Import Stimulus controllers
import './bootstrap';

// Import Leaflet
import L from 'leaflet';
window.L = L;

// Import Leaflet CSS
import 'leaflet/dist/leaflet.css';

// Import plugin assets
import '@kiora/sylius-mondial-relay-plugin/assets/controllers/relay-point-selector_controller';
import '@kiora/sylius-mondial-relay-plugin/assets/controllers/relay-point-map_controller';
import '@kiora/sylius-mondial-relay-plugin/assets/styles/relay-point-widget.css';
```

Installez les dépendances :

```bash
npm install leaflet @hotwired/stimulus --save
npm run dev
```

#### Option B : CDN (développement uniquement)

Ajoutez dans votre layout :

```twig
{# templates/bundles/SyliusShopBundle/layout.html.twig #}
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
```

### 4. Configuration Stimulus

Si Stimulus n'est pas déjà configuré, initialisez-le :

```javascript
// assets/bootstrap.js
import { startStimulusApp } from '@symfony/stimulus-bridge';

export const app = startStimulusApp(require.context(
    '@symfony/stimulus-bridge/lazy-controller-loader!./controllers',
    true,
    /\.[jt]sx?$/
));

// Register plugin controllers
import RelayPointSelectorController from '@kiora/sylius-mondial-relay-plugin/assets/controllers/relay-point-selector_controller';
import RelayPointMapController from '@kiora/sylius-mondial-relay-plugin/assets/controllers/relay-point-map_controller';

app.register('relay-point-selector', RelayPointSelectorController);
app.register('relay-point-map', RelayPointMapController);
```

## Utilisation

### Activation automatique

Le widget s'active automatiquement lors du checkout si :

1. Une méthode de livraison Mondial Relay est sélectionnée
2. Le code de la méthode commence par `mondial_relay_` ou contient "mondial" ou "relay"

### Hook Twig Sylius 2

Le widget s'intègre via le système de Twig Hooks de Sylius 2 :

```php
// src/Twig/Hook/RelayPointSelectorHook.php

#[AsHook(
    hook: 'sylius_shop.checkout.complete.shipments.form',
    template: '@KioraSyliusMondialRelayPlugin/shop/checkout/_relay_point_selector.html.twig',
    priority: 0
)]
```

### Personnalisation du hook

Pour modifier le point d'injection, éditez la configuration dans `config/services.yaml` :

```yaml
Kiora\SyliusMondialRelayPlugin\Twig\Hook\RelayPointSelectorHook:
    tags:
        - name: 'sylius.twig_hooks.hook_template'
          hook: 'sylius_shop.checkout.complete.shipments.form'  # Changez ici
          template: '@KioraSyliusMondialRelayPlugin/shop/checkout/_relay_point_selector.html.twig'
          priority: 10  # Augmentez pour afficher plus tôt
```

Hooks disponibles pour le checkout :
- `sylius_shop.checkout.complete.shipments.form` (par défaut)
- `sylius_shop.checkout.complete.summary`
- `sylius_shop.checkout.address.content`

## API AJAX

### Recherche de points relais

```
GET /checkout/mondial-relay/search
```

**Paramètres de requête :**

| Paramètre | Type | Requis | Description |
|-----------|------|--------|-------------|
| postalCode | string | Oui | Code postal de recherche |
| city | string | Non | Ville (optionnel) |
| countryCode | string | Oui | Code pays ISO (FR, BE, etc.) |
| latitude | float | Non | Latitude GPS (alternative au code postal) |
| longitude | float | Non | Longitude GPS |
| radius | int | Non | Rayon en mètres (défaut: 20000) |
| limit | int | Non | Nombre max de résultats (défaut: 20, max: 50) |

**Réponse (200 OK) :**

```json
{
  "success": true,
  "data": {
    "relayPoints": [
      {
        "relayPointId": "003499",
        "name": "TABAC LE CENTRAL",
        "address": {
          "street": "2 RUE DE LA LIBERTE",
          "postalCode": "75001",
          "city": "PARIS",
          "countryCode": "FR"
        },
        "coordinates": {
          "latitude": 48.8566,
          "longitude": 2.3522
        },
        "distanceMeters": 450,
        "distanceKm": 0.45,
        "openingHours": {
          "monday": [
            {"open": "09:00", "close": "12:00"},
            {"open": "14:00", "close": "18:30"}
          ],
          ...
        },
        "services": ["parking", "wheelchair_accessible"],
        "isActive": true
      }
    ],
    "total": 15,
    "searchCriteria": {
      "postalCode": "75001",
      "city": "Paris",
      "countryCode": "FR",
      "radius": 20000
    }
  }
}
```

**Réponse d'erreur (503) :**

```json
{
  "success": false,
  "error": {
    "message": "Impossible de rechercher les points relais. Veuillez réessayer.",
    "code": 3
  }
}
```

### Sélection d'un point relais

```
POST /checkout/mondial-relay/select/{shipmentId}
```

**Corps de la requête (JSON) :**

```json
{
  "relayPointId": "003499",
  "name": "TABAC LE CENTRAL",
  "street": "2 RUE DE LA LIBERTE",
  "postalCode": "75001",
  "city": "PARIS",
  "countryCode": "FR",
  "latitude": 48.8566,
  "longitude": 2.3522,
  "openingHours": { ... }
}
```

**Réponse (200 OK) :**

```json
{
  "success": true,
  "data": {
    "shipmentId": 42,
    "relayPointId": "003499",
    "message": "Point relais sélectionné avec succès."
  }
}
```

## Personnalisation

### Styles CSS

Le widget utilise des classes CSS préfixées `mr-*` pour éviter les conflits.

Pour personnaliser les styles, créez votre fichier CSS :

```css
/* assets/styles/custom-relay-widget.css */

/* Changer la couleur primaire */
.mr-widget {
    --primary-color: #007bff;
    --success-color: #28a745;
}

/* Personnaliser le bouton de sélection */
.mr-select-button {
    background-color: var(--primary-color);
    border-radius: 8px;
    font-weight: 600;
}

/* Modifier la hauteur de la carte */
.mr-map-container {
    height: 600px;
}
```

### Traductions

Ajoutez vos traductions dans `translations/messages.fr.yaml` :

```yaml
kiora_sylius_mondial_relay:
    widget:
        title: "Mon titre personnalisé"
        description: "Ma description"
```

### Template Twig

Pour override le template, créez :

```twig
{# templates/bundles/KioraSyliusMondialRelayPlugin/shop/checkout/_relay_point_selector.html.twig #}

{% extends '@!KioraSyliusMondialRelayPlugin/shop/checkout/_relay_point_selector.html.twig' %}

{% block widget_header %}
    {# Personnalisez l'en-tête #}
    <h2>Mon en-tête personnalisé</h2>
{% endblock %}
```

### Contrôleur Stimulus

Étendez le contrôleur pour ajouter des fonctionnalités :

```javascript
// assets/controllers/custom-relay-selector_controller.js
import RelayPointSelectorController from '@kiora/sylius-mondial-relay-plugin/assets/controllers/relay-point-selector_controller';

export default class extends RelayPointSelectorController {
    connect() {
        super.connect();
        // Votre logique personnalisée
        console.log('Widget personnalisé chargé');
    }

    async selectRelayPoint(event) {
        // Logique avant sélection
        const confirmed = confirm('Confirmer la sélection ?');
        if (!confirmed) return;

        // Appeler la méthode parente
        await super.selectRelayPoint(event);

        // Logique après sélection
        this.trackSelection();
    }

    trackSelection() {
        // Tracking analytics
        if (window.gtag) {
            gtag('event', 'relay_point_selected', {
                relay_point_id: this.state.selectedRelayPoint.relayPointId
            });
        }
    }
}
```

Enregistrez votre contrôleur personnalisé :

```javascript
// assets/bootstrap.js
import CustomRelaySelectorController from './controllers/custom-relay-selector_controller';
app.register('relay-point-selector', CustomRelaySelectorController);
```

## Événements JavaScript

Le widget émet des événements personnalisés :

### relay-point-selector:relay-points-updated

Émis quand les points relais sont mis à jour après une recherche.

```javascript
document.addEventListener('relay-point-selector:relay-points-updated', (event) => {
    const { relayPoints, searchCriteria } = event.detail;
    console.log(`${relayPoints.length} points relais trouvés`);
});
```

### relay-point-selector:relay-point-selected

Émis quand un point relais est sélectionné avec succès.

```javascript
document.addEventListener('relay-point-selector:relay-point-selected', (event) => {
    const { relayPoint } = event.detail;
    console.log('Point relais sélectionné:', relayPoint.name);

    // Tracking analytics
    trackEvent('relay_point_selected', {
        id: relayPoint.relayPointId,
        distance: relayPoint.distanceKm
    });
});
```

### relay-point-map:marker-clicked

Émis quand un marqueur est cliqué sur la carte.

```javascript
document.addEventListener('relay-point-map:marker-clicked', (event) => {
    const { relayPoint } = event.detail;
    console.log('Marqueur cliqué:', relayPoint.name);
});
```

## Accessibilité

Le widget respecte les normes WCAG 2.1 AA :

- **Navigation au clavier** : Tous les éléments interactifs sont accessibles au clavier
- **Lecteurs d'écran** : Labels et ARIA attributes appropriés
- **Contrastes** : Ratios de contraste conformes (4.5:1 minimum)
- **États de focus** : Indicateurs visuels clairs
- **Messages dynamiques** : ARIA live regions pour les mises à jour

### Attributs ARIA utilisés

```html
<!-- Liste des points relais -->
<div role="list" aria-label="Liste des points relais Mondial Relay">
    <div role="listitem">...</div>
</div>

<!-- Carte interactive -->
<div role="region" aria-label="Carte des points relais Mondial Relay">
    ...
</div>

<!-- Messages d'erreur -->
<div role="alert" aria-live="assertive">
    Erreur de recherche
</div>

<!-- Indicateur de chargement -->
<div aria-live="polite">
    <span class="sr-only">Chargement en cours...</span>
</div>
```

## Tests

### Tests unitaires PHP

```bash
vendor/bin/phpunit tests/Controller/Shop/RelayPointControllerTest.php
```

### Tests fonctionnels

```bash
vendor/bin/behat features/shop/checkout/relay_point_selection.feature
```

### Tests JavaScript

```bash
npm run test:js
```

## Dépannage

### Le widget ne s'affiche pas

1. Vérifiez que la méthode de livraison est bien de type Mondial Relay
2. Vérifiez les logs : `var/log/dev.log` ou `var/log/prod.log`
3. Inspectez la console JavaScript pour les erreurs

### La carte ne s'affiche pas

1. Vérifiez que Leaflet est bien chargé : `console.log(window.L)`
2. Vérifiez le CSS Leaflet : `<link rel="stylesheet" href="...leaflet.css" />`
3. Vérifiez les erreurs de console

### Les points relais ne se chargent pas

1. Testez l'API directement : `/checkout/mondial-relay/search?postalCode=75001&countryCode=FR`
2. Vérifiez les credentials API dans `.env`
3. Vérifiez les logs API : `var/log/mondial_relay.log`

### Erreur 403 lors de la sélection

1. Vérifiez que le shipment appartient bien à l'utilisateur
2. Vérifiez le token de session : `_sylius_cart_token`
3. Vérifiez les permissions dans le contrôleur

## Performance

### Optimisations implémentées

- **Cache API** : Réponses cachées pendant 15 minutes
- **Lazy loading** : Carte chargée uniquement si affichée
- **Debouncing** : Recherche avec délai de 300ms
- **Pagination** : Limite de 50 résultats max
- **Compression** : Assets minifiés en production

### Métriques cibles

- Temps de réponse API : < 500ms
- First Contentful Paint : < 1.5s
- Time to Interactive : < 3s
- Lighthouse Performance : > 90

## Support

Pour toute question ou problème :

- **Documentation** : [docs/](.)
- **Issues** : [GitHub Issues](https://github.com/kiora-tech/sylius-mondial-relay-plugin/issues)
- **Support** : support@kiora.tech

## License

MIT License - voir [LICENSE](../LICENSE)
