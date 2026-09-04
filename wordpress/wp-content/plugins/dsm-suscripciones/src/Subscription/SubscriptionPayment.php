<?php

declare(strict_types=1);

namespace DSM\Suscripciones\Subscription;

use DateTimeImmutable;
use InvalidArgumentException;

if (!defined('ABSPATH')) {
    exit;
}

final class SubscriptionPayment
{
    public function __construct(
        private readonly int $id,
        private readonly int $subscriptionId,
        private readonly ?int $paymentId,
        private readonly ?string $provider,
        private readonly ?string $providerInvoiceReference,
        private readonly ?DateTimeImmutable $periodStart,
        private readonly ?DateTimeImmutable $periodEnd,
        private readonly ?float $amount,
        private readonly ?string $currency,
        private readonly DateTimeImmutable $createdAt
    ) {
        if ($this->id <= 0) {
            throw new InvalidArgumentException(
                'El identificador del cobro no es válido.'
            );
        }

        if ($this->subscriptionId <= 0) {
            throw new InvalidArgumentException(
                'La suscripción del cobro no es válida.'
            );
        }

        if (
            $this->amount !== null
            && $this->amount < 0
        ) {
            throw new InvalidArgumentException(
                'El importe del cobro no puede ser negativo.'
            );
        }

        if (
            $this->currency !== null
            && strlen($this->currency) !== 3
        ) {
            throw new InvalidArgumentException(
                'La moneda del cobro no es válida.'
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

            subscriptionId:
                (int) (
                    $data['subscription_id']
                    ?? 0
                ),

            paymentId:
                isset($data['payment_id'])
                && $data['payment_id'] !== null
                    ? (int) $data['payment_id']
                    : null,

            provider:
                self::optionalString(
                    $data['provider']
                    ?? null
                ),

            providerInvoiceReference:
                self::optionalString(
                    $data['provider_invoice_reference']
                    ?? null
                ),

            periodStart:
                self::optionalDateTime(
                    $data['period_start']
                    ?? null
                ),

            periodEnd:
                self::optionalDateTime(
                    $data['period_end']
                    ?? null
                ),

            amount:
                isset($data['amount'])
                && $data['amount'] !== null
                    ? (float) $data['amount']
                    : null,

            currency:
                self::optionalUppercaseString(
                    $data['currency']
                    ?? null
                ),

            createdAt:
                new DateTimeImmutable(
                    (string) (
                        $data['created_at']
                        ?? ''
                    )
                )
        );
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getSubscriptionId(): int
    {
        return $this->subscriptionId;
    }

    public function getPaymentId(): ?int
    {
        return $this->paymentId;
    }

    public function getProvider(): ?string
    {
        return $this->provider;
    }

    public function getProviderInvoiceReference(): ?string
    {
        return $this->providerInvoiceReference;
    }

    public function getPeriodStart(): ?DateTimeImmutable
    {
        return $this->periodStart;
    }

    public function getPeriodEnd(): ?DateTimeImmutable
    {
        return $this->periodEnd;
    }

    public function getAmount(): ?float
    {
        return $this->amount;
    }

    public function getCurrency(): ?string
    {
        return $this->currency;
    }

    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
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

    private static function optionalString(
        mixed $value
    ): ?string {
        if (
            $value === null
            || trim(
                (string) $value
            ) === ''
        ) {
            return null;
        }

        return trim(
            (string) $value
        );
    }

    private static function optionalUppercaseString(
        mixed $value
    ): ?string {
        $value =
            self::optionalString(
                $value
            );

        return $value !== null
            ? strtoupper(
                $value
            )
            : null;
    }
}
