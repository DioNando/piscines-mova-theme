<?php
/**
 * WP-CLI — Migration configurateur AquaCove
 *
 * Commandes :
 *   wp mova upload-images   — Bulk-upload des PNG vers la médiathèque
 *   wp mova mapping-report  — Rapport des associations piscine ↔ images
 *
 * Fichier temporaire — à supprimer après migration (Phase 5).
 */

if ( ! defined( 'ABSPATH' ) || ! defined( 'WP_CLI' ) ) {
    return;
}

class Mova_Configurator_Migration extends WP_CLI_Command {

    /**
     * Bulk-upload les PNG de tapis-aquacove/ vers la médiathèque WordPress.
     *
     * Vérifie si chaque fichier existe déjà (par filename) avant d'uploader.
     * Génère un résumé : nb uploadés, nb déjà existants, nb erreurs.
     *
     * ## OPTIONS
     *
     * [--dry-run]
     * : Affiche ce qui serait fait sans uploader.
     *
     * ## EXAMPLES
     *
     *     wp mova upload-images
     *     wp mova upload-images --dry-run
     *
     * @subcommand upload-images
     */
    public function upload_images( $args, $assoc_args ) {
        $dry_run  = \WP_CLI\Utils\get_flag_value( $assoc_args, 'dry-run', false );
        $base_dir = get_stylesheet_directory() . '/assets/images/tapis-aquacove/';

        if ( ! is_dir( $base_dir ) ) {
            WP_CLI::error( "Dossier introuvable : {$base_dir}" );
        }

        $files = glob( $base_dir . '*.png' );
        if ( empty( $files ) ) {
            WP_CLI::error( 'Aucun fichier PNG trouvé.' );
        }

        $total    = count( $files );
        $uploaded = 0;
        $skipped  = 0;
        $errors   = 0;

        WP_CLI::log( sprintf( '%d fichiers PNG trouvés.%s', $total, $dry_run ? ' (dry-run)' : '' ) );

        // Require file handling functions
        if ( ! function_exists( 'media_handle_sideload' ) ) {
            require_once ABSPATH . 'wp-admin/includes/file.php';
            require_once ABSPATH . 'wp-admin/includes/media.php';
            require_once ABSPATH . 'wp-admin/includes/image.php';
        }

        $progress = \WP_CLI\Utils\make_progress_bar( 'Upload des images', $total );

        foreach ( $files as $filepath ) {
            $filename = basename( $filepath );
            $name_no_ext = pathinfo( $filename, PATHINFO_FILENAME );

            // Vérifier si déjà en médiathèque (par titre = filename sans extension)
            $existing = get_posts( array(
                'post_type'   => 'attachment',
                'post_status' => 'inherit',
                'title'       => $name_no_ext,
                'numberposts' => 1,
                'fields'      => 'ids',
            ) );

            if ( ! empty( $existing ) ) {
                $skipped++;
                $progress->tick();
                continue;
            }

            if ( $dry_run ) {
                WP_CLI::log( "  [DRY-RUN] Upload : {$filename}" );
                $uploaded++;
                $progress->tick();
                continue;
            }

            // Copier dans le dossier tmp de WP pour media_handle_sideload
            $tmp_file = wp_tempnam( $filename );
            if ( ! copy( $filepath, $tmp_file ) ) {
                WP_CLI::warning( "Impossible de copier : {$filename}" );
                @unlink( $tmp_file );
                $errors++;
                $progress->tick();
                continue;
            }

            $file_array = array(
                'name'     => $filename,
                'tmp_name' => $tmp_file,
            );

            $attachment_id = media_handle_sideload( $file_array, 0, $name_no_ext );

            if ( is_wp_error( $attachment_id ) ) {
                WP_CLI::warning( sprintf( 'Erreur pour %s : %s', $filename, $attachment_id->get_error_message() ) );
                @unlink( $tmp_file );
                $errors++;
            } else {
                $uploaded++;
            }

            $progress->tick();
        }

        $progress->finish();

        WP_CLI::success( sprintf(
            'Terminé — %d uploadés, %d déjà existants, %d erreurs (sur %d fichiers).',
            $uploaded, $skipped, $errors, $total
        ) );
    }

