# Fiche Détaillant — `[mova_dealer_detail]`

## Vue d'ensemble

Affiche la fiche complète d'un détaillant avec ses coordonnées et une carte Leaflet interactive.

## Shortcode

```
[mova_dealer_detail]
[mova_dealer_detail id="123"]
```

| Paramètre | Défaut | Description |
|-----------|--------|-------------|
| `id` | Post courant | ID du détaillant à afficher |

## Fichiers

| Fichier | Rôle |
|---------|------|
| `inc/dealer-detail.php` | Shortcode PHP — requête ACF, rendu HTML |
| `assets/css/dealer-detail.css` | Styles de la fiche |
| `assets/js/dealer-detail.js` | Initialisation de la carte Leaflet |

## Données ACF utilisées

- `adresse` — Adresse postale
- `ville` — Ville
- `code_postal` — Code postal
- `telephone` — Numéro de téléphone
- `email_contact` — Email de contact
- `site_web_url` — URL du site web
- `latitude` / `longitude` — Coordonnées GPS
- Taxonomie `province` — Province du détaillant

## Layout

- **Desktop** : Deux colonnes — infos à gauche, carte à droite (50%)
- **Mobile** (< 768px) : Carte en haut, infos en dessous (`column-reverse`)

## Carte

- Leaflet 1.9.4 avec tuiles CartoDB Voyager
- Marqueur pin teardrop (`#1a4759`) avec icône SVG
- Zoom initial : 14
- Scroll wheel zoom désactivé
- Popup avec le nom du détaillant

## Sections affichées

1. **Nom** — Titre H2
2. **Adresse** — Icône pin + adresse, ville, code postal, province
3. **Téléphone** — Lien `tel:`
4. **Email** — Lien `mailto:`
5. **Site web** — Lien externe (domaine affiché sans protocole)
6. **Bouton « Y aller »** — Lien Google Maps itinéraire

## Dépendances

- Leaflet CSS/JS (CDN unpkg)
- ACF (Advanced Custom Fields)
- Taxonomie `province`
