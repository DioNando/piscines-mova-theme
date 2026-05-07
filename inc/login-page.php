<?php
/**
 * Module : Page de connexion WordPress (wp-login.php)
 *
 * Personnalise l'apparence de wp-login.php pour correspondre
 * au design du thème Piscines Mova.
 *
 * @package HelloElementorChild
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Enqueue the login page stylesheet.
 */
function mova_login_enqueue_styles() {
	wp_enqueue_style(
		'mova-login-page',
		get_stylesheet_directory_uri() . '/assets/css/login-page.css',
		[],
		HELLO_ELEMENTOR_CHILD_VERSION
	);
}
add_action( 'login_enqueue_scripts', 'mova_login_enqueue_styles' );

/**
 * Inject the theme logo URL as a CSS custom property so the
 * stylesheet can reference it without hardcoding the path.
 */
function mova_login_head_styles() {
	$logo_url = get_stylesheet_directory_uri() . '/assets/images/logo-mova.svg';
	echo '<style>:root { --mova-logo-url: url("' . esc_url( $logo_url ) . '"); }</style>' . "\n";
}
add_action( 'login_head', 'mova_login_head_styles' );

/**
 * Logo link → accueil du site.
 */
add_filter( 'login_headerurl', function () {
	return home_url( '/' );
} );

/**
 * Logo title → nom du site.
 */
add_filter( 'login_headertext', function () {
	return get_bloginfo( 'name' );
} );
