<?php

declare(strict_types=1);

use DSM\Catalogo\Admin\ProductAdminController;
use DSM\Catalogo\Brand\Brand;
use DSM\Catalogo\Product\Product;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Variables recibidas desde ProductAdminController:
 *
 * @var Product|null $product
 * @var array<int, Brand> $brands
 * @var int|null $storeId
 * @var int|null $customerId
 * @var string|null $notice
 * @var string|null $error
 * @var string $formAction
 * @var string $listUrl
 */

$isEditing =
    $product instanceof Product;

$pageTitle =
    $isEditing
        ? __('Editar producto', 'dsm-catalogo')
        : __('Añadir producto', 'dsm-catalogo');

$saveButtonText =
    $isEditing
        ? __('Guardar cambios', 'dsm-catalogo')
        : __('Crear producto', 'dsm-catalogo');

$productId =
    $isEditing
        ? $product->getId()
        : 0;

if (
    $isEditing
    && $storeId === null
) {
    $storeId =
        $product->getStoreId();
}

$generalTemplate =
    DSM_CATALOGO_PATH
    . 'templates/admin/product-form-general.php';

$commercialTemplate =
    DSM_CATALOGO_PATH
    . 'templates/admin/product-form-commercial.php';

$sidebarTemplate =
    DSM_CATALOGO_PATH
    . 'templates/admin/product-form-sidebar.php';

foreach (
    [
        $generalTemplate,
        $commercialTemplate,
        $sidebarTemplate,
    ]
    as $partialTemplate
) {
    if (!is_file($partialTemplate)) {
        throw new RuntimeException(
            sprintf(
                'No se encontró la plantilla parcial del producto: %s',
                $partialTemplate
            )
        );
    }
}
?>

<div class="wrap dsm-catalogo-admin">
    <div class="dsm-admin-header">
        <div>
            <h1 class="wp-heading-inline">
                <?php echo esc_html($pageTitle); ?>
            </h1>

            <p class="description">
                <?php
                echo esc_html__(
                    'Define la información base del producto. Las tallas, colores, fotografías y existencias se administrarán posteriormente desde sus variantes.',
                    'dsm-catalogo'
                );
                ?>
            </p>
        </div>

        <div class="dsm-admin-header-actions">
            <a
                href="<?php echo esc_url($listUrl); ?>"
                class="page-title-action"
            >
                <?php
                echo esc_html__(
                    'Volver a productos',
                    'dsm-catalogo'
                );
                ?>
            </a>
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

    <?php if ($storeId === null): ?>
        <div class="notice notice-error">
            <p>
                <?php
                echo esc_html__(
                    'Debes indicar una tienda válida antes de crear un producto.',
                    'dsm-catalogo'
                );
                ?>
            </p>
        </div>
    <?php endif; ?>

    <?php if ($customerId === null): ?>
        <div class="notice notice-error">
            <p>
                <?php
                echo esc_html__(
                    'Debes indicar el cliente administrador responsable de la operación.',
                    'dsm-catalogo'
                );
                ?>
            </p>
        </div>
    <?php endif; ?>

    <form
        method="post"
        action="<?php echo esc_url($formAction); ?>"
        class="dsm-admin-form"
    >
        <input
            type="hidden"
            name="action"
            value="<?php
            echo esc_attr(
                ProductAdminController::getSaveAction()
            );
            ?>"
        >

        <input
            type="hidden"
            name="product_id"
            value="<?php
            echo esc_attr(
                (string) $productId
            );
            ?>"
        >

        <input
            type="hidden"
            name="store_id"
            value="<?php
            echo esc_attr(
                $storeId !== null
                    ? (string) $storeId
                    : ''
            );
            ?>"
        >

        <input
            type="hidden"
            name="customer_id"
            value="<?php
            echo esc_attr(
                $customerId !== null
                    ? (string) $customerId
                    : ''
            );
            ?>"
        >

        <?php
        wp_nonce_field(
            ProductAdminController::getSaveNonceAction(),
            ProductAdminController::getSaveNonceField()
        );
        ?>

        <div class="dsm-form-layout">
            <main class="dsm-form-main">
                <?php require $generalTemplate; ?>

                <?php require $commercialTemplate; ?>
            </main>

            <aside class="dsm-form-sidebar">
                <?php require $sidebarTemplate; ?>
            </aside>
        </div>
    </form>
</div>