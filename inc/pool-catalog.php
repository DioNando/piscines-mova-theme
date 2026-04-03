<?php
// Empêcher l'accès direct
if (! defined('ABSPATH')) {
    exit;
}

/* =============================================
   AJAX Handler — Filtrage & pagination
   ============================================= */
function mova_pool_catalog_ajax()
{
    check_ajax_referer('mova_pool_catalog_nonce', 'nonce');

    $categories = isset($_POST['categories']) ? array_map('sanitize_text_field', (array) $_POST['categories']) : array();
    $dimensions = isset($_POST['dimensions']) ? array_map('sanitize_text_field', (array) $_POST['dimensions']) : array();
    $besoins    = isset($_POST['besoins'])    ? array_map('sanitize_text_field', (array) $_POST['besoins'])    : array();
    $page       = isset($_POST['page'])     ? max(1, intval($_POST['page']))     : 1;
    $per_page   = isset($_POST['per_page']) ? max(1, intval($_POST['per_page'])) : 9;

    // Construire la tax_query
    $tax_query = array();

    if (! empty($categories)) {
        $tax_query[] = array(
            'taxonomy' => 'categorie_piscine',
            'field'    => 'slug',
            'terms'    => $categories,
        );
    }

    if (! empty($dimensions)) {
        $tax_query[] = array(
            'taxonomy' => 'dimension_piscine',
            'field'    => 'slug',
            'terms'    => $dimensions,
        );
    }

    if (! empty($besoins)) {
        $tax_query[] = array(
            'taxonomy' => 'besoin_piscine',
            'field'    => 'slug',
            'terms'    => $besoins,
        );
    }

    if (count($tax_query) > 1) {
        $tax_query['relation'] = 'AND';
    }

    $args = array(
        'post_type'      => 'piscine',
        'posts_per_page' => $per_page,
        'paged'          => $page,
        'post_status'    => 'publish',
    );

    if (! empty($tax_query)) {
        $args['tax_query'] = $tax_query;
    }

    $query = new WP_Query($args);
    $pools = array();

    if ($query->have_posts()) {
        while ($query->have_posts()) {
            $query->the_post();
            $post_id = get_the_ID();

            $cat_terms = wp_get_post_terms($post_id, 'categorie_piscine', array('fields' => 'all'));
            $subtitle  = (! is_wp_error($cat_terms) && ! empty($cat_terms)) ? $cat_terms[0]->name : '';

            $thumbnail = get_the_post_thumbnail_url($post_id, 'medium');

            $pools[] = array(
                'id'        => $post_id,
                'titre'     => html_entity_decode(get_the_title()),
                'permalink' => get_permalink($post_id),
                'thumbnail' => $thumbnail ?: '',
                'subtitle'  => $subtitle,
            );
        }
        wp_reset_postdata();
    }

    wp_send_json_success(array(
        'pools'   => $pools,
        'total'   => (int) $query->found_posts,
        'hasMore' => $page < $query->max_num_pages,
    ));
}
add_action('wp_ajax_mova_pool_catalog_filter',        'mova_pool_catalog_ajax');
add_action('wp_ajax_nopriv_mova_pool_catalog_filter', 'mova_pool_catalog_ajax');

/* =============================================
   Shortcode — [mova_pool_catalog]
   ============================================= */
function mova_pool_catalog_shortcode($atts)
{
    $atts = shortcode_atts(array(
        'per_page' => 9,
    ), $atts, 'mova_pool_catalog');

    $per_page = intval($atts['per_page']);

    // 1. Charger les assets
    wp_enqueue_style('mova-pool-catalog-style', get_stylesheet_directory_uri() . '/assets/css/pool-catalog.css', array(), '1.1.0');
    wp_enqueue_script('mova-pool-catalog-script', get_stylesheet_directory_uri() . '/assets/js/pool-catalog.js', array(), '1.1.0', true);

    // 2. Récupérer les termes pour les filtres
    $filter_categories = get_terms(array(
        'taxonomy'   => 'categorie_piscine',
        'hide_empty' => true,
    ));

    $filter_dimensions = get_terms(array(
        'taxonomy'   => 'dimension_piscine',
        'hide_empty' => true,
    ));

    $filter_besoins = get_terms(array(
        'taxonomy'   => 'besoin_piscine',
        'hide_empty' => true,
    ));

    // 3. Passer les paramètres AJAX au JS (pas de données piscines)
    wp_localize_script('mova-pool-catalog-script', 'movaPoolData', array(
        'ajaxUrl' => admin_url('admin-ajax.php'),
        'nonce'   => wp_create_nonce('mova_pool_catalog_nonce'),
        'perPage' => $per_page,
    ));

    // 4. HTML
    ob_start(); ?>

    <div class="mova-pc-container">
        <aside class="mova-pc-sidebar">
            <button class="mova-pc-filter-toggle" id="mova-pc-filter-toggle">
                <span>Filtres</span>
                <svg width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M4 6l4 4 4-4" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                </svg>
            </button>
            <div class="mova-pc-sidebar-content" id="mova-pc-sidebar-content">
                <div class="mova-pc-sidebar-title">Catégories de piscine</div>

                <div class="mova-pc-filter-group" data-filter="categorie">
                    <label class="mova-pc-checkbox">
                        <input type="checkbox" value="" checked>
                        <span>Tous les modèles</span>
                    </label>
                    <?php if (! is_wp_error($filter_categories)) : ?>
                        <?php foreach ($filter_categories as $cat) : ?>
                            <label class="mova-pc-checkbox">
                                <input type="checkbox" value="<?php echo esc_attr($cat->slug); ?>">
                                <span><?php echo esc_html($cat->name); ?></span>
                            </label>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

                <div class="mova-pc-sidebar-title">Dimensions</div>

                <div class="mova-pc-filter-group" data-filter="dimension">
                    <?php if (! is_wp_error($filter_dimensions)) : ?>
                        <?php foreach ($filter_dimensions as $dim) : ?>
                            <label class="mova-pc-checkbox">
                                <input type="checkbox" value="<?php echo esc_attr($dim->slug); ?>">
                                <span><?php echo esc_html($dim->name); ?></span>
                            </label>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

                <?php if (! is_wp_error($filter_besoins) && ! empty($filter_besoins)) : ?>
                <div class="mova-pc-sidebar-title">Quel est votre besoin ?</div>

                <div class="mova-pc-filter-group" data-filter="besoin">
                    <?php foreach ($filter_besoins as $besoin) : ?>
                        <label class="mova-pc-checkbox">
                            <input type="checkbox" value="<?php echo esc_attr($besoin->slug); ?>">
                            <span><?php echo esc_html($besoin->name); ?></span>
                        </label>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
        </aside>

        <div class="mova-pc-main">
            <p class="mova-pc-count" id="mova-pc-count"></p>
            <div class="mova-pc-grid" id="mova-pc-grid"></div>
            <div class="mova-pc-load-more-wrap">
                <button class="mova-pc-load-more" id="mova-pc-load-more" style="display:none;">
                    Charger plus
                    <svg width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M8 3v10M3 8h10" stroke="currentColor" stroke-width="2" stroke-linecap="round" />
                    </svg>
                </button>
            </div>
        </div>
    </div>

<?php
    return ob_get_clean();
}
add_shortcode('mova_pool_catalog', 'mova_pool_catalog_shortcode');
