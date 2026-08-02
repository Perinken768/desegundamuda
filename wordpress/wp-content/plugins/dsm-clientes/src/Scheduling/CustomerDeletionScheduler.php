<?php

declare(strict_types=1);

namespace DSM\Clientes\Scheduling;

use DSM\Clientes\Application\CustomerDeletionService;
use DSM\Clientes\Authentication\CustomerSessionRepository;
use DSM\Clientes\Customer\CustomerRepository;
use DSM\Clientes\Deletion\CustomerDeletionRequestRepository;

if (!defined('ABSPATH')) {
    exit;
}

final class CustomerDeletionScheduler
{
    public const EVENT =
        'dsm_clientes_process_deletions';

    public static function register(): void
    {
        add_action(
            'init',
            [self::class, 'ensureScheduled']
        );

        add_action(
            self::EVENT,
            [self::class, 'process']
        );
    }

    public static function activate(): void
    {
        self::ensureScheduled();
    }

    public static function deactivate(): void
    {
        wp_clear_scheduled_hook(
            self::EVENT
        );
    }

    public static function ensureScheduled(): void
    {
        if (wp_next_scheduled(self::EVENT) !== false) {
            return;
        }

        wp_schedule_event(
            time() + HOUR_IN_SECONDS,
            'daily',
            self::EVENT
        );
    }

    public static function process(): void
    {
        $service = new CustomerDeletionService(
            new CustomerDeletionRequestRepository(),
            new CustomerRepository(),
            new CustomerSessionRepository()
        );

        $service->executeDueDeletions();
    }

    private function __construct()
    {
    }
}