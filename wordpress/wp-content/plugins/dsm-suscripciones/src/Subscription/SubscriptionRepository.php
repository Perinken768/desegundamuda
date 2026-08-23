<?php

declare(strict_types=1);

namespace DSM\Suscripciones\Subscription;

use RuntimeException;

if (!defined('ABSPATH')) {
    exit;
}

final class SubscriptionRepository
{
    private string $tableName;

    public function __construct()
    {
        global $wpdb;

        $this->tableName =
            $wpdb->prefix
            . 'dsm_subscriptions';
    }

    public function findById(
        int $subscriptionId
    ): ?Subscription {
        global $wpdb;

        if ($subscriptionId <= 0) {
            return null;
        }

        $row =
            $wpdb->get_row(
                $wpdb->prepare(
                    "SELECT *
                    FROM {$this->tableName}
                    WHERE id = %d
                    LIMIT 1",
                    $subscriptionId
                ),
                ARRAY_A
            );

        return is_array($row)
            ? Subscription::fromArray(
                $row
            )
            : null;
    }

    public function findByPaymentId(
        int $paymentId
    ): ?Subscription {
        global $wpdb;

        if ($paymentId <= 0) {
            return null;
        }

        $row =
            $wpdb->get_row(
                $wpdb->prepare(
                    "SELECT *
                    FROM {$this->tableName}
                    WHERE payment_id = %d
                    LIMIT 1",
                    $paymentId
                ),
                ARRAY_A
            );

        return is_array($row)
            ? Subscription::fromArray(
                $row
            )
            : null;
    }

    /**
     * @return array<int, Subscription>
     */
    public function findAll(
        int $limit = 200
    ): array {
        global $wpdb;

        $limit =
            max(
                1,
                min(
                    1000,
                    $limit
                )
            );

        $rows =
            $wpdb->get_results(
                $wpdb->prepare(
                    "SELECT *
                    FROM {$this->tableName}
                    ORDER BY
                        created_at DESC,
                        id DESC
                    LIMIT %d",
                    $limit
                ),
                ARRAY_A
            );

        return $this->hydrateRows(
            $rows
        );
    }

