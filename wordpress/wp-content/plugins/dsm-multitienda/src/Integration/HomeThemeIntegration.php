<?php

declare(strict_types=1);

namespace DSM\Multitienda\Integration;

use DSM\Catalogo\Image\ProductImageRepository;
use DSM\Catalogo\Product\ProductRepository;
use DSM\Catalogo\Product\ProductStatus;
use DSM\Multitienda\Store\StoreRepository;
use Throwable;

if (!defined('ABSPATH')) {
    exit;
}

final class HomeThemeIntegration
{
    public static function register(): void
    {
        add_filter(
            'dsm_theme_home_listing_items',
            [
                self::class,
                'provideListingItems',
            ],
            20,
            2
        );

        /*
         * Directorio de tiendas activas disponible
         * para la portada del tema.
         */
        add_filter(
            'dsm_theme_home_stores',
            [
                self::class,
                'provideStores',
            ],
            20,
            2
        );
    }

    /**
     * @param mixed                $currentItems
     * @param array<string, mixed> $context
     *
     * @return array<int, array<string, mixed>>
     */
    public static function provideListingItems(
        mixed $currentItems,
        array $context = []
    ): array {
        $items =
            is_array($currentItems)
                ? $currentItems
                : [];

        $requestedType =
            sanitize_key(
                (string) (
                    $context['type']
                    ?? 'todos'
                )
            );

        /*
         * La pestaña Anuncios no debe recibir
         * productos de Multitienda.
         */
        if (
            $requestedType
            === 'anuncios'
        ) {
            return $items;
        }

        try {
            $storeRepository =
                new StoreRepository();

            $productRepository =
                new ProductRepository();

            $imageRepository =
                new ProductImageRepository();

            $stores =
                $storeRepository
                    ->findActive(
                        250,
                        0
                    );

            /*
             * Isla efectiva de portada.
             *
             * Si dsm_area existe expresamente en la URL:
             *
             * - dsm_area = 0 significa TODAS LAS ISLAS.
             * - dsm_area > 0 utiliza el área resuelta por el tema.
             *
             * Si no existe dsm_area, respetamos el contexto
             * territorial que pueda proceder del perfil/cookie.
             */
            $requestedAreaId =
                array_key_exists(
                    'dsm_area',
                    $_GET
                )
                    ? max(
                        0,
                        absint(
                            wp_unslash(
                                (string) $_GET[
                                    'dsm_area'
                                ]
                            )
                        )
                    )
                    : null;

            $requestedAreaName = '';

            if (
                $requestedAreaId === null
                || $requestedAreaId > 0
            ) {
                $requestedAreaName =
                    trim(
                        sanitize_text_field(
                            (string) (
                                $context[
                                    'area_name'
                                ]
                                ?? ''
                            )
                        )
                    );
            }

            $requestedCategoryId =
                isset($_GET['dsm_category'])
                    ? absint(
                        wp_unslash(
                            (string) $_GET[
                                'dsm_category'
                            ]
                        )
                    )
                    : 0;

            $search =
                isset($_GET['dsm_search'])
                    ? trim(
                        sanitize_text_field(
                            wp_unslash(
                                (string) $_GET[
                                    'dsm_search'
                                ]
                            )
                        )
                    )
                    : '';

            /*
             * Precio mínimo.
             *
             * Un parámetro presente pero vacío no debe convertirse
             * en 0.0, sino interpretarse como "sin límite".
             */
            $minPriceRaw =
                isset($_GET['dsm_min_price'])
                    ? trim(
                        (string) wp_unslash(
                            $_GET[
                                'dsm_min_price'
                            ]
                        )
                    )
                    : '';

            $minPrice =
                $minPriceRaw !== ''
                    ? (float) $minPriceRaw
                    : null;

            /*
             * Precio máximo.
             *
             * Igual que el mínimo: una cadena vacía significa
             * que no existe límite máximo.
             */
            $maxPriceRaw =
                isset($_GET['dsm_max_price'])
                    ? trim(
                        (string) wp_unslash(
                            $_GET[
                                'dsm_max_price'
                            ]
                        )
                    )
                    : '';

            $maxPrice =
                $maxPriceRaw !== ''
                    ? (float) $maxPriceRaw
                    : null;

            foreach ($stores as $store) {
                /*
                 * Si existe una isla efectiva, únicamente
                 * mostramos tiendas pertenecientes a ella.
                 */
                if ($requestedAreaName !== '') {
                    $storeIsland =
                        trim(
                            (string) (
                                $store->getIsland()
                                ?? ''
                            )
                        );

                    if (
                        $storeIsland === ''
                        || mb_strtolower(
                            $storeIsland
                        ) !== mb_strtolower(
                            $requestedAreaName
                        )
                    ) {
                        continue;
                    }
                }

                $products =
                    $productRepository
                        ->findByStore(
                            storeId:
                                $store->getId(),

                            limit:
                                250,

                            offset:
                                0,

                            status:
                                ProductStatus::ACTIVE
                        );

                foreach ($products as $product) {
                    /*
                     * ------------------------------------------
                     * CATEGORÍA
                     * ------------------------------------------
                     */

                    if (
                        $requestedCategoryId > 0
                        && $product->getCategoryId()
                            !== $requestedCategoryId
                    ) {
                        continue;
                    }

                    /*
                     * ------------------------------------------
                     * BÚSQUEDA
                     * ------------------------------------------
                     */

                    if ($search !== '') {
                        $haystack =
                            mb_strtolower(
                                implode(
                                    ' ',
                                    array_filter(
                                        [
                                            $product
                                                ->getName(),

                                            $product
                                                ->getDescription(),
                                        ]
                                    )
                                )
                            );

                        if (
                            !str_contains(
                                $haystack,
                                mb_strtolower(
                                    $search
                                )
                            )
                        ) {
                            continue;
                        }
                    }

                    $price =
                        $product
                            ->getDefaultPrice();

                    /*
                     * ------------------------------------------
                     * PRECIO
                     * ------------------------------------------
                     */

                    if (
                        $minPrice !== null
                        && $price < $minPrice
                    ) {
                        continue;
                    }

                    if (
                        $maxPrice !== null
                        && $price > $maxPrice
                    ) {
                        continue;
                    }

                    /*
                     * URL pública de la ficha individual
                     * del producto.
                     *
                     * /tienda/{tienda}/{producto}/
                     */
                    $publicUrl =
                        home_url(
                            '/tienda/'
                            . rawurlencode(
                                $store->getSlug()
                            )
                            . '/'
                            . rawurlencode(
                                $product->getSlug()
                            )
                            . '/'
                        );

                    /*
                     * URL pública de la tienda.
                     *
                     * Se entrega también al tema para que
                     * pueda utilizar el vendedor como filtro
                     * independiente del enlace del producto.
                     */
                    $storeUrl =
                        home_url(
                            '/tienda/'
                            . rawurlencode(
                                $store->getSlug()
                            )
                            . '/'
                        );

                    $coverImage =
                        $imageRepository
                            ->findCoverByProductId(
                                $product->getId()
                            );

                    $coverUrl = '';

                    if ($coverImage !== null) {
                        $resolvedCoverUrl =
                            wp_get_attachment_image_url(
                                $coverImage
                                    ->getAttachmentId(),
                                'medium'
                            );

                        if (
                            is_string(
                                $resolvedCoverUrl
                            )
                        ) {
                            $coverUrl =
                                $resolvedCoverUrl;
                        }
                    }

                    $items[] = [
                        'source' =>
                            'dsm-multitienda',

                        'content_type' =>
                            'store_product',

                        'id' =>
                            $product->getId(),

                        'title' =>
                            $product->getName(),

                        'url' =>
                            $publicUrl,

                        'image_url' =>
                            $coverUrl,

                        'price' =>
                            $price,

                        'original_price' =>
                            $product
                                ->getOriginalPrice(),

                        'seller' =>
                            $store->getName(),

                        'store_id' =>
                            $store->getId(),

                        'store_slug' =>
                            $store->getSlug(),

                        'store_url' =>
                            $storeUrl,

                        'category_id' =>
                            $product
                                ->getCategoryId(),

                        'is_promoted' =>
                            false,

                        'is_reserved' =>
                            false,

                        'published_at' =>
                            $product
                                ->getCreatedAt()
                                ->format(
                                    'Y-m-d H:i:s'
                                ),
                    ];
                }
            }

            return $items;
        } catch (Throwable $exception) {
            error_log(
                '[DSM Multitienda] No se pudieron '
                . 'resolver los productos de portada: '
                . $exception->getMessage()
            );

            return $items;
        }
    }

