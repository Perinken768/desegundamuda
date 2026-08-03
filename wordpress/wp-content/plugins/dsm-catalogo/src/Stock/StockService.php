<?php

declare(strict_types=1);

namespace DSM\Catalogo\Stock;

use DSM\Catalogo\Inventory\StockMovementRepository;
use DSM\Catalogo\Inventory\StockMovementType;
use RuntimeException;
use Throwable;

if (!defined('ABSPATH')) {
    exit;
}

final class StockService
{
    private string $variantsTable;

    public function __construct(
        private readonly StockMovementRepository $movementRepository
    ) {
        global $wpdb;

        $this->variantsTable =
            $wpdb->prefix
            . 'dsm_product_variants';
    }

    public function initialize(
        int $variantId,
        int $storeId,
        int $quantity,
        ?int $customerId = null,
        ?int $userId = null,
        ?string $notes = null
    ): StockResult {
        if ($quantity < 0) {
            throw new RuntimeException(
                'El stock inicial no puede ser negativo.'
            );
        }

        return $this->changeStock(
            variantId: $variantId,
            storeId: $storeId,
            movementType: StockMovementType::INITIAL,
            quantityDelta: $quantity,
            reservedDelta: 0,
            referenceType: null,
            referenceId: null,
            customerId: $customerId,
            userId: $userId,
            notes: $notes,
            requireZeroPhysicalStock: true
        );
    }

    public function initializeWithinTransaction(
        int $variantId,
        int $storeId,
        int $quantity,
        ?int $customerId = null,
        ?int $userId = null,
        ?string $notes = null
    ): StockResult {
        if ($quantity < 0) {
            throw new RuntimeException(
                'El stock inicial no puede ser negativo.'
            );
        }

        return $this->changeStock(
            variantId: $variantId,
            storeId: $storeId,
            movementType: StockMovementType::INITIAL,
            quantityDelta: $quantity,
            reservedDelta: 0,
            referenceType: null,
            referenceId: null,
            customerId: $customerId,
            userId: $userId,
            notes: $notes,
            requireZeroPhysicalStock: true,
            manageTransaction: false
        );
    }

    public function replenish(
        int $variantId,
        int $storeId,
        int $quantity,
        ?int $customerId = null,
        ?int $userId = null,
        ?string $notes = null
    ): StockResult {
        if ($quantity <= 0) {
            throw new RuntimeException(
                'La cantidad a reponer debe ser mayor que cero.'
            );
        }

        return $this->changeStock(
            variantId: $variantId,
            storeId: $storeId,
            movementType: StockMovementType::REPLENISHMENT,
            quantityDelta: $quantity,
            reservedDelta: 0,
            referenceType: null,
            referenceId: null,
            customerId: $customerId,
            userId: $userId,
            notes: $notes
        );
    }

    public function replenishWithinTransaction(
        int $variantId,
        int $storeId,
        int $quantity,
        ?int $customerId = null,
        ?int $userId = null,
        ?string $notes = null
    ): StockResult {
        if ($quantity <= 0) {
            throw new RuntimeException(
                'La cantidad a reponer debe ser mayor que cero.'
            );
        }

        return $this->changeStock(
            variantId: $variantId,
            storeId: $storeId,
            movementType: StockMovementType::REPLENISHMENT,
            quantityDelta: $quantity,
            reservedDelta: 0,
            referenceType: null,
            referenceId: null,
            customerId: $customerId,
            userId: $userId,
            notes: $notes,
            manageTransaction: false
        );
    }

    public function adjust(
        int $variantId,
        int $storeId,
        int $quantityDelta,
        ?int $customerId = null,
        ?int $userId = null,
        ?string $notes = null
    ): StockResult {
        if ($quantityDelta === 0) {
            throw new RuntimeException(
                'El ajuste de stock no puede ser cero.'
            );
        }

        return $this->changeStock(
            variantId: $variantId,
            storeId: $storeId,
            movementType: StockMovementType::ADJUSTMENT,
            quantityDelta: $quantityDelta,
            reservedDelta: 0,
            referenceType: null,
            referenceId: null,
            customerId: $customerId,
            userId: $userId,
            notes: $notes
        );
    }

    public function adjustWithinTransaction(
        int $variantId,
        int $storeId,
        int $quantityDelta,
        ?int $customerId = null,
        ?int $userId = null,
        ?string $notes = null
    ): StockResult {
        if ($quantityDelta === 0) {
            throw new RuntimeException(
                'El ajuste de stock no puede ser cero.'
            );
        }

        return $this->changeStock(
            variantId: $variantId,
            storeId: $storeId,
            movementType: StockMovementType::ADJUSTMENT,
            quantityDelta: $quantityDelta,
            reservedDelta: 0,
            referenceType: null,
            referenceId: null,
            customerId: $customerId,
            userId: $userId,
            notes: $notes,
            manageTransaction: false
        );
    }

