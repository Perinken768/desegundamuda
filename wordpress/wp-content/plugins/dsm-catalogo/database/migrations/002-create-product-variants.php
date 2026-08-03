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
        . 'dsm_product_variants';

    $charsetCollate =
        $wpdb->get_charset_collate();

    $sql = "
        CREATE TABLE {$tableName} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            product_id bigint(20) unsigned NOT NULL,
            sku varchar(120) NULL,
            barcode varchar(120) NULL,
            size_value varchar(80) NULL,
            color_value varchar(100) NULL,
            condition_code varchar(40) NULL,
            price decimal(10,2) NULL,
            original_price decimal(10,2) NULL,
            cost_price decimal(10,2) NULL,
            stock_quantity int(10) unsigned NOT NULL DEFAULT 0,
            stock_reserved int(10) unsigned NOT NULL DEFAULT 0,
            low_stock_threshold int(10) unsigned NULL,
            track_stock tinyint(1) unsigned NOT NULL DEFAULT 1,
            is_default tinyint(1) unsigned NOT NULL DEFAULT 0,
            is_active tinyint(1) unsigned NOT NULL DEFAULT 1,
            sort_order int(10) unsigned NOT NULL DEFAULT 0,
            created_at datetime NOT NULL,
            updated_at datetime NOT NULL,
            archived_at datetime NULL,
            PRIMARY KEY  (id),
            UNIQUE KEY sku (sku),
            UNIQUE KEY barcode (barcode),
            KEY product_id (product_id),
            KEY is_active (is_active),
            KEY is_default (is_default),
            KEY product_active (
                product_id,
                is_active
            ),
            KEY product_order (
                product_id,
                sort_order
            ),
            KEY product_stock (
                product_id,
                stock_quantity,
                stock_reserved
            ),
            KEY low_stock_threshold (
                low_stock_threshold
            )
        ) {$charsetCollate};
    ";

    dbDelta($sql);
};