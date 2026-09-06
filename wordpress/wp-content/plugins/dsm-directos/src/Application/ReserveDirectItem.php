<?php

declare(strict_types=1);

namespace DSM\Directos\Application;

use DSM\Anuncios\Advertisement\AdvertisementRepository;
use DSM\Anuncios\Application\ReserveAdvertisement;
use DSM\Anuncios\Moderation\AdvertisementModerationService;
use DSM\Anuncios\Moderation\AdvertisementStatusHistoryRepository;
use DSM\Catalogo\Application\ReserveProductStock;
use DSM\Catalogo\Inventory\StockMovementRepository;
use DSM\Catalogo\Product\ProductRepository;
use DSM\Catalogo\Reservation\ProductReservationRepository;
use DSM\Catalogo\Stock\StockService;
use DSM\Catalogo\Variant\ProductVariantRepository;
use DSM\Directos\Direct\DirectItemRepository;
use DSM\Directos\Direct\DirectRepository;
use DSM\Directos\Direct\DirectReservationRepository;
use DSM\Multitienda\Integration\ReservationNotificationService;
use DSM\Multitienda\Store\StoreRepository;
use RuntimeException;
use Throwable;

if (!defined('ABSPATH')) {
    exit;
}

final class ReserveDirectItem
{
    public function __construct(
        private readonly ?DirectRepository $directRepository = null,
        private readonly ?DirectItemRepository $itemRepository = null,
        private readonly ?DirectReservationRepository $reservationRepository = null
    ) {
    }

    /**
     * @param array<string, mixed> $buyerContext
     *
     * @return array{
     *     reservation_id:int,
     *     source_type:string,
     *     remaining:int,
     *     remove_item:bool
     * }
     */
    public function execute(
        int $liveItemId,
        array $buyerContext
    ): array {
        global $wpdb;

        $buyerCustomerId =
            max(
                0,
                (int) (
                    $buyerContext['id']
                    ?? 0
                )
            );

        if ($buyerCustomerId <= 0) {
            throw new RuntimeException(
                'Debes iniciar sesión para reservar una prenda.'
            );
        }

        $buyerStatus =
            sanitize_key(
                (string) (
                    $buyerContext['status']
                    ?? ''
                )
            );

        if ($buyerStatus !== 'active') {
            throw new RuntimeException(
                'Tu cuenta no está activa.'
            );
        }

        $itemRepository =
            $this->itemRepository
            ?? new DirectItemRepository();

        /*
         * Lo recuperamos directamente para no depender de
         * métodos adicionales del repositorio.
         */
        $itemsTable =
            $wpdb->prefix
            . 'dsm_live_items';

        $item =
            $wpdb->get_row(
                $wpdb->prepare(
                    "
                    SELECT
                        id,
                        live_id,
                        source_type,
                        advertisement_id,
                        product_id,
                        variant_id,
                        allocated_quantity,
                        status
                    FROM {$itemsTable}
                    WHERE id = %d
                    LIMIT 1
                    ",
                    $liveItemId
                ),
                ARRAY_A
            );

        if (!is_array($item)) {
            throw new RuntimeException(
                'No se encontró la prenda del directo.'
            );
        }

        $liveId =
            (int) (
                $item['live_id']
                ?? 0
            );

        $directRepository =
            $this->directRepository
            ?? new DirectRepository();

        /*
         * Recuperamos el directo desde la tabla para garantizar
         * que continúa en emisión justo en el momento de reservar.
         */
        $streamsTable =
            $wpdb->prefix
            . 'dsm_live_streams';

        $direct =
            $wpdb->get_row(
                $wpdb->prepare(
                    "
                    SELECT
                        id,
                        customer_id,
                        status
                    FROM {$streamsTable}
                    WHERE id = %d
                    LIMIT 1
                    ",
                    $liveId
                ),
                ARRAY_A
            );

        if (!is_array($direct)) {
            throw new RuntimeException(
                'No se encontró el directo.'
            );
        }

        if (
            sanitize_key(
                (string) (
                    $direct['status']
                    ?? ''
                )
            ) !== 'live'
        ) {
            throw new RuntimeException(
                'El directo ya no está en emisión.'
            );
        }

        $sellerCustomerId =
            max(
                0,
                (int) (
                    $direct['customer_id']
                    ?? 0
                )
            );

        if (
            $sellerCustomerId <= 0
            || $sellerCustomerId
                === $buyerCustomerId
        ) {
            throw new RuntimeException(
                'No puedes reservar tus propias prendas.'
            );
        }

        $itemStatus =
            sanitize_key(
                (string) (
                    $item['status']
                    ?? ''
                )
            );

        if ($itemStatus !== 'available') {
            throw new RuntimeException(
                'Esta prenda ya no está disponible.'
            );
        }

        $sourceType =
            sanitize_key(
                (string) (
                    $item['source_type']
                    ?? ''
                )
            );

        return match ($sourceType) {
            'inventory' =>
                $this->reserveInventory(
                    item:
                        $item,

                    sellerCustomerId:
                        $sellerCustomerId,

                    buyerCustomerId:
                        $buyerCustomerId,

                    buyerContext:
                        $buyerContext
                ),

            'advertisement' =>
                $this->reserveAdvertisement(
                    item:
                        $item,

                    sellerCustomerId:
                        $sellerCustomerId,

                    buyerCustomerId:
                        $buyerCustomerId
                ),

            default =>
                throw new RuntimeException(
                    'El tipo de prenda del directo no es válido.'
                ),
        };
    }

