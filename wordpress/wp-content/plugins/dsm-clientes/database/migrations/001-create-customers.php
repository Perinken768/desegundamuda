<?php

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

return static function (): void {
    global $wpdb;

    require_once ABSPATH . 'wp-admin/includes/upgrade.php';

    $tableName      = $wpdb->prefix . 'dsm_customers';
    $charsetCollate = $wpdb->get_charset_collate();

    $sql = "
        CREATE TABLE {$tableName} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            email VARCHAR(190) NOT NULL,
            password_hash VARCHAR(255) NOT NULL,
            status VARCHAR(30) NOT NULL DEFAULT 'pending',

            email_verified_at DATETIME NULL,
            last_login_at DATETIME NULL,

            created_at DATETIME NOT NULL,
            updated_at DATETIME NOT NULL,

            PRIMARY KEY (id),
            UNIQUE KEY email (email),
            KEY status (status)
        ) {$charsetCollate};
    ";

    dbDelta($sql);
};