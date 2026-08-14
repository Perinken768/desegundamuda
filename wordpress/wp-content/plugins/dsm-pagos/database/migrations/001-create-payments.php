<?php

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

return static function (): void {
    global $wpdb;

    require_once ABSPATH
        . 'wp-admin/includes/upgrade.php';

    $charsetCollate =
        $wpdb->get_charset_collate();

    $tableName =
        $wpdb->prefix
        . 'dsm_payments';

    $sql = "
        CREATE TABLE {$tableName} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,

            customer_id bigint(20) unsigned NOT NULL,

            purpose varchar(50) NOT NULL,

            amount decimal(10,2) unsigned NOT NULL,

            currency char(3) NOT NULL DEFAULT 'EUR',

            status varchar(30) NOT NULL DEFAULT 'pending',

            provider varchar(50) DEFAULT NULL,

            provider_reference varchar(190) DEFAULT NULL,

            source_type varchar(50) DEFAULT NULL,

            source_id bigint(20) unsigned DEFAULT NULL,

            source_reference varchar(190) DEFAULT NULL,

            created_at datetime NOT NULL,

            updated_at datetime NOT NULL,

            paid_at datetime DEFAULT NULL,

            failed_at datetime DEFAULT NULL,

            cancelled_at datetime DEFAULT NULL,

            PRIMARY KEY  (id),

            KEY customer_id (
                customer_id
            ),

            KEY status (
                status
            ),

            KEY purpose (
                purpose
            ),

            KEY provider_reference (
                provider_reference
            ),

            KEY source (
                source_type,
                source_id
            ),

            KEY customer_status (
                customer_id,
                status
            )
        ) {$charsetCollate};
    ";

    dbDelta(
        $sql
    );
};
