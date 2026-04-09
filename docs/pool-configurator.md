# Documentation — Shortcode `[mova_pool_configurator]`

## Sommaire
1. [Vue d'ensemble](#vue-densemble)
2. [Fichiers du projet](#fichiers-du-projet)
3. [Prérequis WordPress](#prérequis-wordpress)
4. [Convention de nommage des images](#convention-de-nommage-des-images)
5. [Utilisation du shortcode](#utilisation-du-shortcode)
6. [Fonctionnement](#fonctionnement)
7. [Variable JavaScript `movaConfigurator`](#variable-javascript-movaconfigurator)
8. [Intégration avec le formulaire de devis](#intégration-avec-le-formulaire-de-devis)
9. [Personnalisation CSS](#personnalisation-css)
10. [Mode debug](#mode-debug)

---

## Vue d'ensemble

Le shortcode `[mova_pool_configurator]` affiche un configurateur visuel AquaCove qui superpose des couches PNG transparentes (tapis) sur un fond de couleur de coque. Il se compose de :

- **Zone de prévisualisation (sticky)** : empilement de layers PNG — un fond (couleur coque) + un overlay par zone (marches, bancs, terrasse, fond)
- **Panneau Couleur de la coque** : swatches cliquables pour changer le fond
- **Panneau Tapis par zone** : chaque zone a son propre sélecteur de tapis indépendant, avec un toggle on/off
- **Section Options** : checkboxes dynamiques pour les options compatibles (Jets de massage, BaduJet Turbo, etc.)
- **Bouton « Obtenir un devis »** : redirige vers le formulaire de devis avec pré-remplissage (modèle, couleur, options)

Chaque zone peut avoir un modèle de tapis différent. Quand on change la couleur de la coque, les overlays tapis restent en place. Quand on désactive une zone, son overlay disparaît et ses swatches sont grisés.

Le composant est entièrement côté client (pas d'AJAX). Les URLs des images sont construites par concaténation de slugs — zéro requête DB côté front.

---

## Fichiers du projet

| Fichier | Rôle |
|---|---|
| `inc/pool-configurator.php` | Shortcode WordPress — gardes, données ACF, validation `file_exists()`, HTML, `wp_localize_script` |
| `assets/js/pool-configurator.js` | Logique JS : interactions couleur/tapis/zones, gestion des layers, préchargement `new Image()` |
| `assets/css/pool-configurator.css` | Styles : layout grille, layers superposées, swatches, zone toggles, responsive |
| `assets/images/tapis-aquacove/` | ~706 PNG (fonds + overlays). Exclu de Git via `.gitignore` |

---

## Prérequis WordPress

### Custom Post Type
- **`piscine`** — Le shortcode ne s'affiche que sur les posts de ce type

### Taxonomies

| Taxonomie | Slug machine | Usage |
|---|---|---|
| Couleurs de piscine | `couleur_piscine` | Chaque terme = une couleur de coque |
| Modèles de tapis | `modele_tapis` | Chaque terme = un motif de tapis AquaCove |

### Champs ACF sur le CPT `piscine`

| Champ | Clé ACF | Type | Description |
|---|---|---|---|
| Compatible AquaCove | `opt_aquacove` | True/False | Active le configurateur pour ce modèle |
| Slug dimension | `slug_dimension` | Text | Slug fichier de la dimension (ex: `12x34`, `pataugeuse`, `c-de-nage`) |
| Zones AquaCove | `zones_aquacove` | Checkbox | Zones disponibles : marches, bancs, terrasse, fond. Visible si `opt_aquacove = true` |
| Tapis disponibles | `tapis_disponibles` | Taxonomy multi (`modele_tapis`) | Modèles de tapis compatibles. Visible si `opt_aquacove = true` |
| Couleurs disponibles | `couleurs_disponibles` | Taxonomy multi (`couleur_piscine`) | Couleurs de coque compatibles (partagé avec pool-color-preview) |
| Compatible Jets | `opt_jets` | True/False | Affiche l'option « Jets de massage » dans le configurateur |
| Compatible BaduJet | `opt_badujet` | True/False | Affiche l'option « BaduJet Turbo » dans le configurateur |

### Champs ACF sur la taxonomie `couleur_piscine`

| Champ | Clé ACF | Type | Description |
|---|---|---|---|
| Swatch couleur | `swatch_couleur` | Image | Pastille miniature (thumbnail) |
| Slug fichier | `slug_fichier` | Text | Slug dans les noms de fichiers (ex: `fondrocheux`, `grislunaire`) |

### Champs ACF sur la taxonomie `modele_tapis`

| Champ | Clé ACF | Type | Description |
|---|---|---|---|
| Swatch tapis | `swatch_tapis` | Image | Pastille de texture (thumbnail) |
| Slug fichier | `slug_fichier` | Text | Slug dans les noms de fichiers (ex: `lainageblanc`, `chevrons-inverses`) |

---

## Convention de nommage des images

Toutes les images sont dans `assets/images/tapis-aquacove/`.

### Fond (couleur coque)
```
piscine-{slug_dimension}-{slug_fichier_couleur}.png
```
Exemples : `piscine-12x34-grislunaire.png`, `piscine-pataugeuse-blanccoton.png`

### Overlay tapis (PNG transparent)
```
{slug_dimension}-{slug_fichier_tapis}-{zone}.png
```
Exemples : `12x34-abysse-marches.png`, `12x34-sable-terrasse.png`

### Zones par dimension

| Zones | Modèles |
|---|---|
| marches | 8x16, 9x24, 11x23, c-de-nage, couloir |
| marches + bancs | 8x10, 10x20cp, 11x19, 11x25, 12x20, 12x22, 12x24, 12x24cp, 12x28, 13x27 |
| marches + bancs + terrasse | 12x26, 12x30, 12x33, 12x34, 12x37, 14x28cp |
| marches + fond | pataugeuse |

### Slugs de tapis disponibles
`abysse`, `chevrons`, `chevrons-inverses`, `crepuscule`, `feuillage`, `graphite`, `lainageblanc`, `loupdemer`, `mova`, `poussieredelune`, `sable`, `sauna`

---

## Utilisation du shortcode

### Basique (sur la page single d'une piscine)
```
[mova_pool_configurator]
```

### Avec un ID spécifique
```
[mova_pool_configurator id="123"]
```

### Mode debug (admin uniquement)
```
[mova_pool_configurator debug=1]
```

### Paramètres

| Paramètre | Type | Défaut | Description |
|---|---|---|---|
| `id` | int | `0` | ID de la piscine. Si `0`, utilise le post courant |
| `debug` | int | `0` | Active les commentaires HTML de diagnostic (admin seulement) |

### Conditions d'affichage (gardes)

Le shortcode retourne une chaîne vide si l'une de ces conditions échoue :

1. Le post est de type `piscine`
2. `opt_aquacove` est activé
3. `slug_dimension` est renseigné
4. `zones_aquacove` contient au moins 1 zone
5. Au moins 1 couleur a un `slug_fichier` et un fichier fond existant
6. Au moins 1 zone a au moins 1 tapis avec un overlay existant

---

## Fonctionnement

### Architecture en layers

Le preview empile des `<img>` dans un container :

```
.mova-cfg-layers (position: relative)
├── img.mova-cfg-layer--fond     (position: relative → définit la hauteur)
├── img.mova-cfg-layer--overlay  (position: absolute, inset: 0) — marches
├── img.mova-cfg-layer--overlay  (position: absolute, inset: 0) — bancs
└── img.mova-cfg-layer--overlay  (position: absolute, inset: 0) — terrasse
```

Le fond est en `position: relative` avec `width: 100%; height: auto` — il dicte la taille du container selon les proportions naturelles de l'image. Les overlays se calent en absolute par-dessus.

### Interactions

| Action | Comportement |
|---|---|
| Clic swatch couleur | Change le fond. Les overlays tapis restent en place |
| Clic swatch tapis (dans une zone) | Change l'overlay de cette zone uniquement |
| Toggle zone off | Cache l'overlay + grise les swatches de la zone |
| Toggle zone on | Réaffiche l'overlay avec le tapis actif de la zone |
| Coche/décoche option | Met à jour les options envoyées au formulaire de devis |
| Clic « Obtenir un devis » | Redirige vers le formulaire avec model, couleur WP slug, et options cochées en query params |

### Validation côté serveur

PHP vérifie `file_exists()` pour chaque combinaison :
- Une couleur n'apparaît que si son fond existe
- Un tapis n'apparaît dans une zone que si son overlay pour cette zone existe
- Les zones sans aucun tapis valide sont retirées automatiquement

---

## Variable JavaScript `movaConfigurator`

Passée via `wp_localize_script`, contient :

```js
{
    baseUrl: "https://…/assets/images/tapis-aquacove/",
    slugDimension: "12x34",
    zones: ["marches", "bancs", "terrasse"],
    defaultCouleur: "cieldeminuit",
    defaultsTapis: {
        marches: "abysse",
        bancs: "abysse",
        terrasse: "abysse"
    },
    couleurs: [
        { slug: "cieldeminuit", wpSlug: "ciel-de-minuit", name: "Ciel de minuit", swatch: "https://…/thumb.jpg" },
        // ...
    ],
    options: [
        { slug: "jets", label: "Jets de massage" },
        { slug: "badujet", label: "BaduJet Turbo" }
    ],
    devisUrl: "https://piscinesmova.preprod.io/demandez-un-devis/",
    modelSlug: "12x34",
    tapisParZone: {
        marches: [
            { slug: "abysse", name: "Abysse", swatch: "https://…/thumb.jpg" },
            // ...
        ],
        bancs: [ /* ... */ ],
        terrasse: [ /* ... */ ]
    }
}
```

### Construction des URLs (côté JS)

```js
// Fond
cfg.baseUrl + 'piscine-' + cfg.slugDimension + '-' + couleurSlug + '.png'

// Overlay
cfg.baseUrl + cfg.slugDimension + '-' + tapisSlug + '-' + zone + '.png'
```

---

## Intégration avec le formulaire de devis

Le bouton « Obtenir un devis » redirige vers le formulaire `[mova_quote_form]` en passant des query params :

```
https://piscinesmova.preprod.io/demandez-un-devis/?model=12x34&couleur=ciel-de-minuit&options=jets,badujet
```

| Param | Source | Description |
|---|---|---|
| `model` | `modelSlug` (post_name du CPT piscine) | Pré-coche le modèle dans le formulaire |
| `couleur` | `wpSlug` du terme `couleur_piscine` actif | Pré-sélectionne la couleur dans le dropdown |
| `options` | Checkboxes cochées (séparées par virgule) | Optionnel — slugs des options sélectionnées |

### Distinction `slug` vs `wpSlug`

Chaque couleur possède deux slugs :
- **`slug`** (= `slug_fichier` ACF) : utilisé pour construire les URLs d'images (ex: `grislunaire`)
- **`wpSlug`** (= `term->slug` WP) : utilisé pour les query params du devis (ex: `gris-lunaire`)

Le JS maintient un mapping `couleurWpSlugMap` pour la conversion.

### Ajouter des options

Pour ajouter une nouvelle option (ex: Éclairage) :

1. **ACF** — Ajouter un champ `opt_eclairage` (True/False) dans `group_piscine_details.json`, onglet « Configurateur & Options »
2. **PHP** — Ajouter dans le bloc options de `inc/pool-configurator.php` :
   ```php
   if ( get_field( 'opt_eclairage', $post_id ) ) {
       $options[] = array( 'slug' => 'eclairage', 'label' => 'Éclairage' );
   }
   ```
3. **Formulaire de devis** — Si besoin, adapter `inc/quote-form.php` pour lire le param `options` et pré-cocher les cases correspondantes

---

## Personnalisation CSS

### Préfixe
Toutes les classes utilisent le préfixe `mova-cfg-`.

### Variables de design

| Propriété | Valeur | Usage |
|---|---|---|
| Couleur primaire | `#1a4759` | Titres, bordures actives, fond toggle on |
| Taille swatches | `72px` desktop / `48px` mobile | Pastilles couleur et tapis |
| Breakpoints | `1024px`, `767px` | Tablette, mobile |

### Classes utiles

| Classe | Élément | Description |
|---|---|---|
| `.mova-cfg` | Conteneur | Grid 2 colonnes |
| `.mova-cfg-preview` | Colonne gauche | Sticky `top: 120px` (static en mobile) |
| `.mova-cfg-layers` | Container layers | Position relative, background #f5f6f8 |
| `.mova-cfg-layer--fond` | Image fond | `position: relative` — dicte la hauteur |
| `.mova-cfg-layer--overlay` | Image overlay | `position: absolute; inset: 0; pointer-events: none` |
| `.mova-cfg-swatch.is-active` | Swatch sélectionné | Bordure + box-shadow double |
| `.mova-cfg-zone-toggle` | Toggle on/off | Style interrupteur (36×20px) |
| `.mova-cfg-zone-toggle.is-active` | Toggle activé | Background `#1a4759` |
| `.mova-cfg-zone-swatches.is-disabled` | Swatches zone off | Opacité 0.35, pointer-events none |
| `.mova-cfg-section--zone` | Section zone | Contient header + swatches + label |
| `.mova-cfg-options` | Container checkboxes | Flex-wrap, gap 12px/24px |
| `.mova-cfg-option` | Label + checkbox | Option individuelle (Jets, BaduJet…) |
| `.mova-cfg-section--cta` | Section bouton | Contient le bouton devis |
| `.mova-cfg-devis-btn` | Bouton devis | Pleine largeur, fond #1a4759, border-radius 8px |
| `.mova-cfg-devis-icon` | Icône « + » | Affiché à droite du texte |

---

## Mode debug

Ajouter `debug=1` au shortcode pour obtenir des commentaires HTML indiquant quelle garde bloque :

```html
<!-- mova-cfg: opt_aquacove est désactivé -->
<!-- mova-cfg: slug_dimension est vide -->
<!-- mova-cfg: zones_aquacove est vide -->
<!-- mova-cfg: 0 couleurs valides sur 8 brutes. Vérifiez slug_fichier… -->
<!-- mova-cfg: 0 tapis valides sur 12 bruts. Vérifiez slug_fichier… -->
```

Visible uniquement dans le code source HTML et uniquement pour les administrateurs WordPress (`manage_options`).
