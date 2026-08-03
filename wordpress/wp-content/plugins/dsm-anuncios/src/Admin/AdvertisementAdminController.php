<?php

declare(strict_types=1);

namespace DSM\Anuncios\Admin;

use DSM\Anuncios\Advertisement\AdvertisementStatus;
use RuntimeException;
use Throwable;
use wpdb;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Controlador administrativo de anuncios.
 *
 * Gestiona las transiciones realizadas desde WordPress:
 *
 * - pending  → active
 * - pending  → rejected
 * - active   → reserved
 * - reserved → active
 * - active   → closed
 * - reserved → closed
 *
 * Reglas comerciales:
 *
 * - Solo los anuncios activos consumen límite.
 * - El límite predeterminado es 10.
 * - DSM Suscripciones podrá modificar ese límite mediante filtros.
 * - Un valor -1 significa anuncios activos ilimitados.
 * - El cierre administrativo utiliza closure_reason = moderated.
 */
final class AdvertisementAdminController
{
    public const ACTION_PUBLISH =
        'dsm_advertisement_admin_publish';

    public const ACTION_REJECT =
        'dsm_advertisement_admin_reject';

    public const ACTION_RESERVE =
        'dsm_advertisement_admin_reserve';

    public const ACTION_RELEASE =
        'dsm_advertisement_admin_release';

    public const ACTION_CLOSE =
        'dsm_advertisement_admin_close';

    public const NONCE_FIELD =
        'dsm_advertisement_admin_nonce';

    public const CLOSURE_REASON_SOLD =
        'sold';

    public const CLOSURE_REASON_WITHDRAWN =
        'withdrawn';

    public const CLOSURE_REASON_MODERATED =
        'moderated';

    public const CLOSURE_REASON_EXPIRED =
        'expired';

    private const CAPABILITY =
        'manage_options';

    private const DEFAULT_ACTIVE_LIMIT =
        10;

    private const ERROR_TRANSIENT_PREFIX =
        'dsm_advertisement_admin_error_';

    private wpdb $database;

    private string $advertisementsTable;

    private string $statusHistoryTable;

    public function __construct()
    {
        global $wpdb;

        $this->database = $wpdb;

        $this->advertisementsTable =
            $wpdb->prefix
            . 'dsm_ads';

        $this->statusHistoryTable =
            $wpdb->prefix
            . 'dsm_ad_status_history';
    }

    /**
     * Registra las acciones admin-post.
     */
    public function register(): void
    {
        add_action(
            'admin_post_'
            . self::ACTION_PUBLISH,
            [$this, 'handlePublish']
        );

        add_action(
            'admin_post_'
            . self::ACTION_REJECT,
            [$this, 'handleReject']
        );

        add_action(
            'admin_post_'
            . self::ACTION_RESERVE,
            [$this, 'handleReserve']
        );

        add_action(
            'admin_post_'
            . self::ACTION_RELEASE,
            [$this, 'handleRelease']
        );

        add_action(
            'admin_post_'
            . self::ACTION_CLOSE,
            [$this, 'handleClose']
        );
    }

    /**
     * Publica un anuncio pendiente.
     */
    public function handlePublish(): void
    {
        $advertisementId =
            $this->prepareAction(
                self::ACTION_PUBLISH
            );

        try {
            $this->changeStatus(
                advertisementId:
                    $advertisementId,

                expectedStatuses:
                    [
                        AdvertisementStatus::PENDING,
                    ],

                newStatus:
                    AdvertisementStatus::ACTIVE,

                notes:
                    'Anuncio aprobado y publicado desde administración.',

                additionalFields:
                    [
                        'rejection_reason' =>
                            null,

                        'published_at' =>
                            $this->now(),

                        'reserved_at' =>
                            null,

                        'closed_at' =>
                            null,

                        'closure_reason' =>
                            null,
                    ]
            );

            $this->clearLastError();

            do_action(
                'dsm_advertisement_published',
                $advertisementId,
                get_current_user_id()
            );

            $this->emitAuditEvent(
                'advertisement.published',
                $advertisementId
            );

            $status =
                'published';
        } catch (Throwable $exception) {
            $this->handleException(
                'publicando',
                $advertisementId,
                $exception
            );

            $status =
                'error';
        }

        $this->redirectToAdvertisement(
            $advertisementId,
            $status
        );
    }

