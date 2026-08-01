<?php

declare(strict_types=1);

namespace DSM\Clientes\Authentication;

use RuntimeException;

if (!defined('ABSPATH')) {
    exit;
}

final class EmailVerificationRepository
{
    private string $tableName;

    public function __construct()
    {
        global $wpdb;

        $this->tableName =
            $wpdb->prefix . 'dsm_customer_email_verifications';
    }

    public function create(
        int $customerId,
        string $tokenHash,
        int $lifetimeSeconds = 86400
    ): int {
        global $wpdb;

        if ($customerId <= 0) {
            throw new RuntimeException(
                'El identificador del cliente no es válido.'
            );
        }

        if (
            preg_match('/^[a-f0-9]{64}$/', $tokenHash) !== 1
        ) {
            throw new RuntimeException(
                'El hash de verificación no es válido.'
            );
        }

        if ($lifetimeSeconds <= 0) {
            throw new RuntimeException(
                'La duración del token no es válida.'
            );
        }

        /*
         * Invalidamos tokens anteriores que sigan pendientes.
         */
        $this->revokePendingForCustomer($customerId);

        $createdAt = current_time('mysql', true);

        $expiresAt = gmdate(
            'Y-m-d H:i:s',
            time() + $lifetimeSeconds
        );

        $result = $wpdb->insert(
            $this->tableName,
            [
                'customer_id' => $customerId,
                'token_hash' => $tokenHash,
                'created_at' => $createdAt,
                'expires_at' => $expiresAt,
                'used_at' => null,
            ],
            [
                '%d',
                '%s',
                '%s',
                '%s',
                null,
            ]
        );

        if ($result === false) {
            throw new RuntimeException(
                'No se pudo crear el token de verificación.'
            );
        }

        return (int) $wpdb->insert_id;
    }

    /**
     * @return array{
     *     id: int,
     *     customer_id: int,
     *     expires_at: string
     * }|null
     */
    public function findUsableByTokenHash(
        string $tokenHash
    ): ?array {
        global $wpdb;

        $now = current_time('mysql', true);

        $row = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT
                    id,
                    customer_id,
                    expires_at
                FROM {$this->tableName}
                WHERE token_hash = %s
                  AND used_at IS NULL
                  AND expires_at > %s
                LIMIT 1",
                $tokenHash,
                $now
            ),
            ARRAY_A
        );

        if ($row === null) {
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

        $result = $wpdb->update(
            $this->tableName,
            [
                'used_at' => current_time('mysql', true),
            ],
            [
                'id' => $id,
                'used_at' => null,
            ],
            [
                '%s',
            ],
            [
                '%d',
                null,
            ]
        );

        if ($result === false) {
            throw new RuntimeException(
                'No se pudo consumir el token de verificación.'
            );
        }
    }

    public function revokePendingForCustomer(
        int $customerId
    ): void {
        global $wpdb;

        $result = $wpdb->query(
            $wpdb->prepare(
                "UPDATE {$this->tableName}
                SET used_at = %s
                WHERE customer_id = %d
                  AND used_at IS NULL",
                current_time('mysql', true),
                $customerId
            )
        );

        if ($result === false) {
            throw new RuntimeException(
                'No se pudieron invalidar los tokens anteriores.'
            );
        }
    }
}