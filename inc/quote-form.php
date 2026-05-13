<?php
// Empêcher l'accès direct
if (! defined('ABSPATH')) {
    exit;
}

/* =============================================
   Shortcode — [mova_quote_form]
   Formulaire de demande de devis
   ============================================= */
function mova_quote_form_shortcode($atts)
{
    $atts = shortcode_atts(array(
        'model'   => '',
        'couleur' => '',
    ), $atts, 'mova_quote_form');

    // Pré-remplissage via query params (depuis le configurateur)
    $preselect_model   = sanitize_text_field($atts['model'] ?: ($_GET['model'] ?? ''));
    $preselect_couleur = sanitize_text_field($atts['couleur'] ?: ($_GET['couleur'] ?? ''));

    // Pré-remplissage options AquaCove (depuis le configurateur)
    $preselect_options = sanitize_text_field($_GET['options'] ?? '');

    // Pré-remplissage tapis AquaCove par zone (depuis le configurateur)
    $zone_labels = array(
        'marches'  => __( 'Marches', 'piscines-mova' ),
        'bancs'    => __( 'Bancs', 'piscines-mova' ),
        'terrasse' => __( 'Terrasse', 'piscines-mova' ),
    );
    $preselect_tapis = array();
    foreach ($zone_labels as $zone_key => $zone_label) {
        $val = sanitize_text_field($_GET['tapis_' . $zone_key] ?? '');
        if ($val) {
            $preselect_tapis[$zone_key] = $val;
        }
    }

    // Récupérer les modèles de tapis si au moins un est pré-sélectionné
    $tapis_terms_map = array();
    if (! empty($preselect_tapis)) {
        $all_tapis_slugs = array_values($preselect_tapis);
        $tapis_terms = get_terms(array(
            'taxonomy'   => 'modele_tapis',
            'slug'       => $all_tapis_slugs,
            'hide_empty' => false,
        ));
        if (! is_wp_error($tapis_terms)) {
            foreach ($tapis_terms as $tt) {
                $swatch_id  = get_field('swatch_tapis', $tt);
                $swatch_url = $swatch_id ? wp_get_attachment_image_url($swatch_id, 'thumbnail') : '';
                $tapis_terms_map[$tt->slug] = array(
                    'name'   => $tt->name,
                    'swatch' => $swatch_url,
                );
            }
        }
    }

    // Récupérer les modèles de piscines (CPT piscine)
    $piscines = get_posts(array(
        'post_type'      => 'piscine',
        'posts_per_page' => -1,
        'post_status'    => 'publish',
    ));

    // Tri par ordre de grandeur numérique (ex: 8x10 < 10x20 < 12x34)
    usort($piscines, function ( $a, $b ) {
        preg_match('/^(\d+)[x×](\d+)/i', $a->post_title, $ma);
        preg_match('/^(\d+)[x×](\d+)/i', $b->post_title, $mb);
        $aw = isset($ma[1]) ? (int) $ma[1] : 0;
        $al = isset($ma[2]) ? (int) $ma[2] : 0;
        $bw = isset($mb[1]) ? (int) $mb[1] : 0;
        $bl = isset($mb[2]) ? (int) $mb[2] : 0;
        return ( $aw !== $bw ) ? $aw - $bw : $al - $bl;
    });

    // Récupérer les couleurs (taxonomie couleur_piscine) — enfants seulement
    $couleurs = get_terms(array(
        'taxonomy'   => 'couleur_piscine',
        'hide_empty' => false,
        'orderby'    => 'name',
        'order'      => 'ASC',
    ));
    if (is_wp_error($couleurs)) {
        $couleurs = array();
    }
    // Masquer les termes parents (collections)
    $couleurs = array_values(array_filter($couleurs, function ($c) {
        return $c->parent !== 0;
    }));
    // Trier par ordre d'affichage défini dans le BO (champ ACF "ordre")
    usort($couleurs, function ($a, $b) {
        $oa = get_field('ordre', 'couleur_piscine_' . $a->term_id);
        $ob = get_field('ordre', 'couleur_piscine_' . $b->term_id);
        $oa = ($oa !== '' && $oa !== null && $oa !== false) ? (int) $oa : PHP_INT_MAX;
        $ob = ($ob !== '' && $ob !== null && $ob !== false) ? (int) $ob : PHP_INT_MAX;
        return $oa !== $ob ? $oa - $ob : strcmp($a->name, $b->name);
    });

    // Récupérer les provinces (taxonomie province)
    $provinces = get_terms(array(
        'taxonomy'   => 'province',
        'hide_empty' => false,
        'orderby'    => 'name',
        'order'      => 'ASC',
    ));
    if (is_wp_error($provinces)) {
        $provinces = array();
    }

    // Assets
    wp_enqueue_style('mova-quote-form-style', get_stylesheet_directory_uri() . '/assets/css/quote-form.css', array(), '1.0.0');
    wp_enqueue_script('mova-quote-form-script', get_stylesheet_directory_uri() . '/assets/js/quote-form.js', array(), '1.0.0', true);

    wp_localize_script('mova-quote-form-script', 'movaQuoteForm', array(
        'ajaxUrl' => admin_url('admin-ajax.php'),
        'nonce'   => wp_create_nonce('mova_quote_form_nonce'),
    ));

    ob_start(); ?>

    <div class="mova-qf" id="mova-qf">

        <form class="mova-qf-form" id="mova-qf-form" novalidate>
            <input type="hidden" name="action" value="mova_submit_quote" />
            <input type="hidden" name="nonce" value="<?php echo esc_attr(wp_create_nonce('mova_quote_form_nonce')); ?>" />

            <!-- Honeypot anti-spam -->
            <div style="position:absolute;left:-9999px;" aria-hidden="true">
                <label for="mova_qf_website"><?php esc_html_e( 'Ne pas remplir', 'piscines-mova' ); ?></label>
                <input type="text" name="website" id="mova_qf_website" tabindex="-1" autocomplete="off" />
            </div>

            <!-- ====== Section : Coordonnées ====== -->
            <fieldset class="mova-qf-fieldset">
                <legend class="mova-qf-legend"><?php esc_html_e( 'Vos coordonnées', 'piscines-mova' ); ?></legend>

                <div class="mova-qf-row">
                    <div class="mova-qf-field">
                        <label for="mova_qf_prenom"><?php esc_html_e( 'Prénom', 'piscines-mova' ); ?> <span class="mova-qf-req">*</span></label>
                        <input type="text" id="mova_qf_prenom" name="prenom" required />
                    </div>
                    <div class="mova-qf-field">
                        <label for="mova_qf_nom"><?php esc_html_e( 'Nom', 'piscines-mova' ); ?> <span class="mova-qf-req">*</span></label>
                        <input type="text" id="mova_qf_nom" name="nom" required />
                    </div>
                </div>

                <div class="mova-qf-row">
                    <div class="mova-qf-field">
                        <label for="mova_qf_courriel"><?php esc_html_e( 'Courriel', 'piscines-mova' ); ?> <span class="mova-qf-req">*</span></label>
                        <input type="email" id="mova_qf_courriel" name="courriel" required />
                    </div>
                    <div class="mova-qf-field">
                        <label for="mova_qf_telephone"><?php esc_html_e( 'Téléphone', 'piscines-mova' ); ?> <span class="mova-qf-req">*</span></label>
                        <input type="tel" id="mova_qf_telephone" name="telephone" required />
                    </div>
                </div>

                <div class="mova-qf-row">
                    <div class="mova-qf-field">
                        <label for="mova_qf_moment"><?php esc_html_e( 'Meilleur moment pour vous rejoindre', 'piscines-mova' ); ?></label>
                        <select id="mova_qf_moment" name="moment">
                            <option value=""><?php esc_html_e( '— Sélectionner —', 'piscines-mova' ); ?></option>
                            <option value="Avant-midi"><?php esc_html_e( 'Avant-midi', 'piscines-mova' ); ?></option>
                            <option value="Après-midi"><?php esc_html_e( 'Après-midi', 'piscines-mova' ); ?></option>
                            <option value="En soirée"><?php esc_html_e( 'En soirée', 'piscines-mova' ); ?></option>
                        </select>
                    </div>
                    <div class="mova-qf-field">
                        <label for="mova_qf_moyen"><?php esc_html_e( 'Meilleure façon de vous rejoindre', 'piscines-mova' ); ?></label>
                        <select id="mova_qf_moyen" name="moyen_contact">
                            <option value=""><?php esc_html_e( '— Sélectionner —', 'piscines-mova' ); ?></option>
                            <option value="Courriel"><?php esc_html_e( 'Courriel', 'piscines-mova' ); ?></option>
                            <option value="Téléphone"><?php esc_html_e( 'Téléphone', 'piscines-mova' ); ?></option>
                            <option value="Texto"><?php esc_html_e( 'Texto', 'piscines-mova' ); ?></option>
                        </select>
                    </div>
                </div>
            </fieldset>

            <!-- ====== Section : Adresse ====== -->
            <fieldset class="mova-qf-fieldset">
                <legend class="mova-qf-legend"><?php esc_html_e( 'Votre adresse', 'piscines-mova' ); ?></legend>

                <div class="mova-qf-field mova-qf-field--full">
                    <label for="mova_qf_adresse"><?php esc_html_e( 'Adresse', 'piscines-mova' ); ?></label>
                    <input type="text" id="mova_qf_adresse" name="adresse" />
                </div>

                <div class="mova-qf-row mova-qf-row--3">
                    <div class="mova-qf-field">
                        <label for="mova_qf_ville"><?php esc_html_e( 'Ville', 'piscines-mova' ); ?></label>
                        <input type="text" id="mova_qf_ville" name="ville" />
                    </div>
                    <div class="mova-qf-field">
                        <label for="mova_qf_province"><?php esc_html_e( 'Province', 'piscines-mova' ); ?></label>
                        <select id="mova_qf_province" name="province">
                            <option value=""><?php esc_html_e( '— Sélectionner —', 'piscines-mova' ); ?></option>
                            <?php foreach ($provinces as $prov) : ?>
                                <option value="<?php echo esc_attr($prov->name); ?>"><?php echo esc_html($prov->name); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mova-qf-field">
                        <label for="mova_qf_cp"><?php esc_html_e( 'Code postal', 'piscines-mova' ); ?></label>
                        <input type="text" id="mova_qf_cp" name="code_postal" />
                    </div>
                </div>
            </fieldset>

            <!-- ====== Section : Projet ====== -->
            <fieldset class="mova-qf-fieldset">
                <legend class="mova-qf-legend"><?php esc_html_e( 'Votre projet', 'piscines-mova' ); ?></legend>

                <!-- Modèles (checkboxes) -->
                <div class="mova-qf-field mova-qf-field--full">
                    <label><?php esc_html_e( 'Sélectionnez le ou les modèles de piscines qui vous intéressent :', 'piscines-mova' ); ?></label>
                    <div class="mova-qf-checkboxes" id="mova-qf-modeles">
                        <?php foreach ($piscines as $piscine) :
                            $slug         = $piscine->post_name;
                            $checked      = ($preselect_model === $slug) ? 'checked' : '';
                            $cat_terms    = wp_get_post_terms( $piscine->ID, 'categorie_piscine', array( 'fields' => 'names' ) );
                            $cat_name     = ( ! is_wp_error( $cat_terms ) && ! empty( $cat_terms ) ) ? $cat_terms[0] : '';
                            $gamme_terms  = wp_get_post_terms( $piscine->ID, 'gamme_piscine', array( 'fields' => 'slugs' ) );
                            $is_signature = ( ! is_wp_error( $gamme_terms ) && in_array( 'signature', $gamme_terms, true ) );
                        ?>
                            <label class="mova-qf-checkbox-label">
                                <input type="checkbox" name="modeles[]" value="<?php echo esc_attr($slug); ?>" <?php echo $checked; ?> />
                                <span class="mova-qf-checkbox-content">
                                    <span class="mova-qf-checkbox-name-row">
                                        <span class="mova-qf-checkbox-name"><?php echo esc_html($piscine->post_title); ?></span>
                                        <?php if ( $is_signature ) : ?>
                                            <span class="mova-qf-badge-signature"><?php esc_html_e( 'Signature', 'piscines-mova' ); ?></span>
                                        <?php endif; ?>
                                    </span>
                                    <?php if ( $cat_name ) : ?>
                                        <span class="mova-qf-checkbox-cat"><?php echo esc_html( $cat_name ); ?></span>
                                    <?php endif; ?>
                                </span>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Couleur (pastilles) -->
                <div class="mova-qf-field mova-qf-field--full">
                    <label><?php esc_html_e( 'Couleur de la piscine', 'piscines-mova' ); ?></label>
                    <div class="mova-qf-swatches" id="mova-qf-swatches-couleur" role="radiogroup" aria-label="<?php esc_attr_e( 'Couleur de la piscine', 'piscines-mova' ); ?>">
                        <?php foreach ($couleurs as $couleur) :
                            $swatch_id  = get_field('swatch_couleur', $couleur);
                            $swatch_url = $swatch_id ? wp_get_attachment_image_url($swatch_id, 'thumbnail') : '';
                            $checked    = ($preselect_couleur === $couleur->slug);
                        ?>
                            <label class="mova-qf-swatch-label">
                                <input type="radio" name="couleur" value="<?php echo esc_attr($couleur->slug); ?>" <?php echo $checked ? 'checked' : ''; ?> />
                                <span class="mova-qf-swatch-img-wrap">
                                    <?php if ($swatch_url) : ?>
                                        <img src="<?php echo esc_url($swatch_url); ?>" alt="" />
                                    <?php else : ?>
                                        <span class="mova-qf-swatch-placeholder"><?php echo esc_html(mb_substr($couleur->name, 0, 1)); ?></span>
                                    <?php endif; ?>
                                </span>
                                <span class="mova-qf-swatch-name"><?php echo esc_html($couleur->name); ?></span>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Type d'installation -->
                <div class="mova-qf-row">
                    <div class="mova-qf-field">
                        <label for="mova_qf_installation"><?php esc_html_e( "Type d'installation", 'piscines-mova' ); ?></label>
                        <select id="mova_qf_installation" name="type_installation">
                            <option value=""><?php esc_html_e( '— Sélectionner —', 'piscines-mova' ); ?></option>
                            <option value="Clé en main"><?php esc_html_e( 'Clé en main', 'piscines-mova' ); ?></option>
                            <option value="Auto-installation"><?php esc_html_e( 'Auto-installation', 'piscines-mova' ); ?></option>
                        </select>
                    </div>
                </div>

                <?php if (! empty($preselect_tapis) || $preselect_options) : ?>
                    <!-- ====== Sélections AquaCove (depuis le configurateur) ====== -->
                    <div class="mova-qf-aquacove-summary">
                        <p class="mova-qf-aquacove-title"><?php esc_html_e( 'Sélections AquaCove', 'piscines-mova' ); ?></p>

                        <?php if (! empty($preselect_tapis)) : ?>
                            <div class="mova-qf-aquacove-items">
                                <?php foreach ($preselect_tapis as $zone_key => $tapis_slug) :
                                    $tapis_entry  = isset($tapis_terms_map[$tapis_slug]) ? $tapis_terms_map[$tapis_slug] : array('name' => $tapis_slug, 'swatch' => '');
                                    $tapis_name   = $tapis_entry['name'];
                                    $tapis_swatch = $tapis_entry['swatch'];
                                    $zone_name    = isset($zone_labels[$zone_key]) ? $zone_labels[$zone_key] : $zone_key;
                                ?>
                                    <div class="mova-qf-aquacove-item">
                                        <?php if ($tapis_swatch) : ?>
                                            <img class="mova-qf-aquacove-swatch" src="<?php echo esc_url($tapis_swatch); ?>" alt="<?php echo esc_attr($tapis_name); ?>" />
                                        <?php endif; ?>
                                        <span class="mova-qf-aquacove-zone"><?php echo esc_html($zone_name); ?> :</span>
                                        <span class="mova-qf-aquacove-value"><?php echo esc_html($tapis_name); ?></span>
                                        <input type="hidden" name="tapis_<?php echo esc_attr($zone_key); ?>" value="<?php echo esc_attr($tapis_slug); ?>" />
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>

                        <?php if ($preselect_options) : ?>
                            <div class="mova-qf-aquacove-items">
                                <div class="mova-qf-aquacove-item">
                                    <span class="mova-qf-aquacove-zone"><?php esc_html_e( 'Options', 'piscines-mova' ); ?> :</span>
                                    <span class="mova-qf-aquacove-value"><?php echo esc_html(str_replace(',', ', ', $preselect_options)); ?></span>
                                    <input type="hidden" name="options_aquacove" value="<?php echo esc_attr($preselect_options); ?>" />
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>

                <div class="mova-qf-row mova-qf-row--2">
                    <div class="mova-qf-field">
                        <label for="mova_qf_date"><?php esc_html_e( 'Où en êtes-vous dans vos démarches?', 'piscines-mova' ); ?></label>
                        <select id="mova_qf_date" name="date_projet">
                            <option value=""><?php esc_html_e( '— Sélectionner —', 'piscines-mova' ); ?></option>
                            <option value="Je suis curieux / j'explore les options"><?php esc_html_e( "Je suis curieux / j'explore les options", 'piscines-mova' ); ?></option>
                            <option value="J'aimerais me baigner cet été"><?php esc_html_e( "J'aimerais me baigner cet été", 'piscines-mova' ); ?></option>
                            <option value="J'ai un projet concret pour cette année"><?php esc_html_e( "J'ai un projet concret pour cette année", 'piscines-mova' ); ?></option>
                            <option value="Je planifie pour l'an prochain"><?php esc_html_e( "Je planifie pour l'an prochain", 'piscines-mova' ); ?></option>
                            <option value="Je suis prêt à acheter"><?php esc_html_e( 'Je suis prêt à acheter', 'piscines-mova' ); ?></option>
                        </select>
                    </div>
                    <div class="mova-qf-field">
                        <label for="mova_qf_date_concrete"><?php esc_html_e( 'Date souhaitée', 'piscines-mova' ); ?> <span class="mova-qf-optional"><?php esc_html_e( '(optionnel)', 'piscines-mova' ); ?></span></label>
                        <input type="date" id="mova_qf_date_concrete" name="date_concrete" />
                    </div>
                </div>

                <div class="mova-qf-field mova-qf-field--full">
                    <label for="mova_qf_source"><?php esc_html_e( 'Comment avez-vous entendu parler de nous?', 'piscines-mova' ); ?></label>
                    <select id="mova_qf_source" name="source">
                        <option value=""><?php esc_html_e( '— Sélectionner —', 'piscines-mova' ); ?></option>
                        <option value="Radio"><?php esc_html_e( 'Radio', 'piscines-mova' ); ?></option>
                        <option value="Télévision"><?php esc_html_e( 'Télévision', 'piscines-mova' ); ?></option>
                        <option value="J'ai vu le produit chez quelqu'un"><?php esc_html_e( "J'ai vu le produit chez quelqu'un", 'piscines-mova' ); ?></option>
                        <option value="J'ai visité un magasin spécialisé"><?php esc_html_e( "J'ai visité un magasin spécialisé", 'piscines-mova' ); ?></option>
                        <option value="Publication d'un influenceur"><?php esc_html_e( "Publication d'un influenceur", 'piscines-mova' ); ?></option>
                        <option value="Google"><?php esc_html_e( 'Google', 'piscines-mova' ); ?></option>
                        <option value="Réseaux sociaux"><?php esc_html_e( 'Réseaux sociaux', 'piscines-mova' ); ?></option>
                        <option value="Journal"><?php esc_html_e( 'Journal', 'piscines-mova' ); ?></option>
                    </select>
                </div>

                <div class="mova-qf-field mova-qf-field--full">
                    <label for="mova_qf_commentaires"><?php esc_html_e( 'Demandes additionnelles et/ou commentaires', 'piscines-mova' ); ?></label>
                    <textarea id="mova_qf_commentaires" name="commentaires" rows="4"></textarea>
                </div>
            </fieldset>

            <!-- ====== Consentements ====== -->
            <fieldset class="mova-qf-fieldset mova-qf-fieldset--consent">
                <label class="mova-qf-checkbox-label">
                    <input type="checkbox" name="accord_coordonnees" value="1" required />
                    <span><?php esc_html_e( "J'accepte que mes coordonnées soient remises au détaillant le plus près de mon domicile et à ses partenaires", 'piscines-mova' ); ?> <span class="mova-qf-req">*</span></span>
                </label>
                <label class="mova-qf-checkbox-label">
                    <input type="checkbox" name="infolettre" value="1" />
                    <span><?php esc_html_e( "J'accepte de recevoir des courriers électroniques promotionnels.", 'piscines-mova' ); ?></span>
                </label>
                <p class="mova-qf-note"><?php esc_html_e( "* Les champs marqués d'un astérisque sont obligatoires.", 'piscines-mova' ); ?></p>
            </fieldset>

            <!-- ====== Submit ====== -->
            <div class="mova-qf-submit-wrap">
                <button type="submit" class="mova-qf-submit" id="mova-qf-submit"><?php esc_html_e( 'Envoyer', 'piscines-mova' ); ?></button>
            </div>

            <!-- Messages -->
            <div class="mova-qf-message" id="mova-qf-message" role="alert" aria-live="polite"></div>

        </form>

    </div>

<?php
    return ob_get_clean();
}
add_shortcode('mova_quote_form', 'mova_quote_form_shortcode');