    /**
     * Rechaza un anuncio pendiente.
     */
    public function handleReject(): void
    {
        $advertisementId =
            $this->prepareAction(
                self::ACTION_REJECT
            );

        $reason =
            isset($_POST['rejection_reason'])
                ? trim(
                    sanitize_textarea_field(
                        wp_unslash(
                            (string) $_POST[
                                'rejection_reason'
                            ]
                        )
                    )
                )
                : '';

        try {
            if ($reason === '') {
                throw new RuntimeException(
                    'Debes indicar el motivo del rechazo.'
                );
            }

            if (
                function_exists('mb_strlen')
                && mb_strlen($reason) > 2000
            ) {
                throw new RuntimeException(
                    'El motivo del rechazo no puede superar los 2000 caracteres.'
                );
            }

            if (
                !function_exists('mb_strlen')
                && strlen($reason) > 2000
            ) {
                throw new RuntimeException(
                    'El motivo del rechazo no puede superar los 2000 caracteres.'
                );
            }

            $this->changeStatus(
                advertisementId:
                    $advertisementId,

                expectedStatuses:
                    [
                        AdvertisementStatus::PENDING,
                    ],

                newStatus:
                    AdvertisementStatus::REJECTED,

                notes:
                    $reason,

                additionalFields:
                    [
                        'rejection_reason' =>
                            $reason,

                        'published_at' =>
                            null,

                        'reserved_at' =>
                            null,

                        'closed_at' =>
                            null,

                        'closure_reason' =>
                            null,
                    ]
            );

            $this->clearLastError();

            do_action(
                'dsm_advertisement_rejected',
                $advertisementId,
                $reason,
                get_current_user_id()
            );

            $this->emitAuditEvent(
                'advertisement.rejected',
                $advertisementId,
                [
                    'reason' =>
                        $reason,
                ]
            );

            $status =
                'rejected';
        } catch (Throwable $exception) {
            $this->handleException(
                'rechazando',
                $advertisementId,
                $exception
            );

            $status =
                'error';
        }

        $this->redirectToAdvertisement(
            $advertisementId,
            $status
        );
    }

    /**
     * Marca un anuncio activo como reservado.
     *
     * Al pasar a reserved deja de consumir el límite de activos,
     * según la regla comercial definida.
     */
    public function handleReserve(): void
    {
        $advertisementId =
            $this->prepareAction(
                self::ACTION_RESERVE
            );

        try {
            $this->changeStatus(
                advertisementId:
                    $advertisementId,

                expectedStatuses:
                    [
                        AdvertisementStatus::ACTIVE,
                    ],

                newStatus:
                    AdvertisementStatus::RESERVED,

                notes:
                    'Anuncio marcado como reservado desde administración.',

                additionalFields:
                    [
                        'reserved_at' =>
                            $this->now(),

                        'closed_at' =>
                            null,

                        'closure_reason' =>
                            null,
                    ]
            );

            $this->clearLastError();

            do_action(
                'dsm_advertisement_reserved',
                $advertisementId,
                get_current_user_id()
            );

            $this->emitAuditEvent(
                'advertisement.reserved',
                $advertisementId
            );

            $status =
                'reserved';
        } catch (Throwable $exception) {
            $this->handleException(
                'reservando',
                $advertisementId,
                $exception
            );

            $status =
                'error';
        }

        $this->redirectToAdvertisement(
            $advertisementId,
            $status
        );
    }

