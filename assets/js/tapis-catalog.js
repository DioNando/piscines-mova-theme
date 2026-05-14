(function () {
    'use strict';

    var data      = movaTapisCatalog;
    var tapisList = data.tapis;

    var grid            = document.getElementById('mova-tc-grid');
    var layersWrap      = document.getElementById('mova-tc-layers');
    var layerFond       = document.getElementById('mova-tc-layer-fond');
    var tapisNameEl     = document.getElementById('mova-tc-tapis-name');
    var piscinesWrap    = document.getElementById('mova-tc-piscines');
    var couleursSection = document.getElementById('mova-tc-section-couleurs');
    var couleursWrap    = document.getElementById('mova-tc-couleurs');
    var couleurLabel    = document.getElementById('mova-tc-couleur-label');
    var zonesSection    = document.getElementById('mova-tc-section-zones');
    var zonesWrap       = document.getElementById('mova-tc-zones');

    var zoneLabels = movaTapisCatalog.i18n.zoneLabels;

    if (!grid || !layersWrap || tapisList.length === 0) return;

    var currentTapisIndex   = -1;
    var currentPiscineIndex = -1;

    /* ============================================================
       Sélection d'un tapis
       ============================================================ */
    var cards = grid.querySelectorAll('.mova-tc-card');

    cards.forEach(function (card) {
        card.addEventListener('click', function () {
            selectTapis(parseInt(card.dataset.index, 10), true);
        });
    });

    function selectTapis(index, doScroll) {
        if (index < 0 || index >= tapisList.length) return;

        var tapis = tapisList[index];
        currentTapisIndex = index;

        // Activer la carte
        cards.forEach(function (c) { c.classList.remove('active'); });
        cards[index].classList.add('active');

        // Nom
        tapisNameEl.textContent = tapis.name;

        // Construire les boutons piscines
        buildPiscines(tapis.piscines);

        // Sélectionner la première piscine automatiquement
        selectPiscine(0);
    }

    /* ============================================================
       Construction des boutons piscines
       ============================================================ */
    function buildPiscines(piscines) {
        piscinesWrap.innerHTML = '';

        piscines.forEach(function (piscine, idx) {
            var btn = document.createElement('button');
            btn.className = 'mova-tc-piscine-btn';
            btn.dataset.index = idx;
            btn.setAttribute('aria-label', piscine.title);

            var titleEl = document.createElement('span');
            titleEl.className = 'mova-tc-piscine-title';
            titleEl.textContent = piscine.title;
            btn.appendChild(titleEl);

            if (piscine.categorie) {
                var catEl = document.createElement('span');
                catEl.className = 'mova-tc-piscine-cat';
                catEl.textContent = piscine.categorie;
                btn.appendChild(catEl);
            }

            btn.addEventListener('click', function () {
                selectPiscine(idx);
            });

            piscinesWrap.appendChild(btn);
        });
    }

    /* ============================================================
       Sélection d'une piscine — fond + overlays + couleurs + zones
       ============================================================ */
    function selectPiscine(index) {
        var tapis   = tapisList[currentTapisIndex];
        var piscine = tapis.piscines[index];
        if (!piscine) return;

        currentPiscineIndex = index;

        // Activer le bouton
        var btns = piscinesWrap.querySelectorAll('.mova-tc-piscine-btn');
        btns.forEach(function (b) { b.classList.remove('active'); });
        if (btns[index]) btns[index].classList.add('active');

        // Fond (première couleur disponible)
        var couleurs = piscine.couleurs || [];
        layerFond.src = couleurs.length > 0 ? couleurs[0].fondUrl : piscine.defaultFondUrl;
        layerFond.alt = piscine.title;

        // Supprimer les overlays précédents
        var existing = layersWrap.querySelectorAll('.mova-tc-layer--overlay');
        existing.forEach(function (el) { el.parentNode.removeChild(el); });

        // Créer un overlay par zone (avec id + data-zone pour les toggles)
        var zones = Object.keys(piscine.zones);
        zones.forEach(function (zone) {
            var overlayUrl = piscine.zones[zone];
            if (!overlayUrl) return;

            var img = document.createElement('img');
            img.className = 'mova-tc-layer mova-tc-layer--overlay';
            img.alt = zoneLabels[zone] || zone;
            img.src = overlayUrl;
            img.dataset.zone = zone;
            img.id = 'mova-tc-overlay-' + zone;
            layersWrap.appendChild(img);
        });

        // Construire sélecteur couleurs
        buildCouleurs(piscine);

        // Construire toggles zones
        buildZones(piscine);
    }

    /* ============================================================
       Sélecteur de couleur de coque
       ============================================================ */
    function buildCouleurs(piscine) {
        if (!couleursWrap) return;
        couleursWrap.innerHTML = '';

        var couleurs = piscine.couleurs || [];

        if (couleurs.length <= 1) {
            if (couleursSection) couleursSection.style.display = 'none';
            return;
        }

        if (couleursSection) couleursSection.style.display = '';

        couleurs.forEach(function (c, idx) {
            var btn = document.createElement('button');
            btn.className = 'mova-tc-swatch' + (idx === 0 ? ' is-active' : '');
            btn.dataset.slug = c.slug;
            btn.title = c.name;
            btn.setAttribute('aria-label', c.name);

            if (c.swatch) {
                var img = document.createElement('img');
                img.src = c.swatch;
                img.alt = c.name;
                btn.appendChild(img);
            } else {
                var span = document.createElement('span');
                span.className = 'mova-tc-swatch-placeholder';
                span.textContent = c.name.substring(0, 2).toUpperCase();
                btn.appendChild(span);
            }

            btn.addEventListener('click', function () {
                couleursWrap.querySelectorAll('.mova-tc-swatch').forEach(function (s) {
                    s.classList.remove('is-active');
                });
                btn.classList.add('is-active');
                if (couleurLabel) couleurLabel.textContent = c.name;
                layerFond.src = c.fondUrl;
            });

            couleursWrap.appendChild(btn);
        });

        if (couleurLabel) couleurLabel.textContent = couleurs[0].name;
    }

    /* ============================================================
       Toggles par zone
       ============================================================ */
    function buildZones(piscine) {
        if (!zonesWrap) return;
        zonesWrap.innerHTML = '';

        var zones = Object.keys(piscine.zones);

        if (zones.length === 0) {
            if (zonesSection) zonesSection.style.display = 'none';
            return;
        }

        if (zonesSection) zonesSection.style.display = '';

        zones.forEach(function (zone) {
            var label = zoneLabels[zone] || (zone.charAt(0).toUpperCase() + zone.slice(1));

            var row = document.createElement('div');
            row.className = 'mova-tc-zone-row';

            var header = document.createElement('div');
            header.className = 'mova-tc-zone-header';

            var titleEl = document.createElement('span');
            titleEl.className = 'mova-tc-zone-label';
            titleEl.textContent = label;

            var toggle = document.createElement('button');
            toggle.className = 'mova-tc-zone-toggle is-active';
            toggle.dataset.zone = zone;
            toggle.setAttribute('aria-pressed', 'true');
            toggle.title = movaTapisCatalog.i18n.toggleZone.replace('{zone}', label);

            var icon = document.createElement('span');
            icon.className = 'mova-tc-zone-toggle-icon';
            toggle.appendChild(icon);

            toggle.addEventListener('click', function () {
                var overlay = document.getElementById('mova-tc-overlay-' + zone);
                if (toggle.classList.contains('is-active')) {
                    toggle.classList.remove('is-active');
                    toggle.setAttribute('aria-pressed', 'false');
                    if (overlay) overlay.style.opacity = '0';
                } else {
                    toggle.classList.add('is-active');
                    toggle.setAttribute('aria-pressed', 'true');
                    if (overlay) overlay.style.opacity = '1';
                }
            });

            header.appendChild(titleEl);
            header.appendChild(toggle);
            row.appendChild(header);
            zonesWrap.appendChild(row);
        });
    }

    /* ============================================================
       Auto-sélection au chargement
       ============================================================ */
    selectTapis(0, false);

})();
