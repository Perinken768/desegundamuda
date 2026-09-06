<?php

declare(strict_types=1);

namespace DSM\Directos\Application;

use DSM\Directos\Direct\DirectRepository;
use RuntimeException;

if (!defined('ABSPATH')) {
    exit;
}

final class DirectLifecycleService
{
    public function __construct(
        private readonly DirectAccessService $accessService =
            new DirectAccessService(),

        private readonly DirectRepository $repository =
            new DirectRepository()
    ) {
    }

    public function open(
        int $customerId
    ): void {
        if (
            !$this->accessService
                ->hasAccess(
                    $customerId
                )
        ) {
            throw new RuntimeException(
                'Tu suscripción no incluye acceso a DSM Directos.'
            );
        }

        $direct =
            $this->repository
                ->findByCustomerId(
                    $customerId
                );

        if ($direct === null) {
            throw new RuntimeException(
                'Primero debes configurar Mi directo.'
            );
        }

        $directId =
            max(
                0,
                (int) (
                    $direct['id']
                    ?? 0
                )
            );

        if ($directId <= 0) {
            throw new RuntimeException(
                'No se pudo identificar Mi directo.'
            );
        }

        global $wpdb;

        $table =
            $wpdb->prefix
            . 'dsm_live_streams';

        $now =
            current_time(
                'mysql',
                true
            );

        $updated =
            $wpdb->update(
                $table,
                [
                    'status' =>
                        'live',

                    'starts_at' =>
                        $now,

                    'ended_at' =>
                        null,

                    'updated_at' =>
                        $now,
                ],
                [
                    'id' =>
                        $directId,

                    'customer_id' =>
                        $customerId,
                ],
                [
                    '%s',
                    '%s',
                    '%s',
                    '%s',
                ],
                [
                    '%d',
                    '%d',
                ]
            );

        if ($updated === false) {
            throw new RuntimeException(
                'No se pudo abrir el directo.'
            );
        }
    }

    public function close(
        int $customerId
    ): void {
        if (
            !$this->accessService
                ->hasAccess(
                    $customerId
                )
        ) {
            throw new RuntimeException(
                'Tu suscripción no incluye acceso a DSM Directos.'
            );
        }

        $direct =
            $this->repository
                ->findByCustomerId(
                    $customerId
                );

        if ($direct === null) {
            throw new RuntimeException(
                'No existe Mi directo.'
            );
        }

        $directId =
            max(
                0,
                (int) (
                    $direct['id']
                    ?? 0
                )
            );

        global $wpdb;

        $table =
            $wpdb->prefix
            . 'dsm_live_streams';

        $now =
            current_time(
                'mysql',
                true
            );

        $updated =
            $wpdb->update(
                $table,
                [
                    'status' =>
                        'closed',

                    'ended_at' =>
                        $now,

                    'updated_at' =>
                        $now,
                ],
                [
                    'id' =>
                        $directId,

                    'customer_id' =>
                        $customerId,
                ],
                [
                    '%s',
                    '%s',
                    '%s',
                ],
                [
                    '%d',
                    '%d',
                ]
            );

        if ($updated === false) {
            throw new RuntimeException(
                'No se pudo cerrar el directo.'
            );
        }
    }
}
