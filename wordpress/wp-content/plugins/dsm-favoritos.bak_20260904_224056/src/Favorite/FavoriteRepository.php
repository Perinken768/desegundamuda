<?php

declare(strict_types=1);

namespace DSM\Favoritos\Favorite;

use DateTimeImmutable;
use RuntimeException;
use wpdb;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Persistencia de favoritos.
 *
 * Este repositorio trabaja exclusivamente con
 * wp_dsm_favorites.
 *
 * No comprueba:
 *
 * - si el cliente existe;
 * - si el cliente está activo;
 * - si el anuncio existe;
 * - si el anuncio está publicado.
 *
 * Esas comprobaciones pertenecen a la capa
 * Application.
 */
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
                        advertisement_id,
                        created_at
                    FROM {$this->table}
                    WHERE id = %d
                    LIMIT 1
                    ",
                    $favoriteId
                ),
                ARRAY_A
            );

        if (!is_array($row)) {
            return null;
        }

        return $this->hydrate(
            $row
        );
    }

    public function findByCustomerAndAdvertisement(
        int $customerId,
        int $advertisementId
    ): ?Favorite {
        if (
            $customerId <= 0
            || $advertisementId <= 0
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
                        advertisement_id,
                        created_at
                    FROM {$this->table}
                    WHERE customer_id = %d
                      AND advertisement_id = %d
                    LIMIT 1
                    ",
                    $customerId,
                    $advertisementId
                ),
                ARRAY_A
            );

        if (!is_array($row)) {
            return null;
        }

        return $this->hydrate(
            $row
        );
    }

    public function exists(
        int $customerId,
        int $advertisementId
    ): bool {
        if (
            $customerId <= 0
            || $advertisementId <= 0
        ) {
            return false;
        }

        $favoriteId =
            $this->database->get_var(
                $this->database->prepare(
                    "
                    SELECT id
                    FROM {$this->table}
                    WHERE customer_id = %d
                      AND advertisement_id = %d
                    LIMIT 1
                    ",
                    $customerId,
                    $advertisementId
                )
            );

        return $favoriteId !== null;
    }

    public function create(
        int $customerId,
        int $advertisementId
    ): Favorite {
        if ($customerId <= 0) {
            throw new \InvalidArgumentException(
                'El ID del cliente debe ser mayor que cero.'
            );
        }

        if ($advertisementId <= 0) {
            throw new \InvalidArgumentException(
                'El ID del anuncio debe ser mayor que cero.'
            );
        }

        /*
         * La operación es idempotente a nivel de dominio:
         * si la relación ya existe, devolvemos la existente.
         *
         * La restricción UNIQUE de la base de datos continúa
         * siendo la protección definitiva frente a carreras.
         */
        $existing =
            $this->findByCustomerAndAdvertisement(
                $customerId,
                $advertisementId
            );

        if ($existing !== null) {
            return $existing;
        }

        $createdAt =
            current_time(
                'mysql',
                true
            );

        $inserted =
            $this->database->insert(
                $this->table,
                [
                    'customer_id' =>
                        $customerId,

                    'advertisement_id' =>
                        $advertisementId,

                    'created_at' =>
                        $createdAt,
                ],
                [
                    '%d',
                    '%d',
                    '%s',
                ]
            );

        if ($inserted === false) {
            /*
             * Puede haberse producido una carrera entre
             * dos peticiones que intentaron crear exactamente
             * el mismo favorito.
             *
             * Si la relación existe ahora, devolvemos esa
             * relación en lugar de tratarlo como un fallo.
             */
            $existing =
                $this->findByCustomerAndAdvertisement(
                    $customerId,
                    $advertisementId
                );

            if ($existing !== null) {
                return $existing;
            }

            throw new RuntimeException(
                'No se pudo guardar el favorito.'
            );
        }

        $favoriteId =
            (int) $this->database->insert_id;

        if ($favoriteId <= 0) {
            throw new RuntimeException(
                'No se pudo obtener el ID del favorito creado.'
            );
        }

        $favorite =
            $this->findById(
                $favoriteId
            );

        if ($favorite === null) {
            throw new RuntimeException(
                'El favorito se creó, pero no pudo recuperarse.'
            );
        }

        return $favorite;
    }

    public function delete(
        int $favoriteId
    ): bool {
        if ($favoriteId <= 0) {
            return false;
        }

        $deleted =
            $this->database->delete(
                $this->table,
                [
                    'id' =>
                        $favoriteId,
                ],
                [
                    '%d',
                ]
            );

        if ($deleted === false) {
            throw new RuntimeException(
                'No se pudo eliminar el favorito.'
            );
        }

        return $deleted > 0;
    }

    public function deleteByCustomerAndAdvertisement(
        int $customerId,
        int $advertisementId
    ): bool {
        if (
            $customerId <= 0
            || $advertisementId <= 0
        ) {
            return false;
        }

        $deleted =
            $this->database->delete(
                $this->table,
                [
                    'customer_id' =>
                        $customerId,

                    'advertisement_id' =>
                        $advertisementId,
                ],
                [
                    '%d',
                    '%d',
                ]
            );

        if ($deleted === false) {
            throw new RuntimeException(
                'No se pudo eliminar el favorito.'
            );
        }

        return $deleted > 0;
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
                        advertisement_id,
                        created_at
                    FROM {$this->table}
                    WHERE customer_id = %d
                    ORDER BY created_at DESC, id DESC
                    LIMIT %d
                    OFFSET %d
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
     * Devuelve únicamente los IDs de anuncios favoritos.
     *
     * Es útil para integraciones con listados de anuncios
     * donde no necesitamos hidratar entidades Favorite.
     *
     * @return array<int, int>
     */
    public function findAdvertisementIdsByCustomer(
        int $customerId
    ): array {
        if ($customerId <= 0) {
            return [];
        }

        $values =
            $this->database->get_col(
                $this->database->prepare(
                    "
                    SELECT advertisement_id
                    FROM {$this->table}
                    WHERE customer_id = %d
                    ORDER BY created_at DESC, id DESC
                    ",
                    $customerId
                )
            );

        if (!is_array($values)) {
            return [];
        }

        $advertisementIds = [];

        foreach ($values as $value) {
            $advertisementId =
                (int) $value;

            if ($advertisementId <= 0) {
                continue;
            }

            $advertisementIds[] =
                $advertisementId;
        }

        return $advertisementIds;
    }

    public function countByCustomer(
        int $customerId
    ): int {
        if ($customerId <= 0) {
            return 0;
        }

        return max(
            0,
            (int) $this->database->get_var(
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

    public function countByAdvertisement(
        int $advertisementId
    ): int {
        if ($advertisementId <= 0) {
            return 0;
        }

        return max(
            0,
            (int) $this->database->get_var(
                $this->database->prepare(
                    "
                    SELECT COUNT(*)
                    FROM {$this->table}
                    WHERE advertisement_id = %d
                    ",
                    $advertisementId
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

        return max(
            0,
            (int) $deleted
        );
    }

    public function deleteByAdvertisement(
        int $advertisementId
    ): int {
        if ($advertisementId <= 0) {
            return 0;
        }

        $deleted =
            $this->database->delete(
                $this->table,
                [
                    'advertisement_id' =>
                        $advertisementId,
                ],
                [
                    '%d',
                ]
            );

        if ($deleted === false) {
            throw new RuntimeException(
                'No se pudieron eliminar los favoritos del anuncio.'
            );
        }

        return max(
            0,
            (int) $deleted
        );
    }

    /**
     * @param array<string, mixed> $row
     */
    private function hydrate(
        array $row
    ): Favorite {
        $id =
            isset($row['id'])
                ? (int) $row['id']
                : 0;

        $customerId =
            isset($row['customer_id'])
                ? (int) $row[
                    'customer_id'
                ]
                : 0;

        $advertisementId =
            isset($row['advertisement_id'])
                ? (int) $row[
                    'advertisement_id'
                ]
                : 0;

        $createdAtRaw =
            isset($row['created_at'])
                ? trim(
                    (string) $row[
                        'created_at'
                    ]
                )
                : '';

        if (
            $id <= 0
            || $customerId <= 0
            || $advertisementId <= 0
            || $createdAtRaw === ''
        ) {
            throw new RuntimeException(
                'Los datos almacenados del favorito no son válidos.'
            );
        }

        try {
            /*
             * created_at se almacena en UTC mediante
             * current_time("mysql", true).
             */
            $createdAt =
                new DateTimeImmutable(
                    $createdAtRaw,
                    new \DateTimeZone(
                        'UTC'
                    )
                );
        } catch (\Throwable $exception) {
            throw new RuntimeException(
                'La fecha del favorito almacenado no es válida.',
                0,
                $exception
            );
        }

        return new Favorite(
            $id,
            $customerId,
            $advertisementId,
            $createdAt
        );
    }
}
