<?php

declare(strict_types=1);

namespace DSM\Promocionar\Cron;

use DSM\Promocionar\Application\ExpirePromotionAssignments;
use Throwable;

if (!defined('ABSPATH')) {
    exit;
}

final class ExpiredPromotionsCron
{
    public const HOOK =
        'dsm_promocionar_process_expired_promotions';

    private const SCHEDULE =
        'dsm_every_five_minutes';

    private const BATCH_LIMIT =
        100;

    public static function register(): void
    {
        add_filter(
            'cron_schedules',
            [
                self::class,
                'registerSchedule',
            ]
        );

        add_action(
            self::HOOK,
            [
                self::class,
                'run',
            ]
        );
    }

    /**
     * @param array<string, array<string, mixed>> $schedules
     *
     * @return array<string, array<string, mixed>>
     */
    public static function registerSchedule(
        array $schedules
    ): array {
        $schedules[self::SCHEDULE] = [
            'interval' =>
                5 * MINUTE_IN_SECONDS,

            'display' =>
                'Cada cinco minutos',
        ];

        return $schedules;
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

        $scheduled =
            wp_schedule_event(
                time() + MINUTE_IN_SECONDS,
                self::SCHEDULE,
                self::HOOK
            );

        if ($scheduled === false) {
            error_log(
                '[DSM Promocionar] No se pudo programar '
                . 'el cron de promociones agotadas.'
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
            $processor =
                new ExpirePromotionAssignments();

            $result =
                $processor->execute(
                    self::BATCH_LIMIT
                );

            if (
                $result['exhausted'] > 0
                || $result['failed'] > 0
            ) {
                error_log(
                    sprintf(
                        '[DSM Promocionar] Cron terminado. '
                        . 'Revisadas: %d; agotadas: %d; fallidas: %d.',
                        $result['checked'],
                        $result['exhausted'],
                        $result['failed']
                    )
                );
            }
        } catch (Throwable $exception) {
            error_log(
                sprintf(
                    '[DSM Promocionar] Error general en el cron '
                    . 'de promociones agotadas: %s',
                    $exception->getMessage()
                )
            );
        }
    }

    private function __construct()
    {
    }
}
