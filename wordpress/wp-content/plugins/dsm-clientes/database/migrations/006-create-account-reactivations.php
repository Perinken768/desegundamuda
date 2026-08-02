<?php

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

return static function (): void {
    global $wpdb;

    $tableName = $wpdb->prefix
        . 'dsm_customer_account_reactivations';

    $charsetCollate = $wpdb->get_charset_collate();

    $sql = "
        CREATE TABLE {$tableName} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            customer_id bigint(20) unsigned NOT NULL,
            token_hash char(64) NOT NULL,
            created_at datetime NOT NULL,
            expires_at datetime NOT NULL,
            used_at datetime DEFAULT NULL,
            PRIMARY KEY (id),
            KEY customer_id (customer_id),
            KEY expires_at (expires_at),
            KEY used_at (used_at),
            UNIQUE KEY token_hash (token_hash)
        ) {$charsetCollate};
    ";

    require_once ABSPATH
        . 'wp-admin/includes/upgrade.php';

    dbDelta($sql);
};
