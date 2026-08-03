<?php

declare(strict_types=1);

namespace DSM\Catalogo\Application;

use DSM\Catalogo\Product\ProductRepository;
use DSM\Catalogo\Stock\StockResult;
use DSM\Catalogo\Stock\StockService;
use DSM\Catalogo\Variant\ProductVariantRepository;
use RuntimeException;

if (!defined('ABSPATH')) {
    exit;
}

final class AdjustStock
{
    public function __construct(
        private readonly ProductRepository $productRepository,
        private readonly ProductVariantRepository $variantRepository,
        private readonly StockService $stockService
    ) {
    }

    public function execute(
        int $storeId,
        int $customerId,
        int $variantId,
        int $quantityDelta,
        ?int $userId = null,
        ?string $notes = null
    ): StockResult {
        if ($storeId <= 0) {
            throw new RuntimeException(
                'El identificador de la tienda no es válido.'
            );
        }

        if ($customerId <= 0) {
            throw new RuntimeException(
                'El identificador del cliente no es válido.'
            );
        }

        if ($variantId <= 0) {
            throw new RuntimeException(
                'El identificador de la variante no es válido.'
            );
        }

        if ($quantityDelta === 0) {
            throw new RuntimeException(
                'El ajuste de stock no puede ser cero.'
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

        $variant =
            $this->variantRepository->findById(
                $variantId
            );

        if ($variant === null) {
            throw new RuntimeException(
                'No se encontró la variante.'
            );
        }

        if ($variant->isArchived()) {
            throw new RuntimeException(
                'No se puede ajustar una variante archivada.'
            );
        }

        if (!$variant->isActive()) {
            throw new RuntimeException(
                'No se puede ajustar una variante inactiva.'
            );
        }

        if (!$variant->tracksStock()) {
            throw new RuntimeException(
                'La variante no utiliza control de stock.'
            );
        }

        $product =
            $this->productRepository->findById(
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

        if (!$product->canReceiveStock()) {
            throw new RuntimeException(
                'El producto no admite movimientos de stock en su estado actual.'
            );
        }

        $newPhysicalStock =
            $variant->getStockQuantity()
            + $quantityDelta;

        if ($newPhysicalStock < 0) {
            throw new RuntimeException(
                'El ajuste dejaría el stock físico en negativo.'
            );
        }

        if (
            $newPhysicalStock
            < $variant->getStockReserved()
        ) {
            throw new RuntimeException(
                'El ajuste dejaría menos stock físico que unidades reservadas.'
            );
        }

        if (
            $notes === null
            || trim($notes) === ''
        ) {
            throw new RuntimeException(
                'Debes indicar el motivo del ajuste de stock.'
            );
        }

        return $this->stockService->adjust(
            variantId: $variantId,
            storeId: $storeId,
            quantityDelta: $quantityDelta,
            customerId: $customerId,
            userId: $userId,
            notes: trim($notes)
        );
    }
}