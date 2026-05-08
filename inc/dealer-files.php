<?php
// Empêcher l'accès direct
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/* =============================================
   Shortcode — [mova_dealer_files]
   Grille des fiches techniques & dessins par modèle
   (lit le répéteur ACF "detaillant_fichiers")

   Usage :
   [mova_dealer_files]
   [mova_dealer_files order="ASC" category="grands-modeles"]
   ============================================= */
function mova_dealer_files_shortcode( $atts ) {
    $atts = shortcode_atts( array(
        'order'    => 'ASC',   // ASC | DESC
        'category' => '',      // slug de taxonomie categorie_piscine (optionnel)
        'per_page' => -1,      // -1 = tous
    ), $atts, 'mova_dealer_files' );

    // Enqueue assets
    wp_enqueue_style(
        'mova-dealer-files-style',
        get_stylesheet_directory_uri() . '/assets/css/dealer-files.css',
        array(),
        '1.0.0'
    );

    // WP_Query
    $query_args = array(
        'post_type'      => 'piscine',
        'post_status'    => 'publish',
        'posts_per_page' => intval( $atts['per_page'] ),
        'orderby'        => 'title',
        'order'          => sanitize_text_field( $atts['order'] ),
    );

    if ( ! empty( $atts['category'] ) ) {
        $query_args['tax_query'] = array(
            array(
                'taxonomy' => 'categorie_piscine',
                'field'    => 'slug',
                'terms'    => sanitize_text_field( $atts['category'] ),
            ),
        );
    }

    $piscines = new WP_Query( $query_args );

    if ( ! $piscines->have_posts() ) {
        return '<p class="mova-df-empty">Aucun modèle disponible.</p>';
    }

    ob_start();
    ?>
    <div class="mova-df-grid">
    <?php
    while ( $piscines->have_posts() ) :
        $piscines->the_post();
        $post_id  = get_the_ID();
        $titre    = get_the_title();
        $lien     = get_permalink();
        $fichiers = get_field( 'detaillant_fichiers', $post_id );

        // Image de la carte (ACF) ou featured image en fallback
        $img_id  = get_field( 'image_carte', $post_id );
        if ( $img_id ) {
            $img_src = wp_get_attachment_image_url( $img_id, 'medium' );
        } else {
            $img_src = get_the_post_thumbnail_url( $post_id, 'medium' );
        }

        if ( empty( $fichiers ) ) {
            continue; // Ignorer les modèles sans fichiers détaillant
        }

        // Séparer les fichiers par type pour l'affichage
        $pdfs   = array_filter( $fichiers, fn( $f ) => ( $f['type'] ?? 'pdf' ) === 'pdf' );
        $zips   = array_filter( $fichiers, fn( $f ) => ( $f['type'] ?? '' ) === 'zip' );
        $images = array_filter( $fichiers, fn( $f ) => ( $f['type'] ?? '' ) === 'image' );
        ?>
        <div class="mova-df-card">

            <?php if ( $img_src ) : ?>
            <div class="mova-df-card-img">
                <img src="<?php echo esc_url( $img_src ); ?>" alt="<?php echo esc_attr( $titre ); ?>" loading="lazy">
            </div>
            <?php endif; ?>

            <div class="mova-df-card-body">
                <h3 class="mova-df-card-title">
                    <a href="<?php echo esc_url( $lien ); ?>" target="_blank" rel="noopener">
                        <?php echo esc_html( $titre ); ?>
                    </a>
                </h3>

                <?php if ( ! empty( $pdfs ) ) : ?>
                <div class="mova-df-group">
                    <span class="mova-df-group-label">
                        <?php echo mova_df_icon( 'pdf' ); ?>
                        PDF
                    </span>
                    <ul class="mova-df-list">
                        <?php foreach ( $pdfs as $fichier ) :
                            if ( empty( $fichier['fichier'] ) ) continue;
                            $f_url = esc_url( $fichier['fichier']['url'] );
                            $f_label = esc_html( $fichier['label'] ?: $fichier['fichier']['filename'] );
                            ?>
                        <li>
                            <a href="<?php echo $f_url; ?>" target="_blank" rel="noopener" class="mova-df-link mova-df-link--pdf">
                                <?php echo $f_label; ?>
                            </a>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                <?php endif; ?>

                <?php if ( ! empty( $zips ) ) : ?>
                <div class="mova-df-group">
                    <span class="mova-df-group-label">
                        <?php echo mova_df_icon( 'zip' ); ?>
                        ZIP
                    </span>
                    <ul class="mova-df-list">
                        <?php foreach ( $zips as $fichier ) :
                            if ( empty( $fichier['fichier'] ) ) continue;
                            $f_url = esc_url( $fichier['fichier']['url'] );
                            $f_label = esc_html( $fichier['label'] ?: $fichier['fichier']['filename'] );
                            ?>
                        <li>
                            <a href="<?php echo $f_url; ?>" download class="mova-df-link mova-df-link--zip">
                                <?php echo $f_label; ?>
                            </a>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                <?php endif; ?>

                <?php if ( ! empty( $images ) ) : ?>
                <div class="mova-df-group">
                    <span class="mova-df-group-label">
                        <?php echo mova_df_icon( 'image' ); ?>
                        Images
                    </span>
                    <ul class="mova-df-list">
                        <?php foreach ( $images as $fichier ) :
                            if ( empty( $fichier['fichier'] ) ) continue;
                            $f_id    = $fichier['fichier']['id'];
                            $f_url   = esc_url( wp_get_original_image_url( $f_id ) ?: $fichier['fichier']['url'] );
                            $f_name  = esc_attr( basename( wp_get_original_image_path( $f_id ) ?: $fichier['fichier']['url'] ) );
                            $f_label = esc_html( $fichier['label'] ?: $fichier['fichier']['filename'] );
                            ?>
                        <li>
                            <a href="<?php echo $f_url; ?>" download="<?php echo $f_name; ?>" class="mova-df-link mova-df-link--image">
                                <?php echo $f_label; ?>
                            </a>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                <?php endif; ?>

            </div><!-- .mova-df-card-body -->
        </div><!-- .mova-df-card -->
        <?php
    endwhile;
    wp_reset_postdata();
    ?>
    </div><!-- .mova-df-grid -->
    <?php
    return ob_get_clean();
}
add_shortcode( 'mova_dealer_files', 'mova_dealer_files_shortcode' );

/* ---------------------------------------------
   Helper — icônes SVG par type de fichier
   --------------------------------------------- */
function mova_df_icon( $type ) {
    $icons = array(
        'pdf'   => '<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><polyline points="10 9 9 9 8 9"/></svg>',
        'zip'   => '<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>',
        'image' => '<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>',
    );
    return $icons[ $type ] ?? '';
}
