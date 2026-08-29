<?php

declare(strict_types=1);

namespace DSM\Multitienda\Frontend;

use DSM\Catalogo\Image\ProductImageRepository;
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
    private const STORE_QUERY_VAR =
        'dsm_multistore_slug';

    private const PRODUCT_QUERY_VAR =
        'dsm_multistore_product_slug';

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
        /*
         * Ficha individual:
         *
         * /tienda/{tienda}/{producto}/
         */
        add_rewrite_rule(
            '^tienda/([^/]+)/([^/]+)/?$',
            'index.php?'
                . self::STORE_QUERY_VAR
                . '=$matches[1]&'
                . self::PRODUCT_QUERY_VAR
                . '=$matches[2]',
            'top'
        );

        /*
         * Escaparate:
         *
         * /tienda/{tienda}/
         */
        add_rewrite_rule(
            '^tienda/([^/]+)/?$',
            'index.php?'
                . self::STORE_QUERY_VAR
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
        foreach (
            [
                self::STORE_QUERY_VAR,
                self::PRODUCT_QUERY_VAR,
            ]
            as $queryVar
        ) {
            if (
                !in_array(
                    $queryVar,
                    $queryVars,
                    true
                )
            ) {
                $queryVars[] =
                    $queryVar;
            }
        }

        return $queryVars;
    }

    public static function renderPublicStore(): void
    {
        $storeSlug =
            get_query_var(
                self::STORE_QUERY_VAR
            );

        if (!is_string($storeSlug)) {
            return;
        }

        $storeSlug =
            sanitize_title(
                $storeSlug
            );

        if ($storeSlug === '') {
            return;
        }

        $storeRepository =
            new StoreRepository();

        $store =
            $storeRepository->findBySlug(
                $storeSlug
            );

        if (
            $store === null
            || !$store->isActive()
        ) {
            self::render404();
        }

        $productSlug =
            get_query_var(
                self::PRODUCT_QUERY_VAR
            );

        $productSlug =
            is_string($productSlug)
                ? sanitize_title(
                    $productSlug
                )
                : '';

        if ($productSlug !== '') {
            self::renderProduct(
                $store,
                $productSlug
            );
        }

        self::renderStore(
            $store
        );
    }

    private static function renderStore(
        object $store
    ): never {
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

        $variantRepository =
            new ProductVariantRepository();

        $imageRepository =
            new ProductImageRepository();

        $variantsByProduct = [];
        $coverImagesByProduct = [];

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
                self::filterAvailableVariants(
                    $variants
                );

            $coverImagesByProduct[
                $product->getId()
            ] =
                $imageRepository
                    ->findCoverByProductId(
                        $product->getId()
                    );
        }

        self::renderTemplate(
            'store.php',
            compact(
                'store',
                'products',
                'variantsByProduct',
                'coverImagesByProduct'
            )
        );
    }

    private static function renderProduct(
        object $store,
        string $productSlug
    ): never {
        $productRepository =
            new ProductRepository();

        $product =
            $productRepository->findBySlug(
                $store->getId(),
                $productSlug
            );

        if (
            $product === null
            || !$product->isActive()
        ) {
            self::render404();
        }

        $variantRepository =
            new ProductVariantRepository();

        $variants =
            self::filterAvailableVariants(
                $variantRepository
                    ->findByProduct(
                        $product->getId(),
                        true
                    )
            );

        $imageRepository =
            new ProductImageRepository();

        $images =
            $imageRepository->findByProductId(
                $product->getId()
            );

        $coverImage =
            $imageRepository
                ->findCoverByProductId(
                    $product->getId()
                );

        /*
         * Comprador actual.
         */
        $customerContext =
            CustomerContext::current();

        $buyerCustomerId =
            is_array($customerContext)
                ? (int) (
                    $customerContext['id']
                    ?? 0
                )
                : 0;

        $isOwner =
            $buyerCustomerId > 0
            && $buyerCustomerId
                === $store->getCustomerId();

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

        $reservationStatus =
            isset($_GET['reservation_status'])
                ? sanitize_key(
                    wp_unslash(
                        (string) $_GET[
                            'reservation_status'
                        ]
                    )
                )
                : '';

        $reservationError =
            isset($_GET['reservation_error'])
                ? sanitize_text_field(
                    wp_unslash(
                        (string) $_GET[
                            'reservation_error'
                        ]
                    )
                )
                : '';

        self::renderTemplate(
            'product.php',
            compact(
                'store',
                'product',
                'variants',
                'images',
                'coverImage',
                'buyerCustomerId',
                'isOwner',
                'buyerHasWhatsappContact',
                'buyerWhatsappMissingPhone',
                'buyerWhatsappDisabled',
                'reservationStatus',
                'reservationError'
            )
        );
    }

    /**
     * @param array<int, object> $variants
     *
     * @return array<int, object>
     */
    private static function filterAvailableVariants(
        array $variants
    ): array {
        return array_values(
            array_filter(
                $variants,
                static function (
                    object $variant
                ): bool {
                    return
                        !$variant->isArchived()
                        && $variant->isActive()
                        && (
                            !$variant->tracksStock()
                            || $variant
                                ->getAvailableStock()
                                > 0
                        );
                }
            )
        );
    }

    /**
     * @param array<string, mixed> $variables
     */
    private static function renderTemplate(
        string $templateName,
        array $variables
    ): never {
        $template =
            DSM_MULTITIENDA_PATH
            . 'templates/public/'
            . $templateName;

        if (!is_file($template)) {
            wp_die(
                esc_html__(
                    'No se encontró la plantilla pública solicitada.',
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

        extract(
            $variables,
            EXTR_SKIP
        );

        status_header(200);

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

        status_header(404);

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
                'La tienda o producto solicitado no está disponible.',
                'dsm-multitienda'
            ),
            esc_html__(
                'Contenido no disponible',
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
