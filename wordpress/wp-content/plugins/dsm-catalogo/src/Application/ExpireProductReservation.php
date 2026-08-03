<?php

declare(strict_types=1);

namespace DSM\Catalogo\Application;

use DateTimeImmutable;
use DSM\Catalogo\Inventory\StockMovementType;
use DSM\Catalogo\Reservation\ProductReservation;
use DSM\Catalogo\Reservation\ProductReservationRepository;
use DSM\Catalogo\Stock\StockResult;
use DSM\Catalogo\Stock\StockService;
use RuntimeException;
use Throwable;

if (!defined('ABSPATH')) {
    exit;
}

final class ExpireProductReservation
{
    public function __construct(
        private readonly ProductReservationRepository $reservationRepository,
        private readonly StockService $stockService
    ) {
    }

    /**
     * @return array{
     *     reservation: ProductReservation,
     *     stock: StockResult
     * }
     */
    public function execute(
        int $reservationId,
        ?int $userId = null,
        ?string $notes = null,
        ?DateTimeImmutable $moment = null
    ): array {
        global $wpdb;

        if ($reservationId <= 0) {
            throw new RuntimeException(
                'El identificador de la reserva no es válido.'
            );
        }

        if (
            $userId !== null
            && $userId <= 0
        ) {
            throw new RuntimeException(
                'El identificador del usuario de WordPress no es válido.'
            );
        }

        $reservation =
            $this->reservationRepository->findById(
                $reservationId
            );

        if ($reservation === null) {
            throw new RuntimeException(
                'No se encontró la reserva.'
            );
        }

        if (!$reservation->canExpire()) {
            throw new RuntimeException(
                'La reserva no se puede caducar en su estado actual.'
            );
        }

        if (!$reservation->hasExpiration()) {
            throw new RuntimeException(
                'La reserva no tiene fecha prevista de caducidad.'
            );
        }

        $moment ??= new DateTimeImmutable(
            current_time(
                'mysql',
                true
            )
        );

        if (!$reservation->isExpiredAt($moment)) {
            throw new RuntimeException(
                'La reserva todavía no ha alcanzado su fecha de caducidad.'
            );
        }

        $wpdb->query(
            'START TRANSACTION'
        );

        try {
            $stockResult =
                $this->stockService
                    ->releaseReservationWithinTransaction(
                        variantId:
                            $reservation->getVariantId(),

                        storeId:
                            $reservation->getStoreId(),

                        quantity:
                            $reservation->getQuantity(),

                        reservationId:
                            $reservation->getId(),

                        customerId:
                            $reservation->getSellerCustomerId(),

                        userId:
                            $userId,

                        notes:
                            $notes
                            ?? 'Liberación automática por caducidad de la reserva.',

                        movementType:
                            StockMovementType::EXPIRATION
                    );

            $this->reservationRepository
                ->markExpired(
                    $reservationId,
                    $moment
                );

            $expiredReservation =
                $this->reservationRepository->findById(
                    $reservationId
                );

            if ($expiredReservation === null) {
                throw new RuntimeException(
                    'La reserva se marcó como caducada, pero no pudo recuperarse.'
                );
            }

            $wpdb->query(
                'COMMIT'
            );

            return [
                'reservation' =>
                    $expiredReservation,

                'stock' =>
                    $stockResult,
            ];
        } catch (Throwable $exception) {
            $wpdb->query(
                'ROLLBACK'
            );

            throw $exception;
        }
    }
}