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

    public function findByProviderSubscriptionId(
        string $provider,
        string $providerSubscriptionId
    ): ?Subscription {
        global $wpdb;

        $provider =
            strtolower(
                trim(
                    $provider
                )
            );

        $providerSubscriptionId =
            trim(
                $providerSubscriptionId
            );

        if (
            $provider === ''
            || $providerSubscriptionId === ''
        ) {
            return null;
        }

        $row =
            $wpdb->get_row(
                $wpdb->prepare(
                    "SELECT *
                    FROM {$this->tableName}
                    WHERE provider = %s
                    AND provider_subscription_id = %s
                    LIMIT 1",
                    $provider,
                    $providerSubscriptionId
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
        bool $autoRenew = false,
        ?string $provider = null,
        ?string $providerSubscriptionId = null
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

        if ($endsAt === '') {
            $endsAt = null;
        }

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

        $provider =
            $provider !== null
                ? strtolower(
                    trim(
                        $provider
                    )
                )
                : null;

        if ($provider === '') {
            $provider = null;
        }

        $providerSubscriptionId =
            $providerSubscriptionId !== null
                ? trim(
                    $providerSubscriptionId
                )
                : null;

        if ($providerSubscriptionId === '') {
            $providerSubscriptionId = null;
        }

        if (
            $providerSubscriptionId !== null
            && $provider === null
        ) {
            throw new RuntimeException(
                'La suscripción del proveedor requiere indicar el proveedor.'
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

        if (
            $provider !== null
            && $providerSubscriptionId !== null
            && $this->findByProviderSubscriptionId(
                $provider,
                $providerSubscriptionId
            ) !== null
        ) {
            throw new RuntimeException(
                'Ya existe una suscripción asociada a esa referencia del proveedor.'
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

                    'cancel_requested_at' =>
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

                    'provider' =>
                        $provider,

                    'provider_subscription_id' =>
                        $providerSubscriptionId,

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

        return $this->requireById(
            (int) $wpdb->insert_id,
            'La suscripción se creó, pero no pudo recuperarse.'
        );
    }

    /**
     * Vincula una suscripción DSM con una suscripción
     * recurrente gestionada por un proveedor externo.
     */
    public function activateRecurringProvider(
        int $subscriptionId,
        string $provider,
        string $providerSubscriptionId,
        string $endsAt
    ): Subscription {
        global $wpdb;

        $subscription =
            $this->requireById(
                $subscriptionId
            );

        $provider =
            strtolower(
                trim(
                    $provider
                )
            );

        $providerSubscriptionId =
            trim(
                $providerSubscriptionId
            );

        $endsAt =
            trim(
                $endsAt
            );

        if (
            $provider === ''
            || $providerSubscriptionId === ''
        ) {
            throw new RuntimeException(
                'La referencia de la suscripción del proveedor no es válida.'
            );
        }

        if ($endsAt === '') {
            throw new RuntimeException(
                'La fecha de fin del período es obligatoria.'
            );
        }

        $existing =
            $this->findByProviderSubscriptionId(
                $provider,
                $providerSubscriptionId
            );

        if (
            $existing !== null
            && $existing->getId()
                !== $subscription->getId()
        ) {
            throw new RuntimeException(
                'La suscripción del proveedor ya está vinculada a otra suscripción DSM.'
            );
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
                        SubscriptionStatus::ACTIVE,

                    'ends_at' =>
                        $endsAt,

                    'cancelled_at' =>
                        null,

                    'cancel_requested_at' =>
                        null,

                    'auto_renew' =>
                        1,

                    'provider' =>
                        $provider,

                    'provider_subscription_id' =>
                        $providerSubscriptionId,

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
                    'No se pudo vincular la suscripción con el proveedor: %s',
                    $wpdb->last_error
                )
            );
        }

        return $this->requireById(
            $subscriptionId
        );
    }

    /**
     * Actualiza el final del período actualmente pagado.
     *
     * No modifica starts_at porque esa fecha representa
     * el inicio original de la suscripción.
     */
    public function updatePeriodEnd(
        int $subscriptionId,
        string $endsAt
    ): Subscription {
        global $wpdb;

        $this->requireById(
            $subscriptionId
        );

        $endsAt =
            trim(
                $endsAt
            );

        if ($endsAt === '') {
            throw new RuntimeException(
                'La fecha de fin del período es obligatoria.'
            );
        }

        $updated =
            $wpdb->update(
                $this->tableName,
                [
                    'ends_at' =>
                        $endsAt,

                    'updated_at' =>
                        current_time(
                            'mysql',
                            true
                        ),
                ],
                [
                    'id' =>
                        $subscriptionId,
                ]
            );

        if ($updated === false) {
            throw new RuntimeException(
                sprintf(
                    'No se pudo actualizar el período de la suscripción: %s',
                    $wpdb->last_error
                )
            );
        }

        return $this->requireById(
            $subscriptionId
        );
    }

    /**
     * Solicita la cancelación al finalizar el período pagado.
     *
     * IMPORTANTE:
     * La suscripción continúa ACTIVE y conserva los derechos
     * hasta ends_at.
     */
    public function requestCancellationAtPeriodEnd(
        int $subscriptionId
    ): Subscription {
        global $wpdb;

        $subscription =
            $this->requireById(
                $subscriptionId
            );

        if (
            $subscription->getStatus()
            !== SubscriptionStatus::ACTIVE
        ) {
            throw new RuntimeException(
                'Solo puede programarse la cancelación de una suscripción activa.'
            );
        }

        if (
            $subscription->getCancelRequestedAt()
            !== null
            && !$subscription->isAutoRenew()
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
                    'cancel_requested_at' =>
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
                    'No se pudo programar la cancelación: %s',
                    $wpdb->last_error
                )
            );
        }

        return $this->requireById(
            $subscriptionId
        );
    }

    /**
     * Reactiva la renovación antes de finalizar el período.
     */
    public function reactivateRenewal(
        int $subscriptionId
    ): Subscription {
        global $wpdb;

        $subscription =
            $this->requireById(
                $subscriptionId
            );

        if (
            $subscription->getStatus()
            !== SubscriptionStatus::ACTIVE
        ) {
            throw new RuntimeException(
                'Solo puede reactivarse una suscripción activa.'
            );
        }

        if (!$subscription->isProviderManaged()) {
            throw new RuntimeException(
                'La suscripción no está gestionada por un proveedor recurrente.'
            );
        }

        $updated =
            $wpdb->update(
                $this->tableName,
                [
                    'cancel_requested_at' =>
                        null,

                    'auto_renew' =>
                        1,

                    'updated_at' =>
                        current_time(
                            'mysql',
                            true
                        ),
                ],
                [
                    'id' =>
                        $subscriptionId,
                ]
            );

        if ($updated === false) {
            throw new RuntimeException(
                sprintf(
                    'No se pudo reactivar la renovación: %s',
                    $wpdb->last_error
                )
            );
        }

        return $this->requireById(
            $subscriptionId
        );
    }

    /**
     * Cancelación inmediata.
     *
     * Este método se mantiene para administración y
     * revocaciones inmediatas. NO debe utilizarse para
     * la cancelación normal solicitada por el cliente.
     */
    public function cancel(
        int $subscriptionId
    ): Subscription {
        global $wpdb;

        $subscription =
            $this->requireById(
                $subscriptionId
            );

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

        return $this->requireById(
            $subscriptionId,
            'La suscripción se canceló, pero no pudo recuperarse.'
        );
    }

    /**
     * Marca como finalizada una suscripción cuyo período
     * recurrente ha terminado definitivamente.
     */
    public function markCancelled(
        int $subscriptionId,
        ?string $cancelledAt = null
    ): Subscription {
        global $wpdb;

        $subscription =
            $this->requireById(
                $subscriptionId
            );

        if (
            $subscription->getStatus()
            === SubscriptionStatus::CANCELLED
        ) {
            return $subscription;
        }

        $cancelledAt =
            $cancelledAt !== null
                ? trim(
                    $cancelledAt
                )
                : '';

        if ($cancelledAt === '') {
            $cancelledAt =
                current_time(
                    'mysql',
                    true
                );
        }

        $updated =
            $wpdb->update(
                $this->tableName,
                [
                    'status' =>
                        SubscriptionStatus::CANCELLED,

                    'cancelled_at' =>
                        $cancelledAt,

                    'auto_renew' =>
                        0,

                    'updated_at' =>
                        current_time(
                            'mysql',
                            true
                        ),
                ],
                [
                    'id' =>
                        $subscriptionId,
                ]
            );

        if ($updated === false) {
            throw new RuntimeException(
                sprintf(
                    'No se pudo finalizar la suscripción: %s',
                    $wpdb->last_error
                )
            );
        }

        return $this->requireById(
            $subscriptionId
        );
    }

    private function requireById(
        int $subscriptionId,
        string $errorMessage =
            'No se encontró la suscripción.'
    ): Subscription {
        $subscription =
            $this->findById(
                $subscriptionId
            );

        if ($subscription === null) {
            throw new RuntimeException(
                $errorMessage
            );
        }

        return $subscription;
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
