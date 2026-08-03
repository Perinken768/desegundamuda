<?php

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

return static function (): void {
    global $wpdb;

    require_once ABSPATH
        . 'wp-admin/includes/upgrade.php';

    $tableName =
        $wpdb->prefix
        . 'dsm_brands';

    $charsetCollate =
        $wpdb->get_charset_collate();

    $sql = "
        CREATE TABLE {$tableName} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            name varchar(120) NOT NULL,
            slug varchar(150) NOT NULL,
            description text NULL,
            website varchar(255) NULL,
            logo_id bigint(20) unsigned NULL,
            is_active tinyint(1) unsigned NOT NULL DEFAULT 1,
            is_verified tinyint(1) unsigned NOT NULL DEFAULT 1,
            sort_order int(10) unsigned NOT NULL DEFAULT 0,
            created_at datetime NOT NULL,
            updated_at datetime NOT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY slug (slug),
            UNIQUE KEY name (name),
            KEY is_active (is_active),
            KEY is_verified (is_verified),
            KEY sort_order (sort_order),
            KEY active_order (
                is_active,
                sort_order
            )
        ) {$charsetCollate};
    ";

    dbDelta($sql);
};