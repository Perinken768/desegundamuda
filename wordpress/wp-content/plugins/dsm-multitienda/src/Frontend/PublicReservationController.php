<?php

declare(strict_types=1);

namespace DSM\Multitienda\Frontend;

use DSM\Catalogo\Application\ReserveProductStock;
use DSM\Catalogo\Inventory\StockMovementRepository;
use DSM\Catalogo\Product\ProductRepository;
use DSM\Catalogo\Reservation\ProductReservationRepository;
use DSM\Catalogo\Stock\StockService;
use DSM\Catalogo\Variant\ProductVariantRepository;
use DSM\Multitienda\Integration\ReservationNotificationService;
use DSM\Multitienda\Store\StoreRepository;
use DSM\Multitienda\Support\CustomerContext;
use RuntimeException;
use Throwable;

if (!defined('ABSPATH')) {
    exit;
}

final class PublicReservationController
{
    public const ACTION =
        'dsm_multistore_reserve_variant';

    public const NONCE_FIELD =
        'dsm_multistore_reservation_nonce';

    public static function register(): void
    {
        add_action(
            'admin_post_'
            . self::ACTION,
            [
                self::class,
                'handleReserve',
            ]
        );

        /*
         * Los clientes DSM utilizan su propio
         * sistema de sesión, no el login WP.
         */
        add_action(
            'admin_post_nopriv_'
            . self::ACTION,
            [
                self::class,
                'handleReserve',
            ]
        );
    }

