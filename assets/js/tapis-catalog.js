(function () {
    'use strict';

    var data      = movaTapisCatalog;
    var tapisList = data.tapis;

    var grid       = document.getElementById('mova-tc-grid');
    var detailWrap = document.getElementById('mova-tc-detail');
    var previewImg = document.getElementById('mova-tc-preview-img');
    var tapisName  = document.getElementById('mova-tc-tapis-name');
    var tapisDesc  = document.getElementById('mova-tc-tapis-desc');
    var swatchLrg  = document.getElementById('mova-tc-swatch-large');
    var swatchImg  = document.getElementById('mova-tc-swatch-img');
    var noPreview  = document.getElementById('mova-tc-no-preview');

    if (!grid || !detailWrap || tapisList.length === 0) return;

    var currentIndex = -1;

    /* ========================
       Sélection d'un tapis
       ======================== */
    var cards = grid.querySelectorAll('.mova-tc-card');

    cards.forEach(function (card) {
        card.addEventListener('click', function () {
            var index = parseInt(card.dataset.index, 10);
            selectTapis(index);
        });
    });

    function selectTapis(index) {
        if (index < 0 || index >= tapisList.length) return;

        var tapis = tapisList[index];
        currentIndex = index;

        // Activer la carte
        cards.forEach(function (c) { c.classList.remove('active'); });
        cards[index].classList.add('active');

        // Afficher la zone détail
        detailWrap.style.display = '';

        // Nom + description
        tapisName.textContent = tapis.name;
        tapisDesc.textContent = tapis.description || '';

        // Swatch texture large
        if (tapis.swatch) {
            swatchLrg.style.display = '';
            swatchImg.src = tapis.swatch;
            swatchImg.alt = tapis.name;
        } else {
            swatchLrg.style.display = 'none';
        }

        // Preview image
        if (tapis.preview) {
            noPreview.style.display = 'none';
            setPreview(tapis.preview, tapis.name);
        } else {
            noPreview.style.display = '';
            previewImg.src = '';
            previewImg.alt = '';
            previewImg.classList.remove('loading');
        }

        // Scroll vers la zone détail
        detailWrap.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }

    /* ========================
       Mise à jour du preview
       ======================== */
    function setPreview(src, alt) {
        previewImg.classList.add('loading');

        var img = new Image();
        img.onload = function () {
            previewImg.src = src;
            previewImg.alt = alt || '';
            previewImg.classList.remove('loading');
        };
        img.onerror = function () {
            previewImg.classList.remove('loading');
        };
        img.src = src;
    }
})();
