<?php
// Empêcher l'accès direct
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/* =============================================
   Shortcode — [mova_section_filter]
   Filtre de sections Elementor par ID CSS
   
   Usage :
   [mova_section_filter options="Tout afficher:all,Garanties:garanties,Logos:logos"]
   
   Attributs :
   - options   : liste séparée par des virgules de paires "Label:section-id"
                 Utiliser "all" comme id pour l'option "Tout afficher"
   - label     : texte du label visible (défaut : "Filtrer les sections")
   - class     : classe CSS supplémentaire sur le wrapper
   ============================================= */
function mova_section_filter_shortcode( $atts ) {
    $atts = shortcode_atts( array(
        'options' => __( 'Tout afficher', 'piscines-mova' ) . ':all',
        'label'   => __( 'Filtrer les sections', 'piscines-mova' ),
        'class'   => '',
    ), $atts, 'mova_section_filter' );

    // Enqueue assets
    wp_enqueue_style(
        'mova-section-filter-style',
        get_stylesheet_directory_uri() . '/assets/css/dealer-space-filter.css',
        array(),
        '1.0.0'
    );
    wp_enqueue_script(
        'mova-section-filter-script',
        get_stylesheet_directory_uri() . '/assets/js/dealer-space-filter.js',
        array(),
        '1.0.0',
        true
    );

    // Parser les options : "Label:id,Label2:id2,..."
    $raw_options = array_map( 'trim', explode( ',', $atts['options'] ) );
    $options     = array();
    $section_ids = array();

    foreach ( $raw_options as $item ) {
        $parts = explode( ':', $item, 2 );
        if ( count( $parts ) === 2 ) {
            $label_text = trim( $parts[0] );
            $value      = sanitize_html_class( trim( $parts[1] ) );
            $options[]  = array( 'label' => $label_text, 'value' => $value );
            if ( $value !== 'all' ) {
                $section_ids[] = $value;
            }
        }
    }

    // Passer les IDs des sections au JS
    $unique_id = 'mova-sf-' . wp_unique_id();
    wp_add_inline_script(
        'mova-section-filter-script',
        sprintf(
            'document.addEventListener("DOMContentLoaded", function(){ movaInitSectionFilter("%s", %s); });',
            esc_js( $unique_id ),
            wp_json_encode( $section_ids )
        )
    );

    // Construire le HTML
    $wrapper_class = 'mova-section-filter' . ( $atts['class'] ? ' ' . esc_attr( $atts['class'] ) : '' );

    $html  = '<div class="' . $wrapper_class . '" id="' . esc_attr( $unique_id ) . '">';
    $html .= '<label class="mova-sf-label" for="' . esc_attr( $unique_id ) . '-select">';
    $html .= esc_html( $atts['label'] );
    $html .= '</label>';
    $html .= '<div class="mova-sf-select-wrap">';
    $html .= '<select class="mova-sf-select" id="' . esc_attr( $unique_id ) . '-select" aria-label="' . esc_attr( $atts['label'] ) . '">';

    foreach ( $options as $option ) {
        $html .= '<option value="' . esc_attr( $option['value'] ) . '">';
        $html .= esc_html( $option['label'] );
        $html .= '</option>';
    }

    $html .= '</select>';
    $html .= '<span class="mova-sf-chevron" aria-hidden="true">';
    $html .= '<svg width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M3 6l5 5 5-5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>';
    $html .= '</span>';
    $html .= '</div>';
    $html .= '</div>';

    return $html;
}
add_shortcode( 'mova_section_filter', 'mova_section_filter_shortcode' );
