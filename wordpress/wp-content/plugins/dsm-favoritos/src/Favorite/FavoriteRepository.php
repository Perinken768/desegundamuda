<?php

declare(strict_types=1);

namespace DSM\Favoritos\Favorite;

use DateTimeImmutable;
use RuntimeException;
use wpdb;

if (!defined('ABSPATH')) {
    exit;
}

final class FavoriteRepository
{
    private wpdb $database;

    private string $table;

    public function __construct(
        ?wpdb $database = null
    ) {
        global $wpdb;

        $this->database =
            $database ?? $wpdb;

        $this->table =
            $this->database->prefix
            . 'dsm_favorites';
    }

    public function findById(
        int $favoriteId
    ): ?Favorite {
        if ($favoriteId <= 0) {
            return null;
        }

        $row =
            $this->database->get_row(
                $this->database->prepare(
                    "
                    SELECT
                        id,
                        customer_id,
                        item_type,
                        item_id,
                        created_at
                    FROM {$this->table}
                    WHERE id = %d
                    LIMIT 1
                    ",
                    $favoriteId
                ),
                ARRAY_A
            );

        return is_array($row)
            ? $this->hydrate($row)
            : null;
    }

    public function findByCustomerAndItem(
        int $customerId,
        string $itemType,
        int $itemId
    ): ?Favorite {
        $itemType =
            sanitize_key(
                $itemType
            );

        if (
            $customerId <= 0
            || $itemId <= 0
            || !Favorite::isValidType(
                $itemType
            )
        ) {
            return null;
        }

        $row =
            $this->database->get_row(
                $this->database->prepare(
                    "
                    SELECT
                        id,
                        customer_id,
                        item_type,
                        item_id,
                        created_at
                    FROM {$this->table}
                    WHERE customer_id = %d
                      AND item_type = %s
                      AND item_id = %d
                    LIMIT 1
                    ",
                    $customerId,
                    $itemType,
                    $itemId
                ),
                ARRAY_A
            );

        return is_array($row)
            ? $this->hydrate($row)
            : null;
    }

    public function existsItem(
        int $customerId,
        string $itemType,
        int $itemId
    ): bool {
        return $this->findByCustomerAndItem(
            $customerId,
            $itemType,
            $itemId
        ) !== null;
    }

    public function createItem(
        int $customerId,
        string $itemType,
        int $itemId
    ): Favorite {
        $itemType =
            sanitize_key(
                $itemType
            );

        if ($customerId <= 0) {
            throw new \InvalidArgumentException(
                'El ID del cliente debe ser mayor que cero.'
            );
        }

        if (
            !Favorite::isValidType(
                $itemType
            )
        ) {
            throw new \InvalidArgumentException(
                'El tipo de favorito no es válido.'
            );
        }

        if ($itemId <= 0) {
            throw new \InvalidArgumentException(
                'El ID del elemento debe ser mayor que cero.'
            );
        }

        $existing =
            $this->findByCustomerAndItem(
                $customerId,
                $itemType,
                $itemId
            );

        if ($existing !== null) {
            return $existing;
        }

        /*
         * advertisement_id continúa siendo NOT NULL durante
         * esta fase de compatibilidad.
         *
         * Para anuncios guardamos el mismo ID.
         * Para productos de tienda usamos 0 temporalmente.
         */
        $legacyAdvertisementId =
            $itemType
                === Favorite::TYPE_ADVERTISEMENT
                    ? $itemId
                    : 0;

        $inserted =
            $this->database->insert(
                $this->table,
                [
                    'customer_id' =>
                        $customerId,

                    'item_type' =>
                        $itemType,

                    'item_id' =>
                        $itemId,

                    'advertisement_id' =>
                        $legacyAdvertisementId,

                    'created_at' =>
                        current_time(
                            'mysql',
                            true
                        ),
                ],
                [
                    '%d',
                    '%s',
                    '%d',
                    '%d',
                    '%s',
                ]
            );

        if ($inserted === false) {
            throw new RuntimeException(
                'No se pudo crear el favorito.'
            );
        }

        $favoriteId =
            (int) $this->database
                ->insert_id;

        $favorite =
            $this->findById(
                $favoriteId
            );

        if ($favorite === null) {
            throw new RuntimeException(
                'El favorito se creó pero no pudo recuperarse.'
            );
        }

        return $favorite;
    }

