# Plateforme de gestion du patrimoine de l'État togolais

Solution numérique de recensement, suivi, affectation, maintenance, inventaire et sortie des biens publics de l'État togolais.

## Architecture

Micro-services Laravel (PHP 8.3+) + PostgreSQL 16 + RabbitMQ + Redis, orchestrés par Docker Compose, avec API Gateway Nginx et interface web (Inertia.js / Vue 3 / Tailwind CSS).

```
Navigateur ──▶ api-gateway (Nginx) ──▶ web-app (Inertia/Vue)
                                    └──▶ auth / organisation / asset / movement /
                                         inventory / maintenance / valuation /
                                         document / notification / reporting
                                              │                    │
                                        PostgreSQL (1 base/service)  RabbitMQ / Redis
```

## Structure du dépôt

```
docs/            Documentation par étape (analyse, UML, design, finances, fiche technique, guide)
services/        Un dossier par micro-service Laravel (étape 6)
frontend/        Application web Inertia/Vue (étape 6)
docker/          Configuration nginx, postgres init, etc.
docker-compose.yml
```

## Prérequis

- Docker Engine + Docker Compose v2
- 8 Go de RAM minimum recommandés pour l'ensemble des services

## Installation (une seule commande)

```bash
docker compose up -d --build
```

Puis exécuter les migrations et le seeding (voir `docs/06-guide-utilisation/` pour le détail).

## Comptes de démonstration

> En cours de définition — les comptes seront documentés à l'issue de l'initialisation des seeders (étape 6).

## Workflow Git

| Branche | Type | Statut | Contenu |
|---|---|---|---|
| `main` | permanente | stable | Production — fusion de `develop` par étape validée, avec tag de version |
| `develop` | permanente | intégration | Fusion des branches de fonctionnalités validées |
| `docs/analyse-besoin` | documentation | livrée | Étape 1 : analyse du besoin |
| `docs/conception-uml` | documentation | livrée | Étape 2 : diagrammes UML |
| `docs/design-figma` | documentation | livrée | Étape 3 : design UI/UX |
| `docs/proposition-financiere` | documentation | livrée | Étape 4 : chiffrage |
| `docs/fiche-technique` | documentation | livrée | Étape 5 : fiche technique |
| `docs/guide-utilisation` | documentation | livrée | Étape 7 : guide utilisateur |
| `feature/<service>-<fonctionnalité>` | fonctionnalité | à venir | Une branche = une fonctionnalité, PR vers `develop` |

Règles :

- Chaque fonctionnalité est développée sur sa propre branche `feature/<service>-<fonctionnalité>` créée depuis `develop`.
- Chaque branche fait l'objet d'une Pull Request vers `develop` ; aucun commit direct sur `main` ou `develop`.
- Après validation, merge dans `develop` (`--no-ff` ou squash) puis suppression de la branche.
- `develop` est fusionnée dans `main` à la fin de chaque étape complète, avec un tag `vX.Y.Z`.
- Commits : Conventional Commits, messages explicites, en français.

## Documentation

- [Analyse du besoin](docs/01-analyse-besoin/analyse-besoin.md) (étape 1, MD + PDF)
- [Conception UML](docs/02-conception-uml/README.md) (étape 2, 48 sources Mermaid + 96 exports SVG/PNG)
- [Design UI/UX](docs/03-design/) (étape 3, charte, wireframes, maquettes SVG, guide Figma)
- [Proposition financière](docs/04-proposition-financiere/) (étape 4, MD + PDF + Gantt)
- [Fiche technique](docs/05-fiche-technique/) (étape 5, MD + PDF)
- [Guide d'utilisation](docs/06-guide-utilisation/guide-utilisation.md) (étape 7, MD + PDF)

## Architecture logicielle

Micro-services Laravel 12 / PHP 8.3, un dossier par service dans `services/` :

| Service | Rôle | Base |
|---|---|---|
| `auth-service` | JWT, utilisateurs, RBAC, audit | auth_db |
| `organisation-service` | Ministères, directions, sites, catégories | organisation_db |
| `asset-service` | Biens, QR codes, statuts | asset_db |
| `movement-service` | Affectations, transferts, entrées/sorties, validations | movement_db |
| `inventory-service` | Campagnes, constats, écarts, réconciliation | inventory_db |
| `maintenance-service` | Plans, pannes, interventions, prestataires | maintenance_db |
| `valuation-service` | Amortissements, VNC, réévaluations | valuation_db |
| `document-service` | Documents, versions, fichiers | document_db |
| `notification-service` | Alertes in-app | notification_db |
| `reporting-service` | Dashboards, exports CSV/PDF | reporting_db |
| `frontend/` (web-app) | Interface Inertia.js + Vue 3 + Tailwind | — |

Passerelle Nginx (`api-gateway`) : routage `/api/v1/*`, rate limiting, point d'entrée unique.
Infrastructure : PostgreSQL 16, RabbitMQ, Redis — voir `docker-compose.yml`.

## Démarrage rapide

```bash
docker compose up -d --build
# Web : http://localhost — API : http://localhost/api-gateway/api/v1/
```

Comptes de démonstration (voir `docs/06-guide-utilisation/`) : admin@etat.tg, gestionnaire@etat.tg, agent@etat.tg, auditeur@etat.tg.

## Licence

Projet interne — État togolais. Tous droits réservés.
