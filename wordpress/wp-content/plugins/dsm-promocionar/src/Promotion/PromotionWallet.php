<?php

declare(strict_types=1);

namespace DSM\Promocionar\Promotion;

use DateTimeImmutable;
use InvalidArgumentException;

if (!defined('ABSPATH')) {
    exit;
}

final class PromotionWallet
{
    public function __construct(
        private readonly int $id,
        private readonly int $customerId,
        private readonly ?int $planId,
        private readonly int $purchasedSeconds,
        private readonly int $remainingSeconds,
        private readonly ?float $pricePaid,
        private readonly ?string $currency,
        private readonly string $status,
        private readonly ?string $sourceType,
        private readonly ?string $sourceReference,
        private readonly DateTimeImmutable $createdAt,
        private readonly DateTimeImmutable $updatedAt
    ) {
        if ($this->id <= 0) {
            throw new InvalidArgumentException(
                'El identificador del saldo de promoción no es válido.'
            );
        }

        if ($this->customerId <= 0) {
            throw new InvalidArgumentException(
                'El identificador del cliente no es válido.'
            );
        }

        if (
            $this->planId !== null
            && $this->planId <= 0
        ) {
            throw new InvalidArgumentException(
                'El identificador del plan no es válido.'
            );
        }

        if ($this->purchasedSeconds <= 0) {
            throw new InvalidArgumentException(
                'El tiempo adquirido debe ser mayor que cero.'
            );
        }

        if ($this->remainingSeconds < 0) {
            throw new InvalidArgumentException(
                'El tiempo restante no puede ser negativo.'
            );
        }

        if (
            $this->remainingSeconds
            > $this->purchasedSeconds
        ) {
            throw new InvalidArgumentException(
                'El tiempo restante no puede superar el tiempo adquirido.'
            );
        }

        if (
            $this->pricePaid !== null
            && $this->pricePaid < 0
        ) {
            throw new InvalidArgumentException(
                'El precio pagado no puede ser negativo.'
            );
        }

        if (
            $this->currency !== null
            && strlen($this->currency) !== 3
        ) {
            throw new InvalidArgumentException(
                'La moneda no es válida.'
            );
        }

        if (
            !PromotionWalletStatus::isValid(
                $this->status
            )
        ) {
            throw new InvalidArgumentException(
                'El estado del saldo de promoción no es válido.'
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

            customerId:
                (int) (
                    $data['customer_id']
                    ?? 0
                ),

            planId:
                self::nullableInt(
                    $data['plan_id']
                    ?? null
                ),

            purchasedSeconds:
                (int) (
                    $data['purchased_seconds']
                    ?? 0
                ),

            remainingSeconds:
                (int) (
                    $data['remaining_seconds']
                    ?? 0
                ),

            pricePaid:
                self::nullableFloat(
                    $data['price_paid']
                    ?? null
                ),

            currency:
                self::nullableCurrency(
                    $data['currency']
                    ?? null
                ),

            status:
                (string) (
                    $data['status']
                    ?? ''
                ),

            sourceType:
                self::nullableString(
                    $data['source_type']
                    ?? null
                ),

            sourceReference:
                self::nullableString(
                    $data['source_reference']
                    ?? null
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

    public function getCustomerId(): int
    {
        return $this->customerId;
    }

    public function getPlanId(): ?int
    {
        return $this->planId;
    }

    public function getPurchasedSeconds(): int
    {
        return $this->purchasedSeconds;
    }

    public function getRemainingSeconds(): int
    {
        return $this->remainingSeconds;
    }

    public function getPricePaid(): ?float
    {
        return $this->pricePaid;
    }

    public function getCurrency(): ?string
    {
        return $this->currency;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function getSourceType(): ?string
    {
        return $this->sourceType;
    }

    public function getSourceReference(): ?string
    {
        return $this->sourceReference;
    }

    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function belongsToCustomer(
        int $customerId
    ): bool {
        return $this->customerId
            === $customerId;
    }

    public function hasPlan(): bool
    {
        return $this->planId !== null;
    }

    public function hasCommercialSnapshot(): bool
    {
        return $this->planId !== null
            && $this->pricePaid !== null
            && $this->currency !== null;
    }

    public function hasRemainingTime(): bool
    {
        return $this->remainingSeconds > 0;
    }

    public function isAvailable(): bool
    {
        return $this->status
            === PromotionWalletStatus::AVAILABLE;
    }

    public function isInUse(): bool
    {
        return $this->status
            === PromotionWalletStatus::IN_USE;
    }

    public function isExhausted(): bool
    {
        return $this->status
            === PromotionWalletStatus::EXHAUSTED;
    }

    public function canBeAssigned(): bool
    {
        return $this->isAvailable()
            && $this->hasRemainingTime();
    }

    private static function nullableInt(
        mixed $value
    ): ?int {
        if (
            $value === null
            || $value === ''
        ) {
            return null;
        }

        $integer =
            (int) $value;

        return $integer > 0
            ? $integer
            : null;
    }

    private static function nullableFloat(
        mixed $value
    ): ?float {
        if (
            $value === null
            || $value === ''
        ) {
            return null;
        }

        return (float) $value;
    }

    private static function nullableString(
        mixed $value
    ): ?string {
        if (
            $value === null
            || trim((string) $value) === ''
        ) {
            return null;
        }

        return trim(
            (string) $value
        );
    }

    private static function nullableCurrency(
        mixed $value
    ): ?string {
        $currency =
            self::nullableString(
                $value
            );

        if ($currency === null) {
            return null;
        }

        return strtoupper(
            $currency
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
                'La fecha del saldo de promoción no es válida.'
            );
        }

        return new DateTimeImmutable(
            (string) $value
        );
    }
}