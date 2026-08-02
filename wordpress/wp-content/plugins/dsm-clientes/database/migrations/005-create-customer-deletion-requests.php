<?php

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

return static function (): void {
    global $wpdb;

    $tableName = $wpdb->prefix
        . 'dsm_customer_deletion_requests';

    $charsetCollate = $wpdb->get_charset_collate();

    $sql = "
        CREATE TABLE {$tableName} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            customer_id bigint(20) unsigned NOT NULL,
            status varchar(30) NOT NULL,
            confirmation_token_hash char(64) DEFAULT NULL,
            requested_at datetime NOT NULL,
            confirmed_at datetime DEFAULT NULL,
            scheduled_at datetime DEFAULT NULL,
            cancelled_at datetime DEFAULT NULL,
            completed_at datetime DEFAULT NULL,
            created_at datetime NOT NULL,
            updated_at datetime NOT NULL,
            PRIMARY KEY (id),
            KEY customer_id (customer_id),
            KEY status (status),
            KEY scheduled_at (scheduled_at),
            UNIQUE KEY confirmation_token_hash (
                confirmation_token_hash
            )
        ) {$charsetCollate};
    ";

    require_once ABSPATH
        . 'wp-admin/includes/upgrade.php';

    dbDelta($sql);
};