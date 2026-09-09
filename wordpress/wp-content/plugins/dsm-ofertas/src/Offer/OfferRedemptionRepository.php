<?php

declare(strict_types=1);

namespace DSM\Ofertas\Offer;

use RuntimeException;

if (!defined('ABSPATH')) {
    exit;
}

final class OfferRedemptionRepository
{
    private string $table;

    public function __construct()
    {
        global $wpdb;

        $this->table =
            $wpdb->prefix
            . 'dsm_offer_redemptions';
    }

    public function existsForOfferCustomerPlan(
        int $offerId,
        int $customerId,
        int $planId
    ): bool {
        global $wpdb;

        if (
            $offerId <= 0
            || $customerId <= 0
            || $planId <= 0
        ) {
            return false;
        }

        $id =
            $wpdb->get_var(
                $wpdb->prepare(
                    "
                    SELECT id
                    FROM {$this->table}
                    WHERE offer_id = %d
                      AND customer_id = %d
                      AND plan_id = %d
                    LIMIT 1
                    ",
                    $offerId,
                    $customerId,
                    $planId
                )
            );

        return $id !== null;
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
                $this->table,
                [
                    'offer_id' =>
                        $data['offer_id'],

                    'offer_rule_id' =>
                        $data['offer_rule_id'],

                    'customer_id' =>
                        $data['customer_id'],

                    'plan_id' =>
                        $data['plan_id'],

                    'subscription_id' =>
                        $data['subscription_id'],

                    'status' =>
                        $data['status'],

                    'benefit_type' =>
                        $data['benefit_type'],

                    'benefit_value' =>
                        $data['benefit_value'],

                    'duration_months' =>
                        $data['duration_months'],

                    'original_price' =>
                        $data['original_price'],

                    'discount_amount' =>
                        $data['discount_amount'],

                    'final_price' =>
                        $data['final_price'],

                    'currency' =>
                        $data['currency'],

                    'benefit_starts_at' =>
                        $data['benefit_starts_at'],

                    'benefit_ends_at' =>
                        $data['benefit_ends_at'],

                    'accepted_at' =>
                        $data['accepted_at'],

                    'completed_at' =>
                        null,

                    'cancelled_at' =>
                        null,

                    'created_at' =>
                        $now,

                    'updated_at' =>
                        $now,
                ]
            );

        if ($inserted === false) {
            throw new RuntimeException(
                'No se pudo registrar el uso de la oferta: '
                . $wpdb->last_error
            );
        }

        return (int) $wpdb->insert_id;
    }

    /**
     * @return array<int, object>
     */
    public function findRecent(
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

        $customersTable =
            $wpdb->prefix
            . 'dsm_customers';

        $plansTable =
            $wpdb->prefix
            . 'dsm_subscription_plans';

        $offersTable =
            $wpdb->prefix
            . 'dsm_offers';

        $subscriptionsTable =
            $wpdb->prefix
            . 'dsm_subscriptions';

        $rows =
            $wpdb->get_results(
                $wpdb->prepare(
                    "
                    SELECT
                        r.id,
                        r.offer_id,
                        r.offer_rule_id,
                        r.customer_id,
                        r.plan_id,
                        r.subscription_id,
                        r.status,
                        r.benefit_type,
                        r.benefit_value,
                        r.duration_months,
                        r.original_price,
                        r.discount_amount,
                        r.final_price,
                        r.currency,
                        r.benefit_starts_at,
                        r.benefit_ends_at,
                        r.accepted_at,
                        r.created_at,

                        o.name AS offer_name,
                        o.code AS offer_code,

                        c.email AS customer_email,

                        p.name AS plan_name,
                        p.code AS plan_code,

                        s.status AS subscription_status

                    FROM {$this->table} r

                    LEFT JOIN {$offersTable} o
                        ON o.id = r.offer_id

                    LEFT JOIN {$customersTable} c
                        ON c.id = r.customer_id

                    LEFT JOIN {$plansTable} p
                        ON p.id = r.plan_id

                    LEFT JOIN {$subscriptionsTable} s
                        ON s.id = r.subscription_id

                    ORDER BY
                        r.accepted_at DESC,
                        r.id DESC

                    LIMIT %d
                    ",
                    $limit
                )
            );

        return is_array($rows)
            ? $rows
            : [];
    }

}
