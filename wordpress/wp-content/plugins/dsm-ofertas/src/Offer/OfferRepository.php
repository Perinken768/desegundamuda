<?php

declare(strict_types=1);

namespace DSM\Ofertas\Offer;

use RuntimeException;

if (!defined('ABSPATH')) {
    exit;
}

final class OfferRepository
{
    private string $offersTable;

    private string $rulesTable;

    private string $customersTable;

    public function __construct()
    {
        global $wpdb;

        $this->offersTable =
            $wpdb->prefix
            . 'dsm_offers';

        $this->rulesTable =
            $wpdb->prefix
            . 'dsm_offer_plan_rules';

        $this->customersTable =
            $wpdb->prefix
            . 'dsm_offer_customers';
    }

    /**
     * @return array<int, object>
     */
    public function findAll(): array
    {
        global $wpdb;

        $rows =
            $wpdb->get_results(
                "
                SELECT *
                FROM {$this->offersTable}
                ORDER BY
                    priority DESC,
                    created_at DESC,
                    id DESC
                "
            );

        return is_array($rows)
            ? $rows
            : [];
    }

    public function findById(
        int $offerId
    ): ?object {
        global $wpdb;

        if ($offerId <= 0) {
            return null;
        }

        $row =
            $wpdb->get_row(
                $wpdb->prepare(
                    "
                    SELECT *
                    FROM {$this->offersTable}
                    WHERE id = %d
                    LIMIT 1
                    ",
                    $offerId
                )
            );

        return is_object($row)
            ? $row
            : null;
    }

    /**
     * Solo devuelve las reglas actualmente configuradas.
     *
     * @return array<int, object>
     */
    public function findRules(
        int $offerId
    ): array {
        global $wpdb;

        if ($offerId <= 0) {
            return [];
        }

        $rows =
            $wpdb->get_results(
                $wpdb->prepare(
                    "
                    SELECT *
                    FROM {$this->rulesTable}
                    WHERE offer_id = %d
                      AND is_active = 1
                    ORDER BY
                        sort_order ASC,
                        id ASC
                    ",
                    $offerId
                )
            );

        return is_array($rows)
            ? $rows
            : [];
    }

    /**
     * @return array<int, object>
     */
    public function findApplicableRules(
        int $customerId,
        int $planId,
        string $appliesTo,
        string $atUtc
    ): array {
        global $wpdb;

        if (
            $customerId <= 0
            || $planId <= 0
        ) {
            return [];
        }

        $rows =
            $wpdb->get_results(
                $wpdb->prepare(
                    "
                    SELECT
                        o.id AS offer_id,
                        o.code AS offer_code,
                        o.name AS offer_name,
                        o.scope,
                        o.priority,
                        o.stackable,

                        r.id AS rule_id,
                        r.plan_id,
                        r.benefit_type,
                        r.benefit_value,
                        r.duration_months,
                        r.applies_to

                    FROM {$this->offersTable} o

                    INNER JOIN {$this->rulesTable} r
                        ON r.offer_id = o.id
                        AND r.is_active = 1

                    LEFT JOIN {$this->customersTable} oc
                        ON oc.offer_id = o.id
                        AND oc.customer_id = %d
                        AND oc.status = 'assigned'

                    WHERE
                        o.status = 'active'

                        AND r.plan_id = %d

                        AND (
                            r.applies_to = 'any_subscription'
                            OR r.applies_to = %s
                        )

                        AND (
                            o.starts_at IS NULL
                            OR o.starts_at <= %s
                        )

                        AND (
                            o.ends_at IS NULL
                            OR o.ends_at >= %s
                        )

                        AND (
                            o.scope = 'general'

                            OR (
                                o.scope = 'customers'
                                AND oc.id IS NOT NULL
                            )
                        )

                    ORDER BY
                        CASE
                            WHEN o.scope = 'customers'
                                THEN 0
                            ELSE 1
                        END ASC,

                        o.priority DESC,

                        o.id DESC
                    ",
                    $customerId,
                    $planId,
                    $appliesTo,
                    $atUtc,
                    $atUtc
                )
            );

        return is_array($rows)
            ? $rows
            : [];
    }

    /**
     * @param array<string, mixed> $data
     */
    public function create(
        array $data
    ): int {
        global $wpdb;

        $now =
            current_time(
                'mysql',
                true
            );

        $inserted =
            $wpdb->insert(
                $this->offersTable,
                [
                    'code' =>
                        $data['code'],

                    'name' =>
                        $data['name'],

                    'description' =>
                        $data['description'],

                    'scope' =>
                        $data['scope'],

                    'status' =>
                        $data['status'],

                    'starts_at' =>
                        $data['starts_at'],

                    'ends_at' =>
                        $data['ends_at'],

                    'priority' =>
                        $data['priority'],

                    'stackable' =>
                        $data['stackable'],

                    'created_at' =>
                        $now,

                    'updated_at' =>
                        $now,
                ]
            );

        if ($inserted === false) {
            throw new RuntimeException(
                'No se pudo crear la oferta: '
                . $wpdb->last_error
            );
        }

        return (int) $wpdb->insert_id;
    }

