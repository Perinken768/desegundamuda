<?php

declare(strict_types=1);

namespace DSM\Promocionar\Promotion;

use DateTimeImmutable;
use RuntimeException;

if (!defined('ABSPATH')) {
    exit;
}

final class PromotionAssignmentRepository
{
    private string $tableName;

    public function __construct()
    {
        global $wpdb;

        $this->tableName =
            $wpdb->prefix
            . 'dsm_promotion_assignments';
    }

    public function findById(
        int $assignmentId
    ): ?PromotionAssignment {
        global $wpdb;

        if ($assignmentId <= 0) {
            return null;
        }

        $row =
            $wpdb->get_row(
                $wpdb->prepare(
                    "SELECT
                        id,
                        wallet_id,
                        customer_id,
                        advertisement_id,
                        started_at,
                        stopped_at,
                        consumed_seconds,
                        status,
                        created_at,
                        updated_at
                    FROM {$this->tableName}
                    WHERE id = %d
                    LIMIT 1",
                    $assignmentId
                ),
                ARRAY_A
            );

        if (!is_array($row)) {
            return null;
        }

        return PromotionAssignment::fromArray(
            $row
        );
    }

    public function findByIdForUpdate(
        int $assignmentId
    ): ?PromotionAssignment {
        global $wpdb;

        if ($assignmentId <= 0) {
            return null;
        }

        $row =
            $wpdb->get_row(
                $wpdb->prepare(
                    "SELECT
                        id,
                        wallet_id,
                        customer_id,
                        advertisement_id,
                        started_at,
                        stopped_at,
                        consumed_seconds,
                        status,
                        created_at,
                        updated_at
                    FROM {$this->tableName}
                    WHERE id = %d
                    LIMIT 1
                    FOR UPDATE",
                    $assignmentId
                ),
                ARRAY_A
            );

        if (!is_array($row)) {
            return null;
        }

        return PromotionAssignment::fromArray(
            $row
        );
    }

    public function findActiveByWallet(
        int $walletId
    ): ?PromotionAssignment {
        global $wpdb;

        if ($walletId <= 0) {
            return null;
        }

        $row =
            $wpdb->get_row(
                $wpdb->prepare(
                    "SELECT
                        id,
                        wallet_id,
                        customer_id,
                        advertisement_id,
                        started_at,
                        stopped_at,
                        consumed_seconds,
                        status,
                        created_at,
                        updated_at
                    FROM {$this->tableName}
                    WHERE wallet_id = %d
                      AND status = %s
                    ORDER BY id DESC
                    LIMIT 1",
                    $walletId,
                    PromotionAssignmentStatus::ACTIVE
                ),
                ARRAY_A
            );

        if (!is_array($row)) {
            return null;
        }

        return PromotionAssignment::fromArray(
            $row
        );
    }

    public function findActiveByAdvertisement(
        int $advertisementId
    ): ?PromotionAssignment {
        global $wpdb;

        if ($advertisementId <= 0) {
            return null;
        }

        $row =
            $wpdb->get_row(
                $wpdb->prepare(
                    "SELECT
                        id,
                        wallet_id,
                        customer_id,
                        advertisement_id,
                        started_at,
                        stopped_at,
                        consumed_seconds,
                        status,
                        created_at,
                        updated_at
                    FROM {$this->tableName}
                    WHERE advertisement_id = %d
                      AND status = %s
                    ORDER BY id DESC
                    LIMIT 1",
                    $advertisementId,
                    PromotionAssignmentStatus::ACTIVE
                ),
                ARRAY_A
            );

        if (!is_array($row)) {
            return null;
        }

        return PromotionAssignment::fromArray(
            $row
        );
    }

    public function findActiveByWalletForUpdate(
        int $walletId
    ): ?PromotionAssignment {
        global $wpdb;

        if ($walletId <= 0) {
            return null;
        }

        $row =
            $wpdb->get_row(
                $wpdb->prepare(
                    "SELECT
                        id,
                        wallet_id,
                        customer_id,
                        advertisement_id,
                        started_at,
                        stopped_at,
                        consumed_seconds,
                        status,
                        created_at,
                        updated_at
                    FROM {$this->tableName}
                    WHERE wallet_id = %d
                    AND status = %s
                    ORDER BY id DESC
                    LIMIT 1
                    FOR UPDATE",
                    $walletId,
                    PromotionAssignmentStatus::ACTIVE
                ),
                ARRAY_A
            );

        if (!is_array($row)) {
            return null;
        }

        return PromotionAssignment::fromArray(
            $row
        );
    }