    public static function handleReserve(): never
    {
        $storeId =
            isset($_POST['store_id'])
                ? absint(
                    wp_unslash(
                        (string) $_POST[
                            'store_id'
                        ]
                    )
                )
                : 0;

        $storeRepository =
            new StoreRepository();

        $store =
            $storeId > 0
                ? $storeRepository->findById(
                    $storeId
                )
                : null;

        /*
         * Necesitamos conocer la tienda antes de
         * realizar el resto de comprobaciones para
         * disponer siempre de una URL segura de retorno.
         */
        if ($store === null) {
            wp_safe_redirect(
                home_url('/')
            );

            exit;
        }

        $storeUrl =
            home_url(
                '/tienda/'
                . $store->getSlug()
                . '/'
            );

        try {
            /*
             * =================================================
             * CLIENTE COMPRADOR
             * =================================================
             */

            $customerContext =
                CustomerContext::current();

            if ($customerContext === null) {
                wp_safe_redirect(
                    add_query_arg(
                        [
                            'redirect_to' =>
                                $storeUrl,
                        ],
                        home_url(
                            '/iniciar-sesion/'
                        )
                    )
                );

                exit;
            }

            $customerContext =
                CustomerContext::
                    requireCurrentActive();

            $buyerCustomerId =
                max(
                    0,
                    (int) (
                        $customerContext['id']
                        ?? 0
                    )
                );

            if ($buyerCustomerId <= 0) {
                throw new RuntimeException(
                    'No se pudo identificar al comprador.'
                );
            }

            /*
             * =================================================
             * CONTACTO WHATSAPP OBLIGATORIO
             * =================================================
             */

            $buyerPhone =
                trim(
                    (string) (
                        $customerContext['phone']
                        ?? ''
                    )
                );

            $buyerAllowsWhatsapp =
                !empty(
                    $customerContext[
                        'allow_whatsapp'
                    ]
                );

            if ($buyerPhone === '') {
                throw new RuntimeException(
                    'Para realizar reservas debes añadir un teléfono de contacto en tu perfil.'
                );
            }

            if (!$buyerAllowsWhatsapp) {
                throw new RuntimeException(
                    'Para realizar reservas debes permitir el contacto por WhatsApp en tu perfil.'
                );
            }

            /*
             * =================================================
             * TIENDA
             * =================================================
             */

            if (!$store->isActive()) {
                throw new RuntimeException(
                    'La tienda no está disponible.'
                );
            }

            if (
                $buyerCustomerId
                === $store->getCustomerId()
            ) {
                throw new RuntimeException(
                    'No puedes reservar productos de tu propia tienda.'
                );
            }

            /*
             * =================================================
             * DATOS DE LA RESERVA
             * =================================================
             */

            $variantId =
                isset($_POST['variant_id'])
                    ? absint(
                        wp_unslash(
                            (string) $_POST[
                                'variant_id'
                            ]
                        )
                    )
                    : 0;

            $quantity =
                isset($_POST['quantity'])
                    ? absint(
                        wp_unslash(
                            (string) $_POST[
                                'quantity'
                            ]
                        )
                    )
                    : 0;

            if ($variantId <= 0) {
                throw new RuntimeException(
                    'La variante seleccionada no es válida.'
                );
            }

            if ($quantity <= 0) {
                throw new RuntimeException(
                    'La cantidad a reservar debe ser mayor que cero.'
                );
            }

            /*
             * =================================================
             * NONCE
             * =================================================
             *
             * No usamos check_admin_referer() aquí.
             *
             * Esa función puede terminar directamente
             * la ejecución mediante wp_die(), impidiendo
             * que nuestro catch capture el error y lo
             * muestre de nuevo en el escaparate.
             */

            $nonce =
                isset($_POST[self::NONCE_FIELD])
                    ? sanitize_text_field(
                        wp_unslash(
                            (string) $_POST[
                                self::NONCE_FIELD
                            ]
                        )
                    )
                    : '';

            if ($nonce === '') {
                throw new RuntimeException(
                    'No se recibió la validación de seguridad de la reserva.'
                );
            }

            $nonceResult =
                wp_verify_nonce(
                    $nonce,
                    self::getNonceAction(
                        $store->getId(),
                        $variantId
                    )
                );

            if ($nonceResult === false) {
                throw new RuntimeException(
                    'La solicitud de reserva ha caducado. Recarga la tienda e inténtalo de nuevo.'
                );
            }

            /*
             * =================================================
             * SERVICIOS DE CATÁLOGO
             * =================================================
             */

            $productRepository =
                new ProductRepository();

            $variantRepository =
                new ProductVariantRepository();

            $reservationRepository =
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
                    $reservationRepository,
                    $stockService
                );

            /*
             * =================================================
             * CREACIÓN TRANSACCIONAL DE LA RESERVA
             * =================================================
             */

            $result =
                $reserve->execute(
                    storeId:
                        $store->getId(),

                    sellerCustomerId:
                        $store->getCustomerId(),

                    variantId:
                        $variantId,

                    quantity:
                        $quantity,

                    context:
                        [
                            'buyer_customer_id' =>
                                $buyerCustomerId,

                            'conversation_id' =>
                                null,

                            'external_contact' =>
                                null,

                            /*
                             * La caducidad de reservas
                             * se configurará posteriormente
                             * desde Multitienda.
                             */
                            'expires_at' =>
                                null,

                            /*
                             * Los clientes DSM no son
                             * usuarios WordPress.
                             */
                            'user_id' =>
                                null,

                            'notes' =>
                                'Reserva realizada desde el escaparate de DSM Multitienda.',
                        ]
                );

            $reservation =
                $result['reservation']
                ?? null;

            if ($reservation === null) {
                throw new RuntimeException(
                    'La reserva se realizó, pero no pudo recuperarse.'
                );
            }

            error_log(
                sprintf(
                    '[DSM Multitienda] Reserva pública creada. Reserva=%d Tienda=%d Vendedor=%d Comprador=%d Variante=%d Cantidad=%d',
                    $reservation->getId(),
                    $store->getId(),
                    $store->getCustomerId(),
                    $buyerCustomerId,
                    $variantId,
                    $quantity
                )
            );

            /*
             * =================================================
             * NOTIFICACIONES
             * =================================================
             *
             * El correo es posterior a la transacción.
             *
             * Si falla, la reserva NO se revierte ni se pierde
             * el bloqueo de stock.
             */

            try {
                $notificationService =
                    new ReservationNotificationService();

                $notificationService->notify(
                    $store,
                    $reservation,
                    $customerContext
                );
            } catch (Throwable $notificationException) {
                error_log(
                    sprintf(
                        '[DSM Multitienda] Reserva %d creada, pero fallaron las notificaciones: %s',
                        $reservation->getId(),
                        $notificationException->getMessage()
                    )
                );
            }

            self::redirect(
                $storeUrl,
                [
                    'reservation_status' =>
                        'created',

                    'reservation_id' =>
                        $reservation->getId(),
                ]
            );
        } catch (Throwable $exception) {
            error_log(
                sprintf(
                    '[DSM Multitienda] Error en reserva pública. Tienda=%d Error=%s',
                    $store->getId(),
                    $exception->getMessage()
                )
            );

            self::redirect(
                $storeUrl,
                [
                    'reservation_status' =>
                        'error',

                    'reservation_error' =>
                        $exception->getMessage(),
                ]
            );
        }
    }

    public static function getNonceAction(
        int $storeId,
        int $variantId
    ): string {
        return self::ACTION
            . '_'
            . $storeId
            . '_'
            . $variantId;
    }

    /**
     * @param array<string, scalar> $arguments
     */
    private static function redirect(
        string $url,
        array $arguments
    ): never {
        wp_safe_redirect(
            add_query_arg(
                $arguments,
                $url
            )
        );

        exit;
    }

    private function __construct()
    {
    }
}
