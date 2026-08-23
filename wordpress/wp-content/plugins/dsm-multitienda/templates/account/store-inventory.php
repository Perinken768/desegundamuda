<?php

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

/*
 * $inventoryProducts
 * $inventoryVariants
 */

?>

<section class="dsm-store-inventory">

    <article class="dsm-card">

        <h2>Inventario</h2>

        <p>
            Consulta las existencias físicas,
            unidades reservadas y stock disponible
            de todas las variantes de tu tienda.
        </p>

        <?php if ($inventoryProducts === []) : ?>

            <p>
                Todavía no hay productos
                en el inventario.
            </p>

        <?php else : ?>

            <div class="dsm-admin-table-scroll">

                <table class="widefat striped">

                    <thead>
                        <tr>
                            <th>Producto</th>
                            <th>SKU</th>
                            <th>Talla</th>
                            <th>Color</th>
                            <th>Físico</th>
                            <th>Reservado</th>
                            <th>Disponible</th>
                            <th>Estado</th>
                            <th></th>
                        </tr>
                    </thead>

                    <tbody>

                        <?php foreach (
                            $inventoryProducts
                            as $product
                        ) : ?>

                            <?php
                            $variants =
                                $inventoryVariants[
                                    $product->getId()
                                ]
                                ?? [];
                            ?>

                            <?php if ($variants === []) : ?>

                                <tr>
                                    <td>
                                        <strong>
                                            <?php
                                            echo esc_html(
                                                $product
                                                    ->getName()
                                            );
                                            ?>
                                        </strong>
                                    </td>

                                    <td colspan="7">
                                        Sin variantes.
                                    </td>

                                    <td>
                                        <a
                                            href="<?php
                                            echo esc_url(
                                                add_query_arg(
                                                    [
                                                        'store_section' =>
                                                            'edit-product',

                                                        'product_id' =>
                                                            $product
                                                                ->getId(),
                                                    ],
                                                    home_url(
                                                        '/mi-tienda/'
                                                    )
                                                )
                                            );
                                            ?>"
                                        >
                                            Gestionar
                                        </a>
                                    </td>
                                </tr>

                            <?php else : ?>

                                <?php foreach (
                                    $variants
                                    as $variant
                                ) : ?>

                                    <tr>

                                        <td>
                                            <strong>
                                                <?php
                                                echo esc_html(
                                                    $product
                                                        ->getName()
                                                );
                                                ?>
                                            </strong>
                                        </td>

                                        <td>
                                            <?php
                                            echo esc_html(
                                                $variant->hasSku()
                                                    ? $variant->getSku()
                                                    : '—'
                                            );
                                            ?>
                                        </td>

                                        <td>
                                            <?php
                                            echo esc_html(
                                                $variant->hasSize()
                                                    ? $variant
                                                        ->getSizeValue()
                                                    : '—'
                                            );
                                            ?>
                                        </td>

                                        <td>
                                            <?php
                                            echo esc_html(
                                                $variant->hasColor()
                                                    ? $variant
                                                        ->getColorValue()
                                                    : '—'
                                            );
                                            ?>
                                        </td>

                                        <td>
                                            <?php
                                            echo esc_html(
                                                (string)
                                                $variant
                                                    ->getStockQuantity()
                                            );
                                            ?>
                                        </td>

                                        <td>
                                            <?php
                                            echo esc_html(
                                                (string)
                                                $variant
                                                    ->getStockReserved()
                                            );
                                            ?>
                                        </td>

                                        <td>
                                            <strong>
                                                <?php
                                                echo esc_html(
                                                    (string)
                                                    $variant
                                                        ->getAvailableStock()
                                                );
                                                ?>
                                            </strong>
                                        </td>

                                        <td>
                                            <?php if (
                                                !$variant->isActive()
                                            ) : ?>

                                                Inactiva

                                            <?php elseif (
                                                $variant->isOutOfStock()
                                            ) : ?>

                                                Sin stock

                                            <?php elseif (
                                                $variant->isLowStock()
                                            ) : ?>

                                                Stock bajo

                                            <?php else : ?>

                                                Disponible

                                            <?php endif; ?>
                                        </td>

                                        <td>
                                            <a
                                                href="<?php
                                                echo esc_url(
                                                    add_query_arg(
                                                        [
                                                            'store_section' =>
                                                                'edit-product',

                                                            'product_id' =>
                                                                $product
                                                                    ->getId(),
                                                        ],
                                                        home_url(
                                                            '/mi-tienda/'
                                                        )
                                                    )
                                                );
                                                ?>"
                                            >
                                                Gestionar
                                            </a>
                                        </td>

                                    </tr>

                                <?php endforeach; ?>

                            <?php endif; ?>

                        <?php endforeach; ?>

                    </tbody>

                </table>

            </div>

        <?php endif; ?>

    </article>

</section>
