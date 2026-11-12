# Guide d'utilisation — Plateforme de gestion du patrimoine de l'État

Plateforme numérique de recensement, suivi, affectation, maintenance, inventaire et cession des biens publics de l'État togolais.

---

## 1. Installation et démarrage

### Prérequis
- Docker Desktop (ou Docker Engine) + Docker Compose v2
- 8 Go de RAM minimum recommandés

### Démarrage en une commande
```bash
git clone <dépôt>
cd Patrimoine
docker compose up -d --build
```
Le premier démarrage télécharge les images et compile les services (10–20 min). Vérifiez l'état :
```bash
docker compose ps          # tous les services doivent être "healthy"/"running"
curl http://localhost/api-gateway/api/v1/auth/health
```

### Accès
| Service | URL |
|---|---|
| Application web | http://localhost |
| API (passerelle) | http://localhost/api-gateway/api/v1/... |
| RabbitMQ (admin) | http://localhost:15672 (guest/guest) |

### Arrêt / redémarrage
```bash
docker compose down        # arrêt
docker compose up -d       # redémarrage
docker compose restart <service>
docker compose logs -f asset-service
```

### Sauvegarde et restauration
```bash
docker compose exec postgres sh /scripts/backup.sh      # dump dans /backups
docker compose exec postgres sh /scripts/restore.sh /backups/backup-XXX.sql.gz
```
Le volume `backups` conserve les dumps. Prévoyez une copie externe régulière.

### Mises à jour
```bash
git pull
docker compose up -d --build
# Les migrations s'exécutent automatiquement au démarrage de chaque service.
```

---

## 2. Comptes de démonstration

| Rôle | Email | Mot de passe | Droits |
|---|---|---|---|
| Administrateur | admin@etat.tg | Admin@2026 | Tout (`*`) |
| Gestionnaire patrimoine | gestionnaire@etat.tg | Gestion@2026 | Biens, mouvements, valorisation |
| Agent d'inventaire | agent@etat.tg | Agent@2026 | Inventaires, saisie |
| Auditeur | auditeur@etat.tg | Audit@2026 | Lecture seule + rapports |

> Changez ces mots de passe avant toute mise en production.

---

## 3. Procédures par profil

### 3.1 Administrateur
1. **Créer un utilisateur** : menu *Administration → Utilisateurs* → renseigner nom, email, mot de passe temporaire.
2. **Attribuer un rôle** : sélectionner le rôle (RBAC). Les permissions dépendent du rôle : `bien.creer`, `mouvement.valider`, `inventaire.gerer`, `maintenance.gerer`, `valorisation.gerer`, `document.gerer`, `report.gerer`.
3. **Auditer** : consulter le journal d'audit (qui a fait quoi, quand).

### 3.2 Gestionnaire de patrimoine
1. **Enregistrer un bien** : *Biens → Nouveau bien* → désignation, catégorie, marque, modèle, n° série, valeur d'acquisition, date, état → *Enregistrer*. Un code d'inventaire et une étiquette QR sont générés.
2. **Imprimer l'étiquette** : fiche du bien → QR code (à coller sur l'actif).
3. **Demander une affectation** : *Affectations → Nouvelle demande* → type (affectation/transfert/entrée/sortie), bien, structure de destination, motif → *Soumettre*. La demande passe en attente de validation.
4. **Valider un mouvement** : une fois la demande en attente, le valideur (permission `mouvement.valider`) approuve ou rejette avec commentaire.
5. **Planifier une maintenance** : *Maintenance → Plans* → bien, type, périodicité (mois), prochaine échéance.
6. **Suivre les interventions** : déclarer une panne (gravité), planifier l'intervention (agent/prestataire), démarrer, clôturer avec compte-rendu et coûts.
7. **Gérer les documents** : joindre facture, titre, PV, photos à un bien (types : facture, titre, pv, photo, contrat).
8. **Calculer la valorisation** : *Rapports/Valorisation → Calculer* pour un exercice → amortissements linéaires ou dégressifs selon la catégorie.

### 3.3 Agent d'inventaire (terrain, mobile)
1. **Créer une campagne** : *Inventaires → Nouvelle campagne* → libellé, structure/site/catégorie, période, équipes et agents.
2. **Démarrer la campagne** (statut `en_cours`).
3. **Scanner / saisir** : pour chaque bien, scanner le QR ou saisir le code → indiquer *présent/absent*, état constaté (bon, dégradé, hors service), commentaire, GPS (optionnel). Les constats peuvent être saisis hors-ligne puis synchronisés en lot (`POST /inventories/{id}/constats` avec tableau).
4. **Clôturer** : le système compare les biens attendus (asset-service) aux constats → génère écarts (manquants, excédentaires, dégradés) et le rapport de réconciliation (taux de couverture).

### 3.4 Auditeur / décideur
- Consulter le **tableau de bord** : nombre de biens, valeur totale, VNC, amortissements, maintenances à venir.
- **Générer un rapport** : *Rapports → Générer* → type (inventaire global, par structure, valorisation, mouvements, maintenance), format (PDF, Excel, CSV). Les exports volumineux passent par la file RabbitMQ.
- Vérifier la **traçabilité** : chaque bien conserve l'historique complet (mouvements, maintenances, valorisations, documents).

---

## 4. Cycle de vie d'un bien (rappel)

```
Entrée → Disponible → Affecté → (En maintenance) → Sorti
           ↑            ↓
        Inventorié   Transféré / Réaffecté
```

- **Sorties** : réforme, cession, rebut, perte, vol, destruction — chaque motif exige une validation et un justificatif.
- **RG-04** : un bien `sorti` ne peut plus être mouvementé.
- **RG-06** : un agent ne voit/mouvoit que les biens de son périmètre, sauf rôles centraux.

---

## 5. FAQ et dépannage

| Problème | Solution |
|---|---|
| `docker compose up` échoue sur un port | Un autre service occupe le port 80/5432/15672 — changez `${HTTP_PORT}` dans `.env` ou arrêtez le service conflictuel. |
| Service en `unhealthy` | `docker compose logs <service>` ; souvent PostgreSQL pas encore prêt — `depends_on` avec healthcheck gère l'attente. |
| Erreur 401 / session expirée | Reconnectez-vous ; le JWT expire (durée configurable côté auth-service). |
| Erreur 403 | Permission manquante pour votre rôle — contactez l'administrateur. |
| Bien introuvable | Vérifiez le périmètre (structure) ou les droits de lecture. |
| Scan QR ne fonctionne pas | Le code est généré à la création du bien ; régénérez l'étiquette depuis la fiche. En hors-ligne, synchronisez la file locale. |
| Rapport PDF/Excel lent | Génération asynchrone via RabbitMQ ; vérifiez `rabbitmq` et le worker. |
| Base de données corrompue | `docker compose down`, restaurer le dernier dump : `restore.sh`. |

### Réinitialiser les données de démo
```bash
docker compose down -v          # ATTENTION : supprime les volumes (données)
docker compose up -d --build    # recrée et re-seed
```

---

## 6. Exploitation

- **Logs** : `docker compose logs -f <service>` ; journal d'audit dans `auth_db`.
- **Sauvegardes** : planifier `backup.sh` (cron) + copie hors site.
- **Surveillance** : endpoints `/api/v1/<service>/health` et `/up` ; RabbitMQ management pour les files.
- **Production** : générer de nouvelles clés JWT (`openssl genrsa`), définir des mots de passe forts, activer HTTPS (certificats dans `docker/nginx/certs/`), restreindre RabbitMQ.

---

*Document généré pour la plateforme « Gestion du patrimoine de l'État togolais » — Étape 7.*
