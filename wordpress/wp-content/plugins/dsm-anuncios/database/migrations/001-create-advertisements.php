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
        . 'dsm_ads';

    $charsetCollate =
        $wpdb->get_charset_collate();

    $sql = "
        CREATE TABLE {$tableName} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            customer_id bigint(20) unsigned NOT NULL,
            store_id bigint(20) unsigned NULL,
            category_id bigint(20) unsigned NOT NULL,
            island_id bigint(20) unsigned NULL,
            municipality_id bigint(20) unsigned NULL,
            title varchar(180) NOT NULL,
            slug varchar(200) NOT NULL,
            description longtext NOT NULL,
            price decimal(10,2) NOT NULL DEFAULT 0.00,
            original_price decimal(10,2) NULL,
            condition_code varchar(40) NOT NULL,
            status varchar(30) NOT NULL,
            rejection_reason text NULL,
            reserved_at datetime NULL,
            published_at datetime NULL,
            closed_at datetime NULL,
            created_at datetime NOT NULL,
            updated_at datetime NOT NULL,
            PRIMARY KEY  (id),
            UNIQUE KEY slug (slug),
            KEY customer_id (customer_id),
            KEY store_id (store_id),
            KEY category_id (category_id),
            KEY island_id (island_id),
            KEY municipality_id (municipality_id),
            KEY status (status),
            KEY published_at (published_at),
            KEY customer_status (
                customer_id,
                status
            ),
            KEY category_status (
                category_id,
                status
            )
        ) {$charsetCollate};
    ";

    dbDelta($sql);
};