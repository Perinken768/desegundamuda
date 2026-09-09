<?php

declare(strict_types=1);

namespace DSM\Ofertas\Application;

use DSM\Ofertas\Offer\OfferCheckoutIntentRepository;
use Throwable;

if (!defined('ABSPATH')) {
    exit;
}

final class ExpireCheckoutIntents
{
    public const HOOK =
        'dsm_ofertas_expire_checkout_intents';

    public static function register(): void
    {
        add_action(
            self::HOOK,
            [
                self::class,
                'run',
            ]
        );

        /*
         * Como el plugin ya puede estar activo cuando
         * se instala esta versión, garantizamos que el
         * cron exista sin requerir desactivar/activar.
         */
        add_action(
            'init',
            [
                self::class,
                'ensureScheduled',
            ]
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

        wp_schedule_event(
            time() + HOUR_IN_SECONDS,
            'hourly',
            self::HOOK
        );
    }

    public static function run(): void
    {
        try {
            (
                new OfferCheckoutIntentRepository()
            )->expirePending();
        } catch (Throwable $exception) {
            error_log(
                '[DSM Ofertas] Error caducando intents: '
                . $exception->getMessage()
            );
        }
    }

    public static function deactivate(): void
    {
        wp_clear_scheduled_hook(
            self::HOOK
        );
    }

    private function __construct()
    {
    }
}
