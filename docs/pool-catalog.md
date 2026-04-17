# Documentation — Shortcode `[mova_pool_catalog]`

## Sommaire
1. [Vue d'ensemble](#vue-densemble)
2. [Fichiers du projet](#fichiers-du-projet)
3. [Prérequis WordPress](#prérequis-wordpress)
4. [Utilisation du shortcode](#utilisation-du-shortcode)
5. [Fonctionnalités](#fonctionnalités)
6. [Structure des données](#structure-des-données)
7. [Personnalisation CSS](#personnalisation-css)

---

## Vue d'ensemble

Le shortcode `[mova_pool_catalog]` affiche un catalogue filtrable de piscines (CPT `piscine`). La page se compose de :

- **Sidebar gauche (sticky)** : filtres par checkboxes — Catégories de piscine, Dimensions et Besoins
- **Zone principale** : compteur de résultats, grille de cartes 3 colonnes, bouton « Charger plus »

Le filtrage et la pagination sont gérés en AJAX (`admin-ajax.php`). Chaque changement de filtre ou clic « Charger plus » déclenche une requête `POST` vers le serveur qui retourne uniquement les piscines correspondantes. Les requêtes sont sécurisées par un nonce WordPress.

---

## Fichiers du projet

| Fichier | Rôle |
|---|---|
| `inc/pool-catalog.php` | Shortcode WordPress (HTML sidebar + filtres) + handler AJAX `mova_pool_catalog_filter` (WP_Query avec `tax_query` dynamique, pagination serveur) |
| `assets/js/pool-catalog.js` | Logique JS : appels AJAX `fetch()`, rendu dynamique des cartes, gestion filtres/checkboxes, « Charger plus » en append |
| `assets/css/pool-catalog.css` | Styles : layout sidebar + grille, cartes, filtres, bouton, spinner de chargement, responsive |

---

## Prérequis WordPress

### Custom Post Type
- **`piscine`** — Chaque modèle de piscine est un post de ce type

### Taxonomies associées au CPT `piscine`

| Taxonomie | Slug machine | Hiérarchique | Usage dans le catalogue |
|---|---|---|---|
| Catégories de piscine | `categorie_piscine` | Oui | Filtre checkbox + sous-titre des cartes |
| Dimensions | `dimension_piscine` | Oui | Filtre checkbox |
| Besoins | `besoin_piscine` | Oui | Filtre checkbox |
| Couleurs | `couleur_piscine` | Oui | Non utilisé dans le catalogue (utilisé sur la page single) |
| Modèles de tapis | `modele_tapis` | Non | Non utilisé dans le catalogue (utilisé sur la page single) |

### Champs ACF sur le CPT `piscine` (`group_piscine_details`)

| Champ | Nom machine | Type | Usage catalogue |
|---|---|---|---|
| Dimensions | `dimensions` | Texte | Non (affiché sur la single) |
| Profondeur | `profondeur` | Texte | Non |
| Volume d'eau | `volume_eau` | Texte | Non |
| Poids de la coque | `poids_coque` | Texte | Non |
| Galerie Photos | `galerie` | Gallery | Non (single) |
| **Image de la carte** | **`image_carte`** | **Image** | **Oui — image des cartes (priorité sur l'image mise en avant)** |
| Couleurs disponibles | `couleurs_disponibles` | Taxonomy (couleur_piscine) | Non (single/configurateur) |
| Compatible AquaCove | `opt_aquacove` | True/False | Non (single) |
| Compatible Jets | `opt_jets` | True/False | Non (single) |
| Compatible BaduJet | `opt_badujet` | True/False | Non (single) |
| Fiches Techniques | `fiches_techniques` | Repeater | Non (single) |
| Description | `description_modele` | WYSIWYG | Non (single) |
| Caractéristiques | `caracteristiques_modele` | WYSIWYG | Non (single) |
| Finitions | `finitions_options` | WYSIWYG | Non (single) |
| Technologies | `technologies_modele` | WYSIWYG | Non (single) |
| Modèles similaires | `modeles_similaires` | Relationship | Non (single) |

### Champs ACF sur les taxonomies

**Sur `categorie_piscine`** (`group_taxo_images`) :
- `image_categorie` (Image) — Image de la catégorie (non utilisée dans le catalogue actuellement)

**Sur `couleur_piscine`** (`group_couleur_taxonomy_settings`) :
- `swatch_couleur` (Image) — Pastille ronde pour le configurateur
- `image_ambiance` (Image) — Photo ambiance
- `description_couleur` (Textarea) — Texte marketing

**Sur `modele_tapis`** (`group_tapis_settings`) :
- `swatch_tapis` (Image) — Pastille texture
- `image_preview_tapis` (Image) — Image démo

> Le catalogue utilise le champ ACF **`image_carte`** de chaque post `piscine` en priorité. Si ce champ est vide, l'**image à la une** (featured image) est utilisée en fallback. Les taxonomies `categorie_piscine`, `dimension_piscine` et `besoin_piscine` alimentent les filtres.

---

## Utilisation du shortcode

### Basique
```
[mova_pool_catalog]
```
Affiche 9 modèles par page par défaut.

### Avec paramètre
```
[mova_pool_catalog per_page="12"]
```

| Attribut | Type | Défaut | Description |
|---|---|---|---|
| `per_page` | Nombre | `9` | Nombre de modèles visibles avant de cliquer « Charger plus » |

### Pré-sélection via paramètres URL

Il est possible de pré-cocher des filtres au chargement de la page en passant des paramètres dans l'URL. Utile pour créer des liens depuis d'autres pages (ex: bouton « Voir les modèles sportifs »).

| Paramètre | Filtre ciblé | Valeur attendue |
|---|---|---|
| `categorie` | Catégories de piscine | Slug du terme (`categorie_piscine`) |
| `dimension` | Dimensions | Slug du terme (`dimension_piscine`) |
| `besoin` | Besoins | Slug du terme (`besoin_piscine`) |

**Exemples :**
```
/catalogue/?categorie=avec-terrasse
/catalogue/?dimension=12x34&dimension=12x28
/catalogue/?categorie=sportive&besoin=nage
```

- Les valeurs multiples s'obtiennent en répétant le paramètre.
- Un slug inconnu (terme inexistant) est ignoré silencieusement.
- Si une catégorie est pré-sélectionnée, la case « Tous les modèles » est automatiquement décochée.

---

## Fonctionnalités

### 1. Filtrage par catégorie de piscine
Checkboxes dans la sidebar. Le checkbox « Tous les modèles » est coché par défaut. Quand on sélectionne une catégorie spécifique, « Tous » se décoche. Si on décoche toutes les catégories, « Tous » se recoche automatiquement. Chaque changement envoie une requête AJAX.

### 2. Filtrage par dimension
Checkboxes dans la sidebar. Aucune dimension cochée = toutes affichées. Plusieurs dimensions peuvent être cochées simultanément (logique OU via `tax_query`). Chaque changement envoie une requête AJAX.

### 3. Filtrage par besoin
Checkboxes dans la sidebar (section « Quel est votre besoin ? »). Visible uniquement si des termes `besoin_piscine` existent. Plusieurs besoins peuvent être cochés simultanément.

### 4. Filtres combinés
Les filtres catégorie, dimension et besoin sont combinés en logique ET (`tax_query` avec `relation => AND`) côté serveur.

### 5. Compteur dynamique
Affiche « X modèles » au-dessus de la grille, basé sur `found_posts` retourné par le serveur.

### 6. Grille de cartes
Chaque carte affiche :
- Image de la carte (`image_carte` ACF) ou image à la une en fallback (placeholder si aucune des deux n'est disponible)
- Titre du modèle (ex: « 12x34 »)
- Sous-titre = première catégorie (ex: « Piscine avec terrasse »)
- Flèche de lien →
- Lien cliquable vers la page single du modèle

### 7. Charger plus
Bouton en bas de la grille. Incrémente la page et envoie une requête AJAX en mode *append* (les cartes existantes restent). Se masque quand `hasMore === false`.

### 8. Chargement AJAX
Un spinner CSS s'affiche dans la grille pendant chaque requête. En cas d'erreur réseau, un message « Erreur de chargement » apparaît. Les requêtes sont protégées contre les double-clics (`isLoading` flag).

### 9. Responsive
| Breakpoint | Comportement |
|---|---|
| > 1024px | Sidebar sticky + grille 3 colonnes |
| 768px – 1024px | Sidebar sticky + grille 2 colonnes |
| < 768px | Sidebar en haut (non sticky) + grille 1 colonne |

---

## Personnalisation CSS

### Couleur principale
`#1a4759` — Utilisée pour les titres, checkboxes, flèches, bouton. Remplacer toutes les occurrences dans `pool-catalog.css`.

### Police
`AcuminPro` — Utilisée partout. Modifier les déclarations `font-family` si nécessaire.

### Dimensions clés

| Variable | Valeur | Description |
|---|---|---|
| Sidebar | `320px` | Largeur fixe |
| Sticky top | `135px` | Espace haut (ajuster selon le header) |
| Grille | `repeat(3, 1fr)` | 3 colonnes desktop |
| Gap grille | `20px` | Espacement entre cartes |
| Ratio image | `4 / 3` | Proportion des images |
| Per page par défaut | `9` | Modèles visibles initialement |

### Ajouter un nouveau filtre

Pour ajouter un filtre (ex: une nouvelle taxonomie `forme_piscine`) :

1. **PHP** (`inc/pool-catalog.php`) :
   - **Shortcode** : récupérer les termes avec `get_terms()` et ajouter un bloc HTML checkbox avec `data-filter="forme"`
   - **Handler AJAX** : lire `$_POST['formes']`, et ajouter un élément dans `$tax_query` :
     ```php
     if ( ! empty( $formes ) ) {
         $tax_query[] = array(
             'taxonomy' => 'forme_piscine',
             'field'    => 'slug',
             'terms'    => $formes,
         );
     }
     ```

2. **JS** (`assets/js/pool-catalog.js`) :
   - Dans `fetchPools()`, récupérer les filtres actifs et les ajouter au `FormData` :
     ```js
     const formeFilters = getActiveFilters("forme");
     formeFilters.forEach((s) => body.append("formes[]", s));
     ```
