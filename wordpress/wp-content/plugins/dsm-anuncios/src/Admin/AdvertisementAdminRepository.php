<?php

declare(strict_types=1);

namespace DSM\Anuncios\Admin;

use DSM\Anuncios\Advertisement\AdvertisementStatus;
use wpdb;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Repositorio de lectura para la administración de anuncios.
 *
 * Esta clase no modifica el ciclo de vida de los anuncios.
 * Las operaciones de publicación, rechazo, reserva o cierre
 * se delegarán en AdvertisementAdminController.
 */
final class AdvertisementAdminRepository
{
    private wpdb $database;

    private string $advertisementsTable;

    private string $imagesTable;

    private string $categoriesTable;

    private string $statusHistoryTable;

    private string $customersTable;

    private string $customerProfilesTable;

    public function __construct()
    {
        global $wpdb;

        $this->database = $wpdb;

        $this->advertisementsTable =
            $wpdb->prefix
            . 'dsm_ads';

        $this->imagesTable =
            $wpdb->prefix
            . 'dsm_ad_images';

        $this->categoriesTable =
            $wpdb->prefix
            . 'dsm_categories';

        $this->statusHistoryTable =
            $wpdb->prefix
            . 'dsm_ad_status_history';

        $this->customersTable =
            $wpdb->prefix
            . 'dsm_customers';

        $this->customerProfilesTable =
            $wpdb->prefix
            . 'dsm_customer_profiles';
    }

    /**
     * Obtiene anuncios paginados para el panel administrativo.
     *
     * Filtros permitidos:
     *
     * - search
     * - status
     * - category_id
     * - customer_id
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
     *     total_pages:int
     * }
     */
    public function paginate(
        array $filters = [],
        int $page = 1,
        int $perPage = 20
    ): array {
        $page =
            max(
                1,
                $page
            );

        $perPage =
            min(
                100,
                max(
                    1,
                    $perPage
                )
            );

        $offset =
            ($page - 1)
            * $perPage;

        $normalizedFilters =
            $this->normalizeFilters(
                $filters
            );

        $parameters = [];

        $whereSql =
            $this->buildWhereSql(
                $normalizedFilters,
                $parameters
            );

        $orderColumn =
            $this->resolveOrderColumn(
                $normalizedFilters[
                    'orderby'
                ]
            );

        $order =
            $normalizedFilters[
                'order'
            ];

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
                advertisements.rejection_reason,
                advertisements.reserved_at,
                advertisements.published_at,
                advertisements.closed_at,
                advertisements.created_at,
                advertisements.updated_at,

                categories.name
                    AS category_name,

                customers.email
                    AS customer_email,

                profiles.display_name
                    AS customer_display_name,

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
                    MAX(
                        CASE
                            WHEN is_cover = 1
                            THEN attachment_id
                            ELSE NULL
                        END
                    ) AS attachment_id
                FROM {$this->imagesTable}
                GROUP BY advertisement_id
            ) AS cover_images
                ON cover_images.advertisement_id =
                    advertisements.id

            {$whereSql}

