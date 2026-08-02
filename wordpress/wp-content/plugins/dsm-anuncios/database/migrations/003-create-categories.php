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
        . 'dsm_categories';

    $charsetCollate =
        $wpdb->get_charset_collate();

    $sql = "
        CREATE TABLE {$tableName} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            parent_id bigint(20) unsigned NULL,
            name varchar(120) NOT NULL,
            slug varchar(140) NOT NULL,
            description text NULL,
            marketplace_allowed tinyint(1) unsigned NOT NULL DEFAULT 1,
            store_allowed tinyint(1) unsigned NOT NULL DEFAULT 1,
            is_active tinyint(1) unsigned NOT NULL DEFAULT 1,
            sort_order int(10) unsigned NOT NULL DEFAULT 0,
            created_at datetime NOT NULL,
            updated_at datetime NOT NULL,
            PRIMARY KEY  (id),
            UNIQUE KEY slug (slug),
            KEY parent_id (parent_id),
            KEY is_active (is_active),
            KEY marketplace_allowed (
                marketplace_allowed
            ),
            KEY store_allowed (
                store_allowed
            ),
            KEY category_order (
                parent_id,
                sort_order
            )
        ) {$charsetCollate};
    ";

    dbDelta($sql);
};