<?php
// Empêcher l'accès direct
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/* =============================================
   Shortcode — [mova_pool_color_catalog]
   Catalogue de prévisualisation des couleurs
   pour toutes les piscines (page générale)
   ============================================= */
function mova_pool_color_catalog_shortcode( $atts ) {
    $atts = shortcode_atts( array(
        'limit' => -1,
    ), $atts, 'mova_pool_color_catalog' );

    // Récupérer tous les modèles de piscines publiés
    $piscines = get_posts( array(
        'post_type'      => 'piscine',
        'posts_per_page' => intval( $atts['limit'] ),
        'post_status'    => 'publish',
        'orderby'        => 'title',
        'order'          => 'ASC',
    ) );

    if ( empty( $piscines ) ) {
        return '';
    }

    // Construire les données de chaque modèle
    $models = array();

    foreach ( $piscines as $piscine ) {
        $pid = $piscine->ID;

        // Image par défaut : première image de la galerie ou thumbnail
        $default_image = '';
        $galerie = get_field( 'galerie', $pid );
        if ( ! empty( $galerie ) && is_array( $galerie ) ) {
            $first_id = is_array( $galerie[0] ) ? ( $galerie[0]['ID'] ?? $galerie[0]['id'] ?? 0 ) : intval( $galerie[0] );
            if ( $first_id ) {
                $default_image = wp_get_attachment_image_url( $first_id, 'large' );
            }
        }
        if ( ! $default_image ) {
            $default_image = get_the_post_thumbnail_url( $pid, 'large' );
        }

        // Thumbnail pour la carte du sélecteur
        $thumb = '';
        if ( ! empty( $galerie ) && is_array( $galerie ) ) {
            $first_id = is_array( $galerie[0] ) ? ( $galerie[0]['ID'] ?? $galerie[0]['id'] ?? 0 ) : intval( $galerie[0] );
            if ( $first_id ) {
                $thumb = wp_get_attachment_image_url( $first_id, 'medium' );
            }
        }
        if ( ! $thumb ) {
            $thumb = get_the_post_thumbnail_url( $pid, 'medium' );
        }

        // Couleurs disponibles
        $couleurs_raw = get_field( 'couleurs_disponibles', $pid );
        $couleurs = array();

        if ( ! empty( $couleurs_raw ) && is_array( $couleurs_raw ) ) {
            foreach ( $couleurs_raw as $term ) {
                $term_id  = is_object( $term ) ? $term->term_id : intval( $term );
                $term_obj = is_object( $term ) ? $term : get_term( $term_id, 'couleur_piscine' );

                if ( ! $term_obj || is_wp_error( $term_obj ) ) continue;

                $swatch_id   = get_field( 'swatch_couleur', 'couleur_piscine_' . $term_obj->term_id );
                $ambiance_id = get_field( 'image_ambiance', 'couleur_piscine_' . $term_obj->term_id );

                $couleurs[] = array(
                    'term_id'  => $term_obj->term_id,
                    'name'     => $term_obj->name,
                    'slug'     => $term_obj->slug,
                    'swatch'   => $swatch_id ? wp_get_attachment_image_url( $swatch_id, 'thumbnail' ) : '',
                    'ambiance' => $ambiance_id ? wp_get_attachment_image_url( $ambiance_id, 'large' ) : '',
                );
            }
        }

        // On inclut le modèle même sans couleurs (il sera affiché mais sans swatches)
        $models[] = array(
            'id'           => $pid,
            'title'        => html_entity_decode( get_the_title( $pid ) ),
            'slug'         => get_post_field( 'post_name', $pid ),
            'thumb'        => $thumb ?: '',
            'defaultImage' => $default_image ?: '',
            'permalink'    => get_permalink( $pid ),
            'couleurs'     => $couleurs,
        );
    }

    if ( empty( $models ) ) {
        return '';
    }

    // Assets
    wp_enqueue_style( 'mova-pool-color-catalog-style', get_stylesheet_directory_uri() . '/assets/css/pool-color-catalog.css', array(), '1.0.0' );
    wp_enqueue_script( 'mova-pool-color-catalog-script', get_stylesheet_directory_uri() . '/assets/js/pool-color-catalog.js', array(), '1.0.0', true );

    wp_localize_script( 'mova-pool-color-catalog-script', 'movaColorCatalog', array(
        'models'   => $models,
        'devisUrl' => home_url( '/demande-de-devis/' ),
    ) );

    ob_start(); ?>

    <div class="mova-ccc" id="mova-ccc">
        <div class="mova-ccc-layout">

            <!-- Colonne 1 : cartes modèles -->
            <div class="mova-ccc-col-models">
                <h3 class="mova-ccc-models-title">Choisissez un modèle</h3>
                <div class="mova-ccc-models-grid" id="mova-ccc-models-grid">
                    <?php foreach ( $models as $index => $model ) : ?>
                    <button class="mova-ccc-model-card"
                            data-index="<?php echo intval( $index ); ?>"
                            title="<?php echo esc_attr( $model['title'] ); ?>"
                            aria-label="<?php echo esc_attr( $model['title'] ); ?>">
                        <?php if ( $model['thumb'] ) : ?>
                            <img src="<?php echo esc_url( $model['thumb'] ); ?>"
                                 alt="<?php echo esc_attr( $model['title'] ); ?>"
                                 class="mova-ccc-model-thumb" loading="lazy" />
                        <?php else : ?>
                            <span class="mova-ccc-model-placeholder"></span>
                        <?php endif; ?>
                        <span class="mova-ccc-model-name"><?php echo esc_html( $model['title'] ); ?></span>
                    </button>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Colonne 2 : preview + couleurs -->
            <div class="mova-ccc-col-preview">

                <div class="mova-ccc-panel">
                    <h3 class="mova-ccc-model-title" id="mova-ccc-model-title"></h3>

                    <div class="mova-ccc-section" id="mova-ccc-colors-section">
                        <h4 class="mova-ccc-section-title">Couleurs disponibles</h4>
                        <p class="mova-ccc-section-subtitle">Sélectionnez une couleur de piscine</p>
                        <div class="mova-ccc-swatches" id="mova-ccc-swatches"></div>
                        <p class="mova-ccc-color-name" id="mova-ccc-color-name"></p>
                    </div>

                    <div class="mova-ccc-no-colors" id="mova-ccc-no-colors" style="display:none;">
                        <p>Aucune couleur disponible pour ce modèle.</p>
                    </div>

                    <a href="#" class="mova-ccc-link" id="mova-ccc-link-detail" target="_blank" rel="noopener">
                        Voir la fiche complète
                    </a>
                </div>

                <div class="mova-ccc-preview-wrap">
                    <img src="" alt=""
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
