<?php
/**
 * Shortcode : Sélecteur de langue WPML
 *
 * Usage : [language_switcher] ou [language_switcher show_flags="true" show_names="true" layout="dropdown"]
 *
 * Attributs :
 *   show_flags  — true|false  — Afficher les drapeaux (défaut : false)
 *   show_names  — true|false  — Afficher les noms de langue (défaut : true)
 *   layout      — list|dropdown — Style de rendu (défaut : list)
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

function mova_language_switcher_styles() {
    wp_enqueue_style(
        'mova-language-switcher',
        get_stylesheet_directory_uri() . '/assets/css/language-switcher.css',
        array(),
        HELLO_ELEMENTOR_CHILD_VERSION
    );
}
add_action( 'wp_enqueue_scripts', 'mova_language_switcher_styles' );

function mova_language_switcher_shortcode( $atts ) {
    // WPML doit être actif
    if ( ! function_exists( 'icl_get_languages' ) ) {
        return '';
    }

    $atts = shortcode_atts( array(
        'show_flags' => 'false',
        'show_names' => 'false',
        'layout'     => 'list',
    ), $atts, 'language_switcher' );

    $show_flags = filter_var( $atts['show_flags'], FILTER_VALIDATE_BOOLEAN );
    $show_names = filter_var( $atts['show_names'], FILTER_VALIDATE_BOOLEAN );
    $layout     = in_array( $atts['layout'], array( 'list', 'dropdown' ), true ) ? $atts['layout'] : 'list';

    $languages = icl_get_languages( 'skip_missing=0&orderby=code' );

    if ( empty( $languages ) ) {
        return '';
    }

    ob_start();

    if ( $layout === 'dropdown' ) :
        $current_lang = apply_filters( 'wpml_current_language', null );
        ?>
        <div class="mova-lang-switcher mova-lang-switcher--dropdown">
            <select onchange="window.location.href=this.value;" aria-label="<?php esc_attr_e( 'Sélectionner la langue', 'piscines-mova' ); ?>">
                <?php foreach ( $languages as $lang ) : ?>
                    <option value="<?php echo esc_url( $lang['url'] ); ?>"
                        <?php selected( $lang['active'], 1 ); ?>>
                        <?php echo esc_html( strtoupper( $lang['language_code'] ) ); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <?php
    else :
        ?>
        <ul class="mova-lang-switcher mova-lang-switcher--list">
            <?php foreach ( $languages as $lang ) : ?>
                <li class="mova-lang-switcher__item<?php echo $lang['active'] ? ' mova-lang-switcher__item--active' : ''; ?>">
                    <a href="<?php echo esc_url( $lang['url'] ); ?>"
                       lang="<?php echo esc_attr( $lang['language_code'] ); ?>"
                       hreflang="<?php echo esc_attr( $lang['language_code'] ); ?>"
                       <?php echo $lang['active'] ? 'aria-current="true"' : ''; ?>>
                        <?php if ( $show_flags && ! empty( $lang['country_flag_url'] ) ) : ?>
                            <img class="mova-lang-switcher__flag"
                                 src="<?php echo esc_url( $lang['country_flag_url'] ); ?>"
                                 alt="<?php echo esc_attr( $lang['translated_name'] ); ?>"
                                 width="18" height="12" loading="lazy">
                        <?php endif; ?>
                        <?php if ( $show_names ) : ?>
                            <span class="mova-lang-switcher__name"><?php echo esc_html( $lang['native_name'] ); ?></span>
                        <?php else : ?>
                            <span class="mova-lang-switcher__code"><?php echo esc_html( strtoupper( $lang['language_code'] ) ); ?></span>
                        <?php endif; ?>
                    </a>
                </li>
            <?php endforeach; ?>
        </ul>
        <?php
    endif;

    return ob_get_clean();
}
add_shortcode( 'language_switcher', 'mova_language_switcher_shortcode' );
