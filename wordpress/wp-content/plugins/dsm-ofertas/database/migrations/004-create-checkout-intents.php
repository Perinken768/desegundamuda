<?php

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

return static function (): void {
    global $wpdb;

    require_once ABSPATH
        . 'wp-admin/includes/upgrade.php';

    $table =
        $wpdb->prefix
        . 'dsm_offer_checkout_intents';

    $charsetCollate =
        $wpdb->get_charset_collate();

    $sql = "
        CREATE TABLE {$table} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,

            payment_id bigint(20) unsigned NOT NULL,

            offer_id bigint(20) unsigned NOT NULL,

            offer_rule_id bigint(20) unsigned NOT NULL,

            customer_id bigint(20) unsigned NOT NULL,

            plan_id bigint(20) unsigned NOT NULL,

            benefit_type varchar(40) NOT NULL,

            benefit_value decimal(10,2) unsigned NOT NULL DEFAULT 0.00,

            duration_months int(10) unsigned DEFAULT NULL,

            original_price decimal(10,2) unsigned NOT NULL,

            final_price decimal(10,2) unsigned NOT NULL,

            currency char(3) NOT NULL,

            trial_end datetime DEFAULT NULL,

            status varchar(30) NOT NULL DEFAULT 'pending',

            completed_at datetime DEFAULT NULL,

            created_at datetime NOT NULL,

            updated_at datetime NOT NULL,

            PRIMARY KEY (id),

            UNIQUE KEY payment_unique (
                payment_id
            ),

            KEY offer_id (
                offer_id
            ),

            KEY customer_id (
                customer_id
            ),

            KEY plan_id (
                plan_id
            ),

            KEY status (
                status
            )
        ) {$charsetCollate};
    ";

    dbDelta(
        $sql
    );

    $exists =
        $wpdb->get_var(
            $wpdb->prepare(
                'SHOW TABLES LIKE %s',
                $table
            )
        );

    if ($exists !== $table) {
        throw new RuntimeException(
            'No se pudo crear la tabla de intenciones de ofertas.'
        );
    }
};
