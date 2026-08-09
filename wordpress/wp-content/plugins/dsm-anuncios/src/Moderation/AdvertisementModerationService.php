<?php

declare(strict_types=1);

namespace DSM\Anuncios\Moderation;

use DSM\Anuncios\Advertisement\Advertisement;
use DSM\Anuncios\Advertisement\AdvertisementRepository;
use DSM\Anuncios\Advertisement\AdvertisementStatus;
use RuntimeException;
use Throwable;
use wpdb;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Servicio central de moderación y transición de estados.
 *
 * Garantiza:
 *
 * - validación de transiciones;
 * - comprobación del responsable;
 * - control del límite de anuncios abiertos;
 * - actualización del anuncio;
 * - registro del historial;
 * - persistencia del motivo de cierre;
 * - transacciones;
 * - emisión de eventos para otros plugins.
 */
final class AdvertisementModerationService
{
    public const CLOSURE_REASON_SOLD =
        'sold';

    public const CLOSURE_REASON_WITHDRAWN =
        'withdrawn';

    public const CLOSURE_REASON_MODERATED =
        'moderated';

    public const CLOSURE_REASON_EXPIRED =
        'expired';

    private const DEFAULT_OPEN_LIMIT =
        10;

    private wpdb $database;

    private string $advertisementsTable;

    public function __construct(
        private readonly AdvertisementRepository $advertisementRepository,
        private readonly AdvertisementStatusHistoryRepository $historyRepository
    ) {
        global $wpdb;

        $this->database =
            $wpdb;

        $this->advertisementsTable =
            $wpdb->prefix
            . 'dsm_ads';
    }

    /**
     * Envía un anuncio del cliente a revisión.
     *
     * Transiciones:
     *
     * - draft -> pending
     * - rejected -> pending
     */
    public function submitForReview(
        int $customerId,
        int $advertisementId,
        ?string $notes = null
    ): Advertisement {
        $advertisement =
            $this->resolveOwnedAdvertisement(
                $customerId,
                $advertisementId
            );

        if (
            !AdvertisementStatus::canBeSubmitted(
                $advertisement->getStatus()
            )
        ) {
            throw new RuntimeException(
                'El anuncio no se puede enviar a revisión '
                . 'desde su estado actual.'
            );
        }

        return $this->changeStatusByCustomer(
            $advertisement,
            AdvertisementStatus::PENDING,
            $customerId,
            $notes
                ?? 'Anuncio enviado a revisión por el cliente.'
        );
    }

    /**
     * Publica un anuncio pendiente desde administración.
     *
     * Transición:
     *
     * - pending -> active
     */
    public function publish(
        int $advertisementId,
        int $userId,
        ?string $notes = null
    ): Advertisement {
        $advertisement =
            $this->resolveAdvertisement(
                $advertisementId
            );

        $this->validateUserId(
            $userId
        );

        if (
            !AdvertisementStatus::canBePublished(
                $advertisement->getStatus()
            )
        ) {
            throw new RuntimeException(
                'El anuncio no se puede publicar '
                . 'desde su estado actual.'
            );
        }

        return $this->changeStatusByUser(
            $advertisement,
            AdvertisementStatus::ACTIVE,
            $userId,
            $notes
                ?? 'Anuncio aprobado y publicado desde administración.'
        );
    }

    /**
     * Rechaza un anuncio pendiente desde administración.
     *
     * Transición:
     *
     * - pending -> rejected
     */
    public function reject(
        int $advertisementId,
        int $userId,
        string $reason,
        ?string $notes = null
    ): Advertisement {
        $advertisement =
            $this->resolveAdvertisement(
                $advertisementId
            );

        $this->validateUserId(
            $userId
        );

        if (
            !AdvertisementStatus::canBeRejected(
                $advertisement->getStatus()
            )
        ) {
            throw new RuntimeException(
                'El anuncio no se puede rechazar '
                . 'desde su estado actual.'
            );
        }

        $reason =
            sanitize_textarea_field(
                trim(
                    $reason
                )
            );

        if ($reason === '') {
            throw new RuntimeException(
                'El motivo del rechazo es obligatorio.'
            );
        }

        if (mb_strlen($reason) > 2000) {
            throw new RuntimeException(
                'El motivo del rechazo no puede superar '
                . 'los 2000 caracteres.'
            );
        }

        $historyNotes =
            $notes !== null
            && trim($notes) !== ''
                ? $notes
                : 'Anuncio rechazado. Motivo: '
                    . $reason;

        return $this->changeStatusByUser(
            $advertisement,
            AdvertisementStatus::REJECTED,
            $userId,
            $historyNotes,
            $reason
        );
    }

