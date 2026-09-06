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

final class SaveDirectItems
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

    /**
     * @param array<int, int> $advertisementIds
     * @param array<int|string, mixed> $inventoryQuantities
     */
    public function execute(
        int $customerId,
        array $advertisementIds,
        array $inventoryQuantities
    ): void {
        $this->accessService
            ->assertHasAccess(
                $customerId
            );

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

        $liveId =
            (int) (
                $direct['id']
                ?? 0
            );

        if ($liveId <= 0) {
            throw new RuntimeException(
                'No se pudo identificar Mi directo.'
            );
        }

        $items = [];

        /*
         * =====================================================
         * ANUNCIOS
         * =====================================================
         */

        $advertisementIds =
            array_values(
                array_unique(
                    array_filter(
                        array_map(
                            'absint',
                            $advertisementIds
                        )
                    )
                )
            );

        $advertisementRepository =
            new AdvertisementRepository();

        foreach ($advertisementIds as $advertisementId) {
            $advertisement =
                $advertisementRepository
                    ->findById(
                        $advertisementId
                    );

            if ($advertisement === null) {
                throw new RuntimeException(
                    'Uno de los anuncios seleccionados no existe.'
                );
            }

            if (
                !$advertisement
                    ->belongsToCustomer(
                        $customerId
                    )
            ) {
                throw new RuntimeException(
                    'Uno de los anuncios seleccionados no te pertenece.'
                );
            }

            /*
             * Para preparar un directo solo permitimos
             * anuncios disponibles actualmente.
             */
            if (
                $advertisement->getStatus()
                !== 'active'
            ) {
                throw new RuntimeException(
                    sprintf(
                        'El anuncio "%s" no está activo.',
                        $advertisement->getTitle()
                    )
                );
            }

            $items[] = [
                'source_type' =>
                    'advertisement',

                'advertisement_id' =>
                    $advertisement->getId(),

                'product_id' =>
                    null,

                'variant_id' =>
                    null,

                'allocated_quantity' =>
                    1,
            ];
        }

        /*
         * =====================================================
         * INVENTARIO
         * =====================================================
         */

        $inventoryQuantities =
            is_array($inventoryQuantities)
                ? $inventoryQuantities
                : [];

        $hasInventorySelection = false;

        foreach (
            $inventoryQuantities
            as $rawQuantity
        ) {
            if ((int) $rawQuantity > 0) {
                $hasInventorySelection = true;
                break;
            }
        }

        if ($hasInventorySelection) {
            if (
                !$this->accessService
                    ->canUseInventory(
                        $customerId
                    )
            ) {
                throw new RuntimeException(
                    'Tu suscripción no permite utilizar inventario en Directos.'
                );
            }

            if (
                !class_exists(
                    StoreRepository::class
                )
                || !class_exists(
                    ProductVariantRepository::class
                )
                || !class_exists(
                    ProductRepository::class
                )
            ) {
                throw new RuntimeException(
                    'La integración con inventario no está disponible.'
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
                    'No se encontró tu tienda.'
                );
            }

            $storeId =
                $store->getId();

            $variantRepository =
                new ProductVariantRepository();

            $productRepository =
                new ProductRepository();

            foreach (
                $inventoryQuantities
                as $variantIdRaw => $quantityRaw
            ) {
                $variantId =
                    absint(
                        (string) $variantIdRaw
                    );

                $quantity =
                    max(
                        0,
                        (int) $quantityRaw
                    );

                if (
                    $variantId <= 0
                    || $quantity <= 0
                ) {
                    continue;
                }

                $variant =
                    $variantRepository
                        ->findById(
                            $variantId
                        );

                if ($variant === null) {
                    throw new RuntimeException(
                        'Una de las variantes seleccionadas no existe.'
                    );
                }

                if (
                    !$variant->isActive()
                    || $variant->isArchived()
                ) {
                    throw new RuntimeException(
                        'Una de las variantes seleccionadas no está disponible.'
                    );
                }

                /*
                 * El sistema actual de reservas de Multitienda
                 * trabaja con variantes que controlan stock.
                 */
                if (!$variant->tracksStock()) {
                    throw new RuntimeException(
                        'Una de las variantes seleccionadas no utiliza control de stock.'
                    );
                }

                $product =
                    $productRepository
                        ->findById(
                            $variant->getProductId()
                        );

                if ($product === null) {
                    throw new RuntimeException(
                        'No se encontró el producto de una variante seleccionada.'
                    );
                }

                if (
                    $product->getStoreId()
                    !== $storeId
                ) {
                    throw new RuntimeException(
                        'Uno de los productos seleccionados no pertenece a tu tienda.'
                    );
                }

                $availableStock =
                    $variant->getAvailableStock();

                if (
                    $availableStock <= 0
                    || $quantity > $availableStock
                ) {
                    throw new RuntimeException(
                        sprintf(
                            'No hay stock suficiente de "%s". Disponible: %d.',
                            $product->getName(),
                            $availableStock
                        )
                    );
                }

                $items[] = [
                    'source_type' =>
                        'inventory',

                    'advertisement_id' =>
                        null,

                    'product_id' =>
                        $product->getId(),

                    'variant_id' =>
                        $variant->getId(),

                    'allocated_quantity' =>
                        $quantity,
                ];
            }
        }

        $this->itemRepository
            ->replaceSelection(
                $liveId,
                $items
            );
    }
}
