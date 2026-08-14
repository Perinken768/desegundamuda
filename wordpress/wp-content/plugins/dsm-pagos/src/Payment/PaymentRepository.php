<?php

declare(strict_types=1);

namespace DSM\Pagos\Payment;

use RuntimeException;

if (!defined('ABSPATH')) {
    exit;
}

final class PaymentRepository
{
    private string $tableName;

    public function __construct()
    {
        global $wpdb;

        $this->tableName =
            $wpdb->prefix
            . 'dsm_payments';
    }

    public function findById(
        int $paymentId
    ): ?Payment {
        global $wpdb;

        if ($paymentId <= 0) {
            return null;
        }

        $row =
            $wpdb->get_row(
                $wpdb->prepare(
                    "SELECT *
                    FROM {$this->tableName}
                    WHERE id = %d
                    LIMIT 1",
                    $paymentId
                ),
                ARRAY_A
            );

        if (!is_array($row)) {
            return null;
        }

        return Payment::fromArray(
            $row
        );
    }

    public function findByIdForUpdate(
        int $paymentId
    ): ?Payment {
        global $wpdb;

        if ($paymentId <= 0) {
            return null;
        }

        $row =
            $wpdb->get_row(
                $wpdb->prepare(
                    "SELECT *
                    FROM {$this->tableName}
                    WHERE id = %d
                    LIMIT 1
                    FOR UPDATE",
                    $paymentId
                ),
                ARRAY_A
            );

        if (!is_array($row)) {
            return null;
        }

        return Payment::fromArray(
            $row
        );
    }

