# Documentation — Shortcode `[mova_pool_color_catalog]`

## Sommaire
1. [Vue d'ensemble](#vue-densemble)
2. [Fichiers du projet](#fichiers-du-projet)
3. [Prérequis WordPress](#prérequis-wordpress)
4. [Utilisation du shortcode](#utilisation-du-shortcode)
5. [Fonctionnement](#fonctionnement)
6. [Structure des données](#structure-des-données)
7. [Variable JavaScript `movaColorCatalog`](#variable-javascript-movacolorcatalog)
8. [Personnalisation CSS](#personnalisation-css)

---

## Vue d'ensemble

Le shortcode `[mova_pool_color_catalog]` affiche le catalogue complet des couleurs de gel-coat disponibles, regroupées par **collection** (termes parents de la taxonomie `couleur_piscine`). Il peut être placé sur **n'importe quelle page** du site.

Il se compose de :

- **Colonne gauche** : titre de section, pastilles (swatches) groupées par collection, nom de la couleur sélectionnée, disclaimer
- **Colonne droite** : image d'ambiance grande taille qui change selon la couleur cliquée

Le composant est entièrement côté client (pas d'AJAX). Les swatches sont rendus côté serveur (PHP) ; les données sont également injectées via `wp_localize_script` pour le JS.

---

## Fichiers du projet

| Fichier | Rôle |
|---|---|
| `inc/pool-color-catalog.php` | Shortcode WordPress — récupère les collections et leurs couleurs depuis la taxonomie `couleur_piscine`, injecte le HTML et passe les données au JS |
| `assets/js/pool-color-catalog.js` | Logique JS : clic sur swatch, changement d'image d'ambiance avec préchargement, auto-sélection de la première couleur |
| `assets/css/pool-color-catalog.css` | Styles : layout 2 colonnes, groupes de collection, swatches, responsive |

---

## Prérequis WordPress

- **Taxonomie `couleur_piscine`** hiérarchique avec :
  - Des **termes parents** représentant les collections (ex. « Collection Minérale »)
  - Des **termes enfants** représentant les couleurs individuelles
- **Champs ACF sur les termes** `couleur_piscine` :
  - `swatch_couleur` — image de la pastille (taille `thumbnail`)
  - `image_ambiance` — image de prévisualisation (taille `large`)
  - `ordre` — entier pour contrôler l'ordre d'affichage

---

## Utilisation du shortcode

### Basique
```
[mova_pool_color_catalog]
```

Le shortcode n'accepte aucun paramètre. Il affiche toutes les collections et leurs couleurs.

### Conditions de non-affichage

Le shortcode retourne une chaîne vide (`''`) si :
- Aucun terme parent (collection) n'existe dans la taxonomie
- Toutes les collections sont vides (aucun terme enfant)

---

## Fonctionnement

### Au chargement
1. PHP récupère les termes parents (`parent=0`) triés par champ ACF `ordre`
2. Pour chaque collection, les termes enfants sont récupérés et triés de la même façon
3. Le HTML est rendu avec un bloc `.mova-ccc-collection` par collection
4. La première couleur ayant une image d'ambiance est pré-chargée dans le `src` de l'image preview
5. Le JS auto-sélectionne la première pastille au chargement

### Sélection d'une couleur
1. L'utilisateur clique sur une pastille
2. La pastille reçoit la classe `.active`
3. L'image d'ambiance se charge (avec préchargement pour éviter le flash)
4. Le nom de la couleur s'affiche sous les swatches
5. Un second clic sur la même pastille la désélectionne

---

## Structure des données

### Attributs HTML sur les swatches

| Attribut | Contenu |
|---|---|
| `data-slug` | Slug du terme `couleur_piscine` |
| `data-ambiance` | URL de l'image d'ambiance (taille `large`) |
| `title` | Nom de la couleur |
| `aria-label` | Nom de la couleur (accessibilité) |

---

## Variable JavaScript `movaColorCatalog`

Injectée via `wp_localize_script`, accessible globalement. Contient la liste à plat de toutes les couleurs enfants (dans l'ordre collections → couleurs) :

```js
movaColorCatalog = {
    couleurs: [
        {
            term_id:  10,
            name:     "Ciel de minuit",
            slug:     "ciel-de-minuit",
            swatch:   "https://…/swatch.jpg",   // thumbnail
            ambiance: "https://…/ambiance.jpg"  // large
        },
        // ...
    ]
};
```

---

## Personnalisation CSS

### Préfixe
Toutes les classes utilisent le préfixe `mova-ccc-` (color catalog).

### Classes principales

| Classe | Élément |
|---|---|
| `.mova-ccc` | Conteneur racine |
| `.mova-ccc-layout` | Grille 2 colonnes (couleurs / preview) |
| `.mova-ccc-col-colors` | Colonne gauche — swatches (sticky) |
| `.mova-ccc-col-preview` | Colonne droite — image d'ambiance |
| `.mova-ccc-section-title` | Titre « Couleurs disponibles » |
| `.mova-ccc-section-subtitle` | Sous-titre |
| `.mova-ccc-collection` | Bloc d'une collection |
| `.mova-ccc-collection-title` | Nom de la collection (label discret) |
| `.mova-ccc-swatches` | Conteneur flex des pastilles |
| `.mova-ccc-swatch` | Bouton pastille (72×72px) |
| `.mova-ccc-swatch.active` | Pastille sélectionnée |
| `.mova-ccc-swatch-placeholder` | Initiales quand pas de swatch image |
| `.mova-ccc-color-name` | Nom de la couleur sélectionnée |
| `.mova-ccc-disclaimer` | Mention légale couleurs |
| `.mova-ccc-preview-wrap` | Wrapper de l'image (ratio 4/3) |
| `.mova-ccc-preview-img` | Image d'ambiance |
| `.mova-ccc-preview-img.loading` | État de chargement (opacité 0.4) |

### Breakpoints responsive

| Breakpoint | Comportement |
|---|---|
| `> 1024px` | Layout 2 colonnes (`1fr 1.5fr`), colonne couleurs sticky |
| `768px – 1024px` | Layout 2 colonnes (`1fr 1.2fr`), gap réduit |
| `< 767px` | Layout 1 colonne, image au-dessus, swatches en dessous, pastilles 48×48px |
