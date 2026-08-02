<?php

declare(strict_types=1);

namespace DSM\Clientes\Frontend;

use DSM\Clientes\Support\TemplateRenderer;

if (!defined('ABSPATH')) {
    exit;
}

final class ForgotPasswordShortcode
{
    public const SHORTCODE =
        'dsm_customer_forgot_password';

    public static function register(): void
    {
        add_shortcode(
            self::SHORTCODE,
            [self::class, 'render']
        );
    }

    public static function render(): string
    {
        return TemplateRenderer::render(
            'auth/forgot-password'
        );
    }

    private function __construct()
    {
    }
}