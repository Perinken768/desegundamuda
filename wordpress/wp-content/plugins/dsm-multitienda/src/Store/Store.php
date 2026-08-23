<?php

declare(strict_types=1);

namespace DSM\Multitienda\Store;

use DateTimeImmutable;
use DateTimeZone;
use RuntimeException;

if (!defined('ABSPATH')) {
    exit;
}

final class Store
{
    public function __construct(
        private readonly int $id,
        private readonly int $customerId,
        private readonly string $slug,
        private readonly string $name,
        private readonly ?string $description,
        private readonly ?int $logoAttachmentId,
        private readonly ?string $island,
        private readonly ?string $locationText,
        private readonly string $status,
        private readonly DateTimeImmutable $createdAt,
        private readonly DateTimeImmutable $updatedAt
    ) {
        if ($id <= 0) {
            throw new RuntimeException(
                'El identificador de la tienda no es válido.'
            );
        }

        if ($customerId <= 0) {
            throw new RuntimeException(
                'El identificador del cliente no es válido.'
            );
        }

        if ($slug === '') {
            throw new RuntimeException(
                'El slug de la tienda no es válido.'
            );
        }

        if ($name === '') {
            throw new RuntimeException(
                'El nombre de la tienda no es válido.'
            );
        }

        if (!StoreStatus::isValid($status)) {
            throw new RuntimeException(
                'El estado de la tienda no es válido.'
            );
        }
    }

    /**
     * @param array<string, mixed> $row
     */
    public static function fromArray(
        array $row
    ): self {
        $utc =
            new DateTimeZone(
                'UTC'
            );

        return new self(
            id:
                (int) $row['id'],

            customerId:
                (int) $row['customer_id'],

            slug:
                (string) $row['slug'],

            name:
                (string) $row['name'],

            description:
                $row['description'] !== null
                    ? (string) $row['description']
                    : null,

            logoAttachmentId:
                $row['logo_attachment_id'] !== null
                    ? (int) $row['logo_attachment_id']
                    : null,

            island:
                $row['island'] !== null
                    ? (string) $row['island']
                    : null,

            locationText:
                $row['location_text'] !== null
                    ? (string) $row['location_text']
                    : null,

            status:
                (string) $row['status'],

            createdAt:
                new DateTimeImmutable(
                    (string) $row['created_at'],
                    $utc
                ),

            updatedAt:
                new DateTimeImmutable(
                    (string) $row['updated_at'],
                    $utc
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

    public function getSlug(): string
    {
        return $this->slug;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function getLogoAttachmentId(): ?int
    {
        return $this->logoAttachmentId;
    }

    public function getIsland(): ?string
    {
        return $this->island;
    }

    public function getLocationText(): ?string
    {
        return $this->locationText;
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

    public function isDraft(): bool
    {
        return $this->status
            === StoreStatus::DRAFT;
    }

    public function isActive(): bool
    {
        return $this->status
            === StoreStatus::ACTIVE;
    }

    public function isHidden(): bool
    {
        return $this->status
            === StoreStatus::HIDDEN;
    }

    public function isSuspended(): bool
    {
        return $this->status
            === StoreStatus::SUSPENDED;
    }
}
