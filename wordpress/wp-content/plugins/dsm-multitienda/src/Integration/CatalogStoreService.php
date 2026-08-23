<?php

declare(strict_types=1);

namespace DSM\Multitienda\Integration;

use DSM\Catalogo\Product\Product;
use DSM\Catalogo\Product\ProductRepository;
use DSM\Multitienda\Application\MultistoreAccessService;
use DSM\Multitienda\Store\Store;
use DSM\Multitienda\Store\StoreRepository;
use RuntimeException;

if (!defined('ABSPATH')) {
    exit;
}

final class CatalogStoreService
{
    public function __construct(
        private readonly MultistoreAccessService $accessService =
            new MultistoreAccessService(),

        private readonly StoreRepository $storeRepository =
            new StoreRepository(),

        private readonly ProductRepository $productRepository =
            new ProductRepository()
    ) {
    }

    public function requireStoreForCustomer(
        int $customerId
    ): Store {
        if ($customerId <= 0) {
            throw new RuntimeException(
                'El identificador del cliente no es válido.'
            );
        }

        $this->accessService
            ->assertHasAccess(
                $customerId
            );

        $store =
            $this->storeRepository
                ->findByCustomerId(
                    $customerId
                );

        if ($store === null) {
            throw new RuntimeException(
                'El cliente todavía no tiene una tienda creada.'
            );
        }

        return $store;
    }

    /**
     * @return array<int, Product>
     */
    public function getProductsForCustomer(
        int $customerId,
        int $limit = 250,
        int $offset = 0,
        ?string $status = null
    ): array {
        $store =
            $this->requireStoreForCustomer(
                $customerId
            );

        return $this->getProductsForStore(
            storeId:
                $store->getId(),

            limit:
                $limit,

            offset:
                $offset,

            status:
                $status
        );
    }

    /**
     * @return array<int, Product>
     */
    public function getProductsForStore(
        int $storeId,
        int $limit = 250,
        int $offset = 0,
        ?string $status = null
    ): array {
        if ($storeId <= 0) {
            throw new RuntimeException(
                'El identificador de la tienda no es válido.'
            );
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

        return $this->productRepository
            ->findByStore(
                storeId:
                    $storeId,

                limit:
                    $limit,

                offset:
                    $offset,

                status:
                    $status
            );
    }

    public function countProductsForCustomer(
        int $customerId,
        ?string $status = null
    ): int {
        $store =
            $this->requireStoreForCustomer(
                $customerId
            );

        return $this->countProductsForStore(
            storeId:
                $store->getId(),

            status:
                $status
        );
    }

    public function countProductsForStore(
        int $storeId,
        ?string $status = null
    ): int {
        if ($storeId <= 0) {
            throw new RuntimeException(
                'El identificador de la tienda no es válido.'
            );
        }

        return $this->productRepository
            ->countByStore(
                storeId:
                    $storeId,

                status:
                    $status
            );
    }

    public function getProductForCustomer(
        int $customerId,
        int $productId
    ): Product {
        if ($productId <= 0) {
            throw new RuntimeException(
                'El identificador del producto no es válido.'
            );
        }

        $store =
            $this->requireStoreForCustomer(
                $customerId
            );

        if (
            !$this->productRepository
                ->belongsToStore(
                    $productId,
                    $store->getId()
                )
        ) {
            throw new RuntimeException(
                'El producto no pertenece a la tienda del cliente.'
            );
        }

        $product =
            $this->productRepository
                ->findById(
                    $productId
                );

        if ($product === null) {
            throw new RuntimeException(
                'No se encontró el producto.'
            );
        }

        return $product;
    }
}