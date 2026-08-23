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
             * El tema nos entrega tanto ID como nombre.
             * Las tiendas actuales almacenan el nombre
             * territorial, por ejemplo "Gran Canaria".
             */
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

            $minPrice =
                isset($_GET['dsm_min_price'])
                    ? (float) wp_unslash(
                        (string) $_GET[
                            'dsm_min_price'
                        ]
                    )
                    : null;

            $maxPrice =
                isset($_GET['dsm_max_price'])
                    ? (float) wp_unslash(
                        (string) $_GET[
                            'dsm_max_price'
                        ]
                    )
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
                     * Todavía no existe ficha individual
                     * de producto.
                     *
                     * Mientras tanto, enlazamos al escaparate
                     * de la tienda.
                     */
                    $publicUrl =
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

    private function __construct()
    {
    }
}
