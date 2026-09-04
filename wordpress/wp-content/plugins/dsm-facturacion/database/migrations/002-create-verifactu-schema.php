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

    $chainsTable =
        $wpdb->prefix
        . 'dsm_verifactu_chains';

    $recordsTable =
        $wpdb->prefix
        . 'dsm_verifactu_records';

    $submissionsTable =
        $wpdb->prefix
        . 'dsm_verifactu_submissions';

    /*
     * ========================================================
     * CADENAS VERI*FACTU
     * ========================================================
     *
     * Esta tabla actúa como punto de serialización.
     *
     * Antes de generar un nuevo registro fiscal:
     *
     * START TRANSACTION
     *
     * SELECT ...
     * FOR UPDATE
     *
     * De esta forma dos facturas creadas simultáneamente
     * nunca podrán tomar el mismo registro anterior.
     *
     * Separamos cadenas por:
     *
     * - entorno,
     * - NIF del obligado tributario,
     * - sistema,
     * - instalación.
     *
     * Así los registros generados durante las pruebas nunca
     * contaminan la cadena real de producción.
     */

    $chainsSql = "
        CREATE TABLE {$chainsTable} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,

            environment varchar(20) NOT NULL,

            issuer_tax_id varchar(50) NOT NULL,

            system_id varchar(30) NOT NULL,

            installation_id varchar(80) NOT NULL,

            last_sequence bigint(20) unsigned NOT NULL DEFAULT 0,

            last_record_id bigint(20) unsigned DEFAULT NULL,

            last_hash char(64) DEFAULT NULL,

            last_generated_at datetime DEFAULT NULL,

            last_generated_at_iso varchar(40) DEFAULT NULL,

            created_at datetime NOT NULL,

            updated_at datetime NOT NULL,

            PRIMARY KEY (id),

            UNIQUE KEY chain_identity (
                environment,
                issuer_tax_id,
                system_id,
                installation_id
            ),

            KEY last_record_id (
                last_record_id
            )
        ) {$charsetCollate};
    ";

    dbDelta(
        $chainsSql
    );

    /*
     * ========================================================
     * REGISTROS FISCALES VERI*FACTU
     * ========================================================
     *
     * Un registro representa un hecho fiscal generado por
     * nuestro SIF.
     *
     * No representa un intento de comunicación con AEAT.
     *
     * Tipos previstos:
     *
     * - alta
     * - anulacion
     *
     * También dejamos preparado el registro para las
     * situaciones oficiales de subsanación y rechazo previo.
     *
     * Una vez generados:
     *
     * - identificación,
     * - snapshots,
     * - encadenamiento,
     * - entrada de huella,
     * - huella,
     * - XML
     *
     * deberán tratarse como información inmutable.
     */

    $recordsSql = "
        CREATE TABLE {$recordsTable} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,

            record_uuid char(36) NOT NULL,

            chain_id bigint(20) unsigned NOT NULL,

            chain_sequence bigint(20) unsigned NOT NULL,

            invoice_id bigint(20) unsigned NOT NULL,

            record_type varchar(20) NOT NULL,

            generation_type varchar(30) NOT NULL DEFAULT 'normal',

            fiscal_status varchar(30) NOT NULL DEFAULT 'generated',

            environment varchar(20) NOT NULL,

            issuer_fiscal_name varchar(190) NOT NULL,

            issuer_tax_id varchar(50) NOT NULL,

            invoice_number varchar(80) NOT NULL,

            invoice_date date NOT NULL,

            invoice_type varchar(10) DEFAULT NULL,

            description varchar(500) DEFAULT NULL,

            currency char(3) NOT NULL DEFAULT 'EUR',

            tax_type varchar(30) DEFAULT NULL,

            tax_rate decimal(7,4) DEFAULT NULL,

            tax_base decimal(12,2) NOT NULL DEFAULT 0.00,

            tax_amount decimal(12,2) NOT NULL DEFAULT 0.00,

            total_amount decimal(12,2) NOT NULL DEFAULT 0.00,

            tax_breakdown_snapshot longtext DEFAULT NULL,

            customer_snapshot longtext DEFAULT NULL,

            invoice_snapshot longtext DEFAULT NULL,

            system_name varchar(190) NOT NULL,

            system_id varchar(30) NOT NULL,

            system_version varchar(50) NOT NULL,

            installation_id varchar(80) NOT NULL,

            system_snapshot longtext DEFAULT NULL,

            previous_record_id bigint(20) unsigned DEFAULT NULL,

            previous_issuer_tax_id varchar(50) DEFAULT NULL,

            previous_invoice_number varchar(80) DEFAULT NULL,

            previous_invoice_date date DEFAULT NULL,

            previous_hash char(64) DEFAULT NULL,

            hash_algorithm varchar(10) NOT NULL DEFAULT '01',

            hash_input longtext DEFAULT NULL,

            hash_value char(64) DEFAULT NULL,

            generated_at datetime NOT NULL,

            generated_at_iso varchar(40) NOT NULL,

            payload_version varchar(20) DEFAULT NULL,

            payload_xml longtext DEFAULT NULL,

            aeat_record_status varchar(40) DEFAULT NULL,

            aeat_error_code varchar(50) DEFAULT NULL,

            aeat_error_message text DEFAULT NULL,

            accepted_at datetime DEFAULT NULL,

            created_at datetime NOT NULL,

            updated_at datetime NOT NULL,

            PRIMARY KEY (id),

            UNIQUE KEY record_uuid (
                record_uuid
            ),

            UNIQUE KEY chain_sequence (
                chain_id,
                chain_sequence
            ),

            KEY invoice_id (
                invoice_id
            ),

            KEY record_type (
                record_type
            ),

            KEY fiscal_status (
                fiscal_status
            ),

            KEY environment (
                environment
            ),

            KEY issuer_tax_id (
                issuer_tax_id
            ),

            KEY invoice_number (
                invoice_number
            ),

            KEY generated_at (
                generated_at
            ),

            KEY previous_record_id (
                previous_record_id
            ),

            KEY hash_value (
                hash_value
            ),

            KEY aeat_record_status (
                aeat_record_status
            )
        ) {$charsetCollate};
    ";

    dbDelta(
        $recordsSql
    );

    /*
     * ========================================================
     * REMISIONES A AEAT
     * ========================================================
     *
     * Cada fila representa UN intento de comunicación.
     *
     * Ejemplo:
     *
     * Registro fiscal #18
     *
     * intento 1 -> timeout
     * intento 2 -> HTTP 500
     * intento 3 -> Correcto
     *
     * Sigue existiendo un único registro fiscal #18.
     *
     * Conservamos petición y respuesta para trazabilidad.
     */

    $submissionsSql = "
        CREATE TABLE {$submissionsTable} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,

            submission_uuid char(36) NOT NULL,

            record_id bigint(20) unsigned NOT NULL,

            environment varchar(20) NOT NULL,

            attempt_number int(10) unsigned NOT NULL DEFAULT 1,

            endpoint varchar(500) NOT NULL,

            request_xml longtext DEFAULT NULL,

            response_xml longtext DEFAULT NULL,

            http_status smallint(5) unsigned DEFAULT NULL,

            transport_status varchar(30) NOT NULL DEFAULT 'pending',

            aeat_submission_status varchar(50) DEFAULT NULL,

            aeat_record_status varchar(50) DEFAULT NULL,

            aeat_error_code varchar(50) DEFAULT NULL,

            aeat_error_message text DEFAULT NULL,

            aeat_duplicate_status varchar(50) DEFAULT NULL,

            aeat_csv varchar(190) DEFAULT NULL,

            sent_at datetime DEFAULT NULL,

            responded_at datetime DEFAULT NULL,

            duration_ms bigint(20) unsigned DEFAULT NULL,

            created_at datetime NOT NULL,

            updated_at datetime NOT NULL,

            PRIMARY KEY (id),

            UNIQUE KEY submission_uuid (
                submission_uuid
            ),

            UNIQUE KEY record_attempt (
                record_id,
                attempt_number
            ),

            KEY record_id (
                record_id
            ),

            KEY environment (
                environment
            ),

            KEY transport_status (
                transport_status
            ),

            KEY aeat_submission_status (
                aeat_submission_status
            ),

            KEY aeat_record_status (
                aeat_record_status
            ),

            KEY created_at (
                created_at
            )
        ) {$charsetCollate};
    ";

    dbDelta(
        $submissionsSql
    );

    /*
     * ========================================================
     * VERIFICACIÓN
     * ========================================================
     */

    $requiredTables = [
        $chainsTable,
        $recordsTable,
        $submissionsTable,
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
            throw new \RuntimeException(
                sprintf(
                    'No se pudo crear o verificar la tabla %s.',
                    $tableName
                )
            );
        }
    }
};
