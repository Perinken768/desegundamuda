<?php

declare(strict_types=1);

namespace DSM\Promocionar\Promotion;

use DateTimeImmutable;
use InvalidArgumentException;

if (!defined('ABSPATH')) {
    exit;
}

final class PromotionPlan
{
    public function __construct(
        private readonly int $id,
        private readonly string $code,
        private readonly string $name,
        private readonly int $durationSeconds,
        private readonly float $price,
        private readonly string $currency,
        private readonly bool $isActive,
        private readonly int $sortOrder,
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

        if ($this->durationSeconds <= 0) {
            throw new InvalidArgumentException(
                'La duración del plan debe ser mayor que cero.'
            );
        }

        if ($this->price < 0) {
            throw new InvalidArgumentException(
                'El precio del plan no puede ser negativo.'
            );
        }

        if (
            strlen($this->currency) !== 3
        ) {
            throw new InvalidArgumentException(
                'La moneda del plan no es válida.'
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
     */
    public static function fromArray(
        array $data
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

            durationSeconds:
                (int) (
                    $data['duration_seconds']
                    ?? 0
                ),

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

    public function getDurationSeconds(): int
    {
        return $this->durationSeconds;
    }

    public function getPrice(): float
    {
        return $this->price;
    }

    public function getCurrency(): string
    {
        return $this->currency;
    }

    public function isActive(): bool
    {
        return $this->isActive;
    }

    public function getSortOrder(): int
    {
        return $this->sortOrder;
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