    public function create(
        int $customerId,
        string $purpose,
        float $amount,
        string $currency,
        ?string $provider = null,
        ?string $providerReference = null,
        ?string $sourceType = null,
        ?int $sourceId = null,
        ?string $sourceReference = null
    ): Payment {
        global $wpdb;

        if ($customerId <= 0) {
            throw new RuntimeException(
                'El identificador del cliente no es válido.'
            );
        }

        $purpose =
            sanitize_key(
                $purpose
            );

        if ($purpose === '') {
            throw new RuntimeException(
                'La finalidad del pago no es válida.'
            );
        }

        if ($amount < 0) {
            throw new RuntimeException(
                'El importe del pago no puede ser negativo.'
            );
        }

        $currency =
            strtoupper(
                trim(
                    sanitize_text_field(
                        $currency
                    )
                )
            );

        if (
            strlen($currency) !== 3
            || !ctype_alpha($currency)
        ) {
            throw new RuntimeException(
                'La moneda del pago no es válida.'
            );
        }

        if (
            $sourceId !== null
            && $sourceId <= 0
        ) {
            throw new RuntimeException(
                'El identificador de origen no es válido.'
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

                    'purpose' =>
                        $purpose,

                    'amount' =>
                        number_format(
                            $amount,
                            2,
                            '.',
                            ''
                        ),

                    'currency' =>
                        $currency,

                    'status' =>
                        PaymentStatus::PENDING,

                    'provider' =>
                        self::normalizeNullableString(
                            $provider,
                            50
                        ),

                    'provider_reference' =>
                        self::normalizeNullableString(
                            $providerReference,
                            190
                        ),

                    'source_type' =>
                        self::normalizeNullableString(
                            $sourceType,
                            50
                        ),

                    'source_id' =>
                        $sourceId,

                    'source_reference' =>
                        self::normalizeNullableString(
                            $sourceReference,
                            190
                        ),

                    'created_at' =>
                        $now,

                    'updated_at' =>
                        $now,

                    'paid_at' =>
                        null,

                    'failed_at' =>
                        null,

                    'cancelled_at' =>
                        null,
                ]
            );

        if ($inserted === false) {
            throw new RuntimeException(
                sprintf(
                    'No se pudo crear el pago: %s',
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
                'El pago se creó, pero no pudo recuperarse.'
            );
        }

        return $payment;
    }

    public function markPaid(
        int $paymentId,
        ?string $provider = null,
        ?string $providerReference = null
    ): Payment {
        global $wpdb;

        $payment =
            $this->findByIdForUpdate(
                $paymentId
            );

        if ($payment === null) {
            throw new RuntimeException(
                'No se encontró el pago indicado.'
            );
        }

        if ($payment->isPaid()) {
            return $payment;
        }

        if (!$payment->isPending()) {
            throw new RuntimeException(
                'Solo un pago pendiente puede marcarse como pagado.'
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
                        PaymentStatus::PAID,

                    'provider' =>
                        self::normalizeNullableString(
                            $provider,
                            50
                        )
                        ?? $payment->getProvider(),

                    'provider_reference' =>
                        self::normalizeNullableString(
                            $providerReference,
                            190
                        )
                        ?? $payment
                            ->getProviderReference(),

                    'paid_at' =>
                        $now,

                    'updated_at' =>
                        $now,
                ],
                [
                    'id' =>
                        $paymentId,
                ]
            );

        if ($updated === false) {
            throw new RuntimeException(
                sprintf(
                    'No se pudo confirmar el pago: %s',
                    $wpdb->last_error
                )
            );
        }

        $updatedPayment =
            $this->findById(
                $paymentId
            );

        if ($updatedPayment === null) {
            throw new RuntimeException(
                'El pago se confirmó, pero no pudo recuperarse.'
            );
        }

        return $updatedPayment;
    }

    public function markFailed(
        int $paymentId
    ): Payment {
        return $this->markFinalStatus(
            $paymentId,
            PaymentStatus::FAILED,
            'failed_at'
        );
    }

    public function markCancelled(
        int $paymentId
    ): Payment {
        return $this->markFinalStatus(
            $paymentId,
            PaymentStatus::CANCELLED,
            'cancelled_at'
        );
    }

    /**
     * @return array<int, Payment>
     */
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
                    "SELECT *
                    FROM {$this->tableName}
                    WHERE customer_id = %d
                    ORDER BY created_at DESC, id DESC",
                    $customerId
                ),
                ARRAY_A
            );

        return $this->hydrateRows(
            $rows
        );
    }

    private function markFinalStatus(
        int $paymentId,
        string $status,
        string $dateColumn
    ): Payment {
        global $wpdb;

        $payment =
            $this->findByIdForUpdate(
                $paymentId
            );

        if ($payment === null) {
            throw new RuntimeException(
                'No se encontró el pago indicado.'
            );
        }

        if ($payment->getStatus() === $status) {
            return $payment;
        }

        if (!$payment->isPending()) {
            throw new RuntimeException(
                'Solo un pago pendiente puede finalizarse.'
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
                        $status,

                    $dateColumn =>
                        $now,

                    'updated_at' =>
                        $now,
                ],
                [
                    'id' =>
                        $paymentId,
                ]
            );

        if ($updated === false) {
            throw new RuntimeException(
                sprintf(
                    'No se pudo actualizar el pago: %s',
                    $wpdb->last_error
                )
            );
        }

        $updatedPayment =
            $this->findById(
                $paymentId
            );

        if ($updatedPayment === null) {
            throw new RuntimeException(
                'El pago se actualizó, pero no pudo recuperarse.'
            );
        }

        return $updatedPayment;
    }

    /**
     * @param mixed $rows
     *
     * @return array<int, Payment>
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
            ): Payment =>
                Payment::fromArray(
                    $row
                ),
            $rows
        );
    }

    private static function normalizeNullableString(
        ?string $value,
        int $maximumLength
    ): ?string {
        if (
            $value === null
            || trim($value) === ''
        ) {
            return null;
        }

        $value =
            sanitize_text_field(
                $value
            );

        if (
            mb_strlen(
                $value
            ) > $maximumLength
        ) {
            throw new RuntimeException(
                sprintf(
                    'El texto no puede superar los %d caracteres.',
                    $maximumLength
                )
            );
        }

        return $value;
    }
}