    /**
     * Génère un rapport de mapping pour chaque piscine avec AquaCove activé.
     *
     * Pour chaque piscine : liste les couleurs ↔ fonds PNG et
     * les zones × tapis ↔ overlays PNG, avec l'attachment ID s'il existe
     * déjà en médiathèque.
     *
     * ## OPTIONS
     *
     * [--format=<format>]
     * : Format de sortie (table, csv, json).
     * ---
     * default: table
     * options:
     *   - table
     *   - csv
     *   - json
     * ---
     *
     * ## EXAMPLES
     *
     *     wp mova mapping-report
     *     wp mova mapping-report --format=csv
     *
     * @subcommand mapping-report
     */
    public function mapping_report( $args, $assoc_args ) {
        $format = \WP_CLI\Utils\get_flag_value( $assoc_args, 'format', 'table' );

        // Cache : filename → attachment_id
        $media_cache = $this->build_media_cache();

        $piscines = get_posts( array(
            'post_type'   => 'piscine',
            'numberposts' => -1,
            'post_status' => 'publish',
        ) );

        if ( empty( $piscines ) ) {
            WP_CLI::warning( 'Aucune piscine publiée trouvée.' );
            return;
        }

        $rows = array();

        foreach ( $piscines as $piscine ) {
            $pid = $piscine->ID;

            if ( ! get_field( 'opt_aquacove', $pid ) ) {
                continue;
            }

            $slug_dim = get_field( 'slug_dimension', $pid );
            if ( ! $slug_dim ) {
                WP_CLI::warning( sprintf( '[%d] %s — slug_dimension vide, ignoré.', $pid, $piscine->post_title ) );
                continue;
            }

            $zones = get_field( 'zones_aquacove', $pid );
            if ( ! is_array( $zones ) ) {
                $zones = array();
            }

            // --- Couleurs (fonds) ---
            $couleurs_raw = get_field( 'couleurs_disponibles', $pid );
            if ( is_array( $couleurs_raw ) ) {
                foreach ( $couleurs_raw as $term ) {
                    $term_obj = is_object( $term ) ? $term : get_term( intval( $term ), 'couleur_piscine' );
                    if ( ! $term_obj || is_wp_error( $term_obj ) ) continue;

                    $slug_fichier = get_field( 'slug_fichier', 'couleur_piscine_' . $term_obj->term_id );
                    if ( ! $slug_fichier ) continue;

                    $filename = 'piscine-' . $slug_dim . '-' . $slug_fichier;
                    $att_id   = isset( $media_cache[ $filename ] ) ? $media_cache[ $filename ] : '—';

                    $rows[] = array(
                        'piscine'    => $piscine->post_title,
                        'piscine_id' => $pid,
                        'type'       => 'fond',
                        'zone'       => '—',
                        'terme'      => $term_obj->name,
                        'terme_slug' => $term_obj->slug,
                        'fichier'    => $filename . '.png',
                        'att_id'     => $att_id,
                    );
                }
            }

            // --- Tapis (overlays) par zone ---
            $tapis_raw = get_field( 'tapis_disponibles', $pid );
            if ( is_array( $tapis_raw ) && ! empty( $zones ) ) {
                foreach ( $zones as $zone ) {
                    foreach ( $tapis_raw as $term ) {
                        $term_obj = is_object( $term ) ? $term : get_term( intval( $term ), 'modele_tapis' );
                        if ( ! $term_obj || is_wp_error( $term_obj ) ) continue;

                        $slug_fichier = get_field( 'slug_fichier', 'modele_tapis_' . $term_obj->term_id );
                        if ( ! $slug_fichier ) continue;

                        $filename = $slug_dim . '-' . $slug_fichier . '-' . $zone;
                        $att_id   = isset( $media_cache[ $filename ] ) ? $media_cache[ $filename ] : '—';

                        $rows[] = array(
                            'piscine'    => $piscine->post_title,
                            'piscine_id' => $pid,
                            'type'       => 'overlay',
                            'zone'       => $zone,
                            'terme'      => $term_obj->name,
                            'terme_slug' => $term_obj->slug,
                            'fichier'    => $filename . '.png',
                            'att_id'     => $att_id,
                        );
                    }
                }
            }
        }

        if ( empty( $rows ) ) {
            WP_CLI::warning( 'Aucune association trouvée.' );
            return;
        }

        WP_CLI::log( sprintf( '%d associations trouvées.', count( $rows ) ) );

        \WP_CLI\Utils\format_items(
            $format,
            $rows,
            array( 'piscine', 'piscine_id', 'type', 'zone', 'terme', 'terme_slug', 'fichier', 'att_id' )
        );
    }

    /**
     * Construit un cache filename (sans extension) → attachment_id
     * pour tous les attachments dont le titre commence par "piscine-" ou contient un slug dimension.
     */
    private function build_media_cache() {
        global $wpdb;

        $results = $wpdb->get_results(
            "SELECT ID, post_title FROM {$wpdb->posts}
             WHERE post_type = 'attachment'
               AND post_status = 'inherit'
               AND post_mime_type = 'image/png'",
            OBJECT
        );

        $cache = array();
        if ( $results ) {
            foreach ( $results as $row ) {
                $cache[ $row->post_title ] = (int) $row->ID;
            }
        }

        return $cache;
    }
}

WP_CLI::add_command( 'mova', 'Mova_Configurator_Migration' );
