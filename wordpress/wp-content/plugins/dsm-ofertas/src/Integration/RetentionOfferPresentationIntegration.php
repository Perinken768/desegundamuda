<?php

declare(strict_types=1);

namespace DSM\Ofertas\Integration;

use DSM\Ofertas\Application\ResolveOffer;
use DSM\Suscripciones\Subscription\Subscription;
use DSM\Suscripciones\Subscription\SubscriptionPlan;
use Throwable;

if (!defined('ABSPATH')) {
    exit;
}

final class RetentionOfferPresentationIntegration
{
    private const FILTER =
        'dsm_subscription_retention_offer_presentation';

    public static function register(): void
    {
        add_filter(
            self::FILTER,
            [
                self::class,
                'provide',
            ],
            10,
            4
        );
    }

    public static function provide(
        mixed $presentation,
        SubscriptionPlan $plan,
        Subscription $subscription,
        int $customerId
    ): mixed {
        if (is_array($presentation)) {
            return $presentation;
        }

        if ($customerId <= 0) {
            return null;
        }

        try {
            $resolution =
                (
                    new ResolveOffer()
                )->execute(
                    $customerId,
                    $plan->getId(),
                    'retention'
                );

            if ($resolution === null) {
                return null;
            }

            $duration =
                $resolution
                    ->getDurationMonths();

            $type =
                $resolution
                    ->getBenefitType();

            $headline =
                match ($type) {
                    'percentage_discount' =>
                        sprintf(
                            '%s %% de descuento',
                            number_format_i18n(
                                $resolution
                                    ->getBenefitValue(),
                                0
                            )
                        ),

                    'fixed_discount' =>
                        sprintf(
                            '%s %s de descuento',
                            number_format_i18n(
                                $resolution
                                    ->getBenefitValue(),
                                2
                            ),
                            $resolution
                                ->getCurrency()
                        ),

                    'special_price' =>
                        'Precio especial',

                    'free_months' =>
                        sprintf(
                            '%d %s gratis',
                            (int)
                            $resolution
                                ->getBenefitValue(),
                            (int)
                            $resolution
                                ->getBenefitValue()
                            === 1
                                ? 'mes'
                                : 'meses'
                        ),

                    default =>
                        'Oferta especial',
                };

            if (
                $duration !== null
                && $duration > 0
            ) {
                $description =
                    sprintf(
                        '%s %s al mes durante %d %s. Después volverás al precio normal de %s %s al mes.',
                        number_format_i18n(
                            $resolution
                                ->getFinalPrice(),
                            2
                        ),
                        $resolution
                            ->getCurrency(),
                        $duration,
                        $duration === 1
                            ? 'mes'
                            : 'meses',
                        number_format_i18n(
                            $resolution
                                ->getOriginalPrice(),
                            2
                        ),
                        $resolution
                            ->getCurrency()
                    );
            } else {
                $description =
                    sprintf(
                        'Precio promocional: %s %s al mes.',
                        number_format_i18n(
                            $resolution
                                ->getFinalPrice(),
                            2
                        ),
                        $resolution
                            ->getCurrency()
                    );
            }

            return [
                'offer_id' =>
                    $resolution
                        ->getOfferId(),

                'offer_name' =>
                    $resolution
                        ->getOfferName(),

                'headline' =>
                    $headline,

                'description' =>
                    $description,

                'original_price' =>
                    $resolution
                        ->getOriginalPrice(),

                'promotional_price' =>
                    $resolution
                        ->getFinalPrice(),

                'duration_months' =>
                    $duration,

                'subscription_id' =>
                    $subscription
                        ->getId(),
            ];
        } catch (Throwable) {
            return null;
        }
    }

    private function __construct()
    {
    }
}
