<?php
// Empêcher l'accès direct
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/* =============================================
   Shortcode — [mova_pool_gallery]
   Carrousel média (images + vidéos) depuis ACF
   ============================================= */
function mova_pool_gallery_shortcode( $atts ) {
    $atts = shortcode_atts( array(
        'id'    => 0,
        'field' => 'galerie',
    ), $atts, 'mova_pool_gallery' );

    $post_id = intval( $atts['id'] ) ?: get_the_ID();
    $field   = sanitize_text_field( $atts['field'] );

    if ( ! $post_id ) {
        return '';
    }

    // Récupérer les IDs de la galerie ACF
    $gallery_ids = get_field( $field, $post_id );

    if ( empty( $gallery_ids ) || ! is_array( $gallery_ids ) ) {
        return '';
    }

    // Construire les données médias
    $medias = array();
    foreach ( $gallery_ids as $attachment_id ) {
        // Supporter les IDs bruts ou les tableaux ACF
        $att_id = is_array( $attachment_id ) ? ( $attachment_id['ID'] ?? $attachment_id['id'] ?? 0 ) : intval( $attachment_id );
        if ( ! $att_id ) continue;

        $mime = get_post_mime_type( $att_id );
        $alt  = get_post_meta( $att_id, '_wp_attachment_image_alt', true ) ?: get_the_title( $att_id );

        if ( strpos( $mime, 'video/' ) === 0 ) {
            $medias[] = array(
                'type'      => 'video',
                'src'       => wp_get_attachment_url( $att_id ),
                'thumbnail' => wp_get_attachment_image_url( $att_id, 'thumbnail' ),
                'poster'    => wp_get_attachment_image_url( $att_id, 'large' ),
                'alt'       => $alt,
                'mime'      => $mime,
            );
        } else {
            $medias[] = array(
                'type'      => 'image',
                'src'       => wp_get_attachment_image_url( $att_id, 'full' ),
                'large'     => wp_get_attachment_image_url( $att_id, 'large' ),
                'thumbnail' => wp_get_attachment_image_url( $att_id, 'thumbnail' ),
                'alt'       => $alt,
            );
        }
    }

    if ( empty( $medias ) ) {
        return '';
    }

    // Assets
    wp_enqueue_style( 'mova-pool-gallery-style', get_stylesheet_directory_uri() . '/assets/css/pool-gallery.css', array(), '1.0.0' );
    wp_enqueue_script( 'mova-pool-gallery-script', get_stylesheet_directory_uri() . '/assets/js/pool-gallery.js', array(), '1.0.0', true );

    $gallery_id = 'mova-pg-' . $post_id;

    ob_start(); ?>

    <div class="mova-pg" id="<?php echo esc_attr( $gallery_id ); ?>">

        <!-- Viewer principal -->
        <div class="mova-pg-viewer">
            <?php if ( count( $medias ) > 1 ) : ?>
            <button class="mova-pg-arrow mova-pg-arrow-prev" aria-label="Précédent">
                <svg width="20" height="20" viewBox="0 0 16 16" fill="none"><path d="M10 3L5 8l5 5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
            </button>
            <button class="mova-pg-arrow mova-pg-arrow-next" aria-label="Suivant">
                <svg width="20" height="20" viewBox="0 0 16 16" fill="none"><path d="M6 3l5 5-5 5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
            </button>
            <?php endif; ?>

            <div class="mova-pg-stage">
                <?php foreach ( $medias as $i => $media ) : ?>
                <div class="mova-pg-slide<?php echo $i === 0 ? ' active' : ''; ?>" data-index="<?php echo $i; ?>">
                    <?php if ( $media['type'] === 'video' ) : ?>
                        <video controls preload="metadata" poster="<?php echo esc_url( $media['poster'] ?? '' ); ?>">
                            <source src="<?php echo esc_url( $media['src'] ); ?>" type="<?php echo esc_attr( $media['mime'] ); ?>">
                        </video>
                    <?php else : ?>
                        <img src="<?php echo esc_url( $media['src'] ); ?>"
                             alt="<?php echo esc_attr( $media['alt'] ); ?>"
                             loading="<?php echo $i === 0 ? 'eager' : 'lazy'; ?>" />
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Vignettes -->
        <?php if ( count( $medias ) > 1 ) : ?>
        <div class="mova-pg-thumbs">
            <div class="mova-pg-thumbs-track">
                <?php foreach ( $medias as $i => $media ) : ?>
                <button class="mova-pg-thumb<?php echo $i === 0 ? ' active' : ''; ?>"
                        data-index="<?php echo $i; ?>"
                        aria-label="Média <?php echo $i + 1; ?>">
                    <?php if ( $media['type'] === 'video' ) : ?>
                        <span class="mova-pg-thumb-play">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="white"><path d="M8 5v14l11-7z"/></svg>
                        </span>
                    <?php endif; ?>
                    <img src="<?php echo esc_url( $media['thumbnail'] ); ?>"
                         alt="<?php echo esc_attr( $media['alt'] ); ?>"
                         loading="lazy" />
                </button>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

    </div>

    <?php
    return ob_get_clean();
}
add_shortcode( 'mova_pool_gallery', 'mova_pool_gallery_shortcode' );
