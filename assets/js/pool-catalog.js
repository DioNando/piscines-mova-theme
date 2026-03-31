document.addEventListener("DOMContentLoaded", function () {
  if (typeof movaPoolData === "undefined") return;

  const { ajaxUrl, nonce, perPage } = movaPoolData;
  const gridElement = document.getElementById("mova-pc-grid");
  const countElement = document.getElementById("mova-pc-count");
  const loadMoreBtn = document.getElementById("mova-pc-load-more");

  let currentPage = 1;
  let totalPools = 0;
  let hasMore = false;
  let isLoading = false;

  // --- Filtrage ---
  function getActiveFilters(filterType) {
    const group = document.querySelector(
      `.mova-pc-filter-group[data-filter="${filterType}"]`,
    );
    if (!group) return [];
    const checked = group.querySelectorAll('input[type="checkbox"]:checked');
    const values = [];
    checked.forEach((cb) => {
      if (cb.value !== "") values.push(cb.value);
    });
    return values;
  }

  // --- AJAX fetch ---
  function fetchPools(page, append) {
    if (isLoading) return;
    isLoading = true;

    // Afficher le loader
    if (!append) {
      gridElement.innerHTML = '<div class="mova-pc-loader"></div>';
      countElement.textContent = "";
    }
    loadMoreBtn.style.display = "none";

    const catFilters = getActiveFilters("categorie");
    const dimFilters = getActiveFilters("dimension");

    const body = new FormData();
    body.append("action", "mova_pool_catalog_filter");
    body.append("nonce", nonce);
    body.append("page", page);
    body.append("per_page", perPage);
    catFilters.forEach((s) => body.append("categories[]", s));
    dimFilters.forEach((s) => body.append("dimensions[]", s));

    fetch(ajaxUrl, { method: "POST", body })
      .then((res) => res.json())
      .then((json) => {
        if (!json.success) return;

        const { pools, total, hasMore: more } = json.data;
        totalPools = total;
        hasMore = more;

        // Compteur
        countElement.textContent = `${total} modèle${total > 1 ? "s" : ""}`;

        // Grille
        if (!append) gridElement.innerHTML = "";

        pools.forEach((pool) => {
          const card = document.createElement("a");
          card.href = pool.permalink;
          card.className = "mova-pc-card";

          card.innerHTML = `
            <div class="mova-pc-card-img">
              ${pool.thumbnail ? `<img src="${pool.thumbnail}" alt="${pool.titre}" loading="lazy">` : '<div class="mova-pc-card-placeholder"></div>'}
            </div>
            <div class="mova-pc-card-body">
              <div class="mova-pc-card-info">
                <div class="mova-pc-card-title">${pool.titre}</div>
                <span class="mova-pc-card-subtitle">${pool.subtitle}</span>
              </div>
              <span class="mova-pc-card-arrow">
                <svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                  <path d="M7 4l6 6-6 6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
              </span>
            </div>
          `;

          gridElement.appendChild(card);
        });

        // Bouton "Charger plus"
        loadMoreBtn.style.display = hasMore ? "inline-flex" : "none";
      })
      .catch((err) => {
        console.error("Pool catalog AJAX error:", err);
        if (!append) gridElement.innerHTML = '<p class="mova-pc-error">Erreur de chargement.</p>';
      })
      .finally(() => {
        isLoading = false;
      });
  }

  // --- Événements filtres ---
  document.querySelectorAll(".mova-pc-filter-group").forEach((group) => {
    const filterType = group.dataset.filter;

    group.addEventListener("change", (e) => {
      const checkbox = e.target;

      // Gestion spéciale du "Tous les modèles" (value="")
      if (filterType === "categorie") {
        const allCheckbox = group.querySelector('input[value=""]');
        const otherCheckboxes = group.querySelectorAll('input[value]:not([value=""])');

        if (checkbox.value === "") {
          // On coche "Tous" → on décoche les autres
          if (checkbox.checked) {
            otherCheckboxes.forEach((cb) => (cb.checked = false));
          }
        } else {
          // On coche un filtre spécifique → on décoche "Tous"
          if (allCheckbox) allCheckbox.checked = false;

          // Si plus rien n'est coché, on recoche "Tous"
          const anyChecked = [...otherCheckboxes].some((cb) => cb.checked);
          if (!anyChecked && allCheckbox) allCheckbox.checked = true;
        }
      }

      currentPage = 1;
      fetchPools(1, false);
    });
  });

  // --- Charger plus ---
  loadMoreBtn.addEventListener("click", () => {
    currentPage++;
    fetchPools(currentPage, true);
  });

  // --- Premier rendu via AJAX ---
  fetchPools(1, false);

  // --- Accordéon filtres mobile ---
  const filterToggle = document.getElementById("mova-pc-filter-toggle");
  const sidebarContent = document.getElementById("mova-pc-sidebar-content");

  if (filterToggle && sidebarContent) {
    filterToggle.addEventListener("click", () => {
      filterToggle.classList.toggle("open");
      sidebarContent.classList.toggle("open");
    });
  }
});
