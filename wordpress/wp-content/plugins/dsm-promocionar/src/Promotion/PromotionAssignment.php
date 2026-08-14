<?php

declare(strict_types=1);

namespace DSM\Promocionar\Promotion;

use DateTimeImmutable;
use InvalidArgumentException;

if (!defined('ABSPATH')) {
    exit;
}

final class PromotionAssignment
{
    public function __construct(
        private readonly int $id,
        private readonly int $walletId,
        private readonly int $customerId,
        private readonly int $advertisementId,
        private readonly DateTimeImmutable $startedAt,
        private readonly ?DateTimeImmutable $stoppedAt,
        private readonly int $consumedSeconds,
        private readonly string $status,
        private readonly DateTimeImmutable $createdAt,
        private readonly DateTimeImmutable $updatedAt
    ) {
        if ($this->id <= 0) {
            throw new InvalidArgumentException(
                'El identificador de la asignación no es válido.'
            );
        }

        if ($this->walletId <= 0) {
            throw new InvalidArgumentException(
                'El identificador del saldo de promoción no es válido.'
            );
        }

        if ($this->customerId <= 0) {
            throw new InvalidArgumentException(
                'El identificador del cliente no es válido.'
            );
        }

        if ($this->advertisementId <= 0) {
            throw new InvalidArgumentException(
                'El identificador del anuncio no es válido.'
            );
        }

        if ($this->consumedSeconds < 0) {
            throw new InvalidArgumentException(
                'El tiempo consumido no puede ser negativo.'
            );
        }

        if (
            !PromotionAssignmentStatus::isValid(
                $this->status
            )
        ) {
            throw new InvalidArgumentException(
                'El estado de la asignación no es válido.'
            );
        }

        if (
            $this->status
            === PromotionAssignmentStatus::ACTIVE
            && $this->stoppedAt !== null
        ) {
            throw new InvalidArgumentException(
                'Una promoción activa no puede tener fecha de finalización.'
            );
        }

        if (
            $this->status
            !== PromotionAssignmentStatus::ACTIVE
            && $this->stoppedAt === null
        ) {
            throw new InvalidArgumentException(
                'Una promoción finalizada debe tener fecha de finalización.'
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

            walletId:
                (int) (
                    $data['wallet_id']
                    ?? 0
                ),

            customerId:
                (int) (
                    $data['customer_id']
                    ?? 0
                ),

            advertisementId:
                (int) (
                    $data['advertisement_id']
                    ?? 0
                ),

            startedAt:
                self::requiredDateTime(
                    $data['started_at']
                    ?? null
                ),

            stoppedAt:
                self::nullableDateTime(
                    $data['stopped_at']
                    ?? null
                ),

            consumedSeconds:
                (int) (
                    $data['consumed_seconds']
                    ?? 0
                ),

            status:
                (string) (
                    $data['status']
                    ?? ''
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

    public function getWalletId(): int
    {
        return $this->walletId;
    }

    public function getCustomerId(): int
    {
        return $this->customerId;
    }

    public function getAdvertisementId(): int
    {
        return $this->advertisementId;
    }

    public function getStartedAt(): DateTimeImmutable
    {
        return $this->startedAt;
    }

    public function getStoppedAt(): ?DateTimeImmutable
    {
        return $this->stoppedAt;
    }

    public function getConsumedSeconds(): int
    {
        return $this->consumedSeconds;
    }

    public function getStatus(): string
    {
        return $this->status;
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

    public function belongsToAdvertisement(
        int $advertisementId
    ): bool {
        return $this->advertisementId
            === $advertisementId;
    }

    public function isActive(): bool
    {
        return $this->status
            === PromotionAssignmentStatus::ACTIVE;
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

    private static function requiredDateTime(
        mixed $value
    ): DateTimeImmutable {
        if (
            $value === null
            || $value === ''
        ) {
            throw new InvalidArgumentException(
                'La fecha de la asignación no es válida.'
            );
        }

        return new DateTimeImmutable(
            (string) $value
        );
    }
}