(function () {
    'use strict';

    var data        = movaColorCatalog;
    var couleurs    = data.couleurs;

    var swatchesEl  = document.getElementById('mova-ccc-swatches');
    var previewImg  = document.getElementById('mova-ccc-preview-img');
    var colorNameEl = document.getElementById('mova-ccc-color-name');

    if (!swatchesEl || !previewImg || !couleurs || couleurs.length === 0) return;

    var selectedSlug = '';

    /* ========================
       Clic sur swatch
       ======================== */
    var allSwatches = swatchesEl.querySelectorAll('.mova-ccc-swatch');

    allSwatches.forEach(function (swatch) {
        swatch.addEventListener('click', function () {
            handleSwatchClick(swatch);
        });
    });

    function handleSwatchClick(swatch) {
        var wasActive = swatch.classList.contains('active');

        // Désactiver tous
        allSwatches.forEach(function (s) { s.classList.remove('active'); });

        if (wasActive) {
            // Désélection
            selectedSlug = '';
            colorNameEl.textContent = '';
            previewImg.classList.add('loading');
            previewImg.classList.remove('active');
        } else {
            swatch.classList.add('active');
            selectedSlug = swatch.dataset.slug;
            colorNameEl.textContent = swatch.getAttribute('title');

            var ambiance = swatch.dataset.ambiance;
            if (ambiance) {
                setPreview(ambiance);
            }
        }
    }

    /* ========================
       Changer l'image de prévisualisation
       ======================== */
    function setPreview(src) {
        if (!src) return;

        previewImg.classList.add('loading');

        var img = new Image();
        img.onload = function () {
            previewImg.src = src;
            previewImg.alt = colorNameEl.textContent;
            previewImg.classList.remove('loading');
        };
        img.onerror = function () {
            previewImg.classList.remove('loading');
        };
        img.src = src;
    }

    // Sélection automatique de la première couleur au chargement
    if (allSwatches.length > 0) {
        handleSwatchClick(allSwatches[0]);
    }

})();

