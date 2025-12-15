# Protocole de Test - Kiora Sylius Mondial Relay Plugin

Ce document décrit les étapes pour tester le plugin avant une mise en production.

## Prérequis

### Credentials de Test

#### SOAP API v1 (Recherche points relais)
```
Enseigne: BDTEST13
Code Marque: 11
Clé privée: TestAPI1key
```

#### REST API v2 Connect (Expéditions)
> ⚠️ Demander des credentials sandbox à Mondial Relay via votre espace Connect ou support technique.

### Configuration Admin

1. Aller dans **Admin > Configuration > Mondial Relay**
2. Activer le **Mode Sandbox**
3. Renseigner les credentials SOAP (BDTEST13)
4. Renseigner les credentials REST API v2 (si disponibles)
5. Sauvegarder et tester la connexion

---

## Tests Fonctionnels

### 1. Configuration Admin

| Test | Étapes | Résultat attendu |
|------|--------|------------------|
| Accès page config | Aller à `/admin/mondial-relay/configuration` | Page affichée avec formulaire |
| Sauvegarde config | Remplir et sauvegarder | Message "Configuration sauvegardée" |
| Test connexion SOAP | Cliquer "Tester la connexion" | Message de succès |
| Validation champs | Soumettre formulaire vide | Erreurs de validation |

### 2. Recherche Points Relais (SOAP API)

| Test | Étapes | Résultat attendu |
|------|--------|------------------|
| Recherche par CP | Appeler `/checkout/mondial-relay/search?postalCode=75002&countryCode=FR` | Liste de points relais JSON |
| Recherche par GPS | Appeler avec `latitude=48.8566&longitude=2.3522` | Liste triée par distance |
| CP invalide | Rechercher avec `postalCode=00000` | Erreur ou liste vide |
| Pays non couvert | Rechercher avec `countryCode=US` | Erreur appropriée |
| Limite résultats | Ajouter `limit=5` | Maximum 5 résultats |
| Rayon recherche | Ajouter `radius=5000` (5km) | Points dans le rayon |

#### Commande de test CLI
```bash
# Test direct du SOAP client
docker-compose exec php php bin/console debug:container MondialRelaySoapClient
```

### 3. Sélection Point Relais (Checkout)

| Test | Étapes | Résultat attendu |
|------|--------|------------------|
| Affichage widget | Aller au checkout avec méthode Mondial Relay | Widget de sélection visible |
| Sélection point | Cliquer sur un point relais | Point sélectionné, formulaire mis à jour |
| Persistance | Rafraîchir la page | Point relais toujours sélectionné |
| Validation | Finaliser commande sans sélection | Erreur "Veuillez sélectionner un point relais" |

### 4. Stockage Point Relais

| Test | Étapes | Résultat attendu |
|------|--------|------------------|
| Entité créée | Sélectionner un point relais | `MondialRelayPickupPoint` créé en BDD |
| Association shipment | Vérifier la commande | `Shipment.mondialRelayPickupPoint` renseigné |
| Données complètes | Vérifier l'entité | Nom, adresse, coordonnées GPS stockés |

#### Vérification SQL
```sql
SELECT * FROM mondial_relay_pickup_point ORDER BY id DESC LIMIT 5;
SELECT s.id, s.mondial_relay_pickup_point_id, p.name, p.relay_point_id
FROM sylius_shipment s
LEFT JOIN mondial_relay_pickup_point p ON s.mondial_relay_pickup_point_id = p.id
WHERE s.mondial_relay_pickup_point_id IS NOT NULL;
```

### 5. Affichage Admin Commande

| Test | Étapes | Résultat attendu |
|------|--------|------------------|
| Info point relais | Voir détail commande avec Mondial Relay | Bloc "Point Relais" affiché |
| Données affichées | Vérifier le bloc | Nom, adresse, ID point relais |
| Bouton QR code | Si pas de QR code | Bouton "Générer QR Code" visible |
| QR code existant | Si QR code généré | Image QR code + bouton télécharger |

### 6. Génération QR Code

| Test | Étapes | Résultat attendu |
|------|--------|------------------|
| Génération | Cliquer "Générer QR Code" | QR code créé et affiché |
| Fichier créé | Vérifier `/public/media/mondial_relay/qrcodes/` | Fichier PNG présent |
| Contenu QR | Scanner le QR code | Données du point relais encodées |
| Téléchargement | Cliquer "Télécharger" | Fichier PNG téléchargé |