    /**
     * @param array<string, mixed> $item
     * @param array<string, mixed> $buyerContext
     *
     * @return array{
     *     reservation_id:int,
     *     source_type:string,
     *     remaining:int,
     *     remove_item:bool
     * }
     */
    private function reserveInventory(
        array $item,
        int $sellerCustomerId,
        int $buyerCustomerId,
        array $buyerContext
    ): array {
        global $wpdb;

        $productId =
            max(
                0,
                (int) (
                    $item['product_id']
                    ?? 0
                )
            );

        $variantId =
            max(
                0,
                (int) (
                    $item['variant_id']
                    ?? 0
                )
            );

        if (
            $productId <= 0
            || $variantId <= 0
        ) {
            throw new RuntimeException(
                'La variante del directo no es válida.'
            );
        }

        $productRepository =
            new ProductRepository();

        $product =
            $productRepository->findById(
                $productId
            );

        if ($product === null) {
            throw new RuntimeException(
                'No se encontró el producto.'
            );
        }

        $storeRepository =
            new StoreRepository();

        $store =
            $storeRepository->findById(
                $product->getStoreId()
            );

        if ($store === null) {
            throw new RuntimeException(
                'No se encontró la tienda del producto.'
            );
        }

        if (
            $store->getCustomerId()
            !== $sellerCustomerId
        ) {
            throw new RuntimeException(
                'La prenda no pertenece al vendedor del directo.'
            );
        }

        $variantRepository =
            new ProductVariantRepository();

        $productReservationRepository =
            new ProductReservationRepository();

        $movementRepository =
            new StockMovementRepository();

        $stockService =
            new StockService(
                $movementRepository
            );

        $reserve =
            new ReserveProductStock(
                $productRepository,
                $variantRepository,
                $productReservationRepository,
                $stockService
            );

        /*
         * En DSM Directos cada pulsación reserva una unidad.
         */
        $result =
            $reserve->execute(
                storeId:
                    $store->getId(),

                sellerCustomerId:
                    $sellerCustomerId,

                variantId:
                    $variantId,

                quantity:
                    1,

                context:
                    [
                        'buyer_customer_id' =>
                            $buyerCustomerId,

                        'conversation_id' =>
                            null,

                        'external_contact' =>
                            null,

                        'expires_at' =>
                            null,

                        'user_id' =>
                            null,

                        'notes' =>
                            'Reserva realizada desde DSM Directos.',
                    ]
            );

        $sourceReservation =
            $result['reservation']
            ?? null;

        $stockResult =
            $result['stock']
            ?? null;

        if (
            $sourceReservation === null
            || $stockResult === null
        ) {
            throw new RuntimeException(
                'La reserva se realizó, pero no pudo recuperarse.'
            );
        }

        $remaining =
            max(
                0,
                $stockResult
                    ->getAvailableStockAfter()
            );

        $directReservationRepository =
            $this->reservationRepository
            ?? new DirectReservationRepository();

        $directReservationId =
            $directReservationRepository
                ->create(
                    [
                        'live_id' =>
                            (int) $item['live_id'],

                        'live_item_id' =>
                            (int) $item['id'],

                        'buyer_customer_id' =>
                            $buyerCustomerId,

                        'seller_customer_id' =>
                            $sellerCustomerId,

                        'product_id' =>
                            $productId,

                        'variant_id' =>
                            $variantId,

                        'quantity' =>
                            1,

                        'source_reservation_id' =>
                            $sourceReservation
                                ->getId(),
                    ]
                );

        /*
         * Si no queda stock disponible, dejamos la prenda
         * fuera del catálogo del directo.
         */
        if ($remaining <= 0) {
            $wpdb->update(
                $wpdb->prefix
                    . 'dsm_live_items',
                [
                    'status' =>
                        'sold_out',

                    'updated_at' =>
                        current_time(
                            'mysql',
                            true
                        ),
                ],
                [
                    'id' =>
                        (int) $item['id'],
                ],
                [
                    '%s',
                    '%s',
                ],
                [
                    '%d',
                ]
            );
        }

        /*
         * Mismo correo que una reserva normal de Multitienda.
         */
        try {
            (
                new ReservationNotificationService()
            )->notify(
                $store,
                $sourceReservation,
                $buyerContext
            );
        } catch (Throwable $exception) {
            error_log(
                sprintf(
                    '[DSM Directos] Reserva %d creada, pero fallaron las notificaciones: %s',
                    $directReservationId,
                    $exception->getMessage()
                )
            );
        }

        return [
            'reservation_id' =>
                $directReservationId,

            'source_type' =>
                'inventory',

            'remaining' =>
                $remaining,

            'remove_item' =>
                $remaining <= 0,
        ];
    }

