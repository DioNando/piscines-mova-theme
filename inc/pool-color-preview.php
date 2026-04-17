<?php
// Empêcher l'accès direct
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/* =============================================
   Shortcode — [mova_pool_color_preview]
   Prévisualisation des couleurs de piscine
   ============================================= */
function mova_pool_color_preview_shortcode( $atts ) {
    $atts = shortcode_atts( array(
        'id' => 0,
    ), $atts, 'mova_pool_color_preview' );

    $post_id = intval( $atts['id'] ) ?: get_the_ID();

    if ( ! $post_id || get_post_type( $post_id ) !== 'piscine' ) {
        return '';
    }

    // Image par défaut : image_carte ACF, puis première image de la galerie, puis thumbnail
    $default_image = '';
    $image_carte_id = get_field( 'image_carte', $post_id );
    if ( $image_carte_id ) {
        $default_image = wp_get_attachment_image_url( $image_carte_id, 'large' );
    }
    if ( ! $default_image ) {
        $galerie = get_field( 'galerie', $post_id );
        if ( ! empty( $galerie ) && is_array( $galerie ) ) {
            $first_id = is_array( $galerie[0] ) ? ( $galerie[0]['ID'] ?? $galerie[0]['id'] ?? 0 ) : intval( $galerie[0] );
            if ( $first_id ) {
                $default_image = wp_get_attachment_image_url( $first_id, 'large' );
            }
        }
    }
    if ( ! $default_image ) {
        $default_image = get_the_post_thumbnail_url( $post_id, 'large' );
    }

    // Couleurs disponibles pour ce modèle
    $couleurs_raw = get_field( 'couleurs_disponibles', $post_id );
    $couleurs = array();

    if ( ! empty( $couleurs_raw ) && is_array( $couleurs_raw ) ) {
        foreach ( $couleurs_raw as $term ) {
            $term_id = is_object( $term ) ? $term->term_id : intval( $term );
            $term_obj = is_object( $term ) ? $term : get_term( $term_id, 'couleur_piscine' );

            if ( ! $term_obj || is_wp_error( $term_obj ) ) continue;

            $swatch_id   = get_field( 'swatch_couleur', 'couleur_piscine_' . $term_obj->term_id );
            $ambiance_id = get_field( 'image_ambiance', 'couleur_piscine_' . $term_obj->term_id );

            $couleurs[] = array(
                'term_id'   => $term_obj->term_id,
                'name'      => $term_obj->name,
                'slug'      => $term_obj->slug,
                'swatch'    => $swatch_id ? wp_get_attachment_image_url( $swatch_id, 'thumbnail' ) : '',
                'ambiance'  => $ambiance_id ? wp_get_attachment_image_url( $ambiance_id, 'large' ) : '',
            );
        }
    }

    if ( empty( $couleurs ) ) {
        return '';
    }

    // Assets
    wp_enqueue_style( 'mova-pool-color-preview-style', get_stylesheet_directory_uri() . '/assets/css/pool-color-preview.css', array(), '1.0.0' );
    wp_enqueue_script( 'mova-pool-color-preview-script', get_stylesheet_directory_uri() . '/assets/js/pool-color-preview.js', array(), '1.0.0', true );

    wp_localize_script( 'mova-pool-color-preview-script', 'movaColorPreview', array(
        'defaultImage' => $default_image ?: '',
        'modelSlug'    => get_post_field( 'post_name', $post_id ),
        'modelTitle'   => html_entity_decode( get_the_title( $post_id ) ),
        'devisUrl'     => home_url( '/demande-de-devis/' ),
        'couleurs'     => $couleurs,
    ) );

    ob_start(); ?>

    <div class="mova-cpv" id="mova-cpv">

        <!-- Preview -->
        <div class="mova-cpv-preview">
            <div class="mova-cpv-preview-wrap">
                <img src="<?php echo esc_url( $default_image ); ?>"
                     alt="<?php echo esc_attr( get_the_title( $post_id ) ); ?>"
                     id="mova-cpv-preview-img"
                     class="mova-cpv-preview-img" />
            </div>
        </div>

        <!-- Panneau couleurs -->
        <div class="mova-cpv-panel">

            <h3 class="mova-cpv-title">Choisissez votre couleur</h3>

            <!-- Couleurs -->
            <div class="mova-cpv-section">
                <h4 class="mova-cpv-section-title">Couleurs</h4>
                <p class="mova-cpv-section-subtitle">Sélectionnez une couleur de piscine</p>

                <div class="mova-cpv-swatches" id="mova-cpv-couleurs">
                    <?php foreach ( $couleurs as $couleur ) : ?>
                    <button class="mova-cpv-swatch"
                            data-slug="<?php echo esc_attr( $couleur['slug'] ); ?>"
                            data-ambiance="<?php echo esc_url( $couleur['ambiance'] ); ?>"
                            title="<?php echo esc_attr( $couleur['name'] ); ?>"
                            aria-label="<?php echo esc_attr( $couleur['name'] ); ?>">
                        <?php if ( $couleur['swatch'] ) : ?>
                            <img src="<?php echo esc_url( $couleur['swatch'] ); ?>"
                                 alt="<?php echo esc_attr( $couleur['name'] ); ?>" />
                        <?php else : ?>
                            <span class="mova-cpv-swatch-placeholder"><?php echo esc_html( mb_substr( $couleur['name'], 0, 2 ) ); ?></span>
                        <?php endif; ?>
                    </button>
                    <?php endforeach; ?>
                </div>

                <p class="mova-cpv-color-name" id="mova-cpv-color-name"></p>
            </div>

        </div>

    </div>

    <?php
    return ob_get_clean();
}
add_shortcode( 'mova_pool_color_preview', 'mova_pool_color_preview_shortcode' );
