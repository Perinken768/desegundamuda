<?php

declare(strict_types=1);

namespace DSM\Cookies\Integration;

use DSM\Cookies\Consent\ConsentService;

if (!defined('ABSPATH')) {
    exit;
}

final class PublicConsentIntegration
{
    public static function register(): void
    {
        add_filter(
            'dsm_cookie_consent_allowed',
            [
                self::class,
                'filterAllowed',
            ],
            10,
            2
        );

        add_filter(
            'dsm_cookie_consent_state',
            [
                self::class,
                'filterState',
            ],
            10,
            1
        );
    }

    public static function filterAllowed(
        mixed $allowed,
        mixed $category
    ): bool {
        $service =
            new ConsentService();

        return $service->isAllowed(
            sanitize_key(
                (string) $category
            )
        );
    }

    /**
     * @return array<string, mixed>
     */
    public static function filterState(
        mixed $state
    ): array {
        $service =
            new ConsentService();

        $consent =
            $service->getCurrent();

        if ($consent === null) {
            return [
                'has_consent' =>
                    false,

                'necessary' =>
                    true,

                'preferences' =>
                    false,

                'analytics' =>
                    false,

                'marketing' =>
                    false,
            ];
        }

        return array_merge(
            [
                'has_consent' =>
                    true,
            ],
            $consent->toArray()
        );
    }

    private function __construct()
    {
    }
}
