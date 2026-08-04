<?php

declare(strict_types=1);

namespace DSM\Anuncios\Frontend;

use DSM\Anuncios\Advertisement\AdvertisementStatus;
use wpdb;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Repositorio público de anuncios.
 *
 * Gestiona:
 *
 * - listado del marketplace;
 * - búsquedas y filtros;
 * - paginación;
 * - prioridad de anuncios promocionados;
 * - ficha pública por ID o slug;
 * - galería completa de imágenes;
 * - categorías y marcas públicas.
 *
 * No modifica anuncios ni conoce las tablas internas
 * de promociones, ubicaciones o suscripciones.
 */
final class AdvertisementSearchRepository
{
    public const DEFAULT_PER_PAGE = 24;

    public const MAX_PER_PAGE = 60;

    private wpdb $database;

    private string $advertisementsTable;

    private string $imagesTable;

    private string $categoriesTable;

    private string $customersTable;

    private string $customerProfilesTable;

    public function __construct()
    {
        global $wpdb;

        $this->database =
            $wpdb;

        $this->advertisementsTable =
            $wpdb->prefix
            . 'dsm_ads';

        $this->imagesTable =
            $wpdb->prefix
            . 'dsm_ad_images';

        $this->categoriesTable =
            $wpdb->prefix
            . 'dsm_categories';

        $this->customersTable =
            $wpdb->prefix
            . 'dsm_customers';

        $this->customerProfilesTable =
            $wpdb->prefix
            . 'dsm_customer_profiles';
    }

    /**
     * Busca anuncios visibles públicamente.
     *
     * Filtros admitidos:
     *
     * - advertisement_id
     * - slug
     * - search
     * - island_id
     * - municipality_id
     * - category_id
     * - customer_id
     * - store_id
     * - brand
     * - condition_code
     * - min_price
     * - max_price
     * - orderby
     * - order
     *
     * @param array<string, mixed> $filters
     *
     * @return array{
     *     items:array<int, array<string, mixed>>,
     *     total:int,
     *     page:int,
     *     per_page:int,
     *     total_pages:int,
     *     filters:array<string, mixed>
     * }
     */
    public function search(
        array $filters = [],
        int $page = 1,
        int $perPage = self::DEFAULT_PER_PAGE
    ): array {
        $page =
            max(
                1,
                $page
            );

        $perPage =
            min(
                self::MAX_PER_PAGE,
                max(
                    1,
                    $perPage
                )
            );

        $filters =
            $this->normalizeFilters(
                $filters
            );

        $promotedIds =
            $this->getPromotedAdvertisementIds(
                $filters
            );

        $offset =
            ($page - 1)
            * $perPage;

        $parameters = [];

        $whereSql =
            $this->buildWhereSql(
                $filters,
                $parameters
            );

        $promotionOrderSql =
            $this->buildPromotionOrderSql(
                $promotedIds,
                $filters
            );

        $orderSql =
            $this->buildOrderSql(
                $filters
            );

        $sql = "
            SELECT
                advertisements.id,
                advertisements.customer_id,
                advertisements.store_id,
                advertisements.category_id,
                advertisements.island_id,
                advertisements.municipality_id,
                advertisements.title,
                advertisements.slug,
                advertisements.description,
                advertisements.brand,
                advertisements.price,
                advertisements.original_price,
                advertisements.purchase_date,
                advertisements.condition_code,
                advertisements.status,
                advertisements.reserved_at,
                advertisements.published_at,
                advertisements.created_at,
                advertisements.updated_at,

                categories.name
                    AS category_name,

                categories.slug
                    AS category_slug,

                customers.email
                    AS customer_email,

                profiles.display_name
                    AS customer_display_name,

                profiles.avatar_attachment_id
                    AS customer_avatar_attachment_id,

                cover_images.attachment_id
                    AS cover_attachment_id

            FROM {$this->advertisementsTable}
                AS advertisements

            LEFT JOIN {$this->categoriesTable}
                AS categories
                ON categories.id =
                    advertisements.category_id

            LEFT JOIN {$this->customersTable}
                AS customers
                ON customers.id =
                    advertisements.customer_id

            LEFT JOIN {$this->customerProfilesTable}
                AS profiles
                ON profiles.customer_id =
                    advertisements.customer_id

            LEFT JOIN (
                SELECT
                    advertisement_id,
                    COALESCE(
                        MAX(
                            CASE
                                WHEN is_cover = 1
                                THEN attachment_id
                                ELSE NULL
                            END
                        ),
                        SUBSTRING_INDEX(
                            GROUP_CONCAT(
                                attachment_id
                                ORDER BY
                                    sort_order ASC,
                                    id ASC
                            ),
                            ',',
                            1
                        )
                    ) AS attachment_id

                FROM {$this->imagesTable}

                GROUP BY advertisement_id
            ) AS cover_images
                ON cover_images.advertisement_id =
                    advertisements.id

            {$whereSql}

            ORDER BY
                {$promotionOrderSql}
                {$orderSql},
                advertisements.id DESC

            LIMIT %d
            OFFSET %d
        ";

        $parameters[] =
            $perPage;

        $parameters[] =
            $offset;

        $preparedSql =
            $this->database->prepare(
                $sql,
                ...$parameters
            );

        if (!is_string($preparedSql)) {
            return $this->emptyResult(
                $page,
                $perPage,
                $filters
            );
        }

        $rows =
            $this->database->get_results(
                $preparedSql,
                ARRAY_A
            );

        if (!is_array($rows)) {
            $rows = [];
        }

        $items = [];

        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }

