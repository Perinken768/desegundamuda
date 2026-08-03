<?php

declare(strict_types=1);

namespace DSM\Catalogo\Application;

use DateTimeImmutable;
use DSM\Catalogo\Reservation\ProductReservationRepository;
use Throwable;

if (!defined('ABSPATH')) {
    exit;
}

final class ProcessExpiredReservations
{
    public function __construct(
        private readonly ProductReservationRepository $reservationRepository,
        private readonly ExpireProductReservation $expireReservation
    ) {
    }

    /**
     * Procesa las reservas activas cuya fecha expires_at
     * ya se ha alcanzado.
     *
     * Cada reserva se procesa mediante su propia transacción.
     * El fallo de una reserva no detiene el resto del lote.
     *
     * @return array{
     *     checked: int,
     *     expired: int,
     *     failed: int,
     *     reservation_ids: array<int, int>,
     *     errors: array<int, array{
     *         reservation_id: int,
     *         message: string
     *     }>
     * }
     */
    public function execute(
        int $limit = 100,
        ?DateTimeImmutable $moment = null
    ): array {
        $limit = max(
            1,
            min(
                500,
                $limit
            )
        );

        $moment ??= new DateTimeImmutable(
            current_time(
                'mysql',
                true
            )
        );

        /*
         * El mismo momento se utiliza para:
         *
         * 1. Buscar las reservas cuya fecha expires_at
         *    ya se ha alcanzado.
         *
         * 2. Validar individualmente la caducidad.
         *
         * 3. Registrar la fecha real expired_at.
         */
        $candidates =
            $this->reservationRepository
                ->findExpiredCandidates(
                    $limit,
                    $moment
                );

        $checked = 0;
        $expired = 0;
        $failed = 0;

        $reservationIds = [];
        $errors = [];

        foreach ($candidates as $reservation) {
            $checked++;

            try {
                /*
                 * El servicio individual vuelve a recuperar y
                 * validar la reserva antes de modificar el stock.
                 *
                 * Esto protege el lote frente a cambios realizados
                 * entre la consulta inicial y el procesamiento.
                 */
                $this->expireReservation->execute(
                    reservationId:
                        $reservation->getId(),

                    userId:
                        null,

                    notes:
                        'Reserva caducada automáticamente por el procesador de DSM Catálogo.',

                    moment:
                        $moment
                );

                $expired++;

                $reservationIds[] =
                    $reservation->getId();
            } catch (Throwable $exception) {
                $failed++;

                $errors[] = [
                    'reservation_id' =>
                        $reservation->getId(),

                    'message' =>
                        $exception->getMessage(),
                ];

                /*
                 * El error se registra sin detener las siguientes
                 * reservas del lote.
                 */
                error_log(
                    sprintf(
                        '[DSM Catálogo] No se pudo caducar la reserva %d: %s',
                        $reservation->getId(),
                        $exception->getMessage()
                    )
                );
            }
        }

        return [
            'checked' =>
                $checked,

            'expired' =>
                $expired,

            'failed' =>
                $failed,

            'reservation_ids' =>
                $reservationIds,

            'errors' =>
                $errors,
        ];
    }
}