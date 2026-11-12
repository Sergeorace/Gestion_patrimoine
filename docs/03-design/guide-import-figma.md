# Guide d'import dans Figma

Ce dossier constitue le **cahier de design** de la plateforme. Voici comment le transformer en fichier Figma.

## Méthode recommandée (30 min)

1. **Créer le fichier Figma** : `Fichier → Nouveau → Design` — nom : `Patrimoine Etat - Design System`.
2. **Page « 🎨 Design System »** :
   - Créer des **Styles** (couleurs + texte) en recopiant les tokens de `design-system.md` (section 1).
   - Créer des **Components** pour : bouton primaire/secondaire/danger, champ de formulaire, badge de statut, carte KPI, tableau, modal, toast, sidebar, topbar, pagination.
3. **Pages par écran** : une page par écran (`01 Connexion`, `02 Tableau de bord`, …).
4. **Importer les maquettes SVG** :
   - Figma accepte le glisser-déposer de SVG → chaque maquette devient un frame modifiable (texte et formes éditables).
   - Glisser `maquettes/desktop/*.svg` et `maquettes/mobile/*.svg`.
   - Renommer les frames : `Desktop / Tableau de bord`, `Mobile / Inventaire terrain`.
5. **Relier les écrans** : mode Prototype → relier les boutons principaux (connexion → dashboard, « Nouveau bien » → création, scan → constat…).
6. **Wireframes** : reproduire les schémas ASCII de `wireframes/` avec les composants du design system (ou les importer comme référence en les collant dans un frame « wireframe »).

## Structure de pages suggérée

```
🎨 Design System      → tokens, composants, iconographie
📱 Mobile             → écrans mobile-first (inventaire prioritaire)
🖥 Desktop            → écrans desktop
🧭 Parcours           → user flows cliquables
📐 Wireframes         → basse fidélité des 13 écrans
```

## Points d'attention pour Figma

- Respecter les contrastes WCAG AA (palette déjà vérifiée).
- Prévoir les états : hover, focus, disabled, loading, empty, error.
- Le mode hors-ligne de l'inventaire doit apparaître dans les écrans mobiles (badge + file de sync).
- Export final attendu : lien Figma partagé + export PDF des écrans clés (CDC §8.2).
