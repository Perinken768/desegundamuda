<?php

declare(strict_types=1);

use DSM\Multitienda\Frontend\StoreReservationController;

if (!defined('ABSPATH')) {
    exit;
}

/*
 * Variables:
 *
 * $pendingReservations
 * $historyReservations
 *
 * $reservationProducts
 * $reservationVariants
 * $reservationBuyers
 *
 * $reservationStatus
 * $reservationError
 *
 * $reservationPerPage
 *
 * $pendingReservationCount
 * $pendingReservationPage
 * $pendingReservationTotalPages
 * $pendingReservationOffset
 *
 * $historyReservationCount
 * $historyReservationPage
 * $historyReservationTotalPages
 * $historyReservationOffset
 * $historyReservationFilter
 */

$statusLabels = [
    'active' =>
        'Activa',

    'released' =>
        'Liberada',

    'completed' =>
        'Completada',

    'cancelled' =>
        'Cancelada',

    'expired' =>
        'Caducada',
];

$statusClasses = [
    'active' =>
        'active',

    'released' =>
        'released',

    'completed' =>
        'completed',

    'cancelled' =>
        'cancelled',

    'expired' =>
        'expired',
];

$focusedReservationId =
    isset($_GET['reservation_id'])
        ? absint(
            wp_unslash(
                $_GET['reservation_id']
            )
        )
        : 0;


/*
 * ==========================================================
 * URL BASE
 * ==========================================================
 */

$baseReservationsUrl =
    add_query_arg(
        [
            'store_section' =>
                'reservations',
        ],
        home_url(
            '/mi-tienda/'
        )
    );


/*
 * ==========================================================
 * HELPERS
 * ==========================================================
 */

$resolveReservationData =
    static function (
        $reservation
    ) use (
        $reservationProducts,
        $reservationVariants,
        $reservationBuyers
    ): array {
        $productId =
            $reservation->getProductId();

        $variantId =
            $reservation->getVariantId();

        $buyerCustomerId =
            $reservation
                ->getBuyerCustomerId();

        $product =
            $reservationProducts[
                $productId
            ]
            ?? null;

        $variant =
            $reservationVariants[
                $variantId
            ]
            ?? null;

        $buyer =
            $buyerCustomerId !== null
                ? (
                    $reservationBuyers[
                        $buyerCustomerId
                    ]
                    ?? null
                )
                : null;


        $productName =
            $product !== null
                ? $product->getName()
                : (
                    'Producto #'
                    . $productId
                );


        $sku = '';

        if ($variant !== null) {
            $sku =
                trim(
                    (string)
                    $variant->getSku()
                );
        }

        $variantName =
            $sku !== ''
                ? $sku
                : (
                    'Variante #'
                    . $variantId
                );


        $buyerName = '';
        $buyerEmail = '';

        if (is_array($buyer)) {
            $buyerName =
                trim(
                    (string) (
                        $buyer[
                            'display_name'
                        ]
                        ?? ''
                    )
                );

            $buyerEmail =
                trim(
                    (string) (
                        $buyer['email']
                        ?? ''
                    )
                );
        }

        if ($buyerName === '') {
            $buyerName =
                $buyerEmail !== ''
                    ? $buyerEmail
                    : (
                        $buyerCustomerId !== null
                            ? (
                                'Cliente #'
                                . $buyerCustomerId
                            )
                            : 'Cliente'
                    );
        }


        $reservedAt =
            $reservation
                ->getReservedAt();

        $reservedAtText =
            get_date_from_gmt(
                $reservedAt->format(
                    'Y-m-d H:i:s'
                ),
                'd/m/Y H:i'
            );


        return [
            'product_name' =>
                $productName,

            'variant_name' =>
                $variantName,

            'buyer_name' =>
                $buyerName,

            'buyer_email' =>
                $buyerEmail,

            'reserved_at' =>
                $reservedAtText,
        ];
    };


