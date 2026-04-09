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
                'wpSlug'  => $term_obj->slug,
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

    // --- Tapis disponibles par zone ---
    $tapis_raw = get_field( 'tapis_disponibles', $post_id );
    $tapis_par_zone = array(); // zone => [ {slug, name, swatch}, ... ]

    if ( ! empty( $tapis_raw ) && is_array( $tapis_raw ) ) {
        foreach ( $zones as $zone ) {
            $tapis_par_zone[ $zone ] = array();

            foreach ( $tapis_raw as $term ) {
                $term_id  = is_object( $term ) ? $term->term_id : intval( $term );
                $term_obj = is_object( $term ) ? $term : get_term( $term_id, 'modele_tapis' );

                if ( ! $term_obj || is_wp_error( $term_obj ) ) continue;

                $slug_fichier = get_field( 'slug_fichier', 'modele_tapis_' . $term_obj->term_id );
                if ( ! $slug_fichier ) continue;

                // Vérifier que l'overlay existe pour cette zone précise
                $overlay_file = $slug_dimension . '-' . $slug_fichier . '-' . $zone . '.png';
                if ( ! file_exists( $base_dir . $overlay_file ) ) continue;

                $swatch_id = get_field( 'swatch_tapis', 'modele_tapis_' . $term_obj->term_id );

                $tapis_par_zone[ $zone ][] = array(
                    'slug'    => $slug_fichier,
                    'name'    => $term_obj->name,
                    'swatch'  => $swatch_id ? wp_get_attachment_image_url( $swatch_id, 'thumbnail' ) : '',
                );
            }

            // Retirer les zones sans tapis valide
            if ( empty( $tapis_par_zone[ $zone ] ) ) {
                unset( $tapis_par_zone[ $zone ] );
            }
        }
    }

    if ( empty( $tapis_par_zone ) ) {
        if ( $debug ) {
            $nb_raw = is_array( $tapis_raw ) ? count( $tapis_raw ) : 0;
            return '<!-- mova-cfg: 0 tapis valides sur ' . $nb_raw . ' bruts. Vérifiez slug_fichier sur chaque terme modele_tapis ET qu\'au moins un fichier ' . $slug_dimension . '-{slug}-{zone}.png existe dans ' . $base_dir . ' -->';
        }
        return '';
    }

    // Zones effectives (celles qui ont au moins 1 tapis)
    $zones_effectives = array_keys( $tapis_par_zone );

    // Défauts par zone
    $default_couleur = $couleurs[0]['slug'];
    $defaults_tapis  = array();
    foreach ( $tapis_par_zone as $zone => $liste ) {
        $defaults_tapis[ $zone ] = $liste[0]['slug'];
    }

    // --- Options compatibles ---
    $options = array();
    if ( get_field( 'opt_jets', $post_id ) ) {
        $options[] = array( 'slug' => 'jets', 'label' => 'Jets de massage' );
    }
    if ( get_field( 'opt_badujet', $post_id ) ) {
        $options[] = array( 'slug' => 'badujet', 'label' => 'BaduJet Turbo' );
    }

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
        'zones'          => $zones_effectives,
        'defaultCouleur' => $default_couleur,
        'defaultsTapis'  => $defaults_tapis,
        'couleurs'       => $couleurs,
        'tapisParZone'   => $tapis_par_zone,
        'options'        => $options,
        'devisUrl'       => 'https://piscinesmova.preprod.io/demandez-un-devis/',
        'modelSlug'      => get_post_field( 'post_name', $post_id ),
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
                <?php foreach ( $zones_effectives as $zone ) :
                    $def_tapis_slug = $defaults_tapis[ $zone ];
                    $overlay_file   = $slug_dimension . '-' . $def_tapis_slug . '-' . $zone . '.png';
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

            <!-- Tapis par zone -->
            <?php foreach ( $zones_effectives as $zone ) :
                $zone_tapis   = $tapis_par_zone[ $zone ];
                $default_slug = $defaults_tapis[ $zone ];
                $zone_labels  = array( 'marches' => 'Marches', 'bancs' => 'Bancs', 'terrasse' => 'Terrasse', 'fond' => 'Fond' );
                $zone_label   = isset( $zone_labels[ $zone ] ) ? $zone_labels[ $zone ] : ucfirst( $zone );
            ?>
            <div class="mova-cfg-section mova-cfg-section--zone" data-zone="<?php echo esc_attr( $zone ); ?>">
                <div class="mova-cfg-zone-header">
                    <h4 class="mova-cfg-section-title">Tapis — <?php echo esc_html( $zone_label ); ?></h4>
                    <button class="mova-cfg-zone-toggle is-active"
                            data-zone="<?php echo esc_attr( $zone ); ?>"
                            aria-pressed="true"
                            title="Activer/désactiver cette zone">
                        <span class="mova-cfg-zone-toggle-icon"></span>
                    </button>
                </div>
                <div class="mova-cfg-swatches mova-cfg-zone-swatches" data-zone="<?php echo esc_attr( $zone ); ?>">
                    <?php foreach ( $zone_tapis as $t ) : ?>
                    <button class="mova-cfg-swatch mova-cfg-swatch--tapis<?php echo $t['slug'] === $default_slug ? ' is-active' : ''; ?>"
                            data-slug="<?php echo esc_attr( $t['slug'] ); ?>"
                            data-zone="<?php echo esc_attr( $zone ); ?>"
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
                <p class="mova-cfg-active-label" data-zone-label="<?php echo esc_attr( $zone ); ?>"><?php echo esc_html( $zone_tapis[0]['name'] ); ?></p>
            </div>
            <?php endforeach; ?>

            <!-- Options -->
            <?php if ( ! empty( $options ) ) : ?>
            <div class="mova-cfg-section">
                <h4 class="mova-cfg-section-title">Options</h4>
                <div class="mova-cfg-options" id="mova-cfg-options">
                    <?php foreach ( $options as $opt ) : ?>
                    <label class="mova-cfg-option">
                        <input type="checkbox" value="<?php echo esc_attr( $opt['slug'] ); ?>" />
                        <span><?php echo esc_html( $opt['label'] ); ?></span>
                    </label>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- Bouton devis -->
            <div class="mova-cfg-section mova-cfg-section--cta">
                <a href="#" class="mova-cfg-devis-btn" id="mova-cfg-devis-btn">
                    Obtenir un devis <span class="mova-cfg-devis-icon">+</span>
                </a>
            </div>

        </div>

    </div>

    <?php
    return ob_get_clean();
}
add_shortcode( 'mova_pool_configurator', 'mova_pool_configurator_shortcode' );
