# Documentation — Shortcode `[mova_pool_configurator]`

## Sommaire
1. [Vue d'ensemble](#vue-densemble)
2. [Fichiers du projet](#fichiers-du-projet)
3. [Prérequis WordPress](#prérequis-wordpress)
4. [Utilisation du shortcode](#utilisation-du-shortcode)
5. [Fonctionnement](#fonctionnement)
6. [Variable JavaScript `movaConfigurator`](#variable-javascript-movaconfigurator)
7. [Intégration avec le formulaire de devis](#intégration-avec-le-formulaire-de-devis)
8. [Personnalisation CSS](#personnalisation-css)
9. [Mode debug](#mode-debug)
10. [Migration depuis la version filesystem](#migration-depuis-la-version-filesystem)

---

## Vue d'ensemble

Le shortcode `[mova_pool_configurator]` affiche un configurateur visuel AquaCove qui superpose des couches PNG transparentes (tapis) sur un fond de couleur de coque. Il se compose de :

- **Zone de prévisualisation (sticky)** : empilement de layers PNG — un fond (couleur coque) + un overlay par zone (marches, bancs, terrasse, fond)
- **Bouton zoom** : ouvre une lightbox plein écran avec les layers clonées
- **Panneau Couleur de la coque** : swatches cliquables pour changer le fond
- **Panneau Tapis par zone** : chaque zone a son propre sélecteur de tapis indépendant, avec un toggle on/off
- **Section Options** : checkboxes dynamiques pour les options compatibles (Jets de massage, BaduJet Turbo, etc.)
- **Bouton « Obtenir un devis »** : redirige vers le formulaire de devis avec pré-remplissage (modèle, couleur, options, tapis par zone)

Chaque zone peut avoir un modèle de tapis différent. Quand on change la couleur de la coque, les overlays tapis restent en place. Quand on désactive une zone, son overlay disparaît et ses swatches sont grisés.

Le composant est entièrement côté client (pas d'AJAX). Les URLs des images proviennent directement de la médiathèque WordPress — zéro opération filesystem.

---

## Fichiers du projet

| Fichier | Rôle |
|---|---|
| `inc/pool-configurator.php` | Shortcode WordPress — gardes, lecture des repeaters ACF, HTML, `wp_localize_script` |
| `assets/js/pool-configurator.js` | Logique JS : interactions couleur/tapis/zones, gestion des layers, préchargement `new Image()`, lightbox, bouton devis |
| `assets/css/pool-configurator.css` | Styles : layout grille, layers superposées, swatches, zone toggles, lightbox, responsive |

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
| Couleurs du configurateur | `couleurs_configurateur` | Repeater | Couleurs avec images fonds uploadées (visible si `opt_aquacove = true`) |
| ↳ Couleur | `couleur` | Taxonomy select (`couleur_piscine`) | Terme couleur |
| ↳ Image fond | `image_fond` | Image (ID) | PNG de la piscine dans cette couleur, uploadé en médiathèque |
| Zones AquaCove | `zones_configurateur` | Repeater | Zones avec tapis et overlays (visible si `opt_aquacove = true`) |
| ↳ Zone | `zone` | Select | marches, bancs, terrasse ou fond |
| ↳ Tapis zone | `tapis_zone` | Repeater imbriqué | Tapis disponibles pour cette zone |
| ↳↳ Modèle de tapis | `modele_tapis` | Taxonomy select (`modele_tapis`) | Terme tapis |
| ↳↳ Overlay | `overlay` | Image (ID) | PNG transparent du tapis pour cette zone, uploadé en médiathèque |
| ↳↳ Pastille (optionnel) | `swatch_override` | Image (ID) | Override la pastille par défaut du terme |
| Couleurs disponibles | `couleurs_disponibles` | Taxonomy multi (`couleur_piscine`) | Partagé avec pool-color-preview |
| Compatible Jets | `opt_jets` | True/False | Affiche l'option « Jets de massage » |
| Compatible BaduJet | `opt_badujet` | True/False | Affiche l'option « BaduJet Turbo » |

### Champs ACF sur la taxonomie `couleur_piscine`

| Champ | Clé ACF | Type | Description |
|---|---|---|---|
| Swatch couleur | `swatch_couleur` | Image | Pastille miniature (thumbnail) |

### Champs ACF sur la taxonomie `modele_tapis`

| Champ | Clé ACF | Type | Description |
|---|---|---|---|
| Swatch tapis | `swatch_tapis` | Image | Pastille de texture par défaut (thumbnail) |

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
3. Le repeater `couleurs_configurateur` contient au moins 1 couleur avec image fond valide
4. Le repeater `zones_configurateur` contient au moins 1 zone avec au moins 1 tapis ayant un overlay valide

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

### Source des images

Toutes les images proviennent de la **médiathèque WordPress** via `wp_get_attachment_image_url()`. Aucun chemin de fichier n'est construit côté PHP ou JS. Les URLs sont des URLs WordPress standard (ex: `https://example.com/wp-content/uploads/2026/04/piscine-12x34-grislunaire.png`).

### Interactions

| Action | Comportement |
|---|---|
| Clic swatch couleur | Change le fond via `fondUrl` de la couleur. Les overlays tapis restent en place |
| Clic swatch tapis (dans une zone) | Change l'overlay de cette zone via `overlayUrl` du tapis |
| Toggle zone off | Cache l'overlay + grise les swatches de la zone |
| Toggle zone on | Réaffiche l'overlay avec le tapis actif de la zone |
| Coche/décoche option | Met à jour les options envoyées au formulaire de devis |
| Clic bouton zoom | Clone les layers dans une lightbox plein écran |
| Clic « Obtenir un devis » | Redirige vers le formulaire avec model, couleur, options et tapis par zone en query params |

### Lightbox

Le bouton zoom (icône loupe) clone le conteneur `.mova-cfg-layers` dans un overlay plein écran (`.mova-cfg-lightbox`). Fermeture via : bouton ✕, touche Escape, ou clic sur le fond sombre.

---

## Variable JavaScript `movaConfigurator`

Passée via `wp_localize_script`, contient :

```js
{
    zones: ["marches", "bancs", "terrasse"],
    defaultCouleur: "ciel-de-minuit",
    defaultsTapis: {
        marches: "abysse",
        bancs: "abysse",
        terrasse: "abysse"
    },
    couleurs: [
        {
            slug: "ciel-de-minuit",
            name: "Ciel de minuit",
            fondUrl: "https://…/uploads/2026/04/piscine-12x34-cieldeminuit.png",
            swatch: "https://…/uploads/2026/04/swatch-ciel.jpg"
        },
        // ...
    ],
    tapisParZone: {
        marches: [
            {
                slug: "abysse",
                name: "Abysse",
                overlayUrl: "https://…/uploads/2026/04/12x34-abysse-marches.png",
                swatch: "https://…/uploads/2026/04/swatch-abysse.jpg"
            },
            // ...
        ],
        bancs: [ /* ... */ ],
        terrasse: [ /* ... */ ]
    },
    options: [
        { slug: "jets", label: "Jets de massage" },
        { slug: "badujet", label: "BaduJet Turbo" }
    ],
    devisUrl: "https://piscinesmova.preprod.io/demandez-un-devis/",
    modelSlug: "12x34"
}
```

### Lookups JS

Le JS construit deux index au démarrage :

```js
// couleurIndex[slug] → { slug, name, fondUrl, swatch }
var couleurIndex = {};
cfg.couleurs.forEach(function (c) { couleurIndex[c.slug] = c; });

// tapisIndex[zone][slug] → { slug, name, overlayUrl, swatch }
var tapisIndex = {};
```

Le changement d'image se fait par lookup direct :
```js
// Fond
layerFond.src = couleurIndex[slug].fondUrl;

// Overlay
layer.src = tapisIndex[zone][slug].overlayUrl;
```

---

## Intégration avec le formulaire de devis

Le bouton « Obtenir un devis » redirige vers le formulaire `[mova_quote_form]` en passant des query params :

```
https://piscinesmova.preprod.io/demandez-un-devis/?model=12x34&couleur=ciel-de-minuit&options=jets,badujet&tapis_marches=abysse&tapis_bancs=sable&tapis_terrasse=mova
```

| Param | Source | Description |
|---|---|---|
| `model` | `modelSlug` (post_name du CPT piscine) | Pré-coche le modèle dans le formulaire |
| `couleur` | `slug` du terme `couleur_piscine` actif | Pré-sélectionne la couleur dans le dropdown |
| `options` | Checkboxes cochées (séparées par virgule) | Slugs des options sélectionnées |
| `tapis_{zone}` | `slug` du terme `modele_tapis` actif dans la zone | Un param par zone active (marches, bancs, terrasse, fond) |

Les params `tapis_{zone}` ne sont envoyés que pour les zones activées (toggle on) ayant un tapis sélectionné.

> **Note** : un seul slug est utilisé partout (`term->slug`). Il n'y a plus de distinction entre slug fichier et slug WordPress.

### Ajouter des options

Pour ajouter une nouvelle option (ex: Éclairage) :

1. **ACF** — Ajouter un champ `opt_eclairage` (True/False) dans `group_piscine_details.json`, onglet « Configurateur & Options »
2. **PHP** — Ajouter dans le bloc options de `inc/pool-configurator.php` :
   ```php
   if ( get_field( 'opt_eclairage', $post_id ) ) {
       $options[] = array( 'slug' => 'eclairage', 'label' => 'Éclairage' );
   }
   ```
3. **Formulaire de devis** — Si besoin, adapter `inc/quote-form.php` pour lire le param `options`

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
| `.mova-cfg-zoom` | Bouton zoom | Position absolute dans le preview |
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
| `.mova-cfg-lightbox` | Lightbox overlay | Fixed, z-index 9999, fond semi-transparent |
| `.mova-cfg-lightbox.open` | Lightbox ouverte | `display: flex` |
| `.mova-cfg-lb-close` | Bouton fermer | Position absolute top-right |

---

## Mode debug

Ajouter `debug=1` au shortcode pour obtenir des commentaires HTML indiquant quelle garde bloque :

```html
<!-- mova-cfg: post_type n'est pas piscine -->
<!-- mova-cfg: opt_aquacove est désactivé -->
<!-- mova-cfg: 0 couleurs valides sur 8 lignes repeater. Vérifiez que chaque ligne a une couleur et une image fond. -->
<!-- mova-cfg: 0 zones valides sur 3 lignes repeater. Vérifiez que chaque zone a au moins un tapis avec overlay. -->
```

Visible uniquement dans le code source HTML et uniquement pour les administrateurs WordPress (`manage_options`).

---

## Migration depuis la version filesystem

> Cette section documente la migration effectuée. À conserver comme référence puis supprimer quand la migration est terminée.

### Avant (v1 — filesystem)

Les images étaient stockées dans `assets/images/tapis-aquacove/` (~706 PNG) et référencées par convention de nommage :
- Fonds : `piscine-{slug_dimension}-{slug_fichier}.png`
- Overlays : `{slug_dimension}-{slug_fichier}-{zone}.png`

Chaque couleur et tapis avait un champ `slug_fichier` sur sa taxonomie, distinct du `term->slug` WordPress. Le PHP validait chaque combinaison via `file_exists()` (~69 appels par page).

### Après (v2 — repeaters ACF)

Les images sont uploadées en médiathèque WordPress et liées explicitement via des repeaters ACF sur le CPT piscine. Un seul slug (`term->slug`) est utilisé partout. Zéro opération filesystem.

### Script de migration

Le script WP-CLI `inc/cli/migrate-configurator-images.php` (temporaire) fournit :
- `wp mova upload-images` — Bulk-upload des PNG vers la médiathèque
- `wp mova mapping-report` — Rapport des associations existantes pour guider le remplissage des repeaters

À supprimer après migration complète, avec le dossier `assets/images/tapis-aquacove/`.