    /**
     * Libera un anuncio reservado.
     *
     * Como vuelve a estado active, debe comprobar el límite
     * configurable de anuncios activos del cliente.
     */
    public function handleRelease(): void
    {
        $advertisementId =
            $this->prepareAction(
                self::ACTION_RELEASE
            );

        try {
            $this->changeStatus(
                advertisementId:
                    $advertisementId,

                expectedStatuses:
                    [
                        AdvertisementStatus::RESERVED,
                    ],

                newStatus:
                    AdvertisementStatus::ACTIVE,

                notes:
                    'Reserva liberada desde administración.',

                additionalFields:
                    [
                        'reserved_at' =>
                            null,

                        'closed_at' =>
                            null,

                        'closure_reason' =>
                            null,
                    ]
            );

            $this->clearLastError();

            do_action(
                'dsm_advertisement_reservation_released',
                $advertisementId,
                get_current_user_id()
            );

            $this->emitAuditEvent(
                'advertisement.reservation_released',
                $advertisementId
            );

            $status =
                'released';
        } catch (Throwable $exception) {
            $this->handleException(
                'liberando la reserva',
                $advertisementId,
                $exception
            );

            $status =
                'error';
        }

        $this->redirectToAdvertisement(
            $advertisementId,
            $status
        );
    }

    /**
     * Cierra un anuncio desde administración.
     *
     * Este cierre no equivale a una venta.
     *
     * Por tanto:
     *
     * closure_reason = moderated
     *
     * DSM Promocionar no deberá devolver tiempo promocional
     * por este motivo.
     */
    public function handleClose(): void
    {
        $advertisementId =
            $this->prepareAction(
                self::ACTION_CLOSE
            );

        try {
            $result =
                $this->changeStatus(
                    advertisementId:
                        $advertisementId,

                    expectedStatuses:
                        [
                            AdvertisementStatus::ACTIVE,
                            AdvertisementStatus::RESERVED,
                        ],

                    newStatus:
                        AdvertisementStatus::CLOSED,

                    notes:
                        'Anuncio cerrado desde administración.',

                    additionalFields:
                        [
                            'closed_at' =>
                                $this->now(),

                            'reserved_at' =>
                                null,

                            'closure_reason' =>
                                self::CLOSURE_REASON_MODERATED,
                        ]
                );

            $this->clearLastError();

            do_action(
                'dsm_advertisement_closed',
                $advertisementId,
                (int) $result['customer_id'],
                self::CLOSURE_REASON_MODERATED,
                (string) $result['closed_at']
            );

            do_action(
                'dsm_advertisement_moderated_closed',
                $advertisementId,
                (int) $result['customer_id'],
                get_current_user_id()
            );

            $this->emitAuditEvent(
                'advertisement.closed',
                $advertisementId,
                [
                    'closure_reason' =>
                        self::CLOSURE_REASON_MODERATED,

                    'customer_id' =>
                        (int) $result['customer_id'],
                ]
            );

            $status =
                'closed';
        } catch (Throwable $exception) {
            $this->handleException(
                'cerrando',
                $advertisementId,
                $exception
            );

            $status =
                'error';
        }

        $this->redirectToAdvertisement(
            $advertisementId,
            $status
        );
    }

    /**
     * Comprueba permisos, identificador y nonce.
     */
    private function prepareAction(
        string $action
    ): int {
        $this->assertPermission();

        $advertisementId =
            $this->getAdvertisementId();

        check_admin_referer(
            self::getNonceAction(
                $action,
                $advertisementId
            ),
            self::NONCE_FIELD
        );

        return $advertisementId;
    }

