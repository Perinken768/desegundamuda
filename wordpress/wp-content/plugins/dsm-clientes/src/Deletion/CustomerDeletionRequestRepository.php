<?php

declare(strict_types=1);

namespace DSM\Clientes\Deletion;

use RuntimeException;

if (!defined('ABSPATH')) {
    exit;
}

final class CustomerDeletionRequestRepository
{
    public const STATUS_PENDING_CONFIRMATION =
        'pending_confirmation';

    public const STATUS_SCHEDULED =
        'scheduled';

    public const STATUS_CANCELLED =
        'cancelled';

    public const STATUS_COMPLETED =
        'completed';

    private string $tableName;

    public function __construct()
    {
        global $wpdb;

        $this->tableName =
            $wpdb->prefix
            . 'dsm_customer_deletion_requests';
    }

    public function createPending(
        int $customerId,
        string $tokenHash
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

        $this->cancelOpenRequestsForCustomer(
            $customerId
        );

        $now = current_time('mysql', true);

        $result = $wpdb->insert(
            $this->tableName,
            [
                'customer_id' => $customerId,
                'status' =>
                    self::STATUS_PENDING_CONFIRMATION,
                'confirmation_token_hash' =>
                    $tokenHash,
                'requested_at' => $now,
                'confirmed_at' => null,
                'scheduled_at' => null,
                'cancelled_at' => null,
                'completed_at' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                '%d',
                '%s',
                '%s',
                '%s',
                null,
                null,
                null,
                null,
                '%s',
                '%s',
            ]
        );

        if ($result === false) {
            throw new RuntimeException(
                'No se pudo crear la solicitud de eliminación.'
            );
        }

        return (int) $wpdb->insert_id;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function findByTokenHash(
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
                    status,
                    confirmation_token_hash,
                    requested_at,
                    confirmed_at,
                    scheduled_at,
                    cancelled_at,
                    completed_at,
                    created_at,
                    updated_at
                FROM {$this->tableName}
                WHERE confirmation_token_hash = %s
                LIMIT 1",
                $tokenHash
            ),
            ARRAY_A
        );

