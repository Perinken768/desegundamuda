<?php

declare(strict_types=1);

namespace DSM\Suscripciones\Subscription;

if (!defined('ABSPATH')) {
    exit;
}

final class SubscriptionPlanRepository
{
    private string $plansTable;

    private string $featuresTable;

    public function __construct()
    {
        global $wpdb;

        $this->plansTable =
            $wpdb->prefix
            . 'dsm_subscription_plans';

        $this->featuresTable =
            $wpdb->prefix
            . 'dsm_subscription_plan_features';
    }

    public function findById(
        int $planId
    ): ?SubscriptionPlan {
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
                        description,
                        price,
                        currency,
                        billing_interval,
                        billing_interval_count,
                        is_free,
                        is_active,
                        sort_order,
                        created_at,
                        updated_at
                    FROM {$this->plansTable}
                    WHERE id = %d
                    LIMIT 1",
                    $planId
                ),
                ARRAY_A
            );

        if (!is_array($row)) {
            return null;
        }

        return SubscriptionPlan::fromArray(
            $row,
            $this->findFeatures(
                $planId
            )
        );
    }

    public function findByCode(
        string $code
    ): ?SubscriptionPlan {
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
                        description,
                        price,
                        currency,
                        billing_interval,
                        billing_interval_count,
                        is_free,
                        is_active,
                        sort_order,
                        created_at,
                        updated_at
                    FROM {$this->plansTable}
                    WHERE code = %s
                    LIMIT 1",
                    $code
                ),
                ARRAY_A
            );

        if (!is_array($row)) {
            return null;
        }

        $planId =
            (int) $row['id'];

        return SubscriptionPlan::fromArray(
            $row,
            $this->findFeatures(
                $planId
            )
        );
    }

    /**
     * @return array<int, SubscriptionPlan>
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
                    description,
                    price,
                    currency,
                    billing_interval,
                    billing_interval_count,
                    is_free,
                    is_active,
                    sort_order,
                    created_at,
                    updated_at
                FROM {$this->plansTable}
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
     * @return array<int, SubscriptionPlan>
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
                    description,
                    price,
                    currency,
                    billing_interval,
                    billing_interval_count,
                    is_free,
                    is_active,
                    sort_order,
                    created_at,
                    updated_at
                FROM {$this->plansTable}
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

    /**
     * @return array<string, string>
     */
    public function findFeatures(
        int $planId
    ): array {
        global $wpdb;

        if ($planId <= 0) {
            return [];
        }

        $rows =
            $wpdb->get_results(
                $wpdb->prepare(
                    "SELECT
                        feature_key,
                        feature_value
                    FROM {$this->featuresTable}
                    WHERE plan_id = %d
                    ORDER BY id ASC",
                    $planId
                ),
                ARRAY_A
            );

        if (!is_array($rows)) {
            return [];
        }

        $features = [];

        foreach ($rows as $row) {
            $featureKey =
                (string) (
                    $row['feature_key']
                    ?? ''
                );

            if ($featureKey === '') {
                continue;
            }

            $features[$featureKey] =
                (string) (
                    $row['feature_value']
                    ?? ''
                );
        }

        return $features;
    }

    public function updatePresentation(
        int $planId,
        string $name,
        ?string $description
    ): void {
        global $wpdb;

        if ($planId <= 0) {
            throw new \RuntimeException(
                'El identificador del plan no es válido.'
            );
        }

        $plan =
            $this->findById(
                $planId
            );

        if ($plan === null) {
            throw new \RuntimeException(
                'No se encontró el plan.'
            );
        }

        $name =
            trim(
                sanitize_text_field(
                    $name
                )
            );

        if ($name === '') {
            throw new \RuntimeException(
                'El nombre del plan es obligatorio.'
            );
        }

        $description =
            $description !== null
                ? trim(
                    sanitize_textarea_field(
                        $description
                    )
                )
                : null;

        if ($description === '') {
            $description = null;
        }

        $updated =
            $wpdb->update(
                $this->plansTable,
                [
                    'name' =>
                        $name,

                    'description' =>
                        $description,

                    'updated_at' =>
                        current_time(
                            'mysql',
                            true
                        ),
                ],
                [
                    'id' =>
                        $planId,
                ],
                [
                    '%s',
                    '%s',
                    '%s',
                ],
                [
                    '%d',
                ]
            );

        if ($updated === false) {
            throw new \RuntimeException(
                sprintf(
                    'No se pudo actualizar el plan: %s',
                    $wpdb->last_error
                )
            );
        }
    }

    /**
     * @param mixed $rows
     *
     * @return array<int, SubscriptionPlan>
     */
    private function hydrateRows(
        mixed $rows
    ): array {
        if (!is_array($rows)) {
            return [];
        }

        $plans = [];

        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }

            $planId =
                (int) (
                    $row['id']
                    ?? 0
                );

            if ($planId <= 0) {
                continue;
            }

            $plans[] =
                SubscriptionPlan::fromArray(
                    $row,
                    $this->findFeatures(
                        $planId
                    )
                );
        }

        return $plans;
    }
}