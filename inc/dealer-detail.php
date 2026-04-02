<?php
// Empêcher l'accès direct
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/* =============================================
   Shortcode — [mova_dealer_detail]
   Fiche détaillée d'un détaillant avec carte
   ============================================= */
function mova_dealer_detail_shortcode( $atts ) {
    $atts = shortcode_atts( array(
        'id' => 0,
    ), $atts, 'mova_dealer_detail' );

    // Si pas d'ID passé, utiliser le post courant
    $post_id = intval( $atts['id'] ) ?: get_the_ID();

    if ( ! $post_id || get_post_type( $post_id ) !== 'detaillant' ) {
        return '<p>Détaillant introuvable.</p>';
    }

    // Récupérer les champs ACF
    $nom      = html_entity_decode( get_the_title( $post_id ) );
    $adresse  = get_field( 'adresse', $post_id ) ?: '';
    $ville    = get_field( 'ville', $post_id ) ?: '';
    $cp       = get_field( 'code_postal', $post_id ) ?: '';
    $tel      = get_field( 'telephone', $post_id ) ?: '';
    $email    = get_field( 'email_contact', $post_id ) ?: '';
    $site     = get_field( 'site_web_url', $post_id ) ?: '';
    $lat      = get_field( 'latitude', $post_id );
    $lng      = get_field( 'longitude', $post_id );

    // Province
    $province_terms = wp_get_post_terms( $post_id, 'province' );
    $province = ( ! is_wp_error( $province_terms ) && ! empty( $province_terms ) ) ? $province_terms[0]->name : '';

    // Assets
    wp_enqueue_style( 'leaflet-css', 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.css', array(), '1.9.4' );
    wp_enqueue_script( 'leaflet-js', 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js', array(), '1.9.4', true );
    wp_enqueue_style( 'mova-dealer-detail-style', get_stylesheet_directory_uri() . '/assets/css/dealer-detail.css', array(), '1.0.0' );
    wp_enqueue_script( 'mova-dealer-detail-script', get_stylesheet_directory_uri() . '/assets/js/dealer-detail.js', array( 'leaflet-js' ), '1.0.0', true );

    // Passer les coordonnées au JS
    wp_localize_script( 'mova-dealer-detail-script', 'movaDealerData', array(
        'lat'  => (float) $lat,
        'lng'  => (float) $lng,
        'nom'  => $nom,
    ) );

    // Construire l'adresse complète
    $adresse_parts = array_filter( array( $adresse, $ville, $cp ) );
    $adresse_full  = implode( ', ', $adresse_parts );
    $province_line = $province ? $province : '';

    ob_start(); ?>

    <div class="mova-dd-container">
        <div class="mova-dd-info">
            <h2 class="mova-dd-name"><?php echo esc_html( $nom ); ?></h2>

            <?php if ( $adresse_full || $province_line ) : ?>
            <div class="mova-dd-section">
                <div class="mova-dd-section-icon">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none"><path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5A2.5 2.5 0 1112 6.5a2.5 2.5 0 010 5z" fill="#1a4759"/></svg>
                </div>
                <div>
                    <?php if ( $adresse ) : ?><p class="mova-dd-text"><?php echo esc_html( $adresse ); ?></p><?php endif; ?>
                    <p class="mova-dd-text"><?php echo esc_html( $ville ); ?><?php if ( $cp ) echo ', ' . esc_html( $cp ); ?></p>
                    <?php if ( $province_line ) : ?><p class="mova-dd-text mova-dd-province"><?php echo esc_html( $province_line ); ?></p><?php endif; ?>
                </div>
            </div>
            <?php endif; ?>

            <?php if ( $tel ) : ?>
            <div class="mova-dd-section">
                <div class="mova-dd-section-icon">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none"><path d="M6.62 10.79a15.05 15.05 0 006.59 6.59l2.2-2.2a1 1 0 011.01-.24c1.12.45 2.33.69 3.58.69a1 1 0 011 1v3.5a1 1 0 01-1 1A17 17 0 013 4a1 1 0 011-1h3.5a1 1 0 011 1c0 1.25.24 2.46.69 3.58a1 1 0 01-.24 1.01l-2.33 2.2z" fill="#1a4759"/></svg>
                </div>
                <a href="tel:<?php echo esc_attr( $tel ); ?>" class="mova-dd-link"><?php echo esc_html( $tel ); ?></a>
            </div>
            <?php endif; ?>

            <?php if ( $email ) : ?>
            <div class="mova-dd-section">
                <div class="mova-dd-section-icon">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none"><path d="M20 4H4a2 2 0 00-2 2v12a2 2 0 002 2h16a2 2 0 002-2V6a2 2 0 00-2-2zm0 4l-8 5-8-5V6l8 5 8-5v2z" fill="#1a4759"/></svg>
                </div>
                <a href="mailto:<?php echo esc_attr( $email ); ?>" class="mova-dd-link"><?php echo esc_html( $email ); ?></a>
            </div>
            <?php endif; ?>

            <?php if ( $site ) : ?>
            <div class="mova-dd-section">
                <div class="mova-dd-section-icon">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-1 17.93c-3.95-.49-7-3.85-7-7.93 0-.62.08-1.21.21-1.79L9 15v1a2 2 0 002 2v1.93zm6.9-2.54A1.99 1.99 0 0016 16h-1v-3a1 1 0 00-1-1H8v-2h2a1 1 0 001-1V7h2a2 2 0 002-2v-.41A7.997 7.997 0 0120 12c0 2.08-.8 3.97-2.1 5.39z" fill="#1a4759"/></svg>
                </div>
                <a href="<?php echo esc_url( $site ); ?>" target="_blank" class="mova-dd-link"><?php echo esc_html( preg_replace( '#^https?://(www\.)?#', '', $site ) ); ?></a>
            </div>
            <?php endif; ?>

            <div class="mova-dd-actions">
                <?php if ( $lat && $lng ) : ?>
                <a href="https://www.google.com/maps/dir/?api=1&destination=<?php echo esc_attr( $lat ); ?>,<?php echo esc_attr( $lng ); ?>" target="_blank" class="mova-dd-btn mova-dd-btn-primary">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none"><path d="M21.71 11.29l-9-9a1 1 0 00-1.42 0l-9 9a1 1 0 000 1.42l9 9a1 1 0 001.42 0l9-9a1 1 0 000-1.42zM14 14.5V12h-4v3H8v-4a1 1 0 011-1h5V7.5l3.5 3.5L14 14.5z" fill="currentColor"/></svg>
                    Y aller
                </a>
                <?php endif; ?>
            </div>
        </div>

        <?php if ( $lat && $lng ) : ?>
        <div class="mova-dd-map-wrap">
            <div id="mova-dd-map"></div>
        </div>
        <?php endif; ?>
    </div>

    <?php
    return ob_get_clean();
}
add_shortcode( 'mova_dealer_detail', 'mova_dealer_detail_shortcode' );
