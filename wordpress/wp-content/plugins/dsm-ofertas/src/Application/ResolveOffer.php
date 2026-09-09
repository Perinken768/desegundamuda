<?php

declare(strict_types=1);

namespace DSM\Ofertas\Application;

use DSM\Ofertas\Offer\OfferRedemptionRepository;
use DSM\Ofertas\Offer\OfferRepository;
use DSM\Ofertas\Offer\OfferResolution;
use DSM\Suscripciones\Subscription\SubscriptionPlanRepository;
use RuntimeException;

if (!defined('ABSPATH')) {
    exit;
}

final class ResolveOffer
{
    public function __construct(
        private readonly OfferRepository $offerRepository =
            new OfferRepository(),

        private readonly OfferRedemptionRepository $redemptionRepository =
            new OfferRedemptionRepository(),

        private readonly SubscriptionPlanRepository $planRepository =
            new SubscriptionPlanRepository(),

        private readonly OfferPriceCalculator $priceCalculator =
            new OfferPriceCalculator()
    ) {
    }

    public function execute(
        int $customerId,
        int $planId,
        string $context =
            'new_subscription',
        ?string $atUtc = null
    ): ?OfferResolution {
        if ($customerId <= 0) {
            throw new RuntimeException(
                'El cliente indicado no es válido.'
            );
        }

        if ($planId <= 0) {
            throw new RuntimeException(
                'El plan indicado no es válido.'
            );
        }

        $context =
            sanitize_key(
                $context
            );

        if (
            !in_array(
                $context,
                [
                    'new_subscription',
                    'renewal',
                    'retention',
                ],
                true
            )
        ) {
            throw new RuntimeException(
                'El contexto comercial no es válido.'
            );
        }

        $plan =
            $this->planRepository
                ->findById(
                    $planId
                );

        if ($plan === null) {
            throw new RuntimeException(
                'No se encontró el plan indicado.'
            );
        }

        $atUtc =
            $this->normalizeResolutionDate(
                $atUtc
            );

        $rules =
            $this->offerRepository
                ->findApplicableRules(
                    $customerId,
                    $planId,
                    $context,
                    $atUtc
                );

        foreach ($rules as $rule) {
            $offerId =
                (int)
                $rule->offer_id;

            /*
             * Una misma oferta no puede utilizarse
             * dos veces para el mismo cliente y plan.
             */
            if (
                $this->redemptionRepository
                    ->existsForOfferCustomerPlan(
                        $offerId,
                        $customerId,
                        $planId
                    )
            ) {
                continue;
            }

            $benefitType =
                (string)
                $rule->benefit_type;

            $benefitValue =
                round(
                    (float)
                    $rule->benefit_value,
                    2
                );

            $durationMonths =
                $rule->duration_months !== null
                    ? max(
                        1,
                        (int)
                        $rule->duration_months
                    )
                    : null;

            $pricing =
                $this->priceCalculator
                    ->calculate(
                        (float)
                        $plan->getPrice(),

                        $benefitType,

                        $benefitValue
                    );

            return new OfferResolution(
                offerId:
                    $offerId,

                ruleId:
                    (int)
                    $rule->rule_id,

                planId:
                    $planId,

                offerCode:
                    (string)
                    $rule->offer_code,

                offerName:
                    (string)
                    $rule->offer_name,

                scope:
                    (string)
                    $rule->scope,

                benefitType:
                    $benefitType,

                benefitValue:
                    $benefitValue,

                durationMonths:
                    $durationMonths,

                originalPrice:
                    $pricing[
                        'original_price'
                    ],

                discountAmount:
                    $pricing[
                        'discount_amount'
                    ],

                finalPrice:
                    $pricing[
                        'final_price'
                    ],

                currency:
                    $plan->getCurrency()
            );
        }

        return null;
    }

    private function normalizeResolutionDate(
        ?string $atUtc
    ): string {
        if ($atUtc === null) {
            return current_time(
                'mysql',
                true
            );
        }

        $atUtc =
            trim(
                $atUtc
            );

        if ($atUtc === '') {
            throw new RuntimeException(
                'La fecha de resolución no es válida.'
            );
        }

        $timestamp =
            strtotime(
                $atUtc
                . ' UTC'
            );

        if ($timestamp === false) {
            throw new RuntimeException(
                'La fecha de resolución no es válida.'
            );
        }

        return gmdate(
            'Y-m-d H:i:s',
            $timestamp
        );
    }
}
