<?php
// Empêcher l'accès direct
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/* =============================================
   AJAX Handler — Filtrage inspirations
   ============================================= */
function mova_inspirations_ajax() {
    check_ajax_referer( 'mova_inspirations_nonce', 'nonce' );

    $categories = isset( $_POST['categories'] ) ? array_map( 'sanitize_text_field', (array) $_POST['categories'] ) : array();
    $page       = isset( $_POST['page'] )     ? max( 1, intval( $_POST['page'] ) )     : 1;
    $per_page   = isset( $_POST['per_page'] ) ? max( 1, intval( $_POST['per_page'] ) ) : 12;

    $tax_query = array();
    if ( ! empty( $categories ) ) {
        $tax_query[] = array(
            'taxonomy' => 'categorie_inspiration',
            'field'    => 'slug',
            'terms'    => $categories,
        );
    }

    $args = array(
        'post_type'      => 'inspiration',
        'posts_per_page' => $per_page,
        'paged'          => $page,
        'post_status'    => 'publish',
        'meta_key'       => 'ordre_affichage',
        'orderby'        => 'meta_value_num',
        'order'          => 'ASC',
    );

    if ( ! empty( $tax_query ) ) {
        $args['tax_query'] = $tax_query;
    }

    $query = new WP_Query( $args );
    $items = array();

    if ( $query->have_posts() ) {
        while ( $query->have_posts() ) {
            $query->the_post();
            $post_id = get_the_ID();

            $thumbnail_full = get_the_post_thumbnail_url( $post_id, 'full' );
            $thumbnail_grid = get_the_post_thumbnail_url( $post_id, 'large' );
            $taille         = get_field( 'taille_affichage', $post_id ) ?: 'normal';
            $legende        = get_field( 'legende', $post_id ) ?: '';
            $credit         = get_field( 'credit_photo', $post_id ) ?: '';

            $piscine_link = '';
            $piscine_name = '';
            $piscine = get_field( 'piscine_associee', $post_id );
            if ( ! empty( $piscine ) ) {
                $p = is_array( $piscine ) ? $piscine[0] : $piscine;
                $piscine_link = get_permalink( $p );
                $piscine_name = get_the_title( $p );
            }

            $cat_terms = wp_get_post_terms( $post_id, 'categorie_inspiration', array( 'fields' => 'slugs' ) );
            $cats      = ( ! is_wp_error( $cat_terms ) ) ? $cat_terms : array();

            $items[] = array(
                'id'            => $post_id,
                'thumbnail'     => $thumbnail_grid ?: '',
                'thumbnail_full'=> $thumbnail_full ?: '',
                'taille'        => $taille,
                'legende'       => $legende,
                'credit'        => $credit,
                'piscine_link'  => $piscine_link,
                'piscine_name'  => $piscine_name,
                'categories'    => $cats,
            );
        }
        wp_reset_postdata();
    }

    wp_send_json_success( array(
        'items'   => $items,
        'total'   => (int) $query->found_posts,
        'hasMore' => $page < $query->max_num_pages,
    ) );
}
add_action( 'wp_ajax_mova_inspirations', 'mova_inspirations_ajax' );
add_action( 'wp_ajax_nopriv_mova_inspirations', 'mova_inspirations_ajax' );


/* =============================================
   Shortcode — [mova_inspirations]
   ============================================= */
