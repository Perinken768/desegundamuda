<?php

declare(strict_types=1);

namespace DSM\Promocionar\Promotion;

use RuntimeException;

if (!defined('ABSPATH')) {
    exit;
}

final class PromotionPlanRepository
{
    private string $tableName;

    public function __construct()
    {
        global $wpdb;

        $this->tableName =
            $wpdb->prefix
            . 'dsm_promotion_plans';
    }

    public function findById(
        int $planId
    ): ?PromotionPlan {
        global $wpdb;

        if ($planId <= 0) {
            return null;
        }

        $row =
            $wpdb->get_row(
                $wpdb->prepare(
                    "SELECT
                        id,
                        code,
                        name,
                        duration_seconds,
                        price,
                        currency,
                        is_active,
                        sort_order,
                        created_at,
                        updated_at
                    FROM {$this->tableName}
                    WHERE id = %d
                    LIMIT 1",
                    $planId
                ),
                ARRAY_A
            );

        if (!is_array($row)) {
            return null;
        }

        return PromotionPlan::fromArray(
            $row
        );
    }

    public function findByCode(
        string $code
    ): ?PromotionPlan {
        global $wpdb;

        $code =
            sanitize_key(
                $code
            );

        if ($code === '') {
            return null;
        }

        $row =
            $wpdb->get_row(
                $wpdb->prepare(
                    "SELECT
                        id,
                        code,
                        name,
                        duration_seconds,
                        price,
                        currency,
                        is_active,
                        sort_order,
                        created_at,
                        updated_at
                    FROM {$this->tableName}
                    WHERE code = %s
                    LIMIT 1",
                    $code
                ),
                ARRAY_A
            );

        if (!is_array($row)) {
            return null;
        }

        return PromotionPlan::fromArray(
            $row
        );
    }

    /**
     * @return array<int, PromotionPlan>
     */
    public function findAll(): array
    {
        global $wpdb;

        $rows =
            $wpdb->get_results(
                "SELECT
                    id,
                    code,
                    name,
                    duration_seconds,
                    price,
                    currency,
                    is_active,
                    sort_order,
                    created_at,
                    updated_at
                FROM {$this->tableName}
                ORDER BY
                    sort_order ASC,
                    id ASC",
                ARRAY_A
            );

        return $this->hydrateRows(
            $rows
        );
    }

    /**
     * @return array<int, PromotionPlan>
     */
    public function findActive(): array
    {
        global $wpdb;

        $rows =
            $wpdb->get_results(
                "SELECT
                    id,
                    code,
                    name,
                    duration_seconds,
                    price,
                    currency,
                    is_active,
                    sort_order,
                    created_at,
                    updated_at
                FROM {$this->tableName}
                WHERE is_active = 1
                ORDER BY
                    sort_order ASC,
                    id ASC",
                ARRAY_A
            );

        return $this->hydrateRows(
            $rows
        );
    }

    public function update(
        int $planId,
        array $data
    ): PromotionPlan {
        global $wpdb;

        $plan =
            $this->findById(
                $planId
            );

        if ($plan === null) {
            throw new RuntimeException(
                'No se encontró el plan de promoción.'
            );
        }

        $name =
            array_key_exists(
                'name',
                $data
            )
                ? trim(
                    sanitize_text_field(
                        (string) $data['name']
                    )
                )
                : $plan->getName();

        $durationSeconds =
            array_key_exists(
                'duration_seconds',
                $data
            )
                ? max(
                    0,
                    (int) $data['duration_seconds']
                )
                : $plan->getDurationSeconds();

        $price =
            array_key_exists(
                'price',
                $data
            )
                ? (float) $data['price']
                : $plan->getPrice();

        $currency =
            array_key_exists(
                'currency',
                $data
            )
                ? strtoupper(
                    trim(
                        (string) $data['currency']
                    )
                )
                : $plan->getCurrency();

        $isActive =
            array_key_exists(
                'is_active',
                $data
            )
                ? (
                    !empty(
                        $data['is_active']
                    )
                )
                : $plan->isActive();

        $sortOrder =
            array_key_exists(
                'sort_order',
                $data
            )
                ? max(
                    0,
                    (int) $data['sort_order']
                )
                : $plan->getSortOrder();

        if ($name === '') {
            throw new RuntimeException(
                'El nombre del plan es obligatorio.'
            );
        }

        if ($durationSeconds <= 0) {
            throw new RuntimeException(
                'La duración del plan debe ser mayor que cero.'
            );
        }

        if ($price < 0) {
            throw new RuntimeException(
                'El precio del plan no puede ser negativo.'
            );
        }

        if (strlen($currency) !== 3) {
            throw new RuntimeException(
                'La moneda del plan no es válida.'
            );
        }

        $updated =
            $wpdb->update(
                $this->tableName,
                [
                    'name' =>
                        $name,

                    'duration_seconds' =>
                        $durationSeconds,

                    'price' =>
                        number_format(
                            $price,
                            2,
                            '.',
                            ''
                        ),

                    'currency' =>
                        $currency,

                    'is_active' =>
                        $isActive
                            ? 1
                            : 0,

                    'sort_order' =>
                        $sortOrder,

                    'updated_at' =>
                        current_time(
                            'mysql',
                            true
                        ),
                ],
                [
                    'id' =>
                        $planId,
                ]
            );

        if ($updated === false) {
            throw new RuntimeException(
                sprintf(
                    'No se pudo actualizar el plan de promoción: %s',
                    $wpdb->last_error
                )
            );
        }

        $updatedPlan =
            $this->findById(
                $planId
            );

        if ($updatedPlan === null) {
            throw new RuntimeException(
                'El plan se actualizó, pero no pudo recuperarse.'
            );
        }

        return $updatedPlan;
    }

    /**
     * @param mixed $rows
     *
     * @return array<int, PromotionPlan>
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
            ): PromotionPlan =>
                PromotionPlan::fromArray(
                    $row
                ),
            $rows
        );
    }
}
