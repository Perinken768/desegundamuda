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
        . 'dsm_stock_movements';

    $charsetCollate =
        $wpdb->get_charset_collate();

    $sql = "
        CREATE TABLE {$tableName} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            product_id bigint(20) unsigned NOT NULL,
            variant_id bigint(20) unsigned NOT NULL,
            store_id bigint(20) unsigned NOT NULL,
            movement_type varchar(40) NOT NULL,
            quantity_delta int(11) NOT NULL DEFAULT 0,
            reserved_delta int(11) NOT NULL DEFAULT 0,
            stock_quantity_before int(10) unsigned NOT NULL,
            stock_quantity_after int(10) unsigned NOT NULL,
            stock_reserved_before int(10) unsigned NOT NULL,
            stock_reserved_after int(10) unsigned NOT NULL,
            reference_type varchar(60) NULL,
            reference_id bigint(20) unsigned NULL,
            customer_id bigint(20) unsigned NULL,
            user_id bigint(20) unsigned NULL,
            notes text NULL,
            created_at datetime NOT NULL,
            PRIMARY KEY  (id),
            KEY product_id (product_id),
            KEY variant_id (variant_id),
            KEY store_id (store_id),
            KEY movement_type (movement_type),
            KEY reference_type (reference_type),
            KEY reference_id (reference_id),
            KEY customer_id (customer_id),
            KEY user_id (user_id),
            KEY variant_created (
                variant_id,
                created_at
            ),
            KEY store_created (
                store_id,
                created_at
            ),
            KEY reference_lookup (
                reference_type,
                reference_id
            )
        ) {$charsetCollate};
    ";

    dbDelta($sql);
};