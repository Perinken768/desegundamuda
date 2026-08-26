<?php

declare(strict_types=1);

namespace DSM\Mfa\Support;

use RuntimeException;

if (!defined('ABSPATH')) {
    exit;
}

final class TemplateRenderer
{
    /**
     * @param array<string, mixed> $data
     */
    public static function render(
        string $template,
        array $data = []
    ): string {
        $template =
            trim(
                str_replace(
                    '\\',
                    '/',
                    $template
                ),
                '/'
            );

        if (
            $template === ''
            || preg_match(
                '/^[a-zA-Z0-9_\/.-]+$/',
                $template
            ) !== 1
            || str_contains(
                $template,
                '..'
            )
        ) {
            throw new RuntimeException(
                'La plantilla MFA solicitada no es válida.'
            );
        }

        /*
         * Permitimos recibir:
         *
         * challenge/verify
         * challenge/verify.php
         */
        if (
            !str_ends_with(
                $template,
                '.php'
            )
        ) {
            $template .= '.php';
        }

        $file =
            DSM_MFA_PATH
            . 'templates/'
            . $template;

        if (!is_file($file)) {
            throw new RuntimeException(
                sprintf(
                    'No se encontró la plantilla DSM MFA: %s',
                    $file
                )
            );
        }

        extract(
            $data,
            EXTR_SKIP
        );

        ob_start();

        include $file;

        $output =
            ob_get_clean();

        if ($output === false) {
            throw new RuntimeException(
                'No se pudo renderizar la plantilla DSM MFA.'
            );
        }

        return $output;
    }

    private function __construct()
    {
    }
}
