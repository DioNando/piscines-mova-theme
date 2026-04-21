<?php
/**
 * Theme functions and definitions.
 *
 * For additional information on potential customization options,
 * read the developers' documentation:
 *
 * https://developers.elementor.com/docs/hello-elementor-theme/
 *
 * @package HelloElementorChild
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

define( 'HELLO_ELEMENTOR_CHILD_VERSION', '2.0.0' );

/**
 * Load child theme scripts & styles.
 *
 * @return void
 */
function hello_elementor_child_scripts_styles() {

	wp_enqueue_style(
		'hello-elementor-child-style',
		get_stylesheet_directory_uri() . '/style.css',
		[
			'hello-elementor-theme-style',
		],
		HELLO_ELEMENTOR_CHILD_VERSION
	);

}
add_action( 'wp_enqueue_scripts', 'hello_elementor_child_scripts_styles', 20 );

// Autoriser le téléversement de fichiers SVG
function cc_mime_types($mimes) {
  $mimes['svg'] = 'image/svg+xml';
  return $mimes;
}
add_filter('upload_mimes', 'cc_mime_types');

add_filter( 'wpseo_breadcrumb_separator', 'custom_yoast_breadcrumb_separator' );

function custom_yoast_breadcrumb_separator( $separator ) {
    // Remplacez le contenu entre les guillemets par votre propre code <svg>
    $svg_icon = '<svg width="10" height="10" viewBox="0 0 10 10" xmlns="http://www.w3.org/2000/svg">
                    <path d="M2 1l4 4-4 4" stroke="currentColor" fill="none" />
                 </svg>';
                 
    return '<span class="breadcrumb-separator">' . $svg_icon . '</span>';
}

// Ajouter la page "Nos Modèles" dans le fil d'Ariane Yoast pour les piscines
add_filter( 'wpseo_breadcrumb_links', 'mova_custom_breadcrumbs_piscines' );

function mova_custom_breadcrumbs_piscines( $links ) {
    // On cible uniquement les fiches individuelles du CPT 'piscine'
    if ( is_singular( 'piscine' ) ) {
        
        // On crée le lien de la page parent à injecter
        $breadcrumb_parent = array(
            array(
                'url'  => home_url( '/modeles/' ), // L'URL de votre page
                'text' => 'Modèles',           // Le texte affiché dans le fil d'Ariane
            )
        );

        // On sépare le fil d'ariane : on garde le début (Accueil)
        $debut = array_slice( $links, 0, 1 );
        
        // On isole la fin (Le nom de la piscine actuelle)
        $fin = array_slice( $links, 1 );

        // On fusionne le tout : Accueil + Modèles + Nom de la piscine
        $links = array_merge( $debut, $breadcrumb_parent, $fin );
    }

    return $links;
}

// Charger les modules
require_once get_stylesheet_directory() . '/inc/pdf-buttons.php';
require_once get_stylesheet_directory() . '/inc/store-locator.php';
require_once get_stylesheet_directory() . '/inc/pool-catalog.php';
require_once get_stylesheet_directory() . '/inc/dealer-detail.php';
require_once get_stylesheet_directory() . '/inc/nearby-dealers.php';
require_once get_stylesheet_directory() . '/inc/inspirations.php';
require_once get_stylesheet_directory() . '/inc/pool-gallery.php';
require_once get_stylesheet_directory() . '/inc/pool-color-preview.php';
require_once get_stylesheet_directory() . '/inc/pool-color-catalog.php';
require_once get_stylesheet_directory() . '/inc/pool-configurator.php';
require_once get_stylesheet_directory() . '/inc/tapis-catalog.php';
require_once get_stylesheet_directory() . '/inc/carrieres-list.php';
require_once get_stylesheet_directory() . '/inc/quote-form.php';
require_once get_stylesheet_directory() . '/inc/similar-pools.php';