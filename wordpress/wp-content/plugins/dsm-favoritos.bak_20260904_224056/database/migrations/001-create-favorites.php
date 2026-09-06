<?php

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

return static function (): void {
    global $wpdb;

    $table =
        $wpdb->prefix
        . 'dsm_favorites';

    $charsetCollate =
        $wpdb->get_charset_collate();

    require_once ABSPATH
        . 'wp-admin/includes/upgrade.php';

    $sql = "
        CREATE TABLE {$table} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            customer_id bigint(20) unsigned NOT NULL,
            advertisement_id bigint(20) unsigned NOT NULL,
            created_at datetime NOT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY customer_advertisement (
                customer_id,
                advertisement_id
            ),
            KEY customer_id (customer_id),
            KEY advertisement_id (advertisement_id),
            KEY created_at (created_at)
        ) {$charsetCollate};
    ";

    dbDelta(
        $sql
    );

    $tableExists =
        $wpdb->get_var(
            $wpdb->prepare(
                'SHOW TABLES LIKE %s',
                $table
            )
        );

    if ($tableExists !== $table) {
        throw new \RuntimeException(
            'No se pudo crear la tabla de favoritos.'
        );
    }
};