    /**
     * @param array<string, mixed> $item
     *
     * @return array{
     *     reservation_id:int,
     *     source_type:string,
     *     remaining:int,
     *     remove_item:bool
     * }
     */
    private function reserveAdvertisement(
        array $item,
        int $sellerCustomerId,
        int $buyerCustomerId
    ): array {
        global $wpdb;

        $advertisementId =
            max(
                0,
                (int) (
                    $item['advertisement_id']
                    ?? 0
                )
            );

        if ($advertisementId <= 0) {
            throw new RuntimeException(
                'El anuncio del directo no es válido.'
            );
        }

        $directReservationRepository =
            $this->reservationRepository
            ?? new DirectReservationRepository();

        if (
            $directReservationRepository
                ->buyerAlreadyReservedAdvertisement(
                    $buyerCustomerId,
                    $advertisementId
                )
        ) {
            throw new RuntimeException(
                'Ya has reservado este anuncio.'
            );
        }

        $advertisementRepository =
            new AdvertisementRepository();

        $advertisement =
            $advertisementRepository
                ->findById(
                    $advertisementId
                );

        if ($advertisement === null) {
            throw new RuntimeException(
                'No se encontró el anuncio.'
            );
        }

        if (
            $advertisement->getCustomerId()
            !== $sellerCustomerId
        ) {
            throw new RuntimeException(
                'El anuncio no pertenece al vendedor del directo.'
            );
        }

        if (
            $advertisement->getStatus()
            !== 'active'
        ) {
            throw new RuntimeException(
                'El anuncio ya no está disponible.'
            );
        }

        $moderationService =
            new AdvertisementModerationService(
                $advertisementRepository,
                new AdvertisementStatusHistoryRepository()
            );

        $reserveAdvertisement =
            new ReserveAdvertisement(
                $moderationService
            );

        /*
         * ReserveAdvertisement realiza:
         * active -> reserved
         * y registra el historial del anuncio.
         */
        $reserveAdvertisement->execute(
            $sellerCustomerId,
            $advertisementId,
            sprintf(
                'Reservado por el cliente %d desde DSM Directos.',
                $buyerCustomerId
            )
        );

        $directReservationId =
            $directReservationRepository
                ->create(
                    [
                        'live_id' =>
                            (int) $item['live_id'],

                        'live_item_id' =>
                            (int) $item['id'],

                        'buyer_customer_id' =>
                            $buyerCustomerId,

                        'seller_customer_id' =>
                            $sellerCustomerId,

                        'advertisement_id' =>
                            $advertisementId,

                        'quantity' =>
                            1,

                        'source_reservation_id' =>
                            null,
                    ]
                );

        /*
         * Un anuncio particular solo representa una unidad.
         * Una vez reservado desaparece del directo.
         */
        $wpdb->update(
            $wpdb->prefix
                . 'dsm_live_items',
            [
                'status' =>
                    'reserved',

                'updated_at' =>
                    current_time(
                        'mysql',
                        true
                    ),
            ],
            [
                'id' =>
                    (int) $item['id'],
            ],
            [
                '%s',
                '%s',
            ],
            [
                '%d',
            ]
        );

        return [
            'reservation_id' =>
                $directReservationId,

            'source_type' =>
                'advertisement',

            'remaining' =>
                0,

            'remove_item' =>
                true,
        ];
    }
}
