<?php

declare(strict_types=1);

namespace DSM\Anuncios\Advertisement;

use DateTimeImmutable;
use RuntimeException;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Repositorio principal de anuncios.
 *
 * La ubicación territorial utiliza:
 *
 * - area_id
 * - municipality_id
 *
 * area_id puede representar una isla, provincia, comarca,
 * región u otra división territorial gestionada por
 * DSM Ubicaciones.
 */
final class AdvertisementRepository
{
    private string $tableName;

    public function __construct()
    {
        global $wpdb;

        $this->tableName =
            $wpdb->prefix
            . 'dsm_ads';
    }

    public function findById(
        int $advertisementId
    ): ?Advertisement {
        global $wpdb;

        if ($advertisementId <= 0) {
            return null;
        }

        $sql =
            $wpdb->prepare(
                "
                SELECT
                    id,
                    customer_id,
                    store_id,
                    category_id,
                    area_id,
                    municipality_id,
                    title,
                    slug,
                    description,
                    brand,
                    price,
                    original_price,
                    purchase_date,
                    condition_code,
                    status,
                    rejection_reason,
                    reserved_at,
                    published_at,
                    closed_at,
                    created_at,
                    updated_at
                FROM {$this->tableName}
                WHERE id = %d
                LIMIT 1
                ",
                $advertisementId
            );

        if (!is_string($sql)) {
            return null;
        }

        $row =
            $wpdb->get_row(
                $sql,
                ARRAY_A
            );

        return is_array($row)
            ? Advertisement::fromArray($row)
            : null;
    }

    public function findBySlug(
        string $slug
    ): ?Advertisement {
        global $wpdb;

        $slug =
            sanitize_title(
                $slug
            );

        if ($slug === '') {
            return null;
        }

        $sql =
            $wpdb->prepare(
                "
                SELECT
                    id,
                    customer_id,
                    store_id,
                    category_id,
                    area_id,
                    municipality_id,
                    title,
                    slug,
                    description,
                    brand,
                    price,
                    original_price,
                    purchase_date,
                    condition_code,
                    status,
                    rejection_reason,
                    reserved_at,
                    published_at,
                    closed_at,
                    created_at,
                    updated_at
                FROM {$this->tableName}
                WHERE slug = %s
                LIMIT 1
                ",
                $slug
            );

        if (!is_string($sql)) {
            return null;
        }

        $row =
            $wpdb->get_row(
                $sql,
                ARRAY_A
            );

        return is_array($row)
            ? Advertisement::fromArray($row)
            : null;
    }

    /**
     * @param array<string, mixed> $data
     */
    public function create(
        array $data
    ): int {
        global $wpdb;

        $customerId =
            (int) (
                $data['customer_id']
                ?? 0
            );

        $storeId =
            self::nullablePositiveInt(
                $data['store_id']
                ?? null
            );

        $categoryId =
            (int) (
                $data['category_id']
                ?? 0
            );

        $areaId =
            self::nullablePositiveInt(
                $data['area_id']
                ?? null
            );

        $municipalityId =
            self::nullablePositiveInt(
                $data['municipality_id']
                ?? null
            );

        $title =
            trim(
                (string) (
                    $data['title']
                    ?? ''
                )
            );

        $description =
            trim(
                (string) (
                    $data['description']
                    ?? ''
                )
            );

        $brand =
            self::normalizeNullableShortText(
                $data['brand']
                ?? null,
                120
            );

        $price =
            self::normalizePrice(
                $data['price']
                ?? 0
            );

        $originalPrice =
            self::normalizeNullablePrice(
                $data['original_price']
                ?? null
            );

        $purchaseDate =
            self::normalizeNullableDate(
                $data['purchase_date']
                ?? null
            );

        $conditionCode =
            sanitize_key(
                (string) (
                    $data['condition_code']
                    ?? ''
                )
            );

        $status =
            sanitize_key(
                (string) (
                    $data['status']
                    ?? AdvertisementStatus::DRAFT
                )
            );

        $rejectionReason =
            self::normalizeNullableTextarea(
                $data['rejection_reason']
                ?? null
            );

        if ($customerId <= 0) {
            throw new RuntimeException(
                'El identificador del cliente no es válido.'
            );
        }

        if ($categoryId <= 0) {
            throw new RuntimeException(
                'El identificador de la categoría no es válido.'
            );
        }

        if ($title === '') {
            throw new RuntimeException(
                'El título del anuncio es obligatorio.'
            );
        }

        if ($description === '') {
            throw new RuntimeException(
                'La descripción del anuncio es obligatoria.'
            );
        }

        if ($conditionCode === '') {
            throw new RuntimeException(
                'El estado de conservación es obligatorio.'
            );
        }

        if (
            $municipalityId !== null
            && $areaId === null
        ) {
            throw new RuntimeException(
                'No se puede seleccionar un municipio sin indicar un área.'
            );
        }

        if (!AdvertisementStatus::isValid($status)) {
            throw new RuntimeException(
                'El estado del anuncio no es válido.'
            );
        }

        $slug =
            $this->generateUniqueSlug(
                isset($data['slug'])
                    ? (string) $data['slug']
                    : $title
            );

        $now =
            current_time(
                'mysql',
                true
            );

        $insertData = [
            'customer_id' =>
                $customerId,

            'store_id' =>
                $storeId,

            'category_id' =>
                $categoryId,

            'area_id' =>
                $areaId,

            'municipality_id' =>
                $municipalityId,

            'title' =>
                $title,

            'slug' =>
                $slug,

            'description' =>
                $description,

            'brand' =>
                $brand,

            'price' =>
                $price,

            'original_price' =>
                $originalPrice,

            'purchase_date' =>
                $purchaseDate,

            'condition_code' =>
                $conditionCode,

            'status' =>
                $status,

            'rejection_reason' =>
                $rejectionReason,

            'reserved_at' =>
                null,

            'published_at' =>
                null,

            'closed_at' =>
                null,

            'created_at' =>
                $now,

            'updated_at' =>
                $now,
        ];

        $result =
            $wpdb->insert(
                $this->tableName,
                $insertData
            );

        if ($result === false) {
            throw new RuntimeException(
                sprintf(
                    'No se pudo crear el anuncio: %s',
                    $wpdb->last_error
                )
            );
        }

        return (int) $wpdb->insert_id;
    }

