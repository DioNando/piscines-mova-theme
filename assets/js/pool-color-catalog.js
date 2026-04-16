(function () {
    'use strict';

    var data        = movaColorCatalog;
    var models      = data.models;
    var devisUrl    = data.devisUrl;

    var modelsGrid  = document.getElementById('mova-ccc-models-grid');
    var detailWrap  = document.getElementById('mova-ccc-detail');
    var previewImg  = document.getElementById('mova-ccc-preview-img');
    var modelTitle  = document.getElementById('mova-ccc-model-title');
    var swatchesEl  = document.getElementById('mova-ccc-swatches');
    var colorNameEl = document.getElementById('mova-ccc-color-name');
    var colorsSection = document.getElementById('mova-ccc-colors-section');
    var noColorsMsg   = document.getElementById('mova-ccc-no-colors');
    var linkDetail    = document.getElementById('mova-ccc-link-detail');

    if (!modelsGrid || !detailWrap || models.length === 0) return;

    var currentModelIndex = -1;
    var selectedColor     = '';

    /* ========================
       Sélection de modèle
       ======================== */
    var modelCards = modelsGrid.querySelectorAll('.mova-ccc-model-card');

    modelCards.forEach(function (card) {
        card.addEventListener('click', function () {
            var index = parseInt(card.dataset.index, 10);
            selectModel(index);
        });
    });

    function selectModel(index, doScroll) {
        if (index < 0 || index >= models.length) return;

        var model = models[index];
        currentModelIndex = index;
        selectedColor = '';

        // Activer la carte
        modelCards.forEach(function (c) { c.classList.remove('active'); });
        modelCards[index].classList.add('active');

        // Afficher la zone détail
        detailWrap.style.display = '';

        // Image par défaut
        previewImg.src = model.defaultImage || '';
        previewImg.alt = model.title;
        previewImg.classList.remove('loading');

        // Titre
        modelTitle.textContent = model.title;

        // Lien fiche
        linkDetail.href = model.permalink;

        // Nom couleur reset
        colorNameEl.textContent = '';

        // Construire les swatches
        buildSwatches(model.couleurs);

        // Scroll vers la zone détail
        if (doScroll !== false) {
            detailWrap.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }
    }

    /* ========================
       Construction des swatches
       ======================== */
    function buildSwatches(couleurs) {
        swatchesEl.innerHTML = '';

        if (!couleurs || couleurs.length === 0) {
            colorsSection.style.display = 'none';
            noColorsMsg.style.display = '';
            return;
        }

        colorsSection.style.display = '';
        noColorsMsg.style.display = 'none';

        couleurs.forEach(function (couleur) {
            var btn = document.createElement('button');
            btn.className = 'mova-ccc-swatch';
            btn.dataset.slug = couleur.slug;
            btn.dataset.ambiance = couleur.ambiance || '';
            btn.title = couleur.name;
            btn.setAttribute('aria-label', couleur.name);

            if (couleur.swatch) {
                var img = document.createElement('img');
                img.src = couleur.swatch;
                img.alt = couleur.name;
                btn.appendChild(img);
            } else {
                var span = document.createElement('span');
                span.className = 'mova-ccc-swatch-placeholder';
                span.textContent = couleur.name.substring(0, 2).toUpperCase();
                btn.appendChild(span);
            }

            btn.addEventListener('click', function () {
                handleSwatchClick(btn);
            });

            swatchesEl.appendChild(btn);
        });
    }

    /* ========================
       Clic sur swatch
       ======================== */
    function handleSwatchClick(swatch) {
        var wasActive = swatch.classList.contains('active');
        var allSwatches = swatchesEl.querySelectorAll('.mova-ccc-swatch');

        // Désactiver tous
        allSwatches.forEach(function (s) { s.classList.remove('active'); });

        if (wasActive) {
            // Désélection → retour image par défaut
            selectedColor = '';
            setPreview(models[currentModelIndex].defaultImage);
            colorNameEl.textContent = '';
        } else {
            // Sélection
            swatch.classList.add('active');
            selectedColor = swatch.dataset.slug;
            var ambiance = swatch.dataset.ambiance;

            if (ambiance) {
                setPreview(ambiance);
            }

            colorNameEl.textContent = swatch.getAttribute('title');
        }
    }

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

    // Auto-sélection au chargement
    selectModel(0, false);

})();
