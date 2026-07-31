<?php

declare(strict_types=1);

namespace DSM\Clientes\Frontend;

use DSM\Clientes\Support\TemplateRenderer;

if (!defined('ABSPATH')) {
    exit;
}

final class LoginShortcode
{
    public const SHORTCODE = 'dsm_customer_login';

    public static function register(): void
    {
        add_shortcode(
            self::SHORTCODE,
            [self::class, 'render']
        );
    }

    public static function render(): string
    {
        $hasError = isset($_GET['login_error'])
            && sanitize_key(
                wp_unslash($_GET['login_error'])
            ) === 'invalid_credentials';

        return TemplateRenderer::render(
            'auth/login',
            [
                'hasError' => $hasError,
            ]
        );
    }

    private function __construct()
    {
    }
}