    /**
     * Marca como reservado un anuncio del cliente.
     *
     * Transición:
     *
     * - active -> reserved
     */
    public function reserve(
        int $customerId,
        int $advertisementId,
        ?string $notes = null
    ): Advertisement {
        $advertisement =
            $this->resolveOwnedAdvertisement(
                $customerId,
                $advertisementId
            );

        $this->assertCanBeReserved(
            $advertisement
        );

        return $this->changeStatusByCustomer(
            $advertisement,
            AdvertisementStatus::RESERVED,
            $customerId,
            $notes
                ?? 'Anuncio marcado como reservado por el cliente.'
        );
    }

    /**
     * Marca como reservado un anuncio desde administración.
     */
    public function reserveByUser(
        int $advertisementId,
        int $userId,
        ?string $notes = null
    ): Advertisement {
        $advertisement =
            $this->resolveAdvertisement(
                $advertisementId
            );

        $this->validateUserId(
            $userId
        );

        $this->assertCanBeReserved(
            $advertisement
        );

        return $this->changeStatusByUser(
            $advertisement,
            AdvertisementStatus::RESERVED,
            $userId,
            $notes
                ?? 'Anuncio marcado como reservado desde administración.'
        );
    }

    /**
     * Libera una reserva realizada por el cliente.
     *
     * Transición:
     *
     * - reserved -> active
     */
    public function releaseReservation(
        int $customerId,
        int $advertisementId,
        ?string $notes = null
    ): Advertisement {
        $advertisement =
            $this->resolveOwnedAdvertisement(
                $customerId,
                $advertisementId
            );

        $this->assertCanBeReleased(
            $advertisement
        );

        return $this->changeStatusByCustomer(
            $advertisement,
            AdvertisementStatus::ACTIVE,
            $customerId,
            $notes
                ?? 'Reserva liberada por el cliente.'
        );
    }

    /**
     * Libera una reserva desde administración.
     */
    public function releaseReservationByUser(
        int $advertisementId,
        int $userId,
        ?string $notes = null
    ): Advertisement {
        $advertisement =
            $this->resolveAdvertisement(
                $advertisementId
            );

        $this->validateUserId(
            $userId
        );

        $this->assertCanBeReleased(
            $advertisement
        );

        return $this->changeStatusByUser(
            $advertisement,
            AdvertisementStatus::ACTIVE,
            $userId,
            $notes
                ?? 'Reserva liberada desde administración.'
        );
    }

    /**
     * Cierra un anuncio perteneciente al cliente.
     *
     * Transiciones:
     *
     * - active -> closed
     * - reserved -> closed
     */
    public function close(
        int $customerId,
        int $advertisementId,
        ?string $closureReason = null,
        ?string $notes = null
    ): Advertisement {
        $advertisement =
            $this->resolveOwnedAdvertisement(
                $customerId,
                $advertisementId
            );

        $this->assertCanBeClosed(
            $advertisement
        );

        $closureReason =
            $this->normalizeClosureReason(
                $closureReason
                ?? self::CLOSURE_REASON_WITHDRAWN
            );

        return $this->changeStatusByCustomer(
            $advertisement,
            AdvertisementStatus::CLOSED,
            $customerId,
            $notes
                ?? 'Anuncio cerrado por el cliente.',
            null,
            $closureReason
        );
    }

    /**
     * Cierra un anuncio desde administración.
     */
    public function closeByUser(
        int $advertisementId,
        int $userId,
        ?string $closureReason = null,
        ?string $notes = null
    ): Advertisement {
        $advertisement =
            $this->resolveAdvertisement(
                $advertisementId
            );

        $this->validateUserId(
            $userId
        );

        $this->assertCanBeClosed(
            $advertisement
        );

        $closureReason =
            $this->normalizeClosureReason(
                $closureReason
                ?? self::CLOSURE_REASON_MODERATED
            );

        return $this->changeStatusByUser(
            $advertisement,
            AdvertisementStatus::CLOSED,
            $userId,
            $notes
                ?? 'Anuncio cerrado desde administración.',
            null,
            $closureReason
        );
    }

