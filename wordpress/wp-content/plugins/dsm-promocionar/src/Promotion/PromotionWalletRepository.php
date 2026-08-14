<?php

declare(strict_types=1);

namespace DSM\Promocionar\Promotion;

use RuntimeException;

if (!defined('ABSPATH')) {
    exit;
}

final class PromotionWalletRepository
{
    private string $tableName;

    public function __construct()
    {
        global $wpdb;

        $this->tableName =
            $wpdb->prefix
            . 'dsm_promotion_wallets';
    }

    public function findById(
        int $walletId
    ): ?PromotionWallet {
        global $wpdb;

        if ($walletId <= 0) {
            return null;
        }

        $row =
            $wpdb->get_row(
                $wpdb->prepare(
                    "SELECT
                        id,
                        customer_id,
                        plan_id,
                        purchased_seconds,
                        remaining_seconds,
                        price_paid,
                        currency,
                        status,
                        source_type,
                        source_reference,
                        created_at,
                        updated_at
                    FROM {$this->tableName}
                    WHERE id = %d
                    LIMIT 1",
                    $walletId
                ),
                ARRAY_A
            );

        if (!is_array($row)) {
            return null;
        }

        return PromotionWallet::fromArray(
            $row
        );
    }

    public function findByIdForUpdate(
        int $walletId
    ): ?PromotionWallet {
        global $wpdb;

        if ($walletId <= 0) {
            return null;
        }

        $row =
            $wpdb->get_row(
                $wpdb->prepare(
                    "SELECT
                        id,
                        customer_id,
                        plan_id,
                        purchased_seconds,
                        remaining_seconds,
                        price_paid,
                        currency,
                        status,
                        source_type,
                        source_reference,
                        created_at,
                        updated_at
                    FROM {$this->tableName}
                    WHERE id = %d
                    LIMIT 1
                    FOR UPDATE",
                    $walletId
                ),
                ARRAY_A
            );

        if (!is_array($row)) {
            return null;
        }

        return PromotionWallet::fromArray(
            $row
        );
    }

