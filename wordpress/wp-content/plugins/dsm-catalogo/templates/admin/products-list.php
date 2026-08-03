<?php

declare(strict_types=1);

use DSM\Catalogo\Admin\ProductAdminController;
use DSM\Catalogo\Brand\Brand;
use DSM\Catalogo\Product\Product;
use DSM\Catalogo\Product\ProductStatus;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Variables recibidas desde ProductAdminController:
 *
 * @var array<int, Product> $products
 * @var array<int, Brand> $brands
 * @var int|null $storeId
 * @var int|null $customerId
 * @var string $status
 * @var string|null $notice
 * @var string|null $error
 * @var string $createUrl
 */

$totalProducts =
    count($products);

$draftProducts =
    count(
        array_filter(
            $products,
            static fn (Product $product): bool =>
                $product->getStatus()
                === ProductStatus::DRAFT
        )
    );

$activeProducts =
    count(
        array_filter(
            $products,
            static fn (Product $product): bool =>
                $product->getStatus()
                === ProductStatus::ACTIVE
        )
    );

$inactiveProducts =
    count(
        array_filter(
            $products,
            static fn (Product $product): bool =>
                $product->getStatus()
                === ProductStatus::INACTIVE
        )
    );

$archivedProducts =
    count(
        array_filter(
            $products,
            static fn (Product $product): bool =>
                $product->getStatus()
                === ProductStatus::ARCHIVED
        )
    );

$statusLabels = [
    ProductStatus::DRAFT =>
        __('Borrador', 'dsm-catalogo'),

    ProductStatus::ACTIVE =>
        __('Activo', 'dsm-catalogo'),

    ProductStatus::INACTIVE =>
        __('Inactivo', 'dsm-catalogo'),

    ProductStatus::ARCHIVED =>
        __('Archivado', 'dsm-catalogo'),
];

$statusClasses = [
    ProductStatus::DRAFT =>
        'dsm-status-warning',

    ProductStatus::ACTIVE =>
        'dsm-status-success',

    ProductStatus::INACTIVE =>
        'dsm-status-muted',

    ProductStatus::ARCHIVED =>
        'dsm-status-danger',
];

$filterUrl =
    add_query_arg(
        [
            'page' =>
                ProductAdminController::PAGE_SLUG,
        ],
        admin_url('admin.php')
    );
?>

