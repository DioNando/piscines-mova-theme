<?php
// Empêcher l'accès direct
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/* =============================================
   Shortcode — [mova_carrieres_list]
   Affiche la liste des carrières (alternance image/contenu)
   ============================================= */
function mova_carrieres_list_shortcode( $atts ) {
    $args = array(
        'post_type'      => 'carriere',
        'posts_per_page' => -1,
        'meta_key'       => 'ordre_affichage',
        'orderby'        => array('meta_value_num' => 'ASC', 'date' => 'DESC'),
        'order'          => 'ASC',
        'meta_query'     => array([
            'key'     => 'poste_actif',
            'value'   => '1',
            'compare' => '=',
        ]),
    );
    $carrieres = get_posts( $args );
    if ( empty( $carrieres ) ) {
        return '<p>Aucune offre de carrière disponible pour le moment.</p>';
    }
    ob_start();
    ?>
    <div class="mova-carrieres-list">
        <?php foreach ( $carrieres as $index => $carriere ) :
            $salaire = get_field('salaire', $carriere->ID);
            $type_poste = get_field('type_poste', $carriere->ID);
            $horaire = get_field('horaire', $carriere->ID);
            $description = get_field('description_poste', $carriere->ID);
            $image_id = get_field('image_poste', $carriere->ID);
            $image_url = $image_id ? wp_get_attachment_image_url($image_id, 'full') : '';
            $lien_formulaire = get_field('lien_formulaire', $carriere->ID);
            $is_even = $index % 2 === 0;
        ?>
        <div class="mova-carriere-card <?php echo $is_even ? 'even' : 'odd'; ?>">
            <div class="mova-carriere-card-inner">
                <?php if ($is_even): ?>
                    <?php if ($image_url): ?>
                    <div class="mova-carriere-image">
                        <img src="<?php echo esc_url($image_url); ?>" alt="<?php echo esc_attr(get_the_title($carriere->ID)); ?>" />
                    </div>
                    <?php endif; ?>
                <?php endif; ?>
                <div class="mova-carriere-content">
                    <h2 class="mova-carriere-title"> <?php echo esc_html(get_the_title($carriere->ID)); ?> </h2>
                    <ul class="mova-carriere-infos">
                        <?php if ($salaire): ?><li><strong>Salaire</strong> <span><?php echo esc_html($salaire); ?></span></li><?php endif; ?>
                        <?php if ($type_poste): ?><li><strong>Type de poste</strong> <span><?php echo esc_html($type_poste); ?></span></li><?php endif; ?>
                        <?php if ($horaire): ?><li><strong>Horaire</strong> <span><?php echo esc_html($horaire); ?></span></li><?php endif; ?>
                    </ul>
                    <?php if ($description): ?>
                        <div class="mova-carriere-description">
                            <?php echo wpautop($description); ?>
                        </div>
                    <?php endif; ?>
                    <?php if ($lien_formulaire): ?>
                        <a href="<?php echo esc_url($lien_formulaire); ?>" class="mova-carriere-btn" target="_blank" rel="noopener">Voir les détails</a>
                    <?php endif; ?>
                </div>
                <?php if (!$is_even): ?>
                    <?php if ($image_url): ?>
                    <div class="mova-carriere-image">
                        <img src="<?php echo esc_url($image_url); ?>" alt="<?php echo esc_attr(get_the_title($carriere->ID)); ?>" />
                    </div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php
    return ob_get_clean();
}
add_shortcode( 'mova_carrieres_list', 'mova_carrieres_list_shortcode' );

wp_enqueue_style( 'mova-carrieres-list-style', get_stylesheet_directory_uri() . '/assets/css/carrieres-list.css', array(), '1.0.0' );