    /**
     * @param array<string, mixed> $data
     */
    public function updateDetails(
        int $advertisementId,
        array $data
    ): void {
        global $wpdb;

        if ($advertisementId <= 0) {
            throw new RuntimeException(
                'El identificador del anuncio no es válido.'
            );
        }

        $advertisement =
            $this->findById(
                $advertisementId
            );

        if ($advertisement === null) {
            throw new RuntimeException(
                'No se encontró el anuncio.'
            );
        }

        $categoryId =
            array_key_exists(
                'category_id',
                $data
            )
                ? (int) $data['category_id']
                : $advertisement
                    ->getCategoryId();

        $areaId =
            array_key_exists(
                'area_id',
                $data
            )
                ? self::nullablePositiveInt(
                    $data['area_id']
                )
                : $advertisement
                    ->getAreaId();

        $municipalityId =
            array_key_exists(
                'municipality_id',
                $data
            )
                ? self::nullablePositiveInt(
                    $data['municipality_id']
                )
                : $advertisement
                    ->getMunicipalityId();

        $title =
            array_key_exists(
                'title',
                $data
            )
                ? trim(
                    (string) $data['title']
                )
                : $advertisement
                    ->getTitle();

        $description =
            array_key_exists(
                'description',
                $data
            )
                ? trim(
                    (string) $data[
                        'description'
                    ]
                )
                : $advertisement
                    ->getDescription();

        $brand =
            array_key_exists(
                'brand',
                $data
            )
                ? self::normalizeNullableShortText(
                    $data['brand'],
                    120
                )
                : $advertisement
                    ->getBrand();

        $price =
            array_key_exists(
                'price',
                $data
            )
                ? self::normalizePrice(
                    $data['price']
                )
                : $advertisement
                    ->getPrice();

        $originalPrice =
            array_key_exists(
                'original_price',
                $data
            )
                ? self::normalizeNullablePrice(
                    $data['original_price']
                )
                : $advertisement
                    ->getOriginalPrice();

        $purchaseDate =
            array_key_exists(
                'purchase_date',
                $data
            )
                ? self::normalizeNullableDate(
                    $data['purchase_date']
                )
                : $advertisement
                    ->getPurchaseDate()
                    ?->format('Y-m-d');

        $conditionCode =
            array_key_exists(
                'condition_code',
                $data
            )
                ? sanitize_key(
                    (string) $data[
                        'condition_code'
                    ]
                )
                : $advertisement
                    ->getConditionCode();

        if ($categoryId <= 0) {
            throw new RuntimeException(
                'El identificador de la categoría no es válido.'
            );
        }

        if ($title === '') {
            throw new RuntimeException(
                'El título del anuncio es obligatorio.'
            );
        }

        if ($description === '') {
            throw new RuntimeException(
                'La descripción del anuncio es obligatoria.'
            );
        }

        if ($conditionCode === '') {
            throw new RuntimeException(
                'El estado de conservación es obligatorio.'
            );
        }

        if (
            $municipalityId !== null
            && $areaId === null
        ) {
            throw new RuntimeException(
                'No se puede seleccionar un municipio sin indicar un área.'
            );
        }

        $slug =
            $advertisement
                ->getSlug();

        if (
            array_key_exists(
                'title',
                $data
            )
            || array_key_exists(
                'slug',
                $data
            )
        ) {
            $slugSource =
                array_key_exists(
                    'slug',
                    $data
                )
                    ? (string) $data['slug']
                    : $title;

            $slug =
                $this->generateUniqueSlug(
                    $slugSource,
                    $advertisementId
                );
        }

        $updated =
            $wpdb->update(
                $this->tableName,
                [
                    'category_id' =>
                        $categoryId,

                    'area_id' =>
                        $areaId,

                    'municipality_id' =>
                        $municipalityId,

                    'title' =>
                        $title,

                    'slug' =>
                        $slug,

                    'description' =>
                        $description,

                    'brand' =>
                        $brand,

                    'price' =>
                        $price,

                    'original_price' =>
                        $originalPrice,

                    'purchase_date' =>
                        $purchaseDate,

                    'condition_code' =>
                        $conditionCode,

                    'updated_at' =>
                        current_time(
                            'mysql',
                            true
                        ),
                ],
                [
                    'id' =>
                        $advertisementId,
                ]
            );

        if ($updated === false) {
            throw new RuntimeException(
                sprintf(
                    'No se pudo actualizar el anuncio: %s',
                    $wpdb->last_error
                )
            );
        }
    }

