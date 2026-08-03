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
        . 'dsm_products';

    $charsetCollate =
        $wpdb->get_charset_collate();

    $sql = "
        CREATE TABLE {$tableName} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            store_id bigint(20) unsigned NOT NULL,
            brand_id bigint(20) unsigned NULL,
            name varchar(180) NOT NULL,
            slug varchar(200) NOT NULL,
            description longtext NULL,
            internal_reference varchar(100) NULL,
            base_sku varchar(100) NULL,
            default_price decimal(10,2) NOT NULL DEFAULT 0.00,
            original_price decimal(10,2) NULL,
            cost_price decimal(10,2) NULL,
            purchase_date date NULL,
            tax_rate decimal(5,2) NULL,
            track_stock tinyint(1) unsigned NOT NULL DEFAULT 1,
            status varchar(30) NOT NULL DEFAULT 'draft',
            created_by_customer_id bigint(20) unsigned NOT NULL,
            updated_by_customer_id bigint(20) unsigned NULL,
            created_at datetime NOT NULL,
            updated_at datetime NOT NULL,
            archived_at datetime NULL,
            PRIMARY KEY  (id),
            UNIQUE KEY store_slug (
                store_id,
                slug
            ),
            UNIQUE KEY store_internal_reference (
                store_id,
                internal_reference
            ),
            UNIQUE KEY store_base_sku (
                store_id,
                base_sku
            ),
            KEY store_id (store_id),
            KEY brand_id (brand_id),
            KEY status (status),
            KEY created_by_customer_id (
                created_by_customer_id
            ),
            KEY updated_by_customer_id (
                updated_by_customer_id
            ),
            KEY store_status (
                store_id,
                status
            ),
            KEY store_brand (
                store_id,
                brand_id
            ),
            KEY created_at (created_at)
        ) {$charsetCollate};
    ";

    dbDelta($sql);
};