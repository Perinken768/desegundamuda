<?php

declare(strict_types=1);

namespace DSM\Ofertas\Integration;

use DSM\Ofertas\Application\BuildOfferSchedule;
use DSM\Ofertas\Application\ResolveOffer;
use DSM\Suscripciones\Subscription\SubscriptionPlan;
use Throwable;

if (!defined('ABSPATH')) {
    exit;
}

final class SubscriptionPlanPresentationIntegration
{
    private const FILTER =
        'dsm_subscription_plan_offer_presentation';

    public static function register(): void
    {
        add_filter(
            self::FILTER,
            [
                self::class,
                'providePresentation',
            ],
            10,
            3
        );
    }

    /**
     * @param array<string, mixed>|null $presentation
     *
     * @return array<string, mixed>|null
     */
    public static function providePresentation(
        mixed $presentation,
        SubscriptionPlan $plan,
        int $customerId
    ): mixed {
        /*
         * Otro módulo podría haber proporcionado antes
         * una presentación.
         */
        if (is_array($presentation)) {
            return $presentation;
        }

        if ($customerId <= 0) {
            return null;
        }

        if (
            !$plan->isActive()
            || $plan->isFree()
        ) {
            return null;
        }

        try {
            $resolution =
                (
                    new ResolveOffer()
                )->execute(
                    $customerId,
                    $plan->getId(),
                    'new_subscription'
                );

            if ($resolution === null) {
                return null;
            }

            $schedule =
                (
                    new BuildOfferSchedule()
                )->execute(
                    $resolution
                );

            $benefitType =
                $resolution
                    ->getBenefitType();

            $benefitValue =
                $resolution
                    ->getBenefitValue();

            $durationMonths =
                $resolution
                    ->getDurationMonths();

            return [
                'offer_id' =>
                    $resolution
                        ->getOfferId(),

                'offer_name' =>
                    $resolution
                        ->getOfferName(),

                'benefit_type' =>
                    $benefitType,

                'benefit_value' =>
                    $benefitValue,

                'duration_months' =>
                    $durationMonths,

                'original_price' =>
                    $schedule
                        ->getOriginalPrice(),

                'promotional_price' =>
                    $schedule
                        ->getPromotionalPrice(),

                'currency' =>
                    $schedule
                        ->getCurrency(),

                'headline' =>
                    self::buildHeadline(
                        $benefitType,
                        $benefitValue,
                        $durationMonths
                    ),

                'description' =>
                    self::buildDescription(
                        $benefitType,
                        $benefitValue,
                        $durationMonths,
                        $schedule
                            ->getOriginalPrice(),
                        $schedule
                            ->getPromotionalPrice(),
                        $schedule
                            ->getCurrency()
                    ),
            ];
        } catch (Throwable) {
            /*
             * Una incidencia en DSM Ofertas nunca debe impedir
             * que se muestre la página de suscripciones.
             */
            return null;
        }
    }

    private static function buildHeadline(
        string $benefitType,
        float $benefitValue,
        ?int $durationMonths
    ): string {
        return match ($benefitType) {
            'free_months' =>
                sprintf(
                    '%d %s gratis',
                    (int) $benefitValue,
                    (int) $benefitValue === 1
                        ? 'mes'
                        : 'meses'
                ),

            'percentage_discount' =>
                sprintf(
                    '%s %% de descuento',
                    self::formatNumber(
                        $benefitValue
                    )
                ),

            'fixed_discount' =>
                sprintf(
                    '%s € de descuento',
                    self::formatNumber(
                        $benefitValue
                    )
                ),

            'special_price' =>
                sprintf(
                    'Precio especial de %s €',
                    self::formatNumber(
                        $benefitValue
                    )
                ),

            default =>
                'Oferta disponible',
        };
    }

    private static function buildDescription(
        string $benefitType,
        float $benefitValue,
        ?int $durationMonths,
        float $originalPrice,
        float $promotionalPrice,
        string $currency
    ): string {
        if ($benefitType === 'free_months') {
            return sprintf(
                'Disfruta %d %s sin coste. Después, %s %s por mes.',
                (int) $benefitValue,
                (int) $benefitValue === 1
                    ? 'mes'
                    : 'meses',
                self::formatMoney(
                    $originalPrice
                ),
                $currency
            );
        }

        if (
            $durationMonths !== null
            && $durationMonths > 0
        ) {
            return sprintf(
                '%s %s al mes durante %d %s. Después, %s %s al mes.',
                self::formatMoney(
                    $promotionalPrice
                ),
                $currency,
                $durationMonths,
                $durationMonths === 1
                    ? 'mes'
                    : 'meses',
                self::formatMoney(
                    $originalPrice
                ),
                $currency
            );
        }

        return sprintf(
            'Precio promocional: %s %s al mes.',
            self::formatMoney(
                $promotionalPrice
            ),
            $currency
        );
    }

    private static function formatMoney(
        float $amount
    ): string {
        return number_format_i18n(
            $amount,
            2
        );
    }

    private static function formatNumber(
        float $number
    ): string {
        if (
            abs(
                $number
                - round($number)
            ) < 0.00001
        ) {
            return (string)
                (int) round(
                    $number
                );
        }

        return number_format_i18n(
            $number,
            2
        );
    }

    private function __construct()
    {
    }
}
