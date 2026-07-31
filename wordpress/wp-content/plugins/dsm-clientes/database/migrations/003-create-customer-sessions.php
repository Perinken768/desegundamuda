<?php

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

return static function (): void {
    global $wpdb;

    require_once ABSPATH . 'wp-admin/includes/upgrade.php';

    $tableName      = $wpdb->prefix . 'dsm_customer_sessions';
    $charsetCollate = $wpdb->get_charset_collate();

    $sql = "
        CREATE TABLE {$tableName} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            customer_id BIGINT UNSIGNED NOT NULL,

            token_hash CHAR(64) NOT NULL,

            ip_address VARCHAR(45) NULL,
            user_agent VARCHAR(500) NULL,

            created_at DATETIME NOT NULL,
            last_activity_at DATETIME NOT NULL,
            expires_at DATETIME NOT NULL,
            revoked_at DATETIME NULL,

            PRIMARY KEY (id),
            UNIQUE KEY token_hash (token_hash),
            KEY customer_id (customer_id),
            KEY expires_at (expires_at),
            KEY revoked_at (revoked_at)
        ) {$charsetCollate};
    ";

    dbDelta($sql);
};