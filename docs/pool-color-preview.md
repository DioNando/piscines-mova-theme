# Documentation — Shortcode `[mova_pool_color_preview]`

## Sommaire
1. [Vue d'ensemble](#vue-densemble)
2. [Fichiers du projet](#fichiers-du-projet)
3. [Prérequis WordPress](#prérequis-wordpress)
4. [Utilisation du shortcode](#utilisation-du-shortcode)
5. [Fonctionnement](#fonctionnement)
6. [Structure des données](#structure-des-données)
7. [Variable JavaScript `movaColorPreview`](#variable-javascript-movacolorpreview)
8. [Personnalisation CSS](#personnalisation-css)
9. [Intégration avec le formulaire de devis](#intégration-avec-le-formulaire-de-devis)

---

## Vue d'ensemble

Le shortcode `[mova_pool_color_preview]` affiche un composant de prévisualisation des couleurs disponibles pour un modèle de piscine. Il se compose de :

- **Zone de prévisualisation (sticky)** : image grand format du modèle qui change selon la couleur sélectionnée
- **Panneau de sélection** : grille de pastilles (swatches) cliquables représentant chaque couleur disponible

Le composant est entièrement côté client (pas d'AJAX). Au clic sur une pastille, l'image d'ambiance de la couleur remplace l'image par défaut avec un effet de transition. Un second clic sur la même pastille désélectionne la couleur et rétablit l'image par défaut.

---

## Fichiers du projet

| Fichier | Rôle |
|---|---|
| `inc/pool-color-preview.php` | Shortcode WordPress — récupère les données ACF, injecte le HTML et passe les données au JS via `wp_localize_script` |
| `assets/js/pool-color-preview.js` | Logique JS : gestion des clics sur les swatches, changement d'image avec préchargement, affichage du nom de couleur |
| `assets/css/pool-color-preview.css` | Styles : layout grille 2 colonnes, swatches, preview sticky, état actif, responsive |

---

## Prérequis WordPress

### Custom Post Type
- **`piscine`** — Le shortcode ne s'affiche que sur les posts de ce type

### Taxonomie
| Taxonomie | Slug machine | Usage |
|---|---|---|
| Couleurs de piscine | `couleur_piscine` | Chaque terme représente une couleur disponible |

### Champs ACF sur le CPT `piscine`

| Champ | Clé ACF | Type | Description |
|---|---|---|---|
| Galerie | `galerie` | Gallery | Images du modèle — la première sert d'image par défaut |
| Couleurs disponibles | `couleurs_disponibles` | Taxonomy (multi) | Termes `couleur_piscine` associés au modèle |

### Champs ACF sur la taxonomie `couleur_piscine`

| Champ | Clé ACF | Type | Description |
|---|---|---|---|
| Swatch couleur | `swatch_couleur` | Image | Pastille miniature affichée dans le sélecteur (thumbnail) |
| Image ambiance | `image_ambiance` | Image | Photo d'ambiance de la piscine dans cette couleur (large) |

---

## Utilisation du shortcode

### Basique (sur la page single d'une piscine)
```
[mova_pool_color_preview]
```
Utilise automatiquement l'ID du post courant (`get_the_ID()`).

### Avec un ID spécifique
```
[mova_pool_color_preview id="123"]
```
Affiche la prévisualisation pour la piscine #123.

### Paramètres

| Paramètre | Type | Défaut | Description |
|---|---|---|---|
| `id` | int | `0` (post courant) | ID du CPT `piscine` à afficher |

### Conditions de non-affichage

Le shortcode retourne une chaîne vide (`''`) si :
- Le `post_id` est invalide ou ne correspond pas au post type `piscine`
- Aucune couleur n'est associée au modèle (`couleurs_disponibles` vide)

---

## Fonctionnement

### Détermination de l'image par défaut
1. Cherche la première image du champ ACF `galerie` (taille `large`)
2. Si la galerie est vide, utilise l'image mise en avant (`post_thumbnail`)

### Sélection d'une couleur
1. L'utilisateur clique sur une pastille
2. La pastille reçoit la classe `.active` (bordure + ombre)
3. L'image d'ambiance est préchargée via `new Image()`
4. Pendant le chargement, l'image de prévisualisation passe en opacité réduite (`.loading`)
5. Une fois chargée, l'image est injectée dans le `src` de la preview
6. Le nom de la couleur s'affiche sous les pastilles

### Désélection
- Cliquer à nouveau sur la pastille active désélectionne la couleur
- L'image revient à l'image par défaut
- Le nom de couleur est vidé

---

## Structure des données

### Données passées au HTML (attributs `data-*`)

Chaque bouton swatch porte :

| Attribut | Contenu |
|---|---|
| `data-slug` | Slug du terme `couleur_piscine` |
| `data-ambiance` | URL de l'image d'ambiance (taille `large`) |
| `title` | Nom de la couleur |
| `aria-label` | Nom de la couleur (accessibilité) |

---

## Variable JavaScript `movaColorPreview`

Injectée via `wp_localize_script`, accessible globalement :

```js
movaColorPreview = {
    defaultImage: "https://…/image.jpg",   // URL de l'image par défaut
    modelSlug:    "modele-xyz",             // Slug du post piscine
    modelTitle:   "Modèle XYZ",            // Titre du post (décodé)
    devisUrl:     "https://…/demande-de-devis/", // URL du formulaire de devis
    couleurs: [                            // Tableau des couleurs disponibles
        {
            term_id:  42,
            name:     "Bleu Océan",
            slug:     "bleu-ocean",
            swatch:   "https://…/swatch.jpg",
            ambiance: "https://…/ambiance.jpg"
        },
        // ...
    ]
};
```

---

## Personnalisation CSS

### Préfixe
Toutes les classes utilisent le préfixe `mova-cpv-` (color preview).

### Classes principales

| Classe | Élément |
|---|---|
| `.mova-cpv` | Conteneur racine (grille 2 colonnes) |
| `.mova-cpv-preview` | Zone de prévisualisation (sticky en desktop) |
| `.mova-cpv-preview-wrap` | Wrapper image avec ratio 4:3 |
| `.mova-cpv-preview-img` | Image de prévisualisation |
| `.mova-cpv-preview-img.loading` | État de chargement (opacité 0.4) |
| `.mova-cpv-panel` | Panneau de sélection des couleurs |
| `.mova-cpv-title` | Titre « Choisissez votre couleur » |
| `.mova-cpv-section` | Bloc section |
| `.mova-cpv-swatches` | Conteneur flex des pastilles |
| `.mova-cpv-swatch` | Bouton pastille (72×72px) |
| `.mova-cpv-swatch.active` | Pastille sélectionnée (bordure + box-shadow) |
| `.mova-cpv-swatch-placeholder` | Fallback texte si pas d'image swatch |
| `.mova-cpv-color-name` | Nom de la couleur sélectionnée |

### Variables de couleur du thème

| Couleur | Usage |
|---|---|
| `#1a4759` | Bordure active, titres, nom couleur |
| `#f5f6f8` | Fond de la zone preview |
| `#eee` | Fond par défaut des swatches |
| `#777` | Sous-titres |
| `#999` | Texte placeholder swatch |

### Breakpoints responsive

| Breakpoint | Comportement |
|---|---|
| `> 1024px` | Grille 2 colonnes, preview sticky, swatches 72×72px |
| `768px – 1024px` | Gap réduit, titre plus petit |
| `< 767px` | 1 colonne, preview non-sticky, swatches 48×48px |

---

## Intégration avec le formulaire de devis

La variable `movaColorPreview` expose `devisUrl`, `modelSlug` et la couleur sélectionnée, ce qui permet de construire un lien pré-rempli vers le formulaire de devis :

```js
var url = movaColorPreview.devisUrl
        + '?model=' + encodeURIComponent(movaColorPreview.modelSlug)
        + '&couleur=' + encodeURIComponent(selectedColor);
window.location.href = url;
```

Le formulaire (`[mova_quote_form]`) lit automatiquement les paramètres `model` et `couleur` de l'URL pour pré-remplir les champs correspondants.
