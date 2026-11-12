# Analyse du besoin — Plateforme de gestion du patrimoine de l'État togolais

| | |
|---|---|
| **Projet** | Solution numérique de gestion du patrimoine de l'État togolais |
| **Document** | Analyse du besoin (Étape 1) |
| **Version** | 1.0 |
| **Date** | 12 novembre 2026 |
| **Statut** | Soumis à validation du maître d'ouvrage |
| **Sources** | Cahier des charges v1.0 (CHAOLD Komi Serge, 12/11/2026) |

---

## 1. Contexte

La gestion du patrimoine de l'État togolais — terrains, bâtiments, véhicules, mobilier, matériel informatique et équipements — repose aujourd'hui sur des supports dispersés : registres papier, fichiers bureautiques et états isolés tenus par chaque administration. Cette fragmentation génère :

- l'absence de vue consolidée et fiable du patrimoine national ;
- des données redondantes, obsolètes ou contradictoires selon les structures ;
- une traçabilité insuffisante des affectations, transferts et sorties de biens ;
- des campagnes d'inventaire longues, coûteuses et difficilement comparables d'une année à l'autre ;
- une difficulté à évaluer la valeur du patrimoine et à planifier maintenance et renouvellement ;
- une production laborieuse des rapports destinés aux décideurs et aux organes de contrôle.

Le projet consiste à concevoir et développer une **plateforme numérique centralisée, sécurisée et évolutive** permettant de recenser, suivre, valoriser et contrôler l'ensemble des biens de l'État, de leur acquisition jusqu'à leur sortie du patrimoine.

## 2. Objectifs

### 2.1 Objectif général

Doter l'État togolais d'une plateforme numérique unique de gestion du patrimoine, fiable, traçable et accessible aux structures concernées.

### 2.2 Objectifs spécifiques

1. **Centraliser** le recensement de tous les biens meubles et immeubles dans un référentiel unique.
2. **Tracer** le cycle de vie complet de chaque bien : entrée, affectation, transfert, maintenance, sortie.
3. **Faciliter** les campagnes d'inventaire et de récolement, y compris par scan de codes-barres/QR codes, avec tolérance aux coupures de connexion.
4. **Valoriser** les biens : valeur d'acquisition, amortissements, valeur nette comptable (VNC).
5. **Produire** des tableaux de bord et états réglementaires et de gestion en temps réel.
6. **Renforcer** le contrôle interne, la transparence et la lutte contre les pertes ou détournements.
7. **Garantir** sécurité, disponibilité et évolutivité via une architecture micro-services conteneurisée.

## 3. Parties prenantes et acteurs

### 3.1 Parties prenantes

| Partie prenante | Rôle |
|---|---|
| Maître d'ouvrage (État togolais) | Commanditaire ; valide les livrables, arbitre les choix fonctionnels |
| Direction en charge du patrimoine | Utilisateur principal, référent métier, pilotage de la solution |
| Ministères et structures publiques | Utilisateurs pour la gestion et l'inventaire de leurs biens |
| Organes de contrôle et d'audit | Consultation, vérification et audit des données et de la traçabilité |
| CHAOLD Komi Serge (concepteur) | Chef de projet : analyse, conception UML, documentation, guide d'utilisation |

### 3.2 Acteurs de la solution

