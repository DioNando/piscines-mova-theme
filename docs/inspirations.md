# Documentation — Shortcode `[mova_inspirations]`

## Sommaire
1. [Vue d'ensemble](#vue-densemble)
2. [Fichiers du projet](#fichiers-du-projet)
3. [Prérequis WordPress](#prérequis-wordpress)
4. [Utilisation du shortcode](#utilisation-du-shortcode)
5. [Fonctionnalités](#fonctionnalités)
6. [Gestion du contenu (admin)](#gestion-du-contenu-admin)
7. [Personnalisation CSS](#personnalisation-css)
8. [Référence technique AJAX](#référence-technique-ajax)

---

## Vue d'ensemble

Le shortcode `[mova_inspirations]` affiche une galerie photo d'inspirations piscines (CPT `inspiration`). La page se compose de :

- **Barre de filtres** : boutons pilules par catégorie d'inspiration
- **Grille 2 colonnes** : cartes photo avec tailles variées (normal, pleine largeur, double hauteur)
- **Bouton « Voir plus »** : pagination en append via AJAX
- **Lightbox intégrée** : navigation clavier + flèches, affichage plein écran

Le filtrage et la pagination sont gérés en AJAX (`admin-ajax.php`). Chaque changement de filtre reset la grille et recharge depuis la page 1. Les requêtes sont sécurisées par un nonce WordPress.

---

## Fichiers du projet

| Fichier | Rôle |
|---|---|
| `inc/inspirations.php` | Shortcode WordPress (HTML filtres + grille + lightbox) + handler AJAX `mova_inspirations` (WP_Query avec `tax_query` dynamique, tri par `ordre_affichage`, pagination serveur) |
| `assets/js/inspirations.js` | Logique JS : appels AJAX `fetch()`, rendu dynamique des cartes, gestion filtres, « Voir plus » en append, lightbox avec navigation |
| `assets/css/inspirations.css` | Styles : grille CSS Grid 2 colonnes, tailles dynamiques, overlay au hover, lightbox, animations, responsive |
| `acf-json/post_type_69ce947a3cec0.json` | Enregistrement du CPT `inspiration` via ACF |
| `acf-json/group_inspiration_details.json` | Champs ACF (légende, crédit, taille, ordre, piscine associée) |
| `acf-json/taxonomy_69ce94a0b1234.json` | Taxonomie `categorie_inspiration` |

---

## Prérequis WordPress

### Custom Post Type

- **`inspiration`** — Chaque photo d'inspiration est un post de ce type
- Supporte : titre, éditeur, image à la une, champs personnalisés
- Icône admin : `dashicons-heart`
- REST API activée

### Taxonomie

| Taxonomie | Slug machine | Hiérarchique | Usage |
|---|---|---|---|
| Catégories Inspiration | `categorie_inspiration` | Oui | Filtres pilules en frontend, colonne admin |

**Exemples de termes** : Résidentiel, Commercial, Hiver, Été, Nuit, Jour, Spa, Couloir de nage

### Champs ACF sur le CPT `inspiration` (`group_inspiration_details`)

| Champ | Nom machine | Type | Usage galerie |
|---|---|---|---|
| Légende | `legende` | Texte | Texte affiché dans l'overlay et la lightbox |
| Crédit photo | `credit_photo` | Texte | Crédit sous la légende (optionnel) |
| Taille d'affichage | `taille_affichage` | Select | Contrôle la taille de la carte dans la grille |
| Piscine associée | `piscine_associee` | Relationship → piscine | Lien « Voir le modèle » dans l'overlay |
| Ordre d'affichage | `ordre_affichage` | Nombre | Tri ascendant (plus petit = affiché en premier) |

### Valeurs du champ `taille_affichage`

| Valeur | Classe CSS | Comportement dans la grille |
|---|---|---|
| `normal` | `.mova-insp-item--normal` | 1 colonne, 1 rangée (320px) |
| `wide` | `.mova-insp-item--wide` | 2 colonnes (`grid-column: span 2`), 1 rangée |
| `tall` | `.mova-insp-item--tall` | 1 colonne, 2 rangées (`grid-row: span 2`, 656px) |

> L'image principale utilise l'**image à la une** (featured image) du post. Aucun champ image dédié n'est nécessaire.

---

## Utilisation du shortcode

### Basique
```
[mova_inspirations]
```
Affiche 12 inspirations par page par défaut.

### Avec paramètre
```
[mova_inspirations per_page="18"]
```

| Attribut | Type | Défaut | Description |
|---|---|---|---|
| `per_page` | Nombre | `12` | Nombre d'items visibles avant de cliquer « Voir plus » |

> **Recommandation** : utiliser un multiple de 2 pour un rendu propre sur la grille 2 colonnes.

---

## Fonctionnalités

### 1. Filtrage par catégorie
Boutons pilules centrés au-dessus de la grille. « Toutes » est actif par défaut. Un seul filtre actif à la fois. Chaque clic envoie une requête AJAX et remplace le contenu de la grille.

### 2. Grille à tailles mixtes
Chaque carte adopte une taille contrôlée par le champ ACF `taille_affichage` :
- **Normal** : occupe 1 cellule (50% largeur)
- **Pleine largeur** : s'étend sur 2 colonnes (100% largeur)
- **Double hauteur** : s'étend sur 2 rangées (idéal pour les photos verticales)

### 3. Overlay au hover
Au survol d'une carte, un dégradé sombre apparaît avec :
- Légende (titre de l'inspiration)
- Crédit photo (si renseigné)
- Lien « Voir le modèle » (si une piscine est associée)

Sur mobile, l'overlay est toujours visible (pas de hover).

### 4. Lightbox
Clic sur une carte (hors lien) ouvre la photo en plein écran avec :
- **Navigation** : flèches gauche/droite pour parcourir toutes les photos chargées
- **Clavier** : `Escape` pour fermer, `←`/`→` pour naviguer
- **Légende** : affichée sous l'image dans la lightbox
- **Fermeture** : bouton × ou clic sur le fond sombre

### 5. Charger plus
Bouton en bas de la grille. Incrémente la page et envoie une requête AJAX en mode *append*. Se masque automatiquement quand il n'y a plus de résultats (`hasMore === false`).

### 6. Animation d'entrée
Les cartes apparaissent avec un fade-in + translation vers le haut, avec un délai progressif de 50ms entre chaque carte.

### 7. Tri par ordre personnalisé
Les items sont triés par le champ `ordre_affichage` (ascendant). Cela permet de contrôler précisément l'arrangement de la grille : placer une image `wide` entre deux `normal`, etc.

### 8. Responsive

| Breakpoint | Comportement |
|---|---|
| > 768px | Grille 2 colonnes, `grid-auto-rows: 320px`, overlay au hover |
| ≤ 767px | Grille 1 colonne, `grid-auto-rows: 260px`, overlay toujours visible, `wide` et `tall` ignorés |

---

## Gestion du contenu (admin)

### Ajouter une inspiration

1. Aller dans **Inspirations > Ajouter**
2. Renseigner le **titre** (utilisé en interne uniquement)
3. Définir l'**image à la une** (c'est la photo affichée dans la galerie)
4. Remplir les champs ACF :
   - **Légende** : texte affiché au survol (ex: « Modèle 12x28 à fond plat - Loup gris »)
   - **Crédit photo** : optionnel (ex: « © Vanessa Pilon »)
   - **Taille d'affichage** : `Normal`, `Pleine largeur` ou `Double hauteur`
   - **Piscine associée** : sélectionner le modèle correspondant (optionnel)
   - **Ordre d'affichage** : nombre entier pour le tri
5. Assigner une **catégorie d'inspiration** (optionnel, utilisé pour les filtres)
6. **Publier**

### Conseils de mise en page

| Pour obtenir | Configuration |
|---|---|
| Photo hero en haut de page | Ordre: 1, Taille: `wide` |
| Deux photos côte à côte | Ordre: 2 et 3, Taille: `normal` + `normal` |
| Grande image verticale à gauche | Ordre: 4, Taille: `tall`, suivie d'un `normal` |
| Bandeau panoramique | Taille: `wide` |

### Exemple d'arrangement type

```
Ordre 1  → wide   → [========= Panorama =========]
Ordre 2  → normal → [ Photo A  ][ Photo B  ] ← Ordre 3 normal
Ordre 4  → tall   → [ Photo  C ][ Photo D  ] ← Ordre 5 normal
                     [          ][ Photo E  ] ← Ordre 6 normal
Ordre 7  → wide   → [========= Panorama =========]
```

---

## Personnalisation CSS

### Couleur principale
`#1a4759` — Utilisée pour les filtres, liens, boutons. Remplacer toutes les occurrences dans `inspirations.css`.

### Dimensions clés

| Propriété | Valeur | Description |
|---|---|---|
| Grille | `repeat(2, 1fr)` | 2 colonnes desktop |
| Hauteur rangée | `320px` | `grid-auto-rows` |
| Gap | `16px` | Espacement entre cartes |
| Border radius | `10px` | Arrondi des cartes |
| Overlay gradient | `transparent → rgba(0,0,0,0.6)` | Dégradé au survol |
| Zoom hover | `scale(1.04)` | Agrandissement image |
| Animation durée | `0.4s` | Fade-in à l'entrée |
| Lightbox z-index | `99999` | Superposition maximale |

### Modifier le nombre de colonnes

Pour passer à 3 colonnes, modifier dans `inspirations.css` :

```css
.mova-insp-grid {
    grid-template-columns: repeat(3, 1fr);
}
```

> Ajuster aussi les tailles `wide` (garder `span 2` pour occuper 2/3, ou passer à `span 3` pour toute la largeur).

---

## Référence technique AJAX

### Action : `mova_inspirations`

- **URL** : `admin-ajax.php`
- **Méthode** : `POST`
- **Nonce** : `mova_inspirations_nonce` (champ `nonce`)

### Paramètres POST

| Paramètre | Type | Requis | Description |
|---|---|---|---|
| `action` | string | Oui | `mova_inspirations` |
| `nonce` | string | Oui | Nonce de sécurité |
| `page` | int | Non | Page courante (défaut: 1) |
| `per_page` | int | Non | Items par page (défaut: 12) |
| `categories[]` | array | Non | Slugs de `categorie_inspiration` |

### Réponse JSON (`wp_send_json_success`)

```json
{
    "success": true,
    "data": {
        "items": [
            {
                "id": 123,
                "thumbnail": "https://…/image-768x512.jpg",
                "thumbnail_full": "https://…/image-1024x683.jpg",
                "taille": "normal|wide|tall",
                "legende": "Modèle 12x28 à fond plat - Loup gris",
                "credit": "© Photographe",
                "piscine_link": "https://…/piscine/12x28/",
                "piscine_name": "12x28",
                "categories": ["residentiel", "ete"]
            }
        ],
        "total": 24,
        "hasMore": true
    }
}
```

### Tri serveur

Les résultats sont triés par `meta_value_num` sur le champ `ordre_affichage` en ordre ascendant (`ASC`).
