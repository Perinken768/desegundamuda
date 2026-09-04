<?php

declare(strict_types=1);

namespace DSM\Suscripciones\Subscription;

use RuntimeException;

if (!defined('ABSPATH')) {
    exit;
}

final class SubscriptionPaymentRepository
{
    private string $tableName;

    public function __construct()
    {
        global $wpdb;

        $this->tableName =
            $wpdb->prefix
            . 'dsm_subscription_payments';
    }

    public function findById(
        int $id
    ): ?SubscriptionPayment {
        global $wpdb;

        if ($id <= 0) {
            return null;
        }

        $row =
            $wpdb->get_row(
                $wpdb->prepare(
                    "SELECT *
                    FROM {$this->tableName}
                    WHERE id = %d
                    LIMIT 1",
                    $id
                ),
                ARRAY_A
            );

        return is_array($row)
            ? SubscriptionPayment::fromArray(
                $row
            )
            : null;
    }

    public function findByPaymentId(
        int $paymentId
    ): ?SubscriptionPayment {
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
            ? SubscriptionPayment::fromArray(
                $row
            )
            : null;
    }

    public function findByProviderInvoiceReference(
        string $provider,
        string $providerInvoiceReference
    ): ?SubscriptionPayment {
        global $wpdb;

        $provider =
            strtolower(
                trim(
                    $provider
                )
            );

        $providerInvoiceReference =
            trim(
                $providerInvoiceReference
            );

        if (
            $provider === ''
            || $providerInvoiceReference === ''
        ) {
            return null;
        }

        $row =
            $wpdb->get_row(
                $wpdb->prepare(
                    "SELECT *
                    FROM {$this->tableName}
                    WHERE provider = %s
                    AND provider_invoice_reference = %s
                    LIMIT 1",
                    $provider,
                    $providerInvoiceReference
                ),
                ARRAY_A
            );

        return is_array($row)
            ? SubscriptionPayment::fromArray(
                $row
            )
            : null;
    }

    /**
     * @return array<int, SubscriptionPayment>
     */
    public function findBySubscriptionId(
        int $subscriptionId
    ): array {
        global $wpdb;

        if ($subscriptionId <= 0) {
            return [];
        }

        $rows =
            $wpdb->get_results(
                $wpdb->prepare(
                    "SELECT *
                    FROM {$this->tableName}
                    WHERE subscription_id = %d
                    ORDER BY
                        period_start DESC,
                        created_at DESC,
                        id DESC",
                    $subscriptionId
                ),
                ARRAY_A
            );

        if (!is_array($rows)) {
            return [];
        }

        $payments = [];

        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }

            $payments[] =
                SubscriptionPayment::fromArray(
                    $row
                );
        }

        return $payments;
    }

    public function create(
        int $subscriptionId,
        ?int $paymentId,
        ?string $provider,
        ?string $providerInvoiceReference,
        ?string $periodStart,
        ?string $periodEnd,
        ?float $amount,
        ?string $currency
    ): SubscriptionPayment {
        global $wpdb;

        if ($subscriptionId <= 0) {
            throw new RuntimeException(
                'El identificador de la suscripción no es válido.'
            );
        }

        if (
            $paymentId !== null
            && $paymentId <= 0
        ) {
            throw new RuntimeException(
                'El identificador del pago no es válido.'
            );
        }

        if (
            $amount !== null
            && $amount < 0
        ) {
            throw new RuntimeException(
                'El importe del cobro no puede ser negativo.'
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

        $providerInvoiceReference =
            $providerInvoiceReference !== null
                ? trim(
                    $providerInvoiceReference
                )
                : null;

        if ($providerInvoiceReference === '') {
            $providerInvoiceReference = null;
        }

        $periodStart =
            $periodStart !== null
                ? trim(
                    $periodStart
                )
                : null;

        if ($periodStart === '') {
            $periodStart = null;
        }

        $periodEnd =
            $periodEnd !== null
                ? trim(
                    $periodEnd
                )
                : null;

        if ($periodEnd === '') {
            $periodEnd = null;
        }

        $currency =
            $currency !== null
                ? strtoupper(
                    trim(
                        $currency
                    )
                )
                : null;

        if ($currency === '') {
            $currency = null;
        }

        if (
            $currency !== null
            && strlen($currency) !== 3
        ) {
            throw new RuntimeException(
                'La moneda del cobro no es válida.'
            );
        }

        /*
         * Idempotencia por pago DSM.
         */
        if ($paymentId !== null) {
            $existingByPayment =
                $this->findByPaymentId(
                    $paymentId
                );

            if ($existingByPayment !== null) {
                return $existingByPayment;
            }
        }

        /*
         * Idempotencia por factura del proveedor.
         */
        if (
            $provider !== null
            && $providerInvoiceReference !== null
        ) {
            $existingByInvoice =
                $this->findByProviderInvoiceReference(
                    $provider,
                    $providerInvoiceReference
                );

            if ($existingByInvoice !== null) {
                return $existingByInvoice;
            }
        }

        $inserted =
            $wpdb->insert(
                $this->tableName,
                [
                    'subscription_id' =>
                        $subscriptionId,

                    'payment_id' =>
                        $paymentId,

                    'provider' =>
                        $provider,

                    'provider_invoice_reference' =>
                        $providerInvoiceReference,

                    'period_start' =>
                        $periodStart,

                    'period_end' =>
                        $periodEnd,

                    'amount' =>
                        $amount !== null
                            ? number_format(
                                $amount,
                                2,
                                '.',
                                ''
                            )
                            : null,

                    'currency' =>
                        $currency,

                    'created_at' =>
                        current_time(
                            'mysql',
                            true
                        ),
                ]
            );

        if ($inserted === false) {
            throw new RuntimeException(
                sprintf(
                    'No se pudo registrar el cobro de la suscripción: %s',
                    $wpdb->last_error
                )
            );
        }

        $payment =
            $this->findById(
                (int) $wpdb->insert_id
            );

        if ($payment === null) {
            throw new RuntimeException(
                'El cobro se registró, pero no pudo recuperarse.'
            );
        }

        return $payment;
    }
}