/* =============================================
   AJAX Handler — Soumission du formulaire
   ============================================= */
function mova_handle_quote_submission()
{
    // Vérifier le nonce
    if (! isset($_POST['nonce']) || ! wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['nonce'])), 'mova_quote_form_nonce')) {
        wp_send_json_error(array('message' => __( 'Erreur de sécurité. Veuillez rafraîchir la page et réessayer.', 'piscines-mova' )));
    }

    // Honeypot
    if (! empty($_POST['website'])) {
        wp_send_json_success(array('message' => __( 'Merci! Votre demande a été envoyée.', 'piscines-mova' )));
    }

    // Sanitisation
    $prenom           = sanitize_text_field(wp_unslash($_POST['prenom'] ?? ''));
    $nom              = sanitize_text_field(wp_unslash($_POST['nom'] ?? ''));
    $courriel         = sanitize_email(wp_unslash($_POST['courriel'] ?? ''));
    $telephone        = sanitize_text_field(wp_unslash($_POST['telephone'] ?? ''));
    $moment           = sanitize_text_field(wp_unslash($_POST['moment'] ?? ''));
    $moyen_contact    = sanitize_text_field(wp_unslash($_POST['moyen_contact'] ?? ''));
    $adresse          = sanitize_text_field(wp_unslash($_POST['adresse'] ?? ''));
    $ville            = sanitize_text_field(wp_unslash($_POST['ville'] ?? ''));
    $province         = sanitize_text_field(wp_unslash($_POST['province'] ?? ''));
    $code_postal      = sanitize_text_field(wp_unslash($_POST['code_postal'] ?? ''));
    $couleur          = sanitize_text_field(wp_unslash($_POST['couleur'] ?? ''));
    $type_installation = sanitize_text_field(wp_unslash($_POST['type_installation'] ?? ''));
    $date_projet      = sanitize_text_field(wp_unslash($_POST['date_projet'] ?? ''));
    $date_concrete    = sanitize_text_field(wp_unslash($_POST['date_concrete'] ?? ''));
    $source           = sanitize_text_field(wp_unslash($_POST['source'] ?? ''));
    $commentaires     = sanitize_textarea_field(wp_unslash($_POST['commentaires'] ?? ''));
    $accord           = ! empty($_POST['accord_coordonnees']);
    $infolettre       = ! empty($_POST['infolettre']);

    // Modèles (tableau)
    $modeles_raw = isset($_POST['modeles']) && is_array($_POST['modeles']) ? $_POST['modeles'] : array();
    $modeles = array_map('sanitize_text_field', array_map('wp_unslash', $modeles_raw));

    // Tapis AquaCove par zone
    $tapis_zones = array('marches', 'bancs', 'terrasse');
    $tapis_selections = array();
    foreach ($tapis_zones as $tz) {
        $val = sanitize_text_field(wp_unslash($_POST['tapis_' . $tz] ?? ''));
        if ($val) {
            $tapis_selections[$tz] = $val;
        }
    }

    // Options AquaCove
    $options_aquacove = sanitize_text_field(wp_unslash($_POST['options_aquacove'] ?? ''));

    // Validation
    $errors = array();
    if (empty($prenom))   $errors[] = __( 'Le prénom est requis.', 'piscines-mova' );
    if (empty($nom))      $errors[] = __( 'Le nom est requis.', 'piscines-mova' );
    if (! is_email($courriel)) $errors[] = __( 'Veuillez entrer une adresse courriel valide.', 'piscines-mova' );
    if (empty($telephone)) $errors[] = __( 'Le téléphone est requis.', 'piscines-mova' );
    if (! $accord)          $errors[] = __( 'Vous devez accepter le partage de vos coordonnées.', 'piscines-mova' );

    if (! empty($errors)) {
        wp_send_json_error(array('message' => implode('<br>', $errors)));
    }

    // Enregistrer la demande en base de données (CPT demande_devis)
    $modeles_list = ! empty($modeles) ? implode(', ', $modeles) : '—';

    $post_id = wp_insert_post(array(
        'post_type'   => 'demande_devis',
        'post_title'  => 'Devis — ' . $prenom . ' ' . $nom,
        'post_status' => 'publish',
    ));

    if ($post_id && ! is_wp_error($post_id)) {
        update_field('field_devis_prenom',           $prenom, $post_id);
        update_field('field_devis_nom',              $nom, $post_id);
        update_field('field_devis_courriel',         $courriel, $post_id);
        update_field('field_devis_telephone',        $telephone, $post_id);
        update_field('field_devis_moment',           $moment, $post_id);
        update_field('field_devis_moyen_contact',    $moyen_contact, $post_id);
        update_field('field_devis_adresse',          $adresse, $post_id);
        update_field('field_devis_ville',            $ville, $post_id);
        update_field('field_devis_province',         $province, $post_id);
        update_field('field_devis_code_postal',      $code_postal, $post_id);
        update_field('field_devis_modeles',          $modeles_list, $post_id);
        update_field('field_devis_couleur',          $couleur, $post_id);
        update_field('field_devis_type_installation', $type_installation, $post_id);
        update_field('field_devis_date_projet',      $date_projet, $post_id);
        update_field('field_devis_source',           $source, $post_id);
        update_field('field_devis_commentaires',     $commentaires, $post_id);
        update_field('field_devis_accord_coordonnees', $accord ? 1 : 0, $post_id);
        update_field('field_devis_infolettre',       $infolettre ? 1 : 0, $post_id);
        update_field('field_devis_statut',           'nouveau', $post_id);

        // Tapis AquaCove
        foreach ($tapis_selections as $tz_key => $tz_slug) {
            update_field('field_devis_tapis_' . $tz_key, $tz_slug, $post_id);
        }

        // Options AquaCove
        if ($options_aquacove) {
            update_field('field_devis_options_aquacove', $options_aquacove, $post_id);
        }
    }

    // Construire le courriel

    $body  = "Nouvelle demande de devis\n";
    $body .= "========================\n\n";
    $body .= "Prénom : {$prenom}\n";
    $body .= "Nom : {$nom}\n";
    $body .= "Courriel : {$courriel}\n";
    $body .= "Téléphone : {$telephone}\n";
    $body .= "Meilleur moment : {$moment}\n";
    $body .= "Moyen de contact préféré : {$moyen_contact}\n\n";
    $body .= "Adresse : {$adresse}\n";
    $body .= "Ville : {$ville}\n";
    $body .= "Province : {$province}\n";
    $body .= "Code postal : {$code_postal}\n\n";
    $body .= "Modèle(s) : {$modeles_list}\n";
    $body .= "Couleur : {$couleur}\n";
    $body .= "Type d'installation : {$type_installation}\n";

    // Tapis AquaCove
    if (! empty($tapis_selections)) {
        $zone_labels_email = array('marches' => 'Marches', 'bancs' => 'Bancs', 'terrasse' => 'Terrasse');
        $body .= "\nTapis AquaCove :\n";
        foreach ($tapis_selections as $tz_key => $tz_slug) {
            $tz_label = isset($zone_labels_email[$tz_key]) ? $zone_labels_email[$tz_key] : $tz_key;
            $body .= "  {$tz_label} : {$tz_slug}\n";
        }
    }

    // Options AquaCove
    if ($options_aquacove) {
        $body .= "Options AquaCove : " . str_replace(',', ', ', $options_aquacove) . "\n";
    }

    $body .= "Où en êtes-vous dans vos démarches : {$date_projet}\n";
    if ($date_concrete) {
        $body .= "Date souhaitée : {$date_concrete}\n";
    }
    $body .= "Source : {$source}\n\n";
    $body .= "Commentaires :\n{$commentaires}\n\n";
    $body .= "---\n";
    $body .= "Accord coordonnées : " . ($accord ? 'Oui' : 'Non') . "\n";
    $body .= "Infolettre : " . ($infolettre ? 'Oui' : 'Non') . "\n";

    $to      = get_option('admin_email');
    $subject = 'Demande de devis — ' . $prenom . ' ' . $nom;
    $headers = array(
        'Content-Type: text/plain; charset=UTF-8',
        'Reply-To: ' . $prenom . ' ' . $nom . ' <' . $courriel . '>',
    );

    $sent = wp_mail($to, $subject, $body, $headers);

    if ($sent) {
        // Courriel de confirmation au demandeur
        $confirm_body  = "Bonjour {$prenom},\n\n";
        $confirm_body .= "Nous avons bien reçu votre demande de devis. Un représentant Mova vous contactera sous peu.\n\n";
        $confirm_body .= "Voici un résumé de votre demande :\n";
        $confirm_body .= "================================\n\n";

        // Coordonnées
        $confirm_body .= "Prénom : {$prenom}\n";
        $confirm_body .= "Nom : {$nom}\n";
        $confirm_body .= "Courriel : {$courriel}\n";
        $confirm_body .= "Téléphone : {$telephone}\n";
        if ($moment) {
            $confirm_body .= "Meilleur moment pour vous joindre : {$moment}\n";
        }
        if ($moyen_contact) {
            $confirm_body .= "Moyen de contact préféré : {$moyen_contact}\n";
        }

        // Adresse
        if ($adresse || $ville || $province || $code_postal) {
            $confirm_body .= "\nAdresse :\n";
            if ($adresse)     $confirm_body .= "  {$adresse}\n";
            if ($ville)       $confirm_body .= "  {$ville}\n";
            if ($province)    $confirm_body .= "  {$province}\n";
            if ($code_postal) $confirm_body .= "  {$code_postal}\n";
        }

        // Projet
        $confirm_body .= "\nVotre projet :\n";
        if ($modeles_list)        $confirm_body .= "  Modèle(s) : {$modeles_list}\n";
        if ($couleur)             $confirm_body .= "  Couleur : {$couleur}\n";
        if ($type_installation)   $confirm_body .= "  Type d'installation : {$type_installation}\n";
        if ($date_projet)         $confirm_body .= "  Où en êtes-vous dans vos démarches : {$date_projet}\n";
        if ($date_concrete)       $confirm_body .= "  Date souhaitée : {$date_concrete}\n";
        if ($source)              $confirm_body .= "  Comment avez-vous entendu parler de nous : {$source}\n";
        if ($commentaires)        $confirm_body .= "  Commentaires : {$commentaires}\n";

        // Tapis AquaCove
        if (! empty($tapis_selections)) {
            $zone_labels_confirm = array('marches' => 'Marches', 'bancs' => 'Bancs', 'terrasse' => 'Terrasse');
            $confirm_body .= "\nSélections AquaCove :\n";
            foreach ($tapis_selections as $tz_key => $tz_slug) {
                $tz_label = isset($zone_labels_confirm[$tz_key]) ? $zone_labels_confirm[$tz_key] : $tz_key;
                $confirm_body .= "  Tapis {$tz_label} : {$tz_slug}\n";
            }
        }
        if ($options_aquacove) {
            $confirm_body .= "  Options AquaCove : " . str_replace(',', ', ', $options_aquacove) . "\n";
        }

        $confirm_body .= "\n---\n";
        $confirm_body .= "Merci de votre intérêt pour les piscines Mova!\n";
        $confirm_body .= "— L'équipe Piscines Mova\n";

        $confirm_headers = array(
            'Content-Type: text/plain; charset=UTF-8',
        );

        wp_mail(
            $courriel,
            'Confirmation — Votre demande de devis Mova',
            $confirm_body,
            $confirm_headers
        );

        wp_send_json_success(array('message' => __( 'Merci! Votre demande de devis a été envoyée avec succès. Un représentant Mova vous contactera sous peu.', 'piscines-mova' )));
    } else {
        wp_send_json_error(array('message' => __( "Une erreur est survenue lors de l'envoi. Veuillez réessayer ou nous contacter directement.", 'piscines-mova' )));
    }
}
add_action('wp_ajax_mova_submit_quote', 'mova_handle_quote_submission');
add_action('wp_ajax_nopriv_mova_submit_quote', 'mova_handle_quote_submission');


