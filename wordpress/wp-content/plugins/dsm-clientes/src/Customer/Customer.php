<?php

declare(strict_types=1);

namespace DSM\Clientes\Customer;

if (!defined('ABSPATH')) {
    exit;
}

final class Customer
{
    public function __construct(
        private readonly int $id,
        private readonly string $email,
        private readonly string $status,
        private readonly ?string $emailVerifiedAt,
        private readonly ?string $lastLoginAt,
        private readonly string $createdAt,
        private readonly string $updatedAt
    ) {
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function getEmailVerifiedAt(): ?string
    {
        return $this->emailVerifiedAt;
    }

    public function getLastLoginAt(): ?string
    {
        return $this->lastLoginAt;
    }

    public function getCreatedAt(): string
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): string
    {
        return $this->updatedAt;
    }

    public function isActive(): bool
    {
        return $this->status === CustomerStatus::ACTIVE;
    }

    public function isEmailVerified(): bool
    {
        return $this->emailVerifiedAt !== null;
    }
}