(function () {
    'use strict';

    var cfg = window.movaConfigurator;
    if (!cfg) return;

    var layerFond     = document.getElementById('mova-cfg-layer-fond');
    var couleurLabel  = document.getElementById('mova-cfg-couleur-label');
    var tapisLabel    = document.getElementById('mova-cfg-tapis-label');
    var couleurBtns   = document.querySelectorAll('#mova-cfg-couleurs .mova-cfg-swatch');
    var tapisBtns     = document.querySelectorAll('#mova-cfg-tapis .mova-cfg-swatch');
    var zoneBtns      = document.querySelectorAll('#mova-cfg-zones .mova-cfg-zone-toggle');

    if (!layerFond) return;

    var activeCouleur = cfg.defaultCouleur;
    var activeTapis   = cfg.defaultTapis;

    // Zones actives (toutes par défaut)
    var activeZones = {};
    cfg.zones.forEach(function (z) { activeZones[z] = true; });

    /* ========================
       Couleur swatches
       ======================== */
    couleurBtns.forEach(function (btn) {
        btn.addEventListener('click', function () {
            var slug = btn.dataset.slug;
            if (slug === activeCouleur) return;

            activeCouleur = slug;

            // Mise à jour classes actives
            couleurBtns.forEach(function (b) { b.classList.remove('is-active'); });
            btn.classList.add('is-active');

            // Label
            if (couleurLabel) couleurLabel.textContent = btn.getAttribute('title');

            // Changer le fond — les overlays restent
            var fondUrl = cfg.baseUrl + 'piscine-' + cfg.slugDimension + '-' + slug + '.png';
            loadImage(fondUrl, function (src) {
                layerFond.src = src;
            });
        });
    });

    /* ========================
       Tapis swatches
       ======================== */
    tapisBtns.forEach(function (btn) {
        btn.addEventListener('click', function () {
            var slug = btn.dataset.slug;
            if (slug === activeTapis) return;

            activeTapis = slug;

            // Mise à jour classes actives
            tapisBtns.forEach(function (b) { b.classList.remove('is-active'); });
            btn.classList.add('is-active');

            // Label
            if (tapisLabel) tapisLabel.textContent = btn.getAttribute('title');

            // Mettre à jour tous les overlays de zones actives
            updateAllOverlays();
        });
    });

    /* ========================
       Zone toggles
       ======================== */
    zoneBtns.forEach(function (btn) {
        btn.addEventListener('click', function () {
            var zone = btn.dataset.zone;
            var layer = document.getElementById('mova-cfg-layer-' + zone);
            if (!layer) return;

            activeZones[zone] = !activeZones[zone];
            btn.classList.toggle('is-active');
            btn.setAttribute('aria-pressed', activeZones[zone] ? 'true' : 'false');

            if (activeZones[zone]) {
                // Réactiver : charger l'overlay courant
                var url = getOverlayUrl(zone);
                loadImage(url, function (src) {
                    layer.src = src;
                    layer.style.display = '';
                });
            } else {
                layer.style.display = 'none';
            }
        });
    });

    /* ========================
       Helpers
       ======================== */
    function getOverlayUrl(zone) {
        return cfg.baseUrl + cfg.slugDimension + '-' + activeTapis + '-' + zone + '.png';
    }

    function updateAllOverlays() {
        cfg.zones.forEach(function (zone) {
            var layer = document.getElementById('mova-cfg-layer-' + zone);
            if (!layer) return;

            if (!activeZones[zone]) {
                layer.style.display = 'none';
                return;
            }

            var url = getOverlayUrl(zone);
            loadImage(url, function (src) {
                layer.src = src;
                layer.style.display = '';
            }, function () {
                // Overlay inexistant → cacher
                layer.style.display = 'none';
            });
        });
    }

    function loadImage(src, onLoad, onError) {
        var img = new Image();
        img.onload = function () {
            if (onLoad) onLoad(src);
        };
        img.onerror = function () {
            if (onError) onError();
        };
        img.src = src;
    }

})();
