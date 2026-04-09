<?php
// Empêcher l'accès direct
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/* =============================================
   Shortcode — [mova_nearby_dealers]
   Carrousel de détaillants à proximité
   ============================================= */
function mova_nearby_dealers_shortcode( $atts ) {
    $atts = shortcode_atts( array(
        'id'    => 0,
        'limit' => 6,
    ), $atts, 'mova_nearby_dealers' );

    // Post courant ou ID forcé
    $post_id = intval( $atts['id'] ) ?: get_the_ID();
    $limit   = intval( $atts['limit'] );

    if ( ! $post_id || get_post_type( $post_id ) !== 'detaillant' ) {
        return '';
    }

    $current_lat = (float) get_field( 'latitude', $post_id );
    $current_lng = (float) get_field( 'longitude', $post_id );

    if ( ! $current_lat || ! $current_lng ) {
        return '';
    }

    // Récupérer tous les détaillants sauf le courant
    $query = new WP_Query( array(
        'post_type'      => 'detaillant',
        'posts_per_page' => -1,
        'post_status'    => 'publish',
        'post__not_in'   => array( $post_id ),
    ) );

    $dealers = array();

    if ( $query->have_posts() ) {
        while ( $query->have_posts() ) {
            $query->the_post();
            $lat = (float) get_field( 'latitude' );
            $lng = (float) get_field( 'longitude' );

            if ( ! $lat || ! $lng ) continue;

            // Calcul distance Haversine
            $R = 6371;
            $dLat = deg2rad( $lat - $current_lat );
            $dLng = deg2rad( $lng - $current_lng );
            $a = sin( $dLat / 2 ) ** 2 + cos( deg2rad( $current_lat ) ) * cos( deg2rad( $lat ) ) * sin( $dLng / 2 ) ** 2;
            $distance = $R * 2 * atan2( sqrt( $a ), sqrt( 1 - $a ) );

            $province_terms = wp_get_post_terms( get_the_ID(), 'province' );
            $province = ( ! is_wp_error( $province_terms ) && ! empty( $province_terms ) ) ? $province_terms[0]->name : '';

            $dealers[] = array(
                'id'        => get_the_ID(),
                'nom'       => html_entity_decode( get_the_title() ),
                'ville'     => get_field( 'ville' ) ?: '',
                'province'  => $province,
                'tel'       => get_field( 'telephone' ) ?: '',
                'permalink' => get_permalink(),
                'distance'  => $distance,
                'lat'       => $lat,
                'lng'       => $lng,
            );
        }
        wp_reset_postdata();
    }

    if ( empty( $dealers ) ) {
        return '';
    }

    // Trier par distance et limiter
    usort( $dealers, function( $a, $b ) {
        return $a['distance'] <=> $b['distance'];
    } );
    $dealers = array_slice( $dealers, 0, $limit );

    // Assets
    wp_enqueue_style( 'mova-nearby-dealers-style', get_stylesheet_directory_uri() . '/assets/css/nearby-dealers.css', array(), '1.0.0' );
    wp_enqueue_script( 'mova-nearby-dealers-script', get_stylesheet_directory_uri() . '/assets/js/nearby-dealers.js', array(), '1.0.0', true );

    ob_start(); ?>

    <div class="mova-nd-container">
        <h3 class="mova-nd-title">Détaillants à proximité</h3>

        <div class="mova-nd-carousel-wrap">
            <button class="mova-nd-arrow mova-nd-arrow-prev" aria-label="Précédent">
                <svg width="20" height="20" viewBox="0 0 16 16" fill="none"><path d="M10 3L5 8l5 5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
            </button>

            <div class="mova-nd-carousel" id="mova-nd-carousel">
                <?php foreach ( $dealers as $dealer ) : ?>
                <a href="<?php echo esc_url( $dealer['permalink'] ); ?>" class="mova-nd-card">
                    <div class="mova-nd-card-icon">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none"><path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5A2.5 2.5 0 1112 6.5a2.5 2.5 0 010 5z" fill="#1a4759"/></svg>
                    </div>
                    <h4 class="mova-nd-card-name"><?php echo esc_html( $dealer['nom'] ); ?></h4>
                    <p class="mova-nd-card-location"><?php echo esc_html( $dealer['ville'] ); ?><?php if ( $dealer['province'] ) echo ', ' . esc_html( $dealer['province'] ); ?></p>
                    <?php if ( $dealer['tel'] ) : ?>
                        <p class="mova-nd-card-tel"><?php echo esc_html( $dealer['tel'] ); ?></p>
                    <?php endif; ?>
                    <span class="mova-nd-card-distance"><?php echo number_format( $dealer['distance'], 1 ); ?> km</span>
                    <span class="mova-nd-card-cta">
                        Voir la fiche
                    </span>
                </a>
                <?php endforeach; ?>
            </div>

            <button class="mova-nd-arrow mova-nd-arrow-next" aria-label="Suivant">
                <svg width="20" height="20" viewBox="0 0 16 16" fill="none"><path d="M6 3l5 5-5 5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
            </button>
        </div>
    </div>

    <?php
    return ob_get_clean();
}
add_shortcode( 'mova_nearby_dealers', 'mova_nearby_dealers_shortcode' );
