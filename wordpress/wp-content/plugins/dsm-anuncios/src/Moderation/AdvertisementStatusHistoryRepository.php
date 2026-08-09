<?php

declare(strict_types=1);

namespace DSM\Anuncios\Moderation;

use DSM\Anuncios\Advertisement\AdvertisementStatus;
use RuntimeException;
use wpdb;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Gestiona el historial de estados de los anuncios.
 *
 * Cada entrada registra:
 *
 * - estado anterior;
 * - estado nuevo;
 * - cliente responsable, cuando corresponda;
 * - usuario de WordPress responsable, cuando corresponda;
 * - notas descriptivas;
 * - fecha UTC del cambio.
 *
 * Cuando ambos responsables son null, el cambio se considera
 * ejecutado automáticamente por el sistema.
 */
final class AdvertisementStatusHistoryRepository
{
    private wpdb $database;

    private string $tableName;

    public function __construct()
    {
        global $wpdb;

        $this->database =
            $wpdb;

        $this->tableName =
            $wpdb->prefix
            . 'dsm_ad_status_history';
    }

    /**
     * Registra un cambio de estado.
     *
     * Solamente puede indicarse uno de estos responsables:
     *
     * - changedByCustomerId;
     * - changedByUserId.
     *
     * Si ambos son null, el responsable será el sistema.
     */
    public function addEntry(
        int $advertisementId,
        ?string $previousStatus,
        string $newStatus,
        ?int $changedByCustomerId = null,
        ?int $changedByUserId = null,
        ?string $notes = null
    ): int {
        if ($advertisementId <= 0) {
            throw new RuntimeException(
                'El identificador del anuncio no es válido.'
            );
        }

        $previousStatus =
            $this->normalizeNullableStatus(
                $previousStatus
            );

        $newStatus =
            sanitize_key(
                $newStatus
            );

        if (
            !AdvertisementStatus::isValid(
                $newStatus
            )
        ) {
            throw new RuntimeException(
                'El nuevo estado del historial no es válido.'
            );
        }

        $changedByCustomerId =
            $this->normalizeNullablePositiveInt(
                $changedByCustomerId
            );

        $changedByUserId =
            $this->normalizeNullablePositiveInt(
                $changedByUserId
            );

        if (
            $changedByCustomerId !== null
            && $changedByUserId !== null
        ) {
            throw new RuntimeException(
                'Un cambio de estado no puede pertenecer '
                . 'simultáneamente a un cliente y a un usuario '
                . 'de WordPress.'
            );
        }

        $notes =
            $this->normalizeNullableNotes(
                $notes
            );

        $result =
            $this->database->insert(
                $this->tableName,
                [
                    'advertisement_id' =>
                        $advertisementId,

                    'previous_status' =>
                        $previousStatus,

                    'new_status' =>
                        $newStatus,

                    'changed_by_customer_id' =>
                        $changedByCustomerId,

                    'changed_by_user_id' =>
                        $changedByUserId,

                    'notes' =>
                        $notes,

                    'created_at' =>
                        $this->now(),
                ],
                [
                    '%d',
                    '%s',
                    '%s',
                    '%d',
                    '%d',
                    '%s',
                    '%s',
                ]
            );

        if ($result === false) {
            throw new RuntimeException(
                sprintf(
                    'No se pudo registrar el historial del anuncio: %s',
                    $this->database->last_error
                )
            );
        }

        $historyId =
            (int) $this->database
                ->insert_id;

        if ($historyId <= 0) {
            throw new RuntimeException(
                'El historial se guardó, pero no se obtuvo '
                . 'su identificador.'
            );
        }

        return $historyId;
    }

    /**
     * Registra un cambio realizado por el cliente.
     */
    public function addCustomerEntry(
        int $advertisementId,
        ?string $previousStatus,
        string $newStatus,
        int $customerId,
        ?string $notes = null
    ): int {
        if ($customerId <= 0) {
            throw new RuntimeException(
                'El identificador del cliente no es válido.'
            );
        }

        return $this->addEntry(
            $advertisementId,
            $previousStatus,
            $newStatus,
            $customerId,
            null,
            $notes
        );
    }