    public function updateStatus(
        int $advertisementId,
        string $status,
        ?string $rejectionReason = null
    ): void {
        global $wpdb;

        if ($advertisementId <= 0) {
            throw new RuntimeException(
                'El identificador del anuncio no es válido.'
            );
        }

        if (!AdvertisementStatus::isValid($status)) {
            throw new RuntimeException(
                'El nuevo estado del anuncio no es válido.'
            );
        }

        $advertisement =
            $this->findById(
                $advertisementId
            );

        if ($advertisement === null) {
            throw new RuntimeException(
                'No se encontró el anuncio.'
            );
        }

        $now =
            current_time(
                'mysql',
                true
            );

        $reservedAt =
            $advertisement
                ->getReservedAt()
                ?->format('Y-m-d H:i:s');

        $publishedAt =
            $advertisement
                ->getPublishedAt()
                ?->format('Y-m-d H:i:s');

        $closedAt =
            $advertisement
                ->getClosedAt()
                ?->format('Y-m-d H:i:s');

        if ($status === AdvertisementStatus::ACTIVE) {
            $reservedAt =
                null;

            $closedAt =
                null;

            if ($publishedAt === null) {
                $publishedAt =
                    $now;
            }
        }

        if ($status === AdvertisementStatus::RESERVED) {
            $reservedAt =
                $now;
        }

        if ($status === AdvertisementStatus::CLOSED) {
            $closedAt =
                $now;
        }

        if ($status !== AdvertisementStatus::REJECTED) {
            $rejectionReason =
                null;
        } else {
            $rejectionReason =
                self::normalizeNullableTextarea(
                    $rejectionReason
                );

            if ($rejectionReason === null) {
                throw new RuntimeException(
                    'El motivo de rechazo es obligatorio.'
                );
            }
        }

        $updated =
            $wpdb->update(
                $this->tableName,
                [
                    'status' =>
                        $status,

                    'rejection_reason' =>
                        $rejectionReason,

                    'reserved_at' =>
                        $reservedAt,

                    'published_at' =>
                        $publishedAt,

                    'closed_at' =>
                        $closedAt,

                    'updated_at' =>
                        $now,
                ],
                [
                    'id' =>
                        $advertisementId,
                ]
            );

        if ($updated === false) {
            throw new RuntimeException(
                sprintf(
                    'No se pudo actualizar el estado del anuncio: %s',
                    $wpdb->last_error
                )
            );
        }
    }

