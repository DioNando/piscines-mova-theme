<?php
// Empêcher l'accès direct
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/* =============================================
   Shortcode — [mova_pool_color_catalog]
   Catalogue de toutes les couleurs de la
   taxonomie couleur_piscine
   ============================================= */
function mova_pool_color_catalog_shortcode( $atts ) {
    $atts = shortcode_atts( array(), $atts, 'mova_pool_color_catalog' );

    // Helper : construire le tableau d'une couleur à partir d'un terme
    $build_couleur = function ( $term ) {
        $swatch_id   = get_field( 'swatch_couleur', 'couleur_piscine_' . $term->term_id );
        $ambiance_id = get_field( 'image_ambiance', 'couleur_piscine_' . $term->term_id );
        $ordre       = get_field( 'ordre', 'couleur_piscine_' . $term->term_id );

        return array(
            'term_id'  => $term->term_id,
            'name'     => $term->name,
            'slug'     => $term->slug,
            'ordre'    => ( $ordre !== '' && $ordre !== null && $ordre !== false ) ? (int) $ordre : PHP_INT_MAX,
            'swatch'   => $swatch_id ? wp_get_attachment_image_url( $swatch_id, 'thumbnail' ) : '',
            'ambiance' => $ambiance_id ? wp_get_attachment_image_url( $ambiance_id, 'large' ) : '',
        );
    };

    $sort_by_ordre = function ( &$arr ) {
        usort( $arr, function ( $a, $b ) {
            if ( $a['ordre'] !== $b['ordre'] ) {
                return $a['ordre'] - $b['ordre'];
            }
            return strcmp( $a['name'], $b['name'] );
        } );
    };

    // Récupérer les collections (termes parents)
    $parents = get_terms( array(
        'taxonomy'   => 'couleur_piscine',
        'hide_empty' => false,
        'parent'     => 0,
    ) );

    if ( empty( $parents ) || is_wp_error( $parents ) ) {
        return '';
    }

    // Construire les collections avec leurs couleurs enfants
    $collections = array();
    foreach ( $parents as $parent ) {
        $ordre_parent   = get_field( 'ordre', 'couleur_piscine_' . $parent->term_id );
        $ordre_parent   = ( $ordre_parent !== '' && $ordre_parent !== null && $ordre_parent !== false ) ? (int) $ordre_parent : PHP_INT_MAX;
        $children_terms = get_terms( array(
            'taxonomy'   => 'couleur_piscine',
            'hide_empty' => false,
            'parent'     => $parent->term_id,
        ) );

        $enfants = array();
        if ( ! empty( $children_terms ) && ! is_wp_error( $children_terms ) ) {
            foreach ( $children_terms as $child ) {
                $enfants[] = $build_couleur( $child );
            }
            $sort_by_ordre( $enfants );
        }

        $collections[] = array(
            'term_id' => $parent->term_id,
            'name'    => $parent->name,
            'ordre'   => $ordre_parent,
            'enfants' => $enfants,
        );
    }

    usort( $collections, function ( $a, $b ) {
        if ( $a['ordre'] !== $b['ordre'] ) {
            return $a['ordre'] - $b['ordre'];
        }
        return strcmp( $a['name'], $b['name'] );
    } );

    // Liste à plat pour le JS
    $couleurs_js = array();
    foreach ( $collections as $col ) {
        foreach ( $col['enfants'] as $c ) {
            $couleurs_js[] = array(
                'term_id'  => $c['term_id'],
                'name'     => $c['name'],
                'slug'     => $c['slug'],
                'swatch'   => $c['swatch'],
                'ambiance' => $c['ambiance'],
            );
        }
    }

    if ( empty( $couleurs_js ) ) {
        return '';
    }

    // Assets
    wp_enqueue_style( 'mova-pool-color-catalog-style', get_stylesheet_directory_uri() . '/assets/css/pool-color-catalog.css', array(), '1.0.2' );
    wp_enqueue_script( 'mova-pool-color-catalog-script', get_stylesheet_directory_uri() . '/assets/js/pool-color-catalog.js', array(), '1.0.2', true );

    wp_localize_script( 'mova-pool-color-catalog-script', 'movaColorCatalog', array(
        'couleurs' => $couleurs_js,
    ) );

    // Première image d'ambiance pour le pré-chargement initial
    $first_ambiance = '';
    foreach ( $couleurs_js as $c ) {
        if ( ! empty( $c['ambiance'] ) ) {
            $first_ambiance = $c['ambiance'];
            break;
        }
    }

    ob_start(); ?>

    <div class="mova-ccc" id="mova-ccc">
        <div class="mova-ccc-layout">

            <!-- Colonne gauche : swatches groupées par collection -->
            <div class="mova-ccc-col-colors">
                <h3 class="mova-ccc-section-title">Couleurs disponibles</h3>
                <p class="mova-ccc-section-subtitle">Sélectionnez une couleur de piscine</p>
                <div id="mova-ccc-swatches">
                    <?php foreach ( $collections as $collection ) : ?>
                        <?php if ( ! empty( $collection['enfants'] ) ) : ?>
                        <div class="mova-ccc-collection">
                            <h4 class="mova-ccc-collection-title"><?php echo esc_html( $collection['name'] ); ?></h4>
                            <div class="mova-ccc-swatches">
                                <?php foreach ( $collection['enfants'] as $couleur ) : ?>
                                <button class="mova-ccc-swatch"
                                        data-slug="<?php echo esc_attr( $couleur['slug'] ); ?>"
                                        data-ambiance="<?php echo esc_url( $couleur['ambiance'] ); ?>"
                                        title="<?php echo esc_attr( $couleur['name'] ); ?>"
                                        aria-label="<?php echo esc_attr( $couleur['name'] ); ?>">
                                    <?php if ( $couleur['swatch'] ) : ?>
                                        <img src="<?php echo esc_url( $couleur['swatch'] ); ?>"
                                             alt="<?php echo esc_attr( $couleur['name'] ); ?>"
                                             loading="lazy" />
                                    <?php else : ?>
                                        <span class="mova-ccc-swatch-placeholder">
                                            <?php echo esc_html( mb_substr( $couleur['name'], 0, 2 ) ); ?>
                                        </span>
                                    <?php endif; ?>
                                </button>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </div>
                <p class="mova-ccc-color-name" id="mova-ccc-color-name"></p>
                <p class="mova-ccc-disclaimer">Les couleurs, motifs et positions des tapis affichés sur notre site web sont à titre indicatif. Pour une représentation plus fidèle, nous vous recommandons de vous référer aux échantillons physiques.</p>
            </div>

            <!-- Colonne droite : image d'ambiance -->
            <div class="mova-ccc-col-preview">
                <div class="mova-ccc-preview-wrap">
                    <img src="<?php echo esc_url( $first_ambiance ); ?>"
                         alt="Aperçu d'ambiance"
                         id="mova-ccc-preview-img"
                         class="mova-ccc-preview-img" />
                </div>
            </div>

        </div>
    </div>

    <?php
    return ob_get_clean();
}
add_shortcode( 'mova_pool_color_catalog', 'mova_pool_color_catalog_shortcode' );