    /**
     * Cambia el estado de un anuncio dentro de una transacción.
     *
     * @param array<int, string> $expectedStatuses
     * @param array<string, mixed> $additionalFields
     *
     * @return array{
     *     advertisement_id:int,
     *     customer_id:int,
     *     previous_status:string,
     *     new_status:string,
     *     closed_at:?string
     * }
     */
    private function changeStatus(
        int $advertisementId,
        array $expectedStatuses,
        string $newStatus,
        ?string $notes = null,
        array $additionalFields = []
    ): array {
        if ($advertisementId <= 0) {
            throw new RuntimeException(
                'El identificador del anuncio no es válido.'
            );
        }

        if (
            !AdvertisementStatus::isValid(
                $newStatus
            )
        ) {
            throw new RuntimeException(
                'El nuevo estado del anuncio no es válido.'
            );
        }

        foreach (
            $expectedStatuses
            as $expectedStatus
        ) {
            if (
                !AdvertisementStatus::isValid(
                    $expectedStatus
                )
            ) {
                throw new RuntimeException(
                    'Uno de los estados de origen no es válido.'
                );
            }
        }

        $started =
            $this->database->query(
                'START TRANSACTION'
            );

        if ($started === false) {
            throw new RuntimeException(
                'No se pudo iniciar la transacción.'
            );
        }

        try {
            $advertisement =
                $this->lockAdvertisement(
                    $advertisementId
                );

            if ($advertisement === null) {
                throw new RuntimeException(
                    'No se encontró el anuncio.'
                );
            }

            $previousStatus =
                sanitize_key(
                    (string) (
                        $advertisement['status']
                        ?? ''
                    )
                );

            if (
                !in_array(
                    $previousStatus,
                    $expectedStatuses,
                    true
                )
            ) {
                throw new RuntimeException(
                    sprintf(
                        'El anuncio no puede pasar de %s a %s.',
                        $previousStatus,
                        $newStatus
                    )
                );
            }

            $this->validateTransition(
                $advertisement,
                $previousStatus,
                $newStatus
            );

            /*
             * Solo al entrar en estado active comprobamos el cupo.
             *
             * Se excluye el propio anuncio para que una transición
             * idempotente o una futura reactivación no se cuente dos veces.
             */
            if (
                $newStatus
                === AdvertisementStatus::ACTIVE
                && $previousStatus
                !== AdvertisementStatus::ACTIVE
            ) {
                $this->assertActiveAdvertisementLimit(
                    customerId:
                        (int) $advertisement[
                            'customer_id'
                        ],

                    excludedAdvertisementId:
                        $advertisementId
                );
            }

            $updateData = [
                'status' =>
                    $newStatus,

                'updated_at' =>
                    $this->now(),
            ];

            foreach (
                $additionalFields
                as $field => $value
            ) {
                if (
                    !in_array(
                        $field,
                        [
                            'rejection_reason',
                            'reserved_at',
                            'published_at',
                            'closed_at',
                            'closure_reason',
                        ],
                        true
                    )
                ) {
                    continue;
                }

                $updateData[$field] =
                    $value;
            }

            $formats =
                $this->buildFormats(
                    $updateData
                );

            $updated =
                $this->database->update(
                    $this->advertisementsTable,
                    $updateData,
                    [
                        'id' =>
                            $advertisementId,
                    ],
                    $formats,
                    [
                        '%d',
                    ]
                );

            if ($updated === false) {
                throw new RuntimeException(
                    'No se pudo actualizar el anuncio: '
                    . $this->database->last_error
                );
            }

            $this->insertStatusHistory(
                advertisementId:
                    $advertisementId,

                previousStatus:
                    $previousStatus,

                newStatus:
                    $newStatus,

                notes:
                    $notes
            );

            $committed =
                $this->database->query(
                    'COMMIT'
                );

            if ($committed === false) {
                throw new RuntimeException(
                    'No se pudo confirmar la transacción.'
                );
            }

            return [
                'advertisement_id' =>
                    $advertisementId,

                'customer_id' =>
                    (int) $advertisement[
                        'customer_id'
                    ],

                'previous_status' =>
                    $previousStatus,

                'new_status' =>
                    $newStatus,

                'closed_at' =>
                    isset(
                        $updateData['closed_at']
                    )
                    && is_string(
                        $updateData['closed_at']
                    )
                        ? $updateData['closed_at']
                        : null,
            ];
        } catch (Throwable $exception) {
            $this->database->query(
                'ROLLBACK'
            );

            throw $exception;
        }
    }

