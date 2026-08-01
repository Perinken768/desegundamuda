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
        int $lifetimeSeconds = DAY_IN_SECONDS
    ): int {
        global $wpdb;

        if ($customerId <= 0) {
            throw new RuntimeException(
                'El identificador del cliente no es válido.'
            );
        }

        if (
            preg_match(
                '/^[a-f0-9]{64}$/',
                $tokenHash
            ) !== 1
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

        if ($id <= 0) {
            throw new RuntimeException(
                'El identificador del token no es válido.'
            );
        }

        $result = $wpdb->query(
            $wpdb->prepare(
                "UPDATE {$this->tableName}
                SET used_at = %s
                WHERE id = %d
                  AND used_at IS NULL",
                current_time('mysql', true),
                $id
            )
        );

        if ($result === false) {
            throw new RuntimeException(
                'No se pudo consumir el token de verificación.'
            );
        }
    }

    public function revokeOtherPendingForCustomer(
        int $customerId,
        int $excludedTokenId
    ): void {
        global $wpdb;

        if ($customerId <= 0 || $excludedTokenId <= 0) {
            throw new RuntimeException(
                'Los identificadores de verificación no son válidos.'
            );
        }

        $result = $wpdb->query(
            $wpdb->prepare(
                "UPDATE {$this->tableName}
                SET used_at = %s
                WHERE customer_id = %d
                  AND id <> %d
                  AND used_at IS NULL",
                current_time('mysql', true),
                $customerId,
                $excludedTokenId
            )
        );

        if ($result === false) {
            throw new RuntimeException(
                'No se pudieron invalidar los tokens anteriores.'
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
            [
                'id' => $id,
            ],
            [
                '%d',
            ]
        );

        if ($result === false) {
            throw new RuntimeException(
                'No se pudo eliminar el token de verificación.'
            );
        }
    }

    public function deleteExpired(): int
    {
        global $wpdb;

        $result = $wpdb->query(
            $wpdb->prepare(
                "DELETE FROM {$this->tableName}
                WHERE expires_at <= %s",
                current_time('mysql', true)
            )
        );

        if ($result === false) {
            throw new RuntimeException(
                'No se pudieron eliminar los tokens caducados.'
            );
        }

        return $result;
    }
}