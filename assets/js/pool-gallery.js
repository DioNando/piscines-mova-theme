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
    }

})();
