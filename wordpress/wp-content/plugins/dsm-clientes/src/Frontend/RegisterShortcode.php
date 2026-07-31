<?php

declare(strict_types=1);

namespace DSM\Clientes\Frontend;

use DSM\Clientes\Support\TemplateRenderer;

if (!defined('ABSPATH')) {
    exit;
}

final class RegisterShortcode
{
    public const SHORTCODE = 'dsm_customer_register';

    public static function register(): void
    {
        add_shortcode(
            self::SHORTCODE,
            [self::class, 'render']
        );
    }

    public static function render(): string
    {
        $errorCode = isset($_GET['register_error'])
            ? sanitize_key(
                wp_unslash($_GET['register_error'])
            )
            : null;

        return TemplateRenderer::render(
            'auth/register',
            [
                'errorCode' => $errorCode,
            ]
        );
    }

    private function __construct()
    {
    }
}