$buildPageUrl =
    static function (
        string $pageArgument,
        int $page,
        string $historyFilter = ''
    ) use (
        $baseReservationsUrl
    ): string {
        $arguments = [
            $pageArgument =>
                $page,
        ];

        if ($historyFilter !== '') {
            $arguments[
                'reservation_history_status'
            ] =
                $historyFilter;
        }

        return add_query_arg(
            $arguments,
            $baseReservationsUrl
        );
    };

?>

<section class="dsm-store-reservations">


    <!-- =====================================================
         CABECERA
         ===================================================== -->

    <div class="dsm-store-reservations__header">

        <div>

            <h2>
                Reservas
            </h2>

            <p>
                Gestiona las reservas realizadas
                por los clientes de tu tienda.
            </p>

        </div>

    </div>


    <!-- =====================================================
         AVISOS
         ===================================================== -->

    <?php if (
        $reservationStatus === 'completed'
    ) : ?>

        <div
            class="
                dsm-account-notice
                dsm-account-notice--success
            "
        >
            La venta se completó correctamente.
        </div>

    <?php elseif (
        $reservationStatus === 'released'
    ) : ?>

        <div
            class="
                dsm-account-notice
                dsm-account-notice--success
            "
        >
            La reserva se liberó correctamente.
        </div>

    <?php elseif (
        $reservationStatus === 'error'
        && $reservationError !== ''
    ) : ?>

        <div
            class="
                dsm-account-notice
                dsm-account-notice--error
            "
        >
            <?php
            echo esc_html(
                $reservationError
            );
            ?>
        </div>

    <?php endif; ?>


    <!-- =====================================================
         PENDIENTES
         ===================================================== -->

    <article
        id="dsm-reservations-pending"
        class="
            dsm-card
            dsm-reservation-panel
        "
    >

        <header class="dsm-reservation-panel__header">

            <div>

                <h3>
                    Pendientes de gestionar
                </h3>

                <p>
                    Reservas activas que necesitan
                    una acción por parte de la tienda.
                </p>

            </div>


            <span class="dsm-reservation-panel__counter">

                <?php
                echo esc_html(
                    (string)
                    $pendingReservationCount
                );
                ?>

                <?php
                echo $pendingReservationCount === 1
                    ? 'reserva'
                    : 'reservas';
                ?>

            </span>

        </header>


        <?php if (
            $pendingReservations === []
        ) : ?>

            <div class="dsm-reservation-empty">

                <strong>
                    No tienes reservas pendientes.
                </strong>

                <span>
                    Las nuevas reservas aparecerán aquí
                    para poder completarlas o liberarlas.
                </span>

            </div>

        <?php else : ?>

            <div class="dsm-reservation-table-scroll">

                <table class="dsm-reservation-table">

                    <thead>

                        <tr>
                            <th>Reserva</th>
                            <th>Producto</th>
                            <th>Variante</th>
                            <th>Cliente</th>
                            <th class="is-centered">
                                Cantidad
                            </th>
                            <th>Fecha</th>
                            <th>Estado</th>
                            <th>Acciones</th>
                        </tr>

                    </thead>


                    <tbody>

                        <?php foreach (
                            $pendingReservations
                            as $reservation
                        ) : ?>

                            <?php

                            $reservationId =
                                $reservation
                                    ->getId();

                            $data =
                                $resolveReservationData(
                                    $reservation
                                );

                            $status =
                                $reservation
                                    ->getStatus();

                            $focused =
                                $focusedReservationId > 0
                                && $reservationId
                                    === $focusedReservationId;

                            ?>

                            <tr
                                class="<?php
                                echo esc_attr(
                                    $focused
                                        ? 'is-focused'
                                        : ''
                                );
                                ?>"
                            >

                                <td
                                    class="
                                        dsm-reservation-table__id
                                    "
                                >
                                    <strong>
                                        #<?php
                                        echo esc_html(
                                            (string)
                                            $reservationId
                                        );
                                        ?>
                                    </strong>
                                </td>


                                <td
                                    class="
                                        dsm-reservation-table__product
                                    "
                                >
                                    <?php
                                    echo esc_html(
                                        $data[
                                            'product_name'
                                        ]
                                    );
                                    ?>
                                </td>


                                <td
                                    class="
                                        dsm-reservation-table__variant
                                    "
                                >
                                    <?php
                                    echo esc_html(
                                        $data[
                                            'variant_name'
                                        ]
                                    );
                                    ?>
                                </td>


                                <td
                                    class="
                                        dsm-reservation-table__customer
                                    "
                                >

                                    <strong>
                                        <?php
                                        echo esc_html(
                                            $data[
                                                'buyer_name'
                                            ]
                                        );
                                        ?>
                                    </strong>

                                    <?php if (
                                        $data[
                                            'buyer_email'
                                        ] !== ''
                                        && $data[
                                            'buyer_email'
                                        ] !== $data[
                                            'buyer_name'
                                        ]
                                    ) : ?>

                                        <small>
                                            <?php
                                            echo esc_html(
                                                $data[
                                                    'buyer_email'
                                                ]
                                            );
                                            ?>
                                        </small>

                                    <?php endif; ?>

                                </td>


                                <td
                                    class="
                                        is-centered
                                        dsm-reservation-table__quantity
                                    "
                                >
                                    <?php
                                    echo esc_html(
                                        (string)
                                        $reservation
                                            ->getQuantity()
                                    );
                                    ?>
                                </td>


                                <td
                                    class="
                                        dsm-reservation-table__date
                                    "
                                >
                                    <?php
                                    echo esc_html(
                                        $data[
                                            'reserved_at'
                                        ]
                                    );
                                    ?>
                                </td>


                                <td>

                                    <span
                                        class="<?php
                                        echo esc_attr(
                                            'dsm-reservation-status '
                                            . 'dsm-reservation-status--'
                                            . (
                                                $statusClasses[
                                                    $status
                                                ]
                                                ?? 'inactive'
                                            )
                                        );
                                        ?>"
                                    >
                                        <?php
                                        echo esc_html(
                                            $statusLabels[
                                                $status
                                            ]
                                            ?? ucfirst(
                                                $status
                                            )
                                        );
                                        ?>
                                    </span>

                                </td>


                                <td>

                                    <div
                                        class="
                                            dsm-reservation-actions
                                        "
                                    >

                                        <?php if (
                                            $reservation
                                                ->canBeCompleted()
                                        ) : ?>

                                            <form
                                                method="post"
                                                action="<?php
                                                echo esc_url(
                                                    admin_url(
                                                        'admin-post.php'
                                                    )
                                                );
                                                ?>"
                                                onsubmit="
                                                    return confirm(
                                                        '¿Confirmar que esta reserva se ha vendido?'
                                                    );
                                                "
                                            >

                                                <input
                                                    type="hidden"
                                                    name="action"
                                                    value="<?php
                                                    echo esc_attr(
                                                        StoreReservationController::
                                                            COMPLETE_ACTION
                                                    );
                                                    ?>"
                                                >

                                                <input
                                                    type="hidden"
                                                    name="reservation_id"
                                                    value="<?php
                                                    echo esc_attr(
                                                        (string)
                                                        $reservationId
                                                    );
                                                    ?>"
                                                >

                                                <?php
                                                wp_nonce_field(
                                                    StoreReservationController::
                                                        getNonceAction(
                                                            StoreReservationController::
                                                                COMPLETE_ACTION,
                                                            $reservationId
                                                        ),
                                                    StoreReservationController::
                                                        NONCE_FIELD
                                                );
                                                ?>

                                                <button
                                                    type="submit"
                                                    class="
                                                        dsm-reservation-action
                                                        dsm-reservation-action--complete
                                                    "
                                                >
                                                    Completar venta
                                                </button>

                                            </form>

                                        <?php endif; ?>


                                        <?php if (
                                            $reservation
                                                ->canBeReleased()
                                        ) : ?>

                                            <form
                                                method="post"
                                                action="<?php
                                                echo esc_url(
                                                    admin_url(
                                                        'admin-post.php'
                                                    )
                                                );
                                                ?>"
                                                onsubmit="
                                                    return confirm(
                                                        '¿Liberar esta reserva y devolver las unidades al stock disponible?'
                                                    );
                                                "
                                            >

                                                <input
                                                    type="hidden"
                                                    name="action"
                                                    value="<?php
                                                    echo esc_attr(
                                                        StoreReservationController::
                                                            RELEASE_ACTION
                                                    );
                                                    ?>"
                                                >

                                                <input
                                                    type="hidden"
                                                    name="reservation_id"
                                                    value="<?php
                                                    echo esc_attr(
                                                        (string)
                                                        $reservationId
                                                    );
                                                    ?>"
                                                >

                                                <?php
                                                wp_nonce_field(
                                                    StoreReservationController::
                                                        getNonceAction(
                                                            StoreReservationController::
                                                                RELEASE_ACTION,
                                                            $reservationId
                                                        ),
                                                    StoreReservationController::
                                                        NONCE_FIELD
                                                );
                                                ?>

                                                <button
                                                    type="submit"
                                                    class="
                                                        dsm-reservation-action
                                                        dsm-reservation-action--release
                                                    "
                                                >
                                                    Liberar reserva
                                                </button>

                                            </form>

                                        <?php endif; ?>

                                    </div>

                                </td>

                            </tr>

                        <?php endforeach; ?>

                    </tbody>

                </table>

            </div>


            <?php if (
                $pendingReservationTotalPages > 1
            ) : ?>

                <nav
                    class="dsm-reservation-pagination"
                    aria-label="
                        Páginas de reservas pendientes
                    "
                >

                    <?php

                    $pendingStart =
                        max(
                            1,
                            $pendingReservationPage
                            - 2
                        );

                    $pendingEnd =
                        min(
                            $pendingReservationTotalPages,
                            $pendingReservationPage
                            + 2
                        );

                    ?>


                    <?php if (
                        $pendingReservationPage > 1
                    ) : ?>

                        <a
                            href="<?php
                            echo esc_url(
                                $buildPageUrl(
                                    'reservation_pending_page',
                                    $pendingReservationPage
                                    - 1,
                                    $historyReservationFilter
                                )
                                . '#dsm-reservations-pending'
                            );
                            ?>"
                        >
                            ‹
                        </a>

                    <?php endif; ?>


                    <?php for (
                        $page = $pendingStart;
                        $page <= $pendingEnd;
                        $page++
                    ) : ?>

                        <?php if (
                            $page
                            === $pendingReservationPage
                        ) : ?>

                            <span
                                class="is-current"
                                aria-current="page"
                            >
                                <?php
                                echo esc_html(
                                    (string)
                                    $page
                                );
                                ?>
                            </span>

                        <?php else : ?>

                            <a
                                href="<?php
                                echo esc_url(
                                    $buildPageUrl(
                                        'reservation_pending_page',
                                        $page,
                                        $historyReservationFilter
                                    )
                                    . '#dsm-reservations-pending'
                                );
                                ?>"
                            >
                                <?php
                                echo esc_html(
                                    (string)
                                    $page
                                );
                                ?>
                            </a>

                        <?php endif; ?>

                    <?php endfor; ?>


                    <?php if (
                        $pendingReservationPage
                        < $pendingReservationTotalPages
                    ) : ?>

                        <a
                            href="<?php
                            echo esc_url(
                                $buildPageUrl(
                                    'reservation_pending_page',
                                    $pendingReservationPage
                                    + 1,
                                    $historyReservationFilter
                                )
                                . '#dsm-reservations-pending'
                            );
                            ?>"
                        >
                            ›
                        </a>

                    <?php endif; ?>

                </nav>

            <?php endif; ?>

        <?php endif; ?>

    </article>


    <!-- =====================================================
         HISTORICO
         ===================================================== -->

    <article
        id="dsm-reservations-history"
        class="
            dsm-card
            dsm-reservation-panel
        "
    >

        <header class="dsm-reservation-panel__header">

            <div>

                <h3>
                    Histórico de reservas
                </h3>

                <p>
                    Consulta las ventas completadas
                    y las reservas liberadas.
                </p>

            </div>


            <span class="dsm-reservation-panel__counter">

                <?php
                echo esc_html(
                    (string)
                    $historyReservationCount
                );
                ?>

                <?php
                echo $historyReservationCount === 1
                    ? 'registro'
                    : 'registros';
                ?>

            </span>

        </header>


        <!-- FILTROS HISTORICO -->

        <nav
            class="dsm-reservation-history-filter"
            aria-label="
                Filtrar histórico de reservas
            "
        >

            <?php

            $historyFilters = [
                '' =>
                    'Todas',

                'completed' =>
                    'Completadas',

                'released' =>
                    'Liberadas',
            ];

            ?>

            <?php foreach (
                $historyFilters
                as $filterValue => $filterLabel
            ) : ?>

                <?php

                $filterUrl =
                    add_query_arg(
                        array_filter(
                            [
                                'store_section' =>
                                    'reservations',

                                'reservation_history_status' =>
                                    $filterValue,
                            ],
                            static fn (
                                mixed $value
                            ): bool =>
                                $value !== ''
                        ),
                        home_url(
                            '/mi-tienda/'
                        )
                    )
                    . '#dsm-reservations-history';

                $filterActive =
                    $historyReservationFilter
                    === $filterValue;

                ?>

                <a
                    href="<?php
                    echo esc_url(
                        $filterUrl
                    );
                    ?>"
                    class="<?php
                    echo esc_attr(
                        'dsm-reservation-history-filter__item'
                        . (
                            $filterActive
                                ? ' is-active'
                                : ''
                        )
                    );
                    ?>"
                    <?php if (
                        $filterActive
                    ) : ?>
                        aria-current="page"
                    <?php endif; ?>
                >
                    <?php
                    echo esc_html(
                        $filterLabel
                    );
                    ?>
                </a>

            <?php endforeach; ?>

        </nav>


        <?php if (
            $historyReservations === []
        ) : ?>

            <div class="dsm-reservation-empty">

                <strong>
                    No hay reservas en este histórico.
                </strong>

                <span>
                    Prueba con otro filtro de estado.
                </span>

            </div>

        <?php else : ?>

            <div class="dsm-reservation-table-scroll">

                <table class="dsm-reservation-table">

                    <thead>

                        <tr>
                            <th>Reserva</th>
                            <th>Producto</th>
                            <th>Variante</th>
                            <th>Cliente</th>
                            <th class="is-centered">
                                Cantidad
                            </th>
                            <th>Fecha</th>
                            <th>Estado</th>
                        </tr>

                    </thead>


                    <tbody>

                        <?php foreach (
                            $historyReservations
                            as $reservation
                        ) : ?>

                            <?php

                            $reservationId =
                                $reservation
                                    ->getId();

                            $data =
                                $resolveReservationData(
                                    $reservation
                                );

                            $status =
                                $reservation
                                    ->getStatus();

                            $focused =
                                $focusedReservationId > 0
                                && $reservationId
                                    === $focusedReservationId;

                            ?>

                            <tr
                                class="<?php
                                echo esc_attr(
                                    $focused
                                        ? 'is-focused'
                                        : ''
                                );
                                ?>"
                            >

                                <td
                                    class="
                                        dsm-reservation-table__id
                                    "
                                >
                                    <strong>
                                        #<?php
                                        echo esc_html(
                                            (string)
                                            $reservationId
                                        );
                                        ?>
                                    </strong>
                                </td>


                                <td
                                    class="
                                        dsm-reservation-table__product
                                    "
                                >
                                    <?php
                                    echo esc_html(
                                        $data[
                                            'product_name'
                                        ]
                                    );
                                    ?>
                                </td>


                                <td
                                    class="
                                        dsm-reservation-table__variant
                                    "
                                >
                                    <?php
                                    echo esc_html(
                                        $data[
                                            'variant_name'
                                        ]
                                    );
                                    ?>
                                </td>


                                <td
                                    class="
                                        dsm-reservation-table__customer
                                    "
                                >

                                    <strong>
                                        <?php
                                        echo esc_html(
                                            $data[
                                                'buyer_name'
                                            ]
                                        );
                                        ?>
                                    </strong>

                                    <?php if (
                                        $data[
                                            'buyer_email'
                                        ] !== ''
                                        && $data[
                                            'buyer_email'
                                        ] !== $data[
                                            'buyer_name'
                                        ]
                                    ) : ?>

                                        <small>
                                            <?php
                                            echo esc_html(
                                                $data[
                                                    'buyer_email'
                                                ]
                                            );
                                            ?>
                                        </small>

                                    <?php endif; ?>

                                </td>


                                <td
                                    class="
                                        is-centered
                                        dsm-reservation-table__quantity
                                    "
                                >
                                    <?php
                                    echo esc_html(
                                        (string)
                                        $reservation
                                            ->getQuantity()
                                    );
                                    ?>
                                </td>


                                <td
                                    class="
                                        dsm-reservation-table__date
                                    "
                                >
                                    <?php
                                    echo esc_html(
                                        $data[
                                            'reserved_at'
                                        ]
                                    );
                                    ?>
                                </td>


                                <td>

                                    <span
                                        class="<?php
                                        echo esc_attr(
                                            'dsm-reservation-status '
                                            . 'dsm-reservation-status--'
                                            . (
                                                $statusClasses[
                                                    $status
                                                ]
                                                ?? 'inactive'
                                            )
                                        );
                                        ?>"
                                    >
                                        <?php
                                        echo esc_html(
                                            $statusLabels[
                                                $status
                                            ]
                                            ?? ucfirst(
                                                $status
                                            )
                                        );
                                        ?>
                                    </span>

                                </td>

                            </tr>

                        <?php endforeach; ?>

                    </tbody>

                </table>

            </div>


            <?php if (
                $historyReservationTotalPages > 1
            ) : ?>

                <nav
                    class="dsm-reservation-pagination"
                    aria-label="
                        Páginas del histórico de reservas
                    "
                >

                    <?php

                    $historyStart =
                        max(
                            1,
                            $historyReservationPage
                            - 2
                        );

                    $historyEnd =
                        min(
                            $historyReservationTotalPages,
                            $historyReservationPage
                            + 2
                        );

                    ?>


                    <?php if (
                        $historyReservationPage > 1
                    ) : ?>

                        <a
                            href="<?php
                            echo esc_url(
                                $buildPageUrl(
                                    'reservation_history_page',
                                    $historyReservationPage
                                    - 1,
                                    $historyReservationFilter
                                )
                                . '#dsm-reservations-history'
                            );
                            ?>"
                        >
                            ‹
                        </a>

                    <?php endif; ?>


                    <?php for (
                        $page = $historyStart;
                        $page <= $historyEnd;
                        $page++
                    ) : ?>

                        <?php if (
                            $page
                            === $historyReservationPage
                        ) : ?>

                            <span
                                class="is-current"
                                aria-current="page"
                            >
                                <?php
                                echo esc_html(
                                    (string)
                                    $page
                                );
                                ?>
                            </span>

                        <?php else : ?>

                            <a
                                href="<?php
                                echo esc_url(
                                    $buildPageUrl(
                                        'reservation_history_page',
                                        $page,
                                        $historyReservationFilter
                                    )
                                    . '#dsm-reservations-history'
                                );
                                ?>"
                            >
                                <?php
                                echo esc_html(
                                    (string)
                                    $page
                                );
                                ?>
                            </a>

                        <?php endif; ?>

                    <?php endfor; ?>


                    <?php if (
                        $historyReservationPage
                        < $historyReservationTotalPages
                    ) : ?>

                        <a
                            href="<?php
                            echo esc_url(
                                $buildPageUrl(
                                    'reservation_history_page',
                                    $historyReservationPage
                                    + 1,
                                    $historyReservationFilter
                                )
                                . '#dsm-reservations-history'
                            );
                            ?>"
                        >
                            ›
                        </a>

                    <?php endif; ?>

                </nav>

            <?php endif; ?>

        <?php endif; ?>

    </article>

</section>
