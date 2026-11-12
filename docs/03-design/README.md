# Étape 3 — Design UI/UX

Conception visuelle de la plateforme de gestion du patrimoine de l'État togolais.

> **Note Figma** : l'accès à Figma n'étant pas disponible dans l'environnement de conception, cette étape livre un **cahier de design complet** (charte graphique, design system, wireframes, maquettes SVG haute fidélité, guide d'import Figma) conformément au CDC §8 et à l'hypothèse n°6 de l'analyse du besoin. Les maquettes SVG sont vectorielles et importables dans Figma (glisser-déposer ou plugin SVG).

## Organisation

```
docs/03-design/
├── README.md                  ← synthèse et mode d'emploi
├── design-system.md           ← charte graphique, tokens, composants
├── parcours.md                ← parcours utilisateurs par profil
├── wireframes/                ← wireframes basse fidélité (13 écrans)
│   ├── 01-connexion.md
│   ├── 02-tableau-de-bord.md
│   ├── 03-liste-biens.md
│   ├── 04-fiche-bien.md
│   ├── 05-creation-bien.md
│   ├── 06-mouvements.md
│   ├── 07-campagne-inventaire.md
│   ├── 08-inventaire-mobile.md
│   ├── 09-maintenance.md
│   ├── 10-valorisation.md
│   ├── 11-rapports.md
│   ├── 12-administration.md
│   └── 13-audit-notifications.md
├── maquettes/                 ← maquettes SVG haute fidélité
│   ├── desktop/               ← 1440 × 900
│   └── mobile/                ← 390 × 844
└── tools/gen_maquettes.py     ← régénérateur des maquettes SVG
```

## Écrans couverts

| # | Écran | Desktop | Mobile |
|---|---|---|---|
| 1 | Connexion / 2FA | ✔ | ✔ |
| 2 | Tableau de bord | ✔ | ✔ |
| 3 | Liste des biens | ✔ | ✔ |
| 4 | Fiche d'un bien | ✔ | ✔ |
| 5 | Création d'un bien | ✔ | – |
| 6 | Mouvements et demandes | ✔ | ✔ |
| 7 | Campagne d'inventaire | ✔ | ✔ |
| 8 | Inventaire terrain (scan) | – | ✔ (priorité) |
| 9 | Maintenance | ✔ | ✔ |
| 10 | Valorisation | ✔ | ✔ |
| 11 | Rapports et exports | ✔ | ✔ |
| 12 | Administration | ✔ | – |
| 13 | Audit / notifications | ✔ | ✔ |

## Principes directeurs

- **Sobre et institutionnel** : palette bleu marine / or sobre, beaucoup d'espace blanc, hiérarchie typographique claire.
- **Parcours courts** : action principale toujours visible, fil d'Ariane, confirmation pour les actions irréversibles.
- **Accessibilité WCAG 2.1 AA** : contrastes ≥ 4.5:1, focus visible, navigation clavier, libellés explicites.
- **Mobile d'abord pour l'inventaire** : écran 8 conçu pour usage terrain (grand bouton scan, zones tactiles ≥ 48 px).
- **Honnêteté des états** : chaque écran prévoit chargement, vide, erreur et succès.
- **Français** partout, messages d'erreur explicites.
