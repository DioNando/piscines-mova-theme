<?php
// Empêcher l'accès direct
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/* =============================================
   Shortcode — [mova_quote_form]
   Formulaire de demande de devis
   ============================================= */
function mova_quote_form_shortcode( $atts ) {
    $atts = shortcode_atts( array(
        'model'   => '',
        'couleur' => '',
    ), $atts, 'mova_quote_form' );

    // Pré-remplissage via query params (depuis le configurateur)
    $preselect_model   = sanitize_text_field( $atts['model'] ?: ( $_GET['model'] ?? '' ) );
    $preselect_couleur = sanitize_text_field( $atts['couleur'] ?: ( $_GET['couleur'] ?? '' ) );

    // Pré-remplissage options AquaCove (depuis le configurateur)
    $preselect_options = sanitize_text_field( $_GET['options'] ?? '' );

    // Pré-remplissage tapis AquaCove par zone (depuis le configurateur)
    $zone_labels = array(
        'marches'  => 'Marches',
        'bancs'    => 'Bancs',
        'terrasse' => 'Terrasse',
    );
    $preselect_tapis = array();
    foreach ( $zone_labels as $zone_key => $zone_label ) {
        $val = sanitize_text_field( $_GET[ 'tapis_' . $zone_key ] ?? '' );
        if ( $val ) {
            $preselect_tapis[ $zone_key ] = $val;
        }
    }

    // Récupérer les modèles de tapis si au moins un est pré-sélectionné
    $tapis_terms_map = array();
    if ( ! empty( $preselect_tapis ) ) {
        $all_tapis_slugs = array_values( $preselect_tapis );
        $tapis_terms = get_terms( array(
            'taxonomy'   => 'modele_tapis',
            'slug'       => $all_tapis_slugs,
            'hide_empty' => false,
        ) );
        if ( ! is_wp_error( $tapis_terms ) ) {
            foreach ( $tapis_terms as $tt ) {
                $tapis_terms_map[ $tt->slug ] = $tt->name;
            }
        }
    }

    // Récupérer les modèles de piscines (CPT piscine)
    $piscines = get_posts( array(
        'post_type'      => 'piscine',
        'posts_per_page' => -1,
        'post_status'    => 'publish',
        'orderby'        => 'title',
        'order'          => 'ASC',
    ) );

    // Récupérer les couleurs (taxonomie couleur_piscine)
    $couleurs = get_terms( array(
        'taxonomy'   => 'couleur_piscine',
        'hide_empty' => false,
        'orderby'    => 'name',
        'order'      => 'ASC',
    ) );
    if ( is_wp_error( $couleurs ) ) {
        $couleurs = array();
    }

    // Récupérer les provinces (taxonomie province)
    $provinces = get_terms( array(
        'taxonomy'   => 'province',
        'hide_empty' => false,
        'orderby'    => 'name',
        'order'      => 'ASC',
    ) );
    if ( is_wp_error( $provinces ) ) {
        $provinces = array();
    }

    // Assets
    wp_enqueue_style( 'mova-quote-form-style', get_stylesheet_directory_uri() . '/assets/css/quote-form.css', array(), '1.0.0' );
    wp_enqueue_script( 'mova-quote-form-script', get_stylesheet_directory_uri() . '/assets/js/quote-form.js', array(), '1.0.0', true );

    wp_localize_script( 'mova-quote-form-script', 'movaQuoteForm', array(
        'ajaxUrl' => admin_url( 'admin-ajax.php' ),
        'nonce'   => wp_create_nonce( 'mova_quote_form_nonce' ),
    ) );

    ob_start(); ?>

    <div class="mova-qf" id="mova-qf">

        <form class="mova-qf-form" id="mova-qf-form" novalidate>
            <input type="hidden" name="action" value="mova_submit_quote" />
            <input type="hidden" name="nonce" value="<?php echo esc_attr( wp_create_nonce( 'mova_quote_form_nonce' ) ); ?>" />

            <!-- Honeypot anti-spam -->
            <div style="position:absolute;left:-9999px;" aria-hidden="true">
                <label for="mova_qf_website">Ne pas remplir</label>
                <input type="text" name="website" id="mova_qf_website" tabindex="-1" autocomplete="off" />
            </div>

            <!-- ====== Section : Coordonnées ====== -->
            <fieldset class="mova-qf-fieldset">
                <legend class="mova-qf-legend">Vos coordonnées</legend>

                <div class="mova-qf-row">
                    <div class="mova-qf-field">
                        <label for="mova_qf_prenom">Prénom <span class="mova-qf-req">*</span></label>
                        <input type="text" id="mova_qf_prenom" name="prenom" required />
                    </div>
                    <div class="mova-qf-field">
                        <label for="mova_qf_nom">Nom <span class="mova-qf-req">*</span></label>
                        <input type="text" id="mova_qf_nom" name="nom" required />
                    </div>
                </div>

                <div class="mova-qf-row">
                    <div class="mova-qf-field">
                        <label for="mova_qf_courriel">Courriel <span class="mova-qf-req">*</span></label>
                        <input type="email" id="mova_qf_courriel" name="courriel" required />
                    </div>
                    <div class="mova-qf-field">
                        <label for="mova_qf_telephone">Téléphone <span class="mova-qf-req">*</span></label>
                        <input type="tel" id="mova_qf_telephone" name="telephone" required />
                    </div>
                </div>

                <div class="mova-qf-row">
                    <div class="mova-qf-field">
                        <label for="mova_qf_moment">Meilleur moment pour vous rejoindre</label>
                        <select id="mova_qf_moment" name="moment">
                            <option value="">— Sélectionner —</option>
                            <option value="Avant-midi">Avant-midi</option>
                            <option value="Après-midi">Après-midi</option>
                            <option value="En soirée">En soirée</option>
                        </select>
                    </div>
                    <div class="mova-qf-field">
                        <label for="mova_qf_moyen">Meilleure façon de vous rejoindre</label>
                        <select id="mova_qf_moyen" name="moyen_contact">
                            <option value="">— Sélectionner —</option>
                            <option value="Courriel">Courriel</option>
                            <option value="Téléphone">Téléphone</option>
                            <option value="Texto">Texto</option>
                        </select>
                    </div>
                </div>
            </fieldset>

            <!-- ====== Section : Adresse ====== -->
            <fieldset class="mova-qf-fieldset">
                <legend class="mova-qf-legend">Votre adresse</legend>

                <div class="mova-qf-field mova-qf-field--full">
                    <label for="mova_qf_adresse">Adresse</label>
                    <input type="text" id="mova_qf_adresse" name="adresse" />
                </div>

                <div class="mova-qf-row mova-qf-row--3">
                    <div class="mova-qf-field">
                        <label for="mova_qf_ville">Ville</label>
                        <input type="text" id="mova_qf_ville" name="ville" />
                    </div>
                    <div class="mova-qf-field">
                        <label for="mova_qf_province">Province</label>
                        <select id="mova_qf_province" name="province">
                            <option value="">— Sélectionner —</option>
                            <?php foreach ( $provinces as $prov ) : ?>
                                <option value="<?php echo esc_attr( $prov->name ); ?>"><?php echo esc_html( $prov->name ); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mova-qf-field">
                        <label for="mova_qf_cp">Code postal</label>
                        <input type="text" id="mova_qf_cp" name="code_postal" />
                    </div>
                </div>
            </fieldset>

            <!-- ====== Section : Projet ====== -->
            <fieldset class="mova-qf-fieldset">
                <legend class="mova-qf-legend">Votre projet</legend>

                <!-- Modèles (checkboxes) -->
                <div class="mova-qf-field mova-qf-field--full">
                    <label>Sélectionnez le ou les modèles de piscines qui vous intéressent :</label>
                    <div class="mova-qf-checkboxes" id="mova-qf-modeles">
                        <?php foreach ( $piscines as $piscine ) :
                            $slug    = $piscine->post_name;
                            $checked = ( $preselect_model === $slug ) ? 'checked' : '';
                        ?>
                            <label class="mova-qf-checkbox-label">
                                <input type="checkbox" name="modeles[]" value="<?php echo esc_attr( $slug ); ?>" <?php echo $checked; ?> />
                                <span><?php echo esc_html( $piscine->post_title ); ?></span>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="mova-qf-row">
                    <div class="mova-qf-field">
                        <label for="mova_qf_couleur">Couleur de la piscine</label>
                        <select id="mova_qf_couleur" name="couleur">
                            <option value="">— Sélectionner —</option>
                            <?php foreach ( $couleurs as $couleur ) :
                                $selected = ( $preselect_couleur === $couleur->slug ) ? 'selected' : '';
                            ?>
                                <option value="<?php echo esc_attr( $couleur->slug ); ?>" <?php echo $selected; ?>><?php echo esc_html( $couleur->name ); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mova-qf-field">
                        <label for="mova_qf_installation">Type d'installation</label>
                        <select id="mova_qf_installation" name="type_installation">
                            <option value="">— Sélectionner —</option>
                            <option value="Creusée">Creusée</option>
                            <option value="Semi-creusée">Semi-creusée</option>
                            <option value="Hors terre">Hors terre</option>
                            <option value="Je ne sais pas">Je ne sais pas</option>
                        </select>
                    </div>
                </div>

                <?php if ( ! empty( $preselect_tapis ) || $preselect_options ) : ?>
                <!-- ====== Sélections AquaCove (depuis le configurateur) ====== -->
                <div class="mova-qf-aquacove-summary">
                    <p class="mova-qf-aquacove-title">Sélections AquaCove</p>

                    <?php if ( ! empty( $preselect_tapis ) ) : ?>
                    <div class="mova-qf-aquacove-items">
                        <?php foreach ( $preselect_tapis as $zone_key => $tapis_slug ) :
                            $tapis_name = isset( $tapis_terms_map[ $tapis_slug ] ) ? $tapis_terms_map[ $tapis_slug ] : $tapis_slug;
                            $zone_name  = isset( $zone_labels[ $zone_key ] ) ? $zone_labels[ $zone_key ] : $zone_key;
                        ?>
                            <div class="mova-qf-aquacove-item">
                                <span class="mova-qf-aquacove-zone"><?php echo esc_html( $zone_name ); ?> :</span>
                                <span class="mova-qf-aquacove-value"><?php echo esc_html( $tapis_name ); ?></span>
                                <input type="hidden" name="tapis_<?php echo esc_attr( $zone_key ); ?>" value="<?php echo esc_attr( $tapis_slug ); ?>" />
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>

                    <?php if ( $preselect_options ) : ?>
                    <div class="mova-qf-aquacove-items">
                        <div class="mova-qf-aquacove-item">
                            <span class="mova-qf-aquacove-zone">Options :</span>
                            <span class="mova-qf-aquacove-value"><?php echo esc_html( str_replace( ',', ', ', $preselect_options ) ); ?></span>
                            <input type="hidden" name="options_aquacove" value="<?php echo esc_attr( $preselect_options ); ?>" />
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
                <?php endif; ?>

                <div class="mova-qf-row">
                    <div class="mova-qf-field">
                        <label for="mova_qf_date">Date du projet</label>
                        <input type="date" id="mova_qf_date" name="date_projet" />
                    </div>
                    <div class="mova-qf-field">
                        <label for="mova_qf_source">Comment avez-vous entendu parler de nous?</label>
                        <select id="mova_qf_source" name="source">
                            <option value="">— Sélectionner —</option>
                            <option value="Recherche Internet">Recherche Internet</option>
                            <option value="Réseaux sociaux">Réseaux sociaux</option>
                            <option value="Recommandation">Recommandation</option>
                            <option value="Salon / Exposition">Salon / Exposition</option>
                            <option value="Publicité">Publicité</option>
                            <option value="Autre">Autre</option>
                        </select>
                    </div>
                </div>

                <div class="mova-qf-field mova-qf-field--full">
                    <label for="mova_qf_commentaires">Demandes additionnelles et/ou commentaires</label>
                    <textarea id="mova_qf_commentaires" name="commentaires" rows="4"></textarea>
                </div>
            </fieldset>

            <!-- ====== Consentements ====== -->
            <fieldset class="mova-qf-fieldset mova-qf-fieldset--consent">
                <label class="mova-qf-checkbox-label">
                    <input type="checkbox" name="accord_coordonnees" value="1" required />
                    <span>J'accepte que mes coordonnées soient remises au détaillant le plus près de mon domicile et à ses partenaires <span class="mova-qf-req">*</span></span>
                </label>
                <label class="mova-qf-checkbox-label">
                    <input type="checkbox" name="infolettre" value="1" />
                    <span>J'accepte de recevoir des courriers électroniques promotionnels.</span>
                </label>
                <p class="mova-qf-note">* Les champs marqués d'un astérisque sont obligatoires.</p>
            </fieldset>

            <!-- ====== Submit ====== -->
            <div class="mova-qf-submit-wrap">
                <button type="submit" class="mova-qf-submit" id="mova-qf-submit">Envoyer</button>
            </div>

            <!-- Messages -->
            <div class="mova-qf-message" id="mova-qf-message" role="alert" aria-live="polite"></div>

        </form>

    </div>

    <?php
    return ob_get_clean();
}
add_shortcode( 'mova_quote_form', 'mova_quote_form_shortcode' );