    public function reserve(
        int $variantId,
        int $storeId,
        int $quantity,
        int $reservationId,
        ?int $customerId = null,
        ?int $userId = null,
        ?string $notes = null
    ): StockResult {
        if ($quantity <= 0) {
            throw new RuntimeException(
                'La cantidad a reservar debe ser mayor que cero.'
            );
        }

        if ($reservationId <= 0) {
            throw new RuntimeException(
                'El identificador de la reserva no es válido.'
            );
        }

        return $this->changeStock(
            variantId: $variantId,
            storeId: $storeId,
            movementType: StockMovementType::RESERVATION,
            quantityDelta: 0,
            reservedDelta: $quantity,
            referenceType: 'reservation',
            referenceId: $reservationId,
            customerId: $customerId,
            userId: $userId,
            notes: $notes
        );
    }

    public function reserveWithinTransaction(
        int $variantId,
        int $storeId,
        int $quantity,
        int $reservationId,
        ?int $customerId = null,
        ?int $userId = null,
        ?string $notes = null
    ): StockResult {
        if ($quantity <= 0) {
            throw new RuntimeException(
                'La cantidad a reservar debe ser mayor que cero.'
            );
        }

        if ($reservationId <= 0) {
            throw new RuntimeException(
                'El identificador de la reserva no es válido.'
            );
        }

        return $this->changeStock(
            variantId: $variantId,
            storeId: $storeId,
            movementType: StockMovementType::RESERVATION,
            quantityDelta: 0,
            reservedDelta: $quantity,
            referenceType: 'reservation',
            referenceId: $reservationId,
            customerId: $customerId,
            userId: $userId,
            notes: $notes,
            manageTransaction: false
        );
    }

    public function releaseReservation(
        int $variantId,
        int $storeId,
        int $quantity,
        int $reservationId,
        ?int $customerId = null,
        ?int $userId = null,
        ?string $notes = null,
        string $movementType =
            StockMovementType::RESERVATION_RELEASE
    ): StockResult {
        $this->validateReservationRelease(
            $quantity,
            $reservationId,
            $movementType
        );

        return $this->changeStock(
            variantId: $variantId,
            storeId: $storeId,
            movementType: $movementType,
            quantityDelta: 0,
            reservedDelta: -$quantity,
            referenceType: 'reservation',
            referenceId: $reservationId,
            customerId: $customerId,
            userId: $userId,
            notes: $notes
        );
    }

    public function releaseReservationWithinTransaction(
        int $variantId,
        int $storeId,
        int $quantity,
        int $reservationId,
        ?int $customerId = null,
        ?int $userId = null,
        ?string $notes = null,
        string $movementType =
            StockMovementType::RESERVATION_RELEASE
    ): StockResult {
        $this->validateReservationRelease(
            $quantity,
            $reservationId,
            $movementType
        );

        return $this->changeStock(
            variantId: $variantId,
            storeId: $storeId,
            movementType: $movementType,
            quantityDelta: 0,
            reservedDelta: -$quantity,
            referenceType: 'reservation',
            referenceId: $reservationId,
            customerId: $customerId,
            userId: $userId,
            notes: $notes,
            manageTransaction: false
        );
    }

    public function completeSale(
        int $variantId,
        int $storeId,
        int $quantity,
        int $reservationId,
        ?int $customerId = null,
        ?int $userId = null,
        ?string $notes = null
    ): StockResult {
        $this->validateSale(
            $quantity,
            $reservationId
        );

        return $this->changeStock(
            variantId: $variantId,
            storeId: $storeId,
            movementType: StockMovementType::SALE,
            quantityDelta: -$quantity,
            reservedDelta: -$quantity,
            referenceType: 'reservation',
            referenceId: $reservationId,
            customerId: $customerId,
            userId: $userId,
            notes: $notes
        );
    }

    public function completeSaleWithinTransaction(
        int $variantId,
        int $storeId,
        int $quantity,
        int $reservationId,
        ?int $customerId = null,
        ?int $userId = null,
        ?string $notes = null
    ): StockResult {
        $this->validateSale(
            $quantity,
            $reservationId
        );

        return $this->changeStock(
            variantId: $variantId,
            storeId: $storeId,
            movementType: StockMovementType::SALE,
            quantityDelta: -$quantity,
            reservedDelta: -$quantity,
            referenceType: 'reservation',
            referenceId: $reservationId,
            customerId: $customerId,
            userId: $userId,
            notes: $notes,
            manageTransaction: false
        );
    }