            ORDER BY
                {$orderColumn}
                {$order},
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
            ];
        }

        $items =
            $this->database->get_results(
                $preparedSql,
                ARRAY_A
            );

        if (!is_array($items)) {
            $items = [];
        }

        foreach ($items as &$item) {
            $item =
                $this->normalizeAdvertisementRow(
                    $item
                );
        }

        unset($item);

        $total =
            $this->countFiltered(
                $normalizedFilters
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
                $perPage > 0
                    ? (int) ceil(
                        $total
                        / $perPage
                    )
                    : 0,
        ];
    }

    /**
     * Busca un anuncio completo por ID.
     *
     * @return array<string, mixed>|null
     */
    public function findById(
        int $advertisementId
    ): ?array {
        if ($advertisementId <= 0) {
            return null;
        }

        $sql =
            $this->database->prepare(
                "
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
                    advertisements.rejection_reason,
                    advertisements.reserved_at,
                    advertisements.published_at,
                    advertisements.closed_at,
                    advertisements.created_at,
                    advertisements.updated_at,

                    categories.name
                        AS category_name,

                    categories.slug
                        AS category_slug,

                    customers.email
                        AS customer_email,

                    customers.status
                        AS customer_status,

                    customers.email_verified_at
                        AS customer_email_verified_at,

                    profiles.display_name
                        AS customer_display_name,

                    profiles.phone
                        AS customer_phone,

                    profiles.whatsapp_phone
                        AS customer_whatsapp_phone,

                    profiles.avatar_attachment_id
                        AS customer_avatar_attachment_id,

                    profiles.island_id
                        AS customer_island_id,

                    profiles.municipality_id
                        AS customer_municipality_id

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

                WHERE advertisements.id = %d

                LIMIT 1
                ",
                $advertisementId
            );

        if (!is_string($sql)) {
            return null;
        }

        $advertisement =
            $this->database->get_row(
                $sql,
                ARRAY_A
            );

        if (!is_array($advertisement)) {
            return null;
        }

        $advertisement =
            $this->normalizeAdvertisementRow(
                $advertisement
            );

        $advertisement['images'] =
            $this->findImages(
                $advertisementId
            );

        $advertisement['history'] =
            $this->findStatusHistory(
                $advertisementId
            );

        return $advertisement;
    }

    /**
     * Devuelve contadores por estado.
     *
     * @return array<string, int>
     */
    public function countByStatus(): array
    {
        $counts = [];

        foreach (
            AdvertisementStatus::all()
            as $status
        ) {
            $counts[$status] = 0;
        }

        $rows =
            $this->database->get_results(
                "
                SELECT
                    status,
                    COUNT(*) AS total
                FROM {$this->advertisementsTable}
                GROUP BY status
                ",
                ARRAY_A
            );

        if (!is_array($rows)) {
            return $counts;
        }

        foreach ($rows as $row) {
            $status =
                sanitize_key(
                    (string) (
                        $row['status']
                        ?? ''
                    )
                );

            if (
                !array_key_exists(
                    $status,
                    $counts
                )
            ) {
                continue;
            }

            $counts[$status] =
                max(
                    0,
                    (int) (
                        $row['total']
                        ?? 0
                    )
                );
        }

        return $counts;
    }

    /**
     * Número total de anuncios.
     */
    public function countAll(): int
    {
        return max(
            0,
            (int) $this->database->get_var(
                "
                SELECT COUNT(*)
                FROM {$this->advertisementsTable}
                "
            )
        );
    }

    /**
     * Categorías disponibles para filtros administrativos.
     *
     * @return array<int, array{id:int,name:string}>
     */
    public function findCategories(): array
    {
        $rows =
            $this->database->get_results(
                "
                SELECT
                    id,
                    name
                FROM {$this->categoriesTable}
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

        return array_map(
            static function (
                array $row
            ): array {
                return [
                    'id' =>
                        (int) (
                            $row['id']
                            ?? 0
                        ),

                    'name' =>
                        (string) (
                            $row['name']
                            ?? ''
                        ),
                ];
            },
            $rows
        );
    }

    /**
     * Historial de estados.
     *
     * @return array<int, array<string, mixed>>
     */
    public function findStatusHistory(
        int $advertisementId
    ): array {
        if ($advertisementId <= 0) {
            return [];
        }

        $sql =
            $this->database->prepare(
                "
                SELECT
                    history.id,
                    history.advertisement_id,
                    history.previous_status,
                    history.new_status,
                    history.changed_by_customer_id,
                    history.changed_by_user_id,
                    history.notes,
                    history.created_at,

                    customers.email
                        AS changed_by_customer_email,

                    profiles.display_name
                        AS changed_by_customer_name,

                    users.user_login
                        AS changed_by_user_login,

                    users.display_name
                        AS changed_by_user_name

                FROM {$this->statusHistoryTable}
                    AS history

                LEFT JOIN {$this->customersTable}
                    AS customers
                    ON customers.id =
                        history.changed_by_customer_id

                LEFT JOIN {$this->customerProfilesTable}
                    AS profiles
                    ON profiles.customer_id =
                        history.changed_by_customer_id

                LEFT JOIN {$this->database->users}
                    AS users
                    ON users.ID =
                        history.changed_by_user_id

                WHERE history.advertisement_id = %d

                ORDER BY
                    history.created_at DESC,
                    history.id DESC
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

        foreach ($rows as &$row) {
            $row['id'] =
                (int) (
                    $row['id']
                    ?? 0
                );

            $row['advertisement_id'] =
                (int) (
                    $row['advertisement_id']
                    ?? 0
                );

            $row['changed_by_customer_id'] =
                isset(
                    $row[
                        'changed_by_customer_id'
                    ]
                )
                && $row[
                    'changed_by_customer_id'
                ] !== null
                    ? (int) $row[
                        'changed_by_customer_id'
                    ]
                    : null;

            $row['changed_by_user_id'] =
                isset(
                    $row[
                        'changed_by_user_id'
                    ]
                )
                && $row[
                    'changed_by_user_id'
                ] !== null
                    ? (int) $row[
                        'changed_by_user_id'
                    ]
                    : null;
        }

        unset($row);

        return $rows;
    }

    /**
     * Imágenes del anuncio.
     *
     * @return array<int, array<string, mixed>>
     */
    public function findImages(
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

        foreach ($rows as &$row) {
            $attachmentId =
                (int) (
                    $row['attachment_id']
                    ?? 0
                );

            $row['id'] =
                (int) (
                    $row['id']
                    ?? 0
                );

            $row['advertisement_id'] =
                (int) (
                    $row['advertisement_id']
                    ?? 0
                );

            $row['attachment_id'] =
                $attachmentId;

            $row['sort_order'] =
                (int) (
                    $row['sort_order']
                    ?? 0
                );

            $row['is_cover'] =
                (int) (
                    $row['is_cover']
                    ?? 0
                ) === 1;

            $row['thumbnail_url'] =
                $attachmentId > 0
                    ? (
                        wp_get_attachment_image_url(
                            $attachmentId,
                            'thumbnail'
                        )
                        ?: ''
                    )
                    : '';

            $row['medium_url'] =
                $attachmentId > 0
                    ? (
                        wp_get_attachment_image_url(
                            $attachmentId,
                            'medium'
                        )
                        ?: ''
                    )
                    : '';

            $row['full_url'] =
                $attachmentId > 0
                    ? (
                        wp_get_attachment_image_url(
                            $attachmentId,
                            'full'
                        )
                        ?: ''
                    )
                    : '';
        }

        unset($row);

        return $rows;
    }

    /**
     * Cuenta resultados aplicando filtros.
     *
     * @param array{
     *     search:string,
     *     status:string,
     *     category_id:int,
     *     customer_id:int,
     *     orderby:string,
     *     order:string
     * } $filters
     */
    private function countFiltered(
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

            LEFT JOIN {$this->customersTable}
                AS customers
                ON customers.id =
                    advertisements.customer_id

            LEFT JOIN {$this->customerProfilesTable}
                AS profiles
                ON profiles.customer_id =
                    advertisements.customer_id

            {$whereSql}
        ";

        if ($parameters !== []) {
            $preparedSql =
                $this->database->prepare(
                    $sql,
                    ...$parameters
                );

            if (!is_string($preparedSql)) {
                return 0;
            }

            $sql = $preparedSql;
        }

        return max(
            0,
            (int) $this->database->get_var(
                $sql
            )
        );
    }

    /**
     * @param array<string, mixed> $filters
     *
     * @return array{
     *     search:string,
     *     status:string,
     *     category_id:int,
     *     customer_id:int,
     *     orderby:string,
     *     order:string
     * }
     */
    private function normalizeFilters(
        array $filters
    ): array {
        $status =
            sanitize_key(
                (string) (
                    $filters['status']
                    ?? ''
                )
            );

        if (
            $status !== ''
            && !AdvertisementStatus::isValid(
                $status
            )
        ) {
            $status = '';
        }

        $orderby =
            sanitize_key(
                (string) (
                    $filters['orderby']
                    ?? 'created_at'
                )
            );

        $allowedOrderColumns = [
            'id',
            'title',
            'price',
            'status',
            'category',
            'customer',
            'created_at',
            'updated_at',
            'published_at',
        ];

        if (
            !in_array(
                $orderby,
                $allowedOrderColumns,
                true
            )
        ) {
            $orderby =
                'created_at';
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

        return [
            'search' =>
                sanitize_text_field(
                    (string) (
                        $filters['search']
                        ?? ''
                    )
                ),

            'status' =>
                $status,

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

            'orderby' =>
                $orderby,

            'order' =>
                $order,
        ];
    }

    /**
     * @param array{
     *     search:string,
     *     status:string,
     *     category_id:int,
     *     customer_id:int,
     *     orderby:string,
     *     order:string
     * } $filters
     * @param array<int, int|string> $parameters
     */
    private function buildWhereSql(
        array $filters,
        array &$parameters
    ): string {
        $conditions = [];

        if ($filters['status'] !== '') {
            $conditions[] =
                'advertisements.status = %s';

            $parameters[] =
                $filters['status'];
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

        if ($filters['search'] !== '') {
            $like =
                '%'
                . $this->database->esc_like(
                    $filters['search']
                )
                . '%';

            $conditions[] =
                '(
                    advertisements.title LIKE %s
                    OR advertisements.slug LIKE %s
                    OR advertisements.description LIKE %s
                    OR advertisements.brand LIKE %s
                    OR categories.name LIKE %s
                    OR customers.email LIKE %s
                    OR profiles.display_name LIKE %s
                )';

            for ($index = 0; $index < 7; $index++) {
                $parameters[] =
                    $like;
            }
        }

        if ($conditions === []) {
            return '';
        }

        return 'WHERE '
            . implode(
                ' AND ',
                $conditions
            );
    }

    private function resolveOrderColumn(
        string $orderby
    ): string {
        return match ($orderby) {
            'id' =>
                'advertisements.id',

            'title' =>
                'advertisements.title',

            'price' =>
                'advertisements.price',

            'status' =>
                'advertisements.status',

            'category' =>
                'categories.name',

            'customer' =>
                'profiles.display_name',

            'updated_at' =>
                'advertisements.updated_at',

            'published_at' =>
                'advertisements.published_at',

            default =>
                'advertisements.created_at',
        };
    }

    /**
     * @param array<string, mixed> $row
     *
     * @return array<string, mixed>
     */
    private function normalizeAdvertisementRow(
        array $row
    ): array {
        $integerFields = [
            'id',
            'customer_id',
            'category_id',
        ];

        foreach (
            $integerFields
            as $field
        ) {
            $row[$field] =
                (int) (
                    $row[$field]
                    ?? 0
                );
        }

        $nullableIntegerFields = [
            'store_id',
            'island_id',
            'municipality_id',
            'cover_attachment_id',
            'customer_avatar_attachment_id',
            'customer_island_id',
            'customer_municipality_id',
        ];

        foreach (
            $nullableIntegerFields
            as $field
        ) {
            if (!array_key_exists($field, $row)) {
                continue;
            }

            $row[$field] =
                $row[$field] !== null
                && $row[$field] !== ''
                    ? (int) $row[$field]
                    : null;
        }

        $row['price'] =
            isset($row['price'])
                ? (float) $row['price']
                : 0.0;

        $row['original_price'] =
            isset($row['original_price'])
            && $row['original_price'] !== null
            && $row['original_price'] !== ''
                ? (float) $row['original_price']
                : null;

        $coverAttachmentId =
            isset(
                $row['cover_attachment_id']
            )
                ? (int) (
                    $row[
                        'cover_attachment_id'
                    ]
                    ?? 0
                )
                : 0;

        $row['cover_thumbnail_url'] =
            $coverAttachmentId > 0
                ? (
                    wp_get_attachment_image_url(
                        $coverAttachmentId,
                        'thumbnail'
                    )
                    ?: ''
                )
                : '';

        return $row;
    }
}