/* =============================================
   AJAX Handler — Soumission du formulaire
   ============================================= */
function mova_handle_quote_submission() {
    // Vérifier le nonce
    if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'mova_quote_form_nonce' ) ) {
        wp_send_json_error( array( 'message' => 'Erreur de sécurité. Veuillez rafraîchir la page et réessayer.' ) );
    }

    // Honeypot
    if ( ! empty( $_POST['website'] ) ) {
        wp_send_json_success( array( 'message' => 'Merci! Votre demande a été envoyée.' ) );
    }

    // Sanitisation
    $prenom           = sanitize_text_field( wp_unslash( $_POST['prenom'] ?? '' ) );
    $nom              = sanitize_text_field( wp_unslash( $_POST['nom'] ?? '' ) );
    $courriel         = sanitize_email( wp_unslash( $_POST['courriel'] ?? '' ) );
    $telephone        = sanitize_text_field( wp_unslash( $_POST['telephone'] ?? '' ) );
    $moment           = sanitize_text_field( wp_unslash( $_POST['moment'] ?? '' ) );
    $moyen_contact    = sanitize_text_field( wp_unslash( $_POST['moyen_contact'] ?? '' ) );
    $adresse          = sanitize_text_field( wp_unslash( $_POST['adresse'] ?? '' ) );
    $ville            = sanitize_text_field( wp_unslash( $_POST['ville'] ?? '' ) );
    $province         = sanitize_text_field( wp_unslash( $_POST['province'] ?? '' ) );
    $code_postal      = sanitize_text_field( wp_unslash( $_POST['code_postal'] ?? '' ) );
    $couleur          = sanitize_text_field( wp_unslash( $_POST['couleur'] ?? '' ) );
    $type_installation = sanitize_text_field( wp_unslash( $_POST['type_installation'] ?? '' ) );
    $date_projet      = sanitize_text_field( wp_unslash( $_POST['date_projet'] ?? '' ) );
    $source           = sanitize_text_field( wp_unslash( $_POST['source'] ?? '' ) );
    $commentaires     = sanitize_textarea_field( wp_unslash( $_POST['commentaires'] ?? '' ) );
    $accord           = ! empty( $_POST['accord_coordonnees'] );
    $infolettre       = ! empty( $_POST['infolettre'] );

    // Modèles (tableau)
    $modeles_raw = isset( $_POST['modeles'] ) && is_array( $_POST['modeles'] ) ? $_POST['modeles'] : array();
    $modeles = array_map( 'sanitize_text_field', array_map( 'wp_unslash', $modeles_raw ) );

    // Tapis AquaCove par zone
    $tapis_zones = array( 'marches', 'bancs', 'terrasse' );
    $tapis_selections = array();
    foreach ( $tapis_zones as $tz ) {
        $val = sanitize_text_field( wp_unslash( $_POST[ 'tapis_' . $tz ] ?? '' ) );
        if ( $val ) {
            $tapis_selections[ $tz ] = $val;
        }
    }

    // Options AquaCove
    $options_aquacove = sanitize_text_field( wp_unslash( $_POST['options_aquacove'] ?? '' ) );

    // Validation
    $errors = array();
    if ( empty( $prenom ) )   $errors[] = 'Le prénom est requis.';
    if ( empty( $nom ) )      $errors[] = 'Le nom est requis.';
    if ( ! is_email( $courriel ) ) $errors[] = 'Veuillez entrer une adresse courriel valide.';
    if ( empty( $telephone ) ) $errors[] = 'Le téléphone est requis.';
    if ( ! $accord )          $errors[] = 'Vous devez accepter le partage de vos coordonnées.';

    if ( ! empty( $errors ) ) {
        wp_send_json_error( array( 'message' => implode( '<br>', $errors ) ) );
    }

    // Enregistrer la demande en base de données (CPT demande_devis)
    $modeles_list = ! empty( $modeles ) ? implode( ', ', $modeles ) : '—';

    $post_id = wp_insert_post( array(
        'post_type'   => 'demande_devis',
        'post_title'  => 'Devis — ' . $prenom . ' ' . $nom,
        'post_status' => 'publish',
    ) );

    if ( $post_id && ! is_wp_error( $post_id ) ) {
        update_field( 'field_devis_prenom',           $prenom, $post_id );
        update_field( 'field_devis_nom',              $nom, $post_id );
        update_field( 'field_devis_courriel',         $courriel, $post_id );
        update_field( 'field_devis_telephone',        $telephone, $post_id );
        update_field( 'field_devis_moment',           $moment, $post_id );
        update_field( 'field_devis_moyen_contact',    $moyen_contact, $post_id );
        update_field( 'field_devis_adresse',          $adresse, $post_id );
        update_field( 'field_devis_ville',            $ville, $post_id );
        update_field( 'field_devis_province',         $province, $post_id );
        update_field( 'field_devis_code_postal',      $code_postal, $post_id );
        update_field( 'field_devis_modeles',          $modeles_list, $post_id );
        update_field( 'field_devis_couleur',          $couleur, $post_id );
        update_field( 'field_devis_type_installation', $type_installation, $post_id );
        update_field( 'field_devis_date_projet',      $date_projet, $post_id );
        update_field( 'field_devis_source',           $source, $post_id );
        update_field( 'field_devis_commentaires',     $commentaires, $post_id );
        update_field( 'field_devis_accord_coordonnees', $accord ? 1 : 0, $post_id );
        update_field( 'field_devis_infolettre',       $infolettre ? 1 : 0, $post_id );
        update_field( 'field_devis_statut',           'nouveau', $post_id );

        // Tapis AquaCove
        foreach ( $tapis_selections as $tz_key => $tz_slug ) {
            update_field( 'field_devis_tapis_' . $tz_key, $tz_slug, $post_id );
        }

        // Options AquaCove
        if ( $options_aquacove ) {
            update_field( 'field_devis_options_aquacove', $options_aquacove, $post_id );
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
    if ( ! empty( $tapis_selections ) ) {
        $zone_labels_email = array( 'marches' => 'Marches', 'bancs' => 'Bancs', 'terrasse' => 'Terrasse' );
        $body .= "\nTapis AquaCove :\n";
        foreach ( $tapis_selections as $tz_key => $tz_slug ) {
            $tz_label = isset( $zone_labels_email[ $tz_key ] ) ? $zone_labels_email[ $tz_key ] : $tz_key;
            $body .= "  {$tz_label} : {$tz_slug}\n";
        }
    }

    // Options AquaCove
    if ( $options_aquacove ) {
        $body .= "Options AquaCove : " . str_replace( ',', ', ', $options_aquacove ) . "\n";
    }

    $body .= "Date du projet : {$date_projet}\n";
    $body .= "Source : {$source}\n\n";
    $body .= "Commentaires :\n{$commentaires}\n\n";
    $body .= "---\n";
    $body .= "Accord coordonnées : " . ( $accord ? 'Oui' : 'Non' ) . "\n";
    $body .= "Infolettre : " . ( $infolettre ? 'Oui' : 'Non' ) . "\n";

    $to      = get_option( 'admin_email' );
    $subject = 'Demande de devis — ' . $prenom . ' ' . $nom;
    $headers = array(
        'Content-Type: text/plain; charset=UTF-8',
        'Reply-To: ' . $prenom . ' ' . $nom . ' <' . $courriel . '>',
    );

    $sent = wp_mail( $to, $subject, $body, $headers );

    if ( $sent ) {
        wp_send_json_success( array( 'message' => 'Merci! Votre demande de devis a été envoyée avec succès. Un représentant Mova vous contactera sous peu.' ) );
    } else {
        wp_send_json_error( array( 'message' => 'Une erreur est survenue lors de l\'envoi. Veuillez réessayer ou nous contacter directement.' ) );
    }
}
add_action( 'wp_ajax_mova_submit_quote', 'mova_handle_quote_submission' );
add_action( 'wp_ajax_nopriv_mova_submit_quote', 'mova_handle_quote_submission' );


/* =============================================
   Admin — Colonnes personnalisées (demande_devis)
   ============================================= */
function mova_devis_admin_columns( $columns ) {
    $new = array();
    $new['cb']        = $columns['cb'];
    $new['title']     = $columns['title'];
    $new['courriel']  = 'Courriel';
    $new['telephone'] = 'Téléphone';
    $new['modeles']   = 'Modèle(s)';
    $new['statut']    = 'Statut';
    $new['date']      = $columns['date'];
    return $new;
}
add_filter( 'manage_demande_devis_posts_columns', 'mova_devis_admin_columns' );

function mova_devis_admin_column_content( $column, $post_id ) {
    switch ( $column ) {
        case 'courriel':
            $val = get_field( 'courriel', $post_id );
            echo esc_html( $val ?: '—' );
            break;
        case 'telephone':
            $val = get_field( 'telephone', $post_id );
            echo esc_html( $val ?: '—' );
            break;
        case 'modeles':
            $val = get_field( 'modeles', $post_id );
            echo esc_html( $val ?: '—' );
            break;
        case 'statut':
            $val = get_field( 'statut', $post_id );
            $labels = array(
                'nouveau'  => 'Nouveau',
                'en_cours' => 'En cours',
                'traite'   => 'Traité',
                'archive'  => 'Archivé',
            );
            $colors = array(
                'nouveau'  => '#2271b1',
                'en_cours' => '#dba617',
                'traite'   => '#00a32a',
                'archive'  => '#787c82',
            );
            $label = isset( $labels[ $val ] ) ? $labels[ $val ] : $val;
            $color = isset( $colors[ $val ] ) ? $colors[ $val ] : '#787c82';
            if ( $label ) {
                printf(
                    '<span style="display:inline-block;padding:3px 10px;border-radius:4px;background:%s;color:#fff;font-size:12px;font-weight:600;line-height:1.4;">%s</span>',
                    esc_attr( $color ),
                    esc_html( $label )
                );
            } else {
                echo '—';
            }
            break;
    }
}
add_action( 'manage_demande_devis_posts_custom_column', 'mova_devis_admin_column_content', 10, 2 );

function mova_devis_sortable_columns( $columns ) {
    $columns['statut'] = 'statut';
    return $columns;
}
add_filter( 'manage_edit-demande_devis_sortable_columns', 'mova_devis_sortable_columns' );


/* =============================================
   Admin — Filtre par statut (demande_devis)
   ============================================= */
function mova_devis_admin_filter_dropdown() {
    global $typenow;
    if ( 'demande_devis' !== $typenow ) {
        return;
    }

    $current = isset( $_GET['devis_statut'] ) ? sanitize_text_field( $_GET['devis_statut'] ) : '';
    $statuts = array(
        'nouveau'  => 'Nouveau',
        'en_cours' => 'En cours',
        'traite'   => 'Traité',
        'archive'  => 'Archivé',
    );

    echo '<select name="devis_statut">';
    echo '<option value="">Tous les statuts</option>';
    foreach ( $statuts as $value => $label ) {
        printf(
            '<option value="%s" %s>%s</option>',
            esc_attr( $value ),
            selected( $current, $value, false ),
            esc_html( $label )
        );
    }
    echo '</select>';
}
add_action( 'restrict_manage_posts', 'mova_devis_admin_filter_dropdown' );

function mova_devis_admin_filter_query( $query ) {
    global $pagenow, $typenow;
    if ( ! is_admin() || 'edit.php' !== $pagenow || 'demande_devis' !== $typenow || ! $query->is_main_query() ) {
        return;
    }

    // Filtre par statut
    if ( ! empty( $_GET['devis_statut'] ) ) {
        $statut = sanitize_text_field( $_GET['devis_statut'] );
        $meta_query = $query->get( 'meta_query' ) ?: array();
        $meta_query[] = array(
            'key'     => 'statut',
            'value'   => $statut,
            'compare' => '=',
        );
        $query->set( 'meta_query', $meta_query );
    }

    // Tri par statut
    if ( 'statut' === $query->get( 'orderby' ) ) {
        $query->set( 'meta_key', 'statut' );
        $query->set( 'orderby', 'meta_value' );
    }
}
add_action( 'pre_get_posts', 'mova_devis_admin_filter_query' );
