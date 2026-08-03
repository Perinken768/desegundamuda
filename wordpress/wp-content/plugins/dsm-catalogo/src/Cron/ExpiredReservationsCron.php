<?php

declare(strict_types=1);

namespace DSM\Catalogo\Cron;

use DSM\Catalogo\Application\ExpireProductReservation;
use DSM\Catalogo\Application\ProcessExpiredReservations;
use DSM\Catalogo\Inventory\StockMovementRepository;
use DSM\Catalogo\Reservation\ProductReservationRepository;
use DSM\Catalogo\Stock\StockService;
use Throwable;

if (!defined('ABSPATH')) {
    exit;
}

final class ExpiredReservationsCron
{
    public const HOOK =
        'dsm_catalogo_process_expired_reservations';

    private const BATCH_LIMIT =
        100;

    public static function register(): void
    {
        add_action(
            self::HOOK,
            [self::class, 'run']
        );
    }

    public static function activate(): void
    {
        if (
            wp_next_scheduled(
                self::HOOK
            ) !== false
        ) {
            return;
        }

        $scheduled = wp_schedule_event(
            time() + MINUTE_IN_SECONDS,
            'hourly',
            self::HOOK
        );

        if ($scheduled === false) {
            error_log(
                '[DSM Catálogo] No se pudo programar el cron de reservas caducadas.'
            );
        }
    }

    public static function deactivate(): void
    {
        wp_clear_scheduled_hook(
            self::HOOK
        );
    }

    public static function ensureScheduled(): void
    {
        if (
            wp_next_scheduled(
                self::HOOK
            ) !== false
        ) {
            return;
        }

        self::activate();
    }

    public static function run(): void
    {
        try {
            $reservationRepository =
                new ProductReservationRepository();

            $stockService =
                new StockService(
                    new StockMovementRepository()
                );

            $expireReservation =
                new ExpireProductReservation(
                    $reservationRepository,
                    $stockService
                );

            $processor =
                new ProcessExpiredReservations(
                    $reservationRepository,
                    $expireReservation
                );

            $result = $processor->execute(
                limit: self::BATCH_LIMIT
            );

            if (
                $result['expired'] > 0
                || $result['failed'] > 0
            ) {
                error_log(
                    sprintf(
                        '[DSM Catálogo] Cron de reservas terminado. Revisadas: %d; caducadas: %d; fallidas: %d.',
                        $result['checked'],
                        $result['expired'],
                        $result['failed']
                    )
                );
            }
        } catch (Throwable $exception) {
            error_log(
                sprintf(
                    '[DSM Catálogo] Error general en el cron de reservas caducadas: %s',
                    $exception->getMessage()
                )
            );
        }
    }

    private function __construct()
    {
    }
}