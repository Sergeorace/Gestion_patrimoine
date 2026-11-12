# Charte graphique & Design System

## 1. Identité visuelle

Plateforme institutionnelle de l'État togolais : sérieux, lisible, fiable. Pas de gradients criards, pas d'ombres marquées — plat, aéré, professionnel.

### Couleurs (tokens)

| Token | Hex | Usage |
|---|---|---|
| `primary-900` | `#0B2545` | Bleu marine institutionnel — sidebar, titres forts |
| `primary-700` | `#13315C` | Headers, éléments actifs |
| `primary-600` | `#1D4E89` | Boutons primaires, liens |
| `primary-100` | `#DBEAFE` | Fonds survol, badges informatifs |
| `accent-500` | `#B8860B` | Or sobre — accents, badges « validé », jalons |
| `accent-100` | `#FEF3C7` | Fonds badges attention |
| `success-600` | `#15803D` | Succès, bien disponible, inventorié |
| `success-100` | `#DCFCE7` | Fonds succès |
| `warning-600` | `#B45309` | Alertes, maintenance due, écarts |
| `warning-100` | `#FEF3C7` | Fonds alerte |
| `danger-600` | `#B91C1C` | Erreurs, sorties, rejets |
| `danger-100` | `#FEE2E2` | Fonds erreur |
| `neutral-900` | `#111827` | Texte principal |
| `neutral-600` | `#4B5563` | Texte secondaire |
| `neutral-300` | `#D1D5DB` | Bordures, séparateurs |
| `neutral-100` | `#F3F4F6` | Fonds de page, inputs |
| `neutral-50`  | `#F9FAFB` | Fond alternatif lignes |
| `white` | `#FFFFFF` | Cartes, surfaces |

### Typographie

- **Police** : `Inter` (fallback `system-ui, -apple-system, "Segoe UI", Roboto`). Chiffres : `tabular-nums` pour les montants et codes.
- **Échelle** (base 16 px) :

| Style | Taille / graisse | Usage |
|---|---|---|
| `display` | 32 / 700 | Titres de page |
| `h1` | 24 / 700 | Titres de section |
| `h2` | 20 / 600 | Sous-titres cartes |
| `body` | 15 / 400 | Texte courant (confort lecture) |
| `body-sm` | 13 / 400 | Secondaire, tableaux denses |
| `caption` | 12 / 500 | Labels, méta, badges |

### Espacement & grille

- Échelle 4 px : `4, 8, 12, 16, 24, 32, 48, 64`.
- Grille desktop : conteneur 1200 px max, gouttière 24 px.
- Mobile : marges 16 px, breakpoint unique 768 px.
- Rayons : `6 px` (inputs, boutons), `10 px` (cartes), `999 px` (badges/pills).
- Ombres : quasi nulles (`0 1px 2px rgba(11,37,69,.06)` max).

## 2. Composants

| Composant | Spécification |
|---|---|
| **Bouton primaire** | fond `primary-600`, texte blanc, 40 px h, rayon 6, hover `primary-700` |
| **Bouton secondaire** | contour `neutral-300`, texte `primary-600` |
| **Bouton danger** | fond `danger-600` — réservé aux actions irréversibles, avec confirmation modale |
| **Champ** | label au-dessus (13 px `neutral-900`), input 40 px, bordure `neutral-300`, focus `primary-600` + anneau 3 px `primary-100`, erreur bordure `danger-600` + message sous le champ |
| **Tableau** | en-tête `primary-900` texte blanc 12 px uppercase, lignes zébrées `neutral-50`, hover `primary-100`, pagination 20/lignes, tri par colonne, états vide/chargement |
| **Carte KPI** | fond blanc, rayon 10, icône 40 px `primary-100`, valeur 28/700, libellé 13 `neutral-600`, variation ± % colorée |
| **Badge statut** | pill : Disponible=vert, Affecté=bleu, En maintenance=orange, Sorti=gris, En attente=ambre |
| **Modal** | overlay `rgba(11,37,69,.45)`, carte 480 px max, titre + actions alignées droite, focus piégé, Échap pour fermer |
| **Toast** | coin bas-droite, 4 variantes (succès/alerte/erreur/info), auto-masquage 5 s |
| **Navigation** | sidebar fixe 240 px `primary-900`, items 40 px, icône + label, section active `primary-700` + barre or 3 px ; topbar 56 px blanche avec fil d'Ariane, recherche globale, cloche notifications, avatar |
| **Fil d'Ariane** | `Accueil / Biens / Fiche PAT-2026-000123`, liens `primary-600` |
| **États** | squelette de chargement (shimmer `neutral-100`), illustration simple + message pour vide, bannière erreur avec « Réessayer » |

## 3. Icônes & illustrations

- Jeu d'icônes : **Lucide** (style trait 2 px, cohérent, open source).
- Illustrations d'états vides : style ligne sobre, palette primaires.
- QR codes : générés en noir sur blanc, zone de silence 4 modules, taille min 25 mm à l'impression.

## 4. Accessibilité (WCAG 2.1 AA)

- Contraste texte/fond ≥ 4.5:1 (vérifié sur la palette ci-dessus).
- Focus visible : anneau `primary-600` 2 px sur tous les interactifs.
- Cibles tactiles ≥ 44 × 44 px (48 px sur mobile inventaire).
- `prefers-reduced-motion` respecté ; aucune animation > 300 ms.
- Formulaires : label associé, erreurs liées via `aria-describedby`, ordre de tabulation logique.
- Tableaux : `<th scope>` ; graphiques avec alternative textuelle.

## 5. Mode sombre

Non prévu en V1 (complexité de recette). Tokens structurés pour permettre une V2 : les couleurs sémantiques (`surface`, `text`, `border`) sont séparées des couleurs de marque.
