<?php

declare(strict_types=1);

namespace DSM\Directos\Frontend;

use DSM\Directos\Application\ReserveDirectItem;
use RuntimeException;
use Throwable;

if (!defined('ABSPATH')) {
    exit;
}

final class DirectReservationController
{
    public const ACTION =
        'dsm_directos_reserve_item';

    public const NONCE_ACTION =
        'dsm_directos_reserve_item';

    public const NONCE_NAME =
        'dsm_directos_reserve_nonce';

    public static function register(): void
    {
        $controller =
            new self();

        add_action(
            'wp_ajax_'
            . self::ACTION,
            [
                $controller,
                'handle',
            ]
        );

        add_action(
            'wp_ajax_nopriv_'
            . self::ACTION,
            [
                $controller,
                'handle',
            ]
        );
    }

    public function handle(): never
    {
        try {
            $nonce =
                isset(
                    $_POST[
                        self::NONCE_NAME
                    ]
                )
                && is_scalar(
                    $_POST[
                        self::NONCE_NAME
                    ]
                )
                    ? (string) wp_unslash(
                        $_POST[
                            self::NONCE_NAME
                        ]
                    )
                    : '';

            if (
                $nonce === ''
                || !wp_verify_nonce(
                    $nonce,
                    self::NONCE_ACTION
                )
            ) {
                throw new RuntimeException(
                    'La solicitud de reserva ha caducado. Recarga el directo e inténtalo de nuevo.'
                );
            }

            $liveItemId =
                isset(
                    $_POST['live_item_id']
                )
                    ? max(
                        0,
                        absint(
                            wp_unslash(
                                (string) $_POST[
                                    'live_item_id'
                                ]
                            )
                        )
                    )
                    : 0;

            if ($liveItemId <= 0) {
                throw new RuntimeException(
                    'No se pudo identificar la prenda.'
                );
            }

            $buyerContext =
                apply_filters(
                    'dsm_current_customer_context',
                    null
                );

            if (!is_array($buyerContext)) {
                wp_send_json_error(
                    [
                        'message' =>
                            'Debes iniciar sesión para reservar.',

                        'login_required' =>
                            true,

                        'login_url' =>
                            add_query_arg(
                                'redirect_to',
                                rawurlencode(
                                    wp_get_referer()
                                    ?: home_url('/directos/')
                                ),
                                home_url(
                                    '/iniciar-sesion/'
                                )
                            ),
                    ],
                    401
                );
            }

            $result =
                (
                    new ReserveDirectItem()
                )->execute(
                    $liveItemId,
                    $buyerContext
                );

            wp_send_json_success(
                [
                    'reservation_id' =>
                        $result[
                            'reservation_id'
                        ],

                    'source_type' =>
                        $result[
                            'source_type'
                        ],

                    'remaining' =>
                        $result[
                            'remaining'
                        ],

                    'remove_item' =>
                        $result[
                            'remove_item'
                        ],

                    'message' =>
                        'Reserva realizada correctamente.',
                ]
            );

        } catch (Throwable $exception) {
            wp_send_json_error(
                [
                    'message' =>
                        $exception
                            ->getMessage(),
                ],
                400
            );
        }
    }

    private function __construct()
    {
    }
}