    public function registerReturn(
        int $variantId,
        int $storeId,
        int $quantity,
        ?string $referenceType = null,
        ?int $referenceId = null,
        ?int $customerId = null,
        ?int $userId = null,
        ?string $notes = null
    ): StockResult {
        if ($quantity <= 0) {
            throw new RuntimeException(
                'La cantidad devuelta debe ser mayor que cero.'
            );
        }

        return $this->changeStock(
            variantId: $variantId,
            storeId: $storeId,
            movementType: StockMovementType::RETURN,
            quantityDelta: $quantity,
            reservedDelta: 0,
            referenceType: $referenceType,
            referenceId: $referenceId,
            customerId: $customerId,
            userId: $userId,
            notes: $notes
        );
    }

    public function registerReturnWithinTransaction(
        int $variantId,
        int $storeId,
        int $quantity,
        ?string $referenceType = null,
        ?int $referenceId = null,
        ?int $customerId = null,
        ?int $userId = null,
        ?string $notes = null
    ): StockResult {
        if ($quantity <= 0) {
            throw new RuntimeException(
                'La cantidad devuelta debe ser mayor que cero.'
            );
        }

        return $this->changeStock(
            variantId: $variantId,
            storeId: $storeId,
            movementType: StockMovementType::RETURN,
            quantityDelta: $quantity,
            reservedDelta: 0,
            referenceType: $referenceType,
            referenceId: $referenceId,
            customerId: $customerId,
            userId: $userId,
            notes: $notes,
            manageTransaction: false
        );
    }

    private function validateReservationRelease(
        int $quantity,
        int $reservationId,
        string $movementType
    ): void {
        if ($quantity <= 0) {
            throw new RuntimeException(
                'La cantidad a liberar debe ser mayor que cero.'
            );
        }

        if ($reservationId <= 0) {
            throw new RuntimeException(
                'El identificador de la reserva no es válido.'
            );
        }

        if (
            !in_array(
                $movementType,
                [
                    StockMovementType::RESERVATION_RELEASE,
                    StockMovementType::CANCELLATION,
                    StockMovementType::EXPIRATION,
                ],
                true
            )
        ) {
            throw new RuntimeException(
                'El tipo de liberación de reserva no es válido.'
            );
        }
    }

    private function validateSale(
        int $quantity,
        int $reservationId
    ): void {
        if ($quantity <= 0) {
            throw new RuntimeException(
                'La cantidad vendida debe ser mayor que cero.'
            );
        }

        if ($reservationId <= 0) {
            throw new RuntimeException(
                'El identificador de la reserva no es válido.'
            );
        }
    }