/* =============================================
   Admin — Colonnes personnalisées (demande_devis)
   ============================================= */
function mova_devis_admin_columns($columns)
{
    $new = array();
    $new['cb']        = $columns['cb'];
    $new['title']     = $columns['title'];
    $new['courriel']  = __( 'Courriel', 'piscines-mova' );
    $new['telephone'] = __( 'Téléphone', 'piscines-mova' );
    $new['modeles']   = __( 'Modèle(s)', 'piscines-mova' );
    $new['statut']    = __( 'Statut', 'piscines-mova' );
    $new['date']      = $columns['date'];
    return $new;
}
add_filter('manage_demande_devis_posts_columns', 'mova_devis_admin_columns');

function mova_devis_admin_column_content($column, $post_id)
{
    switch ($column) {
        case 'courriel':
            $val = get_field('courriel', $post_id);
            echo esc_html($val ?: '—');
            break;
        case 'telephone':
            $val = get_field('telephone', $post_id);
            echo esc_html($val ?: '—');
            break;
        case 'modeles':
            $val = get_field('modeles', $post_id);
            echo esc_html($val ?: '—');
            break;
        case 'statut':
            $val = get_field('statut', $post_id);
            $labels = array(
                'nouveau'  => __( 'Nouveau', 'piscines-mova' ),
                'en_cours' => __( 'En cours', 'piscines-mova' ),
                'traite'   => __( 'Traité', 'piscines-mova' ),
                'archive'  => __( 'Archivé', 'piscines-mova' ),
            );
            $colors = array(
                'nouveau'  => '#2271b1',
                'en_cours' => '#dba617',
                'traite'   => '#00a32a',
                'archive'  => '#787c82',
            );
            $label = isset($labels[$val]) ? $labels[$val] : $val;
            $color = isset($colors[$val]) ? $colors[$val] : '#787c82';
            if ($label) {
                printf(
                    '<span style="display:inline-block;padding:3px 10px;border-radius:4px;background:%s;color:#fff;font-size:12px;font-weight:600;line-height:1.4;">%s</span>',
                    esc_attr($color),
                    esc_html($label)
                );
            } else {
                echo '—';
            }
            break;
    }
}
add_action('manage_demande_devis_posts_custom_column', 'mova_devis_admin_column_content', 10, 2);

