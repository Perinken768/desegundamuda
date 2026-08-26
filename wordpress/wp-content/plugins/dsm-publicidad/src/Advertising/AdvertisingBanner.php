<?php

declare(strict_types=1);

namespace DSM\Publicidad\Advertising;

use DateTimeImmutable;
use InvalidArgumentException;

if (!defined('ABSPATH')) {
    exit;
}

final class AdvertisingBanner
{
    public function __construct(
        private readonly int $id,
        private readonly ?int $customerId,
        private readonly string $title,
        private readonly int $imageAttachmentId,
        private readonly string $targetUrl,
        private readonly ?int $areaId,
        private readonly int $priority,
        private readonly string $status,
        private readonly ?DateTimeImmutable $startsAt,
        private readonly ?DateTimeImmutable $endsAt,
        private readonly DateTimeImmutable $createdAt,
        private readonly DateTimeImmutable $updatedAt
    ) {
        if ($this->id <= 0) {
            throw new InvalidArgumentException(
                'El identificador del banner no es válido.'
            );
        }

        if ($this->title === '') {
            throw new InvalidArgumentException(
                'El título del banner es obligatorio.'
            );
        }

        if ($this->imageAttachmentId <= 0) {
            throw new InvalidArgumentException(
                'La imagen del banner es obligatoria.'
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
                isset($data['customer_id'])
                && $data['customer_id'] !== null
                && (int) $data['customer_id'] > 0
                    ? (int) $data['customer_id']
                    : null,

            title:
                trim(
                    (string) (
                        $data['title']
                        ?? ''
                    )
                ),

            imageAttachmentId:
                (int) (
                    $data['image_attachment_id']
                    ?? 0
                ),

            targetUrl:
                trim(
                    (string) (
                        $data['target_url']
                        ?? ''
                    )
                ),

            areaId:
                isset($data['area_id'])
                && $data['area_id'] !== null
                    ? (int) $data['area_id']
                    : null,

            priority:
                (int) (
                    $data['priority']
                    ?? 0
                ),

            status:
                sanitize_key(
                    (string) (
                        $data['status']
                        ?? ''
                    )
                ),

            startsAt:
                self::nullableDateTime(
                    $data['starts_at']
                    ?? null
                ),

            endsAt:
                self::nullableDateTime(
                    $data['ends_at']
                    ?? null
                ),

            createdAt:
                new DateTimeImmutable(
                    (string) $data[
                        'created_at'
                    ]
                ),

            updatedAt:
                new DateTimeImmutable(
                    (string) $data[
                        'updated_at'
                    ]
                )
        );
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getCustomerId(): ?int
    {
        return $this->customerId;
    }

    public function belongsToCustomer(
        int $customerId
    ): bool {
        return $customerId > 0
            && $this->customerId === $customerId;
    }

    public function isAdministrative(): bool
    {
        return $this->customerId === null;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function getImageAttachmentId(): int
    {
        return $this->imageAttachmentId;
    }

    public function getTargetUrl(): string
    {
        return $this->targetUrl;
    }

    public function getAreaId(): ?int
    {
        return $this->areaId;
    }

    public function getPriority(): int
    {
        return $this->priority;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function getStartsAt(): ?DateTimeImmutable
    {
        return $this->startsAt;
    }

    public function getEndsAt(): ?DateTimeImmutable
    {
        return $this->endsAt;
    }

    private static function nullableDateTime(
        mixed $value
    ): ?DateTimeImmutable {
        if (
            $value === null
            || trim(
                (string) $value
            ) === ''
        ) {
            return null;
        }

        return new DateTimeImmutable(
            (string) $value
        );
    }
}
