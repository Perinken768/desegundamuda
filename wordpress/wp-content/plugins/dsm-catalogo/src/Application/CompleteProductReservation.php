<?php

declare(strict_types=1);

namespace DSM\Catalogo\Application;

use DSM\Catalogo\Reservation\ProductReservation;
use DSM\Catalogo\Reservation\ProductReservationRepository;
use DSM\Catalogo\Stock\StockResult;
use DSM\Catalogo\Stock\StockService;
use RuntimeException;
use Throwable;

if (!defined('ABSPATH')) {
    exit;
}

final class CompleteProductReservation
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
        int $storeId,
        int $sellerCustomerId,
        int $reservationId,
        ?int $userId = null,
        ?string $notes = null
    ): array {
        global $wpdb;

        if ($storeId <= 0) {
            throw new RuntimeException(
                'El identificador de la tienda no es válido.'
            );
        }

        if ($sellerCustomerId <= 0) {
            throw new RuntimeException(
                'El identificador del vendedor no es válido.'
            );
        }

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

        if (
            !$reservation->belongsToStore(
                $storeId
            )
        ) {
            throw new RuntimeException(
                'La reserva no pertenece a la tienda indicada.'
            );
        }

        if (
            !$reservation->belongsToSeller(
                $sellerCustomerId
            )
        ) {
            throw new RuntimeException(
                'No tienes permisos para completar esta reserva.'
            );
        }

        if (!$reservation->canBeCompleted()) {
            throw new RuntimeException(
                'La reserva no se puede completar en su estado actual.'
            );
        }

        $wpdb->query('START TRANSACTION');

        try {
            $stockResult =
                $this->stockService
                    ->completeSaleWithinTransaction(
                        variantId:
                            $reservation->getVariantId(),

                        storeId:
                            $reservation->getStoreId(),

                        quantity:
                            $reservation->getQuantity(),

                        reservationId:
                            $reservation->getId(),

                        customerId:
                            $sellerCustomerId,

                        userId:
                            $userId,

                        notes:
                            $notes
                    );

            $this->reservationRepository
                ->markCompleted(
                    $reservationId
                );

            $completedReservation =
                $this->reservationRepository->findById(
                    $reservationId
                );

            if ($completedReservation === null) {
                throw new RuntimeException(
                    'La reserva se completó, pero no pudo recuperarse.'
                );
            }

            $wpdb->query('COMMIT');

            return [
                'reservation' =>
                    $completedReservation,

                'stock' =>
                    $stockResult,
            ];
        } catch (Throwable $exception) {
            $wpdb->query('ROLLBACK');

            throw $exception;
        }
    }
}