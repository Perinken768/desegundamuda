<?php

declare(strict_types=1);

namespace DSM\Mfa\Challenge;

if (!defined('ABSPATH')) {
    exit;
}

final class MfaChallenge
{
    public function __construct(
        private readonly string $token,
        private readonly string $context,
        private readonly string $identifier,
        private readonly string $email,
        private readonly string $codeHash,
        private readonly int $createdAt,
        private readonly int $expiresAt,
        private readonly int $attempts = 0,
        private readonly int $maxAttempts = 5,
        private readonly bool $verified = false,
        private readonly int $resendCount = 0,
        private readonly int $lastSentAt = 0
    ) {
    }

    public function getToken(): string
    {
        return $this->token;
    }

    public function getContext(): string
    {
        return $this->context;
    }

    public function getIdentifier(): string
    {
        return $this->identifier;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function getCodeHash(): string
    {
        return $this->codeHash;
    }

    public function getCreatedAt(): int
    {
        return $this->createdAt;
    }

    public function getExpiresAt(): int
    {
        return $this->expiresAt;
    }

    public function getAttempts(): int
    {
        return $this->attempts;
    }

    public function getMaxAttempts(): int
    {
        return $this->maxAttempts;
    }

    public function isVerified(): bool
    {
        return $this->verified;
    }

    public function getResendCount(): int
    {
        return $this->resendCount;
    }

    public function getLastSentAt(): int
    {
        return $this->lastSentAt;
    }

    public function isExpired(?int $now = null): bool
    {
        $now ??= time();

        return $now >= $this->expiresAt;
    }

    public function hasAttemptsRemaining(): bool
    {
        return $this->attempts < $this->maxAttempts;
    }

    public function withFailedAttempt(): self
    {
        return new self(
            token: $this->token,
            context: $this->context,
            identifier: $this->identifier,
            email: $this->email,
            codeHash: $this->codeHash,
            createdAt: $this->createdAt,
            expiresAt: $this->expiresAt,
            attempts: $this->attempts + 1,
            maxAttempts: $this->maxAttempts,
            verified: $this->verified,
            resendCount: $this->resendCount,
            lastSentAt: $this->lastSentAt
        );
    }

    public function withResentCode(
        string $codeHash,
        int $sentAt,
        int $expiresAt
    ): self {
        return new self(
            token: $this->token,
            context: $this->context,
            identifier: $this->identifier,
            email: $this->email,
            codeHash: $codeHash,
            createdAt: $this->createdAt,
            expiresAt: $expiresAt,
            attempts: 0,
            maxAttempts: $this->maxAttempts,
            verified: false,
            resendCount: $this->resendCount + 1,
            lastSentAt: $sentAt
        );
    }

    public function asVerified(): self
    {
        return new self(
            token: $this->token,
            context: $this->context,
            identifier: $this->identifier,
            email: $this->email,
            codeHash: $this->codeHash,
            createdAt: $this->createdAt,
            expiresAt: $this->expiresAt,
            attempts: $this->attempts,
            maxAttempts: $this->maxAttempts,
            verified: true,
            resendCount: $this->resendCount,
            lastSentAt: $this->lastSentAt
        );
    }

    public function toArray(): array
    {
        return [
            'token' =>
                $this->token,

            'context' =>
                $this->context,

            'identifier' =>
                $this->identifier,

            'email' =>
                $this->email,

            'code_hash' =>
                $this->codeHash,

            'created_at' =>
                $this->createdAt,

            'expires_at' =>
                $this->expiresAt,

            'attempts' =>
                $this->attempts,

            'max_attempts' =>
                $this->maxAttempts,

            'verified' =>
                $this->verified,

            'resend_count' =>
                $this->resendCount,

            'last_sent_at' =>
                $this->lastSentAt,
        ];
    }

    public static function fromArray(array $data): ?self
    {
        $token =
            isset($data['token'])
                ? (string) $data['token']
                : '';

        $context =
            isset($data['context'])
                ? (string) $data['context']
                : '';

        $identifier =
            isset($data['identifier'])
                ? (string) $data['identifier']
                : '';

        $email =
            isset($data['email'])
                ? (string) $data['email']
                : '';

        $codeHash =
            isset($data['code_hash'])
                ? (string) $data['code_hash']
                : '';

        if (
            $token === ''
            || $context === ''
            || $identifier === ''
            || $email === ''
            || $codeHash === ''
        ) {
            return null;
        }

        return new self(
            token: $token,
            context: $context,
            identifier: $identifier,
            email: $email,
            codeHash: $codeHash,
            createdAt:
                (int) ($data['created_at'] ?? 0),
            expiresAt:
                (int) ($data['expires_at'] ?? 0),
            attempts:
                (int) ($data['attempts'] ?? 0),
            maxAttempts:
                (int) ($data['max_attempts'] ?? 5),
            verified:
                (bool) ($data['verified'] ?? false),
            resendCount:
                (int) ($data['resend_count'] ?? 0),
            lastSentAt:
                (int) (
                    $data['last_sent_at']
                    ?? $data['created_at']
                    ?? 0
                )
        );
    }
}
