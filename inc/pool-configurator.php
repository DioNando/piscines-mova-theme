<?php
// Empêcher l'accès direct
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/* =============================================
   Shortcode — [mova_pool_configurator]
   Configurateur AquaCove — superposition tapis
   ============================================= */
function mova_pool_configurator_shortcode( $atts ) {
    $atts = shortcode_atts( array(
        'id'    => 0,
        'debug' => 0,
    ), $atts, 'mova_pool_configurator' );

    $debug   = intval( $atts['debug'] ) && current_user_can( 'manage_options' );
    $post_id = intval( $atts['id'] ) ?: get_the_ID();

    if ( ! $post_id || get_post_type( $post_id ) !== 'piscine' ) {
        return $debug ? '<!-- mova-cfg: post_type n\'est pas piscine (id=' . $post_id . ', type=' . get_post_type( $post_id ) . ') -->' : '';
    }

    // Garde : AquaCove doit être activé
    if ( ! get_field( 'opt_aquacove', $post_id ) ) {
        return $debug ? '<!-- mova-cfg: opt_aquacove est désactivé -->' : '';
    }

    $slug_dimension = get_field( 'slug_dimension', $post_id );
    if ( ! $slug_dimension ) {
        return $debug ? '<!-- mova-cfg: slug_dimension est vide -->' : '';
    }

    $zones = get_field( 'zones_aquacove', $post_id );
    if ( ! is_array( $zones ) || empty( $zones ) ) {
        return $debug ? '<!-- mova-cfg: zones_aquacove est vide (valeur=' . print_r( $zones, true ) . ') -->' : '';
    }

    // Chemins
    $base_dir = get_stylesheet_directory() . '/assets/images/tapis-aquacove/';
    $base_url = get_stylesheet_directory_uri() . '/assets/images/tapis-aquacove/';

    // --- Couleurs disponibles ---
    $couleurs_raw = get_field( 'couleurs_disponibles', $post_id );
    $couleurs     = array();

    if ( ! empty( $couleurs_raw ) && is_array( $couleurs_raw ) ) {
        foreach ( $couleurs_raw as $term ) {
            $term_id  = is_object( $term ) ? $term->term_id : intval( $term );
            $term_obj = is_object( $term ) ? $term : get_term( $term_id, 'couleur_piscine' );

            if ( ! $term_obj || is_wp_error( $term_obj ) ) continue;

            $slug_fichier = get_field( 'slug_fichier', 'couleur_piscine_' . $term_obj->term_id );
            if ( ! $slug_fichier ) continue;

            // Vérifier que le fond existe
            $fond_file = 'piscine-' . $slug_dimension . '-' . $slug_fichier . '.png';
            if ( ! file_exists( $base_dir . $fond_file ) ) continue;

            $swatch_id = get_field( 'swatch_couleur', 'couleur_piscine_' . $term_obj->term_id );

            $couleurs[] = array(
                'slug'    => $slug_fichier,
                'name'    => $term_obj->name,
                'swatch'  => $swatch_id ? wp_get_attachment_image_url( $swatch_id, 'thumbnail' ) : '',
            );
        }
    }

    if ( empty( $couleurs ) ) {
        if ( $debug ) {
            $nb_raw = is_array( $couleurs_raw ) ? count( $couleurs_raw ) : 0;
            return '<!-- mova-cfg: 0 couleurs valides sur ' . $nb_raw . ' brutes. Vérifiez slug_fichier sur chaque terme couleur_piscine ET que le fichier piscine-' . $slug_dimension . '-{slug}.png existe dans ' . $base_dir . ' -->';
        }
        return '';
    }

    // --- Tapis disponibles ---
    $tapis_raw = get_field( 'tapis_disponibles', $post_id );
    $tapis     = array();

    if ( ! empty( $tapis_raw ) && is_array( $tapis_raw ) ) {
        foreach ( $tapis_raw as $term ) {
            $term_id  = is_object( $term ) ? $term->term_id : intval( $term );
            $term_obj = is_object( $term ) ? $term : get_term( $term_id, 'modele_tapis' );

            if ( ! $term_obj || is_wp_error( $term_obj ) ) continue;

            $slug_fichier = get_field( 'slug_fichier', 'modele_tapis_' . $term_obj->term_id );
            if ( ! $slug_fichier ) continue;

            // Vérifier qu'au moins un overlay existe pour ce tapis
            $has_overlay = false;
            foreach ( $zones as $zone ) {
                $overlay_file = $slug_dimension . '-' . $slug_fichier . '-' . $zone . '.png';
                if ( file_exists( $base_dir . $overlay_file ) ) {
                    $has_overlay = true;
                    break;
                }
            }
            if ( ! $has_overlay ) continue;

            $swatch_id = get_field( 'swatch_tapis', 'modele_tapis_' . $term_obj->term_id );

            $tapis[] = array(
                'slug'    => $slug_fichier,
                'name'    => $term_obj->name,
                'swatch'  => $swatch_id ? wp_get_attachment_image_url( $swatch_id, 'thumbnail' ) : '',
            );
        }
    }

    if ( empty( $tapis ) ) {
        if ( $debug ) {
            $nb_raw = is_array( $tapis_raw ) ? count( $tapis_raw ) : 0;
            return '<!-- mova-cfg: 0 tapis valides sur ' . $nb_raw . ' bruts. Vérifiez slug_fichier sur chaque terme modele_tapis ET qu\'au moins un fichier ' . $slug_dimension . '-{slug}-{zone}.png existe dans ' . $base_dir . ' -->';
        }
        return '';
    }

    // Défauts
    $default_couleur = $couleurs[0]['slug'];
    $default_tapis   = $tapis[0]['slug'];

    // Image fond par défaut
    $default_fond_url = $base_url . 'piscine-' . $slug_dimension . '-' . $default_couleur . '.png';

    // Assets
    wp_enqueue_style(
        'mova-pool-configurator-style',
        get_stylesheet_directory_uri() . '/assets/css/pool-configurator.css',
        array(),
        '1.0.0'
    );
    wp_enqueue_script(
        'mova-pool-configurator-script',
        get_stylesheet_directory_uri() . '/assets/js/pool-configurator.js',
        array(),
        '1.0.0',
        true
    );

    wp_localize_script( 'mova-pool-configurator-script', 'movaConfigurator', array(
        'baseUrl'        => $base_url,
        'slugDimension'  => $slug_dimension,
        'zones'          => $zones,
        'defaultCouleur' => $default_couleur,
        'defaultTapis'   => $default_tapis,
        'couleurs'       => $couleurs,
        'tapis'          => $tapis,
    ) );

    ob_start(); ?>

    <div class="mova-cfg" id="mova-cfg">

        <!-- Preview avec layers -->
        <div class="mova-cfg-preview">
            <div class="mova-cfg-layers" id="mova-cfg-layers">
                <img src="<?php echo esc_url( $default_fond_url ); ?>"
                     alt="Fond <?php echo esc_attr( $couleurs[0]['name'] ); ?>"
                     class="mova-cfg-layer mova-cfg-layer--fond"
                     id="mova-cfg-layer-fond" />
                <?php foreach ( $zones as $zone ) :
                    $overlay_file = $slug_dimension . '-' . $default_tapis . '-' . $zone . '.png';
                    $overlay_exists = file_exists( $base_dir . $overlay_file );
                ?>
                <img src="<?php echo $overlay_exists ? esc_url( $base_url . $overlay_file ) : ''; ?>"
                     alt="<?php echo esc_attr( ucfirst( $zone ) ); ?>"
                     class="mova-cfg-layer mova-cfg-layer--overlay"
                     data-zone="<?php echo esc_attr( $zone ); ?>"
                     id="mova-cfg-layer-<?php echo esc_attr( $zone ); ?>"
                     <?php if ( ! $overlay_exists ) echo 'style="display:none;"'; ?> />
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Panneau de configuration -->
        <div class="mova-cfg-panel">

            <h3 class="mova-cfg-title">Configurateur AquaCove</h3>

            <!-- Couleurs de fond -->
            <div class="mova-cfg-section">
                <h4 class="mova-cfg-section-title">Couleur de la coque</h4>
                <div class="mova-cfg-swatches" id="mova-cfg-couleurs">
                    <?php foreach ( $couleurs as $couleur ) : ?>
                    <button class="mova-cfg-swatch mova-cfg-swatch--couleur<?php echo $couleur['slug'] === $default_couleur ? ' is-active' : ''; ?>"
                            data-slug="<?php echo esc_attr( $couleur['slug'] ); ?>"
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
                <p class="mova-cfg-active-label" id="mova-cfg-couleur-label"><?php echo esc_html( $couleurs[0]['name'] ); ?></p>
            </div>

            <!-- Modèles de tapis -->
            <div class="mova-cfg-section">
                <h4 class="mova-cfg-section-title">Modèle de tapis</h4>
                <div class="mova-cfg-swatches" id="mova-cfg-tapis">
                    <?php foreach ( $tapis as $t ) : ?>
                    <button class="mova-cfg-swatch mova-cfg-swatch--tapis<?php echo $t['slug'] === $default_tapis ? ' is-active' : ''; ?>"
                            data-slug="<?php echo esc_attr( $t['slug'] ); ?>"
                            title="<?php echo esc_attr( $t['name'] ); ?>"
                            aria-label="<?php echo esc_attr( $t['name'] ); ?>">
                        <?php if ( $t['swatch'] ) : ?>
                            <img src="<?php echo esc_url( $t['swatch'] ); ?>"
                                 alt="<?php echo esc_attr( $t['name'] ); ?>" />
                        <?php else : ?>
                            <span class="mova-cfg-swatch-placeholder"><?php echo esc_html( mb_substr( $t['name'], 0, 2 ) ); ?></span>
                        <?php endif; ?>
                    </button>
                    <?php endforeach; ?>
                </div>
                <p class="mova-cfg-active-label" id="mova-cfg-tapis-label"><?php echo esc_html( $tapis[0]['name'] ); ?></p>
            </div>

            <!-- Zones -->
            <div class="mova-cfg-section">
                <h4 class="mova-cfg-section-title">Zones</h4>
                <div class="mova-cfg-zones" id="mova-cfg-zones">
                    <?php foreach ( $zones as $zone ) : ?>
                    <button class="mova-cfg-zone-toggle is-active"
                            data-zone="<?php echo esc_attr( $zone ); ?>"
                            aria-pressed="true">
                        <?php echo esc_html( ucfirst( $zone ) ); ?>
                    </button>
                    <?php endforeach; ?>
                </div>
            </div>

        </div>

    </div>

    <?php
    return ob_get_clean();
}
add_shortcode( 'mova_pool_configurator', 'mova_pool_configurator_shortcode' );
