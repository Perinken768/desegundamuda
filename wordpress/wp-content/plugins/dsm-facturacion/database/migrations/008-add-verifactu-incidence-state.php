<?php

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

return static function (): void {
    global $wpdb;

    $table =
        $wpdb->prefix
        . 'dsm_verifactu_submissions';

    $columnExists =
        static function (
            string $column
        ) use (
            $wpdb,
            $table
        ): bool {
            $result =
                $wpdb->get_var(
                    $wpdb->prepare(
                        "
                        SHOW COLUMNS
                        FROM {$table}
                        LIKE %s
                        ",
                        $column
                    )
                );

            return $result !== null;
        };

    $indexExists =
        static function (
            string $index
        ) use (
            $wpdb,
            $table
        ): bool {
            $result =
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
                        $table,
                        $index
                    )
                );

            return $result !== null;
        };

    if (
        !$columnExists(
            'submission_type'
        )
    ) {
        $result =
            $wpdb->query(
                "
                ALTER TABLE {$table}
                ADD COLUMN submission_type varchar(30)
                    NOT NULL
                    DEFAULT 'normal'
                AFTER environment
                "
            );

        if ($result === false) {
            throw new \RuntimeException(
                'No se pudo añadir submission_type: '
                . $wpdb->last_error
            );
        }
    }

    if (
        !$columnExists(
            'incidence_reason'
        )
    ) {
        $result =
            $wpdb->query(
                "
                ALTER TABLE {$table}
                ADD COLUMN incidence_reason varchar(500) NULL
                AFTER submission_type
                "
            );

        if ($result === false) {
            throw new \RuntimeException(
                'No se pudo añadir incidence_reason: '
                . $wpdb->last_error
            );
        }
    }

    if (
        !$indexExists(
            'submission_type'
        )
    ) {
        $result =
            $wpdb->query(
                "
                ALTER TABLE {$table}
                ADD INDEX submission_type (
                    submission_type
                )
                "
            );

        if ($result === false) {
            throw new \RuntimeException(
                'No se pudo crear el índice submission_type: '
                . $wpdb->last_error
            );
        }
    }
};
