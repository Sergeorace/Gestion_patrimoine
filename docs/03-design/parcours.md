# Parcours utilisateurs

## Parcours 1 — Administrateur : créer un utilisateur

1. Login → tableau de bord admin.
2. Sidebar « Administration » → « Utilisateurs ».
3. Clic « Nouvel utilisateur » → formulaire (nom, prénom, email, structure, rôles).
4. Soumission → mot de passe temporaire généré + envoyé par e-mail → toast succès.
5. L'utilisateur reçoit un e-mail l'invitant à changer son mot de passe.

**Points de vigilance** : email unique (erreur inline), rôle « Administrateur » réservé (confirmation modale), journal d'audit automatique.

## Parcours 2 — Responsable de structure : enregistrer un bien

1. Login → « Biens » → « Nouveau bien ».
2. Choix du type (immobilier / véhicule / informatique / mobilier / autre) → le formulaire s'adapte (champs spécifiques affichés).
3. Saisie : désignation, catégorie, date et mode d'acquisition, valeur, site + localisation, photos (≤ 5, ≤ 5 Mo).
4. Soumission → code inventaire `PAT-AAAA-NNNNNN` généré + QR code affiché.
5. Téléchargement/ impression de l'étiquette → fiche bien consultable.

**Points de vigilance** : code non réutilisable même après sortie (RG-01), un seul affectataire à la fois (RG-02), structure pré-remplie = périmètre de l'utilisateur (RG-06).

## Parcours 3 — Affectation d'un bien (workflow)

1. Responsable : « Biens » → fiche → « Demander une affectation ».
2. Choix structure + site destinataires, motif, pièces jointes → « Soumettre ».
3. Statut « En attente » ; notification au gestionnaire central.
4. Gestionnaire : file « Demandes » → consultation pièces → « Valider » ou « Rejeter » (motif obligatoire).
5. Validation → bien mis à jour (structure, site, statut « Affecté »), historique + audit, notifications aux deux parties.

## Parcours 4 — Agent inventorialiste : campagne terrain

1. Login mobile → « Inventaires » → campagne assignée.
2. Téléchargement de la liste (cache hors-ligne automatique).
3. Scan QR (bouton caméra plein écran) ou recherche manuelle.
4. Constat : présent ? état (bon / dégradé / hors service) + photo + commentaire.
5. Coupure réseau → file locale ; la sync reprend au retour du réseau.
6. Fin de tournée → « Clôturer mes constats » → rapport provisoire.
7. Gestionnaire central clôture la campagne → rapport d'écarts officiel (RG-08).

## Parcours 5 — Agent de maintenance : intervention

1. « Maintenance » → « Déclarer une panne » (depuis fiche bien ou liste).
2. Description, gravité, photos → intervention « Déclarée ».
3. Planification : date, agent ou prestataire, coût prévu → « En cours ».
4. Réalisation → compte-rendu, coût réel, photos → « Clôturée ».
5. Bien repasse « Disponible/Affecté » ; historique + fiabilité mis à jour ; notification au responsable.

## Parcours 6 — Comptable : valorisation

1. « Valorisation » → filtre exercice / structure / catégorie.
2. Consultation VNC par bien ; lancement du calcul d'amortissement annuel.
3. Export état d'amortissement (PDF/Excel).
4. Enregistrement d'une réévaluation (motif + justificatif, validation requise).

## Parcours 7 — Auditeur : consultation

1. Login (rôle lecture seule) → accès restreint : consultation biens, mouvements, audit, rapports.
2. « Journal d'audit » → filtres (utilisateur, action, période, entité) → export CSV.
3. Aucun bouton d'écriture n'est affiché ; toute tentative d'API retourne 403.

## Parcours 8 — Décideur : pilotage

1. Login → tableau de bord national : KPIs (nb biens, valeur, répartition), graphiques, alertes.
2. Drill-down par catégorie / structure / région.
3. Export des états standardisés pour présentation.
