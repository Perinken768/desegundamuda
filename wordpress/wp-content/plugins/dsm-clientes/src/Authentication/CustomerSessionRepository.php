<?php

declare(strict_types=1);

namespace DSM\Clientes\Authentication;

use RuntimeException;

if (!defined('ABSPATH')) {
    exit;
}

final class CustomerSessionRepository
{
    private string $tableName;

    public function __construct()
    {
        global $wpdb;

        $this->tableName =
            $wpdb->prefix . 'dsm_customer_sessions';
    }

    public function create(
        int $customerId,
        string $tokenHash,
        ?string $ipAddress,
        ?string $userAgent,
        int $durationSeconds
    ): CustomerSession {
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
                'La duración de la sesión no es válida.'
            );
        }

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
                'ip_address' => $ipAddress,
                'user_agent' => $userAgent,
                'created_at' => $createdAt,
                'last_activity_at' => $createdAt,
                'expires_at' => $expiresAt,
                'revoked_at' => null,
            ],
            [
                '%d',
                '%s',
                '%s',
                '%s',
                '%s',
                '%s',
                '%s',
                null,
            ]
        );

        if ($result === false) {
            throw new RuntimeException(
                'No se pudo crear la sesión del cliente.'
            );
        }

        $session = $this->findById(
            (int) $wpdb->insert_id
        );

        if ($session === null) {
            throw new RuntimeException(
                'La sesión fue creada pero no pudo recuperarse.'
            );
        }

        return $session;
    }

    public function findById(
        int $id
    ): ?CustomerSession {
        global $wpdb;

        $row = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT *
                FROM {$this->tableName}
                WHERE id = %d
                LIMIT 1",
                $id
            ),
            ARRAY_A
        );

        return $row === null
            ? null
            : $this->hydrate($row);
    }

    public function findByTokenHash(
        string $tokenHash
    ): ?CustomerSession {
        global $wpdb;

        $row = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT *
                FROM {$this->tableName}
                WHERE token_hash = %s
                LIMIT 1",
                $tokenHash
            ),
            ARRAY_A
        );

        return $row === null
            ? null
            : $this->hydrate($row);
    }

    /**
     * @return array<int, CustomerSession>
     */
    public function findByCustomerId(
        int $customerId
    ): array {
        global $wpdb;

        if ($customerId <= 0) {
            return [];
        }

        $rows = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT *
                FROM {$this->tableName}
                WHERE customer_id = %d
                ORDER BY id DESC",
                $customerId
            ),
            ARRAY_A
        );

        if (!is_array($rows)) {
            return [];
        }

        return array_map(
            fn (array $row): CustomerSession =>
                $this->hydrate($row),
            $rows
        );
    }

    public function touch(int $sessionId): void
    {
        global $wpdb;

        $result = $wpdb->update(
            $this->tableName,
            [
                'last_activity_at' =>
                    current_time('mysql', true),
            ],
            [
                'id' => $sessionId,
            ],
            [
                '%s',
            ],
            [
                '%d',
            ]
        );

        if ($result === false) {
            throw new RuntimeException(
                'No se pudo actualizar la actividad de la sesión.'
            );
        }
    }

    public function revoke(int $sessionId): void
    {
        global $wpdb;

        $result = $wpdb->update(
            $this->tableName,
            [
                'revoked_at' =>
                    current_time('mysql', true),
            ],
            [
                'id' => $sessionId,
            ],
            [
                '%s',
            ],
            [
                '%d',
            ]
        );

        if ($result === false) {
            throw new RuntimeException(
                'No se pudo revocar la sesión.'
            );
        }
    }

    public function revokeAllForCustomer(
        int $customerId
    ): int {
        global $wpdb;

        if ($customerId <= 0) {
            throw new RuntimeException(
                'El identificador del cliente no es válido.'
            );
        }

        $result = $wpdb->query(
            $wpdb->prepare(
                "UPDATE {$this->tableName}
                SET revoked_at = %s
                WHERE customer_id = %d
                  AND revoked_at IS NULL",
                current_time('mysql', true),
                $customerId
            )
        );

        if ($result === false) {
            throw new RuntimeException(
                'No se pudieron revocar las sesiones del cliente.'
            );
        }

        return (int) $result;
    }

    /**
     * @param array<string, mixed> $row
     */
    private function hydrate(
        array $row
    ): CustomerSession {
        return new CustomerSession(
            (int) $row['id'],
            (int) $row['customer_id'],
            (string) $row['token_hash'],
            $row['ip_address'] !== null
                ? (string) $row['ip_address']
                : null,
            $row['user_agent'] !== null
                ? (string) $row['user_agent']
                : null,
            (string) $row['created_at'],
            (string) $row['last_activity_at'],
            (string) $row['expires_at'],
            $row['revoked_at'] !== null
                ? (string) $row['revoked_at']
                : null
        );
    }
}