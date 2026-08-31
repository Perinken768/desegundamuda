<?php

declare(strict_types=1);

namespace DSM\Multitienda\Frontend;

use DSM\Catalogo\Brand\BrandRepository;
use DSM\Catalogo\Image\ProductImageRepository;
use DSM\Catalogo\Inventory\StockMovementRepository;
use DSM\Catalogo\Inventory\StockMovementType;
use DSM\Catalogo\Product\ProductRepository;
use DSM\Catalogo\Reservation\ProductReservationRepository;
use DSM\Catalogo\Reservation\ProductReservationStatus;
use DSM\Catalogo\Support\CategoryContext;
use DSM\Catalogo\Variant\ProductVariantRepository;
use DSM\Clientes\Customer\CustomerRepository;
use DSM\Clientes\Profile\CustomerProfileRepository;
use DSM\Multitienda\Application\MultistoreAccessService;
use DSM\Multitienda\Integration\CatalogStoreService;
use DSM\Multitienda\Store\StoreRepository;
use DSM\Multitienda\Support\CustomerContext;
use RuntimeException;
use Throwable;

if (!defined('ABSPATH')) {
    exit;
}

final class MyStoreShortcode
{
    public const SHORTCODE =
        'dsm_my_store';

    public static function register(): void
    {
        add_shortcode(
            self::SHORTCODE,
            [
                self::class,
                'render',
            ]
        );
    }