            $items[] =
                $this->normalizeAdvertisement(
                    $row,
                    $promotedIds
                );
        }

        $total =
            $this->countResults(
                $filters
            );

        return [
            'items' =>
                $items,

            'total' =>
                $total,

            'page' =>
                $page,

            'per_page' =>
                $perPage,

            'total_pages' =>
                $total > 0
                    ? (int) ceil(
                        $total
                        / $perPage
                    )
                    : 0,

            'filters' =>
                $filters,
        ];
    }

    /**
     * Busca un anuncio público por ID.
     *
     * Incluye la galería completa.
     *
     * @return array<string, mixed>|null
     */
    public function findPublicById(
        int $advertisementId
    ): ?array {
        if ($advertisementId <= 0) {
            return null;
        }

        $result =
            $this->search(
                [
                    'advertisement_id' =>
                        $advertisementId,
                ],
                1,
                1
            );

        $advertisement =
            $result['items'][0]
            ?? null;

        if (!is_array($advertisement)) {
            return null;
        }

        return $this->enrichAdvertisementDetail(
            $advertisement
        );
    }

    /**
     * Busca un anuncio público por slug.
     *
     * Incluye la galería completa.
     *
     * @return array<string, mixed>|null
     */
    public function findPublicBySlug(
        string $slug
    ): ?array {
        $slug =
            sanitize_title(
                $slug
            );

        if ($slug === '') {
            return null;
        }

        $result =
            $this->search(
                [
                    'slug' =>
                        $slug,
                ],
                1,
                1
            );

        $advertisement =
            $result['items'][0]
            ?? null;

        if (!is_array($advertisement)) {
            return null;
        }

        return $this->enrichAdvertisementDetail(
            $advertisement
        );
    }

    /**
     * Recupera todas las imágenes de un anuncio.
     *
     * Orden:
     *
     * 1. Imagen marcada como portada.
     * 2. sort_order.
     * 3. ID de la relación.
     *
     * @return array<int, array<string, mixed>>
     */
    public function findImagesByAdvertisementId(
        int $advertisementId
    ): array {
        if ($advertisementId <= 0) {
            return [];
        }

        $sql =
            $this->database->prepare(
                "
                SELECT
                    id,
                    advertisement_id,
                    attachment_id,
                    sort_order,
                    is_cover,
                    created_at,
                    updated_at
                FROM {$this->imagesTable}
                WHERE advertisement_id = %d
                ORDER BY
                    is_cover DESC,
                    sort_order ASC,
                    id ASC
                ",
                $advertisementId
            );

        if (!is_string($sql)) {
            return [];
        }

        $rows =
            $this->database->get_results(
                $sql,
                ARRAY_A
            );

        if (!is_array($rows)) {
            return [];
        }

        $images = [];

        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }

            $attachmentId =
                (int) (
                    $row['attachment_id']
                    ?? 0
                );

            if ($attachmentId <= 0) {
                continue;
            }

            $fullUrl =
                $this->getAttachmentUrl(
                    $attachmentId,
                    'full'
                );

            $largeUrl =
                $this->getAttachmentUrl(
                    $attachmentId,
                    'large'
                );

            $mediumUrl =
                $this->getAttachmentUrl(
                    $attachmentId,
                    'medium'
                );

            $thumbnailUrl =
                $this->getAttachmentUrl(
                    $attachmentId,
                    'thumbnail'
                );

            $metadata =
                wp_get_attachment_metadata(
                    $attachmentId
                );

            $altText =
                trim(
                    (string) get_post_meta(
                        $attachmentId,
                        '_wp_attachment_image_alt',
                        true
                    )
                );

            $attachmentTitle =
                get_the_title(
                    $attachmentId
                );

            $images[] = [
                'id' =>
                    (int) (
                        $row['id']
                        ?? 0
                    ),

                'advertisement_id' =>
                    $advertisementId,

                'attachment_id' =>
                    $attachmentId,

                'sort_order' =>
                    (int) (
                        $row['sort_order']
                        ?? 0
                    ),

                'is_cover' =>
                    (int) (
                        $row['is_cover']
                        ?? 0
                    ) === 1,

                'alt' =>
                    $altText,

                'title' =>
                    is_string($attachmentTitle)
                        ? $attachmentTitle
                        : '',

                'thumbnail_url' =>
                    $thumbnailUrl,

                'medium_url' =>
                    $mediumUrl,

                'large_url' =>
                    $largeUrl !== ''
                        ? $largeUrl
                        : $fullUrl,

                'full_url' =>
                    $fullUrl,

                'width' =>
                    is_array($metadata)
                    && isset($metadata['width'])
                        ? (int) $metadata['width']
                        : null,

                'height' =>
                    is_array($metadata)
                    && isset($metadata['height'])
                        ? (int) $metadata['height']
                        : null,

                'created_at' =>
                    (string) (
                        $row['created_at']
                        ?? ''
                    ),

                'updated_at' =>
                    (string) (
                        $row['updated_at']
                        ?? ''
                    ),
            ];
        }

        return $images;
    }

    /**
     * Devuelve categorías públicas habilitadas.
     *
     * @return array<int, array<string, mixed>>
     */
    public function findCategories(): array
    {
        $rows =
            $this->database->get_results(
                "
                SELECT
                    id,
                    parent_id,
                    name,
                    slug,
                    description,
                    marketplace_allowed,
                    sort_order
                FROM {$this->categoriesTable}
                WHERE is_active = 1
                  AND marketplace_allowed = 1
                ORDER BY
                    sort_order ASC,
                    name ASC,
                    id ASC
                ",
                ARRAY_A
            );

        if (!is_array($rows)) {
            return [];
        }

        $categories = [];

        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }

            $categories[] = [
                'id' =>
                    (int) (
                        $row['id']
                        ?? 0
                    ),

                'parent_id' =>
                    isset($row['parent_id'])
                    && $row['parent_id'] !== null
                        ? (int) $row['parent_id']
                        : null,

                'name' =>
                    (string) (
                        $row['name']
                        ?? ''
                    ),

                'slug' =>
                    (string) (
                        $row['slug']
                        ?? ''
                    ),

                'description' =>
                    (string) (
                        $row['description']
                        ?? ''
                    ),

                'marketplace_allowed' =>
                    (int) (
                        $row['marketplace_allowed']
                        ?? 0
                    ) === 1,

                'sort_order' =>
                    (int) (
                        $row['sort_order']
                        ?? 0
                    ),
            ];
        }

        return $categories;
    }

    /**
     * Devuelve las marcas presentes en anuncios públicos.
     *
     * @return array<int, string>
     */
    public function findBrands(
        ?int $islandId = null
    ): array {
        $parameters = [
            AdvertisementStatus::ACTIVE,
            AdvertisementStatus::RESERVED,
        ];

        $whereIsland = '';

        if (
            $islandId !== null
            && $islandId > 0
        ) {
            $whereIsland =
                'AND island_id = %d';

            $parameters[] =
                $islandId;
        }

        $sql = "
            SELECT DISTINCT brand
            FROM {$this->advertisementsTable}
            WHERE status IN (%s, %s)
              AND brand IS NOT NULL
              AND brand <> ''
              {$whereIsland}
            ORDER BY brand ASC
        ";

        $prepared =
            $this->database->prepare(
                $sql,
                ...$parameters
            );

        if (!is_string($prepared)) {
            return [];
        }

        $brands =
            $this->database->get_col(
                $prepared
            );

        if (!is_array($brands)) {
            return [];
        }

        return array_values(
            array_filter(
                array_map(
                    static fn (
                        mixed $brand
                    ): string =>
                        trim(
                            (string) $brand
                        ),
                    $brands
                ),
                static fn (
                    string $brand
                ): bool =>
                    $brand !== ''
            )
        );
    }

    /**
     * Añade los datos exclusivos de la ficha pública.
     *
     * @param array<string, mixed> $advertisement
     *
     * @return array<string, mixed>
     */
    private function enrichAdvertisementDetail(
        array $advertisement
    ): array {
        $advertisementId =
            (int) (
                $advertisement['id']
                ?? 0
            );

        $images =
            $this->findImagesByAdvertisementId(
                $advertisementId
            );

        /*
         * Si no existe una relación en dsm_ad_images pero el
         * listado resolvió una portada, se conserva como respaldo.
         */
        if (
            $images === []
            && !empty(
                $advertisement[
                    'cover_attachment_id'
                ]
            )
        ) {
            $attachmentId =
                (int) $advertisement[
                    'cover_attachment_id'
                ];

            $images[] = [
                'id' =>
                    0,

                'advertisement_id' =>
                    $advertisementId,

                'attachment_id' =>
                    $attachmentId,

                'sort_order' =>
                    0,

                'is_cover' =>
                    true,

                'alt' =>
                    trim(
                        (string) get_post_meta(
                            $attachmentId,
                            '_wp_attachment_image_alt',
                            true
                        )
                    ),

                'title' =>
                    (string) get_the_title(
                        $attachmentId
                    ),

                'thumbnail_url' =>
                    $this->getAttachmentUrl(
                        $attachmentId,
                        'thumbnail'
                    ),

                'medium_url' =>
                    $this->getAttachmentUrl(
                        $attachmentId,
                        'medium'
                    ),

                'large_url' =>
                    $this->getAttachmentUrl(
                        $attachmentId,
                        'large'
                    ),

                'full_url' =>
                    $this->getAttachmentUrl(
                        $attachmentId,
                        'full'
                    ),

                'width' =>
                    null,

                'height' =>
                    null,

                'created_at' =>
                    '',

                'updated_at' =>
                    '',
            ];
        }

        $locationData =
            apply_filters(
                'dsm_advertisement_location_data',
                [
                    'island_id' =>
                        $advertisement[
                            'island_id'
                        ]
                        ?? null,

                    'municipality_id' =>
                        $advertisement[
                            'municipality_id'
                        ]
                        ?? null,

                    'island_name' =>
                        '',

                    'municipality_name' =>
                        '',
                ],
                $advertisementId
            );

        if (!is_array($locationData)) {
            $locationData = [];
        }

        $sellerData =
            apply_filters(
                'dsm_advertisement_seller_public_data',
                [
                    'customer_id' =>
                        (int) (
                            $advertisement[
                                'customer_id'
                            ]
                            ?? 0
                        ),

                    'display_name' =>
                        (string) (
                            $advertisement[
                                'customer_display_name'
                            ]
                            ?? ''
                        ),

                    'avatar_url' =>
                        (string) (
                            $advertisement[
                                'customer_avatar_url'
                            ]
                            ?? ''
                        ),

                    'profile_url' =>
                        '',

                    'member_since' =>
                        '',

                    'rating_average' =>
                        null,

                    'rating_count' =>
                        0,

                    'allows_phone_calls' =>
                        false,

                    'allows_whatsapp' =>
                        false,

                    'has_valid_contact' =>
                        false,

                    'phone_call_url' =>
                        '',

                    'whatsapp_url' =>
                        '',
                ],
                $advertisementId
            );

        if (!is_array($sellerData)) {
            $sellerData = [];
        }

        $promotionData =
            apply_filters(
                'dsm_advertisement_promotion_data',
                null,
                $advertisementId
            );

        return array_merge(
            $advertisement,
            [
                'images' =>
                    $images,

                'image_count' =>
                    count($images),

                'location' =>
                    [
                        'island_id' =>
                            isset(
                                $locationData[
                                    'island_id'
                                ]
                            )
                            && $locationData[
                                'island_id'
                            ] !== null
                                ? (int) $locationData[
                                    'island_id'
                                ]
                                : null,

                        'municipality_id' =>
                            isset(
                                $locationData[
                                    'municipality_id'
                                ]
                            )
                            && $locationData[
                                'municipality_id'
                            ] !== null
                                ? (int) $locationData[
                                    'municipality_id'
                                ]
                                : null,

                        'island_name' =>
                            sanitize_text_field(
                                (string) (
                                    $locationData[
                                        'island_name'
                                    ]
                                    ?? ''
                                )
                            ),

                        'municipality_name' =>
                            sanitize_text_field(
                                (string) (
                                    $locationData[
                                        'municipality_name'
                                    ]
                                    ?? ''
                                )
                            ),
                    ],

                'seller' =>
                    [
                        'customer_id' =>
                            max(
                                0,
                                (int) (
                                    $sellerData[
                                        'customer_id'
                                    ]
                                    ?? $advertisement[
                                        'customer_id'
                                    ]
                                    ?? 0
                                )
                            ),

                        'display_name' =>
                            sanitize_text_field(
                                (string) (
                                    $sellerData[
                                        'display_name'
                                    ]
                                    ?? ''
                                )
                            ),

                        'avatar_url' =>
                            esc_url_raw(
                                (string) (
                                    $sellerData[
                                        'avatar_url'
                                    ]
                                    ?? ''
                                )
                            ),

                        'profile_url' =>
                            esc_url_raw(
                                (string) (
                                    $sellerData[
                                        'profile_url'
                                    ]
                                    ?? ''
                                )
                            ),

                        'member_since' =>
                            sanitize_text_field(
                                (string) (
                                    $sellerData[
                                        'member_since'
                                    ]
                                    ?? ''
                                )
                            ),

                        'rating_average' =>
                            isset(
                                $sellerData[
                                    'rating_average'
                                ]
                            )
                            && is_numeric(
                                $sellerData[
                                    'rating_average'
                                ]
                            )
                                ? (float) $sellerData[
                                    'rating_average'
                                ]
                                : null,

                        'rating_count' =>
                            max(
                                0,
                                (int) (
                                    $sellerData[
                                        'rating_count'
                                    ]
                                    ?? 0
                                )
                            ),

                        'allows_phone_calls' =>
                            !empty(
                                $sellerData[
                                    'allows_phone_calls'
                                ]
                            ),

                        'allows_whatsapp' =>
                            !empty(
                                $sellerData[
                                    'allows_whatsapp'
                                ]
                            ),

                        'has_valid_contact' =>
                            !empty(
                                $sellerData[
                                    'has_valid_contact'
                                ]
                            ),

                        'phone_call_url' =>
                            esc_url_raw(
                                (string) (
                                    $sellerData[
                                        'phone_call_url'
                                    ]
                                    ?? ''
                                )
                            ),

                        'whatsapp_url' =>
                            esc_url_raw(
                                (string) (
                                    $sellerData[
                                        'whatsapp_url'
                                    ]
                                    ?? ''
                                )
                            ),
                    ],

                'promotion' =>
                    is_array($promotionData)
                        ? $promotionData
                        : null,
            ]
        );
    }

    /**
     * @param array<string, mixed> $filters
     *
     * @return array<string, mixed>
     */
    private function normalizeFilters(
        array $filters
    ): array {
        $orderby =
            sanitize_key(
                (string) (
                    $filters['orderby']
                    ?? 'published_at'
                )
            );

        $allowedOrderBy = [
            'published_at',
            'updated_at',
            'price',
            'title',
        ];

        if (
            !in_array(
                $orderby,
                $allowedOrderBy,
                true
            )
        ) {
            $orderby =
                'published_at';
        }

        $order =
            strtoupper(
                sanitize_key(
                    (string) (
                        $filters['order']
                        ?? 'DESC'
                    )
                )
            );

        if (
            !in_array(
                $order,
                [
                    'ASC',
                    'DESC',
                ],
                true
            )
        ) {
            $order =
                'DESC';
        }

        $minPrice =
            $this->normalizePrice(
                $filters['min_price']
                ?? null
            );

        $maxPrice =
            $this->normalizePrice(
                $filters['max_price']
                ?? null
            );

        if (
            $minPrice !== null
            && $maxPrice !== null
            && $minPrice > $maxPrice
        ) {
            [
                $minPrice,
                $maxPrice,
            ] = [
                $maxPrice,
                $minPrice,
            ];
        }

        return [
            'advertisement_id' =>
                max(
                    0,
                    (int) (
                        $filters[
                            'advertisement_id'
                        ]
                        ?? 0
                    )
                ),

            'slug' =>
                sanitize_title(
                    (string) (
                        $filters['slug']
                        ?? ''
                    )
                ),

            'search' =>
                sanitize_text_field(
                    (string) (
                        $filters['search']
                        ?? ''
                    )
                ),

            'island_id' =>
                max(
                    0,
                    (int) (
                        $filters['island_id']
                        ?? 0
                    )
                ),

            'municipality_id' =>
                max(
                    0,
                    (int) (
                        $filters[
                            'municipality_id'
                        ]
                        ?? 0
                    )
                ),

            'category_id' =>
                max(
                    0,
                    (int) (
                        $filters['category_id']
                        ?? 0
                    )
                ),

            'customer_id' =>
                max(
                    0,
                    (int) (
                        $filters['customer_id']
                        ?? 0
                    )
                ),

            'store_id' =>
                max(
                    0,
                    (int) (
                        $filters['store_id']
                        ?? 0
                    )
                ),

            'brand' =>
                sanitize_text_field(
                    (string) (
                        $filters['brand']
                        ?? ''
                    )
                ),

            'condition_code' =>
                sanitize_key(
                    (string) (
                        $filters[
                            'condition_code'
                        ]
                        ?? ''
                    )
                ),

            'min_price' =>
                $minPrice,

            'max_price' =>
                $maxPrice,

            'orderby' =>
                $orderby,

            'order' =>
                $order,
        ];
    }

    /**
     * Construye las condiciones de la consulta pública.
     *
     * @param array<string, mixed>         $filters
     * @param array<int, int|float|string> $parameters
     */
    private function buildWhereSql(
        array $filters,
        array &$parameters
    ): string {
        $conditions = [
            'advertisements.status IN (%s, %s)',
        ];

        $parameters[] =
            AdvertisementStatus::ACTIVE;

        $parameters[] =
            AdvertisementStatus::RESERVED;

        if (
            (int) $filters[
                'advertisement_id'
            ] > 0
        ) {
            $conditions[] =
                'advertisements.id = %d';

            $parameters[] =
                (int) $filters[
                    'advertisement_id'
                ];
        }

        if ($filters['slug'] !== '') {
            $conditions[] =
                'advertisements.slug = %s';

            $parameters[] =
                $filters['slug'];
        }

        if ($filters['island_id'] > 0) {
            $conditions[] =
                'advertisements.island_id = %d';

            $parameters[] =
                $filters['island_id'];
        }

        if (
            $filters['municipality_id']
            > 0
        ) {
            $conditions[] =
                'advertisements.municipality_id = %d';

            $parameters[] =
                $filters[
                    'municipality_id'
                ];
        }

        if ($filters['category_id'] > 0) {
            $conditions[] =
                'advertisements.category_id = %d';

            $parameters[] =
                $filters['category_id'];
        }

        if ($filters['customer_id'] > 0) {
            $conditions[] =
                'advertisements.customer_id = %d';

            $parameters[] =
                $filters['customer_id'];
        }

        if ($filters['store_id'] > 0) {
            $conditions[] =
                'advertisements.store_id = %d';

            $parameters[] =
                $filters['store_id'];
        }

        if ($filters['brand'] !== '') {
            $conditions[] =
                'advertisements.brand = %s';

            $parameters[] =
                $filters['brand'];
        }

        if (
            $filters['condition_code']
            !== ''
        ) {
            $conditions[] =
                'advertisements.condition_code = %s';

            $parameters[] =
                $filters[
                    'condition_code'
                ];
        }

        if ($filters['min_price'] !== null) {
            $conditions[] =
                'advertisements.price >= %f';

            $parameters[] =
                $filters['min_price'];
        }

        if ($filters['max_price'] !== null) {
            $conditions[] =
                'advertisements.price <= %f';

            $parameters[] =
                $filters['max_price'];
        }

        if ($filters['search'] !== '') {
            $like =
                '%'
                . $this->database->esc_like(
                    $filters['search']
                )
                . '%';

            $conditions[] = '
                (
                    advertisements.title LIKE %s
                    OR advertisements.description LIKE %s
                    OR advertisements.brand LIKE %s
                    OR categories.name LIKE %s
                )
            ';

            for (
                $index = 0;
                $index < 4;
                $index++
            ) {
                $parameters[] =
                    $like;
            }
        }

        return 'WHERE '
            . implode(
                ' AND ',
                $conditions
            );
    }

    /**
     * @param array<string, mixed> $filters
     */
    private function countResults(
        array $filters
    ): int {
        $parameters = [];

        $whereSql =
            $this->buildWhereSql(
                $filters,
                $parameters
            );

        $sql = "
            SELECT COUNT(*)
            FROM {$this->advertisementsTable}
                AS advertisements

            LEFT JOIN {$this->categoriesTable}
                AS categories
                ON categories.id =
                    advertisements.category_id

            {$whereSql}
        ";

        $prepared =
            $this->database->prepare(
                $sql,
                ...$parameters
            );

        if (!is_string($prepared)) {
            return 0;
        }

        return max(
            0,
            (int) $this->database->get_var(
                $prepared
            )
        );
    }

    /**
     * Recibe los anuncios promocionados desde dsm-promocionar.
     *
     * @param array<string, mixed> $filters
     *
     * @return array<int, int>
     */
    private function getPromotedAdvertisementIds(
        array $filters
    ): array {
        $ids =
            apply_filters(
                'dsm_promoted_advertisement_ids',
                [],
                $filters
            );

        if (!is_array($ids)) {
            return [];
        }

        $ids =
            array_map(
                'absint',
                $ids
            );

        $ids =
            array_filter(
                $ids,
                static fn (
                    int $id
                ): bool =>
                    $id > 0
            );

        return array_values(
            array_unique(
                $ids
            )
        );
    }

    /**
     * Los destacados solo se priorizan en la ordenación inicial.
     *
     * Cuando el usuario ordena manualmente por precio, título
     * o actualización, se respeta su elección.
     *
     * @param array<int, int>      $promotedIds
     * @param array<string, mixed> $filters
     */
    private function buildPromotionOrderSql(
        array $promotedIds,
        array $filters
    ): string {
        if (
            $promotedIds === []
            || $filters['orderby']
                !== 'published_at'
        ) {
            return '';
        }

        $safeIds =
            implode(
                ',',
                array_map(
                    'absint',
                    $promotedIds
                )
            );

        if ($safeIds === '') {
            return '';
        }

        return "
            CASE
                WHEN advertisements.id
                    IN ({$safeIds})
                THEN 0
                ELSE 1
            END ASC,
        ";
    }

    /**
     * @param array<string, mixed> $filters
     */
    private function buildOrderSql(
        array $filters
    ): string {
        $column =
            match ($filters['orderby']) {
                'price' =>
                    'advertisements.price',

                'title' =>
                    'advertisements.title',

                'updated_at' =>
                    'advertisements.updated_at',

                default =>
                    'advertisements.published_at',
            };

        $order =
            $filters['order']
            === 'ASC'
                ? 'ASC'
                : 'DESC';

        return "{$column} {$order}";
    }

    /**
     * @param array<string, mixed> $row
     * @param array<int, int>      $promotedIds
     *
     * @return array<string, mixed>
     */
    private function normalizeAdvertisement(
        array $row,
        array $promotedIds
    ): array {
        $advertisementId =
            (int) (
                $row['id']
                ?? 0
            );

        $coverAttachmentId =
            isset(
                $row['cover_attachment_id']
            )
            && $row[
                'cover_attachment_id'
            ] !== null
                ? (int) $row[
                    'cover_attachment_id'
                ]
                : 0;

        $avatarAttachmentId =
            isset(
                $row[
                    'customer_avatar_attachment_id'
                ]
            )
            && $row[
                'customer_avatar_attachment_id'
            ] !== null
                ? (int) $row[
                    'customer_avatar_attachment_id'
                ]
                : 0;

        return array_merge(
            $row,
            [
                'id' =>
                    $advertisementId,

                'customer_id' =>
                    (int) (
                        $row['customer_id']
                        ?? 0
                    ),

                'store_id' =>
                    isset($row['store_id'])
                    && $row['store_id'] !== null
                        ? (int) $row['store_id']
                        : null,

                'category_id' =>
                    (int) (
                        $row['category_id']
                        ?? 0
                    ),

                'island_id' =>
                    isset($row['island_id'])
                    && $row['island_id'] !== null
                        ? (int) $row['island_id']
                        : null,

                'municipality_id' =>
                    isset(
                        $row['municipality_id']
                    )
                    && $row[
                        'municipality_id'
                    ] !== null
                        ? (int) $row[
                            'municipality_id'
                        ]
                        : null,

                'price' =>
                    (float) (
                        $row['price']
                        ?? 0
                    ),

                'original_price' =>
                    isset(
                        $row['original_price']
                    )
                    && $row[
                        'original_price'
                    ] !== null
                        ? (float) $row[
                            'original_price'
                        ]
                        : null,

                'is_promoted' =>
                    in_array(
                        $advertisementId,
                        $promotedIds,
                        true
                    ),

                'is_reserved' =>
                    (
                        $row['status']
                        ?? ''
                    )
                    === AdvertisementStatus::
                        RESERVED,

                'cover_attachment_id' =>
                    $coverAttachmentId > 0
                        ? $coverAttachmentId
                        : null,

                'cover_thumbnail_url' =>
                    $this->getAttachmentUrl(
                        $coverAttachmentId,
                        'medium'
                    ),

                'cover_full_url' =>
                    $this->getAttachmentUrl(
                        $coverAttachmentId,
                        'full'
                    ),

                'customer_avatar_url' =>
                    $this->getAttachmentUrl(
                        $avatarAttachmentId,
                        'thumbnail'
                    ),

                'public_url' =>
                    $this->buildPublicUrl(
                        (string) (
                            $row['slug']
                            ?? ''
                        )
                    ),
            ]
        );
    }

    private function getAttachmentUrl(
        int $attachmentId,
        string $size
    ): string {
        if ($attachmentId <= 0) {
            return '';
        }

        $url =
            wp_get_attachment_image_url(
                $attachmentId,
                $size
            );

        return is_string($url)
            ? $url
            : '';
    }

    private function buildPublicUrl(
        string $slug
    ): string {
        $slug =
            sanitize_title(
                $slug
            );

        if ($slug === '') {
            return '';
        }

        $url =
            home_url(
                '/anuncio/'
                . rawurlencode($slug)
                . '/'
            );

        return (string) apply_filters(
            'dsm_advertisement_public_url',
            $url,
            $slug
        );
    }

    private function normalizePrice(
        mixed $value
    ): ?float {
        if (
            $value === null
            || $value === ''
        ) {
            return null;
        }

        if (is_string($value)) {
            $value =
                str_replace(
                    ',',
                    '.',
                    trim($value)
                );
        }

        if (!is_numeric($value)) {
            return null;
        }

        return max(
            0.0,
            round(
                (float) $value,
                2
            )
        );
    }

    /**
     * @param array<string, mixed> $filters
     *
     * @return array{
     *     items:array<int, array<string, mixed>>,
     *     total:int,
     *     page:int,
     *     per_page:int,
     *     total_pages:int,
     *     filters:array<string, mixed>
     * }
     */
    private function emptyResult(
        int $page,
        int $perPage,
        array $filters
    ): array {
        return [
            'items' =>
                [],

            'total' =>
                0,

            'page' =>
                $page,

            'per_page' =>
                $perPage,

            'total_pages' =>
                0,

            'filters' =>
                $filters,
        ];
    }
}