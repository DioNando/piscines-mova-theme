<?php
// Empêcher l'accès direct
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/* =============================================
   Shortcodes — Espace Détaillant (Ressources)
   Lit les données de la page d'options ACF
   "espace-detaillant-options"

   Shortcodes disponibles :
   [mova_dealer_formulaires]
   [mova_dealer_garanties]
   [mova_dealer_manuels]
   [mova_dealer_logos]
   [mova_dealer_images]
   [mova_dealer_videos]
   ============================================= */


/* -----------------------------------------------
   Helper global — Icône téléchargement (SVG inline)
   Basé sur assets/images/download-solid-full.svg
   ----------------------------------------------- */
function mova_dr_download_icon( $size = 14 ) {
    return '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 640" width="' . intval( $size ) . '" height="' . intval( $size ) . '" fill="currentColor" aria-hidden="true" focusable="false"><path d="M352 96C352 78.3 337.7 64 320 64C302.3 64 288 78.3 288 96L288 306.7L246.6 265.3C234.1 252.8 213.8 252.8 201.3 265.3C188.8 277.8 188.8 298.1 201.3 310.6L297.3 406.6C309.8 419.1 330.1 419.1 342.6 406.6L438.6 310.6C451.1 298.1 451.1 277.8 438.6 265.3C426.1 252.8 405.8 252.8 393.3 265.3L352 306.7L352 96zM160 384C124.7 384 96 412.7 96 448L96 480C96 515.3 124.7 544 160 544L480 544C515.3 544 544 515.3 544 480L544 448C544 412.7 515.3 384 480 384L433.1 384L376.5 440.6C345.3 471.8 294.6 471.8 263.4 440.6L206.9 384L160 384zM464 440C477.3 440 488 450.7 488 464C488 477.3 477.3 488 464 488C450.7 488 440 477.3 440 464C440 450.7 450.7 440 464 440z"/></svg>';
}

/* -----------------------------------------------
   Helper global — Rendu liste de fichiers
   $items : tableau ACF repeater (label + fichier)
   $download : forcer l'attribut download (ZIP)
   ----------------------------------------------- */
function mova_dr_render_file_list( $items, $download = false ) {
    if ( empty( $items ) ) {
        return '<p class="mova-dr-empty">' . esc_html__( 'Aucun fichier disponible.', 'piscines-mova' ) . '</p>';
    }

    $html = '<ul class="mova-dr-list">';
    foreach ( $items as $item ) {
        $url   = ! empty( $item['fichier'] ) ? esc_url( $item['fichier'] ) : '';
        $label = ! empty( $item['label'] )   ? esc_html( $item['label'] )  : ( $url ? esc_html( basename( $url ) ) : '' );

        if ( ! $url || ! $label ) continue;

        $dl_attr = $download ? ' download' : ' target="_blank" rel="noopener"';
        $html   .= '<li class="mova-dr-item">';
        $html   .= '<a href="' . $url . '" class="mova-dr-link"' . $dl_attr . '>';
        $html   .= mova_dr_download_icon();
        $html   .= '<span>' . $label . '</span>';
        $html   .= '</a>';
        $html   .= '</li>';
    }
    $html .= '</ul>';

    return $html;
}

/* -----------------------------------------------
   Helper global — Rendu grille de cartes
   $items : repeater ACF avec label + fichier
   $download : forcer attribut download (ZIP)
   ----------------------------------------------- */
function mova_dr_render_card_grid( $items, $download = false ) {
    if ( empty( $items ) ) {
        return '<p class="mova-dr-empty">' . esc_html__( 'Aucun fichier disponible.', 'piscines-mova' ) . '</p>';
    }

    $html = '<div class="mova-dr-grid">';
    foreach ( $items as $item ) {
        $url   = ! empty( $item['fichier'] ) ? esc_url( $item['fichier'] ) : '';
        $label = ! empty( $item['label'] )   ? esc_html( $item['label'] )  : ( $url ? esc_html( basename( $url ) ) : '' );

        if ( ! $url || ! $label ) continue;

        $dl_attr = $download ? ' download' : ' target="_blank" rel="noopener"';
        $html   .= '<a href="' . $url . '" class="mova-dr-card"' . $dl_attr . '>';
        $html   .= '<span class="mova-dr-card-label">' . $label . '</span>';
        $html   .= '<span class="mova-dr-card-dl">' . mova_dr_download_icon( 16 ) . '</span>';
        $html   .= '</a>';
    }
    $html .= '</div>';

    return $html;
}

