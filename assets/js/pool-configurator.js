(function () {
    'use strict';

    var previewImg = document.getElementById('mova-cfg-preview-img');
    var colorName  = document.getElementById('mova-cfg-color-name');
    var swatches   = document.querySelectorAll('#mova-cfg-couleurs .mova-cfg-swatch');

    if (!previewImg || swatches.length === 0) return;

    var defaultSrc    = movaConfigurator.defaultImage;
    var selectedColor = '';

    /* ========================
       Couleurs
       ======================== */
    swatches.forEach(function (swatch) {
        swatch.addEventListener('click', function () {
            var wasActive = swatch.classList.contains('active');

            // Désactiver tous
            swatches.forEach(function (s) { s.classList.remove('active'); });

            if (wasActive) {
                // Désélection → retour image par défaut
                selectedColor = '';
                setPreview(defaultSrc);
                if (colorName) colorName.textContent = '';
            } else {
                // Sélection
                swatch.classList.add('active');
                selectedColor = swatch.dataset.slug;
                var ambiance = swatch.dataset.ambiance;

                if (ambiance) {
                    setPreview(ambiance);
                }

                if (colorName) {
                    colorName.textContent = swatch.getAttribute('title');
                }
            }
        });
    });

    /* ========================
       Helpers
       ======================== */
    function setPreview(src) {
        if (!src || previewImg.src === src) return;

        previewImg.classList.add('loading');

        var img = new Image();
        img.onload = function () {
            previewImg.src = src;
            previewImg.classList.remove('loading');
        };
        img.onerror = function () {
            previewImg.classList.remove('loading');
        };
        img.src = src;
    }

})();
