<?php

declare(strict_types=1);

namespace DSM\Facturacion\Verifactu;

use Throwable;

if (!defined('ABSPATH')) {
    exit;
}

final class VerifactuCronRunner
{
    public const HOOK =
        'dsm_facturacion_verifactu_queue';

    private const LOCK_OPTION =
        'dsm_facturacion_verifactu_queue_lock';

    private const LOCK_SECONDS =
        180;

    private const PROCESS_LIMIT =
        25;

    public static function register(): void
    {
        add_filter(
            'cron_schedules',
            [
                self::class,
                'addSchedule',
            ]
        );

        add_action(
            'init',
            [
                self::class,
                'ensureScheduled',
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
    public static function addSchedule(
        array $schedules
    ): array {
        $schedules[
            'dsm_every_minute'
        ] = [
            'interval' =>
                60,

            'display' =>
                'Cada minuto - DSM',
        ];

        return $schedules;
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

        wp_schedule_event(
            time() + 60,
            'dsm_every_minute',
            self::HOOK
        );
    }

    public static function run(): void
    {
        if (!self::acquireLock()) {
            return;
        }

        try {
            $processor =
                new VerifactuQueueProcessor();

            /*
             * La red únicamente queda permitida cuando
             * VERI*FACTU está expresamente habilitado.
             *
             * Aun así, SubmissionSender y HttpTransport
             * vuelven a validar certificado, entorno y
             * endpoint antes de cualquier POST.
             */
            $allowNetwork =
                VerifactuSettings::isEnabled();

            $result =
                $processor->process(
                    $allowNetwork,
                    self::PROCESS_LIMIT
                );

            do_action(
                'dsm_facturacion_verifactu_queue_processed',
                $result
            );
        } catch (Throwable $e) {
            do_action(
                'dsm_facturacion_verifactu_queue_error',
                $e
            );
        } finally {
            self::releaseLock();
        }
    }

    private static function acquireLock(): bool
    {
        global $wpdb;

        $now =
            time();

        $current =
            get_option(
                self::LOCK_OPTION,
                null
            );

        if (
            is_numeric($current)
            && (int) $current
                > $now - self::LOCK_SECONDS
        ) {
            return false;
        }

        /*
         * Intento atómico cuando la opción todavía
         * no existe.
         */
        if (
            add_option(
                self::LOCK_OPTION,
                $now,
                '',
                false
            )
        ) {
            return true;
        }

        /*
         * La opción existe pero puede estar caducada.
         */
        $updated =
            $wpdb->query(
                $wpdb->prepare(
                    "
                    UPDATE {$wpdb->options}
                    SET option_value = %s
                    WHERE option_name = %s
                      AND CAST(option_value AS UNSIGNED) <= %d
                    ",
                    (string) $now,
                    self::LOCK_OPTION,
                    $now - self::LOCK_SECONDS
                )
            );

        if ($updated === 1) {
            wp_cache_delete(
                self::LOCK_OPTION,
                'options'
            );

            return true;
        }

        return false;
    }

    private static function releaseLock(): void
    {
        delete_option(
            self::LOCK_OPTION
        );
    }

    private function __construct()
    {
    }
}
