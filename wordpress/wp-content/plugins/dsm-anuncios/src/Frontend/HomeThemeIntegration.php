<?php

declare(strict_types=1);

namespace DSM\Anuncios\Frontend;

if (!defined('ABSPATH')) {
    exit;
}

final class HomeThemeIntegration
{
    public static function register(): void
    {
        add_action(
            'dsm_theme_home_categories',
            [
                self::class,
                'renderCategories',
            ]
        );

        add_filter(
            'dsm_theme_home_listing_items',
            [
                self::class,
                'provideListingItems',
            ],
            10,
            2
        );
    }

    public static function renderCategories(): void
    {
        $categories =
            apply_filters(
                'dsm_public_categories',
                []
            );

        if (
            !is_array($categories)
            || $categories === []
        ) {
            return;
        }

        foreach ($categories as $category) {
            if (!is_array($category)) {
                continue;
            }

            $categoryId =
                max(
                    0,
                    (int) (
                        $category['id']
                        ?? 0
                    )
                );

            $parentId =
                isset($category['parent_id'])
                    ? max(
                        0,
                        (int) $category['parent_id']
                    )
                    : 0;

            $name =
                trim(
                    (string) (
                        $category['name']
                        ?? ''
                    )
                );

            if (
                $categoryId <= 0
                || $parentId > 0
                || $name === ''
            ) {
                continue;
            }

            $arguments = [
                'dsm_category' =>
                    $categoryId,
            ];

            foreach (
                [
                    'tipo',
                    'dsm_search',
                    'dsm_area',
                    'dsm_min_price',
                    'dsm_max_price',
                    'dsm_orderby',
                ]
                as $parameter
            ) {
                if (
                    !isset($_GET[$parameter])
                    || $_GET[$parameter] === ''
                ) {
                    continue;
                }

                $arguments[$parameter] =
                    sanitize_text_field(
                        wp_unslash(
                            (string) $_GET[
                                $parameter
                            ]
                        )
                    );
            }

            $url =
                add_query_arg(
                    $arguments,
                    home_url('/')
                );
            ?>

            <a
                class="dsm-home-category"
                href="<?php
                echo esc_url(
                    $url
                );
                ?>"
            >
                <?php
                echo esc_html(
                    $name
                );
                ?>
            </a>

            <?php
        }
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

        if (
            $requestedType
            === 'tiendas'
        ) {
            return $items;
        }

        $filters = [];

        if (
            isset($_GET['dsm_search'])
        ) {
            $filters['search'] =
                sanitize_text_field(
                    wp_unslash(
                        (string) $_GET[
                            'dsm_search'
                        ]
                    )
                );
        }

        if (
            isset($_GET['dsm_category'])
        ) {
            $filters['category_id'] =
                absint(
                    wp_unslash(
                        (string) $_GET[
                            'dsm_category'
                        ]
                    )
                );
        }

        if (
            isset($_GET['dsm_brand'])
        ) {
            $filters['brand'] =
                sanitize_text_field(
                    wp_unslash(
                        (string) $_GET[
                            'dsm_brand'
                        ]
                    )
                );
        }

        if (
            isset($_GET['dsm_condition'])
        ) {
            $filters['condition_code'] =
                sanitize_key(
                    wp_unslash(
                        (string) $_GET[
                            'dsm_condition'
                        ]
                    )
                );
        }

        /*
         * Precio mínimo.
         *
         * Si el parámetro existe pero está vacío,
         * significa que no existe límite mínimo.
         *
         * No debemos convertir una cadena vacía
         * directamente a float porque PHP la
         * convertiría en 0.0.
         */
        if (
            isset($_GET['dsm_min_price'])
        ) {
            $minPriceRaw =
                trim(
                    (string) wp_unslash(
                        $_GET[
                            'dsm_min_price'
                        ]
                    )
                );

            if ($minPriceRaw !== '') {
                $filters['min_price'] =
                    (float) $minPriceRaw;
            }
        }

        /*
         * Precio máximo.
         *
         * Una cadena vacía significa "sin límite".
         *
         * Esto es especialmente importante porque
         * convertir '' a float daría 0.0 y haría
         * que desaparecieran todos los anuncios
         * cuyo precio fuera superior a cero.
         */
        if (
            isset($_GET['dsm_max_price'])
        ) {
            $maxPriceRaw =
                trim(
                    (string) wp_unslash(
                        $_GET[
                            'dsm_max_price'
                        ]
                    )
                );

            if ($maxPriceRaw !== '') {
                $filters['max_price'] =
                    (float) $maxPriceRaw;
            }
        }

        if (
            isset($_GET['dsm_area'])
        ) {
            $filters['area_id'] =
                absint(
                    wp_unslash(
                        (string) $_GET[
                            'dsm_area'
                        ]
                    )
                );
        }

        if (
            isset($_GET['dsm_municipality'])
        ) {
            $filters['municipality_id'] =
                absint(
                    wp_unslash(
                        (string) $_GET[
                            'dsm_municipality'
                        ]
                    )
                );
        }

        /*
         * Isla efectiva de portada.
         *
         * Si existe dsm_area en la URL, esa selección
         * manual prevalece.
         *
         * Si no existe, utilizamos el área aportada
         * por el tema, que normalmente procede del
         * perfil del cliente autenticado.
         */
        if (array_key_exists('dsm_area', $_GET)) {
            $filters['area_id'] =
                max(
                    0,
                    absint(
                        wp_unslash(
                            (string) $_GET[
                                'dsm_area'
                            ]
                        )
                    )
                );
        } else {
            $contextAreaId =
                max(
                    0,
                    (int) (
                        $context[
                            'area_id'
                        ]
                        ?? 0
                    )
                );

            if ($contextAreaId > 0) {
                $filters['area_id'] =
                    $contextAreaId;
            }
        }

        $repository =
            new AdvertisementSearchRepository();

        $result =
            $repository->search(
                $filters,
                1,
                24
            );

        $advertisements =
            isset($result['items'])
            && is_array($result['items'])
                ? $result['items']
                : [];

        foreach (
            $advertisements
            as $advertisement
        ) {
            if (!is_array($advertisement)) {
                continue;
            }

            $publicUrl =
                trim(
                    (string) (
                        $advertisement[
                            'public_url'
                        ]
                        ?? ''
                    )
                );

            $title =
                trim(
                    (string) (
                        $advertisement[
                            'title'
                        ]
                        ?? ''
                    )
                );

            if (
                $publicUrl === ''
                || $title === ''
            ) {
                continue;
            }

            $items[] = [
                'source' =>
                    'dsm-anuncios',

                'content_type' =>
                    'advertisement',

                'id' =>
                    (int) (
                        $advertisement['id']
                        ?? 0
                    ),

                'title' =>
                    $title,

                'url' =>
                    $publicUrl,

                'image_url' =>
                    (string) (
                        $advertisement[
                            'cover_thumbnail_url'
                        ]
                        ?? ''
                    ),

                'price' =>
                    (float) (
                        $advertisement[
                            'price'
                        ]
                        ?? 0
                    ),

                'original_price' =>
                    $advertisement[
                        'original_price'
                    ]
                    ?? null,

                'seller' =>
                    trim(
                        (string) (
                            $advertisement[
                                'customer_display_name'
                            ]
                            ?? ''
                        )
                    ),

                'category' =>
                    trim(
                        (string) (
                            $advertisement[
                                'category_name'
                            ]
                            ?? ''
                        )
                    ),

                'is_promoted' =>
                    !empty(
                        $advertisement[
                            'is_promoted'
                        ]
                    ),

                'is_reserved' =>
                    !empty(
                        $advertisement[
                            'is_reserved'
                        ]
                    ),

                'published_at' =>
                    (string) (
                        $advertisement[
                            'published_at'
                        ]
                        ?? $advertisement[
                            'created_at'
                        ]
                        ?? ''
                    ),
            ];
        }

        return $items;
    }

    private function __construct()
    {
    }
}
