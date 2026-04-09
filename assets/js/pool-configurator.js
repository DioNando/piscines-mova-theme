(function () {
    'use strict';

    var cfg = window.movaConfigurator;
    if (!cfg) return;

    var layerFond    = document.getElementById('mova-cfg-layer-fond');
    var couleurLabel = document.getElementById('mova-cfg-couleur-label');
    var couleurBtns  = document.querySelectorAll('#mova-cfg-couleurs .mova-cfg-swatch');

    if (!layerFond) return;

    var activeCouleur = cfg.defaultCouleur;

    // Map slug_fichier → wpSlug for devis URL
    var couleurWpSlugMap = {};
    cfg.couleurs.forEach(function (c) {
        couleurWpSlugMap[c.slug] = c.wpSlug || c.slug;
    });

    // Map slug_fichier → wpSlug for tapis devis URL
    var tapisWpSlugMap = {};
    Object.keys(cfg.tapisParZone || {}).forEach(function (zone) {
        cfg.tapisParZone[zone].forEach(function (t) {
            tapisWpSlugMap[t.slug] = t.wpSlug || t.slug;
        });
    });

    // Tapis actif par zone
    var activeTapis = {};
    var activeZones = {};
    cfg.zones.forEach(function (z) {
        activeTapis[z] = cfg.defaultsTapis[z] || '';
        activeZones[z] = true;
    });

    /* ========================
       Couleur swatches
       ======================== */
    couleurBtns.forEach(function (btn) {
        btn.addEventListener('click', function () {
            var slug = btn.dataset.slug;
            if (slug === activeCouleur) return;

            activeCouleur = slug;

            couleurBtns.forEach(function (b) { b.classList.remove('is-active'); });
            btn.classList.add('is-active');

            if (couleurLabel) couleurLabel.textContent = btn.getAttribute('title');

            var fondUrl = cfg.baseUrl + 'piscine-' + cfg.slugDimension + '-' + slug + '.png';
            loadImage(fondUrl, function (src) {
                layerFond.src = src;
            });
        });
    });

    /* ========================
       Tapis swatches (per zone)
       ======================== */
    cfg.zones.forEach(function (zone) {
        var container = document.querySelector('.mova-cfg-zone-swatches[data-zone="' + zone + '"]');
        if (!container) return;

        var btns = container.querySelectorAll('.mova-cfg-swatch');
        var label = document.querySelector('[data-zone-label="' + zone + '"]');

        btns.forEach(function (btn) {
            btn.addEventListener('click', function () {
                var slug = btn.dataset.slug;
                if (slug === activeTapis[zone]) return;

                activeTapis[zone] = slug;

                // Update active class within this zone only
                btns.forEach(function (b) { b.classList.remove('is-active'); });
                btn.classList.add('is-active');

                if (label) label.textContent = btn.getAttribute('title');

                // Update this zone's overlay only
                if (activeZones[zone]) {
                    updateOverlay(zone);
                }
            });
        });
    });

    /* ========================
       Zone toggles
       ======================== */
    var zoneBtns = document.querySelectorAll('.mova-cfg-zone-toggle');
    zoneBtns.forEach(function (btn) {
        btn.addEventListener('click', function () {
            var zone = btn.dataset.zone;
            var layer = document.getElementById('mova-cfg-layer-' + zone);
            var section = btn.closest('.mova-cfg-section--zone');
            if (!layer) return;

            activeZones[zone] = !activeZones[zone];
            btn.classList.toggle('is-active');
            btn.setAttribute('aria-pressed', activeZones[zone] ? 'true' : 'false');

            // Dim the swatches when zone is off
            if (section) {
                var swatchContainer = section.querySelector('.mova-cfg-zone-swatches');
                if (swatchContainer) {
                    swatchContainer.classList.toggle('is-disabled', !activeZones[zone]);
                }
            }

            if (activeZones[zone]) {
                updateOverlay(zone);
            } else {
                layer.style.display = 'none';
            }
        });
    });

    /* ========================
       Helpers
       ======================== */
    function getOverlayUrl(zone) {
        return cfg.baseUrl + cfg.slugDimension + '-' + activeTapis[zone] + '-' + zone + '.png';
    }

    function updateOverlay(zone) {
        var layer = document.getElementById('mova-cfg-layer-' + zone);
        if (!layer) return;

        var url = getOverlayUrl(zone);
        loadImage(url, function (src) {
            layer.src = src;
            layer.style.display = '';
        }, function () {
            layer.style.display = 'none';
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

    /* ========================
       Lightbox zoom
       ======================== */
    var zoomBtn   = document.getElementById('mova-cfg-zoom');
    var lightbox  = document.getElementById('mova-cfg-lightbox');
    var lbContent = document.getElementById('mova-cfg-lb-content');
    var lbClose   = document.getElementById('mova-cfg-lb-close');
    var lbOpen    = false;

    function openLightbox() {
        if (!lightbox || !lbContent) return;
        // Clone the layers into the lightbox
        var layers = document.getElementById('mova-cfg-layers');
        if (!layers) return;

        lbContent.innerHTML = '';
        var clone = layers.cloneNode(true);
        clone.removeAttribute('id');
        lbContent.appendChild(clone);

        lbOpen = true;
        lightbox.classList.add('open');
        document.body.style.overflow = 'hidden';
    }

    function closeLightbox() {
        if (!lightbox) return;
        lbOpen = false;
        lightbox.classList.remove('open');
        document.body.style.overflow = '';
        if (lbContent) lbContent.innerHTML = '';
    }

    if (zoomBtn) {
        zoomBtn.addEventListener('click', openLightbox);
    }

    if (lbClose) {
        lbClose.addEventListener('click', closeLightbox);
    }

    if (lightbox) {
        lightbox.addEventListener('click', function (e) {
            if (e.target === lightbox || e.target === lbContent) closeLightbox();
        });
    }

    document.addEventListener('keydown', function (e) {
        if (lbOpen && e.key === 'Escape') closeLightbox();
    });

    /* ========================
       Bouton Obtenir un devis
       ======================== */
    var devisBtn = document.getElementById('mova-cfg-devis-btn');
    if (devisBtn && cfg.devisUrl) {
        devisBtn.addEventListener('click', function (e) {
            e.preventDefault();

            var params = [];

            // Modèle
            if (cfg.modelSlug) {
                params.push('model=' + encodeURIComponent(cfg.modelSlug));
            }

            // Couleur active (WP slug for quote form)
            if (activeCouleur) {
                var wpSlug = couleurWpSlugMap[activeCouleur] || activeCouleur;
                params.push('couleur=' + encodeURIComponent(wpSlug));
            }

            // Options cochées
            var checked = document.querySelectorAll('#mova-cfg-options input[type="checkbox"]:checked');
            if (checked.length) {
                var opts = [];
                checked.forEach(function (cb) { opts.push(cb.value); });
                params.push('options=' + encodeURIComponent(opts.join(',')));
            }

            // Tapis actifs par zone (WP slug for quote form)
            cfg.zones.forEach(function (zone) {
                if (activeZones[zone] && activeTapis[zone]) {
                    var wpSlug = tapisWpSlugMap[activeTapis[zone]] || activeTapis[zone];
                    params.push('tapis_' + zone + '=' + encodeURIComponent(wpSlug));
                }
            });

            var url = cfg.devisUrl + (params.length ? '?' + params.join('&') : '');
            window.location.href = url;
        });
    }

})();
