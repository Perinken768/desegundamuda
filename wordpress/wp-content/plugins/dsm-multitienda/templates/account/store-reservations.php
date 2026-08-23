<?php

declare(strict_types=1);

use DSM\Multitienda\Frontend\StoreReservationController;

if (!defined('ABSPATH')) {
    exit;
}

/*
 * Variables proporcionadas por MyStoreShortcode:
 *
 * $reservations
 * $reservationProducts
 * $reservationVariants
 * $reservationBuyers
 * $reservationStatus
 * $reservationError
 */

$statusLabels = [
    'active'    => 'Activa',
    'released'  => 'Liberada',
    'completed' => 'Completada',
    'cancelled' => 'Cancelada',
    'expired'   => 'Caducada',
];

$focusedReservationId =
    isset($_GET['reservation_id'])
        ? absint(
            wp_unslash(
                $_GET['reservation_id']
            )
        )
        : 0;

$focusedReservationExists =
    false;

if ($focusedReservationId > 0) {
    foreach ($reservations as $reservation) {
        if (
            $reservation->getId()
            === $focusedReservationId
        ) {
            $focusedReservationExists =
                true;

            break;
        }
    }
}

/*
 * Si hemos llegado desde un correo de reserva,
 * colocamos esa reserva la primera.
 *
 * No ocultamos las demás.
 */
if ($focusedReservationExists) {
    usort(
        $reservations,
        static function (
            $left,
            $right
        ) use (
            $focusedReservationId
        ): int {
            $leftFocused =
                $left->getId()
                === $focusedReservationId;

            $rightFocused =
                $right->getId()
                === $focusedReservationId;

            if ($leftFocused === $rightFocused) {
                return 0;
            }

            return $leftFocused
                ? -1
                : 1;
        }
    );
}

?>

<section class="dsm-store-reservations">

    <article class="dsm-card">

        <h2>Reservas</h2>

        <p>
            Gestiona las reservas realizadas
            por los clientes de tu tienda.
        </p>

        <?php if (
            $focusedReservationExists
        ) : ?>

            <div
                class="
                    dsm-account-notice
                    dsm-account-notice--success
                "
            >
                <strong>
                    Gestionando reserva
                    #<?php
                    echo esc_html(
                        (string)
                        $focusedReservationId
                    );
                    ?>
                </strong>

                <br>

                Has accedido directamente a esta reserva.
                Puedes completar la venta o liberarla
                desde sus acciones.

                <p style="margin-bottom:0;">
                    <a
                        class="button"
                        href="<?php
                        echo esc_url(
                            add_query_arg(
                                [
                                    'store_section' =>
                                        'reservations',
                                ],
                                home_url(
                                    '/mi-tienda/'
                                )
                            )
                        );
                        ?>"
                    >
                        Ver todas las reservas
                    </a>
                </p>
            </div>

        <?php endif; ?>

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

        <?php if ($reservations === []) : ?>

            <p>
                Todavía no hay reservas
                para esta tienda.
            </p>

        <?php else : ?>

            <div class="dsm-admin-table-scroll">

                <table class="widefat striped">

                    <thead>
                        <tr>
                            <th>Reserva</th>
                            <th>Producto</th>
                            <th>Variante</th>
                            <th>Cliente</th>
                            <th>Cantidad</th>
                            <th>Fecha</th>
                            <th>Estado</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>

                    <tbody>

                        <?php foreach (
                            $reservations
                            as $reservation
                        ) : ?>

                            <?php
                            $reservationId =
                                $reservation->getId();

                            $productId =
                                $reservation
                                    ->getProductId();

                            $variantId =
                                $reservation
                                    ->getVariantId();

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

                            $status =
                                $reservation
                                    ->getStatus();

                            $buyerName =
                                '';

                            $buyerEmail =
                                '';

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
                                            $buyerCustomerId
                                            !== null
                                                ? (
                                                    'Cliente #'
                                                    . $buyerCustomerId
                                                )
                                                : 'Cliente'
                                        );
                            }
                            ?>

                            <tr
                                <?php if (
                                    $focusedReservationExists
                                    && $reservationId
                                        === $focusedReservationId
                                ) : ?>
                                    style="
                                        outline:3px solid currentColor;
                                        outline-offset:-3px;
                                    "
                                <?php endif; ?>
                            >

                                <td>
                                    <strong>
                                        #<?php
                                        echo esc_html(
                                            (string)
                                            $reservationId
                                        );
                                        ?>
                                    </strong>
                                </td>

                                <td>
                                    <?php
                                    echo esc_html(
                                        $product !== null
                                            ? $product->getName()
                                            : (
                                                'Producto #'
                                                . $productId
                                            )
                                    );
                                    ?>
                                </td>

                                <td>
                                    <?php
                                    $sku =
                                        $variant !== null
                                            ? trim(
                                                (string)
                                                $variant->getSku()
                                            )
                                            : '';

                                    echo esc_html(
                                        $sku !== ''
                                            ? $sku
                                            : (
                                                'Variante #'
                                                . $variantId
                                            )
                                    );
                                    ?>
                                </td>

                                <td>

                                    <strong>
                                        <?php
                                        echo esc_html(
                                            $buyerName
                                        );
                                        ?>
                                    </strong>

                                    <?php if (
                                        $buyerEmail !== ''
                                        && $buyerEmail
                                            !== $buyerName
                                    ) : ?>

                                        <br>

                                        <small>
                                            <?php
                                            echo esc_html(
                                                $buyerEmail
                                            );
                                            ?>
                                        </small>

                                    <?php endif; ?>

                                </td>

                                <td>
                                    <?php
                                    echo esc_html(
                                        (string)
                                        $reservation
                                            ->getQuantity()
                                    );
                                    ?>
                                </td>

                                <td>
                                    <?php
                                    $reservedAt =
                                        $reservation
                                            ->getReservedAt();

                                    echo esc_html(
                                        get_date_from_gmt(
                                            $reservedAt->format(
                                                'Y-m-d H:i:s'
                                            ),
                                            'd/m/Y H:i'
                                        )
                                    );
                                    ?>
                                </td>

                                <td>
                                    <?php
                                    echo esc_html(
                                        $statusLabels[$status]
                                        ?? ucfirst($status)
                                    );
                                    ?>
                                </td>

                                <td>

                                    <?php if (
                                        $reservation
                                            ->canBeCompleted()
                                        || $reservation
                                            ->canBeReleased()
                                    ) : ?>

                                        <div
                                            class="
                                                dsm-admin-actions
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
                                                    onsubmit="return confirm(
                                                        '¿Confirmar que esta reserva se ha vendido?'
                                                    );"
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
                                                            button
                                                            button-primary
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
                                                    onsubmit="return confirm(
                                                        '¿Liberar esta reserva y devolver las unidades al stock disponible?'
                                                    );"
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
                                                        class="button"
                                                    >
                                                        Liberar reserva
                                                    </button>

                                                </form>

                                            <?php endif; ?>

                                        </div>

                                    <?php else : ?>

                                        —

                                    <?php endif; ?>

                                </td>

                            </tr>

                        <?php endforeach; ?>

                    </tbody>

                </table>

            </div>

        <?php endif; ?>

    </article>

</section>
