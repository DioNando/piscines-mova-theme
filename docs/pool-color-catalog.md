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

Le shortcode `[mova_pool_color_catalog]` affiche un catalogue de prévisualisation des couleurs pour **tous** les modèles de piscines. Contrairement à `[mova_pool_color_preview]` (qui nécessite d'être placé sur une page single de piscine), ce shortcode peut être utilisé sur **n'importe quelle page** du site.

Il se compose de :

- **Grille de sélection des modèles** : cartes cliquables avec image et nom de chaque piscine
- **Zone de prévisualisation** : apparaît après sélection d'un modèle, avec image grand format et grille de pastilles (swatches) de couleurs
- **Lien vers la fiche** : lien direct vers la page single du modèle sélectionné

Le composant est entièrement côté client (pas d'AJAX). Toutes les données sont injectées au chargement via `wp_localize_script`.

---

## Fichiers du projet

| Fichier | Rôle |
|---|---|
| `inc/pool-color-catalog.php` | Shortcode WordPress — récupère tous les modèles et leurs couleurs ACF, injecte le HTML et passe les données au JS |
| `assets/js/pool-color-catalog.js` | Logique JS : sélection de modèle, construction dynamique des swatches, changement d'image |
| `assets/css/pool-color-catalog.css` | Styles : grille de modèles, layout 2 colonnes preview/panneau, swatches, responsive |

---

## Prérequis WordPress

Identiques à ceux de `[mova_pool_color_preview]` :

- **CPT `piscine`** avec les champs ACF `galerie` et `couleurs_disponibles`
- **Taxonomie `couleur_piscine`** avec les champs ACF `swatch_couleur` et `image_ambiance`

---

## Utilisation du shortcode

### Basique (affiche tous les modèles)
```
[mova_pool_color_catalog]
```

### Limiter le nombre de modèles
```
[mova_pool_color_catalog limit="6"]
```

### Paramètres

| Paramètre | Type | Défaut | Description |
|---|---|---|---|
| `limit` | int | `-1` (tous) | Nombre maximum de modèles à afficher |

### Conditions de non-affichage

Le shortcode retourne une chaîne vide (`''`) si aucune piscine publiée n'existe.

---

## Fonctionnement

### Étape 1 — Sélection du modèle
1. La page affiche une grille de cartes (image + nom) pour chaque modèle de piscine
2. L'utilisateur clique sur une carte
3. La carte reçoit la classe `.active`
4. La zone de prévisualisation apparaît avec un scroll automatique

### Étape 2 — Prévisualisation des couleurs
1. L'image par défaut du modèle s'affiche dans la zone preview
2. Les pastilles de couleurs disponibles pour ce modèle sont générées dynamiquement
3. Au clic sur une pastille, l'image d'ambiance remplace l'image par défaut (avec préchargement)
4. Un second clic désélectionne la couleur et restaure l'image par défaut

### Changement de modèle
- Cliquer sur un autre modèle dans la grille recharge la zone détail avec les données du nouveau modèle

---

## Structure des données

### Attributs HTML sur les cartes de modèles

| Attribut | Contenu |
|---|---|
| `data-index` | Index du modèle dans le tableau JS |

### Attributs HTML sur les swatches (générés dynamiquement)

| Attribut | Contenu |
|---|---|
| `data-slug` | Slug du terme `couleur_piscine` |
| `data-ambiance` | URL de l'image d'ambiance (taille `large`) |
| `title` | Nom de la couleur |
| `aria-label` | Nom de la couleur (accessibilité) |

---

## Variable JavaScript `movaColorCatalog`

Injectée via `wp_localize_script`, accessible globalement :

```js
movaColorCatalog = {
    devisUrl: "https://…/demande-de-devis/",
    models: [
        {
            id:           42,
            title:        "Modèle XYZ",
            slug:         "modele-xyz",
            thumb:        "https://…/thumb.jpg",       // medium
            defaultImage: "https://…/image.jpg",       // large
            permalink:    "https://…/piscine/modele-xyz/",
            couleurs: [
                {
                    term_id:  10,
                    name:     "Bleu Océan",
                    slug:     "bleu-ocean",
                    swatch:   "https://…/swatch.jpg",
                    ambiance: "https://…/ambiance.jpg"
                },
                // ...
            ]
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
| `.mova-ccc-models` | Section sélecteur de modèles |
| `.mova-ccc-models-title` | Titre « Choisissez un modèle » |
| `.mova-ccc-models-grid` | Grille responsive des cartes |
| `.mova-ccc-model-card` | Carte modèle (bouton) |
| `.mova-ccc-model-card.active` | Carte sélectionnée |
| `.mova-ccc-model-thumb` | Image de la carte |
| `.mova-ccc-model-name` | Nom du modèle dans la carte |
| `.mova-ccc-detail` | Zone détail (preview + couleurs) |
| `.mova-ccc-detail-inner` | Grille 2 colonnes (preview / panneau) |
| `.mova-ccc-preview` | Zone de prévisualisation (sticky) |
| `.mova-ccc-preview-img` | Image de prévisualisation |
| `.mova-ccc-preview-img.loading` | État de chargement (opacité 0.4) |
| `.mova-ccc-panel` | Panneau de sélection des couleurs |
| `.mova-ccc-model-title` | Titre du modèle sélectionné |
| `.mova-ccc-swatches` | Conteneur flex des pastilles |
| `.mova-ccc-swatch` | Bouton pastille (72×72px) |
| `.mova-ccc-swatch.active` | Pastille sélectionnée |
| `.mova-ccc-color-name` | Nom de la couleur sélectionnée |
| `.mova-ccc-link` | Lien « Voir la fiche complète » |

### Breakpoints responsive

| Breakpoint | Comportement |
|---|---|
| `> 1024px` | Grille modèles auto-fill 200px, détail 2 colonnes, preview sticky |
| `768px – 1024px` | Grille modèles 160px min, gap réduit |
| `< 767px` | Grille modèles 140px min, détail 1 colonne, preview non-sticky, swatches 48×48px |
