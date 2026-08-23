<?php

declare(strict_types=1);

namespace DSM\Multitienda\Frontend;

use DSM\Catalogo\Application\CompleteProductReservation;
use DSM\Catalogo\Application\ReleaseProductReservation;
use DSM\Catalogo\Inventory\StockMovementRepository;
use DSM\Catalogo\Reservation\ProductReservationRepository;
use DSM\Catalogo\Stock\StockService;
use DSM\Multitienda\Store\StoreRepository;
use DSM\Multitienda\Support\CustomerContext;
use RuntimeException;
use Throwable;

if (!defined('ABSPATH')) {
    exit;
}

final class StoreReservationController
{
    public const COMPLETE_ACTION =
        'dsm_multistore_complete_reservation';

    public const RELEASE_ACTION =
        'dsm_multistore_release_reservation';

    public const NONCE_FIELD =
        'dsm_multistore_reservation_action_nonce';

    public static function register(): void
    {
        /*
         * Los clientes DSM no son necesariamente
         * usuarios autenticados de WordPress.
         */
        add_action(
            'admin_post_'
            . self::COMPLETE_ACTION,
            [
                self::class,
                'handleComplete',
            ]
        );

        add_action(
            'admin_post_nopriv_'
            . self::COMPLETE_ACTION,
            [
                self::class,
                'handleComplete',
            ]
        );

        add_action(
            'admin_post_'
            . self::RELEASE_ACTION,
            [
                self::class,
                'handleRelease',
            ]
        );

        add_action(
            'admin_post_nopriv_'
            . self::RELEASE_ACTION,
            [
                self::class,
                'handleRelease',
            ]
        );
    }

    public static function handleComplete(): never
    {
        try {
            $context =
                CustomerContext::
                    requireCurrentActive();

            $customerId =
                self::getCustomerId(
                    $context
                );

            $store =
                self::getStoreForCustomer(
                    $customerId
                );

            $reservationId =
                self::getReservationId();

            self::verifyNonce(
                $reservationId,
                self::COMPLETE_ACTION
            );

            $reservationRepository =
                new ProductReservationRepository();

            $stockService =
                new StockService(
                    new StockMovementRepository()
                );

            $service =
                new CompleteProductReservation(
                    $reservationRepository,
                    $stockService
                );

            $result =
                $service->execute(
                    storeId:
                        $store->getId(),

                    sellerCustomerId:
                        $customerId,

                    reservationId:
                        $reservationId,

                    userId:
                        null,

                    notes:
                        'Venta completada desde DSM Multitienda.'
                );

            $reservation =
                $result['reservation']
                ?? null;

            if ($reservation === null) {
                throw new RuntimeException(
                    'La venta se completó, pero no pudo recuperarse la reserva.'
                );
            }

            self::redirect(
                [
                    'reservation_status' =>
                        'completed',

                    'reservation_id' =>
                        $reservation->getId(),
                ]
            );
        } catch (Throwable $exception) {
            error_log(
                '[DSM Multitienda] No se pudo completar la reserva: '
                . $exception->getMessage()
            );

            self::redirect(
                [
                    'reservation_status' =>
                        'error',

                    'reservation_error' =>
                        $exception->getMessage(),
                ]
            );
        }
    }

    public static function handleRelease(): never
    {
        try {
            $context =
                CustomerContext::
                    requireCurrentActive();

            $customerId =
                self::getCustomerId(
                    $context
                );

            $store =
                self::getStoreForCustomer(
                    $customerId
                );

            $reservationId =
                self::getReservationId();

            self::verifyNonce(
                $reservationId,
                self::RELEASE_ACTION
            );

            $reservationRepository =
                new ProductReservationRepository();

            $stockService =
                new StockService(
                    new StockMovementRepository()
                );

            $service =
                new ReleaseProductReservation(
                    $reservationRepository,
                    $stockService
                );

            $result =
                $service->execute(
                    storeId:
                        $store->getId(),

                    sellerCustomerId:
                        $customerId,

                    reservationId:
                        $reservationId,

                    userId:
                        null,

                    notes:
                        'Reserva liberada desde DSM Multitienda.'
                );

            $reservation =
                $result['reservation']
                ?? null;

            if ($reservation === null) {
                throw new RuntimeException(
                    'La reserva se liberó, pero no pudo recuperarse.'
                );
            }

            self::redirect(
                [
                    'reservation_status' =>
                        'released',

                    'reservation_id' =>
                        $reservation->getId(),
                ]
            );
        } catch (Throwable $exception) {
            error_log(
                '[DSM Multitienda] No se pudo liberar la reserva: '
                . $exception->getMessage()
            );

            self::redirect(
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
        string $action,
        int $reservationId
    ): string {
        return $action
            . '_'
            . $reservationId;
    }

    /**
     * @param array<string, mixed> $context
     */
    private static function getCustomerId(
        array $context
    ): int {
        $customerId =
            max(
                0,
                (int) (
                    $context['id']
                    ?? 0
                )
            );

        if ($customerId <= 0) {
            throw new RuntimeException(
                'No se pudo identificar al vendedor.'
            );
        }

        return $customerId;
    }

    private static function getStoreForCustomer(
        int $customerId
    ): \DSM\Multitienda\Store\Store {
        $storeRepository =
            new StoreRepository();

        $store =
            $storeRepository
                ->findByCustomerId(
                    $customerId
                );

        if ($store === null) {
            throw new RuntimeException(
                'No se encontró la tienda del vendedor.'
            );
        }

        return $store;
    }

    private static function getReservationId(): int
    {
        $reservationId =
            isset($_POST['reservation_id'])
                ? absint(
                    wp_unslash(
                        (string) $_POST[
                            'reservation_id'
                        ]
                    )
                )
                : 0;

        if ($reservationId <= 0) {
            throw new RuntimeException(
                'La reserva indicada no es válida.'
            );
        }

        return $reservationId;
    }

    private static function verifyNonce(
        int $reservationId,
        string $action
    ): void {
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

        if (
            $nonce === ''
            || wp_verify_nonce(
                $nonce,
                self::getNonceAction(
                    $action,
                    $reservationId
                )
            ) === false
        ) {
            throw new RuntimeException(
                'La solicitud ha caducado. Recarga la página e inténtalo de nuevo.'
            );
        }
    }

    /**
     * @param array<string, scalar> $arguments
     */
    private static function redirect(
        array $arguments
    ): never {
        $url =
            add_query_arg(
                array_merge(
                    [
                        'store_section' =>
                            'reservations',
                    ],
                    $arguments
                ),
                home_url(
                    '/mi-tienda/'
                )
            );

        wp_safe_redirect(
            $url
        );

        exit;
    }

    private function __construct()
    {
    }
}
