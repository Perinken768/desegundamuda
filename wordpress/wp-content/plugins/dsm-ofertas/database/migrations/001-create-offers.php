<?php

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

return static function (): void {
    global $wpdb;

    require_once ABSPATH
        . 'wp-admin/includes/upgrade.php';

    $offersTable =
        $wpdb->prefix
        . 'dsm_offers';

    $rulesTable =
        $wpdb->prefix
        . 'dsm_offer_plan_rules';

    $customersTable =
        $wpdb->prefix
        . 'dsm_offer_customers';

    $redemptionsTable =
        $wpdb->prefix
        . 'dsm_offer_redemptions';

    $charsetCollate =
        $wpdb->get_charset_collate();

    /*
     * ========================================================
     * OFERTAS / CAMPAÑAS
     * ========================================================
     *
     * Ejemplos:
     *
     * - Lanzamiento DSM 2026
     * - Retención Multitienda
     * - Renovación comercios 2027
     *
     * scope:
     *
     * - general
     * - customers
     *
     * status:
     *
     * - draft
     * - active
     * - paused
     * - archived
     */
    $offersSql = "
        CREATE TABLE {$offersTable} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,

            code varchar(80) NOT NULL,

            name varchar(150) NOT NULL,

            description text DEFAULT NULL,

            scope varchar(30) NOT NULL DEFAULT 'general',

            status varchar(30) NOT NULL DEFAULT 'draft',

            starts_at datetime DEFAULT NULL,

            ends_at datetime DEFAULT NULL,

            priority int(11) NOT NULL DEFAULT 0,

            stackable tinyint(1) unsigned NOT NULL DEFAULT 0,

            created_at datetime NOT NULL,

            updated_at datetime NOT NULL,

            PRIMARY KEY (id),

            UNIQUE KEY code (
                code
            ),

            KEY scope (
                scope
            ),

            KEY status (
                status
            ),

            KEY starts_at (
                starts_at
            ),

            KEY ends_at (
                ends_at
            ),

            KEY priority (
                priority
            ),

            KEY active_period (
                status,
                starts_at,
                ends_at
            )
        ) {$charsetCollate};
    ";

    dbDelta(
        $offersSql
    );

    /*
     * ========================================================
     * REGLAS POR PLAN
     * ========================================================
     *
     * Una misma oferta puede aplicar condiciones diferentes
     * a cada plan.
     *
     * benefit_type:
     *
     * - free_months
     * - percentage_discount
     * - fixed_discount
     * - special_price
     *
     * Ejemplos:
     *
     * Oferta lanzamiento:
     *
     * Plus:
     * benefit_type  = free_months
     * benefit_value = 1
     *
     * Multitienda:
     * benefit_type  = free_months
     * benefit_value = 2
     *
     *
     * Oferta renovación:
     *
     * Multitienda:
     * benefit_type   = percentage_discount
     * benefit_value  = 10
     * duration_months = 12
     */
    $rulesSql = "
        CREATE TABLE {$rulesTable} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,

            offer_id bigint(20) unsigned NOT NULL,

            plan_id bigint(20) unsigned NOT NULL,

            benefit_type varchar(40) NOT NULL,

            benefit_value decimal(10,2) unsigned NOT NULL DEFAULT 0.00,

            duration_months int(10) unsigned DEFAULT NULL,

            applies_to varchar(40) NOT NULL DEFAULT 'any_subscription',

            sort_order int(10) unsigned NOT NULL DEFAULT 0,

            created_at datetime NOT NULL,

            updated_at datetime NOT NULL,

            PRIMARY KEY (id),

            UNIQUE KEY offer_plan (
                offer_id,
                plan_id
            ),

            KEY offer_id (
                offer_id
            ),

            KEY plan_id (
                plan_id
            ),

            KEY benefit_type (
                benefit_type
            ),

            KEY applies_to (
                applies_to
            )
        ) {$charsetCollate};
    ";

    dbDelta(
        $rulesSql
    );

    /*
     * ========================================================
     * CLIENTES ASIGNADOS
     * ========================================================
     *
     * Solo se utiliza cuando:
     *
     * scope = customers
     *
     * Ejemplo:
     *
     * Oferta de retención asignada únicamente
     * al cliente 25.
     *
     * status:
     *
     * - assigned
     * - revoked
     */
    $customersSql = "
        CREATE TABLE {$customersTable} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,

            offer_id bigint(20) unsigned NOT NULL,

            customer_id bigint(20) unsigned NOT NULL,

            status varchar(30) NOT NULL DEFAULT 'assigned',

            internal_note text DEFAULT NULL,

            created_at datetime NOT NULL,

            updated_at datetime NOT NULL,

            PRIMARY KEY (id),

            UNIQUE KEY offer_customer (
                offer_id,
                customer_id
            ),

            KEY offer_id (
                offer_id
            ),

            KEY customer_id (
                customer_id
            ),

            KEY status (
                status
            ),

            KEY customer_status (
                customer_id,
                status
            )
        ) {$charsetCollate};
    ";

    dbDelta(
        $customersSql
    );

    /*
     * ========================================================
     * UTILIZACIONES / REDEMPTIONS
     * ========================================================
     *
     * Esta tabla NO representa una oferta disponible.
     *
     * Representa una oferta que ya ha sido aceptada
     * o aplicada a un cliente.
     *
     * Guardamos un snapshot económico para no depender
     * de que posteriormente cambie el precio del plan
     * o la configuración de la oferta.
     *
     * status:
     *
     * - accepted
     * - active
     * - completed
     * - cancelled
     * - revoked
     */
    $redemptionsSql = "
        CREATE TABLE {$redemptionsTable} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,

            offer_id bigint(20) unsigned NOT NULL,

            offer_rule_id bigint(20) unsigned NOT NULL,

            customer_id bigint(20) unsigned NOT NULL,

            plan_id bigint(20) unsigned NOT NULL,

            subscription_id bigint(20) unsigned DEFAULT NULL,

            status varchar(30) NOT NULL DEFAULT 'accepted',

            benefit_type varchar(40) NOT NULL,

            benefit_value decimal(10,2) unsigned NOT NULL DEFAULT 0.00,

            duration_months int(10) unsigned DEFAULT NULL,

            original_price decimal(10,2) unsigned DEFAULT NULL,

            discount_amount decimal(10,2) unsigned DEFAULT NULL,

            final_price decimal(10,2) unsigned DEFAULT NULL,

            currency char(3) DEFAULT NULL,

            benefit_starts_at datetime DEFAULT NULL,

            benefit_ends_at datetime DEFAULT NULL,

            accepted_at datetime DEFAULT NULL,

            completed_at datetime DEFAULT NULL,

            cancelled_at datetime DEFAULT NULL,

            created_at datetime NOT NULL,

            updated_at datetime NOT NULL,

            PRIMARY KEY (id),

            UNIQUE KEY offer_customer_plan (
                offer_id,
                customer_id,
                plan_id
            ),

            KEY offer_id (
                offer_id
            ),

            KEY offer_rule_id (
                offer_rule_id
            ),

            KEY customer_id (
                customer_id
            ),

            KEY plan_id (
                plan_id
            ),

            KEY subscription_id (
                subscription_id
            ),

            KEY status (
                status
            ),

            KEY customer_status (
                customer_id,
                status
            ),

            KEY subscription_status (
                subscription_id,
                status
            )
        ) {$charsetCollate};
    ";

    dbDelta(
        $redemptionsSql
    );

    /*
     * ========================================================
     * COMPROBACIÓN FINAL
     * ========================================================
     */

    $requiredTables = [
        $offersTable,
        $rulesTable,
        $customersTable,
        $redemptionsTable,
    ];

    foreach (
        $requiredTables
        as $requiredTable
    ) {
        $existingTable =
            $wpdb->get_var(
                $wpdb->prepare(
                    'SHOW TABLES LIKE %s',
                    $requiredTable
                )
            );

        if ($existingTable !== $requiredTable) {
            throw new RuntimeException(
                sprintf(
                    'No se pudo crear la tabla %s de DSM Ofertas.',
                    $requiredTable
                )
            );
        }
    }
};
