<?php

declare(strict_types=1);

namespace DSM\Directos\Application;

use DSM\Anuncios\Advertisement\AdvertisementRepository;
use DSM\Catalogo\Product\ProductRepository;
use DSM\Catalogo\Variant\ProductVariantRepository;
use DSM\Directos\Direct\DirectItemRepository;
use DSM\Directos\Direct\DirectRepository;
use DSM\Multitienda\Store\StoreRepository;
use RuntimeException;

if (!defined('ABSPATH')) {
    exit;
}

final class DirectSelectionService
{
    public function __construct(
        private readonly DirectAccessService $accessService =
            new DirectAccessService(),

        private readonly DirectRepository $directRepository =
            new DirectRepository(),

        private readonly DirectItemRepository $itemRepository =
            new DirectItemRepository()
    ) {
    }

    public function isAdvertisementSelected(
        int $customerId,
        int $advertisementId
    ): bool {
        $direct =
            $this->directRepository
                ->findByCustomerId(
                    $customerId
                );

        if ($direct === null) {
            return false;
        }

        return $this->itemRepository
            ->hasAdvertisement(
                (int) $direct['id'],
                $advertisementId
            );
    }

    public function toggleAdvertisement(
        int $customerId,
        int $advertisementId
    ): bool {
        $this->accessService
            ->assertHasAccess(
                $customerId
            );

        $direct =
            $this->requireDirect(
                $customerId
            );

        $repository =
            new AdvertisementRepository();

        $advertisement =
            $repository->findById(
                $advertisementId
            );

        if ($advertisement === null) {
            throw new RuntimeException(
                'No se encontró el anuncio.'
            );
        }

        if (
            !$advertisement
                ->belongsToCustomer(
                    $customerId
                )
        ) {
            throw new RuntimeException(
                'El anuncio no pertenece al cliente.'
            );
        }

        if (
            $advertisement->getStatus()
            !== 'active'
        ) {
            throw new RuntimeException(
                'Solo los anuncios activos pueden añadirse al directo.'
            );
        }

        $liveId =
            (int) $direct['id'];

        if (
            $this->itemRepository
                ->hasAdvertisement(
                    $liveId,
                    $advertisementId
                )
        ) {
            $this->itemRepository
                ->removeAdvertisement(
                    $liveId,
                    $advertisementId
                );

            return false;
        }

        $this->itemRepository
            ->addAdvertisement(
                $liveId,
                $advertisementId
            );

        return true;
    }

    public function isVariantSelected(
        int $customerId,
        int $variantId
    ): bool {
        $direct =
            $this->directRepository
                ->findByCustomerId(
                    $customerId
                );

        if ($direct === null) {
            return false;
        }

        return $this->itemRepository
            ->hasVariant(
                (int) $direct['id'],
                $variantId
            );
    }

    public function toggleVariant(
        int $customerId,
        int $variantId
    ): bool {
        $this->accessService
            ->assertHasAccess(
                $customerId
            );

        if (
            !$this->accessService
                ->canUseInventory(
                    $customerId
                )
        ) {
            throw new RuntimeException(
                'Tu suscripción no permite usar inventario en Directos.'
            );
        }

        $direct =
            $this->requireDirect(
                $customerId
            );

        $variantRepository =
            new ProductVariantRepository();

        $variant =
            $variantRepository->findById(
                $variantId
            );

        if ($variant === null) {
            throw new RuntimeException(
                'No se encontró la variante.'
            );
        }

        $productRepository =
            new ProductRepository();

        $product =
            $productRepository->findById(
                $variant->getProductId()
            );

        if ($product === null) {
            throw new RuntimeException(
                'No se encontró el producto.'
            );
        }

        $store =
            (
                new StoreRepository()
            )->findByCustomerId(
                $customerId
            );

        if ($store === null) {
            throw new RuntimeException(
                'No se encontró la tienda.'
            );
        }

        if (
            $product->getStoreId()
            !== $store->getId()
        ) {
            throw new RuntimeException(
                'El producto no pertenece a tu tienda.'
            );
        }

        if (
            !$variant->isActive()
            || $variant->isArchived()
        ) {
            throw new RuntimeException(
                'La variante no está activa.'
            );
        }

        if (!$variant->tracksStock()) {
            throw new RuntimeException(
                'La variante no utiliza control de stock.'
            );
        }

        $liveId =
            (int) $direct['id'];

        if (
            $this->itemRepository
                ->hasVariant(
                    $liveId,
                    $variantId
                )
        ) {
            $this->itemRepository
                ->removeVariant(
                    $liveId,
                    $variantId
                );

            return false;
        }

        if (
            $variant->getAvailableStock()
            <= 0
        ) {
            throw new RuntimeException(
                'La variante no tiene stock disponible.'
            );
        }

        $this->itemRepository
            ->addVariant(
                $liveId,
                $product->getId(),
                $variantId
            );

        return true;
    }

    /**
     * @return array<string, mixed>
     */
    private function requireDirect(
        int $customerId
    ): array {
        $direct =
            $this->directRepository
                ->findByCustomerId(
                    $customerId
                );

        if ($direct === null) {
            throw new RuntimeException(
                'Primero debes configurar Mi directo.'
            );
        }

        return $direct;
    }
}
