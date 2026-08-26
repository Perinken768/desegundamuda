<?php

declare(strict_types=1);

namespace DSM\Legal\Support;

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
                '/^[a-zA-Z0-9_\/-]+$/',
                $template
            ) !== 1
            || str_contains(
                $template,
                '..'
            )
        ) {
            throw new RuntimeException(
                'La plantilla solicitada no es válida.'
            );
        }

        $file =
            DSM_LEGAL_PATH
            . 'templates/'
            . $template
            . '.php';

        if (!is_file($file)) {
            throw new RuntimeException(
                sprintf(
                    'No se encontró la plantilla DSM Legal: %s',
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
                'No se pudo renderizar la plantilla DSM Legal.'
            );
        }

        return $output;
    }

    private function __construct()
    {
    }
}
