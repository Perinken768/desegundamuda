<?php

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

/*
 * $stockMovements
 * $movementProducts
 * $movementVariants
 *
 * $movementSearch
 * $movementTypeFilter
 * $movementCount
 * $movementPage
 * $movementPerPage
 * $movementTotalPages
 * $movementOffset
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

    'return' =>
        'Devolución',

    'cancellation' =>
        'Cancelación',

    'expiration' =>
        'Caducidad',
];

$typeOptions = $typeLabels;

$baseUrl =
    add_query_arg(
        [
            'store_section' =>
                'movements',
        ],
        home_url(
            '/mi-tienda/'
        )
    );

$firstResult =
    $movementCount > 0
        ? $movementOffset + 1
        : 0;

$lastResult =
    min(
        $movementOffset
        + $movementPerPage,
        $movementCount
    );

?>

<section class="dsm-store-movements">


    <article class="dsm-card dsm-movement-panel">


        <header class="dsm-movement-panel__header">

            <div>

                <h2>
                    Movimientos de stock
                </h2>

                <p>
                    Histórico de cambios de existencias
                    y unidades reservadas de tu tienda.
                </p>

            </div>

            <span class="dsm-movement-panel__counter">

                <?php
                echo esc_html(
                    (string)
                    $movementCount
                );
                ?>

                <?php
                echo $movementCount === 1
                    ? 'movimiento'
                    : 'movimientos';
                ?>

            </span>

        </header>


        <!-- FILTROS -->

        <form
            class="dsm-movement-filters"
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
                value="movements"
            >


            <div class="dsm-movement-filters__search">

                <label for="dsm-movement-search">
                    Buscar
                </label>

                <input
                    id="dsm-movement-search"
                    type="search"
                    name="movement_search"
                    value="<?php
                    echo esc_attr(
                        $movementSearch
                    );
                    ?>"
                    placeholder="Producto, SKU, referencia..."
                >

            </div>


            <div class="dsm-movement-filters__type">

                <label for="dsm-movement-type">
                    Tipo
                </label>

                <select
                    id="dsm-movement-type"
                    name="movement_type"
                >

                    <option value="">
                        Todos los tipos
                    </option>

                    <?php foreach (
                        $typeOptions
                        as $type => $label
                    ) : ?>

                        <option
                            value="<?php
                            echo esc_attr(
                                $type
                            );
                            ?>"
                            <?php
                            selected(
                                $movementTypeFilter,
                                $type
                            );
                            ?>
                        >
                            <?php
                            echo esc_html(
                                $label
                            );
                            ?>
                        </option>

                    <?php endforeach; ?>

                </select>

            </div>


            <div class="dsm-movement-filters__actions">

                <button
                    type="submit"
                    class="
                        dsm-button
                        dsm-button--secondary
                    "
                >
                    Filtrar
                </button>

                <?php if (
                    $movementSearch !== ''
                    || $movementTypeFilter !== ''
                ) : ?>

                    <a
                        href="<?php
                        echo esc_url(
                            $baseUrl
                        );
                        ?>"
                        class="
                            dsm-movement-filters__clear
                        "
                    >
                        Limpiar
                    </a>

                <?php endif; ?>

            </div>

        </form>


        <!-- RESUMEN -->

        <div class="dsm-movement-summary">

            <?php if (
                $movementCount > 0
            ) : ?>

                Mostrando

                <strong>
                    <?php
                    echo esc_html(
                        (string)
                        $firstResult
                    );
                    ?>–<?php
                    echo esc_html(
                        (string)
                        $lastResult
                    );
                    ?>
                </strong>

                de

                <strong>
                    <?php
                    echo esc_html(
                        (string)
                        $movementCount
                    );
                    ?>
                </strong>

                movimientos

            <?php else : ?>

                No se encontraron movimientos.

            <?php endif; ?>

        </div>


        <?php if (
            $stockMovements === []
        ) : ?>

            <div class="dsm-movement-empty">

                <strong>
                    No hay movimientos que mostrar.
                </strong>

                <span>
                    Prueba a cambiar los filtros
                    de búsqueda.
                </span>

            </div>

        <?php else : ?>


            <div class="dsm-movement-table-scroll">

                <table class="dsm-movement-table">

                    <thead>

                        <tr>
                            <th>Fecha</th>
                            <th>Producto</th>
                            <th>Variante</th>
                            <th>Tipo</th>
                            <th class="is-centered">
                                Δ físico
                            </th>
                            <th class="is-centered">
                                Δ reservado
                            </th>
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

                            $sku =
                                $variant !== null
                                    ? trim(
                                        (string) (
                                            $variant
                                                ->getSku()
                                            ?? ''
                                        )
                                    )
                                    : '';

                            $quantityDelta =
                                $movement
                                    ->getQuantityDelta();

                            $reservedDelta =
                                $movement
                                    ->getReservedDelta();

                            $notes =
                                trim(
                                    (string) (
                                        $movement
                                            ->getNotes()
                                        ?? ''
                                    )
                                );

                            $referenceType =
                                trim(
                                    (string) (
                                        $movement
                                            ->getReferenceType()
                                        ?? ''
                                    )
                                );

                            $referenceId =
                                $movement
                                    ->getReferenceId();

                            ?>

                            <tr>


                                <!-- FECHA -->

                                <td
                                    class="
                                        dsm-movement-table__date
                                    "
                                >
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


                                <!-- PRODUCTO -->

                                <td
                                    class="
                                        dsm-movement-table__product
                                    "
                                >
                                    <strong>
                                        <?php
                                        echo esc_html(
                                            $product !== null
                                                ? $product
                                                    ->getName()
                                                : (
                                                    'Producto #'
                                                    . $movement
                                                        ->getProductId()
                                                )
                                        );
                                        ?>
                                    </strong>
                                </td>


                                <!-- VARIANTE -->

                                <td
                                    class="
                                        dsm-movement-table__variant
                                    "
                                >
                                    <?php
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


                                <!-- TIPO -->

                                <td>

                                    <span
                                        class="<?php
                                        echo esc_attr(
                                            'dsm-movement-type '
                                            . 'dsm-movement-type--'
                                            . $movementType
                                        );
                                        ?>"
                                    >
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
                                    </span>

                                </td>


                                <!-- DELTA FISICO -->

                                <td class="is-centered">

                                    <span
                                        class="<?php
                                        echo esc_attr(
                                            'dsm-movement-delta '
                                            . (
                                                $quantityDelta > 0
                                                    ? 'is-positive'
                                                    : (
                                                        $quantityDelta < 0
                                                            ? 'is-negative'
                                                            : 'is-zero'
                                                    )
                                            )
                                        );
                                        ?>"
                                    >
                                        <?php
                                        echo esc_html(
                                            sprintf(
                                                '%+d',
                                                $quantityDelta
                                            )
                                        );
                                        ?>
                                    </span>

                                </td>


                                <!-- DELTA RESERVADO -->

                                <td class="is-centered">

                                    <span
                                        class="<?php
                                        echo esc_attr(
                                            'dsm-movement-delta '
                                            . (
                                                $reservedDelta > 0
                                                    ? 'is-positive'
                                                    : (
                                                        $reservedDelta < 0
                                                            ? 'is-negative'
                                                            : 'is-zero'
                                                    )
                                            )
                                        );
                                        ?>"
                                    >
                                        <?php
                                        echo esc_html(
                                            sprintf(
                                                '%+d',
                                                $reservedDelta
                                            )
                                        );
                                        ?>
                                    </span>

                                </td>


                                <!-- FISICO -->

                                <td
                                    class="
                                        dsm-movement-table__change
                                    "
                                >

                                    <span>
                                        <?php
                                        echo esc_html(
                                            (string)
                                            $movement
                                                ->getStockQuantityBefore()
                                        );
                                        ?>
                                    </span>

                                    <b>→</b>

                                    <strong>
                                        <?php
                                        echo esc_html(
                                            (string)
                                            $movement
                                                ->getStockQuantityAfter()
                                        );
                                        ?>
                                    </strong>

                                </td>


                                <!-- RESERVADO -->

                                <td
                                    class="
                                        dsm-movement-table__change
                                    "
                                >

                                    <span>
                                        <?php
                                        echo esc_html(
                                            (string)
                                            $movement
                                                ->getStockReservedBefore()
                                        );
                                        ?>
                                    </span>

                                    <b>→</b>

                                    <strong>
                                        <?php
                                        echo esc_html(
                                            (string)
                                            $movement
                                                ->getStockReservedAfter()
                                        );
                                        ?>
                                    </strong>

                                </td>


                                <!-- DISPONIBLE -->

                                <td
                                    class="
                                        dsm-movement-table__change
                                    "
                                >

                                    <span>
                                        <?php
                                        echo esc_html(
                                            (string)
                                            $movement
                                                ->getAvailableStockBefore()
                                        );
                                        ?>
                                    </span>

                                    <b>→</b>

                                    <strong>
                                        <?php
                                        echo esc_html(
                                            (string)
                                            $movement
                                                ->getAvailableStockAfter()
                                        );
                                        ?>
                                    </strong>

                                </td>


                                <!-- REFERENCIA -->

                                <td
                                    class="
                                        dsm-movement-table__reference
                                    "
                                >

                                    <?php if (
                                        $referenceType !== ''
                                        && $referenceId !== null
                                    ) : ?>

                                        <span>
                                            <?php
                                            echo esc_html(
                                                $referenceType
                                            );
                                            ?>
                                        </span>

                                        <strong>
                                            #<?php
                                            echo esc_html(
                                                (string)
                                                $referenceId
                                            );
                                            ?>
                                        </strong>

                                    <?php else : ?>

                                        <span class="is-empty">
                                            —
                                        </span>

                                    <?php endif; ?>

                                </td>


                                <!-- NOTAS -->

                                <td
                                    class="
                                        dsm-movement-table__notes
                                    "
                                >
                                    <?php
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


            <!-- PAGINACION -->

            <?php if (
                $movementTotalPages > 1
            ) : ?>

                <nav
                    class="dsm-movement-pagination"
                    aria-label="
                        Páginas del histórico de movimientos
                    "
                >

                    <?php

                    $startPage =
                        max(
                            1,
                            $movementPage - 2
                        );

                    $endPage =
                        min(
                            $movementTotalPages,
                            $movementPage + 2
                        );

                    $buildMovementPageUrl =
                        static function (
                            int $page
                        ) use (
                            $baseUrl,
                            $movementSearch,
                            $movementTypeFilter
                        ): string {
                            $args = [
                                'movement_page' =>
                                    $page,
                            ];

                            if (
                                $movementSearch !== ''
                            ) {
                                $args[
                                    'movement_search'
                                ] =
                                    $movementSearch;
                            }

                            if (
                                $movementTypeFilter !== ''
                            ) {
                                $args[
                                    'movement_type'
                                ] =
                                    $movementTypeFilter;
                            }

                            return add_query_arg(
                                $args,
                                $baseUrl
                            );
                        };

                    ?>


                    <?php if (
                        $movementPage > 1
                    ) : ?>

                        <a
                            href="<?php
                            echo esc_url(
                                $buildMovementPageUrl(
                                    $movementPage - 1
                                )
                            );
                            ?>"
                        >
                            ‹
                        </a>

                    <?php endif; ?>


                    <?php for (
                        $page = $startPage;
                        $page <= $endPage;
                        $page++
                    ) : ?>

                        <?php if (
                            $page === $movementPage
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
                                    $buildMovementPageUrl(
                                        $page
                                    )
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
                        $movementPage
                        < $movementTotalPages
                    ) : ?>

                        <a
                            href="<?php
                            echo esc_url(
                                $buildMovementPageUrl(
                                    $movementPage + 1
                                )
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
