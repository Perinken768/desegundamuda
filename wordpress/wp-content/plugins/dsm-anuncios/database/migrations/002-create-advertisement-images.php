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
        . 'dsm_ad_images';

    $charsetCollate =
        $wpdb->get_charset_collate();

    $sql = "
        CREATE TABLE {$tableName} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            advertisement_id bigint(20) unsigned NOT NULL,
            attachment_id bigint(20) unsigned NOT NULL,
            sort_order int(10) unsigned NOT NULL DEFAULT 0,
            is_cover tinyint(1) unsigned NOT NULL DEFAULT 0,
            created_at datetime NOT NULL,
            updated_at datetime NOT NULL,
            PRIMARY KEY  (id),
            UNIQUE KEY advertisement_attachment (
                advertisement_id,
                attachment_id
            ),
            KEY advertisement_id (advertisement_id),
            KEY attachment_id (attachment_id),
            KEY advertisement_sort (
                advertisement_id,
                sort_order
            ),
            KEY advertisement_cover (
                advertisement_id,
                is_cover
            )
        ) {$charsetCollate};
    ";

    dbDelta($sql);
};