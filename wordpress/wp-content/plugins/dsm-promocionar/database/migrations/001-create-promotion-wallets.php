<?php

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

return static function (): void {
    global $wpdb;

    $walletsTable =
        $wpdb->prefix
        . 'dsm_promotion_wallets';

    $assignmentsTable =
        $wpdb->prefix
        . 'dsm_promotion_assignments';

    $charsetCollate =
        $wpdb->get_charset_collate();

    require_once ABSPATH
        . 'wp-admin/includes/upgrade.php';

    $walletsSql = "
        CREATE TABLE {$walletsTable} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            customer_id bigint(20) unsigned NOT NULL,
            purchased_seconds bigint(20) unsigned NOT NULL,
            remaining_seconds bigint(20) unsigned NOT NULL,
            status varchar(30) NOT NULL DEFAULT 'available',
            source_type varchar(50) DEFAULT NULL,
            source_reference varchar(190) DEFAULT NULL,
            created_at datetime NOT NULL,
            updated_at datetime NOT NULL,
            PRIMARY KEY (id),
            KEY customer_id (customer_id),
            KEY status (status),
            KEY customer_status (
                customer_id,
                status
            )
        ) {$charsetCollate};
    ";

    dbDelta(
        $walletsSql
    );

    $assignmentsSql = "
        CREATE TABLE {$assignmentsTable} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            wallet_id bigint(20) unsigned NOT NULL,
            customer_id bigint(20) unsigned NOT NULL,
            advertisement_id bigint(20) unsigned NOT NULL,
            started_at datetime NOT NULL,
            stopped_at datetime DEFAULT NULL,
            consumed_seconds bigint(20) unsigned NOT NULL DEFAULT 0,
            status varchar(30) NOT NULL DEFAULT 'active',
            created_at datetime NOT NULL,
            updated_at datetime NOT NULL,
            PRIMARY KEY (id),
            KEY wallet_id (wallet_id),
            KEY customer_id (customer_id),
            KEY advertisement_id (advertisement_id),
            KEY status (status),
            KEY advertisement_status (
                advertisement_id,
                status
            )
        ) {$charsetCollate};
    ";

    dbDelta(
        $assignmentsSql
    );

    $walletExists =
        $wpdb->get_var(
            $wpdb->prepare(
                'SHOW TABLES LIKE %s',
                $walletsTable
            )
        );

    if ($walletExists !== $walletsTable) {
        throw new RuntimeException(
            'No se pudo crear la tabla de saldos de promoción.'
        );
    }

    $assignmentsExists =
        $wpdb->get_var(
            $wpdb->prepare(
                'SHOW TABLES LIKE %s',
                $assignmentsTable
            )
        );

    if ($assignmentsExists !== $assignmentsTable) {
        throw new RuntimeException(
            'No se pudo crear la tabla de asignaciones de promoción.'
        );
    }
};
