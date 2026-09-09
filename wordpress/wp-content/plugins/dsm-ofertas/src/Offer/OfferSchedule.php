<?php

declare(strict_types=1);

namespace DSM\Ofertas\Offer;

if (!defined('ABSPATH')) {
    exit;
}

final class OfferSchedule
{
    public function __construct(
        private readonly int $offerId,
        private readonly int $ruleId,
        private readonly int $planId,
        private readonly string $benefitType,
        private readonly float $benefitValue,
        private readonly float $originalPrice,
        private readonly float $promotionalPrice,
        private readonly float $discountAmount,
        private readonly string $currency,
        private readonly string $startsAt,
        private readonly ?string $endsAt,
        private readonly ?int $durationMonths
    ) {
    }

    public function getOfferId(): int
    {
        return $this->offerId;
    }

    public function getRuleId(): int
    {
        return $this->ruleId;
    }

    public function getPlanId(): int
    {
        return $this->planId;
    }

    public function getBenefitType(): string
    {
        return $this->benefitType;
    }

    public function getBenefitValue(): float
    {
        return $this->benefitValue;
    }

    public function getOriginalPrice(): float
    {
        return $this->originalPrice;
    }

    public function getPromotionalPrice(): float
    {
        return $this->promotionalPrice;
    }

    public function getDiscountAmount(): float
    {
        return $this->discountAmount;
    }

    public function getCurrency(): string
    {
        return $this->currency;
    }

    public function getStartsAt(): string
    {
        return $this->startsAt;
    }

    public function getEndsAt(): ?string
    {
        return $this->endsAt;
    }

    public function getDurationMonths(): ?int
    {
        return $this->durationMonths;
    }

    public function hasPromotionalEnd(): bool
    {
        return $this->endsAt !== null;
    }

    public function returnsToRegularPrice(): bool
    {
        return $this->promotionalPrice
            < $this->originalPrice;
    }
}
