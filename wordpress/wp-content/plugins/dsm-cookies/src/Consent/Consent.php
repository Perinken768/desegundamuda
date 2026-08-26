<?php

declare(strict_types=1);

namespace DSM\Cookies\Consent;

if (!defined('ABSPATH')) {
    exit;
}

final class Consent
{
    public const VERSION = 1;

    public function __construct(
        private readonly bool $preferences,
        private readonly bool $analytics,
        private readonly bool $marketing,
        private readonly int $updatedAt
    ) {
    }

    public static function acceptAll(): self
    {
        return new self(
            preferences:
                true,

            analytics:
                true,

            marketing:
                true,

            updatedAt:
                time()
        );
    }

    public static function rejectOptional(): self
    {
        return new self(
            preferences:
                false,

            analytics:
                false,

            marketing:
                false,

            updatedAt:
                time()
        );
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(
        array $data
    ): self {
        return new self(
            preferences:
                !empty(
                    $data[
                        'preferences'
                    ]
                ),

            analytics:
                !empty(
                    $data[
                        'analytics'
                    ]
                ),

            marketing:
                !empty(
                    $data[
                        'marketing'
                    ]
                ),

            updatedAt:
                max(
                    0,
                    (int) (
                        $data[
                            'updated_at'
                        ]
                        ?? time()
                    )
                )
        );
    }

    public function allows(
        string $category
    ): bool {
        return match (
            sanitize_key(
                $category
            )
        ) {
            'necessary' =>
                true,

            'preferences' =>
                $this->preferences,

            'analytics' =>
                $this->analytics,

            'marketing' =>
                $this->marketing,

            default =>
                false,
        };
    }

    public function allowsPreferences(): bool
    {
        return $this->preferences;
    }

    public function allowsAnalytics(): bool
    {
        return $this->analytics;
    }

    public function allowsMarketing(): bool
    {
        return $this->marketing;
    }

    public function getUpdatedAt(): int
    {
        return $this->updatedAt;
    }

    /**
     * @return array<string, int|bool>
     */
    public function toArray(): array
    {
        return [
            'version' =>
                self::VERSION,

            'necessary' =>
                true,

            'preferences' =>
                $this->preferences,

            'analytics' =>
                $this->analytics,

            'marketing' =>
                $this->marketing,

            'updated_at' =>
                $this->updatedAt,
        ];
    }
}
