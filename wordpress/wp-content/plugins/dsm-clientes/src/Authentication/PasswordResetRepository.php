<?php

declare(strict_types=1);

namespace DSM\Clientes\Authentication;

use RuntimeException;

if (!defined('ABSPATH')) {
    exit;
}

final class PasswordResetRepository
{
    private string $tableName;

    public function __construct()
    {
        global $wpdb;

        $this->tableName =
            $wpdb->prefix . 'dsm_customer_password_resets';
    }

    public function create(
        int $customerId,
        string $tokenHash,
        int $durationSeconds
    ): int {
        global $wpdb;

        if ($customerId <= 0) {
            throw new RuntimeException(
                'El identificador del cliente no es válido.'
            );
        }

        if (strlen($tokenHash) !== 64) {
            throw new RuntimeException(
                'El hash del token no es válido.'
            );
        }

        if ($durationSeconds <= 0) {
            throw new RuntimeException(
                'La duración del token no es válida.'
            );
        }

        $this->invalidatePendingForCustomer($customerId);

        $now = current_time('mysql', true);

        $expiresAt = gmdate(
            'Y-m-d H:i:s',
            time() + $durationSeconds
        );

        $result = $wpdb->insert(
            $this->tableName,
            [
                'customer_id' => $customerId,
                'token_hash' => $tokenHash,
                'requested_at' => $now,
                'expires_at' => $expiresAt,
                'used_at' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                '%d',
                '%s',
                '%s',
                '%s',
                null,
                '%s',
                '%s',
            ]
        );

        if ($result === false) {
            throw new RuntimeException(
                'No se pudo crear la solicitud de recuperación.'
            );
        }

        return (int) $wpdb->insert_id;
    }

    public function findPendingByTokenHash(
        string $tokenHash
    ): ?array {
        global $wpdb;

        if (strlen($tokenHash) !== 64) {
            return null;
        }

        $row = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT
                    id,
                    customer_id,
                    expires_at
                FROM {$this->tableName}
                WHERE token_hash = %s
                  AND used_at IS NULL
                  AND expires_at > UTC_TIMESTAMP()
                LIMIT 1",
                $tokenHash
            ),
            ARRAY_A
        );

        if (!is_array($row)) {
            return null;
        }

        return [
            'id' => (int) $row['id'],
            'customer_id' => (int) $row['customer_id'],
            'expires_at' => (string) $row['expires_at'],
        ];
    }

    public function markAsUsed(int $id): void
    {
        global $wpdb;

        if ($id <= 0) {
            throw new RuntimeException(
                'El identificador de la solicitud no es válido.'
            );
        }

        $now = current_time('mysql', true);

        $result = $wpdb->query(
            $wpdb->prepare(
                "UPDATE {$this->tableName}
                SET
                    used_at = %s,
                    updated_at = %s
                WHERE id = %d
                  AND used_at IS NULL",
                $now,
                $now,
                $id
            )
        );

        if ($result !== 1) {
            throw new RuntimeException(
                'La solicitud ya no puede utilizarse.'
            );
        }
    }

    public function deleteById(int $id): void
    {
        global $wpdb;

        if ($id <= 0) {
            return;
        }

        $result = $wpdb->delete(
            $this->tableName,
            ['id' => $id],
            ['%d']
        );

        if ($result === false) {
            throw new RuntimeException(
                'No se pudo eliminar la solicitud.'
            );
        }
    }

    public function invalidatePendingForCustomer(
        int $customerId
    ): void {
        global $wpdb;

        if ($customerId <= 0) {
            return;
        }

        $now = current_time('mysql', true);

        $result = $wpdb->query(
            $wpdb->prepare(
                "UPDATE {$this->tableName}
                SET
                    used_at = %s,
                    updated_at = %s
                WHERE customer_id = %d
                  AND used_at IS NULL",
                $now,
                $now,
                $customerId
            )
        );

        if ($result === false) {
            throw new RuntimeException(
                'No se pudieron invalidar las solicitudes anteriores.'
            );
        }
    }
}