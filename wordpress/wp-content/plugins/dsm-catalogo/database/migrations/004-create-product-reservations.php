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
        . 'dsm_product_reservations';

    $charsetCollate =
        $wpdb->get_charset_collate();

    $sql = "
        CREATE TABLE {$tableName} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            product_id bigint(20) unsigned NOT NULL,
            variant_id bigint(20) unsigned NOT NULL,
            store_id bigint(20) unsigned NOT NULL,
            seller_customer_id bigint(20) unsigned NOT NULL,
            buyer_customer_id bigint(20) unsigned NULL,
            conversation_id bigint(20) unsigned NULL,
            external_contact varchar(190) NULL,
            quantity int(10) unsigned NOT NULL,
            status varchar(30) NOT NULL,
            reserved_at datetime NOT NULL,
            released_at datetime NULL,
            completed_at datetime NULL,
            cancelled_at datetime NULL,
            expires_at datetime NULL,
            created_at datetime NOT NULL,
            updated_at datetime NOT NULL,
            PRIMARY KEY  (id),
            KEY product_id (product_id),
            KEY variant_id (variant_id),
            KEY store_id (store_id),
            KEY seller_customer_id (
                seller_customer_id
            ),
            KEY buyer_customer_id (
                buyer_customer_id
            ),
            KEY conversation_id (
                conversation_id
            ),
            KEY status (status),
            KEY expires_at (expires_at),
            KEY variant_status (
                variant_id,
                status
            ),
            KEY store_status (
                store_id,
                status
            ),
            KEY seller_status (
                seller_customer_id,
                status
            ),
            KEY buyer_status (
                buyer_customer_id,
                status
            ),
            KEY conversation_status (
                conversation_id,
                status
            ),
            KEY active_expiration (
                status,
                expires_at
            ),
            KEY created_at (created_at)
        ) {$charsetCollate};
    ";

    dbDelta($sql);
};