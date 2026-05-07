/**
 * dealer-space-filter.js
 * Filtre les sections Elementor par leur CSS ID
 *
 * Chaque section Elementor ciblée doit avoir un CSS ID défini dans
 * Avancé → CSS ID de la section dans l'éditeur Elementor.
 * Ce CSS ID devient l'attribut `id` sur le wrapper de la section.
 */

/**
 * Initialise le filtre pour un select donné.
 *
 * @param {string}   wrapperId  - ID du wrapper du shortcode
 * @param {string[]} sectionIds - Tableau des IDs CSS de toutes les sections ciblées
 */
function movaInitSectionFilter( wrapperId, sectionIds ) {
    var wrapper = document.getElementById( wrapperId );
    if ( ! wrapper ) return;

    var select = wrapper.querySelector( '.mova-sf-select' );
    if ( ! select ) return;

    /**
     * Récupère l'élément section le plus proche qui wrap un ID Elementor.
     * Elementor génère : <section id="section-id" class="elementor-section ...">
     * ou <div id="section-id" class="e-container ..."> selon la version.
     */
    function getSectionElement( id ) {
        return document.getElementById( id );
    }

    function filterSections( selectedValue ) {
        sectionIds.forEach( function( id ) {
            var el = getSectionElement( id );
            if ( ! el ) return;

            if ( selectedValue === 'all' || selectedValue === '' ) {
                el.classList.remove( 'mova-sf-hidden' );
            } else if ( id === selectedValue ) {
                el.classList.remove( 'mova-sf-hidden' );
            } else {
                el.classList.add( 'mova-sf-hidden' );
            }
        } );
    }

    // Appliquer le filtre initial selon la valeur par défaut du select
    filterSections( select.value );

    // Écouter les changements
    select.addEventListener( 'change', function () {
        filterSections( this.value );

        // Scroll vers la première section visible si on filtre
        if ( this.value !== 'all' && this.value !== '' ) {
            var target = getSectionElement( this.value );
            if ( target ) {
                var offset = 120; // hauteur menu sticky approximative
                var top = target.getBoundingClientRect().top + window.pageYOffset - offset;
                window.scrollTo( { top: top, behavior: 'smooth' } );
            }
        }
    } );
}
