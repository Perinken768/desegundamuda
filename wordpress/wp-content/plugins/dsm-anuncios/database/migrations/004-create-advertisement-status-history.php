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
        . 'dsm_ad_status_history';

    $charsetCollate =
        $wpdb->get_charset_collate();

    $sql = "
        CREATE TABLE {$tableName} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            advertisement_id bigint(20) unsigned NOT NULL,
            previous_status varchar(30) NULL,
            new_status varchar(30) NOT NULL,
            changed_by_customer_id bigint(20) unsigned NULL,
            changed_by_user_id bigint(20) unsigned NULL,
            notes text NULL,
            created_at datetime NOT NULL,
            PRIMARY KEY (id),
            KEY advertisement_id (advertisement_id),
            KEY previous_status (previous_status),
            KEY new_status (new_status),
            KEY changed_by_customer_id (
                changed_by_customer_id
            ),
            KEY changed_by_user_id (
                changed_by_user_id
            ),
            KEY advertisement_created (
                advertisement_id,
                created_at
            )
        ) {$charsetCollate};
    ";

    dbDelta($sql);
};