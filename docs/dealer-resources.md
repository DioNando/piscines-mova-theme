# Shortcodes — Espace Détaillant (Ressources)

Shortcodes pour afficher les ressources téléchargeables de l'espace détaillant. Toutes les données sont gérées via la **page d'options ACF** (`Tableau de bord → Espace Détaillant`).

---

## Fichiers

| Rôle | Chemin |
|---|---|
| Shortcodes PHP | `inc/dealer-resources.php` |
| Styles | `assets/css/dealer-resources.css` |
| Page d'options ACF | `acf-json/group_espace_detaillant_options.json` |
| Icône téléchargement | `assets/images/download-solid-full.svg` |

---

## Prérequis

- Plugin **ACF Pro** (pour les pages d'options)
- La page d'options est enregistrée dans `functions.php` via `acf_add_options_page()`
- Synchroniser les groupes ACF : **Outils → Synchroniser les champs**

---

## Liste des shortcodes

### `[mova_dealer_formulaires]`
Liens vers les formulaires en ligne (Microsoft Forms, Google Forms…).

- Champ ACF : `ed_formulaires` (repeater → `label` + `url`)
- Rendu : liste de liens externes, icône document, mise en avant (bold)

```
[mova_dealer_formulaires]
```

---

### `[mova_dealer_garanties]`
Documents de garantie téléchargeables (PDF).

- Champ ACF : `ed_garanties` (repeater → `label` + `fichier`)
- Rendu : liste de liens avec icône téléchargement, ouverture dans un nouvel onglet

```
[mova_dealer_garanties]
```

---

### `[mova_dealer_manuels]`
Manuels d'installation et procédures (PDF).

- Champ ACF : `ed_manuels` (repeater → `label` + `fichier`)
- Rendu : liste de liens avec icône téléchargement, ouverture dans un nouvel onglet

```
[mova_dealer_manuels]
```

---

### `[mova_dealer_logos]`
Logos et sigles Mova (ZIP, SVG, PNG…).

- Champ ACF : `ed_logos` (repeater → `label` + `fichier`)
- Rendu : liste de liens avec **attribut `download`** (force le téléchargement, adapté aux ZIP)

```
[mova_dealer_logos]
```

---

### `[mova_dealer_images]`
Photos des piscines téléchargeables (JPG, WEBP, PNG).

- Champ ACF : `ed_images` (repeater → `label` + `fichier`)
- Rendu : liste de liens avec icône téléchargement, ouverture dans un nouvel onglet

```
[mova_dealer_images]
```

---

### `[mova_dealer_videos]`
Vidéos promotionnelles. Supporte les fichiers MP4 hébergés localement **et** les liens externes (YouTube/Vimeo).

- Champ ACF : `ed_videos` (repeater → `label` + `fichier` MP4 + `url_externe`)
- Si **fichier MP4** → lien avec attribut `download` + icône téléchargement
- Si **URL externe** → lien externe dans nouvel onglet + icône vidéo
- Si les deux sont renseignés → deux liens affichés sous le même item

```
[mova_dealer_videos]
```

---

## Configuration dans Elementor — Espace Détaillant

Structure recommandée de la page avec le shortcode de filtre :

```
[mova_section_filter options="Tout afficher:all,Formulaires:formulaires,Garanties:garanties,Manuels et procédures:manuels,Logos:logos,Images:images,Dessins et fiches techniques:fiches-techniques,Vidéos:videos"]
```

| Section Elementor (CSS ID) | Shortcode du contenu |
|---|---|
| `formulaires` | `[mova_dealer_formulaires]` |
| `garanties` | `[mova_dealer_garanties]` |
| `manuels` | `[mova_dealer_manuels]` |
| `logos` | `[mova_dealer_logos]` |
| `images` | `[mova_dealer_images]` |
| `fiches-techniques` | `[mova_dealer_files]` (grille par modèle de piscine) |
| `videos` | `[mova_dealer_videos]` |

---

## Gestion du contenu (Admin)

1. Aller dans **Tableau de bord → Espace Détaillant**
2. Choisir l'onglet correspondant (Formulaires, Garanties, Manuels, Logos, Images, Vidéos)
3. Ajouter / modifier / réordonner les lignes du répéteur
4. Cliquer sur **Mettre à jour**

Les changements sont immédiatement reflétés sur le site sans modifier Elementor.

---

## Icône téléchargement

L'icône est le SVG `assets/images/download-solid-full.svg` (Font Awesome Free), intégré **inline** avec `fill="currentColor"` pour hériter de la couleur du lien CSS.

La couleur suit `color` sur `.mova-dr-link` : `#1a4759` au repos, `#9c6d61` au survol.

---

## Classes CSS

| Classe | Élément |
|---|---|
| `.mova-dr-list` | `<ul>` — liste de liens |
| `.mova-dr-item` | `<li>` — item |
| `.mova-dr-item--form` | `<li>` — item formulaire (mise en avant) |
| `.mova-dr-link` | `<a>` — lien fichier / téléchargement |
| `.mova-dr-link--form` | `<a>` — lien formulaire (bold) |
| `.mova-dr-link--external` | `<a>` — lien externe vidéo |
| `.mova-dr-form-icon` | `<span>` — icône document formulaire |
| `.mova-dr-empty` | `<p>` — message si aucune donnée |
