<?php

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

return static function (): void {
    global $wpdb;

    $table =
        $wpdb->prefix
        . 'dsm_offer_plan_rules';

    /*
     * El índice offer_plan nació como UNIQUE:
     *
     * offer_id + plan_id
     *
     * Ahora necesitamos conservar versiones históricas
     * de una misma regla, por lo que esa combinación
     * ya no puede ser única.
     */
    $index =
        $wpdb->get_row(
            $wpdb->prepare(
                "
                SELECT
                    INDEX_NAME,
                    NON_UNIQUE
                FROM INFORMATION_SCHEMA.STATISTICS
                WHERE TABLE_SCHEMA = DATABASE()
                  AND TABLE_NAME = %s
                  AND INDEX_NAME = %s
                LIMIT 1
                ",
                $table,
                'offer_plan'
            )
        );

    if (
        is_object($index)
        && (int) $index->NON_UNIQUE === 0
    ) {
        $result =
            $wpdb->query(
                "
                ALTER TABLE {$table}
                DROP INDEX offer_plan
                "
            );

        if ($result === false) {
            throw new RuntimeException(
                'No se pudo eliminar el índice único offer_plan: '
                . $wpdb->last_error
            );
        }
    }

    /*
     * Creamos un índice normal para mantener eficientes
     * las búsquedas por oferta y plan.
     */
    $normalIndex =
        $wpdb->get_var(
            $wpdb->prepare(
                "
                SELECT INDEX_NAME
                FROM INFORMATION_SCHEMA.STATISTICS
                WHERE TABLE_SCHEMA = DATABASE()
                  AND TABLE_NAME = %s
                  AND INDEX_NAME = %s
                LIMIT 1
                ",
                $table,
                'offer_plan_lookup'
            )
        );

    if ($normalIndex === null) {
        $result =
            $wpdb->query(
                "
                ALTER TABLE {$table}
                ADD KEY offer_plan_lookup (
                    offer_id,
                    plan_id
                )
                "
            );

        if ($result === false) {
            throw new RuntimeException(
                'No se pudo crear el índice offer_plan_lookup: '
                . $wpdb->last_error
            );
        }
    }
};
