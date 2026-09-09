<?php

declare(strict_types=1);

namespace DSM\Ofertas\Application;

use DateInterval;
use DateTimeImmutable;
use DateTimeZone;
use DSM\Ofertas\Offer\OfferRedemptionRepository;
use DSM\Ofertas\Offer\OfferResolution;
use RuntimeException;

if (!defined('ABSPATH')) {
    exit;
}

final class RedeemOffer
{
    public function __construct(
        private readonly OfferRedemptionRepository $repository =
            new OfferRedemptionRepository()
    ) {
    }

    public function execute(
        int $customerId,
        OfferResolution $resolution,
        ?int $subscriptionId = null
    ): int {
        if ($customerId <= 0) {
            throw new RuntimeException(
                'El cliente indicado no es válido.'
            );
        }

        if (
            $subscriptionId !== null
            && $subscriptionId <= 0
        ) {
            throw new RuntimeException(
                'La suscripción indicada no es válida.'
            );
        }

        if (
            $this->repository
                ->existsForOfferCustomerPlan(
                    $resolution->getOfferId(),
                    $customerId,
                    $resolution->getPlanId()
                )
        ) {
            throw new RuntimeException(
                'Este cliente ya ha utilizado esta oferta para este plan.'
            );
        }

        $now =
            new DateTimeImmutable(
                'now',
                new DateTimeZone(
                    'UTC'
                )
            );

        $benefitStartsAt =
            $now;

        $benefitEndsAt =
            null;

        $durationMonths =
            $resolution
                ->getDurationMonths();

        if (
            $durationMonths !== null
            && $durationMonths > 0
        ) {
            $benefitEndsAt =
                $now->add(
                    new DateInterval(
                        'P'
                        . $durationMonths
                        . 'M'
                    )
                );
        }

        return $this->repository
            ->create(
                [
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

                    'subscription_id' =>
                        $subscriptionId,

                    'status' =>
                        'accepted',

                    'benefit_type' =>
                        $resolution
                            ->getBenefitType(),

                    'benefit_value' =>
                        $resolution
                            ->getBenefitValue(),

                    'duration_months' =>
                        $durationMonths,

                    'original_price' =>
                        $resolution
                            ->getOriginalPrice(),

                    'discount_amount' =>
                        $resolution
                            ->getDiscountAmount(),

                    'final_price' =>
                        $resolution
                            ->getFinalPrice(),

                    'currency' =>
                        $resolution
                            ->getCurrency(),

                    'benefit_starts_at' =>
                        $benefitStartsAt
                            ->format(
                                'Y-m-d H:i:s'
                            ),

                    'benefit_ends_at' =>
                        $benefitEndsAt
                            ?->format(
                                'Y-m-d H:i:s'
                            ),

                    'accepted_at' =>
                        $now->format(
                            'Y-m-d H:i:s'
                        ),
                ]
            );
    }
}
