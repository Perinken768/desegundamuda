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

    $billingProfilesTable =
        $wpdb->prefix
        . 'dsm_billing_profiles';

    $sequencesTable =
        $wpdb->prefix
        . 'dsm_invoice_sequences';

    $invoicesTable =
        $wpdb->prefix
        . 'dsm_invoices';

    $itemsTable =
        $wpdb->prefix
        . 'dsm_invoice_items';

    /*
     * ========================================================
     * DATOS FISCALES DEL CLIENTE
     * ========================================================
     *
     * Estos son los datos actuales del cliente.
     *
     * Cuando se emita una factura se copiarán dentro de
     * dsm_invoices, de forma que una modificación posterior
     * del perfil fiscal nunca altere facturas antiguas.
     */

    $billingProfilesSql = "
        CREATE TABLE {$billingProfilesTable} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,

            customer_id bigint(20) unsigned NOT NULL,

            customer_type varchar(20) NOT NULL DEFAULT 'individual',

            fiscal_name varchar(190) NOT NULL,

            tax_id varchar(50) DEFAULT NULL,

            address_line_1 varchar(190) DEFAULT NULL,

            address_line_2 varchar(190) DEFAULT NULL,

            postal_code varchar(20) DEFAULT NULL,

            city varchar(120) DEFAULT NULL,

            province varchar(120) DEFAULT NULL,

            country_code char(2) NOT NULL DEFAULT 'ES',

            billing_email varchar(190) DEFAULT NULL,

            created_at datetime NOT NULL,

            updated_at datetime NOT NULL,

            PRIMARY KEY (id),

            UNIQUE KEY customer_id (
                customer_id
            ),

            KEY tax_id (
                tax_id
            )
        ) {$charsetCollate};
    ";

    dbDelta(
        $billingProfilesSql
    );

    /*
     * ========================================================
     * SECUENCIAS DE NUMERACIÓN
     * ========================================================
     *
     * No utilizamos una option de WordPress para numerar.
     *
     * Esta tabla permitirá bloquear la fila mediante
     * SELECT ... FOR UPDATE cuando emitamos una factura,
     * evitando que dos pagos simultáneos obtengan el mismo
     * número.
     *
     * Ejemplo:
     *
     * serie DSM
     * ejercicio 2026
     * último número 14
     */

    $sequencesSql = "
        CREATE TABLE {$sequencesTable} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,

            series varchar(30) NOT NULL,

            fiscal_year smallint(5) unsigned NOT NULL,

            last_number bigint(20) unsigned NOT NULL DEFAULT 0,

            created_at datetime NOT NULL,

            updated_at datetime NOT NULL,

            PRIMARY KEY (id),

            UNIQUE KEY series_year (
                series,
                fiscal_year
            )
        ) {$charsetCollate};
    ";

    dbDelta(
        $sequencesSql
    );

    /*
     * ========================================================
     * FACTURAS
     * ========================================================
     *
     * Cada factura contiene snapshots completos del emisor
     * y del receptor.
     *
     * Por tanto, modificar posteriormente:
     *
     * - razón social,
     * - NIF/CIF,
     * - domicilio,
     * - email,
     * - configuración fiscal
     *
     * nunca cambiará una factura ya emitida.
     */

    $invoicesSql = "
        CREATE TABLE {$invoicesTable} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,

            customer_id bigint(20) unsigned NOT NULL,

            payment_id bigint(20) unsigned NOT NULL,

            document_type varchar(30) NOT NULL DEFAULT 'invoice',

            status varchar(30) NOT NULL DEFAULT 'issued',

            series varchar(30) NOT NULL,

            sequence_number bigint(20) unsigned NOT NULL,

            fiscal_year smallint(5) unsigned NOT NULL,

            full_number varchar(80) NOT NULL,

            issued_at datetime NOT NULL,

            currency char(3) NOT NULL DEFAULT 'EUR',

            prices_include_tax tinyint(1) unsigned NOT NULL DEFAULT 1,

            subtotal decimal(12,2) NOT NULL DEFAULT 0.00,

            tax_total decimal(12,2) NOT NULL DEFAULT 0.00,

            total decimal(12,2) NOT NULL DEFAULT 0.00,

            tax_type varchar(30) NOT NULL DEFAULT 'IGIC',

            tax_rate decimal(7,4) NOT NULL DEFAULT 7.0000,

            tax_exemption_code varchar(50) DEFAULT NULL,

            tax_exemption_reason text DEFAULT NULL,

            seller_fiscal_name varchar(190) NOT NULL,

            seller_tax_id varchar(50) NOT NULL,

            seller_address_line_1 varchar(190) DEFAULT NULL,

            seller_address_line_2 varchar(190) DEFAULT NULL,

            seller_postal_code varchar(20) DEFAULT NULL,

            seller_city varchar(120) DEFAULT NULL,

            seller_province varchar(120) DEFAULT NULL,

            seller_country_code char(2) NOT NULL DEFAULT 'ES',

            seller_email varchar(190) DEFAULT NULL,

            customer_type varchar(20) NOT NULL DEFAULT 'individual',

            customer_fiscal_name varchar(190) NOT NULL,

            customer_tax_id varchar(50) DEFAULT NULL,

            customer_address_line_1 varchar(190) DEFAULT NULL,

            customer_address_line_2 varchar(190) DEFAULT NULL,

            customer_postal_code varchar(20) DEFAULT NULL,

            customer_city varchar(120) DEFAULT NULL,

            customer_province varchar(120) DEFAULT NULL,

            customer_country_code char(2) NOT NULL DEFAULT 'ES',

            customer_email varchar(190) DEFAULT NULL,

            payment_provider varchar(50) DEFAULT NULL,

            payment_provider_reference varchar(190) DEFAULT NULL,

            payment_purpose varchar(50) DEFAULT NULL,

            payment_source_type varchar(50) DEFAULT NULL,

            payment_source_id bigint(20) unsigned DEFAULT NULL,

            payment_source_reference varchar(190) DEFAULT NULL,

            pdf_relative_path varchar(500) DEFAULT NULL,

            pdf_generated_at datetime DEFAULT NULL,

            design_snapshot longtext DEFAULT NULL,

            created_at datetime NOT NULL,

            updated_at datetime NOT NULL,

            PRIMARY KEY (id),

            UNIQUE KEY payment_id (
                payment_id
            ),

            UNIQUE KEY full_number (
                full_number
            ),

            UNIQUE KEY series_year_number (
                series,
                fiscal_year,
                sequence_number
            ),

            KEY customer_id (
                customer_id
            ),

            KEY issued_at (
                issued_at
            ),

            KEY status (
                status
            ),

            KEY fiscal_year (
                fiscal_year
            ),

            KEY customer_date (
                customer_id,
                issued_at
            ),

            KEY provider_reference (
                payment_provider,
                payment_provider_reference
            )
        ) {$charsetCollate};
    ";

    dbDelta(
        $invoicesSql
    );

    /*
     * ========================================================
     * LÍNEAS DE FACTURA
     * ========================================================
     *
     * Aunque el MVP genere normalmente una sola línea por
     * pago, la estructura admite varias líneas desde el
     * principio.
     */

    $itemsSql = "
        CREATE TABLE {$itemsTable} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,

            invoice_id bigint(20) unsigned NOT NULL,

            description varchar(500) NOT NULL,

            quantity decimal(12,4) NOT NULL DEFAULT 1.0000,

            unit_price decimal(12,4) NOT NULL DEFAULT 0.0000,

            line_subtotal decimal(12,2) NOT NULL DEFAULT 0.00,

            tax_type varchar(30) NOT NULL DEFAULT 'IGIC',

            tax_rate decimal(7,4) NOT NULL DEFAULT 7.0000,

            tax_base decimal(12,2) NOT NULL DEFAULT 0.00,

            tax_amount decimal(12,2) NOT NULL DEFAULT 0.00,

            line_total decimal(12,2) NOT NULL DEFAULT 0.00,

            sort_order int(10) unsigned NOT NULL DEFAULT 0,

            created_at datetime NOT NULL,

            PRIMARY KEY (id),

            KEY invoice_id (
                invoice_id
            ),

            KEY invoice_order (
                invoice_id,
                sort_order
            )
        ) {$charsetCollate};
    ";

    dbDelta(
        $itemsSql
    );

    /*
     * ========================================================
     * VERIFICACIÓN
     * ========================================================
     */

    $requiredTables = [
        $billingProfilesTable,
        $sequencesTable,
        $invoicesTable,
        $itemsTable,
    ];

    foreach (
        $requiredTables
        as $tableName
    ) {
        $exists =
            $wpdb->get_var(
                $wpdb->prepare(
                    'SHOW TABLES LIKE %s',
                    $tableName
                )
            );

        if ($exists !== $tableName) {
            throw new RuntimeException(
                sprintf(
                    'No se pudo crear o verificar la tabla %s.',
                    $tableName
                )
            );
        }
    }
};