    public function deleteByCustomerAndItem(
        int $customerId,
        string $itemType,
        int $itemId
    ): bool {
        $itemType =
            sanitize_key(
                $itemType
            );

        if (
            $customerId <= 0
            || $itemId <= 0
            || !Favorite::isValidType(
                $itemType
            )
        ) {
            return false;
        }

        $deleted =
            $this->database->delete(
                $this->table,
                [
                    'customer_id' =>
                        $customerId,

                    'item_type' =>
                        $itemType,

                    'item_id' =>
                        $itemId,
                ],
                [
                    '%d',
                    '%s',
                    '%d',
                ]
            );

        return $deleted !== false;
    }

    public function deleteByItem(
        string $itemType,
        int $itemId
    ): int {
        $itemType =
            sanitize_key(
                $itemType
            );

        if (
            $itemId <= 0
            || !Favorite::isValidType(
                $itemType
            )
        ) {
            return 0;
        }

        $deleted =
            $this->database->delete(
                $this->table,
                [
                    'item_type' =>
                        $itemType,

                    'item_id' =>
                        $itemId,
                ],
                [
                    '%s',
                    '%d',
                ]
            );

        if ($deleted === false) {
            throw new RuntimeException(
                'No se pudieron eliminar los favoritos del elemento.'
            );
        }

        return (int) $deleted;
    }

    /**
     * @return array<int, Favorite>
     */
    public function findByCustomer(
        int $customerId,
        int $limit = 100,
        int $offset = 0
    ): array {
        if ($customerId <= 0) {
            return [];
        }

        $limit =
            max(
                1,
                min(
                    500,
                    $limit
                )
            );

        $offset =
            max(
                0,
                $offset
            );

        $rows =
            $this->database->get_results(
                $this->database->prepare(
                    "
                    SELECT
                        id,
                        customer_id,
                        item_type,
                        item_id,
                        created_at
                    FROM {$this->table}
                    WHERE customer_id = %d
                    ORDER BY created_at DESC, id DESC
                    LIMIT %d OFFSET %d
                    ",
                    $customerId,
                    $limit,
                    $offset
                ),
                ARRAY_A
            );

        if (!is_array($rows)) {
            return [];
        }

        $favorites = [];

        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }

