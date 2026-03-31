# Documentation — Shortcode `[mova_store_locator]`

## Sommaire
1. [Vue d'ensemble](#vue-densemble)
2. [Fichiers du projet](#fichiers-du-projet)
3. [Prérequis WordPress](#prérequis-wordpress)
4. [Fonctionnalités](#fonctionnalités)
5. [Structure des données (ACF)](#structure-des-données-acf)
6. [Personnalisation CSS](#personnalisation-css)
7. [Migration de Leaflet vers Google Maps](#migration-de-leaflet-vers-google-maps)

---

## Vue d'ensemble

Le shortcode `[mova_store_locator]` affiche une carte interactive avec la liste des détaillants (custom post type `detaillant`). Il se compose de deux colonnes :

- **Gauche (40%)** : filtres sticky (recherche + select province) + liste de cartes groupées par province, synchronisée avec le viewport de la carte
- **Droite (60%)** : carte interactive Leaflet en `position: sticky` avec regroupement automatique des marqueurs (MarkerCluster)

Le tout est responsive : sur mobile (< 992px), la carte passe en haut et la liste en dessous.

---

## Fichiers du projet

| Fichier | Rôle |
|---|---|
| `inc/store-locator.php` | Shortcode WordPress : récupère les données ACF, enqueue Leaflet + MarkerCluster + assets locaux, génère le HTML |
| `assets/js/store-locator.js` | Logique JS : initialisation Leaflet + MarkerCluster, synchronisation carte ↔ liste (moveend), filtrage texte, recherche de proximité (géocodage Nominatim) |
| `assets/css/store-locator.css` | Styles : layout, filtres sticky, cartes, marqueurs, clusters, popups, responsive |

**Chemins d'assets dans le thème :**
- CSS : `{theme}/assets/css/store-locator.css`
- JS : `{theme}/assets/js/store-locator.js`

---

## Prérequis WordPress

1. **Custom Post Type** : `detaillant`
2. **Taxonomie** : `province` (associée au CPT `detaillant`)
3. **Plugin ACF** (Advanced Custom Fields) avec les champs suivants sur le CPT `detaillant` :

| Champ ACF | Nom machine | Type | Obligatoire |
|---|---|---|---|
| Adresse | `adresse` | Texte | Non |
| Ville | `ville` | Texte | Non |
| Code postal | `code_postal` | Texte | Non |
| Téléphone | `telephone` | Texte | Non |
| Email | `email_contact` | Email | Non |
| Site web | `site_web_url` | URL | Non |
| Latitude | `latitude` | Nombre | **Oui** (requis pour affichage) |
| Longitude | `longitude` | Nombre | **Oui** (requis pour affichage) |

> Un détaillant sans coordonnées GPS valides ne sera pas affiché.

---

## Fonctionnalités

### 1. Affichage par province
Par défaut, les détaillants sont groupés par province (taxonomie), triées alphabétiquement. Chaque section a un titre et une grille de cartes.

### 2. Filtre par province (select)
Le menu déroulant filtre la liste et la carte pour n'afficher que les détaillants de la province sélectionnée.

### 3. Recherche textuelle
Le champ de recherche filtre en temps réel sur : nom du magasin, ville et code postal.

### 4. Recherche de proximité (géocodage)
Quand l'utilisateur tape **3 caractères ou plus**, un géocodage est lancé via l'API **Nominatim** (OpenStreetMap, gratuit) après un délai de 500ms (debounce). Si un lieu est trouvé :
- La distance (en km) est calculée pour chaque détaillant via la formule Haversine
- La liste est triée du plus proche au plus éloigné
- L'affichage passe en mode « liste plate » (sans sections province)
- Chaque carte affiche la distance en km
- La carte se recentre sur le lieu recherché (zoom 8)

> **Limitation Nominatim** : 1 requête/seconde max, restreint au Canada (`countrycodes=ca`).

### 5. Cartes de détaillant (sidebar)
Chaque carte affiche :
- Numéro (correspondant au marqueur sur la carte)
- Nom du détaillant
- Adresse et ville
- Téléphone (si renseigné)
- Email (si renseigné, lien mailto)
- Lien « Direction » → Google Maps (itinéraire)
- Lien « Site web » (si renseigné)
- Distance en km (uniquement en mode recherche de proximité)

### 6. Interaction liste ↔ carte
Cliquer sur une carte dans la sidebar :
- Active visuellement la carte (fond inversé)
- Met en surbrillance le marqueur correspondant
- Centre la carte et ouvre la popup
- Sur mobile : scroll automatique vers la carte

### 7. Marqueurs numérotés et regroupement (MarkerCluster)
Les marqueurs sur la carte sont des cercles numérotés (`1, 2, 3...`) correspondant à l'ordre dans la liste. Quand la carte est dézoomée, les marqueurs proches sont automatiquement regroupés en **clusters** affichant le nombre de points contenus. Un clic sur le cluster zoome pour les séparer. Au zoom maximum, les points se déploient en « spiderfy ».

Dépendance : `leaflet.markercluster@1.5.3` (CSS + JS chargés depuis unpkg CDN).

### 8. Synchronisation carte ↔ liste (viewport)
Chaque zoom ou déplacement de la carte met à jour la liste pour n'afficher que les détaillants **visibles dans le viewport actuel**. La numérotation des magasins reste stable (attribuée au rendu initial). Un flag `skipMoveEnd` empêche les boucles infinies lors des mouvements programmatiques (`fitBounds`, `setView`).

### 9. Filtres sticky
La zone de filtres (champ de recherche + select province) reste visible en haut de la sidebar lors du scroll grâce à `position: sticky`.

### 10. Popups
Chaque marqueur a une popup affichant : nom, adresse, téléphone et un bouton « Y aller » (Google Maps).

### 11. Responsive
- **Desktop** : sidebar à gauche (40%) avec filtres sticky, carte sticky à droite (60%)
- **Mobile (< 992px)** : carte en haut (50vh, non sticky), liste en dessous (1 colonne)

---

## Personnalisation CSS

### Couleurs principales
La couleur primaire utilisée partout est `#1a4759`. Pour changer le thème de couleur, remplacer toutes les occurrences de cette valeur dans `store-locator.css` et `store-locator.js` (couleur des marqueurs et clusters).

### Polices
- Titres filtres : `AcuminPro`
- Texte général : `AcuminPro`

### Dimensions clés
| Variable | Valeur | Description |
|---|---|---|
| Sidebar | `40%` | Largeur colonne gauche |
| Carte | `calc(60% - 20px)` | Largeur colonne droite |
| Sticky top | `135px` | Espace haut pour la carte sticky |
| Hauteur carte | `calc(75vh - 40px)` | Hauteur carte desktop |
| Min-height carte | `500px` | Minimum carte |
| Breakpoint mobile | `992px` | Seuil responsive |

---

## Migration de Leaflet vers Google Maps

### Étape 1 — Obtenir une clé API Google Maps

1. Aller sur [Google Cloud Console](https://console.cloud.google.com/)
2. Créer un projet (ou en sélectionner un existant)
3. Activer les APIs suivantes :
   - **Maps JavaScript API** (affichage de la carte)
   - **Geocoding API** (si vous voulez remplacer Nominatim par le géocodeur Google)
4. Créer une clé API dans "Identifiants"
5. **Restreindre la clé** :
   - Restriction HTTP : ajouter le domaine du site
   - Restriction API : limiter aux APIs activées ci-dessus

> **Coût** : Google Maps offre 200$/mois de crédit gratuit (~28 000 chargements de carte/mois). Au-delà, c'est payant. Leaflet + OSM est 100% gratuit.

### Étape 2 — Modifier `store-locator.php`

Remplacer le chargement de Leaflet par le SDK Google Maps :

```php
// AVANT (Leaflet)
wp_enqueue_style( 'leaflet-css', 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.css', array(), '1.9.4' );
wp_enqueue_script( 'leaflet-js', 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js', array(), '1.9.4', true );

// APRÈS (Google Maps)
wp_enqueue_script(
    'google-maps-js',
    'https://maps.googleapis.com/maps/api/js?key=VOTRE_CLE_API&callback=Function.prototype',
    array(),
    null,
    true
);
```

Supprimer la dépendance `'leaflet-js'` du script store-locator :

```php
// AVANT
wp_enqueue_script( 'mova-store-locator-script', ..., array('leaflet-js'), ... );

// APRÈS
wp_enqueue_script( 'mova-store-locator-script', ..., array('google-maps-js'), ... );
```

### Étape 3 — Modifier `store-locator.js`

Voici les correspondances Leaflet → Google Maps pour chaque section du code :

#### Initialisation de la carte

```js
// AVANT (Leaflet)
const map = L.map("mova-sl-map").setView([46.8139, -71.208], 5);
L.tileLayer("https://...", { attribution: "..." }).addTo(map);

// APRÈS (Google Maps)
const map = new google.maps.Map(document.getElementById("mova-sl-map"), {
  center: { lat: 46.8139, lng: -71.208 },
  zoom: 5,
  mapId: "VOTRE_MAP_ID", // optionnel, pour le styling cloud
});
```

#### Vérification d'initialisation

```js
// AVANT
if (!mapElement || typeof L === "undefined") return;

// APRÈS
if (!mapElement || typeof google === "undefined") return;
```

#### Création de marqueurs

```js
// AVANT (Leaflet — divIcon numéroté)
const numberedIcon = L.divIcon({
  className: "custom-div-icon",
  html: `<div class="mova-marker-pin">${num}</div>`,
  iconSize: [30, 30],
  iconAnchor: [15, 15],
});
const marker = L.marker([store.lat, store.lng], { icon: numberedIcon }).addTo(map);

// APRÈS (Google Maps — marqueur avec label)
const marker = new google.maps.Marker({
  position: { lat: store.lat, lng: store.lng },
  map: map,
  label: {
    text: String(num),
    color: "#fff",
    fontWeight: "bold",
    fontSize: "14px",
  },
  icon: {
    path: google.maps.SymbolPath.CIRCLE,
    scale: 15,
    fillColor: "#1a4759",
    fillOpacity: 1,
    strokeColor: "#fff",
    strokeWeight: 2,
  },
});

// Alternative avancée : google.maps.marker.AdvancedMarkerElement (recommandé)
```

#### Popups (InfoWindow)

```js
// AVANT (Leaflet)
marker.bindPopup(popupHTML);
marker.openPopup();

// APRÈS (Google Maps)
const infoWindow = new google.maps.InfoWindow({ content: popupHTML });
marker.addListener("click", () => {
  infoWindow.open(map, marker);
});
// Pour ouvrir programmatiquement :
infoWindow.open(map, marker);
```

#### Bounds (ajuster le zoom)

```js
// AVANT (Leaflet)
let bounds = L.latLngBounds();
bounds.extend([store.lat, store.lng]);
map.fitBounds(bounds, { padding: [50, 50], maxZoom: 14 });

// APRÈS (Google Maps)
const bounds = new google.maps.LatLngBounds();
bounds.extend({ lat: store.lat, lng: store.lng });
map.fitBounds(bounds, 50); // padding en pixels
```

#### Recentrer la carte

```js
// AVANT (Leaflet)
map.setView([store.lat, store.lng], 13);

// APRÈS (Google Maps)
map.setCenter({ lat: store.lat, lng: store.lng });
map.setZoom(13);
```

#### Supprimer les marqueurs

```js
// AVANT (Leaflet)
currentMarkers.forEach((m) => map.removeLayer(m));

// APRÈS (Google Maps)
currentMarkers.forEach((m) => m.setMap(null));
```

### Étape 4 — Modifier `store-locator.css`

- **Supprimer** les règles Leaflet spécifiques :
  - `.leaflet-popup-content-wrapper`
  - `.leaflet-popup-close-button`
  - `.custom-div-icon`
  - `.mova-marker-pin` et `.mova-marker-pin:hover`

- **Ajouter** des styles pour les InfoWindows Google Maps :
```css
/* Google Maps InfoWindow */
.gm-style-iw {
  font-family: "AcuminPro", sans-serif;
  color: #1a4759;
}
```

> Le reste du CSS (layout, sidebar, cartes, responsive) reste inchangé.

### Étape 5 — Géocodage (optionnel)

Si vous voulez aussi remplacer Nominatim par le Geocoder Google :

```js
// AVANT (Nominatim)
function geocode(query) {
  return fetch(`https://nominatim.openstreetmap.org/search?...&q=${query}`)
    .then(r => r.json())
    .then(data => data[0] ? { lat: ..., lng: ... } : null);
}

// APRÈS (Google Geocoder)
const geocoder = new google.maps.Geocoder();

function geocode(query) {
  return new Promise((resolve) => {
    geocoder.geocode(
      { address: query, componentRestrictions: { country: "CA" } },
      (results, status) => {
        if (status === "OK" && results[0]) {
          const loc = results[0].geometry.location;
          resolve({ lat: loc.lat(), lng: loc.lng() });
        } else {
          resolve(null);
        }
      }
    );
  });
}
```

> **Note** : Le Geocoding API Google est payant (5$ / 1000 requêtes après le crédit gratuit). Nominatim est gratuit mais limité à 1 req/s.

### Résumé des coûts

| Solution | Carte | Géocodage | Coût |
|---|---|---|---|
| **Actuelle** (Leaflet + Nominatim) | Gratuit | Gratuit (1 req/s max) | **0$** |
| **Google Maps + Nominatim** | 200$/mois crédit gratuit | Gratuit | ~0$ si < 28k vues/mois |
| **Google Maps + Google Geocoder** | 200$/mois crédit gratuit | 5$/1000 req | Variable |
