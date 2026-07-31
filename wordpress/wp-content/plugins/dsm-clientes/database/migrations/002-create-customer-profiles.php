<?php

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

return static function (): void {
    global $wpdb;

    require_once ABSPATH . 'wp-admin/includes/upgrade.php';

    $tableName      = $wpdb->prefix . 'dsm_customer_profiles';
    $charsetCollate = $wpdb->get_charset_collate();

    $sql = "
        CREATE TABLE {$tableName} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            customer_id BIGINT UNSIGNED NOT NULL,

            display_name VARCHAR(150) NOT NULL,
            phone VARCHAR(30) NULL,
            whatsapp_phone VARCHAR(30) NULL,
            avatar_attachment_id BIGINT UNSIGNED NULL,
            bio TEXT NULL,

            island_id BIGINT UNSIGNED NULL,
            municipality_id BIGINT UNSIGNED NULL,

            created_at DATETIME NOT NULL,
            updated_at DATETIME NOT NULL,

            PRIMARY KEY (id),
            UNIQUE KEY customer_id (customer_id),
            KEY island_id (island_id),
            KEY municipality_id (municipality_id)
        ) {$charsetCollate};
    ";

    dbDelta($sql);
};