    public static function render(): string
    {
        try {
            /*
             * =================================================
             * CLIENTE DSM
             * =================================================
             */

            $customerContext =
                CustomerContext::current();

            $customerId =
                is_array(
                    $customerContext
                )
                    ? max(
                        0,
                        (int) (
                            $customerContext['id']
                            ?? 0
                        )
                    )
                    : 0;

            /*
             * =================================================
             * ACCESO MULTITIENDA + TIENDA
             * =================================================
             */

            $hasAccess =
                false;

            $store =
                null;

            if ($customerId > 0) {
                $accessService =
                    new MultistoreAccessService();

                $hasAccess =
                    $accessService->hasAccess(
                        $customerId
                    );

                if ($hasAccess) {
                    $storeRepository =
                        new StoreRepository();

                    $store =
                        $storeRepository
                            ->findByCustomerId(
                                $customerId
                            );
                }
            }

            /*
             * =================================================
             * SECCIÓN ERP
             * =================================================
             */

            $storeSection =
                isset($_GET['store_section'])
                    ? sanitize_key(
                        wp_unslash(
                            (string) $_GET[
                                'store_section'
                            ]
                        )
                    )
                    : 'dashboard';

            $allowedSections = [
                'dashboard',
                'products',
                'new-product',
                'edit-product',
                'inventory',
                'reservations',
                'movements',
            ];

            if (
                !in_array(
                    $storeSection,
                    $allowedSections,
                    true
                )
            ) {
                $storeSection =
                    'dashboard';
            }

            /*
             * =================================================
             * AVISOS DE TIENDA
             * =================================================
             */

            $status =
                isset($_GET['store_status'])
                    ? sanitize_key(
                        wp_unslash(
                            (string) $_GET[
                                'store_status'
                            ]
                        )
                    )
                    : '';

            $error =
                isset($_GET['store_error'])
                    ? sanitize_text_field(
                        wp_unslash(
                            (string) $_GET[
                                'store_error'
                            ]
                        )
                    )
                    : '';

            /*
             * =================================================
             * LISTADO DE PRODUCTOS
             * =================================================
             */

            $products = [];

            $productCount = 0;

            /*
             * =================================================
             * RESUMEN ERP
             * =================================================
             */

            $dashboardProductCount = 0;
            $dashboardVariantCount = 0;

            $dashboardPhysicalStock = 0;
            $dashboardReservedStock = 0;
            $dashboardAvailableStock = 0;

            $dashboardActiveReservations = 0;
            $dashboardCompletedReservations = 0;

            $productStatusNotice =
                isset(
                    $_GET[
                        'product_status_notice'
                    ]
                )
                    ? sanitize_key(
                        wp_unslash(
                            (string) $_GET[
                                'product_status_notice'
                            ]
                        )
                    )
                    : '';

            $productStatusError =
                isset(
                    $_GET[
                        'product_status_error'
                    ]
                )
                    ? sanitize_text_field(
                        wp_unslash(
                            (string) $_GET[
                                'product_status_error'
                            ]
                        )
                    )
                    : '';

            $productNotice =
                isset($_GET['product_notice'])
                    ? sanitize_key(
                        wp_unslash(
                            (string) $_GET[
                                'product_notice'
                            ]
                        )
                    )
                    : '';

            /*
             * =================================================
             * NUEVO PRODUCTO
             * =================================================
             */

            $productCategories = [];

            $productBrands = [];

            $productFormNotice =
                $productNotice;

            $productFormError =
                isset($_GET['product_error'])
                    ? sanitize_text_field(
                        wp_unslash(
                            (string) $_GET[
                                'product_error'
                            ]
                        )
                    )
                    : '';

            /*
             * =================================================
             * EDICIÓN / VARIANTES
             * =================================================
             */

            $editProduct =
                null;

            $productVariants = [];

            $productImages = [];

            $imageNotice =
                isset($_GET['image_notice'])
                    ? sanitize_key(
                        wp_unslash(
                            (string) $_GET[
                                'image_notice'
                            ]
                        )
                    )
                    : '';

            $imageError =
                isset($_GET['image_error'])
                    ? sanitize_text_field(
                        wp_unslash(
                            (string) $_GET[
                                'image_error'
                            ]
                        )
                    )
                    : '';

            $stockNotice =
                isset($_GET['stock_notice'])
                    ? sanitize_key(
                        wp_unslash(
                            (string) $_GET[
                                'stock_notice'
                            ]
                        )
                    )
                    : '';

            $stockError =
                isset($_GET['stock_error'])
                    ? sanitize_text_field(
                        wp_unslash(
                            (string) $_GET[
                                'stock_error'
                            ]
                        )
                    )
                    : '';

            $variantNotice =
                isset($_GET['variant_notice'])
                    ? sanitize_key(
                        wp_unslash(
                            (string) $_GET[
                                'variant_notice'
                            ]
                        )
                    )
                    : '';

            $variantError =
                isset($_GET['variant_error'])
                    ? sanitize_text_field(
                        wp_unslash(
                            (string) $_GET[
                                'variant_error'
                            ]
                        )
                    )
                    : '';

            /*
             * =================================================
             * INVENTARIO
             * =================================================
             */

            $inventoryProducts = [];

            /*
             * product_id => ProductVariant[]
             */
            $inventoryVariants = [];

            /*
             * =================================================
             * MOVIMIENTOS
             * =================================================
             */

            $stockMovements = [];

            $movementPerPage = 20;
            $movementCount = 0;
            $movementPage = 1;
            $movementTotalPages = 1;
            $movementOffset = 0;

            $movementSearch =
                isset($_GET['movement_search'])
                    ? sanitize_text_field(
                        wp_unslash(
                            (string)
                            $_GET[
                                'movement_search'
                            ]
                        )
                    )
                    : '';

            $movementTypeFilter =
                isset($_GET['movement_type'])
                    ? sanitize_key(
                        wp_unslash(
                            (string)
                            $_GET[
                                'movement_type'
                            ]
                        )
                    )
                    : '';

            if (
                $movementTypeFilter !== ''
                && !StockMovementType::isValid(
                    $movementTypeFilter
                )
            ) {
                $movementTypeFilter = '';
            }

            /*
             * product_id => Product|null
             */
            $movementProducts = [];

            /*
             * variant_id => ProductVariant|null
             */
            $movementVariants = [];

            /*
             * =================================================
             * RESERVAS
             * =================================================
             */

            $reservations = [];

            $pendingReservations = [];
            $historyReservations = [];

            $reservationPerPage = 15;

            $pendingReservationCount = 0;
            $pendingReservationPage = 1;
            $pendingReservationTotalPages = 1;
            $pendingReservationOffset = 0;

            $historyReservationCount = 0;
            $historyReservationPage = 1;
            $historyReservationTotalPages = 1;
            $historyReservationOffset = 0;

            $historyReservationFilter =
                isset(
                    $_GET[
                        'reservation_history_status'
                    ]
                )
                    ? sanitize_key(
                        wp_unslash(
                            (string) $_GET[
                                'reservation_history_status'
                            ]
                        )
                    )
                    : '';

            if (
                !in_array(
                    $historyReservationFilter,
                    [
                        '',
                        ProductReservationStatus::
                            COMPLETED,
                        ProductReservationStatus::
                            RELEASED,
                    ],
                    true
                )
            ) {
                $historyReservationFilter = '';
            }

            /*
             * Índices auxiliares utilizados por la plantilla.
             *
             * product_id => Product|null
             */
            $reservationProducts = [];

            /*
             * variant_id => ProductVariant|null
             */
            $reservationVariants = [];

            /*
             * buyer_customer_id => [
             *     id,
             *     email,
             *     display_name
             * ]
             */
            $reservationBuyers = [];

            $reservationStatus =
                isset($_GET['reservation_status'])
                    ? sanitize_key(
                        wp_unslash(
                            (string) $_GET[
                                'reservation_status'
                            ]
                        )
                    )
                    : '';

            $reservationError =
                isset($_GET['reservation_error'])
                    ? sanitize_text_field(
                        wp_unslash(
                            (string) $_GET[
                                'reservation_error'
                            ]
                        )
                    )
                    : '';

            /*
             * =================================================
             * DATOS DEL ERP
             * =================================================
             */

            if (
                $customerId > 0
                && $hasAccess
                && $store !== null
            ) {
                $catalogService =
                    new CatalogStoreService();

                /*
                 * ----------------------------------------------
                 * RESUMEN ERP
                 * ----------------------------------------------
                 */

                if (
                    $storeSection
                    === 'dashboard'
                ) {
                    $productRepository =
                        new ProductRepository();

                    $variantRepository =
                        new ProductVariantRepository();

                    $reservationRepository =
                        new ProductReservationRepository();

                    /*
                     * ==========================================
                     * PRODUCTOS + VARIANTES + STOCK
                     * ==========================================
                     */

                    $dashboardOffset = 0;
                    $dashboardLimit = 250;

                    do {
                        $dashboardProducts =
                            $productRepository
                                ->findByStore(
                                    storeId:
                                        $store->getId(),

                                    limit:
                                        $dashboardLimit,

                                    offset:
                                        $dashboardOffset,

                                    status:
                                        null
                                );

                        foreach (
                            $dashboardProducts
                            as $dashboardProduct
                        ) {
                            $dashboardProductCount++;

                            $dashboardVariants =
                                $variantRepository
                                    ->findByProduct(
                                        $dashboardProduct
                                            ->getId(),
                                        false
                                    );

                            foreach (
                                $dashboardVariants
                                as $dashboardVariant
                            ) {
                                $dashboardVariantCount++;

                                if (
                                    !$dashboardVariant
                                        ->tracksStock()
                                ) {
                                    continue;
                                }

                                $dashboardPhysicalStock +=
                                    $dashboardVariant
                                        ->getStockQuantity();

                                $dashboardReservedStock +=
                                    $dashboardVariant
                                        ->getStockReserved();

                                $dashboardAvailableStock +=
                                    $dashboardVariant
                                        ->getAvailableStock();
                            }
                        }

                        $dashboardOffset +=
                            $dashboardLimit;
                    } while (
                        count(
                            $dashboardProducts
                        )
                        === $dashboardLimit
                    );

                    /*
                     * ==========================================
                     * RESERVAS
                     * ==========================================
                     */

                    $dashboardOffset = 0;

                    do {
                        $dashboardReservations =
                            $reservationRepository
                                ->findByStore(
                                    storeId:
                                        $store->getId(),

                                    limit:
                                        $dashboardLimit,

                                    offset:
                                        $dashboardOffset,

                                    status:
                                        null
                                );

                        foreach (
                            $dashboardReservations
                            as $dashboardReservation
                        ) {
                            if (
                                $dashboardReservation
                                    ->getStatus()
                                === 'active'
                            ) {
                                $dashboardActiveReservations++;
                            }

                            if (
                                $dashboardReservation
                                    ->getStatus()
                                === 'completed'
                            ) {
                                $dashboardCompletedReservations++;
                            }
                        }

                        $dashboardOffset +=
                            $dashboardLimit;
                    } while (
                        count(
                            $dashboardReservations
                        )
                        === $dashboardLimit
                    );
                }

                /*
                 * ----------------------------------------------
                 * LISTADO DE PRODUCTOS
                 * ----------------------------------------------
                 */

                if (
                    $storeSection
                    === 'products'
                ) {
                    $productPerPage =
                        20;

                    $productSearch =
                        isset(
                            $_GET[
                                'product_search'
                            ]
                        )
                            ? sanitize_text_field(
                                wp_unslash(
                                    (string) $_GET[
                                        'product_search'
                                    ]
                                )
                            )
                            : '';

                    $productPage =
                        isset(
                            $_GET[
                                'product_page'
                            ]
                        )
                            ? max(
                                1,
                                absint(
                                    wp_unslash(
                                        (string) $_GET[
                                            'product_page'
                                        ]
                                    )
                                )
                            )
                            : 1;

                    $productCount =
                        $catalogService
                            ->countProductsForCustomer(
                                customerId:
                                    $customerId,

                                status:
                                    null,

                                search:
                                    $productSearch
                            );

                    $productTotalPages =
                        max(
                            1,
                            (int) ceil(
                                $productCount
                                / $productPerPage
                            )
                        );

                    $productPage =
                        min(
                            $productPage,
                            $productTotalPages
                        );

                    $productOffset =
                        (
                            $productPage
                            - 1
                        )
                        * $productPerPage;

                    $products =
                        $catalogService
                            ->getProductsForCustomer(
                                customerId:
                                    $customerId,

                                limit:
                                    $productPerPage,

                                offset:
                                    $productOffset,

                                status:
                                    null,

                                search:
                                    $productSearch
                            );
                }

                /*
                 * ----------------------------------------------
                 * NUEVO PRODUCTO
                 * ----------------------------------------------
                 */

                if (
                    $storeSection
                    === 'new-product'
                ) {
                    $productCategories =
                        CategoryContext::
                            getStoreCategories();

                    $brandRepository =
                        new BrandRepository();

                    $productBrands =
                        $brandRepository
                            ->findSelectable();
                }

                /*
                 * ----------------------------------------------
                 * EDITAR PRODUCTO / VARIANTES
                 * ----------------------------------------------
                 */

                if (
                    $storeSection
                    === 'edit-product'
                ) {
                    $productId =
                        isset($_GET['product_id'])
                            ? absint(
                                wp_unslash(
                                    (string) $_GET[
                                        'product_id'
                                    ]
                                )
                            )
                            : 0;

                    if ($productId <= 0) {
                        throw new RuntimeException(
                            'El identificador del producto no es válido.'
                        );
                    }

                    /*
                     * Este método verifica también que
                     * el producto pertenezca realmente
                     * a la tienda del cliente.
                     */
                    $editProduct =
                        $catalogService
                            ->getProductForCustomer(
                                $customerId,
                                $productId
                            );

                    $variantRepository =
                        new ProductVariantRepository();

                    /*
                     * false:
                     * mostramos también variantes
                     * inactivas dentro del ERP.
                     */
                    $productVariants =
                        $variantRepository
                            ->findByProduct(
                                $editProduct
                                    ->getId(),
                                false
                            );

                    $imageRepository =
                        new ProductImageRepository();

                    $productImages =
                        $imageRepository
                            ->findByProductId(
                                $editProduct
                                    ->getId()
                            );
                }

                /*
                 * ----------------------------------------------
                 * INVENTARIO
                 * ----------------------------------------------
                 */

                if (
                    $storeSection
                    === 'inventory'
                ) {
                    $variantRepository =
                        new ProductVariantRepository();

                    $inventoryPerPage =
                        20;

                    $inventorySearch =
                        isset(
                            $_GET[
                                'inventory_search'
                            ]
                        )
                            ? sanitize_text_field(
                                wp_unslash(
                                    (string) $_GET[
                                        'inventory_search'
                                    ]
                                )
                            )
                            : '';

                    $inventoryStatus =
                        isset(
                            $_GET[
                                'inventory_status'
                            ]
                        )
                            ? sanitize_key(
                                wp_unslash(
                                    (string) $_GET[
                                        'inventory_status'
                                    ]
                                )
                            )
                            : '';

                    $allowedInventoryStatuses = [
                        '',
                        'available',
                        'low_stock',
                        'out_of_stock',
                        'inactive',
                    ];

                    if (
                        !in_array(
                            $inventoryStatus,
                            $allowedInventoryStatuses,
                            true
                        )
                    ) {
                        $inventoryStatus =
                            '';
                    }

                    $inventoryPage =
                        isset(
                            $_GET[
                                'inventory_page'
                            ]
                        )
                            ? max(
                                1,
                                absint(
                                    wp_unslash(
                                        (string) $_GET[
                                            'inventory_page'
                                        ]
                                    )
                                )
                            )
                            : 1;

                    $inventoryCount =
                        $variantRepository
                            ->countInventoryByStore(
                                storeId:
                                    $store->getId(),

                                search:
                                    $inventorySearch,

                                stockStatus:
                                    $inventoryStatus
                            );

                    $inventoryTotalPages =
                        max(
                            1,
                            (int) ceil(
                                $inventoryCount
                                / $inventoryPerPage
                            )
                        );

                    $inventoryPage =
                        min(
                            $inventoryPage,
                            $inventoryTotalPages
                        );

                    $inventoryOffset =
                        (
                            $inventoryPage
                            - 1
                        )
                        * $inventoryPerPage;

                    $inventoryRows =
                        $variantRepository
                            ->findInventoryByStore(
                                storeId:
                                    $store->getId(),

                                limit:
                                    $inventoryPerPage,

                                offset:
                                    $inventoryOffset,

                                search:
                                    $inventorySearch,

                                stockStatus:
                                    $inventoryStatus
                            );
                }

                /*
                 * ----------------------------------------------
                 * MOVIMIENTOS
                 * ----------------------------------------------
                 */

                if (
                    $storeSection
                    === 'movements'
                ) {
                    $movementRepository =
                        new StockMovementRepository();

                    $productRepository =
                        new ProductRepository();

                    $variantRepository =
                        new ProductVariantRepository();

                    $movementPage =
                        isset(
                            $_GET[
                                'movement_page'
                            ]
                        )
                            ? max(
                                1,
                                absint(
                                    wp_unslash(
                                        (string)
                                        $_GET[
                                            'movement_page'
                                        ]
                                    )
                                )
                            )
                            : 1;


                    $movementCount =
                        $movementRepository
                            ->countStoreHistory(
                                storeId:
                                    $store->getId(),

                                movementType:
                                    $movementTypeFilter
                                    !== ''
                                        ? $movementTypeFilter
                                        : null,

                                search:
                                    $movementSearch
                            );


                    $movementTotalPages =
                        max(
                            1,
                            (int) ceil(
                                $movementCount
                                / $movementPerPage
                            )
                        );


                    $movementPage =
                        min(
                            $movementPage,
                            $movementTotalPages
                        );


                    $movementOffset =
                        (
                            $movementPage
                            - 1
                        )
                        * $movementPerPage;


                    $stockMovements =
                        $movementRepository
                            ->findStoreHistory(
                                storeId:
                                    $store->getId(),

                                limit:
                                    $movementPerPage,

                                offset:
                                    $movementOffset,

                                movementType:
                                    $movementTypeFilter
                                    !== ''
                                        ? $movementTypeFilter
                                        : null,

                                search:
                                    $movementSearch
                            );


                    foreach (
                        $stockMovements
                        as $movement
                    ) {
                        $movementProductId =
                            $movement
                                ->getProductId();

                        if (
                            !array_key_exists(
                                $movementProductId,
                                $movementProducts
                            )
                        ) {
                            $movementProducts[
                                $movementProductId
                            ] =
                                $productRepository
                                    ->findById(
                                        $movementProductId
                                    );
                        }

                        $movementVariantId =
                            $movement
                                ->getVariantId();

                        if (
                            !array_key_exists(
                                $movementVariantId,
                                $movementVariants
                            )
                        ) {
                            $movementVariants[
                                $movementVariantId
                            ] =
                                $variantRepository
                                    ->findById(
                                        $movementVariantId
                                    );
                        }
                    }
                }

                /*
                 * ----------------------------------------------
                 * RESERVAS
                 * ----------------------------------------------
                 */

                if (
                    $storeSection
                    === 'reservations'
                ) {
                    $reservationRepository =
                        new ProductReservationRepository();

                    $productRepository =
                        new ProductRepository();

                    $variantRepository =
                        new ProductVariantRepository();

                    $customerRepository =
                        new CustomerRepository();

                    $profileRepository =
                        new CustomerProfileRepository();

                    /*
                     * ==========================================
                     * PENDIENTES DE GESTIONAR
                     * ==========================================
                     */

                    $pendingReservationPage =
                        isset(
                            $_GET[
                                'reservation_pending_page'
                            ]
                        )
                            ? max(
                                1,
                                absint(
                                    wp_unslash(
                                        $_GET[
                                            'reservation_pending_page'
                                        ]
                                    )
                                )
                            )
                            : 1;

                    $pendingReservationCount =
                        $reservationRepository
                            ->countByStoreStatuses(
                                storeId:
                                    $store->getId(),

                                statuses: [
                                    ProductReservationStatus::
                                        ACTIVE,
                                ]
                            );

                    $pendingReservationTotalPages =
                        max(
                            1,
                            (int) ceil(
                                $pendingReservationCount
                                / $reservationPerPage
                            )
                        );

                    $pendingReservationPage =
                        min(
                            $pendingReservationPage,
                            $pendingReservationTotalPages
                        );

                    $pendingReservationOffset =
                        (
                            $pendingReservationPage
                            - 1
                        )
                        * $reservationPerPage;

                    $pendingReservations =
                        $reservationRepository
                            ->findByStoreStatuses(
                                storeId:
                                    $store->getId(),

                                statuses: [
                                    ProductReservationStatus::
                                        ACTIVE,
                                ],

                                limit:
                                    $reservationPerPage,

                                offset:
                                    $pendingReservationOffset
                            );


                    /*
                     * ==========================================
                     * HISTORICO
                     * ==========================================
                     */

                    $historyReservationPage =
                        isset(
                            $_GET[
                                'reservation_history_page'
                            ]
                        )
                            ? max(
                                1,
                                absint(
                                    wp_unslash(
                                        $_GET[
                                            'reservation_history_page'
                                        ]
                                    )
                                )
                            )
                            : 1;

                    if (
                        $historyReservationFilter
                        === ProductReservationStatus::
                            COMPLETED
                    ) {
                        $historyStatuses = [
                            ProductReservationStatus::
                                COMPLETED,
                        ];
                    } elseif (
                        $historyReservationFilter
                        === ProductReservationStatus::
                            RELEASED
                    ) {
                        $historyStatuses = [
                            ProductReservationStatus::
                                RELEASED,
                        ];
                    } else {
                        $historyStatuses = [
                            ProductReservationStatus::
                                COMPLETED,

                            ProductReservationStatus::
                                RELEASED,
                        ];
                    }

                    $historyReservationCount =
                        $reservationRepository
                            ->countByStoreStatuses(
                                storeId:
                                    $store->getId(),

                                statuses:
                                    $historyStatuses
                            );

                    $historyReservationTotalPages =
                        max(
                            1,
                            (int) ceil(
                                $historyReservationCount
                                / $reservationPerPage
                            )
                        );

                    $historyReservationPage =
                        min(
                            $historyReservationPage,
                            $historyReservationTotalPages
                        );

                    $historyReservationOffset =
                        (
                            $historyReservationPage
                            - 1
                        )
                        * $reservationPerPage;

                    $historyReservations =
                        $reservationRepository
                            ->findByStoreStatuses(
                                storeId:
                                    $store->getId(),

                                statuses:
                                    $historyStatuses,

                                limit:
                                    $reservationPerPage,

                                offset:
                                    $historyReservationOffset
                            );


                    /*
                     * La colección combinada solo se utiliza
                     * para resolver productos, variantes y
                     * compradores de las dos tablas.
                     */
                    $reservations =
                        array_merge(
                            $pendingReservations,
                            $historyReservations
                        );

                    foreach (
                        $reservations
                        as $reservation
                    ) {
                        /*
                         * =======================================
                         * PRODUCTO
                         * =======================================
                         */

                        $reservationProductId =
                            $reservation
                                ->getProductId();

                        if (
                            !array_key_exists(
                                $reservationProductId,
                                $reservationProducts
                            )
                        ) {
                            $reservationProducts[
                                $reservationProductId
                            ] =
                                $productRepository
                                    ->findById(
                                        $reservationProductId
                                    );
                        }

                        /*
                         * =======================================
                         * VARIANTE
                         * =======================================
                         */

                        $reservationVariantId =
                            $reservation
                                ->getVariantId();

                        if (
                            !array_key_exists(
                                $reservationVariantId,
                                $reservationVariants
                            )
                        ) {
                            $reservationVariants[
                                $reservationVariantId
                            ] =
                                $variantRepository
                                    ->findById(
                                        $reservationVariantId
                                    );
                        }

                        /*
                         * =======================================
                         * COMPRADOR
                         * =======================================
                         */

                        $buyerCustomerId =
                            $reservation
                                ->getBuyerCustomerId();

                        if (
                            $buyerCustomerId === null
                        ) {
                            continue;
                        }

                        if (
                            array_key_exists(
                                $buyerCustomerId,
                                $reservationBuyers
                            )
                        ) {
                            continue;
                        }

                        $buyer =
                            $customerRepository
                                ->findById(
                                    $buyerCustomerId
                                );

                        $buyerProfile =
                            $profileRepository
                                ->findByCustomerId(
                                    $buyerCustomerId
                                );

                        $reservationBuyers[
                            $buyerCustomerId
                        ] = [
                            'id' =>
                                $buyerCustomerId,

                            'email' =>
                                $buyer !== null
                                    ? $buyer
                                        ->getEmail()
                                    : '',

                            'display_name' =>
                                $buyerProfile
                                !== null
                                    ? $buyerProfile
                                        ->getDisplayName()
                                    : '',
                        ];
                    }
                }
            }

            /*
             * =================================================
             * PLANTILLA PRINCIPAL
             * =================================================
             */

            $template =
                DSM_MULTITIENDA_PATH
                . 'templates/account/'
                . 'my-store.php';

            if (!is_file($template)) {
                return self::renderError(
                    'No se encontró la plantilla de Mi tienda.'
                );
            }

            ob_start();

            include $template;

            $output =
                ob_get_clean();

            return is_string($output)
                ? $output
                : '';
        } catch (Throwable $exception) {
            return self::renderError(
                $exception->getMessage()
            );
        }
    }

    private static function renderError(
        string $message
    ): string {
        return sprintf(
            '<div class="dsm-account-notice dsm-account-notice--error">%s</div>',
            esc_html(
                $message
            )
        );
    }

    private function __construct()
    {
    }
}