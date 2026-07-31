<?php

declare(strict_types=1);

namespace DSM\Clientes\Authentication;

if (!defined('ABSPATH')) {
    exit;
}

final class CustomerSession
{
    public function __construct(
        private readonly int $id,
        private readonly int $customerId,
        private readonly string $tokenHash,
        private readonly ?string $ipAddress,
        private readonly ?string $userAgent,
        private readonly string $createdAt,
        private readonly string $lastActivityAt,
        private readonly string $expiresAt,
        private readonly ?string $revokedAt
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

    public function getTokenHash(): string
    {
        return $this->tokenHash;
    }

    public function getIpAddress(): ?string
    {
        return $this->ipAddress;
    }

    public function getUserAgent(): ?string
    {
        return $this->userAgent;
    }

    public function getCreatedAt(): string
    {
        return $this->createdAt;
    }

    public function getLastActivityAt(): string
    {
        return $this->lastActivityAt;
    }

    public function getExpiresAt(): string
    {
        return $this->expiresAt;
    }

    public function getRevokedAt(): ?string
    {
        return $this->revokedAt;
    }

    public function isRevoked(): bool
    {
        return $this->revokedAt !== null;
    }

    public function isExpired(): bool
    {
        return strtotime($this->expiresAt) <= time();
    }

    public function isValid(): bool
    {
        return !$this->isRevoked() && !$this->isExpired();
    }
}