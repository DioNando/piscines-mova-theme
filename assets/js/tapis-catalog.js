(function () {
    'use strict';

    var data      = movaTapisCatalog;
    var tapisList = data.tapis;

    var grid         = document.getElementById('mova-tc-grid');
    var layersWrap   = document.getElementById('mova-tc-layers');
    var layerFond    = document.getElementById('mova-tc-layer-fond');
    var tapisNameEl  = document.getElementById('mova-tc-tapis-name');
    var piscinesWrap = document.getElementById('mova-tc-piscines');

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

        // Scroll (uniquement sur clic utilisateur)
        if (doScroll) {
            layersWrap.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }
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
            btn.textContent = piscine.title;
            btn.setAttribute('aria-label', piscine.title);

            btn.addEventListener('click', function () {
                selectPiscine(idx);
            });

            piscinesWrap.appendChild(btn);
        });
    }

    /* ============================================================
       Sélection d'une piscine — affiche toutes les zones
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

        // Fond
        layerFond.src = piscine.defaultFondUrl;
        layerFond.alt = piscine.title;

        // Supprimer les overlays précédents
        var existing = layersWrap.querySelectorAll('.mova-tc-layer--overlay');
        existing.forEach(function (el) { el.parentNode.removeChild(el); });

        // Créer un overlay par zone (toutes simultanément)
        var zones = Object.keys(piscine.zones);
        zones.forEach(function (zone) {
            var overlayUrl = piscine.zones[zone];
            if (!overlayUrl) return;

            var img = document.createElement('img');
            img.className = 'mova-tc-layer mova-tc-layer--overlay';
            img.alt = zone;
            img.src = overlayUrl;
            layersWrap.appendChild(img);
        });
    }

    /* ============================================================
       Auto-sélection au chargement
       ============================================================ */
    selectTapis(0, false);

})();