    /**
     * Bloquea y devuelve un anuncio.
     *
     * @return array<string, mixed>|null
     */
    private function lockAdvertisement(
        int $advertisementId
    ): ?array {
        $sql =
            $this->database->prepare(
                "
                SELECT
                    id,
                    customer_id,
                    store_id,
                    category_id,
                    title,
                    description,
                    price,
                    condition_code,
                    status,
                    rejection_reason,
                    reserved_at,
                    published_at,
                    closed_at,
                    closure_reason
                FROM {$this->advertisementsTable}
                WHERE id = %d
                LIMIT 1
                FOR UPDATE
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
            ? $row
            : null;
    }

    /**
     * Valida requisitos propios de la transición.
     *
     * @param array<string, mixed> $advertisement
     */
    private function validateTransition(
        array $advertisement,
        string $previousStatus,
        string $newStatus
    ): void {
        if (
            $newStatus
            === AdvertisementStatus::ACTIVE
            && $previousStatus
            === AdvertisementStatus::PENDING
        ) {
            if (
                trim(
                    (string) (
                        $advertisement['title']
                        ?? ''
                    )
                ) === ''
            ) {
                throw new RuntimeException(
                    'El anuncio no tiene título.'
                );
            }

            if (
                trim(
                    (string) (
                        $advertisement[
                            'description'
                        ]
                        ?? ''
                    )
                ) === ''
            ) {
                throw new RuntimeException(
                    'El anuncio no tiene descripción.'
                );
            }

            if (
                (int) (
                    $advertisement[
                        'category_id'
                    ]
                    ?? 0
                ) <= 0
            ) {
                throw new RuntimeException(
                    'El anuncio no tiene categoría.'
                );
            }

            if (
                trim(
                    (string) (
                        $advertisement[
                            'condition_code'
                        ]
                        ?? ''
                    )
                ) === ''
            ) {
                throw new RuntimeException(
                    'El anuncio no tiene estado de conservación.'
                );
            }

            if (
                (float) (
                    $advertisement['price']
                    ?? 0
                ) < 0
            ) {
                throw new RuntimeException(
                    'El precio del anuncio no es válido.'
                );
            }
        }

        if (
            $newStatus
            === AdvertisementStatus::RESERVED
            && $previousStatus
            !== AdvertisementStatus::ACTIVE
        ) {
            throw new RuntimeException(
                'Solo pueden reservarse anuncios activos.'
            );
        }

        if (
            $newStatus
            === AdvertisementStatus::ACTIVE
            && !in_array(
                $previousStatus,
                [
                    AdvertisementStatus::PENDING,
                    AdvertisementStatus::RESERVED,
                ],
                true
            )
        ) {
            throw new RuntimeException(
                'El anuncio no puede activarse desde su estado actual.'
            );
        }

        if (
            $newStatus
            === AdvertisementStatus::CLOSED
            && !in_array(
                $previousStatus,
                [
                    AdvertisementStatus::ACTIVE,
                    AdvertisementStatus::RESERVED,
                ],
                true
            )
        ) {
            throw new RuntimeException(
                'Solo pueden cerrarse anuncios activos o reservados.'
            );
        }
    }

    /**
     * Verifica el límite configurable de anuncios activos.
     *
     * Mientras DSM Suscripciones no exista:
     *
     * - el límite predeterminado es 10;
     * - puede modificarse con el filtro;
     * - -1 significa ilimitado.
     */
    private function assertActiveAdvertisementLimit(
        int $customerId,
        int $excludedAdvertisementId = 0
    ): void {
        if ($customerId <= 0) {
            throw new RuntimeException(
                'El anuncio no tiene un cliente válido.'
            );
        }

        $defaultLimit =
            self::DEFAULT_ACTIVE_LIMIT;

        /**
         * Filtro sencillo para modificar el límite activo.
         *
         * DSM Suscripciones podrá engancharse aquí.
         *
         * @param int $limit
         * @param int $customerId
         */
        $limit =
            (int) apply_filters(
                'dsm_customer_active_advertisement_limit',
                $defaultLimit,
                $customerId
            );

        /**
         * Filtro alternativo basado en código de capacidad.
         *
         * Permite reutilizar un futuro servicio genérico de
         * características de suscripción.
         *
         * @param int    $value
         * @param int    $customerId
         * @param string $featureCode
         */
        $limit =
            (int) apply_filters(
                'dsm_customer_feature_value',
                $limit,
                $customerId,
                'advertisements.max_active'
            );

        if ($limit === -1) {
            return;
        }

        $limit =
            max(
                0,
                $limit
            );

        $used =
            $this->countActiveAdvertisements(
                $customerId,
                $excludedAdvertisementId
            );

        $result = [
            'allowed' =>
                $used < $limit,

            'limit' =>
                $limit,

            'used' =>
                $used,

            'remaining' =>
                max(
                    0,
                    $limit - $used
                ),
        ];

        /**
         * Permite que otro módulo sustituya o complete la decisión.
         *
         * @param array<string, int|bool> $result
         * @param int                    $customerId
         * @param int                    $excludedAdvertisementId
         */
        $result =
            apply_filters(
                'dsm_customer_can_activate_advertisement',
                $result,
                $customerId,
                $excludedAdvertisementId
            );

        $allowed =
            isset($result['allowed'])
                ? (bool) $result['allowed']
                : false;

        if ($allowed) {
            return;
        }

        $resolvedLimit =
            isset($result['limit'])
                ? (int) $result['limit']
                : $limit;

        $resolvedUsed =
            isset($result['used'])
                ? (int) $result['used']
                : $used;

        throw new RuntimeException(
            sprintf(
                'El cliente ha alcanzado el límite de anuncios activos (%d de %d).',
                $resolvedUsed,
                $resolvedLimit
            )
        );
    }

    /**
     * Cuenta únicamente anuncios con status = active.
     */
    private function countActiveAdvertisements(
        int $customerId,
        int $excludedAdvertisementId = 0
    ): int {
        if ($excludedAdvertisementId > 0) {
            $sql =
                $this->database->prepare(
                    "
                    SELECT COUNT(*)
                    FROM {$this->advertisementsTable}
                    WHERE customer_id = %d
                      AND status = %s
                      AND id <> %d
                    ",
                    $customerId,
                    AdvertisementStatus::ACTIVE,
                    $excludedAdvertisementId
                );
        } else {
            $sql =
                $this->database->prepare(
                    "
                    SELECT COUNT(*)
                    FROM {$this->advertisementsTable}
                    WHERE customer_id = %d
                      AND status = %s
                    ",
                    $customerId,
                    AdvertisementStatus::ACTIVE
                );
        }

        if (!is_string($sql)) {
            return 0;
        }

        return max(
            0,
            (int) $this->database->get_var(
                $sql
            )
        );
    }

    /**
     * Inserta el historial de estado.
     */
    private function insertStatusHistory(
        int $advertisementId,
        ?string $previousStatus,
        string $newStatus,
        ?string $notes
    ): void {
        $inserted =
            $this->database->insert(
                $this->statusHistoryTable,
                [
                    'advertisement_id' =>
                        $advertisementId,

                    'previous_status' =>
                        $previousStatus,

                    'new_status' =>
                        $newStatus,

                    'changed_by_customer_id' =>
                        null,

                    'changed_by_user_id' =>
                        get_current_user_id(),

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

        if ($inserted === false) {
            throw new RuntimeException(
                'No se pudo registrar el historial del anuncio: '
                . $this->database->last_error
            );
        }
    }

    /**
     * @param array<string, mixed> $data
     *
     * @return array<int, string>
     */
    private function buildFormats(
        array $data
    ): array {
        $formats = [];

        foreach ($data as $value) {
            $formats[] =
                is_int($value)
                    ? '%d'
                    : '%s';
        }

        return $formats;
    }

    /**
     * Obtiene el ID enviado por POST.
     */
    private function getAdvertisementId(): int
    {
        $advertisementId =
            isset($_POST['advertisement_id'])
                ? absint(
                    wp_unslash(
                        (string) $_POST[
                            'advertisement_id'
                        ]
                    )
                )
                : 0;

        if ($advertisementId <= 0) {
            wp_die(
                esc_html__(
                    'No se ha indicado un anuncio válido.',
                    'dsm-anuncios'
                ),
                esc_html__(
                    'Solicitud no válida',
                    'dsm-anuncios'
                ),
                [
                    'response' => 400,
                ]
            );
        }

        return $advertisementId;
    }

    /**
     * Construye el nonce de una acción.
     */
    public static function getNonceAction(
        string $action,
        int $advertisementId
    ): string {
        return $action
            . '_'
            . $advertisementId;
    }

    /**
     * Guarda un error temporal.
     */
    private function storeLastError(
        string $message
    ): void {
        $message =
            sanitize_text_field(
                $message
            );

        if ($message === '') {
            $message =
                __(
                    'No se pudo completar la acción.',
                    'dsm-anuncios'
                );
        }

        set_transient(
            self::ERROR_TRANSIENT_PREFIX
            . get_current_user_id(),
            $message,
            5 * MINUTE_IN_SECONDS
        );
    }

    /**
     * Elimina el error administrativo anterior.
     */
    private function clearLastError(): void
    {
        delete_transient(
            self::ERROR_TRANSIENT_PREFIX
            . get_current_user_id()
        );
    }

    /**
     * Recupera el último error para AdvertisementsPage.
     */
    public static function getLastError(): string
    {
        $error =
            get_transient(
                self::ERROR_TRANSIENT_PREFIX
                . get_current_user_id()
            );

        return is_string($error)
            ? $error
            : '';
    }

    /**
     * Registra una excepción.
     */
    private function handleException(
        string $operation,
        int $advertisementId,
        Throwable $exception
    ): void {
        $this->storeLastError(
            $exception->getMessage()
        );

        error_log(
            '[DSM Anuncios] Error '
            . $operation
            . ' el anuncio '
            . $advertisementId
            . ': '
            . $exception->getMessage()
        );
    }

    /**
     * Emite un evento para la futura auditoría.
     *
     * @param array<string, mixed> $extra
     */
    private function emitAuditEvent(
        string $event,
        int $advertisementId,
        array $extra = []
    ): void {
        do_action(
            'dsm_audit_event',
            $event,
            array_merge(
                [
                    'advertisement_id' =>
                        $advertisementId,

                    'actor_type' =>
                        'wordpress_user',

                    'actor_id' =>
                        get_current_user_id(),
                ],
                $extra
            )
        );
    }

    /**
     * Redirige al detalle del anuncio.
     */
    private function redirectToAdvertisement(
        int $advertisementId,
        string $status
    ): never {
        wp_safe_redirect(
            add_query_arg(
                [
                    'page' =>
                        AdvertisementsPage::
                            MENU_SLUG,

                    'view' =>
                        'detail',

                    'advertisement_id' =>
                        $advertisementId,

                    'dsm_ad_status' =>
                        $status,
                ],
                admin_url('admin.php')
            )
        );

        exit;
    }

    /**
     * Fecha UTC para almacenamiento.
     */
    private function now(): string
    {
        return current_time(
            'mysql',
            true
        );
    }

    /**
     * Comprueba permisos administrativos.
     */
    private function assertPermission(): void
    {
        if (
            current_user_can(
                self::CAPABILITY
            )
        ) {
            return;
        }

        wp_die(
            esc_html__(
                'No tienes permisos para moderar anuncios.',
                'dsm-anuncios'
            ),
            esc_html__(
                'Acceso denegado',
                'dsm-anuncios'
            ),
            [
                'response' => 403,
            ]
        );
    }
}