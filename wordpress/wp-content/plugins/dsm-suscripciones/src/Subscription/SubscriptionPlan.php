<?php

declare(strict_types=1);

namespace DSM\Suscripciones\Subscription;

use DateTimeImmutable;
use InvalidArgumentException;

if (!defined('ABSPATH')) {
    exit;
}

final class SubscriptionPlan
{
    /**
     * @param array<string, string> $features
     */
    public function __construct(
        private readonly int $id,
        private readonly string $code,
        private readonly string $name,
        private readonly ?string $description,
        private readonly float $price,
        private readonly string $currency,
        private readonly string $billingInterval,
        private readonly int $billingIntervalCount,
        private readonly bool $isFree,
        private readonly bool $isActive,
        private readonly int $sortOrder,
        private readonly array $features,
        private readonly DateTimeImmutable $createdAt,
        private readonly DateTimeImmutable $updatedAt
    ) {
        if ($this->id <= 0) {
            throw new InvalidArgumentException(
                'El identificador del plan no es válido.'
            );
        }

        if (trim($this->code) === '') {
            throw new InvalidArgumentException(
                'El código del plan es obligatorio.'
            );
        }

        if (trim($this->name) === '') {
            throw new InvalidArgumentException(
                'El nombre del plan es obligatorio.'
            );
        }

        if ($this->price < 0) {
            throw new InvalidArgumentException(
                'El precio del plan no puede ser negativo.'
            );
        }

        if (strlen($this->currency) !== 3) {
            throw new InvalidArgumentException(
                'La moneda del plan no es válida.'
            );
        }

        if ($this->billingIntervalCount <= 0) {
            throw new InvalidArgumentException(
                'El intervalo de facturación no es válido.'
            );
        }

        if ($this->sortOrder < 0) {
            throw new InvalidArgumentException(
                'El orden del plan no puede ser negativo.'
            );
        }
    }

    /**
     * @param array<string, mixed> $data
     * @param array<string, string> $features
     */
    public static function fromArray(
        array $data,
        array $features = []
    ): self {
        return new self(
            id:
                (int) (
                    $data['id']
                    ?? 0
                ),

            code:
                (string) (
                    $data['code']
                    ?? ''
                ),

            name:
                (string) (
                    $data['name']
                    ?? ''
                ),

            description:
                isset($data['description'])
                && $data['description'] !== ''
                    ? (string) $data['description']
                    : null,

            price:
                (float) (
                    $data['price']
                    ?? 0
                ),

            currency:
                strtoupper(
                    (string) (
                        $data['currency']
                        ?? ''
                    )
                ),

            billingInterval:
                (string) (
                    $data['billing_interval']
                    ?? ''
                ),

            billingIntervalCount:
                (int) (
                    $data['billing_interval_count']
                    ?? 1
                ),

            isFree:
                self::toBool(
                    $data['is_free']
                    ?? false
                ),

            isActive:
                self::toBool(
                    $data['is_active']
                    ?? false
                ),

            sortOrder:
                max(
                    0,
                    (int) (
                        $data['sort_order']
                        ?? 0
                    )
                ),

            features:
                $features,

            createdAt:
                self::requiredDateTime(
                    $data['created_at']
                    ?? null
                ),

            updatedAt:
                self::requiredDateTime(
                    $data['updated_at']
                    ?? null
                )
        );
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getCode(): string
    {
        return $this->code;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function getPrice(): float
    {
        return $this->price;
    }

    public function getCurrency(): string
    {
        return $this->currency;
    }

    public function getBillingInterval(): string
    {
        return $this->billingInterval;
    }

    public function getBillingIntervalCount(): int
    {
        return $this->billingIntervalCount;
    }

    public function isFree(): bool
    {
        return $this->isFree;
    }

    public function isActive(): bool
    {
        return $this->isActive;
    }

    public function getSortOrder(): int
    {
        return $this->sortOrder;
    }

    /**
     * @return array<string, string>
     */
    public function getFeatures(): array
    {
        return $this->features;
    }

    public function hasFeature(
        string $featureKey
    ): bool {
        return array_key_exists(
            $featureKey,
            $this->features
        );
    }

    public function getFeature(
        string $featureKey,
        ?string $default = null
    ): ?string {
        return $this->features[
            $featureKey
        ]
        ?? $default;
    }

    public function getFeatureAsInt(
        string $featureKey,
        int $default = 0
    ): int {
        $value =
            $this->getFeature(
                $featureKey
            );

        if (
            $value === null
            || !is_numeric($value)
        ) {
            return $default;
        }

        return (int) $value;
    }

    public function getFeatureAsBool(
        string $featureKey
    ): bool {
        $value =
            $this->getFeature(
                $featureKey
            );

        return in_array(
            $value,
            [
                '1',
                'true',
                'yes',
                'on',
            ],
            true
        );
    }

    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): DateTimeImmutable
    {
        return $this->updatedAt;
    }

    private static function toBool(
        mixed $value
    ): bool {
        return in_array(
            $value,
            [
                true,
                1,
                '1',
            ],
            true
        );
    }

    private static function requiredDateTime(
        mixed $value
    ): DateTimeImmutable {
        if (
            $value === null
            || $value === ''
        ) {
            throw new InvalidArgumentException(
                'La fecha del plan no es válida.'
            );
        }

        return new DateTimeImmutable(
            (string) $value
        );
    }
}