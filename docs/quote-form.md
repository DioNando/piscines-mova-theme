# Documentation — Shortcode `[mova_quote_form]`

## Sommaire
1. [Vue d'ensemble](#vue-densemble)
2. [Fichiers du projet](#fichiers-du-projet)
3. [Prérequis WordPress](#prérequis-wordpress)
4. [Utilisation du shortcode](#utilisation-du-shortcode)
5. [Fonctionnalités](#fonctionnalités)
6. [Structure des données](#structure-des-données)
7. [Sécurité](#sécurité)
8. [Flux de soumission (AJAX)](#flux-de-soumission-ajax)
9. [Personnalisation CSS](#personnalisation-css)
10. [Intégration avec le configurateur](#intégration-avec-le-configurateur)
11. [Ajouter / modifier des champs](#ajouter--modifier-des-champs)

---

## Vue d'ensemble

Le shortcode `[mova_quote_form]` affiche un formulaire de demande de devis pour les piscines Mova. La page se compose de :

- **3 sections de champs** : Coordonnées, Adresse, Projet
- **Section consentements** : accord de partage des coordonnées + inscription à l'infolettre
- **Soumission AJAX** : envoi sans rechargement de page via `admin-ajax.php`

Le formulaire est dynamique : les modèles de piscines (CPT `piscine`), les couleurs (taxonomie `couleur_piscine`) et les provinces (taxonomie `province`) sont chargés automatiquement depuis la base de données WordPress. Il supporte le pré-remplissage depuis le configurateur de piscine via des query params ou des attributs de shortcode.

---

## Fichiers du projet

| Fichier | Rôle |
|---|---|
| `inc/quote-form.php` | Shortcode WordPress (HTML du formulaire + 3 fieldsets) + handler AJAX `mova_submit_quote` (validation, sanitisation, envoi `wp_mail()`) |
| `assets/js/quote-form.js` | Logique JS : validation côté client, soumission AJAX `XMLHttpRequest`, gestion des états (loading, erreur, succès) |
| `assets/css/quote-form.css` | Styles : layout grille, champs de formulaire, checkboxes, bouton submit, messages feedback, responsive |

**Chemins d'assets dans le thème :**
- CSS : `{theme}/assets/css/quote-form.css`
- JS : `{theme}/assets/js/quote-form.js`

---

## Prérequis WordPress

### Custom Post Type
- **`piscine`** — Chaque modèle de piscine est un post de ce type. Utilisé pour alimenter les checkboxes de sélection de modèle.

### Taxonomies utilisées

| Taxonomie | Slug machine | Usage dans le formulaire |
|---|---|---|
| Couleurs | `couleur_piscine` | Menu déroulant « Couleur de la piscine » |
| Provinces | `province` | Menu déroulant « Province » (section adresse) |

### Plugin requis
- **ACF** (Advanced Custom Fields) — Les taxonomies et CPT doivent être configurés. Aucun champ ACF spécifique n'est requis pour le formulaire lui-même, mais les champs du CPT `piscine` (définis dans `group_piscine_details`) doivent exister pour que les modèles soient publiés.

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

### 8. Envoi de courriel
L'email est envoyé via `wp_mail()` à l'adresse admin du site (`get_option('admin_email')`). Le format est texte brut avec toutes les données du formulaire. Le header `Reply-To` est défini sur l'adresse du demandeur.

### 9. Responsive
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

## Sécurité

| Mesure | Détail |
|---|---|
| **Nonce WordPress** | `wp_create_nonce('mova_quote_form_nonce')` généré dans le HTML, vérifié par `wp_verify_nonce()` dans le handler AJAX |
| **Honeypot anti-spam** | Champ caché `website` (hors écran, `tabindex="-1"`). Si rempli → la soumission est silencieusement acceptée (faux positif pour le bot) sans envoi de courriel |
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
                                            └── OK → wp_mail()
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

2. **PHP** (`inc/quote-form.php`) — Dans le handler AJAX, ajouter la sanitisation et l'inclure dans le body du courriel :
   ```php
   $budget = sanitize_text_field( wp_unslash( $_POST['budget'] ?? '' ) );
   // ...
   $body .= "Budget : {$budget}\n";
   ```

3. **JS** (`assets/js/quote-form.js`) — Si le champ est obligatoire, ajouter la validation dans la fonction `validate()` :
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
