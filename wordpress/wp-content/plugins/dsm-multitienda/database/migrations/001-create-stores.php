<?php

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

return static function (): void {
    global $wpdb;

    $tableName =
        $wpdb->prefix
        . 'dsm_multistore_stores';

    $charsetCollate =
        $wpdb->get_charset_collate();

    $sql = "
        CREATE TABLE {$tableName} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,

            customer_id BIGINT UNSIGNED NOT NULL,

            slug VARCHAR(190) NOT NULL,

            name VARCHAR(190) NOT NULL,

            description TEXT NULL,

            logo_attachment_id BIGINT UNSIGNED NULL,

            island VARCHAR(100) NULL,

            location_text VARCHAR(190) NULL,

            status VARCHAR(30) NOT NULL DEFAULT 'draft',

            created_at DATETIME NOT NULL,

            updated_at DATETIME NOT NULL,

            PRIMARY KEY (id),

            UNIQUE KEY uq_customer_id (
                customer_id
            ),

            UNIQUE KEY uq_slug (
                slug
            ),

            KEY idx_status (
                status
            ),

            KEY idx_island (
                island
            )
        ) {$charsetCollate};
    ";

    require_once ABSPATH
        . 'wp-admin/includes/upgrade.php';

    dbDelta(
        $sql
    );
};
