<?php

declare(strict_types=1);

namespace DSM\Clientes\Profile;

if (!defined('ABSPATH')) {
    exit;
}

final class CustomerProfile
{
    public function __construct(
        private readonly int $id,
        private readonly int $customerId,
        private readonly string $displayName,
        private readonly ?string $phone,
        private readonly ?string $whatsappPhone,
        private readonly ?int $avatarAttachmentId,
        private readonly ?string $bio,
        private readonly ?int $islandId,
        private readonly ?int $municipalityId,
        private readonly string $createdAt,
        private readonly string $updatedAt
    ) {
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getCustomerId(): int
    {
        return $this->customerId;
    }

    public function getDisplayName(): string
    {
        return $this->displayName;
    }

    public function getPhone(): ?string
    {
        return $this->phone;
    }

    public function getWhatsappPhone(): ?string
    {
        return $this->whatsappPhone;
    }

    public function getAvatarAttachmentId(): ?int
    {
        return $this->avatarAttachmentId;
    }

    public function getBio(): ?string
    {
        return $this->bio;
    }

    public function getIslandId(): ?int
    {
        return $this->islandId;
    }

    public function getMunicipalityId(): ?int
    {
        return $this->municipalityId;
    }

    public function getCreatedAt(): string
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): string
    {
        return $this->updatedAt;
    }
}