    public function findActiveByAdvertisementForUpdate(
        int $advertisementId
    ): ?PromotionAssignment {
        global $wpdb;

        if ($advertisementId <= 0) {
            return null;
        }

        $row =
            $wpdb->get_row(
                $wpdb->prepare(
                    "SELECT
                        id,
                        wallet_id,
                        customer_id,
                        advertisement_id,
                        started_at,
                        stopped_at,
                        consumed_seconds,
                        status,
                        created_at,
                        updated_at
                    FROM {$this->tableName}
                    WHERE advertisement_id = %d
                    AND status = %s
                    ORDER BY id DESC
                    LIMIT 1
                    FOR UPDATE",
                    $advertisementId,
                    PromotionAssignmentStatus::ACTIVE
                ),
                ARRAY_A
            );

        if (!is_array($row)) {
            return null;
        }

        return PromotionAssignment::fromArray(
            $row
        );
    }

    public function create(
        int $walletId,
        int $customerId,
        int $advertisementId,
        ?DateTimeImmutable $startedAt = null
    ): PromotionAssignment {
        global $wpdb;

        if ($walletId <= 0) {
            throw new RuntimeException(
                'El identificador del saldo no es válido.'
            );
        }

        if ($customerId <= 0) {
            throw new RuntimeException(
                'El identificador del cliente no es válido.'
            );
        }

        if ($advertisementId <= 0) {
            throw new RuntimeException(
                'El identificador del anuncio no es válido.'
            );
        }

        $startedAt ??=
            new DateTimeImmutable(
                current_time(
                    'mysql',
                    true
                )
            );

        $startedAtSql =
            $startedAt->format(
                'Y-m-d H:i:s'
            );

        $inserted =
            $wpdb->insert(
                $this->tableName,
                [
                    'wallet_id' =>
                        $walletId,

                    'customer_id' =>
                        $customerId,

                    'advertisement_id' =>
                        $advertisementId,

                    'started_at' =>
                        $startedAtSql,

                    'stopped_at' =>
                        null,

                    'consumed_seconds' =>
                        0,

                    'status' =>
                        PromotionAssignmentStatus::ACTIVE,

                    'created_at' =>
                        $startedAtSql,

                    'updated_at' =>
                        $startedAtSql,
                ]
            );

        if ($inserted === false) {
            throw new RuntimeException(
                sprintf(
                    'No se pudo crear la asignación de promoción: %s',
                    $wpdb->last_error
                )
            );
        }

        $assignment =
            $this->findById(
                (int) $wpdb->insert_id
            );

        if ($assignment === null) {
            throw new RuntimeException(
                'La asignación se creó, pero no pudo recuperarse.'
            );
        }

        return $assignment;
    }

    public function stop(
        int $assignmentId,
        int $consumedSeconds,
        string $status,
        ?DateTimeImmutable $stoppedAt = null
    ): PromotionAssignment {
        global $wpdb;

        if ($assignmentId <= 0) {
            throw new RuntimeException(
                'El identificador de la asignación no es válido.'
            );
        }

        if ($consumedSeconds < 0) {
            throw new RuntimeException(
                'El tiempo consumido no puede ser negativo.'
            );
        }

        if (
            !in_array(
                $status,
                [
                    PromotionAssignmentStatus::STOPPED,
                    PromotionAssignmentStatus::EXHAUSTED,
                ],
                true
            )
        ) {
            throw new RuntimeException(
                'El estado final de la asignación no es válido.'
            );
        }

        $stoppedAt ??=
            new DateTimeImmutable(
                current_time(
                    'mysql',
                    true
                )
            );

        $stoppedAtSql =
            $stoppedAt->format(
                'Y-m-d H:i:s'
            );

        $updated =
            $wpdb->update(
                $this->tableName,
                [
                    'stopped_at' =>
                        $stoppedAtSql,

                    'consumed_seconds' =>
                        $consumedSeconds,

                    'status' =>
                        $status,

                    'updated_at' =>
                        $stoppedAtSql,
                ],
                [
                    'id' =>
                        $assignmentId,
                ]
            );

        if ($updated === false) {
            throw new RuntimeException(
                sprintf(
                    'No se pudo detener la asignación de promoción: %s',
                    $wpdb->last_error
                )
            );
        }

        $assignment =
            $this->findById(
                $assignmentId
            );

        if ($assignment === null) {
            throw new RuntimeException(
                'La asignación se actualizó, pero no pudo recuperarse.'
            );
        }

        return $assignment;
    }