function mova_devis_sortable_columns($columns)
{
    $columns['statut'] = 'statut';
    return $columns;
}
add_filter('manage_edit-demande_devis_sortable_columns', 'mova_devis_sortable_columns');


/* =============================================
   Admin — Filtre par statut (demande_devis)
   ============================================= */
function mova_devis_admin_filter_dropdown()
{
    global $typenow;
    if ('demande_devis' !== $typenow) {
        return;
    }

    $current = isset($_GET['devis_statut']) ? sanitize_text_field($_GET['devis_statut']) : '';
    $statuts = array(
        'nouveau'  => __( 'Nouveau', 'piscines-mova' ),
        'en_cours' => __( 'En cours', 'piscines-mova' ),
        'traite'   => __( 'Traité', 'piscines-mova' ),
        'archive'  => __( 'Archivé', 'piscines-mova' ),
    );

    echo '<select name="devis_statut">';
    echo '<option value="">' . esc_html__( 'Tous les statuts', 'piscines-mova' ) . '</option>';
    foreach ($statuts as $value => $label) {
        printf(
            '<option value="%s" %s>%s</option>',
            esc_attr($value),
            selected($current, $value, false),
            esc_html($label)
        );
    }
    echo '</select>';
}
add_action('restrict_manage_posts', 'mova_devis_admin_filter_dropdown');

function mova_devis_admin_filter_query($query)
{
    global $pagenow, $typenow;
    if (! is_admin() || 'edit.php' !== $pagenow || 'demande_devis' !== $typenow || ! $query->is_main_query()) {
        return;
    }

    // Filtre par statut
    if (! empty($_GET['devis_statut'])) {
        $statut = sanitize_text_field($_GET['devis_statut']);
        $meta_query = $query->get('meta_query') ?: array();
        $meta_query[] = array(
            'key'     => 'statut',
            'value'   => $statut,
            'compare' => '=',
        );
        $query->set('meta_query', $meta_query);
    }

    // Tri par statut
    if ('statut' === $query->get('orderby')) {
        $query->set('meta_key', 'statut');
        $query->set('orderby', 'meta_value');
    }
}
add_action('pre_get_posts', 'mova_devis_admin_filter_query');
