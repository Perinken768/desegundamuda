<?php

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

return static function (): void {
    global $wpdb;

    $tableName =
        $wpdb->prefix . 'dsm_customer_password_resets';

    $charsetCollate = $wpdb->get_charset_collate();

    $sql = "
        CREATE TABLE {$tableName} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            customer_id bigint(20) unsigned NOT NULL,
            token_hash char(64) NOT NULL,
            requested_at datetime NOT NULL,
            expires_at datetime NOT NULL,
            used_at datetime DEFAULT NULL,
            created_at datetime NOT NULL,
            updated_at datetime NOT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY token_hash (token_hash),
            KEY customer_id (customer_id),
            KEY expires_at (expires_at),
            KEY used_at (used_at)
        ) {$charsetCollate};
    ";

    require_once ABSPATH
        . 'wp-admin/includes/upgrade.php';

    dbDelta($sql);
};