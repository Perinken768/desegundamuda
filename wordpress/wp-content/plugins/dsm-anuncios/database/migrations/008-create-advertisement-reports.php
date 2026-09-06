<?php

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Crea la tabla de denuncias de anuncios.
 *
 * Importante:
 *
 * Una denuncia NO modifica automáticamente el estado
 * del anuncio. El anuncio continúa visible hasta que
 * administración tome una decisión.
 */
return static function (): void {
    global $wpdb;

    require_once ABSPATH
        . 'wp-admin/includes/upgrade.php';

    $tableName =
        $wpdb->prefix
        . 'dsm_ad_reports';

    $charsetCollate =
        $wpdb->get_charset_collate();

    $sql = "
        CREATE TABLE {$tableName} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,

            advertisement_id bigint(20) unsigned NOT NULL,

            reporter_customer_id bigint(20) unsigned NOT NULL,

            reported_customer_id bigint(20) unsigned NOT NULL,

            reason_code varchar(50) NOT NULL,

            details text DEFAULT NULL,

            status varchar(30) NOT NULL DEFAULT 'pending',

            admin_notes text DEFAULT NULL,

            reviewed_by_user_id bigint(20) unsigned DEFAULT NULL,

            reviewed_at datetime DEFAULT NULL,

            created_at datetime NOT NULL,

            updated_at datetime NOT NULL,

            PRIMARY KEY  (id),

            UNIQUE KEY reporter_advertisement (
                reporter_customer_id,
                advertisement_id
            ),

            KEY advertisement_id (
                advertisement_id
            ),

            KEY reporter_customer_id (
                reporter_customer_id
            ),

            KEY reported_customer_id (
                reported_customer_id
            ),

            KEY status (
                status
            ),

            KEY created_at (
                created_at
            )
        ) {$charsetCollate};
    ";

    dbDelta(
        $sql
    );

    $existingTable =
        $wpdb->get_var(
            $wpdb->prepare(
                'SHOW TABLES LIKE %s',
                $tableName
            )
        );

    if ($existingTable !== $tableName) {
        throw new RuntimeException(
            'No se pudo crear la tabla de denuncias de anuncios.'
        );
    }
};
