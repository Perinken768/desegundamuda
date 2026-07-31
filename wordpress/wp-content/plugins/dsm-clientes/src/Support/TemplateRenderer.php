<?php

declare(strict_types=1);

namespace DSM\Clientes\Support;

use RuntimeException;

if (!defined('ABSPATH')) {
    exit;
}

final class TemplateRenderer
{
    /**
     * Renderiza una plantilla del plugin.
     *
     * @param array<string, mixed> $data
     */
    public static function render(
        string $template,
        array $data = []
    ): string {
        $template = self::normalizeTemplateName($template);

        $templateFile = DSM_CLIENTES_PATH
            . 'templates/'
            . $template
            . '.php';

        if (!is_file($templateFile)) {
            throw new RuntimeException(
                sprintf(
                    'No se encontró la plantilla DSM Clientes: %s',
                    $templateFile
                )
            );
        }

        /*
         * Convierte las claves del array en variables locales.
         *
         * Ejemplo:
         * ['hasError' => true]
         *
         * Dentro de la plantilla:
         * $hasError
         */
        extract(
            $data,
            EXTR_SKIP
        );

        ob_start();

        include $templateFile;

        $output = ob_get_clean();

        if ($output === false) {
            throw new RuntimeException(
                sprintf(
                    'No se pudo renderizar la plantilla DSM Clientes: %s',
                    $template
                )
            );
        }

        return $output;
    }

    private static function normalizeTemplateName(
        string $template
    ): string {
        $template = trim(
            str_replace('\\', '/', $template),
            '/'
        );

        /*
         * Solo permitimos letras, números, guiones,
         * barras y guiones bajos.
         */
        if (
            $template === ''
            || preg_match(
                '/^[a-zA-Z0-9_\/-]+$/',
                $template
            ) !== 1
        ) {
            throw new RuntimeException(
                'El nombre de la plantilla no es válido.'
            );
        }

        /*
         * Protección contra intentos de subir directorios.
         */
        if (str_contains($template, '..')) {
            throw new RuntimeException(
                'La ruta de la plantilla no es válida.'
            );
        }

        return $template;
    }

    private function __construct()
    {
    }
}