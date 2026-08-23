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
        . 'dsm_subscriptions';

    $charsetCollate =
        $wpdb->get_charset_collate();

    $sql = "
        CREATE TABLE {$tableName} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,

            customer_id bigint(20) unsigned NOT NULL,

            plan_id bigint(20) unsigned NOT NULL,

            payment_id bigint(20) unsigned DEFAULT NULL,

            status varchar(30) NOT NULL DEFAULT 'active',

            starts_at datetime NOT NULL,

            ends_at datetime DEFAULT NULL,

            cancelled_at datetime DEFAULT NULL,

            price_paid decimal(10,2) unsigned DEFAULT NULL,

            currency char(3) DEFAULT NULL,

            source_type varchar(50) DEFAULT NULL,

            source_reference varchar(190) DEFAULT NULL,

            auto_renew tinyint(1) unsigned NOT NULL DEFAULT 0,

            created_at datetime NOT NULL,

            updated_at datetime NOT NULL,

            PRIMARY KEY (id),

            UNIQUE KEY payment_id_unique (
                payment_id
            ),

            KEY customer_id (
                customer_id
            ),

            KEY plan_id (
                plan_id
            ),

            KEY status (
                status
            ),

            KEY ends_at (
                ends_at
            ),

            KEY customer_status (
                customer_id,
                status
            ),

            KEY customer_plan_status (
                customer_id,
                plan_id,
                status
            ),

            KEY source (
                source_type,
                source_reference
            )
        ) {$charsetCollate};
    ";

    dbDelta(
        $sql
    );

    $tableExists =
        $wpdb->get_var(
            $wpdb->prepare(
                'SHOW TABLES LIKE %s',
                $tableName
            )
        );

    if ($tableExists !== $tableName) {
        throw new RuntimeException(
            'No se pudo crear la tabla de suscripciones.'
        );
    }
};
