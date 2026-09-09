<?php

declare(strict_types=1);

namespace DSM\Ofertas\Offer;

if (!defined('ABSPATH')) {
    exit;
}

final class OfferResolution
{
    public function __construct(
        private readonly int $offerId,
        private readonly int $ruleId,
        private readonly int $planId,
        private readonly string $offerCode,
        private readonly string $offerName,
        private readonly string $scope,
        private readonly string $benefitType,
        private readonly float $benefitValue,
        private readonly ?int $durationMonths,
        private readonly float $originalPrice,
        private readonly float $discountAmount,
        private readonly float $finalPrice,
        private readonly string $currency
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

    public function getOfferCode(): string
    {
        return $this->offerCode;
    }

    public function getOfferName(): string
    {
        return $this->offerName;
    }

    public function getScope(): string
    {
        return $this->scope;
    }

    public function getBenefitType(): string
    {
        return $this->benefitType;
    }

    public function getBenefitValue(): float
    {
        return $this->benefitValue;
    }

    public function getDurationMonths(): ?int
    {
        return $this->durationMonths;
    }

    public function getOriginalPrice(): float
    {
        return $this->originalPrice;
    }

    public function getDiscountAmount(): float
    {
        return $this->discountAmount;
    }

    public function getFinalPrice(): float
    {
        return $this->finalPrice;
    }

    public function getCurrency(): string
    {
        return $this->currency;
    }

    public function isFreePeriod(): bool
    {
        return $this->benefitType
            === 'free_months';
    }
}
