# Détaillants à proximité — `[mova_nearby_dealers]`

## Vue d'ensemble

Affiche un carrousel horizontal de détaillants triés par distance géographique par rapport au détaillant courant.

## Shortcode

```
[mova_nearby_dealers]
[mova_nearby_dealers limit="8"]
[mova_nearby_dealers id="123" limit="4"]
```

| Paramètre | Défaut | Description |
|-----------|--------|-------------|
| `id` | Post courant | ID du détaillant de référence |
| `limit` | 6 | Nombre max de détaillants affichés |

## Fichiers

| Fichier | Rôle |
|---------|------|
| `inc/nearby-dealers.php` | Shortcode PHP — calcul Haversine, tri par distance, rendu HTML |
| `assets/css/nearby-dealers.css` | Styles du carrousel et des cards |
| `assets/js/nearby-dealers.js` | Navigation du carrousel (flèches, scroll snap) |

## Algorithme de proximité

1. Récupère les coordonnées GPS (latitude/longitude) du détaillant courant
2. Requête `WP_Query` pour tous les détaillants publiés (sauf le courant)
3. Calcul de distance Haversine en PHP pour chaque détaillant
4. Tri par distance croissante
5. Limite aux `n` premiers résultats

## Carrousel

- Scroll horizontal natif avec `scroll-snap-type: x mandatory`
- Flèches prev/next avec état `disabled` automatique aux extrémités
- Cards de largeur fixe `280px` (260px sur mobile)
- Gap de `20px` entre les cards
- Scrollbar masquée (CSS)

## Card

Chaque card affiche :
- Icône pin SVG
- **Nom** du détaillant
- **Ville** + province
- **Téléphone** (si disponible)
- **Distance** en km (1 décimale)
- **CTA « Voir la fiche »** — lien vers le single du détaillant

## Responsive

- **Desktop** : Carrousel avec flèches de navigation
- **Mobile** (< 768px) : Flèches masquées, scroll tactile natif (swipe)

## Dépendances

- ACF (Advanced Custom Fields)
- Taxonomie `province`
- Aucune dépendance JS externe (vanilla JS)