    /**
     * Cambia el estado automáticamente desde el sistema.
     *
     * Queda disponible para:
     *
     * - tareas programadas;
     * - reglas automáticas;
     * - integraciones internas;
     * - DSM Reglas.
     */
    public function changeStatusBySystem(
        int $advertisementId,
        string $newStatus,
        ?string $notes = null,
        ?string $rejectionReason = null,
        ?string $closureReason = null
    ): Advertisement {
        $advertisement =
            $this->resolveAdvertisement(
                $advertisementId
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
                'El nuevo estado del anuncio no es válido.'
            );
        }

        if (
            $advertisement->getStatus()
            === $newStatus
        ) {
            return $advertisement;
        }

        if ($newStatus === AdvertisementStatus::ACTIVE) {
            $this->assertOpenAdvertisementLimit(
                $advertisement
            );
        }

        if ($newStatus === AdvertisementStatus::CLOSED) {
            $closureReason =
                $this->normalizeClosureReason(
                    $closureReason
                    ?? self::CLOSURE_REASON_EXPIRED
                );
        }

        return $this->performStatusChange(
            $advertisement,
            $newStatus,
            null,
            null,
            $notes,
            $rejectionReason,
            $closureReason
        );
    }

    /**
     * Ejecuta un cambio atribuido al cliente.
     */
    private function changeStatusByCustomer(
        Advertisement $advertisement,
        string $newStatus,
        int $customerId,
        ?string $notes = null,
        ?string $rejectionReason = null,
        ?string $closureReason = null
    ): Advertisement {
        if ($customerId <= 0) {
            throw new RuntimeException(
                'El identificador del cliente no es válido.'
            );
        }

        if (
            !$advertisement->belongsToCustomer(
                $customerId
            )
        ) {
            throw new RuntimeException(
                'El anuncio no pertenece al cliente indicado.'
            );
        }

        if ($newStatus === AdvertisementStatus::ACTIVE) {
            $this->assertOpenAdvertisementLimit(
                $advertisement
            );
        }

        return $this->performStatusChange(
            $advertisement,
            $newStatus,
            $customerId,
            null,
            $notes,
            $rejectionReason,
            $closureReason
        );
    }

    /**
     * Ejecuta un cambio atribuido a un usuario de WordPress.
     */
    private function changeStatusByUser(
        Advertisement $advertisement,
        string $newStatus,
        int $userId,
        ?string $notes = null,
        ?string $rejectionReason = null,
        ?string $closureReason = null
    ): Advertisement {
        $this->validateUserId(
            $userId
        );

        if ($newStatus === AdvertisementStatus::ACTIVE) {
            $this->assertOpenAdvertisementLimit(
                $advertisement
            );
        }

        return $this->performStatusChange(
            $advertisement,
            $newStatus,
            null,
            $userId,
            $notes,
            $rejectionReason,
            $closureReason
        );
    }

    /**
     * Actualiza el estado y registra el historial dentro
     * de una transacción.
     */
    private function performStatusChange(
        Advertisement $advertisement,
        string $newStatus,
        ?int $changedByCustomerId,
        ?int $changedByUserId,
        ?string $notes,
        ?string $rejectionReason,
        ?string $closureReason
    ): Advertisement {
        $previousStatus =
            $advertisement->getStatus();

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
                'El nuevo estado del anuncio no es válido.'
            );
        }

        if ($previousStatus === $newStatus) {
            return $advertisement;
        }

        $notes =
            $this->normalizeNotes(
                $notes
            );

        if ($newStatus !== AdvertisementStatus::CLOSED) {
            $closureReason =
                null;
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
            $this->advertisementRepository
                ->updateStatus(
                    $advertisement->getId(),
                    $newStatus,
                    $rejectionReason
                );

            $this->updateClosureReason(
                $advertisement->getId(),
                $closureReason
            );

            $this->historyRepository
                ->addEntry(
                    $advertisement->getId(),
                    $previousStatus,
                    $newStatus,
                    $changedByCustomerId,
                    $changedByUserId,
                    $notes
                );

            $updatedAdvertisement =
                $this->advertisementRepository
                    ->findById(
                        $advertisement->getId()
                    );

            if ($updatedAdvertisement === null) {
                throw new RuntimeException(
                    'El anuncio se actualizó, pero no pudo recuperarse.'
                );
            }

            $committed =
                $this->database->query(
                    'COMMIT'
                );

            if ($committed === false) {
                throw new RuntimeException(
                    'No se pudo confirmar la transacción.'
                );
            }
        } catch (Throwable $exception) {
            $this->database->query(
                'ROLLBACK'
            );

            throw $exception;
        }

        $this->emitStatusEvents(
            $updatedAdvertisement,
            $previousStatus,
            $newStatus,
            $changedByCustomerId,
            $changedByUserId,
            $notes,
            $rejectionReason,
            $closureReason
        );

        return $updatedAdvertisement;
    }

    private function assertCanBeReserved(
        Advertisement $advertisement
    ): void {
        if (
            AdvertisementStatus::canBeReserved(
                $advertisement->getStatus()
            )
        ) {
            return;
        }

        throw new RuntimeException(
            'El anuncio no se puede reservar '
            . 'desde su estado actual.'
        );
    }

    private function assertCanBeReleased(
        Advertisement $advertisement
    ): void {
        if (
            AdvertisementStatus::canBeReleased(
                $advertisement->getStatus()
            )
        ) {
            return;
        }

        throw new RuntimeException(
            'La reserva no se puede liberar '
            . 'desde el estado actual del anuncio.'
        );
    }

    private function assertCanBeClosed(
        Advertisement $advertisement
    ): void {
        if (
            AdvertisementStatus::canBeClosed(
                $advertisement->getStatus()
            )
        ) {
            return;
        }

        throw new RuntimeException(
            'El anuncio no se puede cerrar '
            . 'desde su estado actual.'
        );
    }

    /**
     * Comprueba el límite de anuncios abiertos.
     *
     * DSM Suscripciones podrá modificar la decisión
     * mediante filtros.
     */
    private function assertOpenAdvertisementLimit(
        Advertisement $advertisement
    ): void {
        $customerId =
            $advertisement->getCustomerId();

        if ($customerId <= 0) {
            throw new RuntimeException(
                'El anuncio no tiene un cliente válido.'
            );
        }

        /*
         * Si el anuncio ya cuenta dentro del límite no se
         * añade una plaza adicional.
         */
        $alreadyCounts =
            AdvertisementStatus::countsTowardsActiveLimit(
                $advertisement->getStatus()
            );

        $used =
            $this->advertisementRepository
                ->countActiveForCustomer(
                    $customerId
                );

        if ($alreadyCounts) {
            $used =
                max(
                    0,
                    $used - 1
                );
        }

        $limit =
            (int) apply_filters(
                'dsm_customer_active_advertisement_limit',
                self::DEFAULT_OPEN_LIMIT,
                $customerId
            );

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

        $result =
            apply_filters(
                'dsm_customer_can_activate_advertisement',
                $result,
                $customerId,
                $advertisement->getId()
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
                'El cliente ha alcanzado el límite '
                . 'de anuncios abiertos (%d de %d).',
                $resolvedUsed,
                $resolvedLimit
            )
        );
    }

    /**
     * Actualiza el motivo de cierre.
     *
     * Se mantiene aquí temporalmente hasta ampliar la firma
     * de AdvertisementRepository::updateStatus().
     */
    private function updateClosureReason(
        int $advertisementId,
        ?string $closureReason
    ): void {
        $updated =
            $this->database->update(
                $this->advertisementsTable,
                [
                    'closure_reason' =>
                        $closureReason,

                    'updated_at' =>
                        current_time(
                            'mysql',
                            true
                        ),
                ],
                [
                    'id' =>
                        $advertisementId,
                ],
                [
                    '%s',
                    '%s',
                ],
                [
                    '%d',
                ]
            );

        if ($updated === false) {
            throw new RuntimeException(
                'No se pudo actualizar el motivo de cierre: '
                . $this->database->last_error
            );
        }
    }

    /**
     * Emite eventos después del COMMIT.
     */
    private function emitStatusEvents(
        Advertisement $advertisement,
        string $previousStatus,
        string $newStatus,
        ?int $changedByCustomerId,
        ?int $changedByUserId,
        ?string $notes,
        ?string $rejectionReason,
        ?string $closureReason
    ): void {
        do_action(
            'dsm_advertisement_status_changed',
            $advertisement,
            $previousStatus,
            $newStatus,
            $changedByCustomerId,
            $changedByUserId,
            $notes
        );

        do_action(
            'dsm_advertisement_status_changed_'
            . $newStatus,
            $advertisement,
            $previousStatus,
            $changedByCustomerId,
            $changedByUserId,
            $notes
        );

        switch ($newStatus) {
            case AdvertisementStatus::ACTIVE:
                if (
                    $previousStatus
                    === AdvertisementStatus::RESERVED
                ) {
                    do_action(
                        'dsm_advertisement_reservation_released',
                        $advertisement->getId(),
                        $changedByUserId
                        ?? $changedByCustomerId
                        ?? 0
                    );
                } else {
                    do_action(
                        'dsm_advertisement_published',
                        $advertisement->getId(),
                        $changedByUserId
                        ?? $changedByCustomerId
                        ?? 0
                    );
                }
                break;

            case AdvertisementStatus::REJECTED:
                do_action(
                    'dsm_advertisement_rejected',
                    $advertisement->getId(),
                    $rejectionReason,
                    $changedByUserId
                    ?? 0
                );
                break;

            case AdvertisementStatus::RESERVED:
                do_action(
                    'dsm_advertisement_reserved',
                    $advertisement->getId(),
                    $changedByUserId
                    ?? $changedByCustomerId
                    ?? 0
                );
                break;

            case AdvertisementStatus::CLOSED:
                do_action(
                    'dsm_advertisement_closed',
                    $advertisement->getId(),
                    $advertisement->getCustomerId(),
                    $closureReason,
                    $advertisement
                        ->getClosedAt()
                        ?->format('Y-m-d H:i:s')
                );

                if (
                    $closureReason
                    === self::CLOSURE_REASON_MODERATED
                ) {
                    do_action(
                        'dsm_advertisement_moderated_closed',
                        $advertisement->getId(),
                        $advertisement->getCustomerId(),
                        $changedByUserId
                        ?? 0
                    );
                }
                break;
        }
    }

    /**
     * Recupera un anuncio o genera un error claro.
     */
    private function resolveAdvertisement(
        int $advertisementId
    ): Advertisement {
        if ($advertisementId <= 0) {
            throw new RuntimeException(
                'El identificador del anuncio no es válido.'
            );
        }

        $advertisement =
            $this->advertisementRepository
                ->findById(
                    $advertisementId
                );

        if ($advertisement === null) {
            throw new RuntimeException(
                'No se encontró el anuncio.'
            );
        }

        return $advertisement;
    }

    /**
     * Recupera un anuncio y comprueba su propietario.
     */
    private function resolveOwnedAdvertisement(
        int $customerId,
        int $advertisementId
    ): Advertisement {
        if ($customerId <= 0) {
            throw new RuntimeException(
                'El identificador del cliente no es válido.'
            );
        }

        $advertisement =
            $this->resolveAdvertisement(
                $advertisementId
            );

        if (
            !$advertisement->belongsToCustomer(
                $customerId
            )
        ) {
            throw new RuntimeException(
                'No tienes permisos para modificar este anuncio.'
            );
        }

        return $advertisement;
    }

    /**
     * Verifica que el usuario de WordPress exista.
     */
    private function validateUserId(
        int $userId
    ): void {
        if ($userId <= 0) {
            throw new RuntimeException(
                'El identificador del usuario no es válido.'
            );
        }

        if (get_user_by('id', $userId) === false) {
            throw new RuntimeException(
                'No se encontró el usuario de WordPress indicado.'
            );
        }
    }

    private function normalizeClosureReason(
        ?string $closureReason
    ): string {
        $closureReason =
            sanitize_key(
                (string) $closureReason
            );

        if (
            !in_array(
                $closureReason,
                [
                    self::CLOSURE_REASON_SOLD,
                    self::CLOSURE_REASON_WITHDRAWN,
                    self::CLOSURE_REASON_MODERATED,
                    self::CLOSURE_REASON_EXPIRED,
                ],
                true
            )
        ) {
            throw new RuntimeException(
                'El motivo de cierre no es válido.'
            );
        }

        return $closureReason;
    }

    private function normalizeNotes(
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

        if (mb_strlen($notes) > 5000) {
            throw new RuntimeException(
                'Las notas no pueden superar los 5000 caracteres.'
            );
        }

        return $notes;
    }
}