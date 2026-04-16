<?php
// Empêcher l'accès direct
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/* =============================================
   Shortcode — [mova_similar_pools]
   Grille des modèles de piscines similaires
   Utilisation : [mova_similar_pools id="0"]
   ============================================= */
function mova_similar_pools_shortcode( $atts ) {
    $atts = shortcode_atts( array(
        'id' => 0,
    ), $atts, 'mova_similar_pools' );

    // get_queried_object_id() retourne le bon post dans un template Elementor
    // get_the_ID() est utilisé en fallback (contenu classique WordPress)
    $post_id = intval( $atts['id'] ) ?: get_queried_object_id() ?: get_the_ID();

    if ( ! $post_id ) {
        return '';
    }

    $similar_pools = get_field( 'modeles_similaires', $post_id );

    if ( empty( $similar_pools ) || ! is_array( $similar_pools ) ) {
        return '';
    }

    wp_enqueue_style( 'mova-pool-catalog-style', get_stylesheet_directory_uri() . '/assets/css/pool-catalog.css', array(), '1.1.0' );
    wp_enqueue_style( 'mova-similar-pools-style', get_stylesheet_directory_uri() . '/assets/css/similar-pools.css', array( 'mova-pool-catalog-style' ), '1.0.0' );

    ob_start(); ?>

    <section class="mova-sp">
        <div class="mova-sp-grid">
            <?php foreach ( $similar_pools as $pool ) :
                // ACF peut retourner un WP_Post ou un ID entier selon la config
                $pool_id = $pool instanceof WP_Post ? $pool->ID : intval( $pool );
                if ( ! $pool_id ) continue;

                $permalink  = get_permalink( $pool_id );
                $title      = get_the_title( $pool_id );
                $thumbnail  = get_the_post_thumbnail_url( $pool_id, 'medium' );
                $dimensions = get_field( 'dimensions', $pool_id );
            ?>
            <a class="mova-pc-card" href="<?php echo esc_url( $permalink ); ?>">
                <div class="mova-pc-card-img">
                    <?php if ( $thumbnail ) : ?>
                        <img src="<?php echo esc_url( $thumbnail ); ?>" alt="<?php echo esc_attr( $title ); ?>" loading="lazy" />
                    <?php else : ?>
                        <div class="mova-pc-card-placeholder"></div>
                    <?php endif; ?>
                </div>
                <div class="mova-pc-card-body">
                    <div class="mova-pc-card-info">
                        <p class="mova-pc-card-title"><?php echo esc_html( $title ); ?></p>
                        <?php if ( $dimensions ) : ?>
                            <span class="mova-pc-card-subtitle"><?php echo esc_html( $dimensions ); ?></span>
                        <?php endif; ?>
                    </div>
                    <div class="mova-pc-card-arrow">
                        <svg width="20" height="20" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M6 3l5 5-5 5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </div>
                </div>
            </a>
            <?php endforeach; ?>
        </div>
    </section>

    <?php
    return ob_get_clean();
}
add_shortcode( 'mova_similar_pools', 'mova_similar_pools_shortcode' );