    public function create(
        int $customerId,
        int $seconds,
        ?string $sourceType = null,
        ?string $sourceReference = null,
        ?int $planId = null,
        ?float $pricePaid = null,
        ?string $currency = null,
        ?int $paymentId = null
    ): PromotionWallet {
        global $wpdb;

        if ($customerId <= 0) {
            throw new RuntimeException(
                'El identificador del cliente no es válido.'
            );
        }

        if ($seconds <= 0) {
            throw new RuntimeException(
                'El tiempo adquirido debe ser mayor que cero.'
            );
        }

        if (
            $planId !== null
            && $planId <= 0
        ) {
            throw new RuntimeException(
                'El identificador del plan no es válido.'
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
            $pricePaid !== null
            && $pricePaid < 0
        ) {
            throw new RuntimeException(
                'El precio pagado no puede ser negativo.'
            );
        }

        $sourceType =
            self::normalizeNullableString(
                $sourceType,
                50
            );

        $sourceReference =
            self::normalizeNullableString(
                $sourceReference,
                190
            );

        $currency =
            self::normalizeCurrency(
                $currency
            );

        if (
            ($pricePaid === null)
            !== ($currency === null)
        ) {
            throw new RuntimeException(
                'El precio pagado y la moneda deben informarse conjuntamente.'
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

                    'purchased_seconds' =>
                        $seconds,

                    'remaining_seconds' =>
                        $seconds,

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

                    'status' =>
                        PromotionWalletStatus::AVAILABLE,

                    'source_type' =>
                        $sourceType,

                    'source_reference' =>
                        $sourceReference,

                    'created_at' =>
                        $now,

                    'updated_at' =>
                        $now,
                ]
            );

        if ($inserted === false) {
            throw new RuntimeException(
                sprintf(
                    'No se pudo crear el saldo de promoción: %s',
                    $wpdb->last_error
                )
            );
        }

        $wallet =
            $this->findById(
                (int) $wpdb->insert_id
            );

        if ($wallet === null) {
            throw new RuntimeException(
                'El saldo de promoción se creó, pero no pudo recuperarse.'
            );
        }

        return $wallet;
    }

    /**
     * @return array<int, PromotionWallet>
     */

    public function findByPaymentId(
        int $paymentId
    ): ?PromotionWallet {
        global $wpdb;

        if ($paymentId <= 0) {
            return null;
        }

        $row =
            $wpdb->get_row(
                $wpdb->prepare(
                    "SELECT
                        id,
                        customer_id,
                        plan_id,
                        purchased_seconds,
                        remaining_seconds,
                        price_paid,
                        currency,
                        status,
                        source_type,
                        source_reference,
                        created_at,
                        updated_at
                    FROM {$this->tableName}
                    WHERE payment_id = %d
                    LIMIT 1",
                    $paymentId
                ),
                ARRAY_A
            );

        if (!is_array($row)) {
            return null;
        }

        return PromotionWallet::fromArray(
            $row
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
                        customer_id,
                        plan_id,
                        purchased_seconds,
                        remaining_seconds,
                        price_paid,
                        currency,
                        status,
                        source_type,
                        source_reference,
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
     * @return array<int, PromotionWallet>
     */
    public function findAvailableByCustomer(
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
                        customer_id,
                        plan_id,
                        purchased_seconds,
                        remaining_seconds,
                        price_paid,
                        currency,
                        status,
                        source_type,
                        source_reference,
                        created_at,
                        updated_at
                    FROM {$this->tableName}
                    WHERE customer_id = %d
                      AND status = %s
                      AND remaining_seconds > 0
                    ORDER BY
                        created_at ASC,
                        id ASC",
                    $customerId,
                    PromotionWalletStatus::AVAILABLE
                ),
                ARRAY_A
            );

        return $this->hydrateRows(
            $rows
        );
    }

    public function setStatus(
        int $walletId,
        string $status
    ): void {
        global $wpdb;

        if ($walletId <= 0) {
            throw new RuntimeException(
                'El identificador del saldo no es válido.'
            );
        }

        if (
            !PromotionWalletStatus::isValid(
                $status
            )
        ) {
            throw new RuntimeException(
                'El estado del saldo no es válido.'
            );
        }

        $updated =
            $wpdb->update(
                $this->tableName,
                [
                    'status' =>
                        $status,

                    'updated_at' =>
                        current_time(
                            'mysql',
                            true
                        ),
                ],
                [
                    'id' =>
                        $walletId,
                ]
            );

        if ($updated === false) {
            throw new RuntimeException(
                sprintf(
                    'No se pudo actualizar el estado del saldo: %s',
                    $wpdb->last_error
                )
            );
        }
    }

    public function consumeSeconds(
        int $walletId,
        int $seconds
    ): PromotionWallet {
        global $wpdb;

        if ($walletId <= 0) {
            throw new RuntimeException(
                'El identificador del saldo no es válido.'
            );
        }

        if ($seconds < 0) {
            throw new RuntimeException(
                'El tiempo consumido no puede ser negativo.'
            );
        }

        $wallet =
            $this->findByIdForUpdate(
                $walletId
            );

        if ($wallet === null) {
            throw new RuntimeException(
                'No se encontró el saldo de promoción.'
            );
        }

        $remainingSeconds =
            max(
                0,
                $wallet->getRemainingSeconds()
                - $seconds
            );

        $status =
            $remainingSeconds > 0
                ? PromotionWalletStatus::AVAILABLE
                : PromotionWalletStatus::EXHAUSTED;

        $updated =
            $wpdb->update(
                $this->tableName,
                [
                    'remaining_seconds' =>
                        $remainingSeconds,

                    'status' =>
                        $status,

                    'updated_at' =>
                        current_time(
                            'mysql',
                            true
                        ),
                ],
                [
                    'id' =>
                        $walletId,
                ]
            );

        if ($updated === false) {
            throw new RuntimeException(
                sprintf(
                    'No se pudo descontar el tiempo de promoción: %s',
                    $wpdb->last_error
                )
            );
        }

        $updatedWallet =
            $this->findById(
                $walletId
            );

        if ($updatedWallet === null) {
            throw new RuntimeException(
                'El saldo se actualizó, pero no pudo recuperarse.'
            );
        }

        return $updatedWallet;
    }

    /**
     * @param mixed $rows
     *
     * @return array<int, PromotionWallet>
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
            ): PromotionWallet =>
                PromotionWallet::fromArray(
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
            mb_strlen($value)
            > $maximumLength
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

    private static function normalizeCurrency(
        ?string $currency
    ): ?string {
        if (
            $currency === null
            || trim($currency) === ''
        ) {
            return null;
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
                'La moneda no es válida.'
            );
        }

        return $currency;
    }
}