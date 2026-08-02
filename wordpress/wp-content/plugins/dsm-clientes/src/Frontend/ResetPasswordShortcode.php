<?php

declare(strict_types=1);

namespace DSM\Clientes\Frontend;

use DSM\Clientes\Support\TemplateRenderer;

if (!defined('ABSPATH')) {
    exit;
}

final class ResetPasswordShortcode
{
    public const SHORTCODE =
        'dsm_customer_reset_password';

    public static function register(): void
    {
        add_shortcode(
            self::SHORTCODE,
            [self::class, 'render']
        );
    }

    public static function render(): string
    {
        $token = isset($_GET['token'])
            ? sanitize_text_field(
                wp_unslash($_GET['token'])
            )
            : '';

        return TemplateRenderer::render(
            'auth/reset-password',
            [
                'token' => $token,
            ]
        );
    }

    private function __construct()
    {
    }
}