/* -----------------------------------------------
   Enqueue CSS partagé
   ----------------------------------------------- */
function mova_dr_enqueue() {
    wp_enqueue_style(
        'mova-dealer-resources-style',
        get_stylesheet_directory_uri() . '/assets/css/dealer-resources.css',
        array(),
        '1.0.0'
    );
}


/* =============================================
   [mova_dealer_formulaires]
   Liens vers les formulaires en ligne
   ============================================= */
function mova_dealer_formulaires_shortcode( $atts ) {
    mova_dr_enqueue();

    $items = get_field( 'ed_formulaires', 'option' );

    if ( empty( $items ) ) {
        return '<p class="mova-dr-empty">' . esc_html__( 'Aucun formulaire disponible.', 'piscines-mova' ) . '</p>';
    }

    $form_icon = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><polyline points="10 9 9 9 8 9"/></svg>';

    $html = '<ul class="mova-dr-form-list">';
    foreach ( $items as $item ) {
        $url   = ! empty( $item['url'] )   ? esc_url( $item['url'] )   : '';
        $label = ! empty( $item['label'] ) ? esc_html( $item['label'] ) : '';

        if ( ! $url || ! $label ) continue;

        $html .= '<li class="mova-dr-form-item">';
        $html .= '<a href="' . $url . '" class="mova-dr-form-link" target="_blank" rel="noopener">';
        $html .= '<span class="mova-dr-form-icon">' . $form_icon . '</span>';
        $html .= '<span>' . $label . '</span>';
        $html .= '</a>';
        $html .= '</li>';
    }
    $html .= '</ul>';

    return $html;
}
add_shortcode( 'mova_dealer_formulaires', 'mova_dealer_formulaires_shortcode' );


/* =============================================
   [mova_dealer_garanties]
   Fichiers PDF de garanties
   ============================================= */
function mova_dealer_garanties_shortcode( $atts ) {
    mova_dr_enqueue();
    $items = get_field( 'ed_garanties', 'option' );
    return mova_dr_render_card_grid( $items );
}
add_shortcode( 'mova_dealer_garanties', 'mova_dealer_garanties_shortcode' );


/* =============================================
   [mova_dealer_manuels]
   Manuels et procédures (PDF)
   ============================================= */
function mova_dealer_manuels_shortcode( $atts ) {
    mova_dr_enqueue();
    $items = get_field( 'ed_manuels', 'option' );
    return mova_dr_render_card_grid( $items );
}
add_shortcode( 'mova_dealer_manuels', 'mova_dealer_manuels_shortcode' );


/* =============================================
   [mova_dealer_logos]
   Logos (ZIP, SVG, PNG…) avec aperçu visuel
   ============================================= */
function mova_dealer_logos_shortcode( $atts ) {
    mova_dr_enqueue();

    $items = get_field( 'ed_logos', 'option' );

    if ( empty( $items ) ) {
        return '<p class="mova-dr-empty">' . esc_html__( 'Aucun logo disponible.', 'piscines-mova' ) . '</p>';
    }

    $html = '<div class="mova-dr-grid mova-dr-grid--logos">';
    foreach ( $items as $item ) {
        $url    = ! empty( $item['fichier'] ) ? esc_url( $item['fichier'] )  : '';
        $label  = ! empty( $item['label'] )   ? esc_html( $item['label'] )   : ( $url ? esc_html( basename( $url ) ) : '' );
        $apercu = ! empty( $item['apercu'] )  ? esc_url( $item['apercu'] )   : '';

        if ( ! $url || ! $label ) continue;

        $html .= '<a href="' . $url . '" class="mova-dr-logo-card" download>';

        $html .= '<div class="mova-dr-logo-card-img' . ( ! $apercu ? ' mova-dr-logo-card-img--empty' : '' ) . '">';
        if ( $apercu ) {
            $html .= '<img src="' . $apercu . '" alt="' . esc_attr( $label ) . '" loading="lazy" />';
        }
        $html .= '</div>';

        $html .= '<div class="mova-dr-logo-card-footer">';
        $html .= mova_dr_download_icon( 13 );
        $html .= '<span>' . $label . '</span>';
        $html .= '</div>';

        $html .= '</a>';
    }
    $html .= '</div>';

    return $html;
}
add_shortcode( 'mova_dealer_logos', 'mova_dealer_logos_shortcode' );