function mova_inspirations_shortcode( $atts ) {
    $atts = shortcode_atts( array(
        'per_page' => 12,
    ), $atts, 'mova_inspirations' );

    $per_page = intval( $atts['per_page'] );

    // Récupérer les catégories pour les filtres
    $categories = get_terms( array(
        'taxonomy'   => 'categorie_inspiration',
        'hide_empty' => true,
    ) );

    // Requête initiale
    $args = array(
        'post_type'      => 'inspiration',
        'posts_per_page' => $per_page,
        'paged'          => 1,
        'post_status'    => 'publish',
        'meta_key'       => 'ordre_affichage',
        'orderby'        => 'meta_value_num',
        'order'          => 'ASC',
    );

    $query = new WP_Query( $args );
    $total = (int) $query->found_posts;
    $has_more = 1 < $query->max_num_pages;

    // Assets
    wp_enqueue_style( 'mova-inspirations-style', get_stylesheet_directory_uri() . '/assets/css/inspirations.css', array(), '1.0.0' );
    wp_enqueue_script( 'mova-inspirations-script', get_stylesheet_directory_uri() . '/assets/js/inspirations.js', array(), '1.0.0', true );

    wp_localize_script( 'mova-inspirations-script', 'movaInspirations', array(
        'ajaxUrl' => admin_url( 'admin-ajax.php' ),
        'nonce'   => wp_create_nonce( 'mova_inspirations_nonce' ),
        'perPage' => $per_page,
    ) );

    ob_start(); ?>

    <div class="mova-insp" data-total="<?php echo esc_attr( $total ); ?>">

        <?php if ( ! empty( $categories ) && ! is_wp_error( $categories ) ) : ?>
        <div class="mova-insp-filters">
            <button class="mova-insp-filter active" data-slug="">Toutes</button>
            <?php foreach ( $categories as $cat ) : ?>
                <button class="mova-insp-filter" data-slug="<?php echo esc_attr( $cat->slug ); ?>">
                    <?php echo esc_html( $cat->name ); ?>
                </button>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <div class="mova-insp-grid" id="mova-insp-grid">
            <?php if ( $query->have_posts() ) : ?>
                <?php while ( $query->have_posts() ) : $query->the_post();
                    $post_id        = get_the_ID();
                    $thumbnail_full = get_the_post_thumbnail_url( $post_id, 'full' );
                    $thumbnail_grid = get_the_post_thumbnail_url( $post_id, 'large' );
                    $taille         = get_field( 'taille_affichage', $post_id ) ?: 'normal';
                    $legende        = get_field( 'legende', $post_id ) ?: '';
                    $credit         = get_field( 'credit_photo', $post_id ) ?: '';
                    $piscine        = get_field( 'piscine_associee', $post_id );
                    $piscine_link   = '';
                    $piscine_name   = '';
                    if ( ! empty( $piscine ) ) {
                        $p = is_array( $piscine ) ? $piscine[0] : $piscine;
                        $piscine_link = get_permalink( $p );
                        $piscine_name = get_the_title( $p );
                    }
                ?>
                <div class="mova-insp-item mova-insp-item--<?php echo esc_attr( $taille ); ?>"
                     data-full="<?php echo esc_url( $thumbnail_full ); ?>">
                    <img src="<?php echo esc_url( $thumbnail_grid ); ?>"
                         alt="<?php echo esc_attr( $legende ); ?>"
                         loading="lazy" />
                    <div class="mova-insp-overlay">
                        <?php if ( $legende ) : ?>
                            <h4 class="mova-insp-legende"><?php echo esc_html( $legende ); ?></h4>
                        <?php endif; ?>
                        <?php if ( $credit ) : ?>
                            <p class="mova-insp-credit"><?php echo esc_html( $credit ); ?></p>
                        <?php endif; ?>
                        <?php if ( $piscine_link ) : ?>
                            <a href="<?php echo esc_url( $piscine_link ); ?>" class="mova-insp-link">
                                Voir le modèle
                                <svg width="14" height="14" viewBox="0 0 16 16" fill="none"><path d="M6 3l5 5-5 5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endwhile; wp_reset_postdata(); ?>
            <?php endif; ?>
        </div>

        <?php if ( $has_more ) : ?>
        <div class="mova-insp-loadmore-wrap">
            <button class="mova-insp-loadmore" id="mova-insp-loadmore">
                Voir plus d'inspirations
            </button>
        </div>
        <?php endif; ?>

    </div>

    <!-- Lightbox -->
    <div class="mova-insp-lightbox" id="mova-insp-lightbox">
        <button class="mova-insp-lightbox-close" aria-label="Fermer">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none"><path d="M18 6L6 18M6 6l12 12" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
        </button>
        <button class="mova-insp-lightbox-nav mova-insp-lightbox-prev" aria-label="Précédent">
            <svg width="24" height="24" viewBox="0 0 16 16" fill="none"><path d="M10 3L5 8l5 5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
        </button>
        <button class="mova-insp-lightbox-nav mova-insp-lightbox-next" aria-label="Suivant">
            <svg width="24" height="24" viewBox="0 0 16 16" fill="none"><path d="M6 3l5 5-5 5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
        </button>
        <div class="mova-insp-lightbox-content">
            <img src="" alt="" id="mova-insp-lightbox-img" />
            <div class="mova-insp-lightbox-caption" id="mova-insp-lightbox-caption"></div>
        </div>
    </div>

    <?php
    return ob_get_clean();
}
add_shortcode( 'mova_inspirations', 'mova_inspirations_shortcode' );
