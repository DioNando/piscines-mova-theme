<?php
// Empêcher l'accès direct
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

function mova_custom_store_locator_shortcode() {
    // 1. Charger Leaflet depuis un CDN
    wp_enqueue_style( 'leaflet-css', 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.css', array(), '1.9.4' );
    wp_enqueue_script( 'leaflet-js', 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js', array(), '1.9.4', true );

    // 1b. Charger le plugin MarkerCluster
    wp_enqueue_style( 'leaflet-markercluster-css', 'https://unpkg.com/leaflet.markercluster@1.5.3/dist/MarkerCluster.css', array('leaflet-css'), '1.5.3' );
    wp_enqueue_style( 'leaflet-markercluster-default-css', 'https://unpkg.com/leaflet.markercluster@1.5.3/dist/MarkerCluster.Default.css', array('leaflet-css'), '1.5.3' );
    wp_enqueue_script( 'leaflet-markercluster-js', 'https://unpkg.com/leaflet.markercluster@1.5.3/dist/leaflet.markercluster.js', array('leaflet-js'), '1.5.3', true );

    // 2. Charger nos fichiers locaux CSS et JS
    wp_enqueue_style( 'mova-store-locator-style', get_stylesheet_directory_uri() . '/assets/css/store-locator.css', array(), '1.1.0' );
    wp_enqueue_script( 'mova-store-locator-script', get_stylesheet_directory_uri() . '/assets/js/store-locator.js', array('leaflet-js', 'leaflet-markercluster-js'), '1.1.0', true );

    // 3. Récupérer les détaillants depuis la base de données
    $args = array(
        'post_type'      => 'detaillant',
        'posts_per_page' => -1,
        'post_status'    => 'publish',
    );
    $query = new WP_Query( $args );
    
    $detaillants_data = array();
    $provinces_count = array(); // province => nb de détaillants

    if ( $query->have_posts() ) {
        while ( $query->have_posts() ) {
            $query->the_post();
            
            // Récupérer les champs ACF
            $lat = get_field('latitude');
            $lng = get_field('longitude');
            
            // Récupérer la taxonomie province
            $province_terms = wp_get_post_terms(get_the_ID(), 'province');
            $province = !is_wp_error($province_terms) && !empty($province_terms) ? $province_terms[0]->name : '';
            
            if ($province) {
                $provinces_count[$province] = isset($provinces_count[$province]) ? $provinces_count[$province] + 1 : 1;
            }

            // Uniquement si on a des coordonnées GPS valides
            if ( $lat && $lng ) {
                $detaillants_data[] = array(
                    'id'       => get_the_ID(),
                    'nom'      => html_entity_decode(get_the_title()),
                    'adresse'  => get_field('adresse') ?: '',
                    'ville'    => get_field('ville') ?: '',
                    'cp'       => get_field('code_postal') ?: '',
                    'tel'      => get_field('telephone') ?: '',
                    'email'    => get_field('email_contact') ?: '',
                    'site'     => get_field('site_web_url') ?: '',
                    'permalink'=> get_permalink(),
                    'province' => $province,
                    'lat'      => (float) $lat,
                    'lng'      => (float) $lng,
                );
            }
        }
        wp_reset_postdata();
    }
    
    // Trier les provinces par nombre de détaillants décroissant (Québec > Ontario > ...)
    arsort($provinces_count);
    $provinces_list = array_keys($provinces_count);

    // 4. Exporter les données vers le fichier JavaScript
    wp_localize_script( 'mova-store-locator-script', 'movaStoreData', array(
        'stores'    => $detaillants_data,
        'provinces' => $provinces_list,
    ));

    // 5. Structure HTML affichée sur la page
    ob_start(); ?>
    
    <div class="mova-sl-container">
        <div class="mova-sl-sidebar">
            <div class="mova-sl-filters">
                <h3>Trouver un détaillant</h3>
                <div class="mova-sl-input-group">
                    <input type="text" id="mova-sl-search" placeholder="Rechercher une ville, un nom ou code postal...">
                </div>
                <div class="mova-sl-input-group">
                    <select id="mova-sl-province">
                        <option value="">Toutes les provinces</option>
                        <?php foreach($provinces_list as $prov): ?>
                            <option value="<?php echo esc_attr($prov); ?>"><?php echo esc_html($prov); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="mova-sl-input-group mova-sl-radius-group" style="display:none;">
                    <select id="mova-sl-radius">
                        <option value="25">Dans un rayon de 25 km</option>
                        <option value="50">Dans un rayon de 50 km</option>
                        <option value="100" selected>Dans un rayon de 100 km</option>
                        <option value="200">Dans un rayon de 200 km</option>
                    </select>
                </div>
                <div class="mova-sl-input-group">
                    <button id="mova-sl-geolocate" type="button">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="3"/><path d="M12 2v3M12 19v3M2 12h3M19 12h3"/><circle cx="12" cy="12" r="10" stroke-opacity=".3"/></svg>
                        <span class="mova-sl-geolocate-label">Me localiser</span>
                    </button>
                </div>
            </div>
            
            <div class="mova-sl-list" id="mova-sl-list"></div>
        </div>

        <div class="mova-sl-map-wrap">
            <div id="mova-sl-map"></div>
        </div>
    </div>

    <?php
    return ob_get_clean();
}
// Enregistrement du shortcode
add_shortcode('mova_store_locator', 'mova_custom_store_locator_shortcode');