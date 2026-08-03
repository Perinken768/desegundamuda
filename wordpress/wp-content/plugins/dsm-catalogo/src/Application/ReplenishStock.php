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

final class ReplenishStock
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
        int $quantity,
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

        if ($quantity <= 0) {
            throw new RuntimeException(
                'La cantidad a reponer debe ser mayor que cero.'
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
                'No se puede reponer una variante archivada.'
            );
        }

        if (!$variant->isActive()) {
            throw new RuntimeException(
                'No se puede reponer una variante inactiva.'
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
                'El producto no puede recibir stock en su estado actual.'
            );
        }

        return $this->stockService->replenish(
            variantId: $variantId,
            storeId: $storeId,
            quantity: $quantity,
            customerId: $customerId,
            userId: $userId,
            notes: $notes
        );
    }
}