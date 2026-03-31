<?php
// Empêcher l'accès direct
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

function mova_pdf_buttons_shortcode() {
    wp_enqueue_style( 'mova-pdf-buttons-style', get_stylesheet_directory_uri() . '/assets/css/pdf-buttons.css', array(), '1.0.0' );

    if( have_rows('fiches_techniques') ):
        $output = '<div class="mova-pdf-container">';
        
        while( have_rows('fiches_techniques') ) : the_row();
            $nom = get_sub_field('nom_document');
            $url = get_sub_field('fichier_pdf');
            
            // Code SVG de l'icône PDF
            $svg_icon = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 384 512" width="16" height="16" fill="currentColor" style="flex-shrink: 0;"><path d="M181.9 256.1c-5-16-4.9-46.9-2-46.9 8.4 0 7.6 36.9 2 46.9zm-1.7 47.2c-7.7 20.2-17.3 43.3-28.4 62.7 18.3-7 39-17.2 62.9-21.9-12.7-9.6-24.9-23.4-34.5-40.8zM86.1 428.1c0 .8 13.2-5.4 34.9-40.2-6.7 6.3-29.1 24.5-34.9 40.2zM261.6 306c-11.4 0-20.2 4.6-26 12.2 21 4.8 31.1 11.2 31.1 11.2 1.9-10.2-1.6-23.4-5.1-23.4zM384 121.9v358.1c0 17.7-14.3 32-32 32H32c-17.7 0-32-14.3-32-32V32C0 14.3 14.3 0 32 0h224l128 121.9zM256 128V16L368 128H256zm90.4 195.9c-11.6-14-31.5-24-48-24-11.4 0-22.1 4-30.8 11.3-10.4-18.4-22.1-34.3-32.9-50.5 4.3-16.7 7.1-36.9 7.1-50.7 0-18.7-5.9-36.4-17.6-43.1-6.1-3.5-13.8-4.4-22.7-1.1-14.4 5.3-22.8 24.3-22.8 45.3 0 18.5 4.8 40.2 12.9 61.4-15.6 38.6-32.5 73.6-50.2 101.9-25.5 13.1-57 32-60.5 50-1.4 7.2 2 13.4 8.7 16 10.7 4.1 30.6-2.5 51.5-22.1 20-17.6 42.6-35.1 65.5-47.5 25.1 6.5 50.8 15.5 70.3 28.5 7.1 4.7 15.9 8 23 8 13.2 0 24.1-8.5 28.4-21.7 4.3-13.4-1.2-28.7-12-41.6z"/></svg>';

            $output .= '<a href="' . esc_url($url) . '" target="_blank" class="mova-btn-pdf">';
            $output .= esc_html($nom) . $svg_icon;
            $output .= '</a>';
        endwhile;
        
        $output .= '</div>';
        return $output;
    endif;
    return '';
}
add_shortcode('mova_pdfs', 'mova_pdf_buttons_shortcode');