    /**
     * @param array<string, mixed> $data
     */
    public function update(
        int $offerId,
        array $data
    ): void {
        global $wpdb;

        if ($offerId <= 0) {
            throw new RuntimeException(
                'La oferta indicada no es válida.'
            );
        }

        $updated =
            $wpdb->update(
                $this->offersTable,
                [
                    'code' =>
                        $data['code'],

                    'name' =>
                        $data['name'],

                    'description' =>
                        $data['description'],

                    'scope' =>
                        $data['scope'],

                    'status' =>
                        $data['status'],

                    'starts_at' =>
                        $data['starts_at'],

                    'ends_at' =>
                        $data['ends_at'],

                    'priority' =>
                        $data['priority'],

                    'stackable' =>
                        $data['stackable'],

                    'updated_at' =>
                        current_time(
                            'mysql',
                            true
                        ),
                ],
                [
                    'id' =>
                        $offerId,
                ]
            );

        if ($updated === false) {
            throw new RuntimeException(
                'No se pudo actualizar la oferta: '
                . $wpdb->last_error
            );
        }
    }

    /**
     * Desactiva todas las reglas actuales de una oferta.
     *
     * No las elimina porque pueden estar referenciadas
     * por utilizaciones históricas.
     */
    public function deactivateRules(
        int $offerId
    ): void {
        global $wpdb;

        if ($offerId <= 0) {
            return;
        }

        $updated =
            $wpdb->update(
                $this->rulesTable,
                [
                    'is_active' =>
                        0,

                    'updated_at' =>
                        current_time(
                            'mysql',
                            true
                        ),
                ],
                [
                    'offer_id' =>
                        $offerId,

                    'is_active' =>
                        1,
                ]
            );

        if ($updated === false) {
            throw new RuntimeException(
                'No se pudieron desactivar las reglas anteriores: '
                . $wpdb->last_error
            );
        }
    }

    public function findLatestRuleByOfferAndPlan(
        int $offerId,
        int $planId
    ): ?object {
        global $wpdb;

        if (
            $offerId <= 0
            || $planId <= 0
        ) {
            return null;
        }

        $row =
            $wpdb->get_row(
                $wpdb->prepare(
                    "
                    SELECT *
                    FROM {$this->rulesTable}
                    WHERE offer_id = %d
                      AND plan_id = %d
                    ORDER BY id DESC
                    LIMIT 1
                    ",
                    $offerId,
                    $planId
                )
            );

        return is_object($row)
            ? $row
            : null;
    }

    /**
     * @param array<string, mixed> $data
     */
    public function saveRule(
        int $offerId,
        array $data
    ): int {
        global $wpdb;

        $planId =
            (int) $data['plan_id'];

        $existing =
            $this->findLatestRuleByOfferAndPlan(
                $offerId,
                $planId
            );

        $now =
            current_time(
                'mysql',
                true
            );

        /*
         * Si nunca ha sido utilizada podemos reutilizar
         * la misma fila.
         *
         * Si ya existe un redemption que referencia esa
         * regla, creamos una nueva versión para mantener
         * intacto el historial.
         */
        if ($existing !== null) {
            $used =
                $wpdb->get_var(
                    $wpdb->prepare(
                        "
                        SELECT id
                        FROM {$wpdb->prefix}dsm_offer_redemptions
                        WHERE offer_rule_id = %d
                        LIMIT 1
                        ",
                        (int) $existing->id
                    )
                );

            if ($used === null) {
                $updated =
                    $wpdb->update(
                        $this->rulesTable,
                        [
                            'benefit_type' =>
                                $data['benefit_type'],

                            'benefit_value' =>
                                $data['benefit_value'],

                            'duration_months' =>
                                $data['duration_months'],

                            'applies_to' =>
                                $data['applies_to'],

                            'is_active' =>
                                1,

                            'sort_order' =>
                                $data['sort_order'],

                            'updated_at' =>
                                $now,
                        ],
                        [
                            'id' =>
                                (int) $existing->id,
                        ]
                    );

                if ($updated === false) {
                    throw new RuntimeException(
                        'No se pudo actualizar la regla de la oferta: '
                        . $wpdb->last_error
                    );
                }

                return (int) $existing->id;
            }
        }

        $inserted =
            $wpdb->insert(
                $this->rulesTable,
                [
                    'offer_id' =>
                        $offerId,

                    'plan_id' =>
                        $planId,

                    'benefit_type' =>
                        $data['benefit_type'],

                    'benefit_value' =>
                        $data['benefit_value'],

                    'duration_months' =>
                        $data['duration_months'],

                    'applies_to' =>
                        $data['applies_to'],

                    'is_active' =>
                        1,

                    'sort_order' =>
                        $data['sort_order'],

                    'created_at' =>
                        $now,

                    'updated_at' =>
                        $now,
                ]
            );

        if ($inserted === false) {
            throw new RuntimeException(
                'No se pudo crear la regla de la oferta: '
                . $wpdb->last_error
            );
        }

        return (int) $wpdb->insert_id;
    }
}
