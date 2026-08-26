<?php

declare(strict_types=1);

namespace DSM\Cookies\Frontend;

use DSM\Cookies\Consent\ConsentService;
use DSM\Cookies\Support\TemplateRenderer;

if (!defined('ABSPATH')) {
    exit;
}

final class ConsentBanner
{
    public static function register(): void
    {
        add_action(
            'wp_enqueue_scripts',
            [
                self::class,
                'enqueueAssets',
            ]
        );

        add_action(
            'wp_footer',
            [
                self::class,
                'render',
            ],
            5
        );
    }

    public static function enqueueAssets(): void
    {
        if (is_admin()) {
            return;
        }

        wp_enqueue_style(
            'dsm-cookies',
            DSM_COOKIES_URL
                . 'assets/css/cookies.css',
            [],
            DSM_COOKIES_VERSION
        );

        wp_enqueue_script(
            'dsm-cookies',
            DSM_COOKIES_URL
                . 'assets/js/cookies.js',
            [],
            DSM_COOKIES_VERSION,
            true
        );
    }

    public static function render(): void
    {
        if (is_admin()) {
            return;
        }

        $service =
            new ConsentService();

        $consent =
            $service->getCurrent();

        /*
         * Solicitamos la URL de la política de cookies
         * mediante el contrato público de DSM Legal.
         *
         * DSM Cookies no conoce slugs, dominios ni rutas
         * internas de DSM Legal.
         */
        $cookiePolicyUrl =
            apply_filters(
                'dsm_legal_document_url',
                '',
                'cookies'
            );

        echo TemplateRenderer::render(
            'consent/banner',
            [
                'consent' =>
                    $consent,

                'hasConsent' =>
                    $consent !== null,

                'cookiePolicyUrl' =>
                    is_string(
                        $cookiePolicyUrl
                    )
                        ? $cookiePolicyUrl
                        : '',
            ]
        );
    }

    private function __construct()
    {
    }
}