    public function belongsToCustomer(
        int $advertisementId,
        int $customerId
    ): bool {
        global $wpdb;

        if (
            $advertisementId <= 0
            || $customerId <= 0
        ) {
            return false;
        }

        $sql =
            $wpdb->prepare(
                "
                SELECT id
                FROM {$this->tableName}
                WHERE id = %d
                  AND customer_id = %d
                LIMIT 1
                ",
                $advertisementId,
                $customerId
            );

        if (!is_string($sql)) {
            return false;
        }

        $found =
            $wpdb->get_var(
                $sql
            );

        return $found !== null;
    }

    /**
     * Cuenta los anuncios que consumen una plaza dentro
     * del límite de anuncios abiertos del cliente.
     */
    public function countActiveForCustomer(
        int $customerId
    ): int {
        global $wpdb;

        if ($customerId <= 0) {
            return 0;
        }

        $statuses =
            AdvertisementStatus::
                statusesCountingTowardsActiveLimit();

        if ($statuses === []) {
            return 0;
        }

        $placeholders =
            implode(
                ', ',
                array_fill(
                    0,
                    count($statuses),
                    '%s'
                )
            );

        $parameters = [
            $customerId,
            ...$statuses,
        ];

        $sql =
            $wpdb->prepare(
                "
                SELECT COUNT(*)
                FROM {$this->tableName}
                WHERE customer_id = %d
                  AND status IN ({$placeholders})
                ",
                ...$parameters
            );

        if (!is_string($sql)) {
            return 0;
        }

        return max(
            0,
            (int) $wpdb->get_var(
                $sql
            )
        );
    }

    /**
     * @return array<int, Advertisement>
     */
    public function findByCustomer(
        int $customerId,
        int $limit = 50,
        int $offset = 0
    ): array {
        global $wpdb;

        if ($customerId <= 0) {
            return [];
        }

        $limit =
            max(
                1,
                min(
                    200,
                    $limit
                )
            );

        $offset =
            max(
                0,
                $offset
            );

        $sql =
            $wpdb->prepare(
                "
                SELECT
                    id,
                    customer_id,
                    store_id,
                    category_id,
                    area_id,
                    municipality_id,
                    title,
                    slug,
                    description,
                    brand,
                    price,
                    original_price,
                    purchase_date,
                    condition_code,
                    status,
                    rejection_reason,
                    reserved_at,
                    published_at,
                    closed_at,
                    created_at,
                    updated_at
                FROM {$this->tableName}
                WHERE customer_id = %d
                ORDER BY
                    created_at DESC,
                    id DESC
                LIMIT %d
                OFFSET %d
                ",
                $customerId,
                $limit,
                $offset
            );

        if (!is_string($sql)) {
            return [];
        }

        $rows =
            $wpdb->get_results(
                $sql,
                ARRAY_A
            );

        return $this->hydrateRows(
            $rows
        );
    }

    /**
     * @return array<int, Advertisement>
     */
    public function findPublic(
        int $limit = 24,
        int $offset = 0,
        ?int $categoryId = null
    ): array {
        global $wpdb;

        $limit =
            max(
                1,
                min(
                    100,
                    $limit
                )
            );

        $offset =
            max(
                0,
                $offset
            );

        $where = "
            WHERE status IN (%s, %s)
        ";

        $parameters = [
            AdvertisementStatus::ACTIVE,
            AdvertisementStatus::RESERVED,
        ];

        if (
            $categoryId !== null
            && $categoryId > 0
        ) {
            $where .= "
                AND category_id = %d
            ";

            $parameters[] =
                $categoryId;
        }

        $parameters[] =
            $limit;

        $parameters[] =
            $offset;

        $query =
            $wpdb->prepare(
                "
                SELECT
                    id,
                    customer_id,
                    store_id,
                    category_id,
                    area_id,
                    municipality_id,
                    title,
                    slug,
                    description,
                    brand,
                    price,
                    original_price,
                    purchase_date,
                    condition_code,
                    status,
                    rejection_reason,
                    reserved_at,
                    published_at,
                    closed_at,
                    created_at,
                    updated_at
                FROM {$this->tableName}
                {$where}
                ORDER BY
                    published_at DESC,
                    id DESC
                LIMIT %d
                OFFSET %d
                ",
                ...$parameters
            );

        if (!is_string($query)) {
            return [];
        }

        $rows =
            $wpdb->get_results(
                $query,
                ARRAY_A
            );

        return $this->hydrateRows(
            $rows
        );
    }

