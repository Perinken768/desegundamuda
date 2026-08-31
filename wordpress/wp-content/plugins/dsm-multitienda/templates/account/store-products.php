<?php

declare(strict_types=1);

use DSM\Catalogo\Product\Product;
use DSM\Catalogo\Product\ProductStatus;
use DSM\Multitienda\Frontend\StoreProductsController;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * @var array<int, Product> $products
 * @var int $productCount
 * @var string $productStatusNotice
 * @var string $productStatusError
 * @var string $productSearch
 * @var int $productPage
 * @var int $productPerPage
 * @var int $productTotalPages
 * @var int $productOffset
 */

?>

<section class="dsm-card">

    <header>

        <h2>
            Productos
        </h2>

        <p>
            Gestiona los artículos disponibles
            en tu tienda.
        </p>

    </header>

    <?php if (
        $productStatusNotice === 'updated'
    ) : ?>

        <div
            class="
                dsm-account-notice
                dsm-account-notice--success
            "
        >
            El estado del producto se actualizó
            correctamente.
        </div>

    <?php elseif (
        $productStatusNotice === 'error'
        && $productStatusError !== ''
    ) : ?>

        <div
            class="
                dsm-account-notice
                dsm-account-notice--error
            "
        >
            <?php
            echo esc_html(
                $productStatusError
            );
            ?>
        </div>

    <?php endif; ?>

    <div class="dsm-store-products__toolbar">

        <form
            class="dsm-store-products__search"
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
                value="products"
            >

            <label
                class="screen-reader-text"
                for="dsm-product-search"
            >
                Buscar productos
            </label>

            <input
                id="dsm-product-search"
                type="search"
                name="product_search"
                value="<?php
                echo esc_attr(
                    $productSearch
                );
                ?>"
                placeholder="Buscar por nombre, SKU o referencia..."
            >

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
                $productSearch !== ''
            ) : ?>

                <a
                    class="dsm-store-products__clear"
                    href="<?php
                    echo esc_url(
                        add_query_arg(
                            'store_section',
                            'products',
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

        <a
            class="
                dsm-button
                dsm-button--primary
            "
            href="<?php
            echo esc_url(
                add_query_arg(
                    [
                        'store_section' =>
                            'new-product',
                    ],
                    home_url(
                        '/mi-tienda/'
                    )
                )
            );
            ?>"
        >
            Nuevo producto
        </a>

    </div>

    <div class="dsm-store-products__summary">

        <?php if (
            $productCount > 0
        ) : ?>

            <?php

            $productFirst =
                $productOffset
                + 1;

            $productLast =
                min(
                    $productOffset
                    + count(
                        $products
                    ),
                    $productCount
                );

            ?>

            <strong>
                <?php
                echo esc_html(
                    sprintf(
                        'Mostrando %d–%d de %d productos',
                        $productFirst,
                        $productLast,
                        $productCount
                    )
                );
                ?>
            </strong>

            <?php if (
                $productSearch !== ''
            ) : ?>

                <span>
                    para “<?php
                    echo esc_html(
                        $productSearch
                    );
                    ?>”
                </span>

            <?php endif; ?>

        <?php elseif (
            $productSearch !== ''
        ) : ?>

            <strong>
                No se encontraron productos.
            </strong>

        <?php endif; ?>

    </div>

    <?php if ($products === []) : ?>

        <div
            class="
                dsm-account-notice
                dsm-account-notice--info
            "
        >
            Todavía no has creado ningún producto.
        </div>

    <?php else : ?>

        <div
            style="
                overflow-x:auto;
            "
        >

            <table
                style="
                    width:100%;
                    border-collapse:collapse;
                "
            >

                <thead>

                    <tr>

                        <th
                            style="
                                text-align:left;
                                padding:10px;
                            "
                        >
                            Producto
                        </th>

                        <th
                            style="
                                text-align:left;
                                padding:10px;
                            "
                        >
                            Referencia
                        </th>

                        <th
                            style="
                                text-align:left;
                                padding:10px;
                            "
                        >
                            Precio
                        </th>

                        <th
                            style="
                                text-align:left;
                                padding:10px;
                            "
                        >
                            Stock
                        </th>

                        <th
                            style="
                                text-align:left;
                                padding:10px;
                            "
                        >
                            Estado
                        </th>

                        <th
                            style="
                                text-align:left;
                                padding:10px;
                            "
                        >
                            Acciones
                        </th>

                    </tr>

                </thead>

                <tbody>

                    <?php foreach (
                        $products
                        as $product
                    ) : ?>

                        <tr>

                            <td
                                style="
                                    padding:10px;
                                    border-top:1px solid #ddd;
                                "
                            >

                                <strong>
                                    <?php
                                    echo esc_html(
                                        $product
                                            ->getName()
                                    );
                                    ?>
                                </strong>

                                <?php if (
                                    $product
                                        ->getBaseSku()
                                    !== null
                                ) : ?>

                                    <br>

                                    <small>
                                        SKU:
                                        <?php
                                        echo esc_html(
                                            $product
                                                ->getBaseSku()
                                        );
                                        ?>
                                    </small>

                                <?php endif; ?>

                            </td>

                            <td
                                style="
                                    padding:10px;
                                    border-top:1px solid #ddd;
                                "
                            >

                                <?php
                                echo esc_html(
                                    $product
                                        ->getInternalReference()
                                    ?? '—'
                                );
                                ?>

                            </td>

                            <td
                                style="
                                    padding:10px;
                                    border-top:1px solid #ddd;
                                "
                            >

                                <?php
                                echo esc_html(
                                    number_format_i18n(
                                        $product
                                            ->getDefaultPrice(),
                                        2
                                    )
                                );
                                ?>
                                €

                            </td>

                            <td
                                style="
                                    padding:10px;
                                    border-top:1px solid #ddd;
                                "
                            >

                                <?php if (
                                    $product
                                        ->tracksStock()
                                ) : ?>

                                    Gestionado por variantes

                                <?php else : ?>

                                    Sin control de stock

                                <?php endif; ?>

                            </td>

                            <td
                                style="
                                    padding:10px;
                                    border-top:1px solid #ddd;
                                "
                            >

                                <?php
                                echo esc_html(
                                    match (
                                        $product
                                            ->getStatus()
                                    ) {
                                        ProductStatus::DRAFT =>
                                            'Borrador',

                                        ProductStatus::ACTIVE =>
                                            'Activo',

                                        ProductStatus::INACTIVE =>
                                            'Inactivo',

                                        ProductStatus::ARCHIVED =>
                                            'Archivado',

                                        default =>
                                            $product
                                                ->getStatus(),
                                    }
                                );
                                ?>

                            </td>

                            <td
                                style="
                                    padding:10px;
                                    border-top:1px solid #ddd;
                                "
                            >

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
                                    Editar
                                </a>

                                <?php if (
                                    $product->getStatus()
                                    === ProductStatus::ACTIVE
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
                                        style="
                                            display:inline-block;
                                            margin-left:10px;
                                        "
                                    >

                                        <input
                                            type="hidden"
                                            name="action"
                                            value="<?php
                                            echo esc_attr(
                                                StoreProductsController::
                                                    STATUS_ACTION
                                            );
                                            ?>"
                                        >

                                        <input
                                            type="hidden"
                                            name="product_id"
                                            value="<?php
                                            echo esc_attr(
                                                (string)
                                                $product
                                                    ->getId()
                                            );
                                            ?>"
                                        >

                                        <input
                                            type="hidden"
                                            name="product_status"
                                            value="<?php
                                            echo esc_attr(
                                                ProductStatus::
                                                    INACTIVE
                                            );
                                            ?>"
                                        >

                                        <?php
                                        wp_nonce_field(
                                            StoreProductsController::
                                                getNonceAction(
                                                    $product
                                                        ->getId()
                                                ),
                                            StoreProductsController::
                                                NONCE_FIELD
                                        );
                                        ?>

                                        <button
                                            type="submit"
                                            class="
                                                dsm-button
                                                dsm-button--secondary
                                            "
                                        >
                                            Desactivar
                                        </button>

                                    </form>

                                <?php elseif (
                                    in_array(
                                        $product
                                            ->getStatus(),
                                        [
                                            ProductStatus::DRAFT,
                                            ProductStatus::INACTIVE,
                                        ],
                                        true
                                    )
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
                                        style="
                                            display:inline-block;
                                            margin-left:10px;
                                        "
                                    >

                                        <input
                                            type="hidden"
                                            name="action"
                                            value="<?php
                                            echo esc_attr(
                                                StoreProductsController::
                                                    STATUS_ACTION
                                            );
                                            ?>"
                                        >

                                        <input
                                            type="hidden"
                                            name="product_id"
                                            value="<?php
                                            echo esc_attr(
                                                (string)
                                                $product
                                                    ->getId()
                                            );
                                            ?>"
                                        >

                                        <input
                                            type="hidden"
                                            name="product_status"
                                            value="<?php
                                            echo esc_attr(
                                                ProductStatus::
                                                    ACTIVE
                                            );
                                            ?>"
                                        >

                                        <?php
                                        wp_nonce_field(
                                            StoreProductsController::
                                                getNonceAction(
                                                    $product
                                                        ->getId()
                                                ),
                                            StoreProductsController::
                                                NONCE_FIELD
                                        );
                                        ?>

                                        <button
                                            type="submit"
                                            class="
                                                dsm-button
                                                dsm-button--primary
                                            "
                                        >
                                            Activar
                                        </button>

                                    </form>

                                <?php endif; ?>

                            </td>

                        </tr>

                    <?php endforeach; ?>

                </tbody>

            </table>

        </div>

    <?php endif; ?>


    <?php if (
        $productTotalPages > 1
    ) : ?>

        <nav
            class="dsm-store-products__pagination"
            aria-label="Páginas de productos"
        >

            <?php

            $paginationBaseArgs = [
                'store_section' =>
                    'products',
            ];

            if (
                $productSearch !== ''
            ) {
                $paginationBaseArgs[
                    'product_search'
                ] =
                    $productSearch;
            }

            ?>

            <?php if (
                $productPage > 1
            ) : ?>

                <a
                    href="<?php
                    echo esc_url(
                        add_query_arg(
                            array_merge(
                                $paginationBaseArgs,
                                [
                                    'product_page' =>
                                        $productPage
                                        - 1,
                                ]
                            ),
                            home_url(
                                '/mi-tienda/'
                            )
                        )
                    );
                    ?>"
                >
                    ‹
                </a>

            <?php endif; ?>

            <?php

            $pageStart =
                max(
                    1,
                    $productPage - 2
                );

            $pageEnd =
                min(
                    $productTotalPages,
                    $productPage + 2
                );

            for (
                $pageNumber = $pageStart;
                $pageNumber <= $pageEnd;
                $pageNumber++
            ) :

                ?>

                <a
                    class="<?php
                    echo $pageNumber ===
                        $productPage
                            ? 'is-current'
                            : '';
                    ?>"
                    href="<?php
                    echo esc_url(
                        add_query_arg(
                            array_merge(
                                $paginationBaseArgs,
                                [
                                    'product_page' =>
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
                        $productPage
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
                $productPage <
                $productTotalPages
            ) : ?>

                <a
                    href="<?php
                    echo esc_url(
                        add_query_arg(
                            array_merge(
                                $paginationBaseArgs,
                                [
                                    'product_page' =>
                                        $productPage
                                        + 1,
                                ]
                            ),
                            home_url(
                                '/mi-tienda/'
                            )
                        )
                    );
                    ?>"
                >
                    ›
                </a>

            <?php endif; ?>

        </nav>

    <?php endif; ?>


</section>
