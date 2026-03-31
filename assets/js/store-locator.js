document.addEventListener("DOMContentLoaded", function () {
  // Vérifier si les données PHP sont bien passées
  if (typeof movaStoreData === "undefined" || !movaStoreData.stores) return;

  const allStores = movaStoreData.stores;
  const mapElement = document.getElementById("mova-sl-map");
  const listElement = document.getElementById("mova-sl-list");
  const searchInput = document.getElementById("mova-sl-search");
  const provinceSelect = document.getElementById("mova-sl-province");

  if (!mapElement || typeof L === "undefined") return;

  // --- Calcul de distance Haversine (en km) ---
  function haversineKm(lat1, lng1, lat2, lng2) {
    const R = 6371;
    const dLat = ((lat2 - lat1) * Math.PI) / 180;
    const dLng = ((lng2 - lng1) * Math.PI) / 180;
    const a =
      Math.sin(dLat / 2) ** 2 +
      Math.cos((lat1 * Math.PI) / 180) *
        Math.cos((lat2 * Math.PI) / 180) *
        Math.sin(dLng / 2) ** 2;
    return R * 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
  }

  // --- Géocodage via Nominatim (OpenStreetMap, gratuit) ---
  function geocode(query) {
    const url = `https://nominatim.openstreetmap.org/search?format=json&countrycodes=ca&limit=1&q=${encodeURIComponent(query)}`;
    return fetch(url)
      .then((r) => r.json())
      .then((data) => {
        if (data.length > 0) {
          return { lat: parseFloat(data[0].lat), lng: parseFloat(data[0].lon) };
        }
        return null;
      })
      .catch(() => null);
  }

  // Initialisation de la carte (OpenStreetMap)
  const map = L.map("mova-sl-map").setView([46.8139, -71.208], 5);
  L.tileLayer(
    "https://{s}.basemaps.cartocdn.com/rastertiles/voyager/{z}/{x}/{y}{r}.png",
    {
      attribution: "© OpenStreetMap contributors",
    },
  ).addTo(map);

  // Groupe de clusters pour regrouper les marqueurs proches
  const clusterGroup = L.markerClusterGroup({
    maxClusterRadius: 45,
    spiderfyOnMaxZoom: true,
    showCoverageOnHover: false,
    zoomToBoundsOnClick: true,
    iconCreateFunction: function (cluster) {
      const count = cluster.getChildCount();
      return L.divIcon({
        html: '<div class="mova-cluster-icon">' + count + '</div>',
        className: 'mova-cluster',
        iconSize: [40, 40],
      });
    },
  });
  map.addLayer(clusterGroup);

  let currentMarkers = [];

  // Fonction d'affichage dynamique (Liste + Carte)
  // mode: "province" (groupé) ou "proximity" (liste plate triée par distance)
  function renderStores(storesToRender, mode) {
    // Nettoyage de la vue précédente
    listElement.innerHTML = "";
    clusterGroup.clearLayers();
    currentMarkers = [];

    if (storesToRender.length === 0) {
      listElement.innerHTML =
        '<div style="padding:20px; text-align:center; color:#666;">Aucun détaillant trouvé.</div>';
      return;
    }

    let bounds = L.latLngBounds();

    // Fonction interne pour créer une carte de magasin
    function createStoreCard(store, num, container) {
      const numberedIcon = L.divIcon({
        className: "custom-div-icon",
        html: `<div class="mova-marker-pin">${num}</div>`,
        iconSize: [30, 30],
        iconAnchor: [15, 15],
        popupAnchor: [0, -15],
      });

      const popupHTML = `
                <div class="mova-map-popup">
                    <h5>${store.nom}</h5>
                    <p>${store.adresse}<br>${store.ville}, ${store.cp}</p>
                    ${store.tel ? `<p style="font-weight:bold; margin-top:-5px;">${store.tel}</p>` : ""}
                    <a href="https://www.google.com/maps/dir/?api=1&destination=${store.lat},${store.lng}" target="_blank" class="btn-itineraire">Y aller</a>
                </div>
            `;

      const marker = L.marker([store.lat, store.lng], { icon: numberedIcon })
        .bindPopup(popupHTML);
      clusterGroup.addLayer(marker);

      bounds.extend([store.lat, store.lng]);
      currentMarkers.push(marker);

      const listItem = document.createElement("div");
      listItem.className = "mova-sl-item";
      listItem.innerHTML = `
                <div class="mova-sl-item-header">
                    <span class="mova-sl-number">${num}</span>
                    <h5>${store.nom}</h5>
                </div>
                <p> ${store.adresse}, ${store.ville}</p>
                ${store.tel ? `<p class="store-tel"> ${store.tel}</p>` : ""}
                ${store.email ? `<p class="store-email"><a href="mailto:${store.email}">${store.email}</a></p>` : ""}
                <p class="store-direction"><a href="https://www.google.com/maps/dir/?api=1&destination=${store.lat},${store.lng}" target="_blank">Direction</a></p>
                ${store.site ? `<p class="store-site"><a href="${store.site}" target="_blank">Site web</a></p>` : ""}
                ${store.distance != null ? `<p class="store-distance">${store.distance.toFixed(1)} km</p>` : ""}
            `;

      listItem.addEventListener("click", () => {
        document
          .querySelectorAll(".mova-sl-item")
          .forEach((el) => el.classList.remove("active"));
        document
          .querySelectorAll(".mova-marker-pin")
          .forEach((pin) => (pin.style.background = "#707070"));

        listItem.classList.add("active");
        marker._icon.querySelector(".mova-marker-pin").style.background =
          "#1a4759";

        map.setView([store.lat, store.lng], 13);
        marker.openPopup();

        if (window.innerWidth <= 992) {
          document
            .querySelector(".mova-sl-map-wrap")
            .scrollIntoView({ behavior: "smooth" });
        }
      });

      container.appendChild(listItem);
    }

    if (mode === "proximity") {
      // Mode proximité : liste plate triée par distance, sans sections province
      const grid = document.createElement("div");
      grid.className = "mova-sl-province-grid";
      listElement.appendChild(grid);

      storesToRender.forEach((store, index) => {
        createStoreCard(store, index + 1, grid);
      });
    } else {
      // Mode province : groupé par province
      const grouped = {};
      storesToRender.forEach((store) => {
        const prov = store.province || "Autre";
        if (!grouped[prov]) grouped[prov] = [];
        grouped[prov].push(store);
      });

      const sortedProvinces = Object.keys(grouped).sort();
      let globalIndex = 0;

      sortedProvinces.forEach((province) => {
        const section = document.createElement("div");
        section.className = "mova-sl-province-section";

        const title = document.createElement("h4");
        title.className = "mova-sl-province-title";
        title.textContent = province;
        section.appendChild(title);

        const grid = document.createElement("div");
        grid.className = "mova-sl-province-grid";
        section.appendChild(grid);

        grouped[province].forEach((store) => {
          globalIndex++;
          createStoreCard(store, globalIndex, grid);
        });

        listElement.appendChild(section);
      });
    }

    // Ajuster le zoom pour inclure tous les points affichés
    map.fitBounds(bounds, { padding: [50, 50], maxZoom: 14 });
  }

  // Premier affichage : on charge tout (mode province)
  renderStores(allStores, "province");

  let debounceTimer = null;

  // Fonction de filtrage avec recherche de proximité
  function applyFilters() {
    const term = searchInput ? searchInput.value.trim() : "";
    const province = provinceSelect ? provinceSelect.value : "";

    // Filtrer par province d'abord
    let stores = allStores.filter(
      (s) => province === "" || s.province === province,
    );

    // Champ vide : affichage par province
    if (term.length === 0) {
      clearTimeout(debounceTimer);
      renderStores(stores, "province");
      return;
    }

    // Filtrage texte immédiat (nom, ville, code postal)
    const textMatch = stores.filter((s) =>
      `${s.nom} ${s.ville} ${s.cp}`.toLowerCase().includes(term.toLowerCase()),
    );

    // Si on a des correspondances texte, les afficher tout de suite
    if (textMatch.length > 0) {
      renderStores(textMatch, "province");
    }

    // Géocodage en arrière-plan avec debounce (500ms)
    if (term.length >= 3) {
      clearTimeout(debounceTimer);
      debounceTimer = setTimeout(() => {
        geocode(term).then((coords) => {
          if (!coords) return; // Pas trouvé, on garde le filtrage texte

          // Calculer la distance pour chaque magasin
          const withDistance = stores.map((s) => ({
            ...s,
            distance: haversineKm(coords.lat, coords.lng, s.lat, s.lng),
          }));

          // Trier par distance (plus proche en premier)
          withDistance.sort((a, b) => a.distance - b.distance);

          // Afficher en mode proximité (liste plate avec distances)
          renderStores(withDistance, "proximity");
          map.setView([coords.lat, coords.lng], 8);
        });
      }, 500);
    }
  }

  // Écouteurs d'événements pour les filtres
  if (searchInput) searchInput.addEventListener("input", applyFilters);
  if (provinceSelect) provinceSelect.addEventListener("change", applyFilters);
});
