(function () {
    'use strict';

    const grid      = document.getElementById('mova-insp-grid');
    const loadBtn   = document.getElementById('mova-insp-loadmore');
    const lightbox  = document.getElementById('mova-insp-lightbox');
    const lbImg     = document.getElementById('mova-insp-lightbox-img');
    const lbCaption = document.getElementById('mova-insp-lightbox-caption');

    if (!grid) return;

    let currentPage  = 1;
    let activeFilter = '';
    let lbIndex      = 0;

    /* ========================
       Filtres
       ======================== */
    document.querySelectorAll('.mova-insp-filter').forEach(function (btn) {
        btn.addEventListener('click', function () {
            document.querySelector('.mova-insp-filter.active')?.classList.remove('active');
            btn.classList.add('active');
            activeFilter = btn.dataset.slug || '';
            currentPage = 1;
            fetchItems(true);
        });
    });

    /* ========================
       Load more
       ======================== */
    if (loadBtn) {
        loadBtn.addEventListener('click', function () {
            currentPage++;
            fetchItems(false);
        });
    }

    /* ========================
       AJAX fetch
       ======================== */
    function fetchItems(replace) {
        if (loadBtn) {
            loadBtn.classList.add('loading');
            loadBtn.textContent = 'Chargement…';
        }

        var data = new FormData();
        data.append('action', 'mova_inspirations');
        data.append('nonce', movaInspirations.nonce);
        data.append('page', currentPage);
        data.append('per_page', movaInspirations.perPage);

        if (activeFilter) {
            data.append('categories[]', activeFilter);
        }

        fetch(movaInspirations.ajaxUrl, {
            method: 'POST',
            credentials: 'same-origin',
            body: data,
        })
        .then(function (res) { return res.json(); })
        .then(function (res) {
            if (!res.success) return;

            if (replace) {
                grid.innerHTML = '';
            }

            res.data.items.forEach(function (item) {
                grid.insertAdjacentHTML('beforeend', buildCard(item));
            });

            // Load more visibility
            if (loadBtn) {
                if (res.data.hasMore) {
                    loadBtn.classList.remove('loading');
                    loadBtn.textContent = 'Voir plus d\'inspirations';
                    loadBtn.style.display = '';
                } else {
                    loadBtn.style.display = 'none';
                }
            }

            bindCards();
        });
    }

    /* ========================
       Build card HTML
       ======================== */
    function buildCard(item) {
        var linkHtml = '';
        if (item.piscine_link) {
            linkHtml = '<a href="' + escHtml(item.piscine_link) + '" class="mova-insp-link">' +
                'Voir le modèle ' +
                '<svg width="14" height="14" viewBox="0 0 16 16" fill="none"><path d="M6 3l5 5-5 5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>' +
                '</a>';
        }

        return '<div class="mova-insp-item mova-insp-item--' + escAttr(item.taille) + '" data-full="' + escAttr(item.thumbnail_full) + '">' +
            '<img src="' + escAttr(item.thumbnail) + '" alt="' + escAttr(item.legende) + '" loading="lazy" />' +
            linkHtml +
            '<div class="mova-insp-overlay">' +
                (item.legende ? '<h4 class="mova-insp-legende">' + escHtml(item.legende) + '</h4>' : '') +
                (item.credit ? '<p class="mova-insp-credit">' + escHtml(item.credit) + '</p>' : '') +
            '</div>' +
        '</div>';
    }

    /* ========================
       Lightbox
       ======================== */
    function bindCards() {
        grid.querySelectorAll('.mova-insp-item').forEach(function (card, i) {
            card.style.animationDelay = (i % 12) * 0.05 + 's';

            // Éviter le double-bind
            if (card.dataset.bound) return;
            card.dataset.bound = '1';

            card.addEventListener('click', function (e) {
                // Ne pas ouvrir la lightbox si on clique sur le lien
                if (e.target.closest('.mova-insp-link')) return;
                openLightbox(card);
            });
        });
    }

    function getItems() {
        return Array.from(grid.querySelectorAll('.mova-insp-item'));
    }

    function openLightbox(card) {
        var items = getItems();
        lbIndex = items.indexOf(card);
        showLightboxImage();
        lightbox.classList.add('open');
        document.body.style.overflow = 'hidden';
    }

    function closeLightbox() {
        lightbox.classList.remove('open');
        document.body.style.overflow = '';
    }

    function showLightboxImage() {
        var items = getItems();
        var card  = items[lbIndex];
        if (!card) return;

        var src = card.dataset.full || card.querySelector('img').src;
        lbImg.src = src;
        lbImg.alt = card.querySelector('img')?.alt || '';

        var legende = card.querySelector('.mova-insp-legende');
        var credit  = card.querySelector('.mova-insp-credit');
        var caption = '';
        if (legende) caption += '<strong>' + legende.textContent + '</strong>';
        if (credit)  caption += '<div class="credit">' + credit.textContent + '</div>';
        lbCaption.innerHTML = caption;
    }

    if (lightbox) {
        lightbox.querySelector('.mova-insp-lightbox-close').addEventListener('click', closeLightbox);

        lightbox.querySelector('.mova-insp-lightbox-prev').addEventListener('click', function () {
            var items = getItems();
            lbIndex = (lbIndex - 1 + items.length) % items.length;
            showLightboxImage();
        });

        lightbox.querySelector('.mova-insp-lightbox-next').addEventListener('click', function () {
            var items = getItems();
            lbIndex = (lbIndex + 1) % items.length;
            showLightboxImage();
        });

        // Fermer via overlay
        lightbox.addEventListener('click', function (e) {
            if (e.target === lightbox) closeLightbox();
        });

        // Clavier
        document.addEventListener('keydown', function (e) {
            if (!lightbox.classList.contains('open')) return;
            if (e.key === 'Escape') closeLightbox();
            if (e.key === 'ArrowLeft')  lightbox.querySelector('.mova-insp-lightbox-prev').click();
            if (e.key === 'ArrowRight') lightbox.querySelector('.mova-insp-lightbox-next').click();
        });
    }

    /* ========================
       Helpers
       ======================== */
    function escHtml(str) {
        var div = document.createElement('div');
        div.appendChild(document.createTextNode(str));
        return div.innerHTML;
    }

    function escAttr(str) {
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;');
    }

    // Bind initial cards
    bindCards();

})();
