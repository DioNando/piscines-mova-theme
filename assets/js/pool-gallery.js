(function () {
    'use strict';

    document.querySelectorAll('.mova-pg').forEach(initGallery);

    function initGallery(container) {
        var slides  = container.querySelectorAll('.mova-pg-slide');
        var thumbs  = container.querySelectorAll('.mova-pg-thumb');
        var prevBtn = container.querySelector('.mova-pg-arrow-prev');
        var nextBtn = container.querySelector('.mova-pg-arrow-next');
        var total   = slides.length;

        if (total < 1) return;

        var current = 0;

        function goTo(index) {
            // Pause toute vidéo active
            var activeVideo = slides[current].querySelector('video');
            if (activeVideo) activeVideo.pause();

            // Désactiver courant
            slides[current].classList.remove('active');
            if (thumbs[current]) thumbs[current].classList.remove('active');

            // Activer nouveau
            current = (index + total) % total;
            slides[current].classList.add('active');
            if (thumbs[current]) {
                thumbs[current].classList.add('active');
                scrollThumbIntoView(thumbs[current]);
            }
        }

        function scrollThumbIntoView(thumb) {
            var track = container.querySelector('.mova-pg-thumbs-track');
            if (!track) return;

            var trackRect = track.getBoundingClientRect();
            var thumbRect = thumb.getBoundingClientRect();

            if (thumbRect.left < trackRect.left) {
                track.scrollLeft -= trackRect.left - thumbRect.left + 20;
            } else if (thumbRect.right > trackRect.right) {
                track.scrollLeft += thumbRect.right - trackRect.right + 20;
            }
        }

        // Flèches
        if (prevBtn) {
            prevBtn.addEventListener('click', function () {
                goTo(current - 1);
            });
        }
        if (nextBtn) {
            nextBtn.addEventListener('click', function () {
                goTo(current + 1);
            });
        }

        // Vignettes
        thumbs.forEach(function (thumb) {
            thumb.addEventListener('click', function () {
                goTo(parseInt(thumb.dataset.index, 10));
            });
        });

        // Clavier quand le carrousel a le focus
        container.setAttribute('tabindex', '0');
        container.addEventListener('keydown', function (e) {
            if (e.key === 'ArrowLeft')  { e.preventDefault(); goTo(current - 1); }
            if (e.key === 'ArrowRight') { e.preventDefault(); goTo(current + 1); }
        });

        // Swipe tactile
        var touchStartX = 0;
        var touchEndX   = 0;
        var stage = container.querySelector('.mova-pg-stage');

        stage.addEventListener('touchstart', function (e) {
            touchStartX = e.changedTouches[0].screenX;
        }, { passive: true });

        stage.addEventListener('touchend', function (e) {
            touchEndX = e.changedTouches[0].screenX;
            var diff = touchStartX - touchEndX;
            if (Math.abs(diff) > 40) {
                if (diff > 0) goTo(current + 1);
                else goTo(current - 1);
            }
        }, { passive: true });

        /* ========================
           Lightbox
           ======================== */
        var lightbox   = document.getElementById(container.id + '-lightbox');
        var lbImg      = document.getElementById(container.id + '-lb-img');
        var lbVideo    = document.getElementById(container.id + '-lb-video');
        var lbCounter  = document.getElementById(container.id + '-lb-counter');
        var lbPrev     = lightbox ? lightbox.querySelector('.mova-pg-lb-prev') : null;
        var lbNext     = lightbox ? lightbox.querySelector('.mova-pg-lb-next') : null;
        var lbClose    = lightbox ? lightbox.querySelector('.mova-pg-lb-close') : null;
        var lbOpen     = false;

        function openLightbox() {
            if (!lightbox) return;
            lbOpen = true;
            showLbSlide(current);
            lightbox.classList.add('open');
            document.body.style.overflow = 'hidden';
        }

        function closeLightbox() {
            if (!lightbox) return;
            lbOpen = false;
            lightbox.classList.remove('open');
            document.body.style.overflow = '';
            if (lbVideo) { lbVideo.pause(); lbVideo.style.display = 'none'; }
        }

        function showLbSlide(index) {
            var slide = slides[index];
            if (!slide) return;

            var video = slide.querySelector('video');
            var img   = slide.querySelector('img');

            if (video) {
                lbImg.style.display   = 'none';
                lbVideo.style.display = 'block';
                lbVideo.src           = video.querySelector('source').src;
                lbVideo.poster        = video.poster || '';
            } else if (img) {
                lbVideo.style.display = 'none';
                lbVideo.pause();
                lbImg.style.display   = 'block';
                lbImg.src             = img.src;
                lbImg.alt             = img.alt;
            }

            if (lbCounter) {
                lbCounter.textContent = (index + 1) + ' / ' + total;
            }
        }

        // Bouton zoom
        var zoomBtn = document.createElement('button');
        zoomBtn.className = 'mova-pg-zoom';
        zoomBtn.setAttribute('aria-label', 'Agrandir');
        zoomBtn.innerHTML = '<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/><line x1="11" y1="8" x2="11" y2="14"/><line x1="8" y1="11" x2="14" y2="11"/></svg>';
        container.querySelector('.mova-pg-viewer').appendChild(zoomBtn);

        zoomBtn.addEventListener('click', function () {
            openLightbox();
        });

        if (lbClose) lbClose.addEventListener('click', closeLightbox);

        if (lbPrev) {
            lbPrev.addEventListener('click', function () {
                if (lbVideo) lbVideo.pause();
                goTo(current - 1);
                showLbSlide(current);
            });
        }

        if (lbNext) {
            lbNext.addEventListener('click', function () {
                if (lbVideo) lbVideo.pause();
                goTo(current + 1);
                showLbSlide(current);
            });
        }

        // Fermer via fond
        if (lightbox) {
            lightbox.addEventListener('click', function (e) {
                if (e.target === lightbox) closeLightbox();
            });
        }

        // Clavier lightbox
        document.addEventListener('keydown', function (e) {
            if (!lbOpen) return;
            if (e.key === 'Escape') closeLightbox();
            if (e.key === 'ArrowLeft' && lbPrev)  lbPrev.click();
            if (e.key === 'ArrowRight' && lbNext) lbNext.click();
        });
    }

})();
