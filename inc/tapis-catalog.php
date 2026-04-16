<?php
// Empêcher l'accès direct
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/* =============================================
   Shortcode — [mova_tapis_catalog]
   Catalogue de prévisualisation des tapis AquaCove
   ============================================= */
function mova_tapis_catalog_shortcode( $atts ) {
    $atts = shortcode_atts( array(
        'limit'   => 0,
        'orderby' => 'name',
    ), $atts, 'mova_tapis_catalog' );

    // Récupérer tous les termes de la taxonomie modele_tapis
    $args = array(
        'taxonomy'   => 'modele_tapis',
        'hide_empty' => false,
        'orderby'    => sanitize_text_field( $atts['orderby'] ),
        'order'      => 'ASC',
    );

    $limit = intval( $atts['limit'] );
    if ( $limit > 0 ) {
        $args['number'] = $limit;
    }

    $terms = get_terms( $args );

    if ( is_wp_error( $terms ) || empty( $terms ) ) {
        return '';
    }

    // Construire les données de chaque tapis
    $tapis_list = array();

    foreach ( $terms as $term ) {
        $swatch_id  = get_field( 'swatch_tapis', 'modele_tapis_' . $term->term_id );
        $preview_id = get_field( 'image_preview_tapis', 'modele_tapis_' . $term->term_id );

        $swatch_url  = $swatch_id ? wp_get_attachment_image_url( $swatch_id, 'thumbnail' ) : '';
        $preview_url = $preview_id ? wp_get_attachment_image_url( $preview_id, 'large' ) : '';

        // On inclut le tapis même sans preview (affiché avec placeholder)
        $tapis_list[] = array(
            'term_id'     => $term->term_id,
            'name'        => html_entity_decode( $term->name ),
            'slug'        => $term->slug,
            'description' => $term->description ?: '',
            'swatch'      => $swatch_url,
            'preview'     => $preview_url,
        );
    }

    if ( empty( $tapis_list ) ) {
        return '';
    }

    // Assets
    wp_enqueue_style(
        'mova-tapis-catalog-style',
        get_stylesheet_directory_uri() . '/assets/css/tapis-catalog.css',
        array(),
        '1.0.0'
    );
    wp_enqueue_script(
        'mova-tapis-catalog-script',
        get_stylesheet_directory_uri() . '/assets/js/tapis-catalog.js',
        array(),
        '1.0.0',
        true
    );

    wp_localize_script( 'mova-tapis-catalog-script', 'movaTapisCatalog', array(
        'tapis' => $tapis_list,
    ) );

    ob_start(); ?>

    <div class="mova-tc" id="mova-tc">

        <!-- Grille des tapis -->
        <div class="mova-tc-grid" id="mova-tc-grid">
            <?php foreach ( $tapis_list as $index => $tapis ) : ?>
            <button class="mova-tc-card"
                    data-index="<?php echo intval( $index ); ?>"
                    title="<?php echo esc_attr( $tapis['name'] ); ?>"
                    aria-label="<?php echo esc_attr( $tapis['name'] ); ?>">
                <?php if ( $tapis['swatch'] ) : ?>
                    <img src="<?php echo esc_url( $tapis['swatch'] ); ?>"
                         alt="<?php echo esc_attr( $tapis['name'] ); ?>"
                         class="mova-tc-card-swatch" loading="lazy" />
                <?php else : ?>
                    <span class="mova-tc-card-placeholder">
                        <?php echo esc_html( mb_strtoupper( mb_substr( $tapis['name'], 0, 2 ) ) ); ?>
                    </span>
                <?php endif; ?>
                <span class="mova-tc-card-name"><?php echo esc_html( $tapis['name'] ); ?></span>
            </button>
            <?php endforeach; ?>
        </div>

        <!-- Zone preview (masquée tant qu'aucun tapis sélectionné) -->
        <div class="mova-tc-detail" id="mova-tc-detail" style="display:none;">

            <div class="mova-tc-detail-inner">

                <!-- Preview -->
                <div class="mova-tc-preview">
                    <div class="mova-tc-preview-wrap">
                        <img src="" alt=""
                             id="mova-tc-preview-img"
                             class="mova-tc-preview-img" />
                    </div>
                </div>

                <!-- Panneau info -->
                <div class="mova-tc-panel">
                    <h3 class="mova-tc-tapis-name" id="mova-tc-tapis-name"></h3>
                    <p class="mova-tc-tapis-desc" id="mova-tc-tapis-desc"></p>

                    <div class="mova-tc-swatch-large" id="mova-tc-swatch-large">
                        <h4 class="mova-tc-section-title">Texture</h4>
                        <img src="" alt="" id="mova-tc-swatch-img" class="mova-tc-swatch-img" />
                    </div>

                    <div class="mova-tc-no-preview" id="mova-tc-no-preview" style="display:none;">
                        <p>Aucune image de démonstration disponible pour ce tapis.</p>
                    </div>
                </div>

            </div>

        </div>

    </div>

    <?php
    return ob_get_clean();
}
add_shortcode( 'mova_tapis_catalog', 'mova_tapis_catalog_shortcode' );
