<?php
// Empêcher l'accès direct
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/* =============================================
   Shortcode — [mova_pool_configurator]
   Configurateur visuel de piscine
   ============================================= */
function mova_pool_configurator_shortcode( $atts ) {
    $atts = shortcode_atts( array(
        'id' => 0,
    ), $atts, 'mova_pool_configurator' );

    $post_id = intval( $atts['id'] ) ?: get_the_ID();

    if ( ! $post_id || get_post_type( $post_id ) !== 'piscine' ) {
        return '';
    }

    // Image par défaut : première image de la galerie ou thumbnail
    $default_image = '';
    $galerie = get_field( 'galerie', $post_id );
    if ( ! empty( $galerie ) && is_array( $galerie ) ) {
        $first_id = is_array( $galerie[0] ) ? ( $galerie[0]['ID'] ?? $galerie[0]['id'] ?? 0 ) : intval( $galerie[0] );
        if ( $first_id ) {
            $default_image = wp_get_attachment_image_url( $first_id, 'large' );
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
    wp_enqueue_style( 'mova-pool-configurator-style', get_stylesheet_directory_uri() . '/assets/css/pool-configurator.css', array(), '1.0.0' );
    wp_enqueue_script( 'mova-pool-configurator-script', get_stylesheet_directory_uri() . '/assets/js/pool-configurator.js', array(), '1.0.0', true );

    wp_localize_script( 'mova-pool-configurator-script', 'movaConfigurator', array(
        'defaultImage' => $default_image ?: '',
        'modelSlug'    => get_post_field( 'post_name', $post_id ),
        'modelTitle'   => html_entity_decode( get_the_title( $post_id ) ),
        'devisUrl'     => home_url( '/demande-de-devis/' ),
        'couleurs'     => $couleurs,
    ) );

    ob_start(); ?>

    <div class="mova-cfg" id="mova-cfg">

        <!-- Preview -->
        <div class="mova-cfg-preview">
            <div class="mova-cfg-preview-wrap">
                <img src="<?php echo esc_url( $default_image ); ?>"
                     alt="<?php echo esc_attr( get_the_title( $post_id ) ); ?>"
                     id="mova-cfg-preview-img"
                     class="mova-cfg-preview-img" />
            </div>
        </div>

        <!-- Panneau de configuration -->
        <div class="mova-cfg-panel">

            <h3 class="mova-cfg-title">Personnalisez votre piscine</h3>

            <!-- Couleurs -->
            <div class="mova-cfg-section">
                <h4 class="mova-cfg-section-title">Couleurs</h4>
                <p class="mova-cfg-section-subtitle">Sélectionnez une couleur de piscine</p>

                <div class="mova-cfg-swatches" id="mova-cfg-couleurs">
                    <?php foreach ( $couleurs as $couleur ) : ?>
                    <button class="mova-cfg-swatch"
                            data-slug="<?php echo esc_attr( $couleur['slug'] ); ?>"
                            data-ambiance="<?php echo esc_url( $couleur['ambiance'] ); ?>"
                            title="<?php echo esc_attr( $couleur['name'] ); ?>"
                            aria-label="<?php echo esc_attr( $couleur['name'] ); ?>">
                        <?php if ( $couleur['swatch'] ) : ?>
                            <img src="<?php echo esc_url( $couleur['swatch'] ); ?>"
                                 alt="<?php echo esc_attr( $couleur['name'] ); ?>" />
                        <?php else : ?>
                            <span class="mova-cfg-swatch-placeholder"><?php echo esc_html( mb_substr( $couleur['name'], 0, 2 ) ); ?></span>
                        <?php endif; ?>
                    </button>
                    <?php endforeach; ?>
                </div>

                <p class="mova-cfg-color-name" id="mova-cfg-color-name"></p>
            </div>

        </div>

    </div>

    <?php
    return ob_get_clean();
}
add_shortcode( 'mova_pool_configurator', 'mova_pool_configurator_shortcode' );
