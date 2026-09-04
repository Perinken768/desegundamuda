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

    /**
     * Comprueba si existe una columna.
     */
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

    /**
     * Comprueba si existe un índice.
     */
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
            'sending_started_at'
        )
    ) {
        $result =
            $wpdb->query(
                "
                ALTER TABLE {$table}
                ADD COLUMN sending_started_at datetime NULL
                AFTER transport_status
                "
            );

        if ($result === false) {
            throw new \RuntimeException(
                'No se pudo añadir sending_started_at: '
                . $wpdb->last_error
            );
        }
    }

    if (
        !$columnExists(
            'retryable'
        )
    ) {
        $result =
            $wpdb->query(
                "
                ALTER TABLE {$table}
                ADD COLUMN retryable tinyint(1) unsigned
                    NOT NULL
                    DEFAULT 0
                AFTER sending_started_at
                "
            );

        if ($result === false) {
            throw new \RuntimeException(
                'No se pudo añadir retryable: '
                . $wpdb->last_error
            );
        }
    }

    if (
        !$columnExists(
            'retry_reason'
        )
    ) {
        $result =
            $wpdb->query(
                "
                ALTER TABLE {$table}
                ADD COLUMN retry_reason varchar(500) NULL
                AFTER retryable
                "
            );

        if ($result === false) {
            throw new \RuntimeException(
                'No se pudo añadir retry_reason: '
                . $wpdb->last_error
            );
        }
    }

    if (
        !$columnExists(
            'next_retry_at'
        )
    ) {
        $result =
            $wpdb->query(
                "
                ALTER TABLE {$table}
                ADD COLUMN next_retry_at datetime NULL
                AFTER retry_reason
                "
            );

        if ($result === false) {
            throw new \RuntimeException(
                'No se pudo añadir next_retry_at: '
                . $wpdb->last_error
            );
        }
    }

    if (
        !$columnExists(
            'aeat_presenter_tax_id'
        )
    ) {
        $result =
            $wpdb->query(
                "
                ALTER TABLE {$table}
                ADD COLUMN aeat_presenter_tax_id varchar(50) NULL
                AFTER aeat_csv
                "
            );

        if ($result === false) {
            throw new \RuntimeException(
                'No se pudo añadir aeat_presenter_tax_id: '
                . $wpdb->last_error
            );
        }
    }

    if (
        !$columnExists(
            'aeat_presentation_timestamp'
        )
    ) {
        $result =
            $wpdb->query(
                "
                ALTER TABLE {$table}
                ADD COLUMN aeat_presentation_timestamp varchar(40) NULL
                AFTER aeat_presenter_tax_id
                "
            );

        if ($result === false) {
            throw new \RuntimeException(
                'No se pudo añadir aeat_presentation_timestamp: '
                . $wpdb->last_error
            );
        }
    }

    if (
        !$columnExists(
            'aeat_wait_seconds'
        )
    ) {
        $result =
            $wpdb->query(
                "
                ALTER TABLE {$table}
                ADD COLUMN aeat_wait_seconds int(10) unsigned NULL
                AFTER aeat_presentation_timestamp
                "
            );

        if ($result === false) {
            throw new \RuntimeException(
                'No se pudo añadir aeat_wait_seconds: '
                . $wpdb->last_error
            );
        }
    }

    if (
        !$indexExists(
            'retry_queue'
        )
    ) {
        $result =
            $wpdb->query(
                "
                ALTER TABLE {$table}
                ADD INDEX retry_queue (
                    retryable,
                    next_retry_at
                )
                "
            );

        if ($result === false) {
            throw new \RuntimeException(
                'No se pudo crear el índice retry_queue: '
                . $wpdb->last_error
            );
        }
    }

    if (
        !$indexExists(
            'sending_recovery'
        )
    ) {
        $result =
            $wpdb->query(
                "
                ALTER TABLE {$table}
                ADD INDEX sending_recovery (
                    transport_status,
                    sending_started_at
                )
                "
            );

        if ($result === false) {
            throw new \RuntimeException(
                'No se pudo crear el índice sending_recovery: '
                . $wpdb->last_error
            );
        }
    }
};