    public function deleteById(
        int $advertisementId
    ): void {
        global $wpdb;

        if ($advertisementId <= 0) {
            throw new RuntimeException(
                'El identificador del anuncio no es válido.'
            );
        }

        $deleted =
            $wpdb->delete(
                $this->tableName,
                [
                    'id' =>
                        $advertisementId,
                ],
                [
                    '%d',
                ]
            );

        if ($deleted === false) {
            throw new RuntimeException(
                sprintf(
                    'No se pudo eliminar el anuncio: %s',
                    $wpdb->last_error
                )
            );
        }

        if ($deleted !== 1) {
            throw new RuntimeException(
                'No se encontró el anuncio que se debía eliminar.'
            );
        }
    }

    /**
     * @param mixed $rows
     *
     * @return array<int, Advertisement>
     */
    private function hydrateRows(
        mixed $rows
    ): array {
        if (!is_array($rows)) {
            return [];
        }

        $advertisements = [];

        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }

            $advertisements[] =
                Advertisement::fromArray(
                    $row
                );
        }

        return $advertisements;
    }

    private function generateUniqueSlug(
        string $source,
        ?int $excludeAdvertisementId = null
    ): string {
        $baseSlug =
            sanitize_title(
                $source
            );

        if ($baseSlug === '') {
            $baseSlug =
                'anuncio';
        }

        $candidate =
            $baseSlug;

        $suffix =
            2;

        while (
            $this->slugExists(
                $candidate,
                $excludeAdvertisementId
            )
        ) {
            $candidate =
                $baseSlug
                . '-'
                . $suffix;

            $suffix++;
        }

        return $candidate;
    }

    private function slugExists(
        string $slug,
        ?int $excludeAdvertisementId = null
    ): bool {
        global $wpdb;

        if (
            $excludeAdvertisementId !== null
            && $excludeAdvertisementId > 0
        ) {
            $sql =
                $wpdb->prepare(
                    "
                    SELECT id
                    FROM {$this->tableName}
                    WHERE slug = %s
                      AND id <> %d
                    LIMIT 1
                    ",
                    $slug,
                    $excludeAdvertisementId
                );
        } else {
            $sql =
                $wpdb->prepare(
                    "
                    SELECT id
                    FROM {$this->tableName}
                    WHERE slug = %s
                    LIMIT 1
                    ",
                    $slug
                );
        }

        if (!is_string($sql)) {
            return false;
        }

        $found =
            $wpdb->get_var(
                $sql
            );

        return $found !== null;
    }

    private static function normalizePrice(
        mixed $value
    ): float {
        if (is_string($value)) {
            $value =
                str_replace(
                    ',',
                    '.',
                    $value
                );
        }

        $price =
            round(
                (float) $value,
                2
            );

        if ($price < 0) {
            throw new RuntimeException(
                'El precio no puede ser negativo.'
            );
        }

        return $price;
    }

    private static function normalizeNullablePrice(
        mixed $value
    ): ?float {
        if (
            $value === null
            || $value === ''
        ) {
            return null;
        }

        return self::normalizePrice(
            $value
        );
    }

    private static function nullablePositiveInt(
        mixed $value
    ): ?int {
        if (
            $value === null
            || $value === ''
        ) {
            return null;
        }

        $integer =
            (int) $value;

        return $integer > 0
            ? $integer
            : null;
    }

    private static function normalizeNullableShortText(
        mixed $value,
        int $maximumLength
    ): ?string {
        if (
            $value === null
            || trim((string) $value) === ''
        ) {
            return null;
        }

        $text =
            sanitize_text_field(
                (string) $value
            );

        if (
            mb_strlen(
                $text
            ) > $maximumLength
        ) {
            throw new RuntimeException(
                sprintf(
                    'El texto no puede superar los %d caracteres.',
                    $maximumLength
                )
            );
        }

        return $text;
    }

    private static function normalizeNullableTextarea(
        mixed $value
    ): ?string {
        if (
            $value === null
            || trim((string) $value) === ''
        ) {
            return null;
        }

        return sanitize_textarea_field(
            (string) $value
        );
    }

    private static function normalizeNullableDate(
        mixed $value
    ): ?string {
        if (
            $value === null
            || trim((string) $value) === ''
        ) {
            return null;
        }

        $value =
            trim(
                (string) $value
            );

        $date =
            DateTimeImmutable::createFromFormat(
                '!Y-m-d',
                $value
            );

        $errors =
            DateTimeImmutable::getLastErrors();

        if (
            $date === false
            || (
                is_array($errors)
                && (
                    $errors['warning_count'] > 0
                    || $errors['error_count'] > 0
                )
            )
        ) {
            throw new RuntimeException(
                'La fecha de compra no es válida.'
            );
        }

        return $date->format(
            'Y-m-d'
        );
    }
}