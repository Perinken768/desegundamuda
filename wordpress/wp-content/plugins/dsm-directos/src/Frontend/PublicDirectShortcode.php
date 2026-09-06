<?php

declare(strict_types=1);

namespace DSM\Directos\Frontend;

use DSM\Directos\Application\DirectItemViewService;
use DSM\Directos\Application\DirectVideoEmbedService;
use DSM\Directos\Direct\DirectItemRepository;
use DSM\Directos\Direct\DirectRepository;
use Throwable;

if (!defined('ABSPATH')) {
    exit;
}

final class PublicDirectShortcode
{
    public const SHORTCODE =
        'dsm_public_direct';

    public static function register(): void
    {
        add_shortcode(
            self::SHORTCODE,
            [
                new self(),
                'render',
            ]
        );
    }

    public function render(): string
    {
        try {
            $customer =
                apply_filters(
                    'dsm_current_customer_context',
                    null
                );

            $viewerCustomerId =
                is_array($customer)
                    ? max(
                        0,
                        (int) (
                            $customer['id']
                            ?? 0
                        )
                    )
                    : 0;

            $repository =
                new DirectRepository();

            /*
             * URL pública:
             *
             * /directo/?live=directo-2
             */
            $requestedSlug =
                isset($_GET['live'])
                && is_scalar(
                    $_GET['live']
                )
                    ? sanitize_title(
                        wp_unslash(
                            (string) $_GET['live']
                        )
                    )
                    : '';

            if ($requestedSlug !== '') {
                $direct =
                    $repository
                        ->findBySlug(
                            $requestedSlug
                        );
            } elseif ($viewerCustomerId > 0) {
                /*
                 * El propietario puede seguir entrando desde
                 * Mi directo sin especificar slug.
                 */
                $direct =
                    $repository
                        ->findByCustomerId(
                            $viewerCustomerId
                        );
            } else {
                return '<p>No se pudo identificar el directo.</p>';
            }

            if ($direct === null) {
                return '<p>El directo no existe.</p>';
            }

            $ownerCustomerId =
                max(
                    0,
                    (int) (
                        $direct['customer_id']
                        ?? 0
                    )
                );

            $isOwner =
                $viewerCustomerId > 0
                && $viewerCustomerId
                    === $ownerCustomerId;

            /*
             * Un comprador no debe acceder a una emisión
             * todavía no abierta.
             *
             * Los directos cerrados sí conservan su URL para
             * poder mostrar que la emisión terminó.
             */
            $status =
                sanitize_key(
                    (string) (
                        $direct['status']
                        ?? ''
                    )
                );

            if (
                !$isOwner
                && !in_array(
                    $status,
                    [
                        'live',
                        'closed',
                    ],
                    true
                )
            ) {
                return '<p>Este directo todavía no está disponible.</p>';
            }

            $rawItems =
                (
                    new DirectItemRepository()
                )->findByLiveId(
                    (int) $direct['id']
                );

            $viewService =
                new DirectItemViewService();

            $items = [];

            foreach ($rawItems as $rawItem) {
                if (!is_array($rawItem)) {
                    continue;
                }

                if (
                    sanitize_key(
                        (string) (
                            $rawItem['status']
                            ?? ''
                        )
                    ) !== 'available'
                ) {
                    continue;
                }

                $resolved =
                    $viewService->resolve(
                        $rawItem
                    );

                if ($resolved !== null) {
                    $items[] =
                        $resolved;
                }
            }

            $video =
                (
                    new DirectVideoEmbedService()
                )->resolve(
                    (string) (
                        $direct['platform']
                        ?? ''
                    ),
                    (string) (
                        $direct['platform_url']
                        ?? ''
                    )
                );

            ob_start();

            $template =
                DSM_DIRECTOS_PATH
                . 'templates/public/direct.php';

            require $template;

            return (string) ob_get_clean();

        } catch (Throwable $exception) {
            return sprintf(
                '<p>%s</p>',
                esc_html(
                    $exception->getMessage()
                )
            );
        }
    }

    private function __construct()
    {
    }
}