    /**
     * @return array<int, PromotionAssignment>
     */
    public function findActive(
        int $limit = 100
    ): array {
        global $wpdb;

        $limit =
            max(
                1,
                min(
                    500,
                    $limit
                )
            );

        $rows =
            $wpdb->get_results(
                $wpdb->prepare(
                    "SELECT
                        id,
                        wallet_id,
                        customer_id,
                        advertisement_id,
                        started_at,
                        stopped_at,
                        consumed_seconds,
                        status,
                        created_at,
                        updated_at
                    FROM {$this->tableName}
                    WHERE status = %s
                    ORDER BY
                        started_at ASC,
                        id ASC
                    LIMIT %d",
                    PromotionAssignmentStatus::ACTIVE,
                    $limit
                ),
                ARRAY_A
            );

        return $this->hydrateRows(
            $rows
        );
    }

    public function findByCustomer(
        int $customerId
    ): array {
        global $wpdb;

        if ($customerId <= 0) {
            return [];
        }

        $rows =
            $wpdb->get_results(
                $wpdb->prepare(
                    "SELECT
                        id,
                        wallet_id,
                        customer_id,
                        advertisement_id,
                        started_at,
                        stopped_at,
                        consumed_seconds,
                        status,
                        created_at,
                        updated_at
                    FROM {$this->tableName}
                    WHERE customer_id = %d
                    ORDER BY
                        created_at DESC,
                        id DESC",
                    $customerId
                ),
                ARRAY_A
            );

        return $this->hydrateRows(
            $rows
        );
    }

    /**
     * @return array<int, PromotionAssignment>
     */
    public function findActiveByCustomer(
        int $customerId
    ): array {
        global $wpdb;

        if ($customerId <= 0) {
            return [];
        }

        $rows =
            $wpdb->get_results(
                $wpdb->prepare(
                    "SELECT
                        id,
                        wallet_id,
                        customer_id,
                        advertisement_id,
                        started_at,
                        stopped_at,
                        consumed_seconds,
                        status,
                        created_at,
                        updated_at
                    FROM {$this->tableName}
                    WHERE customer_id = %d
                      AND status = %s
                    ORDER BY
                        started_at DESC,
                        id DESC",
                    $customerId,
                    PromotionAssignmentStatus::ACTIVE
                ),
                ARRAY_A
            );

        return $this->hydrateRows(
            $rows
        );
    }

    /**
     * @return array<int, PromotionAssignment>
     */
    public function findHistoryByCustomer(
        int $customerId,
        int $limit,
        int $offset = 0
    ): array {
        global $wpdb;

        if ($customerId <= 0) {
            return [];
        }

        $limit =
            max(
                1,
                min(
                    100,
                    $limit
                )
            );

        $offset =
            max(
                0,
                $offset
            );

        $rows =
            $wpdb->get_results(
                $wpdb->prepare(
                    "SELECT
                        id,
                        wallet_id,
                        customer_id,
                        advertisement_id,
                        started_at,
                        stopped_at,
                        consumed_seconds,
                        status,
                        created_at,
                        updated_at
                    FROM {$this->tableName}
                    WHERE customer_id = %d
                      AND status <> %s
                    ORDER BY
                        created_at DESC,
                        id DESC
                    LIMIT %d
                    OFFSET %d",
                    $customerId,
                    PromotionAssignmentStatus::ACTIVE,
                    $limit,
                    $offset
                ),
                ARRAY_A
            );

        return $this->hydrateRows(
            $rows
        );
    }

    public function countHistoryByCustomer(
        int $customerId
    ): int {
        global $wpdb;

        if ($customerId <= 0) {
            return 0;
        }

        return max(
            0,
            (int) $wpdb->get_var(
                $wpdb->prepare(
                    "SELECT COUNT(*)
                    FROM {$this->tableName}
                    WHERE customer_id = %d
                      AND status <> %s",
                    $customerId,
                    PromotionAssignmentStatus::ACTIVE
                )
            )
        );
    }

    /**
     * @param mixed $rows
     *
     * @return array<int, PromotionAssignment>
     */
    private function hydrateRows(
        mixed $rows
    ): array {
        if (!is_array($rows)) {
            return [];
        }

        return array_map(
            static fn (
                array $row
            ): PromotionAssignment =>
                PromotionAssignment::fromArray(
                    $row
                ),
            $rows
        );
    }
}