    /**
     * Registra un cambio realizado por un administrador
     * o usuario interno de WordPress.
     */
    public function addUserEntry(
        int $advertisementId,
        ?string $previousStatus,
        string $newStatus,
        int $userId,
        ?string $notes = null
    ): int {
        if ($userId <= 0) {
            throw new RuntimeException(
                'El identificador del usuario no es válido.'
            );
        }

        return $this->addEntry(
            $advertisementId,
            $previousStatus,
            $newStatus,
            null,
            $userId,
            $notes
        );
    }

    /**
     * Registra un cambio automático del sistema.
     */
    public function addSystemEntry(
        int $advertisementId,
        ?string $previousStatus,
        string $newStatus,
        ?string $notes = null
    ): int {
        return $this->addEntry(
            $advertisementId,
            $previousStatus,
            $newStatus,
            null,
            null,
            $notes
        );
    }

    /**
     * Busca una entrada por su identificador.
     *
     * @return array<string, mixed>|null
     */
    public function findById(
        int $historyId
    ): ?array {
        if ($historyId <= 0) {
            return null;
        }

        $sql =
            $this->database->prepare(
                "
                SELECT
                    id,
                    advertisement_id,
                    previous_status,
                    new_status,
                    changed_by_customer_id,
                    changed_by_user_id,
                    notes,
                    created_at
                FROM {$this->tableName}
                WHERE id = %d
                LIMIT 1
                ",
                $historyId
            );

        if (!is_string($sql)) {
            return null;
        }

        $row =
            $this->database->get_row(
                $sql,
                ARRAY_A
            );

        return is_array($row)
            ? $this->normalizeRow(
                $row
            )
            : null;
    }

    /**
     * Devuelve el historial completo de un anuncio.
     *
     * Se ordena desde el cambio más reciente al más antiguo.
     *
     * @return array<int, array<string, mixed>>
     */
    public function findByAdvertisementId(
        int $advertisementId,
        int $limit = 100,
        int $offset = 0
    ): array {
        if ($advertisementId <= 0) {
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

        $sql =
            $this->database->prepare(
                "
                SELECT
                    id,
                    advertisement_id,
                    previous_status,
                    new_status,
                    changed_by_customer_id,
                    changed_by_user_id,
                    notes,
                    created_at
                FROM {$this->tableName}
                WHERE advertisement_id = %d
                ORDER BY
                    created_at DESC,
                    id DESC
                LIMIT %d
                OFFSET %d
                ",
                $advertisementId,
                $limit,
                $offset
            );

        if (!is_string($sql)) {
            return [];
        }

        $rows =
            $this->database->get_results(
                $sql,
                ARRAY_A
            );

        return $this->normalizeRows(
            $rows
        );
    }

    /**
     * Devuelve la entrada más reciente de un anuncio.
     *
     * @return array<string, mixed>|null
     */
    public function findLatestByAdvertisementId(
        int $advertisementId
    ): ?array {
        if ($advertisementId <= 0) {
            return null;
        }

        $sql =
            $this->database->prepare(
                "
                SELECT
                    id,
                    advertisement_id,
                    previous_status,
                    new_status,
                    changed_by_customer_id,
                    changed_by_user_id,
                    notes,
                    created_at
                FROM {$this->tableName}
                WHERE advertisement_id = %d
                ORDER BY
                    created_at DESC,
                    id DESC
                LIMIT 1
                ",
                $advertisementId
            );

        if (!is_string($sql)) {
            return null;
        }

        $row =
            $this->database->get_row(
                $sql,
                ARRAY_A
            );

        return is_array($row)
            ? $this->normalizeRow(
                $row
            )
            : null;
    }

    /**
     * Cuenta las entradas de historial de un anuncio.
     */
    public function countByAdvertisementId(
        int $advertisementId
    ): int {
        if ($advertisementId <= 0) {
            return 0;
        }

        $sql =
            $this->database->prepare(
                "
                SELECT COUNT(*)
                FROM {$this->tableName}
                WHERE advertisement_id = %d
                ",
                $advertisementId
            );

        if (!is_string($sql)) {
            return 0;
        }

        return max(
            0,
            (int) $this->database
                ->get_var(
                    $sql
                )
        );
    }

    /**
     * Elimina una entrada concreta.
     *
     * Este método queda disponible para tareas internas
     * o correcciones administrativas excepcionales.
     */
    public function deleteById(
        int $historyId
    ): bool {
        if ($historyId <= 0) {
            return false;
        }

        $deleted =
            $this->database->delete(
                $this->tableName,
                [
                    'id' =>
                        $historyId,
                ],
                [
                    '%d',
                ]
            );

        if ($deleted === false) {
            throw new RuntimeException(
                sprintf(
                    'No se pudo eliminar la entrada del historial: %s',
                    $this->database->last_error
                )
            );
        }

        return $deleted > 0;
    }

    /**
     * Elimina todo el historial de un anuncio.
     *
     * Se utilizará durante la eliminación definitiva
     * del propio anuncio.
     */
    public function deleteByAdvertisementId(
        int $advertisementId
    ): int {
        if ($advertisementId <= 0) {
            return 0;
        }

        $deleted =
            $this->database->delete(
                $this->tableName,
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
                sprintf(
                    'No se pudo eliminar el historial del anuncio: %s',
                    $this->database->last_error
                )
            );
        }

        return (int) $deleted;
    }

