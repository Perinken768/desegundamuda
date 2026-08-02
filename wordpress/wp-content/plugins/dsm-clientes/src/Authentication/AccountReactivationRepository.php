<?php

declare(strict_types=1);

namespace DSM\Clientes\Authentication;

use RuntimeException;

if (!defined('ABSPATH')) {
    exit;
}

final class AccountReactivationRepository
{
    private string $tableName;

    public function __construct()
    {
        global $wpdb;

        $this->tableName = $wpdb->prefix
            . 'dsm_customer_account_reactivations';
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

        $this->revokePendingForCustomer(
            $customerId
        );

        $createdAt = current_time('mysql', true);

        $expiresAt = gmdate(
            'Y-m-d H:i:s',
            time() + $durationSeconds
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
                'No se pudo crear la solicitud de reactivación.'
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
    public function findPendingByTokenHash(
        string $tokenHash
    ): ?array {
        global $wpdb;

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
            'customer_id' =>
                (int) $row['customer_id'],
            'expires_at' =>
                (string) $row['expires_at'],
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

        $result = $wpdb->update(
            $this->tableName,
            [
                'used_at' =>
                    current_time('mysql', true),
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
                'No se pudo consumir el token de reactivación.'
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
                'No se pudo eliminar el token de reactivación.'
            );
        }
    }

    public function revokePendingForCustomer(
        int $customerId
    ): void {
        global $wpdb;

        if ($customerId <= 0) {
            return;
        }

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
