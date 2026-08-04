<?php

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

return static function (): void {
    global $wpdb;

    $tableName =
        $wpdb->prefix
        . 'dsm_customer_profiles';

    $allowPhoneCallsExists =
        $wpdb->get_var(
            $wpdb->prepare(
                "
                SELECT COLUMN_NAME
                FROM INFORMATION_SCHEMA.COLUMNS
                WHERE TABLE_SCHEMA = DATABASE()
                  AND TABLE_NAME = %s
                  AND COLUMN_NAME = 'allow_phone_calls'
                ",
                $tableName
            )
        );

    if ($allowPhoneCallsExists === null) {
        $result =
            $wpdb->query(
                "
                ALTER TABLE {$tableName}
                ADD COLUMN allow_phone_calls
                    tinyint(1) unsigned
                    NOT NULL
                    DEFAULT 0
                    AFTER phone
                "
            );

        if ($result === false) {
            throw new RuntimeException(
                'No se pudo añadir allow_phone_calls '
                . 'a la tabla de perfiles.'
            );
        }
    }

    $allowWhatsappExists =
        $wpdb->get_var(
            $wpdb->prepare(
                "
                SELECT COLUMN_NAME
                FROM INFORMATION_SCHEMA.COLUMNS
                WHERE TABLE_SCHEMA = DATABASE()
                  AND TABLE_NAME = %s
                  AND COLUMN_NAME = 'allow_whatsapp'
                ",
                $tableName
            )
        );

    if ($allowWhatsappExists === null) {
        $result =
            $wpdb->query(
                "
                ALTER TABLE {$tableName}
                ADD COLUMN allow_whatsapp
                    tinyint(1) unsigned
                    NOT NULL
                    DEFAULT 0
                    AFTER whatsapp_phone
                "
            );

        if ($result === false) {
            throw new RuntimeException(
                'No se pudo añadir allow_whatsapp '
                . 'a la tabla de perfiles.'
            );
        }
    }
};