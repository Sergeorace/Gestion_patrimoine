# Fiche technique — Plateforme de gestion du patrimoine de l'État togolais

| | |
|---|---|
| **Document** | Fiche technique (Étape 5) |
| **Version** | 1.0 |
| **Date** | 12 novembre 2026 |
| **Statut** | Validé pour développement |

---

## 1. Vue d'ensemble

| Élément | Spécification |
|---|---|
| Nom | Plateforme de gestion du patrimoine de l'État togolais |
| Type | Application web responsive (PWA pour l'inventaire) + API REST |
| Architecture | Micro-services, API Gateway, REST synchrone + événements asynchrones |
| Langage / framework | PHP 8.3+ / Laravel 12 |
| Interface | Inertia.js, Vue 3, Tailwind CSS, Chart.js |
| Base de données | PostgreSQL 16 — une base logique par service |
| Messagerie / cache | RabbitMQ 4, Redis 7 |
| Conteneurisation | Docker + Docker Compose (images Alpine multi-étapes) |
| Authentification | JWT RS256, 2FA TOTP, RBAC granulaire |
| Documentation API | OpenAPI 3 (Scramble) |
| Qualité / CI | Git, PHPUnit/Pest, PHPStan, Laravel Pint, pipeline CI |
| Exports | PDF (DomPDF), Excel/CSV (maatwebsite/excel) |
| Langue interface | Français |

## 2. Architecture micro-services

### 2.1 Découpage (database per service)

| Service | Responsabilité | Base logique | Port interne |
|---|---|---|---|
| api-gateway | Routage, validation JWT, rate limiting, CORS, TLS | — | 80/443 |
| auth-service | Utilisateurs, rôles, permissions, JWT, 2FA | auth_db | 8001 |
| organisation-service | Structures, sites, localisations, nomenclatures | organisation_db | 8002 |
| asset-service | Fiches biens, QR codes, import | asset_db | 8003 |
| movement-service | Entrées, affectations, transferts, sorties, workflows | movement_db | 8004 |
| inventory-service | Campagnes, constats, écarts, récolement | inventory_db | 8005 |
| maintenance-service | Plans, interventions, pannes, prestataires | maintenance_db | 8006 |
| valuation-service | Amortissements, VNC, réévaluations | valuation_db | 8007 |
| document-service | GED, versionnage, stockage fichiers | document_db + volume | 8008 |
| notification-service | Notifications, journal d'audit | notification_db | 8009 |
| reporting-service | Tableaux de bord, états, exports | reporting_db (agrégats) | 8010 |
| web-app | Interface Inertia/Vue | — (consomme les API) | 3000 |

Le découpage détaillé, les diagrammes de classes, de composants et de déploiement sont dans `docs/02-conception-uml/`.

### 2.2 Communication inter-services

**Synchrone — REST/JSON**

- Préfixe `/api/v1/` versionné sur tous les services.
- Auth : en-tête `Authorization: Bearer <JWT>` ; le gateway vérifie la signature (clé publique RS256) et le `scope` avant de router.
- Contrats : spécification OpenAPI par service, générée depuis les Form Requests (Scramble) et publiée sur `/docs` interne.
- Références inter-services par **identifiants fonctionnels** (UUID) — pas de clé étrangère inter-bases (intégrité applicative contrôlée par les API).

**Asynchrone — événements RabbitMQ**

| Événement | Producteur | Consommateurs |
|---|---|---|
| `bien.enregistre` | asset-service | notification, reporting |
| `bien.affecte` / `bien.sorti` | movement-service | notification, reporting, valuation |
| `inventaire.cloture` | inventory-service | notification, reporting |
| `maintenance.echeance` | maintenance-service (scheduler) | notification |
| `maintenance.cloturee` | maintenance-service | notification, reporting, asset |
| `utilisateur.connecte` | auth-service | notification (audit) |

- Exchanges : `patrimoine.events` (topic), files durables par consommateur, accusés manuels.
- **Idempotence** : chaque événement porte un `event_id` UUID ; les consommateurs dédupliquent via table `traites_evenements`.
- **Retry** : 3 tentatives exponentielles puis dead-letter queue `*.dlq` + alerte supervision.

**Cohérence** : cohérence à terme entre services. Les workflows critiques (affectation, sortie) sont orchestrés par movement-service qui appelle les API métier de façon synchrone pour l'effet immédiat, puis publie l'événement pour les traitements différés.

### 2.3 Gestion des erreurs

- Format d'erreur uniforme : `{ "error": { "code": "ASSET_NOT_FOUND", "message": "...", "details": {...} } }` avec codes HTTP standards.
- Exceptions métier mappées sur des classes dédiées (`DomainException`) → codes stables pour le frontend.
- Timeouts inter-services : 5 s ; circuit breaker simple (compteur d'échecs → 503 rapide pendant 30 s).
- Logs structurés JSON (Monolog) avec `trace_id` propagé (en-tête `X-Request-Id`) pour le suivi de bout en bout.

## 3. Stack technique et justification

| Couche | Choix | Version | Justification |
|---|---|---|---|
| Runtime | PHP | 8.3+ (8.4 recommandé) | Exigence CDC ; typage strict, performances |
| Framework | Laravel | 12.x stable | CDC ; écosystème, Eloquent, queues, sécurité |
| Frontend | Inertia.js + Vue 3 + Tailwind | Inertia 2 / Vue 3.5 / TW 4 | SPA sans duplication d'API pour le web ; SSR optionnel |
| Graphiques | Chart.js via `vue-chartjs` | 4.x | Légers, interactifs, exportables |
| PWA inventaire | Workbox / vite-plugin-pwa | — | Hors-ligne + sync pour terrain |
| BDD | PostgreSQL | 16 | CDC ; JSONB, robustesse, une base par service |
| Cache/files | Redis | 7 | Sessions, cache, queues Laravel, rate limiting |
| Messagerie | RabbitMQ | 4.x (management) | CDC ; topic exchanges, DLQ, management UI |
| Proxy | Nginx | alpine | CDC ; TLS, routage, rate limiting |
| PDF | DomPDF / barryvdh | 3.x | Génération d'états côté reporting-service |
| Excel/CSV | maatwebsite/excel | 3.x | Imports/exports |
| QR codes | simplesoftwareio/simple-qrcode | — | Étiquettes inventaire |
| Tests | PHPUnit + Pest | 3.x | CDC |
| Qualité | PHPStan (level 6), Pint | — | CDC |
| API docs | Scramble (OpenAPI) | — | Génération depuis le code |

### Authentification — décision d'implémentation

Le CDC mentionne « Laravel Passport/JWT ». Décision retenue :

- **auth-service** émet des **JWT signés RS256** (paquet `lcobucci/jwt`), access token 15 min + refresh token 7 jours (rotation à chaque usage).
- **Clés** : paire RSA générée au déploiement, clé privée dans auth-service (`.env`), clé publique distribuée aux autres services via variable d'environnement ou endpoint `/.well-known/jwks.json`.
- **2FA** : TOTP (RFC 6238) pour les rôles sensibles (admin, gestionnaire central, comptable).
- **RBAC** : tables `roles`/`permissions` en base ; le JWT embarque `permissions[]` et `structure_id` (périmètre RG-06) ; chaque service revérifie le périmètre sur les ressources.
- Mot de passe : `bcrypt` (coût 12) ; verrouillage 5 échecs / 15 min ; politique : 12 caractères min, complexité.

## 4. Modèle de données

- **Un schéma par service** : `auth_db`, `organisation_db`, `asset_db`, `movement_db`, `inventory_db`, `maintenance_db`, `valuation_db`, `document_db`, `notification_db`, `reporting_db` — créés par `docker/postgres/init.sql` au premier démarrage.
- **Clés primaires UUID** (v7 pour tri chronologique) ; `created_at`/`updated_at` partout ; **soft delete** (`deleted_at`) sur les entités métier.
- **Types** : `jsonb` pour caractéristiques variables des biens ; `numeric(15,2)` pour les montants ; `timestamptz` pour les horodatages.
- **Index** sur colonnes de recherche : `code_inventaire`, `structure_id`, `statut`, `date_acquisition`, `email`.
- **Contraintes** : unicité `code_inventaire` et `immatriculation`, checks `valeur_acquisition >= 0`, `vnc >= 0`.
- **Migrations + seeders** Laravel versionnés par service ; seeders de démonstration réalistes (structures togolaises, catégories, ~200 biens).
- **Pas de FK inter-bases** : références fonctionnelles UUID contrôlées applicativement.

Le MLD complet est dans `docs/02-conception-uml/sources/donnees/`.

## 5. Sécurité

| Domaine | Mesure |
|---|---|
| Transport | HTTPS/TLS obligatoire en production (Nginx + certbot ou certificat fourni) ; HSTS ; redirection 80→443 |
| AuthN | JWT RS256 15 min + refresh rotatif ; 2FA TOTP rôles sensibles ; verrouillage après 5 échecs |
| AuthZ | RBAC par permission + cloisonnement par `structure_id` (RG-06) ; policies Laravel par ressource |
| Injection | Eloquent/Query Builder uniquement ; validation Form Requests stricte ; jamais de SQL concaténé |
| XSS | Échappement Blade/Vue par défaut ; CSP stricte ; `{{ }}` uniquement |
| CSRF | Jeton de session Inertia + SameSite=Strict sur cookies |
| Fichiers | Whitelist MIME (pdf, jpg, png, xlsx, csv), taille max 10 Mo, scan nom (pas de chemin), stockage hors webroot, hash SHA-256 |
| Rate limiting | Gateway : 100 req/min/IP anonyme, 300/min authentifié ; endpoints login : 5/min |
| Secrets | `.env` non versionnés ; secrets Docker hors images ; rotation documentée |
| Conteneurs | Images Alpine minimales, `USER` non-root, healthchecks, read-only rootfs où possible |
| Audit | `audit_logs` immuable applicativement (append-only) pour actions sensibles (RG-07) |
| Données personnelles | Minimisation ; hachage mots de passe ; conformité loi togolaise protection données |

## 6. Sauvegardes et reprise

- **Fréquence** : `pg_dump` quotidien par base (script `docker/scripts/backup.sh`), rétention 30 jours glissants + archive mensuelle 12 mois.
- **Stockage** : volume dédié `backups` + option `pg_dump | gzip | aws s3` (ou S3-compatible) pour externalisation.
- **Documents** : volume `documents` sauvegardé avec les dumps (rsync incrémental).
- **Test de restauration** : procédure mensuelle documentée (`restore.sh`) et testée en recette — critère d'acceptation CDC.
- **RPO 24 h / RTO 4 h** indicatifs pour la production.

## 7. Supervision et exploitation

| Besoin | Solution |
|---|---|
| Healthchecks | `HEALTHCHECK` Docker sur chaque service (`/health` Laravel, `pg_isready`, `rabbitmq-diagnostics`) |
| Logs | JSON stdout → driver `json-file` avec rotation (10 Mo × 5) ; `docker compose logs` |
| Métriques | Endpoint `/metrics` (Prometheus) par service — phase 2 : stack Prometheus/Grafana |
| Alertes | Notification-service consomme événements + watchdog planifié (échéances, échecs DLQ) |
| Traçabilité | `X-Request-Id` propagé gateway → services → logs → audit |
| Supervisor | `restart: unless-stopped` partout ; depends_on `service_healthy` |

## 8. Exigences d'infrastructure

### Production (recommandé, CDC §14.1)

| Ressource | Dimensionnement |
|---|---|
| Serveur applicatif | 8 vCPU, 16 Go RAM, 200 Go SSD — Linux + Docker Engine |
| Stockage / sauvegardes | Volume ≥ 500 Go, sauvegarde externalisée |
| Réseau | IP publique ou intranet, HTTPS, nom de domaine |
| OS | Debian 12 / Ubuntu 22.04 LTS |

### Recette / développement

| Environnement | Dimensionnement |
|---|---|
| Recette | 4 vCPU, 8 Go RAM |
| Dev local | Docker Desktop, 4 Go RAM alloués |

## 9. Stratégie de déploiement

1. **Dev local** : `docker compose up -d --build` — réseau `patrimoine-net`, volumes `pgdata`, `documents`, `backups`, `redis-data`, `rabbitmq-data`.
2. **Initialisation BDD** : `docker/postgres/init.sql` crée les 10 bases au 1er démarrage ; `php artisan migrate --seed` par service (cible : tous les services).
3. **Recette** : `docker-compose.recette.yml` ( surcharge : domaines, TLS, ressources).
4. **Production** : `docker-compose.prod.yml` + secrets via `.env` ; images taguées `vX.Y.Z` ; migration planifiée avec sauvegarde préalable ; retour arrière = image N-1 + restauration dump si nécessaire.
5. **Mise à jour** : `git pull` → `docker compose build <service>` → `up -d` → migrations → smoke tests.

## 10. Stratégie de tests

| Type | Outil | Couverture cible |
|---|---|---|
| Unitaires | PHPUnit/Pest | ≥ 70 % logique métier (CDC) |
| Feature par service | Pest HTTP tests | CRUD, validation, autorisation, périmètre |
| Intégration | Tests cross-services (gateway → service → DB) | Contrats API, événements RabbitMQ |
| Contrats API | Pest + collections OpenAPI | Codes réponse, schémas |
| E2E | Playwright (recette) | Parcours : login, création bien, affectation, inventaire, maintenance, sortie, rapport |
| Sécurité | Tests 403/404, rate limit, validation fichiers | OWASP de base |
| Performance | k6 (recette) | < 2 s pour 95 % requêtes courantes |

CI : à chaque push — `composer install`, Pint, PHPStan, PHPUnit par service, build des images Docker.

## 11. Limites connues et évolutions

- Interconnexion finances publiques : non prévue (phase ultérieure via API REST déjà versionnée).
- Application mobile native : non prévue ; la PWA couvre le besoin inventaire.
- Multi-instance : les services sont sans état (sessions Redis) → scalabilité horizontale possible ; reporting-service pourra consommer un read replica si volume élevé.

---

*Document de référence pour l'équipe de développement — Étape 5.*
