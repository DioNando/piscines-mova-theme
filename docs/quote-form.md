# Documentation — Shortcode `[mova_quote_form]`

## Sommaire
1. [Vue d'ensemble](#vue-densemble)
2. [Fichiers du projet](#fichiers-du-projet)
3. [Prérequis WordPress](#prérequis-wordpress)
4. [Utilisation du shortcode](#utilisation-du-shortcode)
5. [Fonctionnalités](#fonctionnalités)
6. [Structure des données](#structure-des-données)
7. [Stockage en base de données (CPT)](#stockage-en-base-de-données-cpt)
8. [Administration (Back-Office)](#administration-back-office)
9. [Sécurité](#sécurité)
10. [Flux de soumission (AJAX)](#flux-de-soumission-ajax)
11. [Personnalisation CSS](#personnalisation-css)
12. [Intégration avec le configurateur](#intégration-avec-le-configurateur)
13. [Ajouter / modifier des champs](#ajouter--modifier-des-champs)

---

## Vue d'ensemble

Le shortcode `[mova_quote_form]` affiche un formulaire de demande de devis pour les piscines Mova. La page se compose de :

- **3 sections de champs** : Coordonnées, Adresse, Projet
- **Section consentements** : accord de partage des coordonnées + inscription à l'infolettre
- **Soumission AJAX** : envoi sans rechargement de page via `admin-ajax.php`
- **Stockage en base de données** : chaque soumission est enregistrée comme un CPT `demande_devis` consultable dans le back-office WordPress

Le formulaire est dynamique : les modèles de piscines (CPT `piscine`), les couleurs (taxonomie `couleur_piscine`) et les provinces (taxonomie `province`) sont chargés automatiquement depuis la base de données WordPress. Il supporte le pré-remplissage depuis le configurateur de piscine via des query params ou des attributs de shortcode.

Quand le formulaire est atteint depuis le configurateur AquaCove, les sélections de tapis par zone et d'options sont également affichées dans un bloc résumé « Sélections AquaCove » et incluses dans le courriel de devis.

---

## Fichiers du projet

| Fichier | Rôle |
|---|---|
| `inc/quote-form.php` | Shortcode WordPress (HTML du formulaire + 3 fieldsets) + handler AJAX `mova_submit_quote` (validation, sanitisation, stockage CPT `demande_devis`, envoi `wp_mail()`) + colonnes admin + filtre par statut |
| `assets/js/quote-form.js` | Logique JS : validation côté client, soumission AJAX `XMLHttpRequest`, gestion des états (loading, erreur, succès) |
| `assets/css/quote-form.css` | Styles : layout grille, champs de formulaire, checkboxes, bouton submit, messages feedback, responsive |
| `acf-json/post_type_69d4b8f1a7e20.json` | Enregistrement ACF du CPT `demande_devis` |
| `acf-json/group_demande_devis_details.json` | Groupe de champs ACF « Détails de la demande » (5 onglets) |

**Chemins d'assets dans le thème :**
- CSS : `{theme}/assets/css/quote-form.css`
- JS : `{theme}/assets/js/quote-form.js`

---

## Prérequis WordPress

### Custom Post Types
- **`piscine`** — Chaque modèle de piscine est un post de ce type. Utilisé pour alimenter les checkboxes de sélection de modèle.
- **`demande_devis`** — Chaque soumission du formulaire est enregistrée comme un post de ce type. Visible uniquement dans l'administration (`public: false`, `show_ui: true`). Enregistré via ACF JSON (`post_type_69d4b8f1a7e20.json`).

### Taxonomies utilisées

| Taxonomie | Slug machine | Usage dans le formulaire |
|---|---|---|
| Couleurs | `couleur_piscine` | Menu déroulant « Couleur de la piscine » |
| Provinces | `province` | Menu déroulant « Province » (section adresse) |

### Plugin requis
- **ACF** (Advanced Custom Fields) — Les taxonomies et CPT doivent être configurés. Le groupe de champs `group_demande_devis_details` définit les champs de stockage des soumissions. La fonction `update_field()` d'ACF est utilisée dans le handler AJAX pour enregistrer les données. Les champs du CPT `piscine` (définis dans `group_piscine_details`) doivent exister pour que les modèles soient publiés.

### Enregistrement du module
Le fichier est inclus dans `functions.php` :
```php
require_once get_stylesheet_directory() . '/inc/quote-form.php';
```

---

## Utilisation du shortcode

### Basique
```
[mova_quote_form]
```
Affiche le formulaire complet avec tous les modèles, couleurs et provinces disponibles.

### Avec pré-sélection (attributs)
```
[mova_quote_form model="12x24" couleur="gris-perle"]
```

### Avec pré-sélection (URL query params)
```
/demande-de-devis/?model=12x24&couleur=gris-perle
```

| Attribut / Param | Type | Défaut | Description |
|---|---|---|---|
| `model` | Slug | `''` | Slug du modèle de piscine à pré-cocher dans les checkboxes |
| `couleur` | Slug | `''` | Slug de la couleur à pré-sélectionner dans le menu déroulant |

> Les query params (`$_GET`) sont lus en fallback si les attributs du shortcode sont vides. Les attributs du shortcode ont priorité.

---

## Fonctionnalités

### 1. Section Coordonnées
| Champ | Type HTML | Name | Obligatoire |
|---|---|---|---|
| Prénom | `text` | `prenom` | **Oui** |
| Nom | `text` | `nom` | **Oui** |
| Courriel | `email` | `courriel` | **Oui** |
| Téléphone | `tel` | `telephone` | **Oui** |
| Meilleur moment | `select` | `moment` | Non |
| Meilleure façon de rejoindre | `select` | `moyen_contact` | Non |

**Options « Moment »** : Avant-midi, Après-midi, En soirée

**Options « Moyen de contact »** : Courriel, Téléphone, Texto

### 2. Section Adresse
| Champ | Type HTML | Name | Obligatoire |
|---|---|---|---|
| Adresse | `text` | `adresse` | Non |
| Ville | `text` | `ville` | Non |
| Province | `select` (dynamique) | `province` | Non |
| Code postal | `text` | `code_postal` | Non |

La liste des provinces est tirée de la taxonomie `province` (même taxonomie que le CPT `detaillant`).

### 3. Section Projet
| Champ | Type HTML | Name | Obligatoire |
|---|---|---|---|
| Modèles de piscines | `checkbox[]` (dynamique) | `modeles[]` | Non |
| Couleur de la piscine | `select` (dynamique) | `couleur` | Non |
| Type d'installation | `select` (statique) | `type_installation` | Non |
| Date du projet | `date` | `date_projet` | Non |
| Source (comment entendu parler) | `select` (statique) | `source` | Non |
| Commentaires | `textarea` | `commentaires` | Non |

**Options « Type d'installation »** : Creusée, Semi-creusée, Hors terre, Je ne sais pas

**Options « Source »** : Recherche Internet, Réseaux sociaux, Recommandation, Salon / Exposition, Publicité, Autre

### 4. Section Consentements
| Champ | Type HTML | Name | Obligatoire |
|---|---|---|---|
| Accord coordonnées | `checkbox` | `accord_coordonnees` | **Oui** |
| Infolettre | `checkbox` | `infolettre` | Non |

### 5. Validation côté client (JS)
La validation JavaScript vérifie avant l'envoi :
- Prénom non vide
- Nom non vide
- Courriel non vide et format valide (regex basique)
- Téléphone non vide
- Accord coordonnées coché

Les champs en erreur reçoivent la classe `.mova-qf-error` (bordure rouge). L'erreur est retirée dès que l'utilisateur saisit dans le champ.

### 6. Validation côté serveur (PHP)
Le handler AJAX revalide indépendamment les mêmes champs obligatoires. En cas d'erreur, un `wp_send_json_error()` est retourné avec les messages d'erreur.

### 7. Soumission AJAX
Le formulaire est envoyé via `XMLHttpRequest` en `POST` vers `admin-ajax.php`. Pendant l'envoi :
- Le bouton est désactivé
- Un spinner CSS s'affiche (classe `.mova-qf-loading`)
- En cas de succès : message vert + `form.reset()`
- En cas d'erreur : message rouge avec détails

### 8. Stockage en base de données
Avant l'envoi du courriel, la soumission est enregistrée en base de données via `wp_insert_post()` (CPT `demande_devis`) avec tous les champs remplis via `update_field()` d'ACF. Le statut est initialisé à `"nouveau"`. Si l'insertion échoue, le courriel est quand même envoyé (pas de blocage).

### 9. Envoi de courriel
L'email est envoyé via `wp_mail()` à l'adresse admin du site (`get_option('admin_email')`). Le format est texte brut avec toutes les données du formulaire. Le header `Reply-To` est défini sur l'adresse du demandeur.

### 10. Responsive
| Breakpoint | Comportement |
|---|---|
| > 767px | Grille 2 colonnes pour les champs, 3 colonnes pour l'adresse |
| ≤ 767px | 1 colonne, bouton submit plein largeur |
| ≤ 480px | Padding réduit, `font-size: 16px` sur les inputs (prévention du zoom iOS) |

---

## Structure des données

### Données envoyées en POST (FormData)

```
action              = mova_submit_quote
nonce               = {wp_nonce}
prenom              = Jean
nom                 = Tremblay
courriel            = jean@example.com
telephone           = 514-555-0123
moment              = Après-midi
moyen_contact       = Téléphone
adresse             = 123 rue Principale
ville               = Montréal
province            = Québec
code_postal         = H2X 1A1
modeles[]           = 12x24
modeles[]           = 12x34
couleur             = gris-perle
type_installation   = Creusée
date_projet         = 2026-06-15
source              = Recherche Internet
commentaires        = Terrain en pente, besoin de conseils.
accord_coordonnees  = 1
infolettre          = 1
```

### Réponse JSON (succès)
```json
{
  "success": true,
  "data": {
    "message": "Merci! Votre demande de devis a été envoyée avec succès. Un représentant Mova vous contactera sous peu."
  }
}
```

### Réponse JSON (erreur)
```json
{
  "success": false,
  "data": {
    "message": "Le prénom est requis.<br>Veuillez entrer une adresse courriel valide."
  }
}
```

### Format du courriel envoyé

```
Sujet : Demande de devis — Jean Tremblay
Reply-To : Jean Tremblay <jean@example.com>

Nouvelle demande de devis
========================

Prénom : Jean
Nom : Tremblay
Courriel : jean@example.com
Téléphone : 514-555-0123
Meilleur moment : Après-midi
Moyen de contact préféré : Téléphone

Adresse : 123 rue Principale
Ville : Montréal
Province : Québec
Code postal : H2X 1A1

Modèle(s) : 12x24, 12x34
Couleur : gris-perle
Type d'installation : Creusée
Date du projet : 2026-06-15
Source : Recherche Internet

Commentaires :
Terrain en pente, besoin de conseils.

---
Accord coordonnées : Oui
Infolettre : Oui
```

---

## Stockage en base de données (CPT)

Chaque soumission valide du formulaire crée un post de type `demande_devis` via `wp_insert_post()`. Les données sont stockées dans des champs ACF via `update_field()`.

### CPT `demande_devis`

| Propriété | Valeur |
|---|---|
| **Slug** | `demande_devis` |
| **Fichier ACF** | `acf-json/post_type_69d4b8f1a7e20.json` |
| **Public** | `false` (pas de page front-end) |
| **Visible dans l'admin** | `true` |
| **Icône menu** | `dashicons-email-alt` |
| **Position menu** | 25 (sous Commentaires) |
| **Supports** | `title`, `custom-fields` |

### Groupe de champs ACF — `group_demande_devis_details`

**Fichier :** `acf-json/group_demande_devis_details.json`

Organisé en **5 onglets** :

#### Onglet « Coordonnées »
| Champ ACF | Clé | Type |
|---|---|---|
| Prénom | `field_devis_prenom` | text |
| Nom | `field_devis_nom` | text |
| Courriel | `field_devis_courriel` | email |
| Téléphone | `field_devis_telephone` | text |
| Meilleur moment | `field_devis_moment` | text |
| Moyen de contact | `field_devis_moyen_contact` | text |

#### Onglet « Adresse »
| Champ ACF | Clé | Type |
|---|---|---|
| Adresse | `field_devis_adresse` | text |
| Ville | `field_devis_ville` | text |
| Province | `field_devis_province` | text |
| Code postal | `field_devis_code_postal` | text |

#### Onglet « Projet »
| Champ ACF | Clé | Type |
|---|---|---|
| Modèle(s) | `field_devis_modeles` | text |
| Couleur | `field_devis_couleur` | text |
| Type d'installation | `field_devis_type_installation` | text |
| Date du projet | `field_devis_date_projet` | text |
| Source | `field_devis_source` | text |
| Commentaires | `field_devis_commentaires` | textarea |

#### Onglet « AquaCove »
| Champ ACF | Clé | Type |
|---|---|---|
| Tapis — Marches | `field_devis_tapis_marches` | text |
| Tapis — Bancs | `field_devis_tapis_bancs` | text |
| Tapis — Terrasse | `field_devis_tapis_terrasse` | text |
| Options AquaCove | `field_devis_options_aquacove` | text |

#### Onglet « Suivi »
| Champ ACF | Clé | Type | Détail |
|---|---|---|---|
| Statut | `field_devis_statut` | select | Nouveau / En cours / Traité / Archivé (défaut : `nouveau`) |
| Accord coordonnées | `field_devis_accord_coordonnees` | true_false | Toggle Oui/Non |
| Infolettre | `field_devis_infolettre` | true_false | Toggle Oui/Non |
| Notes internes | `field_devis_notes_internes` | textarea | Visible uniquement par l'équipe |

### Titre du post

Le titre est automatiquement généré : `"Devis — {prénom} {nom}"`.

### Modèles de piscines

Le tableau `modeles[]` est joint en chaîne séparée par virgules avant stockage (ex: `"12x24, 12x34"`).

### Résilience

Si `wp_insert_post()` échoue (retourne `WP_Error`), le courriel est quand même envoyé. Le stockage en base ne bloque jamais l'envoi du courriel.

---

## Administration (Back-Office)

### Menu WordPress

Le CPT `demande_devis` apparaît dans le menu admin avec l'icône `dashicons-email-alt` (enveloppe) à la position 25.

### Colonnes de la liste

La liste des demandes affiche les colonnes personnalisées suivantes :

| Colonne | Contenu |
|---|---|
| **Titre** | `Devis — Prénom Nom` (lien vers l'édition) |
| **Courriel** | Adresse courriel du demandeur |
| **Téléphone** | Numéro de téléphone |
| **Modèle(s)** | Modèles sélectionnés (séparés par virgule) |
| **Statut** | Pastille colorée selon le statut |
| **Date** | Date de soumission |

### Pastilles de statut

| Valeur | Label | Couleur |
|---|---|---|
| `nouveau` | Nouveau | Bleu (`#2271b1`) |
| `en_cours` | En cours | Jaune (`#dba617`) |
| `traite` | Traité | Vert (`#00a32a`) |
| `archive` | Archivé | Gris (`#787c82`) |

### Filtre par statut

Un menu déroulant « Tous les statuts / Nouveau / En cours / Traité / Archivé » est disponible au-dessus de la liste. Le filtre utilise une `meta_query` sur le champ ACF `statut`.

### Tri

La colonne **Statut** est triable (cliquer sur l'en-tête pour trier).

### Édition d'une demande

En cliquant sur une demande, les 5 onglets ACF sont affichés. Le champ **Statut** et **Notes internes** (onglet « Suivi ») permettent de suivre le traitement de la demande. Tous les autres champs sont en lecture/écriture mais reflètent les données soumises.

### Fonctions PHP (hooks admin)

| Fonction | Hook | Rôle |
|---|---|---|
| `mova_devis_admin_columns()` | `manage_demande_devis_posts_columns` | Définit les colonnes personnalisées |
| `mova_devis_admin_column_content()` | `manage_demande_devis_posts_custom_column` | Affiche le contenu des colonnes |
| `mova_devis_sortable_columns()` | `manage_edit-demande_devis_sortable_columns` | Rend la colonne Statut triable |
| `mova_devis_admin_filter_dropdown()` | `restrict_manage_posts` | Affiche le dropdown de filtre par statut |
| `mova_devis_admin_filter_query()` | `pre_get_posts` | Applique le filtre meta_query + tri par statut |

---

## Sécurité

| Mesure | Détail |
|---|---|
| **Nonce WordPress** | `wp_create_nonce('mova_quote_form_nonce')` généré dans le HTML, vérifié par `wp_verify_nonce()` dans le handler AJAX |
| **Honeypot anti-spam** | Champ caché `website` (hors écran, `tabindex="-1"`). Si rempli → la soumission est silencieusement acceptée (faux positif pour le bot) sans envoi de courriel ni création de post en base |
| **Sanitisation serveur** | Chaque champ est passé par `sanitize_text_field()`, `sanitize_email()` ou `sanitize_textarea_field()` avec `wp_unslash()` |
| **Validation serveur** | Les champs obligatoires sont revalidés côté serveur indépendamment du JS |
| **Échappement HTML** | Toutes les valeurs affichées dans le formulaire utilisent `esc_attr()` / `esc_html()` |
| **AJAX sécurisé** | Enregistré sur `wp_ajax_` et `wp_ajax_nopriv_` pour supporter les utilisateurs connectés et non connectés |

---

## Flux de soumission (AJAX)

```
Utilisateur clique « Envoyer »
        │
        ▼
  [JS] Validation client
        │
        ├── Erreur → Affiche message rouge + bordures rouges
        │
        └── OK → FormData envoyé en POST
                    │
                    ▼
            admin-ajax.php
                    │
                    ▼
        [PHP] wp_verify_nonce()
                    │
                    ├── Échec → JSON error "Erreur de sécurité"
                    │
                    └── OK → Vérification honeypot
                                │
                                ├── Bot détecté → JSON success (faux positif)
                                │
                                └── OK → Sanitisation des champs
                                            │
                                            ▼
                                    Validation serveur
                                            │
                                            ├── Erreur → JSON error + messages
                                            │
                                            └── OK → wp_insert_post(demande_devis)
                                                        │
                                                        ▼
                                                update_field() × N champs ACF
                                                        │
                                                        ▼
                                                    wp_mail()
                                                        │
                                                        ├── Échec → JSON error
                                                        │
                                                        └── OK → JSON success
                                                                    │
                                                                    ▼
                                                        [JS] Message vert + form.reset()
```

---

## Personnalisation CSS

### Couleur principale
`#1a4759` — Utilisée pour les labels, legends, bordures focus, bouton submit, accent des checkboxes. Remplacer toutes les occurrences dans `quote-form.css`.

### Couleur d'erreur
`#c0392b` — Utilisée pour les astérisques obligatoires, bordures d'erreur, messages d'erreur.

### Couleur de succès
`#27ae60` — Utilisée pour les messages de succès.

### Police
`AcuminPro` — Utilisée partout. Modifier les déclarations `font-family` si nécessaire.

### Dimensions clés

| Variable | Valeur | Description |
|---|---|---|
| Largeur max formulaire | `820px` | `max-width` du conteneur `.mova-qf` |
| Grille par défaut | `1fr 1fr` | 2 colonnes par défaut |
| Grille adresse | `1fr 1fr 1fr` | 3 colonnes pour Ville / Province / CP |
| Gap horizontal | `24px` | Entre les colonnes |
| Gap vertical | `16px` | Entre les lignes |
| Padding input | `12px 14px` | Espace interne des champs |
| Breakpoint mobile | `767px` | Passage en 1 colonne |
| Breakpoint petit écran | `480px` | Font-size 16px (anti-zoom iOS) |

### Classes CSS de référence

| Classe | Élément |
|---|---|
| `.mova-qf` | Conteneur principal |
| `.mova-qf-form` | Balise `<form>` |
| `.mova-qf-fieldset` | Chaque section (coordonnées, adresse, projet) |
| `.mova-qf-legend` | Titre de section |
| `.mova-qf-row` | Ligne de champs (grille 2 colonnes) |
| `.mova-qf-row--3` | Ligne de champs (grille 3 colonnes) |
| `.mova-qf-field` | Conteneur d'un champ (label + input) |
| `.mova-qf-field--full` | Champ pleine largeur |
| `.mova-qf-checkboxes` | Grille des checkboxes de modèles |
| `.mova-qf-checkbox-label` | Checkbox avec label (modèles + consentements) |
| `.mova-qf-error` | État d'erreur sur un champ |
| `.mova-qf-submit` | Bouton d'envoi |
| `.mova-qf-loading` | État de chargement du bouton (spinner) |
| `.mova-qf-message` | Conteneur du message de feedback |
| `.mova-qf-message--success` | Message de succès (vert) |
| `.mova-qf-message--error` | Message d'erreur (rouge) |

---

## Intégration avec la prévisualisation couleurs

Le shortcode `[mova_pool_color_preview]` (dans `inc/pool-color-preview.php`) exporte une variable `devisUrl` pointant vers `/demande-de-devis/` :

```php
wp_localize_script( 'mova-pool-color-preview-script', 'movaColorPreview', array(
    'devisUrl' => home_url( '/demande-de-devis/' ),
    // ...
) );
```

Pour créer un lien depuis la prévisualisation vers le formulaire de devis avec pré-remplissage :

```js
// Dans pool-color-preview.js
var url = movaColorPreview.devisUrl
        + '?model=' + encodeURIComponent(movaColorPreview.modelSlug)
        + '&couleur=' + encodeURIComponent(selectedColor);
window.location.href = url;
```

Le formulaire lira automatiquement les paramètres `model` et `couleur` de l'URL pour pré-cocher le modèle et pré-sélectionner la couleur.

---

## Intégration avec le configurateur AquaCove

Le configurateur `[mova_pool_configurator]` envoie des paramètres supplémentaires via l'URL :

```
?model=12x34&couleur=ciel-de-minuit&options=jets,badujet&tapis_marches=abysse&tapis_bancs=sable&tapis_terrasse=mova
```

| Param | Description |
|---|---|
| `model` | Slug du modèle de piscine (CPT `piscine`) |
| `couleur` | Slug du terme `couleur_piscine` |
| `options` | Slugs d'options séparés par virgule (ex: `jets,badujet`) |
| `tapis_marches` | Slug du `modele_tapis` sélectionné pour la zone marches |
| `tapis_bancs` | Slug du `modele_tapis` sélectionné pour la zone bancs |
| `tapis_terrasse` | Slug du `modele_tapis` sélectionné pour la zone terrasse |

### Bloc « Sélections AquaCove »

Quand des tapis ou options sont présents dans l'URL, un bloc résumé `.mova-qf-aquacove-summary` s'affiche dans la section « Votre projet », après la couleur/installation, avec :

- Le nom du tapis par zone (résolu depuis la taxonomie `modele_tapis`)
- Les options sélectionnées (texte brut)
- Des champs `<input type="hidden">` pour inclure ces valeurs dans la soumission AJAX

### Dans le courriel

Le handler AJAX ajoute au corps du courriel :

```
Tapis AquaCove :
  Marches : abysse
  Bancs : sable
  Terrasse : mova
Options AquaCove : jets, badujet
```

### Classes CSS du bloc AquaCove

| Classe | Description |
|---|---|
| `.mova-qf-aquacove-summary` | Conteneur principal (background gris clair, bordure, border-radius) |
| `.mova-qf-aquacove-title` | Titre « Sélections AquaCove » (uppercase, 600 weight) |
| `.mova-qf-aquacove-items` | Conteneur flex pour les items |
| `.mova-qf-aquacove-item` | Un item (zone + valeur) |
| `.mova-qf-aquacove-zone` | Label de la zone (bold) |
| `.mova-qf-aquacove-value` | Valeur sélectionnée |

---

## Ajouter / modifier des champs

### Ajouter un champ simple

1. **PHP** (`inc/quote-form.php`) — Dans le shortcode, ajouter le HTML du champ dans le fieldset approprié :
   ```html
   <div class="mova-qf-field">
       <label for="mova_qf_budget">Budget estimé</label>
       <select id="mova_qf_budget" name="budget">
           <option value="">— Sélectionner —</option>
           <option value="20000-30000">20 000$ – 30 000$</option>
           <option value="30000-50000">30 000$ – 50 000$</option>
           <option value="50000+">50 000$+</option>
       </select>
   </div>
   ```

2. **ACF** (`acf-json/group_demande_devis_details.json`) — Ajouter un champ dans le groupe, dans l'onglet approprié :
   ```json
   {
     "key": "field_devis_budget",
     "label": "Budget estimé",
     "name": "budget",
     "type": "text",
     "wrapper": { "width": "50", "class": "", "id": "" }
   }
   ```
   > Alternative : ajouter le champ directement dans l'interface ACF de WordPress, puis synchroniser le JSON.

3. **PHP** (`inc/quote-form.php`) — Dans le handler AJAX, ajouter la sanitisation, le stockage ACF, et l'inclure dans le body du courriel :
   ```php
   $budget = sanitize_text_field( wp_unslash( $_POST['budget'] ?? '' ) );
   // ...
   // Stockage ACF (dans le bloc wp_insert_post)
   update_field( 'field_devis_budget', $budget, $post_id );
   // ...
   $body .= "Budget : {$budget}\n";
   ```

4. **JS** (`assets/js/quote-form.js`) — Si le champ est obligatoire, ajouter la validation dans la fonction `validate()` :
   ```js
   var budget = form.querySelector('[name="budget"]');
   if (!budget.value) {
       errors.push('Le budget est requis.');
       budget.classList.add('mova-qf-error');
   }
   ```

### Rendre un champ obligatoire

1. Ajouter `required` à la balise HTML de l'input
2. Ajouter `<span class="mova-qf-req">*</span>` après le texte du label
3. Ajouter la validation JS dans `validate()`
4. Ajouter la validation PHP dans le handler AJAX

### Ajouter une colonne admin

Dans `inc/quote-form.php`, ajouter la clé dans `mova_devis_admin_columns()` et le `case` correspondant dans `mova_devis_admin_column_content()`.

### Modifier le destinataire du courriel

Dans le handler AJAX (`inc/quote-form.php`), modifier la variable `$to` :

```php
// Un seul destinataire
$to = 'ventes@piscinesmova.com';

// Plusieurs destinataires
$to = array( 'ventes@piscinesmova.com', 'admin@piscinesmova.com' );
```

### Ajouter un courriel de confirmation au demandeur

Après le `wp_mail()` principal, ajouter un second envoi :

```php
if ( $sent ) {
    // Confirmation au demandeur
    $confirm_body  = "Bonjour {$prenom},\n\n";
    $confirm_body .= "Nous avons bien reçu votre demande de devis.\n";
    $confirm_body .= "Un représentant Mova vous contactera sous peu.\n\n";
    $confirm_body .= "Merci de votre intérêt!\n";
    $confirm_body .= "— L'équipe Piscines Mova";

    wp_mail(
        $courriel,
        'Confirmation — Votre demande de devis Mova',
        $confirm_body,
        array( 'Content-Type: text/plain; charset=UTF-8' )
    );
}
```
