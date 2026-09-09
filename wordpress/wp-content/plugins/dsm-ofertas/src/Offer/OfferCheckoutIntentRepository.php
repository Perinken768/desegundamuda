<?php

declare(strict_types=1);

namespace DSM\Ofertas\Offer;

use DSM\Ofertas\Application\BuildOfferSchedule;
use RuntimeException;

if (!defined('ABSPATH')) {
    exit;
}

final class OfferCheckoutIntentRepository
{
    private string $table;

    public function __construct()
    {
        global $wpdb;

        $this->table =
            $wpdb->prefix
            . 'dsm_offer_checkout_intents';
    }

    public function findByPaymentId(
        int $paymentId
    ): ?object {
        global $wpdb;

        if ($paymentId <= 0) {
            return null;
        }

        $row =
            $wpdb->get_row(
                $wpdb->prepare(
                    "
                    SELECT *
                    FROM {$this->table}
                    WHERE payment_id = %d
                    LIMIT 1
                    ",
                    $paymentId
                )
            );

        return is_object($row)
            ? $row
            : null;
    }

    public function create(
        int $paymentId,
        int $customerId,
        OfferResolution $resolution,
        string $commercialContext =
            'new_subscription',
        ?string $benefitStartsAtUtc = null
    ): int {
        global $wpdb;

        if (
            $paymentId <= 0
            || $customerId <= 0
        ) {
            throw new RuntimeException(
                'No se pudo crear la intención de oferta.'
            );
        }

        $commercialContext =
            sanitize_key(
                $commercialContext
            );

        if (
            !in_array(
                $commercialContext,
                [
                    'new_subscription',
                    'renewal',
                    'retention',
                ],
                true
            )
        ) {
            throw new RuntimeException(
                'El contexto comercial de la intención no es válido.'
            );
        }

        $schedule =
            (
                new BuildOfferSchedule()
            )->execute(
                $resolution,
                $benefitStartsAtUtc
            );

        $benefitStartsAt =
            $schedule->getStartsAt();

        $benefitEndsAt =
            $schedule->getEndsAt();

        /*
         * trial_end se mantiene como dato específico
         * para la integración futura con Stripe.
         *
         * Para otros descuentos utilizaremos
         * benefit_starts_at / benefit_ends_at.
         */
        $trialEnd =
            $resolution->isFreePeriod()
                ? $benefitEndsAt
                : null;

        $now =
            current_time(
                'mysql',
                true
            );

        $expiresAt =
            gmdate(
                'Y-m-d H:i:s',
                strtotime(
                    $now . ' UTC'
                )
                + DAY_IN_SECONDS
            );

        $inserted =
            $wpdb->insert(
                $this->table,
                [
                    'payment_id' =>
                        $paymentId,

                    'offer_id' =>
                        $resolution
                            ->getOfferId(),

                    'offer_rule_id' =>
                        $resolution
                            ->getRuleId(),

                    'customer_id' =>
                        $customerId,

                    'plan_id' =>
                        $resolution
                            ->getPlanId(),

                    'commercial_context' =>
                        $commercialContext,

                    'benefit_type' =>
                        $resolution
                            ->getBenefitType(),

                    'benefit_value' =>
                        $resolution
                            ->getBenefitValue(),

                    'duration_months' =>
                        $resolution
                            ->getDurationMonths(),

                    'original_price' =>
                        $schedule
                            ->getOriginalPrice(),

                    'final_price' =>
                        $schedule
                            ->getPromotionalPrice(),

                    'currency' =>
                        $schedule
                            ->getCurrency(),

                    'benefit_starts_at' =>
                        $benefitStartsAt,

                    'benefit_ends_at' =>
                        $benefitEndsAt,

                    'trial_end' =>
                        $trialEnd,

                    'status' =>
                        'pending',

                    'expires_at' =>
                        $expiresAt,

                    'expired_at' =>
                        null,

                    'completed_at' =>
                        null,

                    'created_at' =>
                        $now,

                    'updated_at' =>
                        $now,
                ]
            );

        if ($inserted === false) {
            throw new RuntimeException(
                'No se pudo registrar la intención de oferta: '
                . $wpdb->last_error
            );
        }

        return (int) $wpdb->insert_id;
    }

    public function markCompleted(
        int $paymentId
    ): void {
        global $wpdb;

        if ($paymentId <= 0) {
            throw new RuntimeException(
                'El pago indicado no es válido.'
            );
        }

        $now =
            current_time(
                'mysql',
                true
            );

        $updated =
            $wpdb->update(
                $this->table,
                [
                    'status' =>
                        'completed',

                    'completed_at' =>
                        $now,

                    'updated_at' =>
                        $now,
                ],
                [
                    'payment_id' =>
                        $paymentId,
                ]
            );

        if ($updated === false) {
            throw new RuntimeException(
                'No se pudo completar la intención de oferta: '
                . $wpdb->last_error
            );
        }
    }

    public function expirePending(
        ?string $nowUtc = null
    ): int {
        global $wpdb;

        $paymentsTable =
            $wpdb->prefix
            . 'dsm_payments';

        $nowUtc =
            $nowUtc !== null
                ? trim(
                    $nowUtc
                )
                : current_time(
                    'mysql',
                    true
                );

        if ($nowUtc === '') {
            throw new RuntimeException(
                'La fecha de caducidad no es válida.'
            );
        }

        $timestamp =
            strtotime(
                $nowUtc
                . ' UTC'
            );

        if ($timestamp === false) {
            throw new RuntimeException(
                'La fecha de caducidad no es válida.'
            );
        }

        $nowUtc =
            gmdate(
                'Y-m-d H:i:s',
                $timestamp
            );

        /*
         * Solo caducamos una intención cuando:
         *
         * - sigue pending;
         * - ha alcanzado expires_at;
         * - su Payment DSM continúa pending.
         *
         * Un pago ya confirmado jamás debe perder
         * accidentalmente su intención comercial.
         */
        $updated =
            $wpdb->query(
                $wpdb->prepare(
                    "
                    UPDATE {$this->table} i

                    INNER JOIN {$paymentsTable} p
                        ON p.id = i.payment_id

                    SET
                        i.status = 'expired',
                        i.expired_at = %s,
                        i.updated_at = %s

                    WHERE i.status = 'pending'
                      AND i.expires_at IS NOT NULL
                      AND i.expires_at <= %s
                      AND p.status = 'pending'
                    ",
                    $nowUtc,
                    $nowUtc,
                    $nowUtc
                )
            );

        if ($updated === false) {
            throw new RuntimeException(
                'No se pudieron caducar las intenciones: '
                . $wpdb->last_error
            );
        }

        return (int) $updated;
    }

}