    /**
     * @return array<int, Subscription>
     */
    public function findByCustomerId(
        int $customerId
    ): array {
        global $wpdb;

        if ($customerId <= 0) {
            return [];
        }

        $rows =
            $wpdb->get_results(
                $wpdb->prepare(
                    "SELECT *
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
     * @return array<int, Subscription>
     */
    public function findActiveByCustomerId(
        int $customerId
    ): array {
        global $wpdb;

        if ($customerId <= 0) {
            return [];
        }

        $now =
            current_time(
                'mysql',
                true
            );

        $rows =
            $wpdb->get_results(
                $wpdb->prepare(
                    "SELECT *
                    FROM {$this->tableName}
                    WHERE customer_id = %d
                    AND status = %s
                    AND starts_at <= %s
                    AND (
                        ends_at IS NULL
                        OR ends_at > %s
                    )
                    ORDER BY
                        created_at DESC,
                        id DESC",
                    $customerId,
                    SubscriptionStatus::ACTIVE,
                    $now,
                    $now
                ),
                ARRAY_A
            );

        return $this->hydrateRows(
            $rows
        );
    }

    public function findActiveByCustomerAndPlan(
        int $customerId,
        int $planId
    ): ?Subscription {
        global $wpdb;

        if (
            $customerId <= 0
            || $planId <= 0
        ) {
            return null;
        }

        $now =
            current_time(
                'mysql',
                true
            );

        $row =
            $wpdb->get_row(
                $wpdb->prepare(
                    "SELECT *
                    FROM {$this->tableName}
                    WHERE customer_id = %d
                    AND plan_id = %d
                    AND status = %s
                    AND starts_at <= %s
                    AND (
                        ends_at IS NULL
                        OR ends_at > %s
                    )
                    ORDER BY
                        created_at DESC,
                        id DESC
                    LIMIT 1",
                    $customerId,
                    $planId,
                    SubscriptionStatus::ACTIVE,
                    $now,
                    $now
                ),
                ARRAY_A
            );

        return is_array($row)
            ? Subscription::fromArray(
                $row
            )
            : null;
    }

    public function create(
        int $customerId,
        int $planId,
        string $startsAt,
        ?string $endsAt,
        string $status = SubscriptionStatus::ACTIVE,
        ?int $paymentId = null,
        ?float $pricePaid = null,
        ?string $currency = null,
        ?string $sourceType = null,
        ?string $sourceReference = null,
        bool $autoRenew = false
    ): Subscription {
        global $wpdb;

        if ($customerId <= 0) {
            throw new RuntimeException(
                'El identificador del cliente no es válido.'
            );
        }

        if ($planId <= 0) {
            throw new RuntimeException(
                'El identificador del plan no es válido.'
            );
        }

        if (
            !SubscriptionStatus::isValid(
                $status
            )
        ) {
            throw new RuntimeException(
                'El estado de la suscripción no es válido.'
            );
        }

        $startsAt =
            trim(
                $startsAt
            );

        if ($startsAt === '') {
            throw new RuntimeException(
                'La fecha de inicio es obligatoria.'
            );
        }

        $endsAt =
            $endsAt !== null
                ? trim(
                    $endsAt
                )
                : null;

        $currency =
            $currency !== null
                ? strtoupper(
                    trim(
                        $currency
                    )
                )
                : null;

        if (
            $currency !== null
            && strlen($currency) !== 3
        ) {
            throw new RuntimeException(
                'La moneda no es válida.'
            );
        }

        if (
            $pricePaid !== null
            && $pricePaid < 0
        ) {
            throw new RuntimeException(
                'El importe pagado no puede ser negativo.'
            );
        }

        if (
            $paymentId !== null
            && $this->findByPaymentId(
                $paymentId
            ) !== null
        ) {
            throw new RuntimeException(
                'Ya existe una suscripción asociada a ese pago.'
            );
        }

        $now =
            current_time(
                'mysql',
                true
            );

        $inserted =
            $wpdb->insert(
                $this->tableName,
                [
                    'customer_id' =>
                        $customerId,

                    'plan_id' =>
                        $planId,

                    'payment_id' =>
                        $paymentId,

                    'status' =>
                        $status,

                    'starts_at' =>
                        $startsAt,

                    'ends_at' =>
                        $endsAt,

                    'cancelled_at' =>
                        null,

                    'price_paid' =>
                        $pricePaid !== null
                            ? number_format(
                                $pricePaid,
                                2,
                                '.',
                                ''
                            )
                            : null,

                    'currency' =>
                        $currency,

                    'source_type' =>
                        $sourceType,

                    'source_reference' =>
                        $sourceReference,

                    'auto_renew' =>
                        $autoRenew
                            ? 1
                            : 0,

                    'created_at' =>
                        $now,

                    'updated_at' =>
                        $now,
                ]
            );

        if ($inserted === false) {
            throw new RuntimeException(
                sprintf(
                    'No se pudo crear la suscripción: %s',
                    $wpdb->last_error
                )
            );
        }

        $subscription =
            $this->findById(
                (int) $wpdb->insert_id
            );

        if ($subscription === null) {
            throw new RuntimeException(
                'La suscripción se creó, pero no pudo recuperarse.'
            );
        }

        return $subscription;
    }

    public function cancel(
        int $subscriptionId
    ): Subscription {
        global $wpdb;

        $subscription =
            $this->findById(
                $subscriptionId
            );

        if ($subscription === null) {
            throw new RuntimeException(
                'No se encontró la suscripción.'
            );
        }

        if (
            $subscription->getStatus()
            === SubscriptionStatus::CANCELLED
        ) {
            return $subscription;
        }

        $now =
            current_time(
                'mysql',
                true
            );

        $updated =
            $wpdb->update(
                $this->tableName,
                [
                    'status' =>
                        SubscriptionStatus::CANCELLED,

                    'cancelled_at' =>
                        $now,

                    'auto_renew' =>
                        0,

                    'updated_at' =>
                        $now,
                ],
                [
                    'id' =>
                        $subscriptionId,
                ]
            );

        if ($updated === false) {
            throw new RuntimeException(
                sprintf(
                    'No se pudo cancelar la suscripción: %s',
                    $wpdb->last_error
                )
            );
        }

        $cancelled =
            $this->findById(
                $subscriptionId
            );

        if ($cancelled === null) {
            throw new RuntimeException(
                'La suscripción se canceló, pero no pudo recuperarse.'
            );
        }

        return $cancelled;
    }

    /**
     * @param mixed $rows
     *
     * @return array<int, Subscription>
     */
    private function hydrateRows(
        mixed $rows
    ): array {
        if (!is_array($rows)) {
            return [];
        }

        $subscriptions = [];

        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }

            $subscriptions[] =
                Subscription::fromArray(
                    $row
                );
        }

        return $subscriptions;
    }
}