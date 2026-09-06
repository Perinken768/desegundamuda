<?php

declare(strict_types=1);

namespace DSM\Directos\Application;

use DSM\Directos\Direct\DirectRepository;
use RuntimeException;

if (!defined('ABSPATH')) {
    exit;
}

final class SaveDirectConfiguration
{
    private const ALLOWED_PLATFORMS = [
        'instagram',
        'tiktok',
        'youtube',
        'twitch',
        'facebook',
        'other',
    ];

    public function __construct(
        private readonly DirectAccessService $accessService =
            new DirectAccessService(),

        private readonly DirectRepository $repository =
            new DirectRepository()
    ) {
    }

    public function execute(
        int $customerId,
        string $title,
        string $description,
        string $platform,
        string $platformUrl
    ): int {
        $this->accessService
            ->assertHasAccess(
                $customerId
            );

        $platform =
            sanitize_key(
                $platform
            );

        if (
            !in_array(
                $platform,
                self::ALLOWED_PLATFORMS,
                true
            )
        ) {
            throw new RuntimeException(
                'La plataforma seleccionada no es válida.'
            );
        }

        return $this->repository
            ->saveConfiguration(
                customerId:
                    $customerId,

                title:
                    $title,

                description:
                    $description,

                platform:
                    $platform,

                platformUrl:
                    $platformUrl
            );
    }
}
