<?php

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

/**
 * @var array<int, array<string, mixed>> $inventoryRows
 * @var int $inventoryCount
 * @var string $inventorySearch
 * @var string $inventoryStatus
 * @var int $inventoryPage
 * @var int $inventoryPerPage
 * @var int $inventoryTotalPages
 * @var int $inventoryOffset
 */

?>

<section class="dsm-store-inventory">

    <article class="dsm-card">

        <header class="dsm-store-inventory__header">

            <div>

                <h2>
                    Inventario
                </h2>

                <p>
                    Consulta las existencias físicas,
                    unidades reservadas y stock disponible
                    de todas las variantes de tu tienda.
                </p>

            </div>

        </header>

        <form
            class="dsm-store-inventory__filters"
            method="get"
            action="<?php
            echo esc_url(
                home_url(
                    '/mi-tienda/'
                )
            );
            ?>"
        >

            <input
                type="hidden"
                name="store_section"
                value="inventory"
            >

            <div class="dsm-store-inventory__search">

                <label
                    class="screen-reader-text"
                    for="dsm-inventory-search"
                >
                    Buscar en inventario
                </label>

                <input
                    id="dsm-inventory-search"
                    type="search"
                    name="inventory_search"
                    value="<?php
                    echo esc_attr(
                        $inventorySearch
                    );
                    ?>"
                    placeholder="Buscar producto, SKU, referencia, talla o color..."
                >

            </div>

            <div class="dsm-store-inventory__status">

                <label
                    class="screen-reader-text"
                    for="dsm-inventory-status"
                >
                    Estado del stock
                </label>

                <select
                    id="dsm-inventory-status"
                    name="inventory_status"
                >

                    <option
                        value=""
                        <?php
                        selected(
                            $inventoryStatus,
                            ''
                        );
                        ?>
                    >
                        Todos los estados
                    </option>

                    <option
                        value="available"
                        <?php
                        selected(
                            $inventoryStatus,
                            'available'
                        );
                        ?>
                    >
                        Disponible
                    </option>

                    <option
                        value="low_stock"
                        <?php
                        selected(
                            $inventoryStatus,
                            'low_stock'
                        );
                        ?>
                    >
                        Stock bajo
                    </option>

                    <option
                        value="out_of_stock"
                        <?php
                        selected(
                            $inventoryStatus,
                            'out_of_stock'
                        );
                        ?>
                    >
                        Sin stock
                    </option>

                    <option
                        value="inactive"
                        <?php
                        selected(
                            $inventoryStatus,
                            'inactive'
                        );
                        ?>
                    >
                        Inactiva
                    </option>

                </select>

            </div>

            <button
                type="submit"
                class="
                    dsm-button
                    dsm-button--secondary
                "
            >
                Buscar
            </button>

            <?php if (
                $inventorySearch !== ''
                || $inventoryStatus !== ''
            ) : ?>

                <a
                    class="dsm-store-inventory__clear"
                    href="<?php
                    echo esc_url(
                        add_query_arg(
                            'store_section',
                            'inventory',
                            home_url(
                                '/mi-tienda/'
                            )
                        )
                    );
                    ?>"
                >
                    Limpiar
                </a>

            <?php endif; ?>

        </form>

        <div class="dsm-store-inventory__summary">

            <?php if (
                $inventoryCount > 0
            ) : ?>

                <?php

                $inventoryFirst =
                    $inventoryOffset
                    + 1;

                $inventoryLast =
                    min(
                        $inventoryOffset
                        + count(
                            $inventoryRows
                        ),
                        $inventoryCount
                    );

                ?>

                <strong>
                    <?php
                    echo esc_html(
                        sprintf(
                            'Mostrando %d–%d de %d registros',
                            $inventoryFirst,
                            $inventoryLast,
                            $inventoryCount
                        )
                    );
                    ?>
                </strong>

            <?php endif; ?>

        </div>

        <?php if (
            $inventoryRows === []
        ) : ?>

            <div
                class="
                    dsm-account-notice
                    dsm-account-notice--info
                "
            >

                <?php if (
                    $inventorySearch !== ''
                    || $inventoryStatus !== ''
                ) : ?>

                    No hay registros de inventario
                    que coincidan con la búsqueda.

                <?php else : ?>

                    Todavía no hay productos
                    en el inventario.

                <?php endif; ?>

            </div>

        <?php else : ?>

            <div class="dsm-store-inventory__table-scroll">

                <table class="dsm-store-inventory__table">

                    <thead>

                        <tr>
                            <th>Producto</th>
                            <th>SKU</th>
                            <th>Talla</th>
                            <th>Color</th>
                            <th class="is-number">
                                Físico
                            </th>
                            <th class="is-number">
                                Reservado
                            </th>
                            <th class="is-number">
                                Disponible
                            </th>
                            <th>Estado</th>
                            <th class="is-action">
                                Acción
                            </th>
                        </tr>

                    </thead>

                    <tbody>

                        <?php foreach (
                            $inventoryRows
                            as $row
                        ) : ?>

                            <?php

                            $productId =
                                (int) (
                                    $row[
                                        'product_id'
                                    ]
                                    ?? 0
                                );

                            $variantId =
                                isset(
                                    $row[
                                        'variant_id'
                                    ]
                                )
                                    ? (int) $row[
                                        'variant_id'
                                    ]
                                    : 0;

                            $hasVariant =
                                $variantId > 0;

                            $trackStock =
                                $hasVariant
                                && (int) (
                                    $row[
                                        'track_stock'
                                    ]
                                    ?? 0
                                ) === 1;

                            $isActive =
                                $hasVariant
                                && (int) (
                                    $row[
                                        'is_active'
                                    ]
                                    ?? 0
                                ) === 1;

                            $physicalStock =
                                $hasVariant
                                    ? (int) (
                                        $row[
                                            'stock_quantity'
                                        ]
                                        ?? 0
                                    )
                                    : 0;

                            $reservedStock =
                                $hasVariant
                                    ? (int) (
                                        $row[
                                            'stock_reserved'
                                        ]
                                        ?? 0
                                    )
                                    : 0;

                            $availableStock =
                                $trackStock
                                    ? max(
                                        0,
                                        $physicalStock
                                        - $reservedStock
                                    )
                                    : null;

                            $lowStockThreshold =
                                isset(
                                    $row[
                                        'low_stock_threshold'
                                    ]
                                )
                                && $row[
                                    'low_stock_threshold'
                                ] !== null
                                    ? (int) $row[
                                        'low_stock_threshold'
                                    ]
                                    : null;

                            $statusClass =
                                'available';

                            $statusLabel =
                                'Disponible';

                            if (!$hasVariant) {
                                $statusClass =
                                    'no-variants';

                                $statusLabel =
                                    'Sin variantes';
                            } elseif (!$isActive) {
                                $statusClass =
                                    'inactive';

                                $statusLabel =
                                    'Inactiva';
                            } elseif (
                                $trackStock
                                && $availableStock !== null
                                && $availableStock <= 0
                            ) {
                                $statusClass =
                                    'out-of-stock';

                                $statusLabel =
                                    'Sin stock';
                            } elseif (
                                $trackStock
                                && $availableStock !== null
                                && $lowStockThreshold !== null
                                && $availableStock
                                    <= $lowStockThreshold
                            ) {
                                $statusClass =
                                    'low-stock';

                                $statusLabel =
                                    'Stock bajo';
                            }

                            ?>

                            <tr>

                                <td
                                    data-label="Producto"
                                    class="
                                        dsm-store-inventory__product
                                    "
                                >

                                    <strong>
                                        <?php
                                        echo esc_html(
                                            (string) (
                                                $row[
                                                    'product_name'
                                                ]
                                                ?? ''
                                            )
                                        );
                                        ?>
                                    </strong>

                                    <?php if (
                                        !empty(
                                            $row[
                                                'internal_reference'
                                            ]
                                        )
                                    ) : ?>

                                        <small>
                                            Ref:
                                            <?php
                                            echo esc_html(
                                                (string) $row[
                                                    'internal_reference'
                                                ]
                                            );
                                            ?>
                                        </small>

                                    <?php endif; ?>

                                </td>

                                <td data-label="SKU">

                                    <?php

                                    $sku =
                                        $row['sku']
                                        ?? $row['base_sku']
                                        ?? null;

                                    echo esc_html(
                                        is_string($sku)
                                        && trim($sku) !== ''
                                            ? $sku
                                            : '—'
                                    );

                                    ?>

                                </td>

                                <td data-label="Talla">
                                    <?php
                                    echo esc_html(
                                        !empty(
                                            $row[
                                                'size_value'
                                            ]
                                        )
                                            ? (string) $row[
                                                'size_value'
                                            ]
                                            : '—'
                                    );
                                    ?>
                                </td>

                                <td data-label="Color">
                                    <?php
                                    echo esc_html(
                                        !empty(
                                            $row[
                                                'color_value'
                                            ]
                                        )
                                            ? (string) $row[
                                                'color_value'
                                            ]
                                            : '—'
                                    );
                                    ?>
                                </td>

                                <td
                                    data-label="Físico"
                                    class="is-number"
                                >
                                    <?php
                                    echo $hasVariant
                                        ? esc_html(
                                            (string)
                                            $physicalStock
                                        )
                                        : '—';
                                    ?>
                                </td>

                                <td
                                    data-label="Reservado"
                                    class="is-number"
                                >
                                    <?php
                                    echo $hasVariant
                                        ? esc_html(
                                            (string)
                                            $reservedStock
                                        )
                                        : '—';
                                    ?>
                                </td>

                                <td
                                    data-label="Disponible"
                                    class="
                                        is-number
                                        dsm-store-inventory__available
                                    "
                                >

                                    <?php if (
                                        !$hasVariant
                                    ) : ?>

                                        —

                                    <?php elseif (
                                        !$trackStock
                                    ) : ?>

                                        ∞

                                    <?php else : ?>

                                        <strong>
                                            <?php
                                            echo esc_html(
                                                (string)
                                                $availableStock
                                            );
                                            ?>
                                        </strong>

                                    <?php endif; ?>

                                </td>

                                <td data-label="Estado">

                                    <span
                                        class="<?php
                                        echo esc_attr(
                                            'dsm-store-inventory__badge '
                                            . 'dsm-store-inventory__badge--'
                                            . $statusClass
                                        );
                                        ?>"
                                    >
                                        <?php
                                        echo esc_html(
                                            $statusLabel
                                        );
                                        ?>
                                    </span>

                                </td>

                                <td
                                    data-label="Acción"
                                    class="is-action"
                                >

                                    <a
                                        class="
                                            dsm-button
                                            dsm-button--secondary
                                            dsm-store-inventory__manage
                                        "
                                        href="<?php
                                        echo esc_url(
                                            add_query_arg(
                                                [
                                                    'store_section' =>
                                                        'edit-product',

                                                    'product_id' =>
                                                        $productId,
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

                    </tbody>

                </table>

            </div>

            <?php if (
                $inventoryTotalPages > 1
            ) : ?>

                <?php

                $paginationArgs = [
                    'store_section' =>
                        'inventory',
                ];

                if (
                    $inventorySearch !== ''
                ) {
                    $paginationArgs[
                        'inventory_search'
                    ] =
                        $inventorySearch;
                }

                if (
                    $inventoryStatus !== ''
                ) {
                    $paginationArgs[
                        'inventory_status'
                    ] =
                        $inventoryStatus;
                }

                $pageStart =
                    max(
                        1,
                        $inventoryPage - 2
                    );

                $pageEnd =
                    min(
                        $inventoryTotalPages,
                        $inventoryPage + 2
                    );

                ?>

                <nav
                    class="dsm-store-inventory__pagination"
                    aria-label="Páginas del inventario"
                >

                    <?php if (
                        $inventoryPage > 1
                    ) : ?>

                        <a
                            href="<?php
                            echo esc_url(
                                add_query_arg(
                                    array_merge(
                                        $paginationArgs,
                                        [
                                            'inventory_page' =>
                                                $inventoryPage
                                                - 1,
                                        ]
                                    ),
                                    home_url(
                                        '/mi-tienda/'
                                    )
                                )
                            );
                            ?>"
                            aria-label="Página anterior"
                        >
                            ‹
                        </a>

                    <?php endif; ?>

                    <?php for (
                        $pageNumber =
                            $pageStart;

                        $pageNumber <=
                            $pageEnd;

                        $pageNumber++
                    ) : ?>

                        <a
                            class="<?php
                            echo $pageNumber
                                === $inventoryPage
                                    ? 'is-current'
                                    : '';
                            ?>"
                            href="<?php
                            echo esc_url(
                                add_query_arg(
                                    array_merge(
                                        $paginationArgs,
                                        [
                                            'inventory_page' =>
                                                $pageNumber,
                                        ]
                                    ),
                                    home_url(
                                        '/mi-tienda/'
                                    )
                                )
                            );
                            ?>"
                            <?php if (
                                $pageNumber ===
                                $inventoryPage
                            ) : ?>
                                aria-current="page"
                            <?php endif; ?>
                        >
                            <?php
                            echo esc_html(
                                (string)
                                $pageNumber
                            );
                            ?>
                        </a>

                    <?php endfor; ?>

                    <?php if (
                        $inventoryPage
                        < $inventoryTotalPages
                    ) : ?>

                        <a
                            href="<?php
                            echo esc_url(
                                add_query_arg(
                                    array_merge(
                                        $paginationArgs,
                                        [
                                            'inventory_page' =>
                                                $inventoryPage
                                                + 1,
                                        ]
                                    ),
                                    home_url(
                                        '/mi-tienda/'
                                    )
                                )
                            );
                            ?>"
                            aria-label="Página siguiente"
                        >
                            ›
                        </a>

                    <?php endif; ?>

                </nav>

            <?php endif; ?>

        <?php endif; ?>

    </article>

</section>
