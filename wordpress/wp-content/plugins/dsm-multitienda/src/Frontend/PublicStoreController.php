<?php

declare(strict_types=1);

namespace DSM\Multitienda\Frontend;

use DSM\Catalogo\Product\ProductRepository;
use DSM\Catalogo\Product\ProductStatus;
use DSM\Catalogo\Variant\ProductVariantRepository;
use DSM\Multitienda\Store\StoreRepository;
use DSM\Multitienda\Support\CustomerContext;

if (!defined('ABSPATH')) {
    exit;
}

final class PublicStoreController
{
    private const QUERY_VAR =
        'dsm_multistore_slug';

    public static function register(): void
    {
        add_action(
            'init',
            [
                self::class,
                'registerRewriteRules',
            ]
        );

        add_filter(
            'query_vars',
            [
                self::class,
                'registerQueryVars',
            ]
        );

        add_action(
            'template_redirect',
            [
                self::class,
                'renderPublicStore',
            ]
        );
    }

    public static function registerRewriteRules(): void
    {
        add_rewrite_rule(
            '^tienda/([^/]+)/?$',
            'index.php?'
                . self::QUERY_VAR
                . '=$matches[1]',
            'top'
        );
    }

    /**
     * @param array<int, string> $queryVars
     *
     * @return array<int, string>
     */
    public static function registerQueryVars(
        array $queryVars
    ): array {
        if (
            !in_array(
                self::QUERY_VAR,
                $queryVars,
                true
            )
        ) {
            $queryVars[] =
                self::QUERY_VAR;
        }

        return $queryVars;
    }

    public static function renderPublicStore(): void
    {
        $slug =
            get_query_var(
                self::QUERY_VAR
            );

        if (!is_string($slug)) {
            return;
        }

        $slug =
            sanitize_title(
                $slug
            );

        if ($slug === '') {
            return;
        }

        $storeRepository =
            new StoreRepository();

        $store =
            $storeRepository->findBySlug(
                $slug
            );

        /*
         * Solo ACTIVE es visible públicamente.
         */
        if (
            $store === null
            || !$store->isActive()
        ) {
            self::render404();
        }

        /*
         * =====================================================
         * PRODUCTOS ACTIVOS
         * =====================================================
         */

        $productRepository =
            new ProductRepository();

        $products =
            $productRepository->findByStore(
                storeId:
                    $store->getId(),

                limit:
                    250,

                offset:
                    0,

                status:
                    ProductStatus::ACTIVE
            );

        /*
         * =====================================================
         * VARIANTES
         * =====================================================
         */

        $variantRepository =
            new ProductVariantRepository();

        $variantsByProduct = [];

        foreach ($products as $product) {
            $variants =
                $variantRepository
                    ->findByProduct(
                        $product->getId(),
                        true
                    );

            $variantsByProduct[
                $product->getId()
            ] =
                array_values(
                    array_filter(
                        $variants,
                        static function (
                            $variant
                        ): bool {
                            return
                                !$variant
                                    ->isArchived()
                                && $variant
                                    ->isActive()
                                && (
                                    !$variant
                                        ->tracksStock()
                                    || $variant
                                        ->getAvailableStock()
                                        > 0
                                );
                        }
                    )
                );
        }

        /*
         * =====================================================
         * COMPRADOR ACTUAL
         * =====================================================
         */

        $customerContext =
            CustomerContext::current();

        $buyerCustomerId =
            is_array(
                $customerContext
            )
                ? (int) (
                    $customerContext['id']
                    ?? 0
                )
                : 0;

        $isOwner =
            $buyerCustomerId > 0
            && $buyerCustomerId
                === $store->getCustomerId();

        /*
         * =====================================================
         * CONTACTO WHATSAPP DEL COMPRADOR
         * =====================================================
         */

        $buyerHasWhatsappContact =
            false;

        $buyerWhatsappMissingPhone =
            false;

        $buyerWhatsappDisabled =
            false;

        if (
            $buyerCustomerId > 0
            && !$isOwner
            && is_array($customerContext)
        ) {
            $buyerPhone =
                trim(
                    (string) (
                        $customerContext['phone']
                        ?? ''
                    )
                );

            $buyerAllowsWhatsapp =
                !empty(
                    $customerContext[
                        'allow_whatsapp'
                    ]
                );

            $buyerWhatsappMissingPhone =
                $buyerPhone === '';

            $buyerWhatsappDisabled =
                !$buyerAllowsWhatsapp;

            $buyerHasWhatsappContact =
                !$buyerWhatsappMissingPhone
                && !$buyerWhatsappDisabled;
        }

        /*
         * =====================================================
         * AVISOS DE RESERVA
         * =====================================================
         */

        $reservationStatus =
            isset(
                $_GET[
                    'reservation_status'
                ]
            )
                ? sanitize_key(
                    wp_unslash(
                        (string) $_GET[
                            'reservation_status'
                        ]
                    )
                )
                : '';

        $reservationError =
            isset(
                $_GET[
                    'reservation_error'
                ]
            )
                ? sanitize_text_field(
                    wp_unslash(
                        (string) $_GET[
                            'reservation_error'
                        ]
                    )
                )
                : '';

        $template =
            DSM_MULTITIENDA_PATH
            . 'templates/public/store.php';

        if (!is_file($template)) {
            wp_die(
                esc_html__(
                    'No se encontró la plantilla pública de la tienda.',
                    'dsm-multitienda'
                ),
                esc_html__(
                    'Error de Multitienda',
                    'dsm-multitienda'
                ),
                [
                    'response' =>
                        500,
                ]
            );
        }

        status_header(
            200
        );

        nocache_headers();

        get_header();

        require $template;

        get_footer();

        exit;
    }

    private static function render404(): never
    {
        global $wp_query;

        if ($wp_query !== null) {
            $wp_query->set_404();
        }

        status_header(
            404
        );

        nocache_headers();

        $template404 =
            get_404_template();

        if (
            is_string($template404)
            && $template404 !== ''
            && is_file($template404)
        ) {
            include $template404;

            exit;
        }

        wp_die(
            esc_html__(
                'La tienda solicitada no está disponible.',
                'dsm-multitienda'
            ),
            esc_html__(
                'Tienda no disponible',
                'dsm-multitienda'
            ),
            [
                'response' =>
                    404,
            ]
        );
    }

    private function __construct()
    {
    }
}