        return is_array($row)
            ? $row
            : null;
    }

    public function confirm(
        int $requestId,
        int $gracePeriodSeconds
    ): string {
        global $wpdb;

        if ($requestId <= 0) {
            throw new RuntimeException(
                'La solicitud de eliminación no es válida.'
            );
        }

        if ($gracePeriodSeconds <= 0) {
            throw new RuntimeException(
                'El periodo de gracia no es válido.'
            );
        }

        $now = current_time('mysql', true);

        $scheduledAt = gmdate(
            'Y-m-d H:i:s',
            time() + $gracePeriodSeconds
        );

        $result = $wpdb->update(
            $this->tableName,
            [
                'status' => self::STATUS_SCHEDULED,
                'confirmed_at' => $now,
                'scheduled_at' => $scheduledAt,
                'updated_at' => $now,
            ],
            [
                'id' => $requestId,
                'status' =>
                    self::STATUS_PENDING_CONFIRMATION,
            ],
            [
                '%s',
                '%s',
                '%s',
                '%s',
            ],
            [
                '%d',
                '%s',
            ]
        );

        if ($result !== 1) {
            throw new RuntimeException(
                'La solicitud ya no puede confirmarse.'
            );
        }

        return $scheduledAt;
    }

    public function cancel(int $requestId): void
    {
        global $wpdb;

        if ($requestId <= 0) {
            throw new RuntimeException(
                'La solicitud de eliminación no es válida.'
            );
        }

        $now = current_time('mysql', true);

        $result = $wpdb->query(
            $wpdb->prepare(
                "UPDATE {$this->tableName}
                SET
                    status = %s,
                    cancelled_at = %s,
                    updated_at = %s
                WHERE id = %d
                  AND status IN (%s, %s)",
                self::STATUS_CANCELLED,
                $now,
                $now,
                $requestId,
                self::STATUS_PENDING_CONFIRMATION,
                self::STATUS_SCHEDULED
            )
        );

        if ($result !== 1) {
            throw new RuntimeException(
                'La solicitud ya no puede cancelarse.'
            );
        }
    }

    public function deleteById(int $requestId): void
    {
        global $wpdb;

        if ($requestId <= 0) {
            return;
        }

        $result = $wpdb->delete(
            $this->tableName,
            [
                'id' => $requestId,
            ],
            [
                '%d',
            ]
        );

        if ($result === false) {
            throw new RuntimeException(
                'No se pudo eliminar la solicitud.'
            );
        }
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function findDueRequests(
        int $limit = 50
    ): array {
        global $wpdb;

        $limit = max(1, min(500, $limit));

        $rows = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT
                    id,
                    customer_id,
                    status,
                    scheduled_at
                FROM {$this->tableName}
                WHERE status = %s
                  AND scheduled_at IS NOT NULL
                  AND scheduled_at <= UTC_TIMESTAMP()
                ORDER BY scheduled_at ASC
                LIMIT %d",
                self::STATUS_SCHEDULED,
                $limit
            ),
            ARRAY_A
        );

        return is_array($rows)
            ? $rows
            : [];
    }

    public function permanentlyDeleteCustomerData(
        int $requestId,
        int $customerId
    ): void {
        global $wpdb;

        if ($requestId <= 0 || $customerId <= 0) {
            throw new RuntimeException(
                'Los datos de eliminación no son válidos.'
            );
        }

        $customersTable =
            $wpdb->prefix . 'dsm_customers';

        $profilesTable =
            $wpdb->prefix . 'dsm_customer_profiles';

        $sessionsTable =
            $wpdb->prefix . 'dsm_customer_sessions';

        $emailVerificationsTable =
            $wpdb->prefix
            . 'dsm_customer_email_verifications';

        $reactivationsTable =
            $wpdb->prefix
            . 'dsm_customer_account_reactivations';

        $passwordResetsTable =
            $wpdb->prefix
            . 'dsm_customer_password_resets';

        $now = current_time('mysql', true);

        $wpdb->query('START TRANSACTION');

        try {
            $queries = [
                $wpdb->prepare(
                    "DELETE FROM {$sessionsTable}
                    WHERE customer_id = %d",
                    $customerId
                ),
                $wpdb->prepare(
                    "DELETE FROM {$emailVerificationsTable}
                    WHERE customer_id = %d",
                    $customerId
                ),
                $wpdb->prepare(
                    "DELETE FROM {$reactivationsTable}
                    WHERE customer_id = %d",
                    $customerId
                ),
                $wpdb->prepare(
                    "DELETE FROM {$passwordResetsTable}
                    WHERE customer_id = %d",
                    $customerId
                ),
                $wpdb->prepare(
                    "DELETE FROM {$profilesTable}
                    WHERE customer_id = %d",
                    $customerId
                ),
                $wpdb->prepare(
                    "DELETE FROM {$this->tableName}
                    WHERE customer_id = %d
                      AND id <> %d",
                    $customerId,
                    $requestId
                ),
            ];

            foreach ($queries as $query) {
                if ($wpdb->query($query) === false) {
                    throw new RuntimeException(
                        'No se pudieron eliminar los datos relacionados.'
                    );
                }
            }

            $completed = $wpdb->update(
                $this->tableName,
                [
                    'status' =>
                        self::STATUS_COMPLETED,
                    'confirmation_token_hash' => null,
                    'completed_at' => $now,
                    'updated_at' => $now,
                ],
                [
                    'id' => $requestId,
                    'customer_id' => $customerId,
                    'status' =>
                        self::STATUS_SCHEDULED,
                ],
                [
                    '%s',
                    null,
                    '%s',
                    '%s',
                ],
                [
                    '%d',
                    '%d',
                    '%s',
                ]
            );

            if ($completed !== 1) {
                throw new RuntimeException(
                    'No se pudo completar la solicitud.'
                );
            }

            $deleted = $wpdb->delete(
                $customersTable,
                [
                    'id' => $customerId,
                ],
                [
                    '%d',
                ]
            );

            if ($deleted !== 1) {
                throw new RuntimeException(
                    'No se pudo eliminar la cuenta.'
                );
            }

            $wpdb->query('COMMIT');
        } catch (\Throwable $exception) {
            $wpdb->query('ROLLBACK');

            throw $exception;
        }
    }

    private function cancelOpenRequestsForCustomer(
        int $customerId
    ): void {
        global $wpdb;

        $now = current_time('mysql', true);

        $result = $wpdb->query(
            $wpdb->prepare(
                "UPDATE {$this->tableName}
                SET
                    status = %s,
                    cancelled_at = %s,
                    updated_at = %s
                WHERE customer_id = %d
                  AND status IN (%s, %s)",
                self::STATUS_CANCELLED,
                $now,
                $now,
                $customerId,
                self::STATUS_PENDING_CONFIRMATION,
                self::STATUS_SCHEDULED
            )
        );

        if ($result === false) {
            throw new RuntimeException(
                'No se pudieron cancelar las solicitudes anteriores.'
            );
        }
    }
}