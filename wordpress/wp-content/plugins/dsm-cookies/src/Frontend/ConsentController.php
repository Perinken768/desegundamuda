<?php

declare(strict_types=1);

namespace DSM\Cookies\Frontend;

use DSM\Cookies\Consent\ConsentService;

if (!defined('ABSPATH')) {
    exit;
}

final class ConsentController
{
    public const ACTION =
        'dsm_cookie_consent';

    public const NONCE_ACTION =
        'dsm_cookie_consent';

    public const NONCE_FIELD =
        'dsm_cookie_nonce';

    public static function register(): void
    {
        add_action(
            'admin_post_nopriv_'
            . self::ACTION,
            [
                self::class,
                'handle',
            ]
        );

        add_action(
            'admin_post_'
            . self::ACTION,
            [
                self::class,
                'handle',
            ]
        );
    }

    public static function handle(): never
    {
        check_admin_referer(
            self::NONCE_ACTION,
            self::NONCE_FIELD
        );

        $operation =
            isset(
                $_POST[
                    'consent_action'
                ]
            )
                ? sanitize_key(
                    wp_unslash(
                        (string) $_POST[
                            'consent_action'
                        ]
                    )
                )
                : '';

        $service =
            new ConsentService();

        switch ($operation) {
            case 'accept_all':
                $service->acceptAll();
                break;

            case 'reject_optional':
                $service->rejectOptional();
                break;

            case 'save_preferences':
                $service->savePreferences(
                    preferences:
                        isset(
                            $_POST[
                                'preferences'
                            ]
                        ),

                    analytics:
                        isset(
                            $_POST[
                                'analytics'
                            ]
                        ),

                    marketing:
                        isset(
                            $_POST[
                                'marketing'
                            ]
                        )
                );
                break;

            default:
                break;
        }

        $redirectTo =
            isset(
                $_POST[
                    'redirect_to'
                ]
            )
                ? wp_validate_redirect(
                    wp_unslash(
                        (string) $_POST[
                            'redirect_to'
                        ]
                    ),
                    home_url('/')
                )
                : home_url('/');

        wp_safe_redirect(
            $redirectTo
        );

        exit;
    }

    private function __construct()
    {
    }
}
