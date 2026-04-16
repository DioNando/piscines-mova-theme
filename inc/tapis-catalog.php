<?php
// Empêcher l'accès direct
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/* =============================================
   Shortcode — [mova_tapis_catalog]
   Catalogue de prévisualisation des tapis AquaCove
   Preview via les configurateurs piscine (index inversé)
   ============================================= */
function mova_tapis_catalog_shortcode( $atts ) {
    $atts = shortcode_atts( array(
        'limit' => 0,
    ), $atts, 'mova_tapis_catalog' );

    // -------------------------------------------------------
    // 1. Requêter toutes les piscines AquaCove activées
    // -------------------------------------------------------
    $piscines = get_posts( array(
        'post_type'      => 'piscine',
        'posts_per_page' => -1,
        'post_status'    => 'publish',
        'orderby'        => 'title',
        'order'          => 'ASC',
        'meta_query'     => array(
            array(
                'key'   => 'opt_aquacove',
                'value' => '1',
            ),
        ),
    ) );

    if ( empty( $piscines ) ) {
        return '';
    }

    // -------------------------------------------------------
    // 2. Construire l'index inversé : tapis_slug → données
    // -------------------------------------------------------
    // $tapis_index[ slug ] = [
    //   'name', 'slug', 'swatch',
    //   'piscines' => [
    //     [ 'id', 'title', 'slug', 'defaultFondUrl', 'zones' => [ zone => overlayUrl ] ]
    //   ]
    // ]
    $tapis_index = array();

    foreach ( $piscines as $piscine ) {
        $pid        = $piscine->ID;
        $zones_cfg  = get_field( 'zones_configurateur', $pid );

        if ( empty( $zones_cfg ) || ! is_array( $zones_cfg ) ) continue;

        // Fond par défaut : première couleur du repeater
        $couleurs_cfg    = get_field( 'couleurs_configurateur', $pid );
        $default_fond    = '';
        if ( ! empty( $couleurs_cfg ) && is_array( $couleurs_cfg ) ) {
            foreach ( $couleurs_cfg as $row ) {
                $img_id = isset( $row['image_fond'] ) ? intval( $row['image_fond'] ) : 0;
                if ( $img_id ) {
                    $url = wp_get_attachment_image_url( $img_id, 'full' );
                    if ( $url ) {
                        $default_fond = $url;
                        break;
                    }
                }
            }
        }

        if ( ! $default_fond ) continue;

        // Parcourir les zones
        foreach ( $zones_cfg as $zone_row ) {
            $zone       = isset( $zone_row['zone'] ) ? $zone_row['zone'] : '';
            $tapis_rows = isset( $zone_row['tapis_zone'] ) ? $zone_row['tapis_zone'] : array();

            if ( ! $zone || empty( $tapis_rows ) || ! is_array( $tapis_rows ) ) continue;

            foreach ( $tapis_rows as $t_row ) {
                $term_obj   = isset( $t_row['modele_tapis'] ) ? $t_row['modele_tapis'] : null;
                $overlay_id = isset( $t_row['overlay'] ) ? intval( $t_row['overlay'] ) : 0;

                if ( ! $term_obj || ! is_object( $term_obj ) || ! $overlay_id ) continue;

                $overlay_url = wp_get_attachment_image_url( $overlay_id, 'full' );
                if ( ! $overlay_url ) continue;

                $slug = $term_obj->slug;

                // Initialiser l'entrée tapis si inexistante
                if ( ! isset( $tapis_index[ $slug ] ) ) {
                    $swatch_id  = get_field( 'swatch_tapis', 'modele_tapis_' . $term_obj->term_id );
                    $tapis_index[ $slug ] = array(
                        'name'     => html_entity_decode( $term_obj->name ),
                        'slug'     => $slug,
                        'swatch'   => $swatch_id ? wp_get_attachment_image_url( $swatch_id, 'thumbnail' ) : '',
                        'piscines' => array(),
                    );
                }

                // Chercher si la piscine est déjà dans la liste de ce tapis
                $piscine_key = null;
                foreach ( $tapis_index[ $slug ]['piscines'] as $k => $p ) {
                    if ( $p['id'] === $pid ) {
                        $piscine_key = $k;
                        break;
                    }
                }

                if ( $piscine_key === null ) {
                    // Nouvelle piscine pour ce tapis
                    $tapis_index[ $slug ]['piscines'][] = array(
                        'id'            => $pid,
                        'title'         => html_entity_decode( get_the_title( $pid ) ),
                        'slug'          => get_post_field( 'post_name', $pid ),
                        'defaultFondUrl' => $default_fond,
                        'zones'         => array( $zone => $overlay_url ),
                    );
                } else {
                    // Ajouter la zone à la piscine existante
                    $tapis_index[ $slug ]['piscines'][ $piscine_key ]['zones'][ $zone ] = $overlay_url;
                }
            }
        }
    }

    if ( empty( $tapis_index ) ) {
        return '';
    }

    // -------------------------------------------------------
    // 3. Trier par nom et appliquer la limite
    // -------------------------------------------------------
    uasort( $tapis_index, function ( $a, $b ) {
        return strcmp( $a['name'], $b['name'] );
    } );

    $tapis_list = array_values( $tapis_index );

    $limit = intval( $atts['limit'] );
    if ( $limit > 0 ) {
        $tapis_list = array_slice( $tapis_list, 0, $limit );
    }

    // -------------------------------------------------------
    // 4. Enqueue assets + localisation
    // -------------------------------------------------------
    wp_enqueue_style(
        'mova-tapis-catalog-style',
        get_stylesheet_directory_uri() . '/assets/css/tapis-catalog.css',
        array(),
        '2.0.0'
    );
    wp_enqueue_script(
        'mova-tapis-catalog-script',
        get_stylesheet_directory_uri() . '/assets/js/tapis-catalog.js',
        array(),
        '2.0.0',
        true
    );

    wp_localize_script( 'mova-tapis-catalog-script', 'movaTapisCatalog', array(
        'tapis' => $tapis_list,
    ) );

    // -------------------------------------------------------
    // 5. HTML
    // -------------------------------------------------------
    ob_start(); ?>

    <div class="mova-tc" id="mova-tc">
        <div class="mova-tc-layout">

            <!-- Colonne 1 : grille pastilles -->
            <div class="mova-tc-col-swatches">
                <div class="mova-tc-grid" id="mova-tc-grid">
                    <?php foreach ( $tapis_list as $index => $tapis ) : ?>
                    <button class="mova-tc-card"
                            data-index="<?php echo intval( $index ); ?>"
                            title="<?php echo esc_attr( $tapis['name'] ); ?>"
                            aria-label="<?php echo esc_attr( $tapis['name'] ); ?>">
                        <?php if ( $tapis['swatch'] ) : ?>
                            <img src="<?php echo esc_url( $tapis['swatch'] ); ?>"
                                 alt="<?php echo esc_attr( $tapis['name'] ); ?>"
                                 class="mova-tc-card-swatch" loading="lazy" />
                        <?php else : ?>
                            <span class="mova-tc-card-placeholder">
                                <?php echo esc_html( mb_strtoupper( mb_substr( $tapis['name'], 0, 2 ) ) ); ?>
                            </span>
                        <?php endif; ?>
                        <span class="mova-tc-card-name"><?php echo esc_html( $tapis['name'] ); ?></span>
                    </button>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Colonne 2 : preview + infos -->
            <div class="mova-tc-col-preview">
                <div class="mova-tc-layers" id="mova-tc-layers">
                    <img src="" alt="" class="mova-tc-layer mova-tc-layer--fond" id="mova-tc-layer-fond" />
                    <!-- Les overlays par zone sont injectés dynamiquement par JS -->
                </div>
                <div class="mova-tc-panel">
                    <h3 class="mova-tc-tapis-name" id="mova-tc-tapis-name"></h3>
                    <div class="mova-tc-section">
                        <h4 class="mova-tc-section-title">Aperçu sur le modèle</h4>
                        <div class="mova-tc-piscines" id="mova-tc-piscines"></div>
                    </div>
                </div>
            </div>

        </div>
    </div>

    <?php
    return ob_get_clean();
}
add_shortcode( 'mova_tapis_catalog', 'mova_tapis_catalog_shortcode' );