    /**
     * Proporciona al tema las tiendas activas.
     *
     * @param mixed                $currentStores
     * @param array<string, mixed> $context
     *
     * @return array<int, array<string, mixed>>
     */
    public static function provideStores(
        mixed $currentStores,
        array $context = []
    ): array {
        $items =
            is_array($currentStores)
                ? $currentStores
                : [];

        try {
            $storeRepository =
                new StoreRepository();

            $stores =
                $storeRepository->findActive(
                    250,
                    0
                );

            /*
             * dsm_area = 0 significa expresamente
             * "Todas las islas".
             */
            $requestedAreaId =
                array_key_exists(
                    'dsm_area',
                    $_GET
                )
                    ? max(
                        0,
                        absint(
                            wp_unslash(
                                (string) $_GET[
                                    'dsm_area'
                                ]
                            )
                        )
                    )
                    : null;

            $requestedAreaName = '';

            if (
                $requestedAreaId === null
                || $requestedAreaId > 0
            ) {
                $requestedAreaName =
                    trim(
                        sanitize_text_field(
                            (string) (
                                $context[
                                    'area_name'
                                ]
                                ?? ''
                            )
                        )
                    );
            }

            foreach ($stores as $store) {
                $storeIsland =
                    trim(
                        (string) (
                            $store->getIsland()
                            ?? ''
                        )
                    );

                /*
                 * Si existe una isla seleccionada,
                 * solo mostramos tiendas de esa isla.
                 */
                if (
                    $requestedAreaName !== ''
                    && (
                        $storeIsland === ''
                        || mb_strtolower(
                            $storeIsland
                        ) !== mb_strtolower(
                            $requestedAreaName
                        )
                    )
                ) {
                    continue;
                }

                $logoUrl = '';

                $logoAttachmentId =
                    $store->getLogoAttachmentId();

                if ($logoAttachmentId !== null) {
                    $resolvedLogoUrl =
                        wp_get_attachment_image_url(
                            $logoAttachmentId,
                            'medium'
                        );

                    if (
                        is_string(
                            $resolvedLogoUrl
                        )
                    ) {
                        $logoUrl =
                            $resolvedLogoUrl;
                    }
                }

                $items[] = [
                    'id' =>
                        $store->getId(),

                    'name' =>
                        $store->getName(),

                    'slug' =>
                        $store->getSlug(),

                    'description' =>
                        (string) (
                            $store->getDescription()
                            ?? ''
                        ),

                    'island' =>
                        $storeIsland,

                    'location' =>
                        (string) (
                            $store->getLocationText()
                            ?? ''
                        ),

                    'logo_url' =>
                        $logoUrl,

                    'url' =>
                        home_url(
                            '/tienda/'
                            . rawurlencode(
                                $store->getSlug()
                            )
                            . '/'
                        ),
                ];
            }

            return $items;
        } catch (Throwable $exception) {
            error_log(
                '[DSM Multitienda] No se pudieron '
                . 'resolver las tiendas de portada: '
                . $exception->getMessage()
            );

            return $items;
        }
    }

    private function __construct()
    {
    }
}