/* =============================================
   [mova_dealer_images]
   Photos téléchargeables (JPG, WEBP, PNG)
   ============================================= */
function mova_dealer_images_shortcode( $atts ) {
    mova_dr_enqueue();

    $items = get_field( 'ed_images', 'option' );

    if ( empty( $items ) ) {
        return '<p class="mova-dr-empty">' . esc_html__( 'Aucune image disponible.', 'piscines-mova' ) . '</p>';
    }

    $html = '<div class="mova-dr-photo-grid">';
    foreach ( $items as $item ) {
        $fichier = ! empty( $item['fichier'] ) ? $item['fichier'] : null;
        $label   = ! empty( $item['label'] )   ? esc_html( $item['label'] ) : '';

        if ( ! $fichier || ! $label ) continue;

        // URL scalée pour l'aperçu (optimisée navigateur)
        $preview_url  = esc_url( $fichier['url'] );
        // URL originale pour le téléchargement
        $original_url = esc_url( wp_get_original_image_url( $fichier['id'] ) ?: $fichier['url'] );
        $filename     = esc_attr( basename( wp_get_original_image_path( $fichier['id'] ) ?: $fichier['url'] ) );

        $html .= '<div class="mova-dr-photo-item">';
        $html .= '<a href="' . $original_url . '" class="mova-dr-photo-img" download="' . $filename . '">';
        $html .= '<div class="mova-dr-photo-img-wrap">';
        $html .= '<img src="' . $preview_url . '" alt="' . esc_attr( $label ) . '" loading="lazy" />';
        $html .= '</div>';
        $html .= '</a>';
        $html .= '<a href="' . $original_url . '" class="mova-dr-photo-dl" download="' . $filename . '">';
        $html .= mova_dr_download_icon( 13 );
        $html .= '<span>' . $label . '</span>';
        $html .= '</a>';
        $html .= '</div>';
    }
    $html .= '</div>';

    return $html;
}
add_shortcode( 'mova_dealer_images', 'mova_dealer_images_shortcode' );


/* =============================================
   [mova_dealer_videos]
   Vidéos (MP4 en téléchargement et/ou URL externe)
   ============================================= */
function mova_dealer_videos_shortcode( $atts ) {
    mova_dr_enqueue();

    $items = get_field( 'ed_videos', 'option' );

    if ( empty( $items ) ) {
        return '<p class="mova-dr-empty">' . esc_html__( 'Aucune vidéo disponible.', 'piscines-mova' ) . '</p>';
    }

    $html = '<div class="mova-dr-photo-grid">';
    foreach ( $items as $item ) {
        $label   = ! empty( $item['label'] )      ? esc_html( $item['label'] )      : '';
        $fichier = ! empty( $item['fichier'] )     ? esc_url( $item['fichier'] )     : '';
        $apercu  = ! empty( $item['apercu'] )      ? esc_url( $item['apercu'] )      : '';

        if ( ! $label || ! $fichier ) continue;

        $html .= '<div class="mova-dr-photo-item">';

        $html .= '<a href="' . $fichier . '" class="mova-dr-photo-img mova-dr-photo-img--video" download>';
        $html .= '<div class="mova-dr-photo-img-wrap">';
        if ( $apercu ) {
            $html .= '<img src="' . $apercu . '" alt="' . esc_attr( $label ) . '" loading="lazy" />';
        } else {
            $html .= '<span class="mova-dr-video-placeholder" aria-hidden="true">';
            $html .= '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="40" height="40" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><polygon points="23 7 16 12 23 17 23 7"/><rect x="1" y="5" width="15" height="14" rx="2" ry="2"/></svg>';
            $html .= '</span>';
        }
        $html .= '</div>';
        $html .= '</a>';

        $html .= '<a href="' . $fichier . '" class="mova-dr-photo-dl" download>';
        $html .= mova_dr_download_icon( 13 );
        $html .= '<span>' . $label . '</span>';
        $html .= '</a>';

        $html .= '</div>';
    }
    $html .= '</div>';

    return $html;
}
add_shortcode( 'mova_dealer_videos', 'mova_dealer_videos_shortcode' );