### 7. Création Expédition (REST API v2)

> ⚠️ Nécessite credentials REST API v2 sandbox

| Test | Étapes | Résultat attendu |
|------|--------|------------------|
| Création | Appeler `createShipment()` | Réponse avec numéro expédition |
| Numéro tracking | Vérifier la réponse | `expeditionNumber` renseigné |
| URL étiquette | Vérifier la réponse | `labelUrl` valide |
| Erreur validation | Envoyer données invalides | Exception avec message clair |

### 8. Téléchargement Étiquette

| Test | Étapes | Résultat attendu |
|------|--------|------------------|
| Téléchargement | Appeler `getLabel()` | PDF retourné |
| Format | Vérifier le fichier | PDF valide, lisible |
| Sauvegarde | Utiliser `saveToFile()` | Fichier créé sur disque |

---

## Tests d'Intégration

### Parcours Complet Client

1. **Ajouter produit au panier**
2. **Checkout - Adresse** : Renseigner adresse de livraison
3. **Checkout - Livraison** : Sélectionner "Mondial Relay"
4. **Widget points relais** : Vérifier affichage carte/liste
5. **Sélection** : Choisir un point relais
6. **Checkout - Paiement** : Finaliser le paiement
7. **Confirmation** : Vérifier que le point relais est affiché
8. **Admin** : Vérifier la commande côté admin

### Parcours Admin

1. **Liste commandes** : Filtrer par méthode Mondial Relay
2. **Détail commande** : Voir infos point relais
3. **Générer QR** : Créer le QR code
4. **Télécharger** : Récupérer le QR code

---

## Tests de Régression

### Après mise à jour Sylius

- [ ] Routes chargées (`debug:router | grep mondial`)
- [ ] Services disponibles (`debug:container | grep MondialRelay`)
- [ ] Twig hooks actifs (`debug:config sylius_twig_hooks`)
- [ ] Migrations appliquées (`doctrine:migrations:status`)
- [ ] Assets compilés (si JS/CSS modifiés)

### Après mise à jour Plugin

```bash
# Vérifier la version
composer show kiora/sylius-mondial-relay-plugin

# Vider le cache
bin/console cache:clear

# Vérifier les routes
bin/console debug:router | grep mondial

# Vérifier les services
bin/console debug:container MondialRelay
```

---

## Environnements

### Local (Docker)

```bash
# Démarrer l'environnement
docker-compose up -d

# Accéder au conteneur PHP
docker-compose exec php bash

# Lancer les tests
bin/console cache:clear
```

### Staging

- URL : `https://staging.example.com`
- Mode sandbox activé
- Credentials de test configurés

### Production

- URL : `https://www.example.com`
- Mode sandbox **désactivé**
- Credentials production configurés
- Vérifier les logs après déploiement

---

## Checklist Pré-Production

- [ ] Tests fonctionnels passés en staging
- [ ] Credentials production configurés
- [ ] Mode sandbox désactivé
- [ ] Logs Mondial Relay configurés (monolog)
- [ ] Monitoring/alerting en place
- [ ] Documentation utilisateur à jour
- [ ] Support informé du déploiement

---

## Dépannage

### Erreur SOAP "Could not connect to host"

```bash
# Vérifier l'extension SOAP
php -m | grep soap

# Tester la connectivité
curl -I https://api.mondialrelay.com/Web_Services.asmx?WSDL
```

### Erreur "Invalid credentials"

1. Vérifier les credentials dans Admin
2. Vérifier le mode sandbox (credentials test vs prod)
3. Consulter les logs : `var/log/mondial_relay_*.log`

### Points relais non affichés

1. Vérifier la console JS (erreurs réseau)
2. Tester l'endpoint directement : `/checkout/mondial-relay/search?postalCode=75002&countryCode=FR`
3. Vérifier les logs serveur

### QR Code non généré

1. Vérifier les permissions sur `/public/media/mondial_relay/qrcodes/`
2. Vérifier que `endroid/qr-code` est installé
3. Consulter les logs PHP

---

## Contacts

- **Support Mondial Relay** : support technique via espace Connect
- **Documentation API** : https://www.mondialrelay.fr/solutionspro/documentation-technique/
- **Issues Plugin** : https://github.com/kiora-tech/sylius-mondial-relay-plugin/issues