    /**
     * Normaliza un conjunto de filas procedentes de la base
     * de datos.
     *
     * @param mixed $rows
     *
     * @return array<int, array<string, mixed>>
     */
    private function normalizeRows(
        mixed $rows
    ): array {
        if (!is_array($rows)) {
            return [];
        }

        $result = [];

        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }

            $result[] =
                $this->normalizeRow(
                    $row
                );
        }

        return $result;
    }

    /**
     * @param array<string, mixed> $row
     *
     * @return array<string, mixed>
     */
    private function normalizeRow(
        array $row
    ): array {
        return [
            'id' =>
                max(
                    0,
                    (int) (
                        $row['id']
                        ?? 0
                    )
                ),

            'advertisement_id' =>
                max(
                    0,
                    (int) (
                        $row['advertisement_id']
                        ?? 0
                    )
                ),

            'previous_status' =>
                isset($row['previous_status'])
                && $row['previous_status'] !== null
                    ? (string) $row[
                        'previous_status'
                    ]
                    : null,

            'new_status' =>
                (string) (
                    $row['new_status']
                    ?? ''
                ),

            'changed_by_customer_id' =>
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
                    : null,

            'changed_by_user_id' =>
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
                    : null,

            'notes' =>
                isset($row['notes'])
                && $row['notes'] !== null
                    ? (string) $row['notes']
                    : null,

            'created_at' =>
                (string) (
                    $row['created_at']
                    ?? ''
                ),
        ];
    }

    private function normalizeNullableStatus(
        ?string $status
    ): ?string {
        if (
            $status === null
            || trim($status) === ''
        ) {
            return null;
        }

        $status =
            sanitize_key(
                $status
            );

        if (
            !AdvertisementStatus::isValid(
                $status
            )
        ) {
            throw new RuntimeException(
                'El estado anterior del historial no es válido.'
            );
        }

        return $status;
    }

    private function normalizeNullablePositiveInt(
        ?int $value
    ): ?int {
        if (
            $value === null
            || $value <= 0
        ) {
            return null;
        }

        return $value;
    }

    private function normalizeNullableNotes(
        ?string $notes
    ): ?string {
        if (
            $notes === null
            || trim($notes) === ''
        ) {
            return null;
        }

        $notes =
            sanitize_textarea_field(
                $notes
            );

        if (
            mb_strlen($notes)
            > 5000
        ) {
            throw new RuntimeException(
                'Las notas del historial no pueden superar '
                . 'los 5000 caracteres.'
            );
        }

        return $notes;
    }

    private function now(): string
    {
        return current_time(
            'mysql',
            true
        );
    }
}