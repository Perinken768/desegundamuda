<?php

declare(strict_types=1);

namespace DSM\Suscripciones\Subscription;

use DateTimeImmutable;
use InvalidArgumentException;

if (!defined('ABSPATH')) {
    exit;
}

final class Subscription
{
    public function __construct(
        private readonly int $id,
        private readonly int $customerId,
        private readonly int $planId,
        private readonly ?int $paymentId,
        private readonly string $status,
        private readonly DateTimeImmutable $startsAt,
        private readonly ?DateTimeImmutable $endsAt,
        private readonly ?DateTimeImmutable $cancelledAt,
        private readonly ?float $pricePaid,
        private readonly ?string $currency,
        private readonly ?string $sourceType,
        private readonly ?string $sourceReference,
        private readonly bool $autoRenew,
        private readonly DateTimeImmutable $createdAt,
        private readonly DateTimeImmutable $updatedAt
    ) {
        if ($this->id <= 0) {
            throw new InvalidArgumentException(
                'El identificador de la suscripción no es válido.'
            );
        }

        if ($this->customerId <= 0) {
            throw new InvalidArgumentException(
                'El cliente de la suscripción no es válido.'
            );
        }

        if ($this->planId <= 0) {
            throw new InvalidArgumentException(
                'El plan de la suscripción no es válido.'
            );
        }

        if (
            !SubscriptionStatus::isValid(
                $this->status
            )
        ) {
            throw new InvalidArgumentException(
                'El estado de la suscripción no es válido.'
            );
        }

        if (
            $this->pricePaid !== null
            && $this->pricePaid < 0
        ) {
            throw new InvalidArgumentException(
                'El importe pagado no puede ser negativo.'
            );
        }

        if (
            $this->currency !== null
            && strlen($this->currency) !== 3
        ) {
            throw new InvalidArgumentException(
                'La moneda de la suscripción no es válida.'
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
                (int) (
                    $data['plan_id']
                    ?? 0
                ),

            paymentId:
                isset($data['payment_id'])
                && $data['payment_id'] !== null
                    ? (int) $data['payment_id']
                    : null,

            status:
                (string) (
                    $data['status']
                    ?? ''
                ),

            startsAt:
                self::requiredDateTime(
                    $data['starts_at']
                    ?? null
                ),

            endsAt:
                self::optionalDateTime(
                    $data['ends_at']
                    ?? null
                ),

            cancelledAt:
                self::optionalDateTime(
                    $data['cancelled_at']
                    ?? null
                ),

            pricePaid:
                isset($data['price_paid'])
                && $data['price_paid'] !== null
                    ? (float) $data['price_paid']
                    : null,

            currency:
                isset($data['currency'])
                && $data['currency'] !== null
                    ? strtoupper(
                        (string) $data['currency']
                    )
                    : null,

            sourceType:
                isset($data['source_type'])
                && $data['source_type'] !== null
                    ? (string) $data['source_type']
                    : null,

            sourceReference:
                isset($data['source_reference'])
                && $data['source_reference'] !== null
                    ? (string) $data['source_reference']
                    : null,

            autoRenew:
                self::toBool(
                    $data['auto_renew']
                    ?? false
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

    public function getPlanId(): int
    {
        return $this->planId;
    }

    public function getPaymentId(): ?int
    {
        return $this->paymentId;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function getStartsAt(): DateTimeImmutable
    {
        return $this->startsAt;
    }

    public function getEndsAt(): ?DateTimeImmutable
    {
        return $this->endsAt;
    }

    public function getCancelledAt(): ?DateTimeImmutable
    {
        return $this->cancelledAt;
    }

    public function getPricePaid(): ?float
    {
        return $this->pricePaid;
    }

    public function getCurrency(): ?string
    {
        return $this->currency;
    }

    public function getSourceType(): ?string
    {
        return $this->sourceType;
    }

    public function getSourceReference(): ?string
    {
        return $this->sourceReference;
    }

    public function isAutoRenew(): bool
    {
        return $this->autoRenew;
    }

    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function isActive(): bool
    {
        if (
            $this->status
            !== SubscriptionStatus::ACTIVE
        ) {
            return false;
        }

        $now =
            new DateTimeImmutable(
                'now',
                wp_timezone()
            );

        if ($this->startsAt > $now) {
            return false;
        }

        if (
            $this->endsAt !== null
            && $this->endsAt <= $now
        ) {
            return false;
        }

        return true;
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
                'La fecha de la suscripción no es válida.'
            );
        }

        return new DateTimeImmutable(
            (string) $value
        );
    }

    private static function optionalDateTime(
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