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
        . 'dsm_advertising_banners';

    $charsetCollate =
        $wpdb->get_charset_collate();

    $sql = "
        CREATE TABLE {$tableName} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            title varchar(180) NOT NULL,
            image_attachment_id bigint(20) unsigned NOT NULL,
            target_url varchar(2048) NOT NULL,
            area_id bigint(20) unsigned DEFAULT NULL,
            priority int(11) NOT NULL DEFAULT 0,
            status varchar(30) NOT NULL DEFAULT 'draft',
            starts_at datetime DEFAULT NULL,
            ends_at datetime DEFAULT NULL,
            created_at datetime NOT NULL,
            updated_at datetime NOT NULL,
            PRIMARY KEY  (id),
            KEY status (status),
            KEY area_id (area_id),
            KEY priority (priority),
            KEY starts_at (starts_at),
            KEY ends_at (ends_at)
        ) {$charsetCollate};
    ";

    dbDelta($sql);
};