<div class="wrap dsm-catalogo-admin">
    <div class="dsm-admin-header">
        <div>
            <h1 class="wp-heading-inline">
                <?php
                echo esc_html__(
                    'Productos',
                    'dsm-catalogo'
                );
                ?>
            </h1>

            <p class="description">
                <?php
                echo esc_html__(
                    'Gestiona los productos base de las tiendas. Las tallas, colores y existencias se gestionarán posteriormente desde sus variantes.',
                    'dsm-catalogo'
                );
                ?>
            </p>
        </div>

        <div class="dsm-admin-header-actions">
            <?php if (
                $storeId !== null
                && $customerId !== null
            ): ?>
                <a
                    href="<?php echo esc_url($createUrl); ?>"
                    class="page-title-action"
                >
                    <?php
                    echo esc_html__(
                        'Añadir producto',
                        'dsm-catalogo'
                    );
                    ?>
                </a>
            <?php endif; ?>
        </div>
    </div>

    <hr class="wp-header-end">

    <?php if ($notice !== null): ?>
        <div class="notice notice-success is-dismissible">
            <p>
                <?php echo esc_html($notice); ?>
            </p>
        </div>
    <?php endif; ?>

    <?php if ($error !== null): ?>
        <div class="notice notice-error is-dismissible">
            <p>
                <?php echo esc_html($error); ?>
            </p>
        </div>
    <?php endif; ?>

    <div class="dsm-panel">
        <div class="dsm-panel-header">
            <h2>
                <?php
                echo esc_html__(
                    'Contexto de administración',
                    'dsm-catalogo'
                );
                ?>
            </h2>
        </div>

        <div class="dsm-panel-body">
            <form
                method="get"
                action="<?php echo esc_url($filterUrl); ?>"
                class="dsm-filter-form"
            >
                <input
                    type="hidden"
                    name="page"
                    value="<?php
                    echo esc_attr(
                        ProductAdminController::PAGE_SLUG
                    );
                    ?>"
                >

                <div class="dsm-filter-grid">
                    <div class="dsm-filter-field">
                        <label for="dsm-product-store-id">
                            <strong>
                                <?php
                                echo esc_html__(
                                    'ID de tienda',
                                    'dsm-catalogo'
                                );
                                ?>
                            </strong>
                        </label>

                        <input
                            type="number"
                            id="dsm-product-store-id"
                            name="store_id"
                            value="<?php
                            echo esc_attr(
                                $storeId !== null
                                    ? (string) $storeId
                                    : ''
                            );
                            ?>"
                            min="1"
                            step="1"
                            required
                        >

                        <p class="description">
                            <?php
                            echo esc_html__(
                                'Campo temporal hasta conectar DSM Tiendas.',
                                'dsm-catalogo'
                            );
                            ?>
                        </p>
                    </div>

                    <div class="dsm-filter-field">
                        <label for="dsm-product-customer-id">
                            <strong>
                                <?php
                                echo esc_html__(
                                    'ID de cliente administrador',
                                    'dsm-catalogo'
                                );
                                ?>
                            </strong>
                        </label>

                        <input
                            type="number"
                            id="dsm-product-customer-id"
                            name="customer_id"
                            value="<?php
                            echo esc_attr(
                                $customerId !== null
                                    ? (string) $customerId
                                    : ''
                            );
                            ?>"
                            min="1"
                            step="1"
                            required
                        >

                        <p class="description">
                            <?php
                            echo esc_html__(
                                'Cliente responsable de crear o modificar los productos.',
                                'dsm-catalogo'
                            );
                            ?>
                        </p>
                    </div>

                    <div class="dsm-filter-field">
                        <label for="dsm-product-status">
                            <strong>
                                <?php
                                echo esc_html__(
                                    'Estado',
                                    'dsm-catalogo'
                                );
                                ?>
                            </strong>
                        </label>

                        <select
                            id="dsm-product-status"
                            name="status"
                        >
                            <option value="">
                                <?php
                                echo esc_html__(
                                    'Todos los estados',
                                    'dsm-catalogo'
                                );
                                ?>
                            </option>

                            <?php
                            foreach (
                                $statusLabels
                                as $statusValue => $statusLabel
                            ):
                                ?>
                                <option
                                    value="<?php
                                    echo esc_attr($statusValue);
                                    ?>"
                                    <?php
                                    selected(
                                        $status,
                                        $statusValue
                                    );
                                    ?>
                                >
                                    <?php
                                    echo esc_html($statusLabel);
                                    ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="dsm-filter-actions">
                        <?php
                        submit_button(
                            __(
                                'Aplicar filtros',
                                'dsm-catalogo'
                            ),
                            'secondary',
                            'submit',
                            false
                        );
                        ?>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <?php if ($storeId === null): ?>
        <div class="dsm-empty-state">
            <span
                class="dashicons dashicons-store"
                aria-hidden="true"
            ></span>

            <h2>
                <?php
                echo esc_html__(
                    'Selecciona una tienda',
                    'dsm-catalogo'
                );
                ?>
            </h2>

            <p>
                <?php
                echo esc_html__(
                    'Introduce el identificador de la tienda y del cliente administrador para consultar y gestionar sus productos.',
                    'dsm-catalogo'
                );
                ?>
            </p>
        </div>
    <?php else: ?>
        <div class="dsm-summary-grid">
            <div class="dsm-summary-card">
                <span class="dsm-summary-label">
                    <?php
                    echo esc_html__(
                        'Total mostrado',
                        'dsm-catalogo'
                    );
                    ?>
                </span>

                <strong class="dsm-summary-value">
                    <?php
                    echo esc_html(
                        (string) $totalProducts
                    );
                    ?>
                </strong>
            </div>

            <div class="dsm-summary-card">
                <span class="dsm-summary-label">
                    <?php
                    echo esc_html__(
                        'Activos',
                        'dsm-catalogo'
                    );
                    ?>
                </span>

                <strong class="dsm-summary-value">
                    <?php
                    echo esc_html(
                        (string) $activeProducts
                    );
                    ?>
                </strong>
            </div>

            <div class="dsm-summary-card">
                <span class="dsm-summary-label">
                    <?php
                    echo esc_html__(
                        'Borradores',
                        'dsm-catalogo'
                    );
                    ?>
                </span>

                <strong class="dsm-summary-value">
                    <?php
                    echo esc_html(
                        (string) $draftProducts
                    );
                    ?>
                </strong>
            </div>

            <div class="dsm-summary-card">
                <span class="dsm-summary-label">
                    <?php
                    echo esc_html__(
                        'Inactivos / archivados',
                        'dsm-catalogo'
                    );
                    ?>
                </span>

                <strong class="dsm-summary-value">
                    <?php
                    echo esc_html(
                        (string) (
                            $inactiveProducts
                            + $archivedProducts
                        )
                    );
                    ?>
                </strong>
            </div>
        </div>

        <?php if ($products === []): ?>
            <div class="dsm-empty-state">
                <span
                    class="dashicons dashicons-products"
                    aria-hidden="true"
                ></span>

                <h2>
                    <?php
                    echo esc_html__(
                        'No hay productos',
                        'dsm-catalogo'
                    );
                    ?>
                </h2>

                <p>
                    <?php
                    echo esc_html__(
                        'La tienda seleccionada todavía no tiene productos con los filtros indicados.',
                        'dsm-catalogo'
                    );
                    ?>
                </p>

                <?php if ($customerId !== null): ?>
                    <a
                        href="<?php echo esc_url($createUrl); ?>"
                        class="button button-primary"
                    >
                        <?php
                        echo esc_html__(
                            'Crear primer producto',
                            'dsm-catalogo'
                        );
                        ?>
                    </a>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <div class="dsm-table-wrapper">
                <table
                    class="wp-list-table widefat fixed striped table-view-list"
                >
                    <thead>
                        <tr>
                            <th
                                scope="col"
                                class="column-primary"
                            >
                                <?php
                                echo esc_html__(
                                    'Producto',
                                    'dsm-catalogo'
                                );
                                ?>
                            </th>

                            <th scope="col">
                                <?php
                                echo esc_html__(
                                    'Marca',
                                    'dsm-catalogo'
                                );
                                ?>
                            </th>

                            <th scope="col">
                                <?php
                                echo esc_html__(
                                    'Referencia',
                                    'dsm-catalogo'
                                );
                                ?>
                            </th>

                            <th scope="col">
                                <?php
                                echo esc_html__(
                                    'Precio',
                                    'dsm-catalogo'
                                );
                                ?>
                            </th>

                            <th scope="col">
                                <?php
                                echo esc_html__(
                                    'Stock',
                                    'dsm-catalogo'
                                );
                                ?>
                            </th>

                            <th scope="col">
                                <?php
                                echo esc_html__(
                                    'Estado',
                                    'dsm-catalogo'
                                );
                                ?>
                            </th>

                            <th scope="col">
                                <?php
                                echo esc_html__(
                                    'Actualizado',
                                    'dsm-catalogo'
                                );
                                ?>
                            </th>
                        </tr>
                    </thead>

                    <tbody>
                        <?php foreach ($products as $product): ?>
                            <?php
                            $editUrl =
                                ProductAdminController::getEditUrl(
                                    $product,
                                    $customerId
                                );

                            $productStatus =
                                $product->getStatus();

                            $brandId =
                                $product->getBrandId();

                            $brand =
                                $brandId !== null
                                && isset($brands[$brandId])
                                    ? $brands[$brandId]
                                    : null;

                            $internalReference =
                                $product->getInternalReference();

                            $baseSku =
                                $product->getBaseSku();

                            $description =
                                $product->getDescription();

                            $updatedAt =
                                $product->getUpdatedAt();
                            ?>

                            <tr>
                                <td
                                    class="column-primary"
                                    data-colname="<?php
                                    echo esc_attr__(
                                        'Producto',
                                        'dsm-catalogo'
                                    );
                                    ?>"
                                >
                                    <strong>
                                        <a
                                            href="<?php
                                            echo esc_url($editUrl);
                                            ?>"
                                        >
                                            <?php
                                            echo esc_html(
                                                $product->getName()
                                            );
                                            ?>
                                        </a>
                                    </strong>

                                    <?php if ($description !== null): ?>
                                        <p class="description">
                                            <?php
                                            echo esc_html(
                                                wp_trim_words(
                                                    $description,
                                                    16
                                                )
                                            );
                                            ?>
                                        </p>
                                    <?php endif; ?>

                                    <div class="row-actions">
                                        <span class="edit">
                                            <a
                                                href="<?php
                                                echo esc_url($editUrl);
                                                ?>"
                                            >
                                                <?php
                                                echo esc_html__(
                                                    'Editar',
                                                    'dsm-catalogo'
                                                );
                                                ?>
                                            </a>
                                        </span>

                                        <?php if (
                                            $customerId !== null
                                            && $productStatus
                                                !== ProductStatus::ARCHIVED
                                        ): ?>
                                            <span aria-hidden="true">
                                                |
                                            </span>

                                            <?php if (
                                                $productStatus
                                                !== ProductStatus::ACTIVE
                                            ): ?>
                                                <span class="activate">
                                                    <a
                                                        href="<?php
                                                        echo esc_url(
                                                            ProductAdminController::getStatusUrl(
                                                                $product,
                                                                ProductStatus::ACTIVE,
                                                                $customerId
                                                            )
                                                        );
                                                        ?>"
                                                    >
                                                        <?php
                                                        echo esc_html__(
                                                            'Activar',
                                                            'dsm-catalogo'
                                                        );
                                                        ?>
                                                    </a>
                                                </span>
                                            <?php else: ?>
                                                <span class="deactivate">
                                                    <a
                                                        href="<?php
                                                        echo esc_url(
                                                            ProductAdminController::getStatusUrl(
                                                                $product,
                                                                ProductStatus::INACTIVE,
                                                                $customerId
                                                            )
                                                        );
                                                        ?>"
                                                    >
                                                        <?php
                                                        echo esc_html__(
                                                            'Desactivar',
                                                            'dsm-catalogo'
                                                        );
                                                        ?>
                                                    </a>
                                                </span>
                                            <?php endif; ?>

                                            <span aria-hidden="true">
                                                |
                                            </span>

                                            <span class="archive">
                                                <a
                                                    href="<?php
                                                    echo esc_url(
                                                        ProductAdminController::getStatusUrl(
                                                            $product,
                                                            ProductStatus::ARCHIVED,
                                                            $customerId
                                                        )
                                                    );
                                                    ?>"
                                                    data-dsm-confirm="<?php
                                                    echo esc_attr__(
                                                        '¿Seguro que quieres archivar este producto? Un producto archivado no se podrá reactivar.',
                                                        'dsm-catalogo'
                                                    );
                                                    ?>"
                                                    class="submitdelete"
                                                >
                                                    <?php
                                                    echo esc_html__(
                                                        'Archivar',
                                                        'dsm-catalogo'
                                                    );
                                                    ?>
                                                </a>
                                            </span>
                                        <?php endif; ?>
                                    </div>

                                    <button
                                        type="button"
                                        class="toggle-row"
                                    >
                                        <span class="screen-reader-text">
                                            <?php
                                            echo esc_html__(
                                                'Mostrar más detalles',
                                                'dsm-catalogo'
                                            );
                                            ?>
                                        </span>
                                    </button>
                                </td>

                                <td
                                    data-colname="<?php
                                    echo esc_attr__(
                                        'Marca',
                                        'dsm-catalogo'
                                    );
                                    ?>"
                                >
                                    <?php if ($brand instanceof Brand): ?>
                                        <?php
                                        echo esc_html(
                                            $brand->getName()
                                        );
                                        ?>
                                    <?php else: ?>
                                        <span aria-hidden="true">
                                            —
                                        </span>
                                    <?php endif; ?>
                                </td>

                                <td
                                    data-colname="<?php
                                    echo esc_attr__(
                                        'Referencia',
                                        'dsm-catalogo'
                                    );
                                    ?>"
                                >
                                    <?php if (
                                        $internalReference !== null
                                    ): ?>
                                        <div>
                                            <code>
                                                <?php
                                                echo esc_html(
                                                    $internalReference
                                                );
                                                ?>
                                            </code>
                                        </div>
                                    <?php endif; ?>

                                    <?php if ($baseSku !== null): ?>
                                        <div>
                                            <small>
                                                <?php
                                                echo esc_html__(
                                                    'SKU:',
                                                    'dsm-catalogo'
                                                );
                                                ?>

                                                <code>
                                                    <?php
                                                    echo esc_html(
                                                        $baseSku
                                                    );
                                                    ?>
                                                </code>
                                            </small>
                                        </div>
                                    <?php endif; ?>

                                    <?php if (
                                        $internalReference === null
                                        && $baseSku === null
                                    ): ?>
                                        <span aria-hidden="true">
                                            —
                                        </span>
                                    <?php endif; ?>
                                </td>

                                <td
                                    data-colname="<?php
                                    echo esc_attr__(
                                        'Precio',
                                        'dsm-catalogo'
                                    );
                                    ?>"
                                >
                                    <strong>
                                        <?php
                                        echo esc_html(
                                            number_format_i18n(
                                                $product->getDefaultPrice(),
                                                2
                                            )
                                        );
                                        ?>

                                        €
                                    </strong>

                                    <?php if (
                                        $product->getOriginalPrice()
                                        !== null
                                    ): ?>
                                        <div>
                                            <small>
                                                <del>
                                                    <?php
                                                    echo esc_html(
                                                        number_format_i18n(
                                                            $product->getOriginalPrice(),
                                                            2
                                                        )
                                                    );
                                                    ?>

                                                    €
                                                </del>
                                            </small>
                                        </div>
                                    <?php endif; ?>
                                </td>

                                <td
                                    data-colname="<?php
                                    echo esc_attr__(
                                        'Stock',
                                        'dsm-catalogo'
                                    );
                                    ?>"
                                >
                                    <?php if ($product->tracksStock()): ?>
                                        <span class="dsm-status dsm-status-info">
                                            <?php
                                            echo esc_html__(
                                                'Controlado',
                                                'dsm-catalogo'
                                            );
                                            ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="dsm-status dsm-status-muted">
                                            <?php
                                            echo esc_html__(
                                                'Sin control',
                                                'dsm-catalogo'
                                            );
                                            ?>
                                        </span>
                                    <?php endif; ?>
                                </td>

                                <td
                                    data-colname="<?php
                                    echo esc_attr__(
                                        'Estado',
                                        'dsm-catalogo'
                                    );
                                    ?>"
                                >
                                    <span
                                        class="dsm-status <?php
                                        echo esc_attr(
                                            $statusClasses[$productStatus]
                                            ?? 'dsm-status-muted'
                                        );
                                        ?>"
                                    >
                                        <?php
                                        echo esc_html(
                                            $statusLabels[$productStatus]
                                            ?? $productStatus
                                        );
                                        ?>
                                    </span>
                                </td>

                                <td
                                    data-colname="<?php
                                    echo esc_attr__(
                                        'Actualizado',
                                        'dsm-catalogo'
                                    );
                                    ?>"
                                >
                                    <?php
                                    echo esc_html(
                                        $updatedAt->format(
                                            'd/m/Y H:i'
                                        )
                                    );
                                    ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</div>