# Documentation — Shortcode `[mova_tapis_catalog]`

## Sommaire
1. [Vue d'ensemble](#vue-densemble)
2. [Fichiers du projet](#fichiers-du-projet)
3. [Prérequis WordPress](#prérequis-wordpress)
4. [Utilisation du shortcode](#utilisation-du-shortcode)
5. [Fonctionnement](#fonctionnement)
6. [Variable JavaScript `movaTapisCatalog`](#variable-javascript-movatapiscatalog)
7. [Personnalisation CSS](#personnalisation-css)

---

## Vue d'ensemble

Le shortcode `[mova_tapis_catalog]` affiche un catalogue de prévisualisation des tapis AquaCove. Il présente tous les modèles de tapis disponibles dans une grille de cartes. Un clic sur une carte affiche la zone de détail avec :

- **Image de démonstration** (preview large)
- **Nom du modèle** et description du terme
- **Texture** (swatch agrandi)

Le composant est entièrement côté client (pas d'AJAX). Les données proviennent de la taxonomie `modele_tapis` et de ses champs ACF.

---

## Fichiers du projet

| Fichier | Rôle |
|---|---|
| `inc/tapis-catalog.php` | Shortcode WordPress — lecture des termes `modele_tapis`, HTML, `wp_localize_script` |
| `assets/js/tapis-catalog.js` | Logique JS : sélection de carte, affichage preview, préchargement image |
| `assets/css/tapis-catalog.css` | Styles : grille, cartes, zone détail, responsive |

---

## Prérequis WordPress

### Taxonomie

| Taxonomie | Slug machine | Usage |
|---|---|---|
| Modèles de tapis | `modele_tapis` | Chaque terme = un modèle de tapis AquaCove |

### Champs ACF sur la taxonomie `modele_tapis`

| Champ | Clé ACF | Type | Description |
|---|---|---|---|
| Pastille de texture | `swatch_tapis` | Image (ID) | Pastille miniature du motif |
| Image de démonstration | `image_preview_tapis` | Image (ID) | Photo de démonstration / rendu du tapis |

---

## Utilisation du shortcode

### Basique
```
[mova_tapis_catalog]
```

### Avec limite
```
[mova_tapis_catalog limit="6"]
```

### Tri par slug
```
[mova_tapis_catalog orderby="slug"]
```

### Paramètres

| Paramètre | Type | Défaut | Description |
|---|---|---|---|
| `limit` | int | `0` | Nombre max de tapis à afficher. `0` = tous |
| `orderby` | string | `name` | Tri des termes (`name`, `slug`, `term_id`, `count`) |

---

## Fonctionnement

### Flow en 2 étapes

1. **Grille de cartes** — Chaque tapis est représenté par une carte avec sa pastille de texture et son nom
2. **Zone détail** — Un clic sur une carte affiche l'image de démonstration, le nom, la description du terme et la texture agrandie

### Interactions

| Action | Comportement |
|---|---|
| Clic carte tapis | Active la carte, affiche la zone détail avec preview + infos |
| Clic autre carte | Change le tapis affiché dans la zone détail |

### Préchargement

Le JS précharge l'image de démonstration via `new Image()` avant de la basculer dans le preview, avec une classe `.loading` pendant le chargement.

---

## Variable JavaScript `movaTapisCatalog`

Passée via `wp_localize_script`, contient :

```js
{
    tapis: [
        {
            term_id: 12,
            name: "Abysse",
            slug: "abysse",
            description: "Motif profond aux tons bleutés",
            swatch: "https://…/uploads/2026/04/swatch-abysse.jpg",
            preview: "https://…/uploads/2026/04/demo-abysse.jpg"
        },
        // ...
    ]
}
```

---

## Personnalisation CSS

### Préfixe
Toutes les classes utilisent le préfixe `mova-tc-`.

### Variables de design

| Propriété | Valeur | Usage |
|---|---|---|
| Couleur primaire | `#1a4759` | Titres, bordures actives |
| Taille carte min | `160px` desktop / `120px` mobile | Largeur min grille |
| Breakpoints | `1024px`, `767px` | Tablette, mobile |

### Classes utiles

| Classe | Élément | Description |
|---|---|---|
| `.mova-tc` | Conteneur | Wrapper principal |
| `.mova-tc-grid` | Grille | `auto-fill, minmax(160px, 1fr)` |
| `.mova-tc-card` | Carte tapis | Bouton cliquable avec swatch + nom |
| `.mova-tc-card.active` | Carte active | Bordure + box-shadow double |
| `.mova-tc-card-swatch` | Image swatch | `aspect-ratio: 1/1`, cover |
| `.mova-tc-card-name` | Nom tapis | Sous la pastille |
| `.mova-tc-detail` | Zone détail | Masquée par défaut, grid 2 colonnes |
| `.mova-tc-preview` | Colonne preview | Sticky `top: 120px` |
| `.mova-tc-preview-img` | Image demo | Contain dans `4/3` |
| `.mova-tc-preview-img.loading` | Chargement | Opacité réduite |
| `.mova-tc-panel` | Colonne infos | Nom, description, swatch large |
| `.mova-tc-swatch-img` | Swatch agrandi | `120px`, border-radius 8px |
| `.mova-tc-no-preview` | Message fallback | Si aucune image de démo |
