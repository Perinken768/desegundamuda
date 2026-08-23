<?php

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

/*
 * $stockMovements
 * $movementProducts
 * $movementVariants
 */

$typeLabels = [
    'initial' =>
        'Stock inicial',

    'replenishment' =>
        'Reposición',

    'adjustment' =>
        'Ajuste',

    'reservation' =>
        'Reserva',

    'reservation_release' =>
        'Liberación',

    'sale' =>
        'Venta',

    'expiration' =>
        'Caducidad',
];

?>

<section class="dsm-store-movements">

    <article class="dsm-card">

        <h2>Movimientos de stock</h2>

        <p>
            Histórico de cambios de existencias
            y unidades reservadas de tu tienda.
        </p>

        <?php if ($stockMovements === []) : ?>

            <p>
                Todavía no hay movimientos
                de inventario.
            </p>

        <?php else : ?>

            <div class="dsm-admin-table-scroll">

                <table class="widefat striped">

                    <thead>
                        <tr>
                            <th>Fecha</th>
                            <th>Producto</th>
                            <th>Variante</th>
                            <th>Tipo</th>
                            <th>Δ físico</th>
                            <th>Δ reservado</th>
                            <th>Físico</th>
                            <th>Reservado</th>
                            <th>Disponible</th>
                            <th>Referencia</th>
                            <th>Notas</th>
                        </tr>
                    </thead>

                    <tbody>

                        <?php foreach (
                            $stockMovements
                            as $movement
                        ) : ?>

                            <?php
                            $product =
                                $movementProducts[
                                    $movement
                                        ->getProductId()
                                ]
                                ?? null;

                            $variant =
                                $movementVariants[
                                    $movement
                                        ->getVariantId()
                                ]
                                ?? null;

                            $createdAt =
                                $movement
                                    ->getCreatedAt();

                            $movementType =
                                $movement
                                    ->getMovementType();
                            ?>

                            <tr>

                                <td>
                                    <?php
                                    echo esc_html(
                                        get_date_from_gmt(
                                            $createdAt->format(
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
                                        $product !== null
                                            ? $product->getName()
                                            : (
                                                'Producto #'
                                                . $movement
                                                    ->getProductId()
                                            )
                                    );
                                    ?>
                                </td>

                                <td>
                                    <?php
                                    $sku =
                                        $variant !== null
                                        && $variant->hasSku()
                                            ? $variant->getSku()
                                            : '';

                                    echo esc_html(
                                        $sku !== ''
                                            ? $sku
                                            : (
                                                'Variante #'
                                                . $movement
                                                    ->getVariantId()
                                            )
                                    );
                                    ?>
                                </td>

                                <td>
                                    <?php
                                    echo esc_html(
                                        $typeLabels[
                                            $movementType
                                        ]
                                        ?? ucfirst(
                                            str_replace(
                                                '_',
                                                ' ',
                                                $movementType
                                            )
                                        )
                                    );
                                    ?>
                                </td>

                                <td>
                                    <?php
                                    echo esc_html(
                                        sprintf(
                                            '%+d',
                                            $movement
                                                ->getQuantityDelta()
                                        )
                                    );
                                    ?>
                                </td>

                                <td>
                                    <?php
                                    echo esc_html(
                                        sprintf(
                                            '%+d',
                                            $movement
                                                ->getReservedDelta()
                                        )
                                    );
                                    ?>
                                </td>

                                <td>
                                    <?php
                                    echo esc_html(
                                        sprintf(
                                            '%d → %d',
                                            $movement
                                                ->getStockQuantityBefore(),
                                            $movement
                                                ->getStockQuantityAfter()
                                        )
                                    );
                                    ?>
                                </td>

                                <td>
                                    <?php
                                    echo esc_html(
                                        sprintf(
                                            '%d → %d',
                                            $movement
                                                ->getStockReservedBefore(),
                                            $movement
                                                ->getStockReservedAfter()
                                        )
                                    );
                                    ?>
                                </td>

                                <td>
                                    <?php
                                    echo esc_html(
                                        sprintf(
                                            '%d → %d',
                                            $movement
                                                ->getAvailableStockBefore(),
                                            $movement
                                                ->getAvailableStockAfter()
                                        )
                                    );
                                    ?>
                                </td>

                                <td>
                                    <?php if (
                                        $movement->hasReference()
                                    ) : ?>

                                        <?php
                                        echo esc_html(
                                            sprintf(
                                                '%s #%d',
                                                (string)
                                                $movement
                                                    ->getReferenceType(),
                                                (int)
                                                $movement
                                                    ->getReferenceId()
                                            )
                                        );
                                        ?>

                                    <?php else : ?>

                                        —

                                    <?php endif; ?>
                                </td>

                                <td>
                                    <?php
                                    $notes =
                                        trim(
                                            (string) (
                                                $movement
                                                    ->getNotes()
                                                ?? ''
                                            )
                                        );

                                    echo esc_html(
                                        $notes !== ''
                                            ? $notes
                                            : '—'
                                    );
                                    ?>
                                </td>

                            </tr>

                        <?php endforeach; ?>

                    </tbody>

                </table>

            </div>

        <?php endif; ?>

    </article>

</section>
