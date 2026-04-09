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

    // --- Couleurs depuis le repeater ---
    $couleurs_cfg = get_field( 'couleurs_configurateur', $post_id );
    $couleurs     = array();

    if ( ! empty( $couleurs_cfg ) && is_array( $couleurs_cfg ) ) {
        foreach ( $couleurs_cfg as $row ) {
            $term_obj = isset( $row['couleur'] ) ? $row['couleur'] : null;
            $image_id = isset( $row['image_fond'] ) ? intval( $row['image_fond'] ) : 0;

            if ( ! $term_obj || ! is_object( $term_obj ) || ! $image_id ) continue;

            $fond_url = wp_get_attachment_image_url( $image_id, 'full' );
            if ( ! $fond_url ) continue;

            $swatch_id = get_field( 'swatch_couleur', 'couleur_piscine_' . $term_obj->term_id );

            $couleurs[] = array(
                'slug'    => $term_obj->slug,
                'name'    => $term_obj->name,
                'fondUrl' => $fond_url,
                'swatch'  => $swatch_id ? wp_get_attachment_image_url( $swatch_id, 'thumbnail' ) : '',
            );
        }
    }

    if ( empty( $couleurs ) ) {
        if ( $debug ) {
            $nb_raw = is_array( $couleurs_cfg ) ? count( $couleurs_cfg ) : 0;
            return '<!-- mova-cfg: 0 couleurs valides sur ' . $nb_raw . ' lignes repeater. Vérifiez que chaque ligne a une couleur et une image fond. -->';
        }
        return '';
    }

    // --- Tapis par zone depuis le repeater ---
    $zones_cfg      = get_field( 'zones_configurateur', $post_id );
    $tapis_par_zone = array(); // zone => [ {slug, name, swatch, overlayUrl}, ... ]

    if ( ! empty( $zones_cfg ) && is_array( $zones_cfg ) ) {
        foreach ( $zones_cfg as $zone_row ) {
            $zone       = isset( $zone_row['zone'] ) ? $zone_row['zone'] : '';
            $tapis_rows = isset( $zone_row['tapis_zone'] ) ? $zone_row['tapis_zone'] : array();

            if ( ! $zone || empty( $tapis_rows ) || ! is_array( $tapis_rows ) ) continue;

            $zone_tapis = array();

            foreach ( $tapis_rows as $t_row ) {
                $term_obj   = isset( $t_row['modele_tapis'] ) ? $t_row['modele_tapis'] : null;
                $overlay_id = isset( $t_row['overlay'] ) ? intval( $t_row['overlay'] ) : 0;

                if ( ! $term_obj || ! is_object( $term_obj ) || ! $overlay_id ) continue;

                $overlay_url = wp_get_attachment_image_url( $overlay_id, 'full' );
                if ( ! $overlay_url ) continue;

                // Swatch : override du repeater, sinon default du terme
                $swatch_id = ! empty( $t_row['swatch_override'] ) ? intval( $t_row['swatch_override'] ) : 0;
                if ( ! $swatch_id ) {
                    $swatch_id = get_field( 'swatch_tapis', 'modele_tapis_' . $term_obj->term_id );
                }

                $zone_tapis[] = array(
                    'slug'       => $term_obj->slug,
                    'name'       => $term_obj->name,
                    'overlayUrl' => $overlay_url,
                    'swatch'     => $swatch_id ? wp_get_attachment_image_url( $swatch_id, 'thumbnail' ) : '',
                );
            }

            if ( ! empty( $zone_tapis ) ) {
                $tapis_par_zone[ $zone ] = $zone_tapis;
            }
        }
    }

    if ( empty( $tapis_par_zone ) ) {
        if ( $debug ) {
            $nb_raw = is_array( $zones_cfg ) ? count( $zones_cfg ) : 0;
            return '<!-- mova-cfg: 0 zones valides sur ' . $nb_raw . ' lignes repeater. Vérifiez que chaque zone a au moins un tapis avec overlay. -->';
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
    $default_fond_url = $couleurs[0]['fondUrl'];

    // Assets
    wp_enqueue_style(
        'mova-pool-configurator-style',
        get_stylesheet_directory_uri() . '/assets/css/pool-configurator.css',
        array(),
        '1.1.0'
    );
    wp_enqueue_script(
        'mova-pool-configurator-script',
        get_stylesheet_directory_uri() . '/assets/js/pool-configurator.js',
        array(),
        '1.1.0',
        true
    );

    wp_localize_script( 'mova-pool-configurator-script', 'movaConfigurator', array(
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
            <button class="mova-cfg-zoom" id="mova-cfg-zoom" aria-label="Agrandir">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/><line x1="11" y1="8" x2="11" y2="14"/><line x1="8" y1="11" x2="14" y2="11"/></svg>
            </button>
            <div class="mova-cfg-layers" id="mova-cfg-layers">
                <img src="<?php echo esc_url( $default_fond_url ); ?>"
                     alt="Fond <?php echo esc_attr( $couleurs[0]['name'] ); ?>"
                     class="mova-cfg-layer mova-cfg-layer--fond"
                     id="mova-cfg-layer-fond" />
                <?php foreach ( $zones_effectives as $zone ) :
                    $def_tapis     = $tapis_par_zone[ $zone ][0];
                    $overlay_url   = $def_tapis['overlayUrl'];
                ?>
                <img src="<?php echo esc_url( $overlay_url ); ?>"
                     alt="<?php echo esc_attr( ucfirst( $zone ) ); ?>"
                     class="mova-cfg-layer mova-cfg-layer--overlay"
                     data-zone="<?php echo esc_attr( $zone ); ?>"
                     id="mova-cfg-layer-<?php echo esc_attr( $zone ); ?>" />
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
                    Obtenir un devis
                </a>
            </div>

        </div>

    </div>

    <!-- Lightbox -->
    <div class="mova-cfg-lightbox" id="mova-cfg-lightbox">
        <button class="mova-cfg-lb-close" id="mova-cfg-lb-close" aria-label="Fermer">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none"><path d="M18 6L6 18M6 6l12 12" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
        </button>
        <div class="mova-cfg-lb-content" id="mova-cfg-lb-content"></div>
    </div>

    <?php
    return ob_get_clean();
}
add_shortcode( 'mova_pool_configurator', 'mova_pool_configurator_shortcode' );
