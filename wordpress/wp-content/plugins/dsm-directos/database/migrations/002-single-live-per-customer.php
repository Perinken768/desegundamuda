<?php

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

return static function (): void {
    global $wpdb;

    $streamsTable =
        $wpdb->prefix
        . 'dsm_live_streams';

    $itemsTable =
        $wpdb->prefix
        . 'dsm_live_items';

    $reservationsTable =
        $wpdb->prefix
        . 'dsm_live_reservations';

    /*
     * =========================================================
     * NORMALIZAMOS ESTADO ANTERIOR
     * =========================================================
     */

    $result =
        $wpdb->query(
            "
            UPDATE {$streamsTable}
            SET status = 'setup'
            WHERE status = 'draft'
            "
        );

    if ($result === false) {
        throw new RuntimeException(
            'No se pudieron normalizar los estados de DSM Directos: '
            . $wpdb->last_error
        );
    }

    /*
     * =========================================================
     * LIMPIEZA DE DUPLICADOS PREVIOS
     * =========================================================
     *
     * Durante el desarrollo inicial se permitían varios
     * directos por cliente.
     *
     * Conservamos únicamente el registro más reciente.
     */

    $duplicateRows =
        $wpdb->get_results(
            "
            SELECT
                customer_id,
                MAX(id) AS keep_id
            FROM {$streamsTable}
            GROUP BY customer_id
            HAVING COUNT(*) > 1
            ",
            ARRAY_A
        );

    if (is_array($duplicateRows)) {
        foreach ($duplicateRows as $row) {
            $customerId =
                (int) (
                    $row['customer_id']
                    ?? 0
                );

            $keepId =
                (int) (
                    $row['keep_id']
                    ?? 0
                );

            if (
                $customerId <= 0
                || $keepId <= 0
            ) {
                continue;
            }

            $obsoleteIds =
                $wpdb->get_col(
                    $wpdb->prepare(
                        "
                        SELECT id
                        FROM {$streamsTable}
                        WHERE customer_id = %d
                          AND id <> %d
                        ",
                        $customerId,
                        $keepId
                    )
                );

            foreach ($obsoleteIds as $obsoleteId) {
                $obsoleteId =
                    (int) $obsoleteId;

                if ($obsoleteId <= 0) {
                    continue;
                }

                /*
                 * Estos posibles registros pertenecen únicamente
                 * al modelo descartado de múltiples directos.
                 */

                $wpdb->delete(
                    $reservationsTable,
                    [
                        'live_id' =>
                            $obsoleteId,
                    ],
                    [
                        '%d',
                    ]
                );

                $wpdb->delete(
                    $itemsTable,
                    [
                        'live_id' =>
                            $obsoleteId,
                    ],
                    [
                        '%d',
                    ]
                );

                $deleted =
                    $wpdb->delete(
                        $streamsTable,
                        [
                            'id' =>
                                $obsoleteId,
                        ],
                        [
                            '%d',
                        ]
                    );

                if ($deleted === false) {
                    throw new RuntimeException(
                        'No se pudo limpiar un directo antiguo: '
                        . $wpdb->last_error
                    );
                }
            }
        }
    }

    /*
     * =========================================================
     * UN ÚNICO DIRECTO POR CLIENTE
     * =========================================================
     */

    $indexExists =
        $wpdb->get_var(
            $wpdb->prepare(
                "
                SELECT INDEX_NAME
                FROM information_schema.STATISTICS
                WHERE TABLE_SCHEMA = DATABASE()
                  AND TABLE_NAME = %s
                  AND INDEX_NAME = %s
                LIMIT 1
                ",
                $streamsTable,
                'customer_id_unique'
            )
        );

    if ($indexExists === null) {
        $result =
            $wpdb->query(
                "
                ALTER TABLE {$streamsTable}
                ADD UNIQUE KEY customer_id_unique (customer_id)
                "
            );

        if ($result === false) {
            throw new RuntimeException(
                'No se pudo crear la restricción de un único directo por cliente: '
                . $wpdb->last_error
            );
        }
    }
};