| Acteur | Description et responsabilités |
|---|---|
| Administrateur système | Utilisateurs, rôles, paramètres généraux, référentiels transverses |
| Gestionnaire central du patrimoine | Supervision de l'ensemble du patrimoine, validation des mouvements, consultation de tous les états |
| Responsable de structure | Gestion des biens de son ministère/direction/service, initiation des demandes d'affectation et de sortie |
| Agent inventorialiste | Inventaires et récolements sur le terrain (scan, saisie, constat d'état) |
| Comptable matières | Valorisation, amortissements, écritures de mouvement |
| Agent de maintenance | Planification et enregistrement des interventions |
| Auditeur / Contrôleur | Accès en lecture seule aux données, journaux d'audit et rapports |
| Décideur | Consultation des tableaux de bord synthétiques et indicateurs clés |

## 4. Besoins fonctionnels (priorisation MoSCoW)

La priorisation du cahier des charges (Essentielle / Haute / Moyenne) est traduite ci-dessous en MoSCoW.

| Réf. | Besoin | Priorité CDC | MoSCoW |
|---|---|---|---|
| BF-01 | Authentification sécurisée, comptes, rôles et permissions | Essentielle | **Must** |
| BF-02 | Organigramme des structures (ministères, directions, services) et sites/localisations | Essentielle | **Must** |
| BF-03 | Enregistrement, modification, consultation et classement des biens par catégorie/nomenclature | Essentielle | **Must** |
| BF-04 | Entrées de biens (acquisition, don, transfert, construction) avec pièces justificatives | Essentielle | **Must** |
| BF-05 | Affectations, mises à disposition et transferts entre structures avec workflow de validation | Essentielle | **Must** |
| BF-06 | Sorties de biens (réforme, cession, rebut, perte, vol, destruction) | Essentielle | **Must** |
| BF-07 | Campagnes d'inventaire et récolement avec scan codes-barres/QR codes | Essentielle | **Must** |
| BF-08 | Suivi de la valorisation et de l'amortissement des biens | Haute | **Must** |
| BF-09 | Planification et suivi de la maintenance préventive et corrective | Haute | **Must** |
| BF-10 | Gestion documentaire (titres, factures, PV, photos) | Haute | **Must** |
| BF-11 | Notifications (validations, échéances, alertes) et journal d'audit | Haute | **Must** |
| BF-12 | Tableaux de bord, états et exports (PDF, Excel/CSV) | Haute | **Must** |
| BF-13 | Import de données depuis fichiers structurés (Excel/CSV) | Moyenne | **Should** |
| BF-14 | Recherche et filtrage multicritères des biens | Haute | **Must** |

**Lecture MoSCoW :**

- **Must** : indispensable à la recette (12 besoins, BF-01 à BF-12 + BF-14).
- **Should** : important mais négociable en cas de tension sur le délai (BF-13, import).
- **Could / Won't (cette phase)** : application mobile native, interconnexion avec les systèmes de finances publiques, cartographie SIG, lecture RFID — voir §7.

### 4.1 Détail des modules fonctionnels

| Module | Contenu |
|---|---|
| Authentification et accès | Connexion identifiant/mot de passe, politique de mots de passe, 2FA pour rôles sensibles, RBAC, réinitialisation, verrouillage après échecs, expiration de sessions, jetons JWT/OAuth2 pour les services |
| Référentiel organisationnel | Hiérarchie des structures (ministère → direction → service → unité), sites et localisations (région, préfecture, commune, adresse, bâtiment, bureau), nomenclatures (catégories, états, modes d'acquisition, motifs de sortie), paramètres d'amortissement par catégorie |
| Gestion des biens | Fiche bien complète, spécificités par type (immobilier : superficie, référence cadastrale, coordonnées ; véhicule : immatriculation, châssis, kilométrage ; informatique : n° de série, configuration), génération de QR codes/étiquettes, recherche multicritère, import Excel/CSV avec rapport d'anomalies |
| Mouvements et affectations | Entrées, workflow d'affectation/mise à disposition/transfert (demande → validation → exécution), workflow de sortie (réforme, cession, rebut, perte, vol, destruction) avec justificatifs et PV, historique complet par bien |
| Inventaire et récolement | Campagnes par structure/site/catégorie avec équipes et calendrier, saisie terrain responsive/PWA (scan, constat, photos, commentaires), fonctionnement hors-ligne avec synchronisation, détection automatique des écarts, rapport de récolement, clôture et archivage |
| Maintenance | Plans préventifs (périodicité, alertes), déclaration de pannes, ordres d'intervention, suivi des coûts et prestataires, indicateurs de fiabilité |
| Valorisation et amortissement | Calcul automatique (linéaire, dégressif selon paramétrage), suivi valeur d'acquisition / amortissements cumulés / VNC, états par structure/catégorie/période, réévaluations |
| Gestion documentaire | Stockage sécurisé des pièces, association aux biens/mouvements/inventaires/interventions, contrôle formats et tailles, versionnage, horodatage |
| Notifications et audit | Notifications internes et e-mail, journal d'audit filtrable (qui, quoi, quand, avant/après) |
| Reporting | Tableau de bord national, états standards (registre, mouvements, inventaire, amortissements, maintenance), exports PDF/Excel/CSV, filtres dynamiques, graphiques interactifs |

### 4.2 Matrice des droits (synthèse du CDC)

| Fonction | Admin | Gest. central | Resp. struct. | Inventoriste | Auditeur | Décideur |
|---|---|---|---|---|---|---|
| Gestion des utilisateurs | CRUD | – | – | – | – | – |
| Référentiels | CRUD | CRUD | Lecture | Lecture | Lecture | Lecture |
| Fiches biens | Lecture | CRUD | CRUD (périm.) | Lecture | Lecture | Lecture |
| Validation des mouvements | – | Oui | Niveau 1 | – | – | – |
| Campagnes d'inventaire | – | Créer/clôturer | Créer (périm.) | Saisie | Lecture | Lecture |
| Valorisation | – | Oui | Lecture | – | Lecture | Lecture |
| Journal d'audit | Lecture | Lecture | – | – | Lecture | – |
| Tableaux de bord | Oui | Oui | Périm. | – | Oui | Oui |

## 5. Besoins non fonctionnels

| Catégorie | Exigence |
|---|---|
| Performance | Temps de réponse < 2 s pour 95 % des requêtes courantes ; pagination et indexation des listes volumineuses |
| Disponibilité | Objectif 99 % ; redémarrage automatique des conteneurs ; supervision des services |
| Scalabilité | Montée en charge horizontale par service ; ajout de modules sans refonte |
| Sécurité | Authentification forte, HTTPS/TLS, protection OWASP Top 10, journal d'audit complet |
| Fiabilité des données | Sauvegardes quotidiennes PostgreSQL, tests de restauration, intégrité référentielle |
| Utilisabilité | Interface claire et cohérente, parcours courts, aide contextuelle, messages d'erreur explicites en français |
| Compatibilité | Navigateurs récents (Chrome, Firefox, Edge, Safari) ; responsive ordinateur, tablette, mobile |
| Maintenabilité | Code structuré (PSR-12), tests automatisés, API versionnées, intégration continue |
| Portabilité | Déploiement identique sur tout hôte compatible Docker |
| Traçabilité | Toute modification de donnée sensible horodatée et rattachée à son auteur |
| Interopérabilité | API REST documentées (OpenAPI/Swagger) pour de futures intégrations |

## 6. Règles de gestion

| Réf. | Règle |
|---|---|
| RG-01 | Chaque bien possède un identifiant unique et un **code d'inventaire non réutilisable**, y compris après sortie |
| RG-02 | Un bien n'est affecté qu'à **une seule structure responsable** à un instant donné |
| RG-03 | Tout mouvement (affectation, transfert, sortie) fait l'objet d'une **demande, d'une validation par un rôle habilité** et d'un historique horodaté |
| RG-04 | Un bien sorti du patrimoine **n'est plus modifiable** mais reste consultable pour l'historique et l'audit |
| RG-05 | La **valeur nette comptable est calculée automatiquement** selon la méthode d'amortissement paramétrée par catégorie |
| RG-06 | Une structure ne peut consulter et gérer que les biens de **son périmètre**, sauf rôle central ou d'audit |
| RG-07 | Toute action sensible est enregistrée dans le **journal d'audit** (utilisateur, date, action, valeurs avant/après) |
| RG-08 | Une campagne d'inventaire **clôturée génère un rapport d'écarts** (biens manquants, excédentaires, dégradés) |

**États du cycle de vie d'un bien (extrait du CDC §6.4) :**

`Enregistré → Disponible → Affecté → En maintenance → Affecté (cycle) → Proposé à la sortie → Sorti (état final, non modifiable)`

## 7. Contraintes

| Type | Contrainte |
|---|---|
| Technique | Laravel (PHP 8.3+), PostgreSQL 16, Docker + Docker Compose, architecture micro-services |
| Conception | Modélisation UML obligatoire ; maquettes Figma (ou cahier de design si accès impossible) |
| Données | PostgreSQL **conteneurisé** dans `docker-compose.yml` (volumes persistants, healthcheck, variables via `.env`) |
| Interface | Langue française ; responsive ; **tolérance aux connectivités limitées** (PWA hors-ligne pour l'inventaire) |
| Documentation | Documentation complète et guide d'utilisation détaillé par profil |
| Déploiement | Un seul point d'entrée (API Gateway + reverse proxy Nginx) ; démarrage par `docker compose up -d --build` |
| Planning | Réalisation en **6 semaines**, méthode agile (sprints d'une semaine), équipe de 9 intervenants |

## 8. Architecture cible (vue issue du CDC §5)

Architecture **micro-services** : chaque service possède sa responsabilité, son Dockerfile et sa base PostgreSQL logique (« database per service »). Communication synchrone REST/JSON et asynchrone par bus de messages (RabbitMQ). Point d'entrée unique : API Gateway (Nginx) pour routage, authentification centralisée et rate limiting.

| Service | Responsabilité | Base |
|---|---|---|
| api-gateway | Routage, auth, rate limiting, agrégation, CORS (Nginx) | – |
| auth-service | Utilisateurs, rôles, permissions, JWT/OAuth2, 2FA | auth_db |
| organisation-service | Structures, sites, localisations, nomenclatures, paramètres | organisation_db |
| asset-service | Fiches biens, catégories, codes-barres/QR, import | asset_db |
| movement-service | Entrées, affectations, transferts, sorties, workflows | movement_db |
| inventory-service | Campagnes, constats, écarts, récolement | inventory_db |
| maintenance-service | Plans et ordres de maintenance, coûts, prestataires | maintenance_db |
| valuation-service | Valorisation, amortissements, réévaluations | valuation_db |
| document-service | Stockage et versionnage des pièces jointes | document_db + fichiers |
| notification-service | Notifications e-mail/internes, journal d'audit | notification_db |
| reporting-service | Tableaux de bord, états, exports PDF/Excel | reporting_db (lecture agrégée) |
| web-app | Interface utilisateur | – |

**Infrastructure :** PostgreSQL 16 (un conteneur, une base par service), RabbitMQ, Redis (cache/files), Nginx (reverse proxy/TLS), stockage objet ou volume Docker pour documents/photos.

**Stack :** PHP 8.3+ / Laravel (dernière stable), Inertia.js + Vue 3 + Tailwind CSS + Chart.js (PWA pour l'inventaire), PHPUnit/Pest, PHPStan, Pint, OpenAPI/Swagger (Scramble ou L5-Swagger), Git + CI.

## 9. Périmètre

### 9.1 Inclus

- Analyse du besoin et formalisation des exigences (ce document)
- Conception UML complète (cas d'utilisation, classes, séquences, activités, composants, déploiement, états-transitions)
- Maquettage/design Figma (ou cahier de design si accès impossible)
- Développement de la solution en micro-services Laravel
- PostgreSQL conteneurisé via Docker Compose
- Fiche technique, proposition financière, guide d'utilisation
- Tests, recette, déploiement, formation des utilisateurs clés

### 9.2 Exclus (sauf avenant)

- Acquisition du matériel d'hébergement (serveurs, réseau) et lecteurs de codes-barres
- Reprise massive de données non numérisées (saisie de registres papier) — **l'import de fichiers structurés Excel/CSV est inclus** (BF-13, Should)
- Application mobile native (interface web responsive/PWA prévue)
- Interconnexion avec des systèmes tiers non identifiés (finances publiques, etc.) — phase ultérieure

## 10. Risques et hypothèses

### 10.1 Risques majeurs (extrait du CDC §17)

| Risque | Niveau | Mesure de maîtrise |
|---|---|---|
| Dérive fonctionnelle (périmètre évolutif) | Élevé | Backlog priorisé MoSCoW, jalons de validation, gestion formelle des avenants |
| Qualité/exhaustivité des données initiales | Élevé | Modèles d'import, contrôles automatiques, rapports d'anomalies |
| Faille de sécurité | Élevé | Bonnes pratiques OWASP, revues, tests de sécurité, mises à jour |
| Perte de données | Élevé | Sauvegardes automatiques, volumes persistants, tests de restauration |
| Délai contraint (6 semaines) | Élevé | Équipe de 9 en parallèle, périmètre gelé S1, validations < 48 h, point quotidien |
| Complexité micro-services | Moyen | Socle commun, conventions strictes, contrats d'API, supervision |
| Connectivité limitée sur le terrain | Moyen | PWA tolérante aux coupures, synchronisation différée |
| Résistance au changement | Moyen | Formation, utilisateurs pilotes, communication |
| Retards de livraison | Moyen | Sprints hebdomadaires, suivi quotidien, marge ~15 % |

### 10.2 Hypothèses de travail

1. **Stack frontend** : le CDC impose Inertia.js + Vue 3 + Tailwind CSS (§5.5, §14). La consigne initiale autorisait Blade/Livewire ou SPA Vue/React : **nous retenons la stack du cahier des charges (Inertia + Vue 3)**, qui offre une SPA moderne sans API backend dédiée pour le web.
2. **Découpage des services** : la liste des services du CDC (§5.2, 12 services) affine la liste initiale à 9 services ; elle est retenue comme référence.
3. **JWT/OAuth2** : le CDC mentionne les deux ; nous prévoyons des **jetons JWT** (via Laravel Passport ou un service JWT léger) avec refresh tokens.
4. **Données de démonstration** : un jeu réaliste togolais (structures, catégories, biens types) sera produit en seeders.
5. **Hébergement** : le livrable démarre en local via `docker compose up -d --build` ; l'hébergement de production est hors périmètre.
6. **Figma** : en l'absence d'accès à Figma, un **cahier de design détaillé** (charte, wireframes ASCII, parcours, composants) sera produit à l'étape 3, importable ensuite dans Figma.
7. **PDF** : les documents PDF seront générés via une chaîne outillée (print CSS ou DomPDF côté reporting-service pour les exports métier).

## 11. Critères d'acceptation de l'analyse

- [ ] Acteurs, besoins et règles de gestion compris et validés par le maître d'ouvrage
- [ ] Priorisation MoSCoW arrêtée (base du backlog des sprints)
- [ ] Périmètre inclus/exclus confirmé
- [ ] Hypothèses documentées et non contestées

---

*Document rédigé dans le cadre de l'étape 1 « Analyse du besoin » du projet de gestion du patrimoine de l'État togolais.*
