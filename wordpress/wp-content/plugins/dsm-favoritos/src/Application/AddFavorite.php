<?php

declare(strict_types=1);

namespace DSM\Favoritos\Application;

use DSM\Anuncios\Advertisement\AdvertisementRepository;
use DSM\Favoritos\Favorite\Favorite;
use DSM\Favoritos\Favorite\FavoriteRepository;
use RuntimeException;

if (!defined('ABSPATH')) {
    exit;
}

final class AddFavorite
{
    private FavoriteRepository $favoriteRepository;

    private AdvertisementRepository $advertisementRepository;

    public function __construct(
        ?FavoriteRepository $favoriteRepository = null,
        ?AdvertisementRepository $advertisementRepository = null
    ) {
        $this->favoriteRepository =
            $favoriteRepository
            ?? new FavoriteRepository();

        $this->advertisementRepository =
            $advertisementRepository
            ?? new AdvertisementRepository();
    }

    public function execute(
        int $customerId,
        string $itemType,
        int $itemId
    ): Favorite {
        if ($customerId <= 0) {
            throw new RuntimeException(
                'No se pudo identificar al cliente.'
            );
        }

        $itemType =
            sanitize_key(
                $itemType
            );

        if (
            !Favorite::isValidType(
                $itemType
            )
        ) {
            throw new RuntimeException(
                'El tipo de favorito indicado no es válido.'
            );
        }

        if ($itemId <= 0) {
            throw new RuntimeException(
                'El elemento indicado no es válido.'
            );
        }

        $this->validateCustomer(
            $customerId
        );

        if (
            $itemType
            === Favorite::TYPE_ADVERTISEMENT
        ) {
            $this->validateAdvertisement(
                $customerId,
                $itemId
            );
        } elseif (
            $itemType
            === Favorite::TYPE_STORE_PRODUCT
        ) {
            $this->validateStoreProduct(
                $customerId,
                $itemId
            );
        } elseif (
            $itemType
            === Favorite::TYPE_SELLER
        ) {
            $this->validateSeller(
                $customerId,
                $itemId
            );
        }

        return $this->favoriteRepository
            ->createItem(
                $customerId,
                $itemType,
                $itemId
            );
    }

    /*
     * Compatibilidad temporal con llamadas antiguas.
     */
    public function executeAdvertisement(
        int $customerId,
        int $advertisementId
    ): Favorite {
        return $this->execute(
            $customerId,
            Favorite::TYPE_ADVERTISEMENT,
            $advertisementId
        );
    }

    private function validateCustomer(
        int $customerId
    ): void {
        $customerContext =
            apply_filters(
                'dsm_customer_context_by_id',
                null,
                $customerId
            );

        if (!is_array($customerContext)) {
            throw new RuntimeException(
                'No se encontró el cliente indicado.'
            );
        }

        $resolvedCustomerId =
            max(
                0,
                (int) (
                    $customerContext['id']
                    ?? 0
                )
            );

        if (
            $resolvedCustomerId <= 0
            || $resolvedCustomerId !== $customerId
        ) {
            throw new RuntimeException(
                'El cliente indicado no es válido.'
            );
        }

        $customerStatus =
            sanitize_key(
                (string) (
                    $customerContext['status']
                    ?? ''
                )
            );

        if ($customerStatus !== 'active') {
            throw new RuntimeException(
                'La cuenta del cliente no está activa.'
            );
        }
    }

    private function validateAdvertisement(
        int $customerId,
        int $advertisementId
    ): void {
        $advertisement =
            $this->advertisementRepository
                ->findById(
                    $advertisementId
                );

        if ($advertisement === null) {
            throw new RuntimeException(
                'El anuncio indicado no existe.'
            );
        }

        if (!$advertisement->isPublic()) {
            throw new RuntimeException(
                'El anuncio no está disponible públicamente.'
            );
        }

        if (
            $advertisement->getCustomerId()
            === $customerId
        ) {
            throw new RuntimeException(
                'No puedes añadir tu propio anuncio a favoritos.'
            );
        }
    }

    private function validateSeller(
        int $customerId,
        int $sellerCustomerId
    ): void {
        if ($sellerCustomerId <= 0) {
            throw new RuntimeException(
                'El vendedor indicado no es válido.'
            );
        }

        if (
            $sellerCustomerId
            === $customerId
        ) {
            throw new RuntimeException(
                'No puedes añadirte a ti mismo como vendedor favorito.'
            );
        }

        $sellerContext =
            apply_filters(
                'dsm_customer_context_by_id',
                null,
                $sellerCustomerId
            );

        if (!is_array($sellerContext)) {
            throw new RuntimeException(
                'No se encontró el vendedor indicado.'
            );
        }

        $resolvedSellerId =
            max(
                0,
                (int) (
                    $sellerContext['id']
                    ?? 0
                )
            );

        if (
            $resolvedSellerId <= 0
            || $resolvedSellerId
                !== $sellerCustomerId
        ) {
            throw new RuntimeException(
                'El vendedor indicado no es válido.'
            );
        }

        $status =
            sanitize_key(
                (string) (
                    $sellerContext['status']
                    ?? ''
                )
            );

        if ($status !== 'active') {
            throw new RuntimeException(
                'El vendedor no tiene una cuenta activa.'
            );
        }
    }

    private function validateStoreProduct(
        int $customerId,
        int $productId
    ): void {
        /*
         * DSM Favoritos no debe conocer las tablas internas
         * de Multitienda/Catálogo.
         *
         * El producto se valida mediante contrato público.
         */
        $productContext =
            apply_filters(
                'dsm_store_product_favorite_context',
                null,
                $productId
            );

        if (!is_array($productContext)) {
            throw new RuntimeException(
                'El producto indicado no existe o no está disponible.'
            );
        }

        $resolvedProductId =
            max(
                0,
                (int) (
                    $productContext['id']
                    ?? 0
                )
            );

        if (
            $resolvedProductId <= 0
            || $resolvedProductId !== $productId
        ) {
            throw new RuntimeException(
                'El producto indicado no es válido.'
            );
        }

        $isPublic =
            !empty(
                $productContext['is_public']
            );

        if (!$isPublic) {
            throw new RuntimeException(
                'El producto no está disponible públicamente.'
            );
        }

        $ownerCustomerId =
            max(
                0,
                (int) (
                    $productContext['owner_customer_id']
                    ?? 0
                )
            );

        if (
            $ownerCustomerId > 0
            && $ownerCustomerId === $customerId
        ) {
            throw new RuntimeException(
                'No puedes añadir un producto de tu propia tienda a favoritos.'
            );
        }
    }
}
