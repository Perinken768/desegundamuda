<?php

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

return static function (): void {
    global $wpdb;

    require_once ABSPATH
        . 'wp-admin/includes/upgrade.php';

    $plansTable =
        $wpdb->prefix
        . 'dsm_subscription_plans';

    $featuresTable =
        $wpdb->prefix
        . 'dsm_subscription_plan_features';

    $charsetCollate =
        $wpdb->get_charset_collate();

    /*
     * Planes comerciales.
     */
    $plansSql = "
        CREATE TABLE {$plansTable} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,

            code varchar(50) NOT NULL,

            name varchar(120) NOT NULL,

            description text DEFAULT NULL,

            price decimal(10,2) unsigned NOT NULL DEFAULT 0.00,

            currency char(3) NOT NULL DEFAULT 'EUR',

            billing_interval varchar(30) NOT NULL DEFAULT 'month',

            billing_interval_count int(10) unsigned NOT NULL DEFAULT 1,

            is_free tinyint(1) unsigned NOT NULL DEFAULT 0,

            is_active tinyint(1) unsigned NOT NULL DEFAULT 1,

            sort_order int(10) unsigned NOT NULL DEFAULT 0,

            created_at datetime NOT NULL,

            updated_at datetime NOT NULL,

            PRIMARY KEY (id),

            UNIQUE KEY code (
                code
            ),

            KEY is_active (
                is_active
            ),

            KEY sort_order (
                sort_order
            ),

            KEY active_order (
                is_active,
                sort_order
            )
        ) {$charsetCollate};
    ";

    dbDelta(
        $plansSql
    );

    /*
     * Prestaciones de cada plan.
     *
     * No codificamos las prestaciones como columnas fijas.
     *
     * Ejemplos:
     *
     * max_active_ads = 10
     * advertising = 1
     * multistore = 1
     */
    $featuresSql = "
        CREATE TABLE {$featuresTable} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,

            plan_id bigint(20) unsigned NOT NULL,

            feature_key varchar(100) NOT NULL,

            feature_value varchar(190) DEFAULT NULL,

            created_at datetime NOT NULL,

            updated_at datetime NOT NULL,

            PRIMARY KEY (id),

            UNIQUE KEY plan_feature (
                plan_id,
                feature_key
            ),

            KEY plan_id (
                plan_id
            ),

            KEY feature_key (
                feature_key
            )
        ) {$charsetCollate};
    ";

    dbDelta(
        $featuresSql
    );

    $plansTableExists =
        $wpdb->get_var(
            $wpdb->prepare(
                'SHOW TABLES LIKE %s',
                $plansTable
            )
        );

    if ($plansTableExists !== $plansTable) {
        throw new RuntimeException(
            'No se pudo crear la tabla de planes de suscripción.'
        );
    }

    $featuresTableExists =
        $wpdb->get_var(
            $wpdb->prepare(
                'SHOW TABLES LIKE %s',
                $featuresTable
            )
        );

    if ($featuresTableExists !== $featuresTable) {
        throw new RuntimeException(
            'No se pudo crear la tabla de prestaciones de los planes.'
        );
    }

    /*
     * Planes iniciales.
     *
     * Son datos iniciales, no lógica codificada.
     * Posteriormente podrán modificarse desde administración.
     */
    $plans = [
        [
            'code' =>
                'free',

            'name' =>
                'Free',

            'description' =>
                'Plan gratuito de DeSegundaMuda.',

            'price' =>
                '0.00',

            'currency' =>
                'EUR',

            'billing_interval' =>
                'month',

            'billing_interval_count' =>
                1,

            'is_free' =>
                1,

            'is_active' =>
                1,

            'sort_order' =>
                10,

            'features' => [
                'max_active_ads' =>
                    '10',
            ],
        ],

        [
            'code' =>
                'ads_50',

            'name' =>
                '50 anuncios',

            'description' =>
                'Amplía el límite hasta 50 anuncios activos.',

            'price' =>
                '4.99',

            'currency' =>
                'EUR',

            'billing_interval' =>
                'month',

            'billing_interval_count' =>
                1,

            'is_free' =>
                0,

            'is_active' =>
                1,

            'sort_order' =>
                20,

            'features' => [
                'max_active_ads' =>
                    '50',
            ],
        ],

        [
            'code' =>
                'advertising',

            'name' =>
                'Publicidad',

            'description' =>
                'Permite utilizar espacios publicitarios en los banners de DeSegundaMuda.',

            'price' =>
                '9.99',

            'currency' =>
                'EUR',

            'billing_interval' =>
                'month',

            'billing_interval_count' =>
                1,

            'is_free' =>
                0,

            'is_active' =>
                1,

            'sort_order' =>
                30,

            'features' => [
                'advertising' =>
                    '1',
            ],
        ],

        [
            'code' =>
                'multistore',

            'name' =>
                'Multitienda',

            'description' =>
                'Acceso a las funcionalidades de multitienda con publicaciones ilimitadas.',

            'price' =>
                '19.99',

            'currency' =>
                'EUR',

            'billing_interval' =>
                'month',

            'billing_interval_count' =>
                1,

            'is_free' =>
                0,

            'is_active' =>
                1,

            'sort_order' =>
                40,

            'features' => [
                'multistore' =>
                    '1',

                'max_active_ads' =>
                    '-1',
            ],
        ],
    ];

    $now =
        current_time(
            'mysql',
            true
        );

    foreach ($plans as $planData) {
        $existingPlanId =
            $wpdb->get_var(
                $wpdb->prepare(
                    "SELECT id
                    FROM {$plansTable}
                    WHERE code = %s
                    LIMIT 1",
                    $planData['code']
                )
            );

        if ($existingPlanId === null) {
            $inserted =
                $wpdb->insert(
                    $plansTable,
                    [
                        'code' =>
                            $planData['code'],

                        'name' =>
                            $planData['name'],

                        'description' =>
                            $planData['description'],

                        'price' =>
                            $planData['price'],

                        'currency' =>
                            $planData['currency'],

                        'billing_interval' =>
                            $planData['billing_interval'],

                        'billing_interval_count' =>
                            $planData['billing_interval_count'],

                        'is_free' =>
                            $planData['is_free'],

                        'is_active' =>
                            $planData['is_active'],

                        'sort_order' =>
                            $planData['sort_order'],

                        'created_at' =>
                            $now,

                        'updated_at' =>
                            $now,
                    ]
                );

            if ($inserted === false) {
                throw new RuntimeException(
                    sprintf(
                        'No se pudo crear el plan de suscripción %s: %s',
                        $planData['code'],
                        $wpdb->last_error
                    )
                );
            }

            $planId =
                (int) $wpdb->insert_id;
        } else {
            $planId =
                (int) $existingPlanId;
        }

        foreach (
            $planData['features']
            as $featureKey => $featureValue
        ) {
            $existingFeatureId =
                $wpdb->get_var(
                    $wpdb->prepare(
                        "SELECT id
                        FROM {$featuresTable}
                        WHERE plan_id = %d
                        AND feature_key = %s
                        LIMIT 1",
                        $planId,
                        $featureKey
                    )
                );

            if ($existingFeatureId !== null) {
                continue;
            }

            $insertedFeature =
                $wpdb->insert(
                    $featuresTable,
                    [
                        'plan_id' =>
                            $planId,

                        'feature_key' =>
                            $featureKey,

                        'feature_value' =>
                            $featureValue,

                        'created_at' =>
                            $now,

                        'updated_at' =>
                            $now,
                    ]
                );

            if ($insertedFeature === false) {
                throw new RuntimeException(
                    sprintf(
                        'No se pudo crear la prestación %s del plan %s: %s',
                        $featureKey,
                        $planData['code'],
                        $wpdb->last_error
                    )
                );
            }
        }
    }
};
