# Shortcode `[mova_section_filter]`

Affiche un menu déroulant (`<select>`) permettant de filtrer les sections Elementor de la page **Espace détaillant** par leur CSS ID.

---

## Fichiers

| Rôle | Chemin |
|---|---|
| Shortcode PHP | `inc/dealer-space-filter.php` |
| Styles | `assets/css/dealer-space-filter.css` |
| Script | `assets/js/dealer-space-filter.js` |

---

## Usage dans Elementor

### 1. Assigner un CSS ID à chaque section

Dans l'éditeur Elementor, pour chaque section à filtrer :

1. Sélectionner la section
2. Onglet **Avancé** → champ **CSS ID**
3. Saisir l'identifiant (ex. `garanties`, `logos`, `videos`...)

> L'ID doit être unique sur la page et ne contenir que des lettres minuscules, chiffres et tirets (pas d'espaces ni d'accents).

### 2. Placer le shortcode

Dans le widget **Shortcode** d'Elementor, insérer :

```
[mova_section_filter options="Tout afficher:all,Garanties:garanties,Manuels et procédures:manuels,Logos:logos,Images:images,Dessins et fiches techniques:fiches-techniques,Vidéos:videos,Formulaires:formulaires"]
```

Placer ce widget **au-dessus** des sections à filtrer sur la page.

---

## Attributs

| Attribut | Type | Défaut | Description |
|---|---|---|---|
| `options` | `string` | `Tout afficher:all` | Liste séparée par des virgules de paires `Label:css-id`. Utiliser `all` comme ID pour l'option "Tout afficher". |
| `label` | `string` | `Filtrer les sections` | Texte du label affiché à gauche du select. |
| `class` | `string` | _(vide)_ | Classe CSS supplémentaire ajoutée au wrapper. |

---

## Exemples

### Basique

```
[mova_section_filter options="Tout afficher:all,Garanties:garanties,Logos:logos,Vidéos:videos"]
```

### Sans label

```
[mova_section_filter label="" options="Tout afficher:all,Garanties:garanties,Logos:logos"]
```

### Label personnalisé

```
[mova_section_filter label="Afficher" options="Tout afficher:all,Garanties:garanties,Logos:logos"]
```

### Avec classe CSS custom

```
[mova_section_filter class="mon-filtre" options="Tout afficher:all,Garanties:garanties"]
```

---

## Sections recommandées — Espace détaillant

| Label affiché | CSS ID suggéré |
|---|---|
| Tout afficher | `all` |
| Garanties | `garanties` |
| Manuels et procédures | `manuels` |
| Logos | `logos` |
| Images | `images` |
| Dessins et fiches techniques | `fiches-techniques` |
| Vidéos | `videos` |
| Formulaires | `formulaires` |

---

## Comportement JS

- À l'initialisation : si la valeur du select est `all` (ou vide), toutes les sections sont visibles.
- Lors d'un changement de sélection :
  - Si `all` → toutes les sections ciblées sont affichées.
  - Sinon → seule la section dont l'ID correspond à la valeur sélectionnée est affichée, les autres reçoivent la classe `mova-sf-hidden` (`display: none`).
  - Un scroll fluide amène la section visible dans le viewport (avec un offset pour le menu sticky).

---

## Notes

- Le shortcode peut être utilisé **plusieurs fois sur la même page** avec des ensembles de sections différents.
- Si un CSS ID défini dans les options n'existe pas sur la page, il est simplement ignoré (aucune erreur JS).
- Compatible Elementor **Flexbox Containers** (`e-container`) et l'ancien mode **Sections** (`.elementor-section`).
