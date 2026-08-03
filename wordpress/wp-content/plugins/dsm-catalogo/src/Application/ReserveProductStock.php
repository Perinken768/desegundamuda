<?php

declare(strict_types=1);

namespace DSM\Catalogo\Application;

use DSM\Catalogo\Product\ProductRepository;
use DSM\Catalogo\Reservation\ProductReservation;
use DSM\Catalogo\Reservation\ProductReservationRepository;
use DSM\Catalogo\Reservation\ProductReservationStatus;
use DSM\Catalogo\Stock\StockResult;
use DSM\Catalogo\Stock\StockService;
use DSM\Catalogo\Variant\ProductVariantRepository;
use RuntimeException;
use Throwable;

if (!defined('ABSPATH')) {
    exit;
}

final class ReserveProductStock
{
    public function __construct(
        private readonly ProductRepository $productRepository,
        private readonly ProductVariantRepository $variantRepository,
        private readonly ProductReservationRepository $reservationRepository,
        private readonly StockService $stockService
    ) {
    }

    /**
     * @param array<string, mixed> $context
     *
     * @return array{
     *     reservation: ProductReservation,
     *     stock: StockResult
     * }
     */
    public function execute(
        int $storeId,
        int $sellerCustomerId,
        int $variantId,
        int $quantity,
        array $context = []
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

        if ($variantId <= 0) {
            throw new RuntimeException(
                'El identificador de la variante no es válido.'
            );
        }

        if ($quantity <= 0) {
            throw new RuntimeException(
                'La cantidad a reservar debe ser mayor que cero.'
            );
        }

        $variant = $this->variantRepository->findById(
            $variantId
        );

        if ($variant === null) {
            throw new RuntimeException(
                'No se encontró la variante.'
            );
        }

        if (!$variant->isActive()) {
            throw new RuntimeException(
                'La variante no está activa.'
            );
        }

        if ($variant->isArchived()) {
            throw new RuntimeException(
                'La variante está archivada.'
            );
        }

        if (!$variant->tracksStock()) {
            throw new RuntimeException(
                'La variante no utiliza control de stock.'
            );
        }

        $product = $this->productRepository->findById(
            $variant->getProductId()
        );

        if ($product === null) {
            throw new RuntimeException(
                'No se encontró el producto.'
            );
        }

        if (!$product->belongsToStore($storeId)) {
            throw new RuntimeException(
                'El producto no pertenece a la tienda indicada.'
            );
        }

        if ($product->isArchived()) {
            throw new RuntimeException(
                'No se puede reservar un producto archivado.'
            );
        }

        $buyerCustomerId = self::nullablePositiveInt(
            $context['buyer_customer_id']
            ?? null
        );

        $conversationId = self::nullablePositiveInt(
            $context['conversation_id']
            ?? null
        );

        $externalContact = self::nullableString(
            $context['external_contact']
            ?? null
        );

        if (
            $buyerCustomerId === null
            && $conversationId === null
            && $externalContact === null
        ) {
            throw new RuntimeException(
                'La reserva debe estar asociada a un comprador, una conversación o un contacto externo.'
            );
        }

        $expiresAt = self::nullableString(
            $context['expires_at']
            ?? null
        );

        $notes = self::nullableString(
            $context['notes']
            ?? null
        );

        $userId = self::nullablePositiveInt(
            $context['user_id']
            ?? null
        );

        $wpdb->query('START TRANSACTION');

        try {
            $reservationId =
                $this->reservationRepository->create(
                    [
                        'product_id' =>
                            $product->getId(),

                        'variant_id' =>
                            $variantId,

                        'store_id' =>
                            $storeId,

                        'seller_customer_id' =>
                            $sellerCustomerId,

                        'buyer_customer_id' =>
                            $buyerCustomerId,

                        'conversation_id' =>
                            $conversationId,

                        'external_contact' =>
                            $externalContact,

                        'quantity' =>
                            $quantity,

                        'status' =>
                            ProductReservationStatus::ACTIVE,

                        'expires_at' =>
                            $expiresAt,
                    ]
                );

            $stockResult =
                $this->stockService
                    ->reserveWithinTransaction(
                        variantId: $variantId,
                        storeId: $storeId,
                        quantity: $quantity,
                        reservationId: $reservationId,
                        customerId: $sellerCustomerId,
                        userId: $userId,
                        notes: $notes
                    );

            $reservation =
                $this->reservationRepository->findById(
                    $reservationId
                );

            if ($reservation === null) {
                throw new RuntimeException(
                    'La reserva se creó, pero no pudo recuperarse.'
                );
            }

            $wpdb->query('COMMIT');

            return [
                'reservation' =>
                    $reservation,

                'stock' =>
                    $stockResult,
            ];
        } catch (Throwable $exception) {
            $wpdb->query('ROLLBACK');

            throw $exception;
        }
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

        $integer = (int) $value;

        return $integer > 0
            ? $integer
            : null;
    }

    private static function nullableString(
        mixed $value
    ): ?string {
        if (
            $value === null
            || trim((string) $value) === ''
        ) {
            return null;
        }

        return trim(
            (string) $value
        );
    }
}