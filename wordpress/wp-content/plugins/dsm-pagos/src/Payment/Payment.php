<?php

declare(strict_types=1);

namespace DSM\Pagos\Payment;

use DateTimeImmutable;
use InvalidArgumentException;

if (!defined('ABSPATH')) {
    exit;
}

final class Payment
{
    public function __construct(
        private readonly int $id,
        private readonly int $customerId,
        private readonly string $purpose,
        private readonly float $amount,
        private readonly string $currency,
        private readonly string $status,
        private readonly ?string $provider,
        private readonly ?string $providerReference,
        private readonly ?string $sourceType,
        private readonly ?int $sourceId,
        private readonly ?string $sourceReference,
        private readonly DateTimeImmutable $createdAt,
        private readonly DateTimeImmutable $updatedAt,
        private readonly ?DateTimeImmutable $paidAt,
        private readonly ?DateTimeImmutable $failedAt,
        private readonly ?DateTimeImmutable $cancelledAt
    ) {
        if ($this->id <= 0) {
            throw new InvalidArgumentException(
                'El identificador del pago no es válido.'
            );
        }

        if ($this->customerId <= 0) {
            throw new InvalidArgumentException(
                'El identificador del cliente no es válido.'
            );
        }

        if (trim($this->purpose) === '') {
            throw new InvalidArgumentException(
                'La finalidad del pago no es válida.'
            );
        }

        if ($this->amount < 0) {
            throw new InvalidArgumentException(
                'El importe del pago no puede ser negativo.'
            );
        }

        if (
            strlen($this->currency) !== 3
            || !ctype_alpha($this->currency)
        ) {
            throw new InvalidArgumentException(
                'La moneda del pago no es válida.'
            );
        }

        if (
            !PaymentStatus::isValid(
                $this->status
            )
        ) {
            throw new InvalidArgumentException(
                'El estado del pago no es válido.'
            );
        }

        if (
            $this->sourceId !== null
            && $this->sourceId <= 0
        ) {
            throw new InvalidArgumentException(
                'El identificador de origen del pago no es válido.'
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

            purpose:
                (string) (
                    $data['purpose']
                    ?? ''
                ),

            amount:
                (float) (
                    $data['amount']
                    ?? 0
                ),

            currency:
                strtoupper(
                    (string) (
                        $data['currency']
                        ?? ''
                    )
                ),

            status:
                (string) (
                    $data['status']
                    ?? ''
                ),

            provider:
                self::nullableString(
                    $data['provider']
                    ?? null
                ),

            providerReference:
                self::nullableString(
                    $data['provider_reference']
                    ?? null
                ),

            sourceType:
                self::nullableString(
                    $data['source_type']
                    ?? null
                ),

            sourceId:
                self::nullableInt(
                    $data['source_id']
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
                ),

            paidAt:
                self::nullableDateTime(
                    $data['paid_at']
                    ?? null
                ),

            failedAt:
                self::nullableDateTime(
                    $data['failed_at']
                    ?? null
                ),

            cancelledAt:
                self::nullableDateTime(
                    $data['cancelled_at']
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

    public function getPurpose(): string
    {
        return $this->purpose;
    }

    public function getAmount(): float
    {
        return $this->amount;
    }

    public function getCurrency(): string
    {
        return $this->currency;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function getProvider(): ?string
    {
        return $this->provider;
    }

    public function getProviderReference(): ?string
    {
        return $this->providerReference;
    }

    public function getSourceType(): ?string
    {
        return $this->sourceType;
    }

    public function getSourceId(): ?int
    {
        return $this->sourceId;
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

    public function getPaidAt(): ?DateTimeImmutable
    {
        return $this->paidAt;
    }

    public function getFailedAt(): ?DateTimeImmutable
    {
        return $this->failedAt;
    }

    public function getCancelledAt(): ?DateTimeImmutable
    {
        return $this->cancelledAt;
    }

    public function belongsToCustomer(
        int $customerId
    ): bool {
        return $this->customerId
            === $customerId;
    }

    public function isPending(): bool
    {
        return $this->status
            === PaymentStatus::PENDING;
    }

    public function isPaid(): bool
    {
        return $this->status
            === PaymentStatus::PAID;
    }

    public function isFailed(): bool
    {
        return $this->status
            === PaymentStatus::FAILED;
    }

    public function isCancelled(): bool
    {
        return $this->status
            === PaymentStatus::CANCELLED;
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

    private static function nullableInt(
        mixed $value
    ): ?int {
        if (
            $value === null
            || $value === ''
        ) {
            return null;
        }

        $value =
            (int) $value;

        return $value > 0
            ? $value
            : null;
    }

    private static function requiredDateTime(
        mixed $value
    ): DateTimeImmutable {
        if (
            $value === null
            || $value === ''
        ) {
            throw new InvalidArgumentException(
                'La fecha del pago no es válida.'
            );
        }

        return new DateTimeImmutable(
            (string) $value
        );
    }

    private static function nullableDateTime(
        mixed $value
    ): ?DateTimeImmutable {
        if (
            $value === null
            || $value === ''
        ) {
            return null;
        }

        return new DateTimeImmutable(
            (string) $value
        );
    }
}
