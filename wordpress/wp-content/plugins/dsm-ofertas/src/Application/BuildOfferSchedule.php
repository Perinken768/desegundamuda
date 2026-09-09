<?php

declare(strict_types=1);

namespace DSM\Ofertas\Application;

use DateInterval;
use DateTimeImmutable;
use DateTimeZone;
use DSM\Ofertas\Offer\OfferResolution;
use DSM\Ofertas\Offer\OfferSchedule;
use RuntimeException;

if (!defined('ABSPATH')) {
    exit;
}

final class BuildOfferSchedule
{
    public function execute(
        OfferResolution $resolution,
        ?string $startsAtUtc = null
    ): OfferSchedule {
        $start =
            $this->resolveStart(
                $startsAtUtc
            );

        $durationMonths =
            $resolution
                ->getDurationMonths();

        $end =
            null;

        if (
            $durationMonths !== null
            && $durationMonths > 0
        ) {
            $end =
                $start->add(
                    new DateInterval(
                        'P'
                        . $durationMonths
                        . 'M'
                    )
                );
        }

        return new OfferSchedule(
            offerId:
                $resolution->getOfferId(),

            ruleId:
                $resolution->getRuleId(),

            planId:
                $resolution->getPlanId(),

            benefitType:
                $resolution->getBenefitType(),

            benefitValue:
                $resolution->getBenefitValue(),

            originalPrice:
                $resolution->getOriginalPrice(),

            promotionalPrice:
                $resolution->getFinalPrice(),

            discountAmount:
                $resolution->getDiscountAmount(),

            currency:
                $resolution->getCurrency(),

            startsAt:
                $start->format(
                    'Y-m-d H:i:s'
                ),

            endsAt:
                $end?->format(
                    'Y-m-d H:i:s'
                ),

            durationMonths:
                $durationMonths
        );
    }

    private function resolveStart(
        ?string $startsAtUtc
    ): DateTimeImmutable {
        $timezone =
            new DateTimeZone(
                'UTC'
            );

        if ($startsAtUtc === null) {
            return new DateTimeImmutable(
                'now',
                $timezone
            );
        }

        $startsAtUtc =
            trim(
                $startsAtUtc
            );

        if ($startsAtUtc === '') {
            throw new RuntimeException(
                'La fecha de inicio del beneficio no es válida.'
            );
        }

        $date =
            DateTimeImmutable::createFromFormat(
                'Y-m-d H:i:s',
                $startsAtUtc,
                $timezone
            );

        if ($date === false) {
            throw new RuntimeException(
                'La fecha de inicio del beneficio no es válida.'
            );
        }

        return $date;
    }
}