    private function changeStock(
        int $variantId,
        int $storeId,
        string $movementType,
        int $quantityDelta,
        int $reservedDelta,
        ?string $referenceType,
        ?int $referenceId,
        ?int $customerId,
        ?int $userId,
        ?string $notes,
        bool $requireZeroPhysicalStock = false,
        bool $manageTransaction = true
    ): StockResult {
        global $wpdb;

        if ($variantId <= 0) {
            throw new RuntimeException(
                'El identificador de la variante no es válido.'
            );
        }

        if ($storeId <= 0) {
            throw new RuntimeException(
                'El identificador de la tienda no es válido.'
            );
        }

        if (
            !StockMovementType::isValid(
                $movementType
            )
        ) {
            throw new RuntimeException(
                'El tipo de movimiento de stock no es válido.'
            );
        }

        if (
            ($referenceType === null)
            !== ($referenceId === null)
        ) {
            throw new RuntimeException(
                'El tipo y el identificador de referencia deben informarse juntos.'
            );
        }

        if (
            $customerId !== null
            && $customerId <= 0
        ) {
            throw new RuntimeException(
                'El identificador del cliente no es válido.'
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

        if ($manageTransaction) {
            $this->beginTransaction();
        }

        try {
            $row = $wpdb->get_row(
                $wpdb->prepare(
                    "SELECT
                        id,
                        product_id,
                        stock_quantity,
                        stock_reserved,
                        track_stock,
                        is_active,
                        archived_at
                    FROM {$this->variantsTable}
                    WHERE id = %d
                    LIMIT 1
                    FOR UPDATE",
                    $variantId
                ),
                ARRAY_A
            );

            if (!is_array($row)) {
                throw new RuntimeException(
                    'No se encontró la variante.'
                );
            }

            if (!empty($row['archived_at'])) {
                throw new RuntimeException(
                    'No se puede modificar el stock de una variante archivada.'
                );
            }

            if ((int) $row['is_active'] !== 1) {
                throw new RuntimeException(
                    'No se puede modificar el stock de una variante inactiva.'
                );
            }

            if ((int) $row['track_stock'] !== 1) {
                throw new RuntimeException(
                    'La variante no utiliza control de stock.'
                );
            }

            $productId =
                (int) $row['product_id'];

            $stockQuantityBefore =
                (int) $row['stock_quantity'];

            $stockReservedBefore =
                (int) $row['stock_reserved'];

            if (
                $requireZeroPhysicalStock
                && (
                    $stockQuantityBefore !== 0
                    || $stockReservedBefore !== 0
                )
            ) {
                throw new RuntimeException(
                    'El stock inicial solo puede registrarse sobre una variante sin existencias.'
                );
            }

            $stockQuantityAfter =
                $stockQuantityBefore
                + $quantityDelta;

            $stockReservedAfter =
                $stockReservedBefore
                + $reservedDelta;

            if ($stockQuantityAfter < 0) {
                throw new RuntimeException(
                    'La operación dejaría el stock físico en negativo.'
                );
            }

            if ($stockReservedAfter < 0) {
                throw new RuntimeException(
                    'La operación liberaría más stock del que está reservado.'
                );
            }

            if (
                $stockReservedAfter
                > $stockQuantityAfter
            ) {
                throw new RuntimeException(
                    'No hay stock disponible suficiente para completar la operación.'
                );
            }

            $updated = $wpdb->update(
                $this->variantsTable,
                [
                    'stock_quantity' =>
                        $stockQuantityAfter,

                    'stock_reserved' =>
                        $stockReservedAfter,

                    'updated_at' =>
                        current_time(
                            'mysql',
                            true
                        ),
                ],
                [
                    'id' =>
                        $variantId,
                ],
                [
                    '%d',
                    '%d',
                    '%s',
                ],
                [
                    '%d',
                ]
            );

            if ($updated === false) {
                throw new RuntimeException(
                    sprintf(
                        'No se pudo actualizar el stock: %s',
                        $wpdb->last_error
                    )
                );
            }

            $movementId =
                $this->movementRepository->create(
                    [
                        'product_id' =>
                            $productId,

                        'variant_id' =>
                            $variantId,

                        'store_id' =>
                            $storeId,

                        'movement_type' =>
                            $movementType,

                        'quantity_delta' =>
                            $quantityDelta,

                        'reserved_delta' =>
                            $reservedDelta,

                        'stock_quantity_before' =>
                            $stockQuantityBefore,

                        'stock_quantity_after' =>
                            $stockQuantityAfter,

                        'stock_reserved_before' =>
                            $stockReservedBefore,

                        'stock_reserved_after' =>
                            $stockReservedAfter,

                        'reference_type' =>
                            $referenceType,

                        'reference_id' =>
                            $referenceId,

                        'customer_id' =>
                            $customerId,

                        'user_id' =>
                            $userId,

                        'notes' =>
                            $notes,
                    ]
                );

            $result = new StockResult(
                productId: $productId,
                variantId: $variantId,
                movementId: $movementId,
                movementType: $movementType,
                quantityDelta: $quantityDelta,
                reservedDelta: $reservedDelta,
                stockQuantityBefore: $stockQuantityBefore,
                stockQuantityAfter: $stockQuantityAfter,
                stockReservedBefore: $stockReservedBefore,
                stockReservedAfter: $stockReservedAfter
            );

            if ($manageTransaction) {
                $this->commitTransaction();
            }

            return $result;
        } catch (Throwable $exception) {
            if ($manageTransaction) {
                $this->rollbackTransaction();
            }

            throw $exception;
        }
    }

    private function beginTransaction(): void
    {
        global $wpdb;

        $result = $wpdb->query(
            'START TRANSACTION'
        );

        if ($result === false) {
            throw new RuntimeException(
                sprintf(
                    'No se pudo iniciar la transacción de stock: %s',
                    $wpdb->last_error
                )
            );
        }
    }

    private function commitTransaction(): void
    {
        global $wpdb;

        $result = $wpdb->query(
            'COMMIT'
        );

        if ($result === false) {
            throw new RuntimeException(
                sprintf(
                    'No se pudo confirmar la transacción de stock: %s',
                    $wpdb->last_error
                )
            );
        }
    }

    private function rollbackTransaction(): void
    {
        global $wpdb;

        $wpdb->query(
            'ROLLBACK'
        );
    }
}