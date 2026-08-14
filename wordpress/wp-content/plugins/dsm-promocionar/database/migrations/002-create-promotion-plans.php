<?php

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

return static function (): void {
    global $wpdb;

    $tableName =
        $wpdb->prefix
        . 'dsm_promotion_plans';

    $charsetCollate =
        $wpdb->get_charset_collate();

    require_once ABSPATH
        . 'wp-admin/includes/upgrade.php';

    $sql = "
        CREATE TABLE {$tableName} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            code varchar(50) NOT NULL,
            name varchar(120) NOT NULL,
            duration_seconds bigint(20) unsigned NOT NULL,
            price decimal(10,2) unsigned NOT NULL,
            currency char(3) NOT NULL DEFAULT 'EUR',
            is_active tinyint(1) unsigned NOT NULL DEFAULT 1,
            sort_order int(10) unsigned NOT NULL DEFAULT 0,
            created_at datetime NOT NULL,
            updated_at datetime NOT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY code (code),
            KEY is_active (is_active),
            KEY sort_order (sort_order),
            KEY active_order (
                is_active,
                sort_order
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
            'No se pudo crear la tabla de planes de promoción.'
        );
    }

    /*
     * Planes iniciales.
     *
     * Se insertan únicamente si no existe ya un plan
     * con el mismo código, de forma que futuras
     * migraciones nunca sobrescriban precios
     * configurados por administración.
     */
    $plans = [
        [
            'code' =>
                'promotion_3_days',

            'name' =>
                'Promoción 3 días',

            'duration_seconds' =>
                3 * DAY_IN_SECONDS,

            'price' =>
                '2.99',

            'sort_order' =>
                10,
        ],

        [
            'code' =>
                'promotion_5_days',

            'name' =>
                'Promoción 5 días',

            'duration_seconds' =>
                5 * DAY_IN_SECONDS,

            'price' =>
                '3.99',

            'sort_order' =>
                20,
        ],

        [
            'code' =>
                'promotion_7_days',

            'name' =>
                'Promoción 7 días',

            'duration_seconds' =>
                7 * DAY_IN_SECONDS,

            'price' =>
                '4.99',

            'sort_order' =>
                30,
        ],
    ];

    $now =
        current_time(
            'mysql',
            true
        );

    foreach ($plans as $plan) {
        $existingId =
            $wpdb->get_var(
                $wpdb->prepare(
                    "SELECT id
                    FROM {$tableName}
                    WHERE code = %s
                    LIMIT 1",
                    $plan['code']
                )
            );

        if ($existingId !== null) {
            continue;
        }

        $inserted =
            $wpdb->insert(
                $tableName,
                [
                    'code' =>
                        $plan['code'],

                    'name' =>
                        $plan['name'],

                    'duration_seconds' =>
                        $plan['duration_seconds'],

                    'price' =>
                        $plan['price'],

                    'currency' =>
                        'EUR',

                    'is_active' =>
                        1,

                    'sort_order' =>
                        $plan['sort_order'],

                    'created_at' =>
                        $now,

                    'updated_at' =>
                        $now,
                ]
            );

        if ($inserted === false) {
            throw new RuntimeException(
                sprintf(
                    'No se pudo crear el plan %s: %s',
                    $plan['code'],
                    $wpdb->last_error
                )
            );
        }
    }
};