            $favorites[] =
                $this->hydrate(
                    $row
                );
        }

        return $favorites;
    }

    /**
     * @return array<int, int>
     */
    public function findItemIdsByCustomer(
        int $customerId,
        string $itemType
    ): array {
        $itemType =
            sanitize_key(
                $itemType
            );

        if (
            $customerId <= 0
            || !Favorite::isValidType(
                $itemType
            )
        ) {
            return [];
        }

        $ids =
            $this->database->get_col(
                $this->database->prepare(
                    "
                    SELECT item_id
                    FROM {$this->table}
                    WHERE customer_id = %d
                      AND item_type = %s
                    ORDER BY created_at DESC, id DESC
                    ",
                    $customerId,
                    $itemType
                )
            );

        if (!is_array($ids)) {
            return [];
        }

        return array_values(
            array_filter(
                array_map(
                    static fn ($id): int =>
                        max(
                            0,
                            (int) $id
                        ),
                    $ids
                )
            )
        );
    }

    public function countByCustomer(
        int $customerId
    ): int {
        if ($customerId <= 0) {
            return 0;
        }

        return max(
            0,
            (int) $this->database
                ->get_var(
                    $this->database->prepare(
                        "
                        SELECT COUNT(*)
                        FROM {$this->table}
                        WHERE customer_id = %d
                        ",
                        $customerId
                    )
                )
        );
    }

    public function countByItem(
        string $itemType,
        int $itemId
    ): int {
        $itemType =
            sanitize_key(
                $itemType
            );

        if (
            $itemId <= 0
            || !Favorite::isValidType(
                $itemType
            )
        ) {
            return 0;
        }

        return max(
            0,
            (int) $this->database
                ->get_var(
                    $this->database->prepare(
                        "
                        SELECT COUNT(*)
                        FROM {$this->table}
                        WHERE item_type = %s
                          AND item_id = %d
                        ",
                        $itemType,
                        $itemId
                    )
                )
        );
    }

    public function deleteByCustomer(
        int $customerId
    ): int {
        if ($customerId <= 0) {
            return 0;
        }

        $deleted =
            $this->database->delete(
                $this->table,
                [
                    'customer_id' =>
                        $customerId,
                ],
                [
                    '%d',
                ]
            );

        if ($deleted === false) {
            throw new RuntimeException(
                'No se pudieron eliminar los favoritos del cliente.'
            );
        }

        return (int) $deleted;
    }

    /*
     * =========================================================
     * COMPATIBILIDAD CON FAVORITOS DE ANUNCIOS
     * =========================================================
     */

    public function findByCustomerAndAdvertisement(
        int $customerId,
        int $advertisementId
    ): ?Favorite {
        return $this->findByCustomerAndItem(
            $customerId,
            Favorite::TYPE_ADVERTISEMENT,
            $advertisementId
        );
    }

    public function exists(
        int $customerId,
        int $advertisementId
    ): bool {
        return $this->existsItem(
            $customerId,
            Favorite::TYPE_ADVERTISEMENT,
            $advertisementId
        );
    }

    public function create(
        int $customerId,
        int $advertisementId
    ): Favorite {
        return $this->createItem(
            $customerId,
            Favorite::TYPE_ADVERTISEMENT,
            $advertisementId
        );
    }

    public function deleteByCustomerAndAdvertisement(
        int $customerId,
        int $advertisementId
    ): bool {
        return $this->deleteByCustomerAndItem(
            $customerId,
            Favorite::TYPE_ADVERTISEMENT,
            $advertisementId
        );
    }

    public function deleteByAdvertisement(
        int $advertisementId
    ): int {
        return $this->deleteByItem(
            Favorite::TYPE_ADVERTISEMENT,
            $advertisementId
        );
    }

    /**
     * @return array<int, int>
     */
    public function findAdvertisementIdsByCustomer(
        int $customerId
    ): array {
        return $this->findItemIdsByCustomer(
            $customerId,
            Favorite::TYPE_ADVERTISEMENT
        );
    }

    public function countByAdvertisement(
        int $advertisementId
    ): int {
        return $this->countByItem(
            Favorite::TYPE_ADVERTISEMENT,
            $advertisementId
        );
    }

    /**
     * @param array<string, mixed> $row
     */
    private function hydrate(
        array $row
    ): Favorite {
        $itemType =
            sanitize_key(
                (string) (
                    $row['item_type']
                    ?? ''
                )
            );

        $itemId =
            max(
                0,
                (int) (
                    $row['item_id']
                    ?? 0
                )
            );

        if (
            $itemType === ''
            && isset(
                $row['advertisement_id']
            )
        ) {
            $itemType =
                Favorite::TYPE_ADVERTISEMENT;

            $itemId =
                max(
                    0,
                    (int) $row[
                        'advertisement_id'
                    ]
                );
        }

        $createdAtRaw =
            (string) (
                $row['created_at']
                ?? ''
            );

        try {
            $createdAt =
                new DateTimeImmutable(
                    $createdAtRaw
                );
        } catch (\Throwable) {
            $createdAt =
                new DateTimeImmutable(
                    'now'
                );
        }

        return new Favorite(
            max(
                0,
                (int) (
                    $row['id']
                    ?? 0
                )
            ),
            max(
                0,
                (int) (
                    $row['customer_id']
                    ?? 0
                )
            ),
            $itemType,
            $itemId,
            $createdAt
        );
    }
}
