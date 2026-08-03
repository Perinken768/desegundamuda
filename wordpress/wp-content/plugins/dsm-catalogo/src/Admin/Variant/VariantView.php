<?php

declare(strict_types=1);

namespace DSM\Catalogo\Admin\Variant;

use DSM\Catalogo\Admin\ProductAdminController;
use DSM\Catalogo\Admin\VariantAdminController;
use wpdb;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Vista administrativa de variantes comerciales.
 *
 * No muestra ni permite editar stock, reservas o movimientos.
 */
final class VariantView
{
    private const CAPABILITY =
        'manage_options';

    private wpdb $database;

    private string $variantsTable;

    private string $productsTable;

    public function __construct()
    {
        global $wpdb;

        $this->database = $wpdb;

        $this->variantsTable =
            $wpdb->prefix
            . 'dsm_product_variants';

        $this->productsTable =
            $wpdb->prefix
            . 'dsm_products';
    }

    public function render(): void
    {
        $this->assertPermission();

        $action =
            $this->getRequestedAction();

        if ($action === 'new') {
            $this->renderCreateForm();

            return;
        }

        if ($action === 'edit') {
            $this->renderEditForm();

            return;
        }

        $this->renderList();
    }

    private function renderList(): void
    {
        $productId =
            $this->getRequestedProductId();

        $product =
            $productId > 0
                ? $this->findProduct(
                    $productId
                )
                : null;

        if (
            $productId > 0
            && $product === null
        ) {
            $productId = 0;
        }

        $list =
            new VariantList(
                $productId > 0
                    ? $productId
                    : null
            );

        $list->prepare_items();

        ?>
        <div class="wrap dsm-catalogo-admin">
            <div class="dsm-admin-header">
                <div>
                    <h1>
                        <?php
                        if ($product !== null) {
                            printf(
                                esc_html__(
                                    'Variantes de %s',
                                    'dsm-catalogo'
                                ),
                                esc_html(
                                    (string) $product[
                                        'name'
                                    ]
                                )
                            );
                        } else {
                            esc_html_e(
                                'Variantes',
                                'dsm-catalogo'
                            );
                        }
                        ?>
                    </h1>

                    <p class="description">
                        <?php
                        esc_html_e(
                            'Gestiona las tallas, colores, referencias y precios de los productos. El inventario será gestionado desde DSM Multitienda.',
                            'dsm-catalogo'
                        );
                        ?>
                    </p>
                </div>

                <div class="dsm-admin-header-actions">
                    <a
                        class="page-title-action"
                        href="<?php echo esc_url(
                            VariantAdminController::
                                getCreateUrl(
                                    $productId
                                )
                        ); ?>"
                    >
                        <?php
                        esc_html_e(
                            'Añadir variante',
                            'dsm-catalogo'
                        );
                        ?>
                    </a>

                    <?php if ($product !== null) : ?>
                        <a
                            class="button button-secondary"
                            href="<?php echo esc_url(
                                $this->getProductEditUrl(
                                    (int) $product['id']
                                )
                            ); ?>"
                        >
                            <?php
                            esc_html_e(
                                'Volver al producto',
                                'dsm-catalogo'
                            );
                            ?>
                        </a>
                    <?php endif; ?>
                </div>
            </div>

            <hr class="wp-header-end">

            <?php
            $this->renderNotice();
            ?>

            <form method="get">
                <input
                    type="hidden"
                    name="page"
                    value="<?php echo esc_attr(
                        VariantAdminController::
                            getMenuSlug()
                    ); ?>"
                >

                <?php if ($productId > 0) : ?>
                    <input
                        type="hidden"
                        name="product_id"
                        value="<?php echo esc_attr(
                            (string) $productId
                        ); ?>"
                    >
                <?php endif; ?>

                <?php
                $list->search_box(
                    __(
                        'Buscar variantes',
                        'dsm-catalogo'
                    ),
                    'dsm-catalogo-variants'
                );
                ?>

                <div class="dsm-table-wrapper">
                    <?php
                    $list->display();
                    ?>
                </div>
            </form>
        </div>
        <?php
    }

    private function renderCreateForm(): void
    {
        $productId =
            $this->getRequestedProductId();

        $product =
            $productId > 0
                ? $this->findProduct(
                    $productId
                )
                : null;

        if (
            $productId > 0
            && $product === null
        ) {
            $this->renderErrorPage(
                __(
                    'El producto indicado no existe.',
                    'dsm-catalogo'
                )
            );

            return;
        }

        $products =
            $this->findAvailableProducts();

        if ($products === []) {
            $this->renderNoProductsPage();

            return;
        }

        $variant = [
            'id' =>
                0,

            'product_id' =>
                $productId,

            'sku' =>
                '',

            'barcode' =>
                '',

            'size_value' =>
                '',

            'color_value' =>
                '',

            'condition_code' =>
                '',

            'price' =>
                $product !== null
                    ? $product[
                        'default_price'
                    ]
                    : '',

            'original_price' =>
                $product[
                    'original_price'
                ]
                ?? '',

            'cost_price' =>
                $product[
                    'cost_price'
                ]
                ?? '',

            'is_default' =>
                0,

            'is_active' =>
                1,

            'sort_order' =>
                0,

            'created_at' =>
                null,

            'updated_at' =>
                null,

            'archived_at' =>
                null,
        ];

        $this->renderForm(
            $variant,
            $products,
            false
        );
    }

    private function renderEditForm(): void
    {
        $variantId =
            $this->getRequestedVariantId();

        if ($variantId <= 0) {
            $this->renderErrorPage(
                __(
                    'No se ha indicado una variante válida.',
                    'dsm-catalogo'
                )
            );

            return;
        }

        $variant =
            $this->findVariant(
                $variantId
            );

        if ($variant === null) {
            $this->renderErrorPage(
                __(
                    'La variante solicitada no existe.',
                    'dsm-catalogo'
                )
            );

            return;
        }

        $products =
            $this->findAvailableProducts(
                (int) $variant[
                    'product_id'
                ]
            );

        $this->renderForm(
            $variant,
            $products,
            true
        );
    }

    /**
     * @param array<string, mixed> $variant
     * @param array<int, array<string, mixed>> $products
     */
    private function renderForm(
        array $variant,
        array $products,
        bool $isEditing
    ): void {
        $variantId =
            (int) (
                $variant['id']
                ?? 0
            );

        $productId =
            (int) (
                $variant['product_id']
                ?? 0
            );

        $isArchived =
            !empty(
                $variant['archived_at']
            );

        $cancelUrl =
            VariantAdminController::
                getListUrl(
                    $productId > 0
                        ? [
                            'product_id' =>
                                $productId,
                        ]
                        : []
                );

        ?>
        <div class="wrap dsm-catalogo-admin">
            <div class="dsm-admin-header">
                <div>
                    <h1>
                        <?php
                        echo esc_html(
                            $isEditing
                                ? __(
                                    'Editar variante',
                                    'dsm-catalogo'
                                )
                                : __(
                                    'Añadir variante',
                                    'dsm-catalogo'
                                )
                        );
                        ?>
                    </h1>

                    <p class="description">
                        <?php
                        esc_html_e(
                            'Define las características comerciales de la variante. El stock se configurará posteriormente desde DSM Multitienda.',
                            'dsm-catalogo'
                        );
                        ?>
                    </p>
                </div>

                <div class="dsm-admin-header-actions">
                    <a
                        class="button button-secondary"
                        href="<?php echo esc_url(
                            $cancelUrl
                        ); ?>"
                    >
                        <?php
                        esc_html_e(
                            'Volver al listado',
                            'dsm-catalogo'
                        );
                        ?>
                    </a>
                </div>
            </div>

            <hr class="wp-header-end">

            <?php
            $this->renderNotice();

            if ($isArchived) {
                ?>
                <div class="notice notice-warning inline">
                    <p>
                        <?php
                        esc_html_e(
                            'Esta variante está archivada y se muestra en modo de consulta.',
                            'dsm-catalogo'
                        );
                        ?>
                    </p>
                </div>
                <?php
            }
            ?>

            <form
                class="dsm-admin-form"
                method="post"
                action="<?php echo esc_url(
                    admin_url('admin-post.php')
                ); ?>"
            >
                <input
                    type="hidden"
                    name="action"
                    value="<?php echo esc_attr(
                        VariantAdminController::
                            getSaveAction()
                    ); ?>"
                >

                <input
                    type="hidden"
                    name="variant_id"
                    value="<?php echo esc_attr(
                        (string) $variantId
                    ); ?>"
                >

                <?php
                wp_nonce_field(
                    VariantAdminController::
                        getNonceAction(),

                    VariantAdminController::
                        getNonceField()
                );
                ?>

                <div class="dsm-form-layout">
                    <div class="dsm-form-main">
                        <?php
                        $this->renderIdentificationPanel(
                            $variant,
                            $products,
                            $isEditing,
                            $isArchived
                        );

                        $this->renderCommercialPanel(
                            $variant,
                            $isArchived
                        );
                        ?>
                    </div>

                    <aside class="dsm-form-sidebar">
                        <?php
                        $this->renderStatusPanel(
                            $variant,
                            $isEditing,
                            $isArchived
                        );

                        if ($isEditing) {
                            $this->renderMetadataPanel(
                                $variant
                            );
                        }
                        ?>
                    </aside>
                </div>

                <?php if (!$isArchived) : ?>
                    <p class="submit">
                        <?php
                        submit_button(
                            $isEditing
                                ? __(
                                    'Guardar cambios',
                                    'dsm-catalogo'
                                )
                                : __(
                                    'Crear variante',
                                    'dsm-catalogo'
                                ),
                            'primary',
                            'submit',
                            false
                        );
                        ?>

                        <a
                            class="button button-secondary"
                            href="<?php echo esc_url(
                                $cancelUrl
                            ); ?>"
                        >
                            <?php
                            esc_html_e(
                                'Cancelar',
                                'dsm-catalogo'
                            );
                            ?>
                        </a>
                    </p>
                <?php endif; ?>
            </form>
        </div>
        <?php
    }

    /**
     * @param array<string, mixed> $variant
     * @param array<int, array<string, mixed>> $products
     */
    private function renderIdentificationPanel(
        array $variant,
        array $products,
        bool $isEditing,
        bool $disabled
    ): void {
        $selectedProductId =
            (int) (
                $variant['product_id']
                ?? 0
            );

        ?>
        <section class="dsm-panel">
            <div class="dsm-panel-header">
                <h2>
                    <?php
                    esc_html_e(
                        'Identificación',
                        'dsm-catalogo'
                    );
                    ?>
                </h2>
            </div>

            <div class="dsm-panel-body">
                <table class="form-table">
                    <tbody>
                        <tr>
                            <th>
                                <label for="dsm-variant-product">
                                    <?php
                                    esc_html_e(
                                        'Producto',
                                        'dsm-catalogo'
                                    );
                                    ?>
                                </label>
                            </th>

                            <td>
                                <?php if ($isEditing) : ?>
                                    <input
                                        type="hidden"
                                        name="product_id"
                                        value="<?php echo esc_attr(
                                            (string) $selectedProductId
                                        ); ?>"
                                    >
                                <?php endif; ?>

                                <select
                                    id="dsm-variant-product"
                                    name="<?php echo esc_attr(
                                        $isEditing
                                            ? 'product_display'
                                            : 'product_id'
                                    ); ?>"
                                    required
                                    <?php
                                    disabled(
                                        $isEditing
                                        || $disabled
                                    );
                                    ?>
                                >
                                    <option value="">
                                        <?php
                                        esc_html_e(
                                            'Selecciona un producto',
                                            'dsm-catalogo'
                                        );
                                        ?>
                                    </option>

                                    <?php foreach (
                                        $products as $product
                                    ) : ?>
                                        <option
                                            value="<?php echo esc_attr(
                                                (string) $product['id']
                                            ); ?>"
                                            <?php selected(
                                                $selectedProductId,
                                                (int) $product['id']
                                            ); ?>
                                        >
                                            <?php echo esc_html(
                                                $this->buildProductLabel(
                                                    $product
                                                )
                                            ); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </td>
                        </tr>

                        <?php
                        $this->renderTextRow(
                            'dsm-variant-sku',
                            'sku',
                            __('SKU', 'dsm-catalogo'),
                            $variant['sku']
                                ?? '',
                            120,
                            $disabled
                        );

                        $this->renderTextRow(
                            'dsm-variant-barcode',
                            'barcode',
                            __('Código de barras', 'dsm-catalogo'),
                            $variant['barcode']
                                ?? '',
                            120,
                            $disabled
                        );

                        $this->renderTextRow(
                            'dsm-variant-size',
                            'size_value',
                            __('Talla', 'dsm-catalogo'),
                            $variant['size_value']
                                ?? '',
                            80,
                            $disabled
                        );

                        $this->renderTextRow(
                            'dsm-variant-color',
                            'color_value',
                            __('Color', 'dsm-catalogo'),
                            $variant['color_value']
                                ?? '',
                            100,
                            $disabled
                        );
                        ?>

                        <tr>
                            <th>
                                <label for="dsm-variant-condition">
                                    <?php
                                    esc_html_e(
                                        'Condición',
                                        'dsm-catalogo'
                                    );
                                    ?>
                                </label>
                            </th>

                            <td>
                                <select
                                    id="dsm-variant-condition"
                                    name="condition_code"
                                    <?php disabled($disabled); ?>
                                >
                                    <option value="">
                                        <?php
                                        esc_html_e(
                                            'Sin especificar',
                                            'dsm-catalogo'
                                        );
                                        ?>
                                    </option>

                                    <?php foreach (
                                        $this->getConditions()
                                        as $code => $label
                                    ) : ?>
                                        <option
                                            value="<?php echo esc_attr(
                                                $code
                                            ); ?>"
                                            <?php selected(
                                                (string) (
                                                    $variant[
                                                        'condition_code'
                                                    ]
                                                    ?? ''
                                                ),
                                                $code
                                            ); ?>
                                        >
                                            <?php echo esc_html(
                                                $label
                                            ); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </section>
        <?php
    }

    /**
     * @param array<string, mixed> $variant
     */
    private function renderCommercialPanel(
        array $variant,
        bool $disabled
    ): void {
        ?>
        <section class="dsm-panel">
            <div class="dsm-panel-header">
                <h2>
                    <?php
                    esc_html_e(
                        'Información comercial',
                        'dsm-catalogo'
                    );
                    ?>
                </h2>
            </div>

            <div class="dsm-panel-body">
                <table class="form-table">
                    <tbody>
                        <?php
                        $this->renderMoneyRow(
                            'dsm-variant-price',
                            'price',
                            __('Precio de venta', 'dsm-catalogo'),
                            $variant['price']
                                ?? '',
                            $disabled
                        );

                        $this->renderMoneyRow(
                            'dsm-variant-original-price',
                            'original_price',
                            __('Precio original', 'dsm-catalogo'),
                            $variant['original_price']
                                ?? '',
                            $disabled
                        );

                        $this->renderMoneyRow(
                            'dsm-variant-cost-price',
                            'cost_price',
                            __('Precio de coste', 'dsm-catalogo'),
                            $variant['cost_price']
                                ?? '',
                            $disabled
                        );
                        ?>
                    </tbody>
                </table>
            </div>
        </section>
        <?php
    }

    /**
     * @param array<string, mixed> $variant
     */
    private function renderStatusPanel(
        array $variant,
        bool $isEditing,
        bool $disabled
    ): void {
        ?>
        <section class="dsm-panel">
            <div class="dsm-panel-header">
                <h2>
                    <?php
                    esc_html_e(
                        'Estado',
                        'dsm-catalogo'
                    );
                    ?>
                </h2>
            </div>

            <div class="dsm-panel-body">
                <fieldset class="dsm-checkbox-group">
                    <label>
                        <input
                            name="is_active"
                            type="checkbox"
                            value="1"
                            <?php
                            checked(
                                (int) (
                                    $variant[
                                        'is_active'
                                    ]
                                    ?? 0
                                ),
                                1
                            );

                            disabled($disabled);
                            ?>
                        >

                        <span>
                            <strong>
                                <?php
                                esc_html_e(
                                    'Variante activa',
                                    'dsm-catalogo'
                                );
                                ?>
                            </strong>
                        </span>
                    </label>

                    <label>
                        <input
                            name="is_default"
                            type="checkbox"
                            value="1"
                            <?php
                            checked(
                                (int) (
                                    $variant[
                                        'is_default'
                                    ]
                                    ?? 0
                                ),
                                1
                            );

                            disabled($disabled);
                            ?>
                        >

                        <span>
                            <strong>
                                <?php
                                esc_html_e(
                                    'Variante predeterminada',
                                    'dsm-catalogo'
                                );
                                ?>
                            </strong>
                        </span>
                    </label>
                </fieldset>

                <hr>

                <label for="dsm-variant-sort-order">
                    <strong>
                        <?php
                        esc_html_e(
                            'Orden',
                            'dsm-catalogo'
                        );
                        ?>
                    </strong>
                </label>

                <p>
                    <input
                        id="dsm-variant-sort-order"
                        class="small-text"
                        name="sort_order"
                        type="number"
                        min="0"
                        step="1"
                        value="<?php echo esc_attr(
                            (string) (
                                $variant[
                                    'sort_order'
                                ]
                                ?? 0
                            )
                        ); ?>"
                        <?php disabled($disabled); ?>
                    >
                </p>
            </div>

            <?php if (!$disabled) : ?>
                <div class="dsm-panel-footer">
                    <?php
                    submit_button(
                        $isEditing
                            ? __(
                                'Guardar variante',
                                'dsm-catalogo'
                            )
                            : __(
                                'Crear variante',
                                'dsm-catalogo'
                            ),
                        'primary',
                        'submit',
                        false
                    );
                    ?>
                </div>
            <?php endif; ?>
        </section>
        <?php
    }

    /**
     * @param array<string, mixed> $variant
     */
    private function renderMetadataPanel(
        array $variant
    ): void {
        ?>
        <section class="dsm-panel">
            <div class="dsm-panel-header">
                <h2>
                    <?php
                    esc_html_e(
                        'Información',
                        'dsm-catalogo'
                    );
                    ?>
                </h2>
            </div>

            <div class="dsm-panel-body">
                <dl class="dsm-meta-list">
                    <div>
                        <dt>ID</dt>

                        <dd>
                            <?php echo esc_html(
                                (string) $variant['id']
                            ); ?>
                        </dd>
                    </div>

                    <div>
                        <dt>
                            <?php
                            esc_html_e(
                                'Creada',
                                'dsm-catalogo'
                            );
                            ?>
                        </dt>

                        <dd>
                            <?php echo esc_html(
                                $this->formatDate(
                                    $variant[
                                        'created_at'
                                    ]
                                    ?? null
                                )
                            ); ?>
                        </dd>
                    </div>

                    <div>
                        <dt>
                            <?php
                            esc_html_e(
                                'Actualizada',
                                'dsm-catalogo'
                            );
                            ?>
                        </dt>

                        <dd>
                            <?php echo esc_html(
                                $this->formatDate(
                                    $variant[
                                        'updated_at'
                                    ]
                                    ?? null
                                )
                            ); ?>
                        </dd>
                    </div>
                </dl>
            </div>
        </section>
        <?php
    }

    private function renderTextRow(
        string $id,
        string $name,
        string $label,
        mixed $value,
        int $maxlength,
        bool $disabled
    ): void {
        ?>
        <tr>
            <th>
                <label for="<?php echo esc_attr($id); ?>">
                    <?php echo esc_html($label); ?>
                </label>
            </th>

            <td>
                <input
                    id="<?php echo esc_attr($id); ?>"
                    class="regular-text"
                    name="<?php echo esc_attr($name); ?>"
                    type="text"
                    maxlength="<?php echo esc_attr(
                        (string) $maxlength
                    ); ?>"
                    value="<?php echo esc_attr(
                        (string) $value
                    ); ?>"
                    <?php disabled($disabled); ?>
                >
            </td>
        </tr>
        <?php
    }

    private function renderMoneyRow(
        string $id,
        string $name,
        string $label,
        mixed $value,
        bool $disabled
    ): void {
        ?>
        <tr>
            <th>
                <label for="<?php echo esc_attr($id); ?>">
                    <?php echo esc_html($label); ?>
                </label>
            </th>

            <td>
                <span class="dsm-input-suffix">
                    <input
                        id="<?php echo esc_attr($id); ?>"
                        name="<?php echo esc_attr($name); ?>"
                        type="number"
                        min="0"
                        step="0.01"
                        value="<?php echo esc_attr(
                            $this->formatDecimal(
                                $value
                            )
                        ); ?>"
                        <?php disabled($disabled); ?>
                    >

                    <span aria-hidden="true">€</span>
                </span>
            </td>
        </tr>
        <?php
    }

    private function renderNotice(): void
    {
        $status =
            isset($_GET['dsm_status'])
                ? sanitize_key(
                    wp_unslash(
                        (string) $_GET[
                            'dsm_status'
                        ]
                    )
                )
                : '';

        $notices = [
            'variant_created' => [
                'success',
                __(
                    'La variante se creó correctamente.',
                    'dsm-catalogo'
                ),
            ],

            'variant_updated' => [
                'success',
                __(
                    'La variante se actualizó correctamente.',
                    'dsm-catalogo'
                ),
            ],

            'variant_activated' => [
                'success',
                __(
                    'La variante se activó.',
                    'dsm-catalogo'
                ),
            ],

            'variant_deactivated' => [
                'success',
                __(
                    'La variante se desactivó.',
                    'dsm-catalogo'
                ),
            ],

            'variant_defaulted' => [
                'success',
                __(
                    'La variante se estableció como predeterminada.',
                    'dsm-catalogo'
                ),
            ],

            'variant_archived' => [
                'success',
                __(
                    'La variante se archivó.',
                    'dsm-catalogo'
                ),
            ],

            'variant_error' => [
                'error',
                __(
                    'No se pudo guardar la variante.',
                    'dsm-catalogo'
                ),
            ],

            'status_error' => [
                'error',
                __(
                    'No se pudo cambiar el estado.',
                    'dsm-catalogo'
                ),
            ],

            'default_error' => [
                'error',
                __(
                    'No se pudo cambiar la variante predeterminada.',
                    'dsm-catalogo'
                ),
            ],

            'archive_error' => [
                'error',
                __(
                    'No se pudo archivar la variante.',
                    'dsm-catalogo'
                ),
            ],
        ];

        if (!isset($notices[$status])) {
            return;
        }

        [$type, $message] =
            $notices[$status];

        if ($status === 'variant_error') {
            $storedError =
                VariantSaveAction::
                    getLastError();

            if ($storedError !== '') {
                $message = $storedError;
            }
        }

        ?>
        <div
            class="<?php echo esc_attr(
                'notice notice-'
                . $type
                . ' is-dismissible'
            ); ?>"
        >
            <p><?php echo esc_html($message); ?></p>
        </div>
        <?php
    }

    private function renderErrorPage(
        string $message
    ): void {
        ?>
        <div class="wrap">
            <h1>
                <?php
                esc_html_e(
                    'Variantes',
                    'dsm-catalogo'
                );
                ?>
            </h1>

            <div class="notice notice-error">
                <p><?php echo esc_html($message); ?></p>
            </div>

            <a
                class="button button-primary"
                href="<?php echo esc_url(
                    VariantAdminController::
                        getListUrl()
                ); ?>"
            >
                <?php
                esc_html_e(
                    'Volver al listado',
                    'dsm-catalogo'
                );
                ?>
            </a>
        </div>
        <?php
    }

    private function renderNoProductsPage(): void
    {
        ?>
        <div class="wrap dsm-catalogo-admin">
            <h1>
                <?php
                esc_html_e(
                    'Añadir variante',
                    'dsm-catalogo'
                );
                ?>
            </h1>

            <div class="dsm-empty-state">
                <h2>
                    <?php
                    esc_html_e(
                        'Primero debes crear un producto',
                        'dsm-catalogo'
                    );
                    ?>
                </h2>

                <a
                    class="button button-primary"
                    href="<?php echo esc_url(
                        add_query_arg(
                            [
                                'page' =>
                                    ProductAdminController::
                                        PAGE_SLUG,

                                'view' =>
                                    'create',
                            ],
                            admin_url('admin.php')
                        )
                    ); ?>"
                >
                    <?php
                    esc_html_e(
                        'Crear producto',
                        'dsm-catalogo'
                    );
                    ?>
                </a>
            </div>
        </div>
        <?php
    }

    /**
     * @return array<string, mixed>|null
     */
    private function findVariant(
        int $variantId
    ): ?array {
        $sql =
            $this->database->prepare(
                "
                SELECT
                    id,
                    product_id,
                    sku,
                    barcode,
                    size_value,
                    color_value,
                    condition_code,
                    price,
                    original_price,
                    cost_price,
                    is_default,
                    is_active,
                    sort_order,
                    created_at,
                    updated_at,
                    archived_at
                FROM {$this->variantsTable}
                WHERE id = %d
                LIMIT 1
                ",
                $variantId
            );

        if (!is_string($sql)) {
            return null;
        }

        $variant =
            $this->database->get_row(
                $sql,
                ARRAY_A
            );

        return is_array($variant)
            ? $variant
            : null;
    }

    /**
     * @return array<string, mixed>|null
     */
    private function findProduct(
        int $productId
    ): ?array {
        $sql =
            $this->database->prepare(
                "
                SELECT
                    id,
                    name,
                    internal_reference,
                    base_sku,
                    default_price,
                    original_price,
                    cost_price,
                    status,
                    archived_at
                FROM {$this->productsTable}
                WHERE id = %d
                LIMIT 1
                ",
                $productId
            );

        if (!is_string($sql)) {
            return null;
        }

        $product =
            $this->database->get_row(
                $sql,
                ARRAY_A
            );

        return is_array($product)
            ? $product
            : null;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function findAvailableProducts(
        int $includeProductId = 0
    ): array {
        if ($includeProductId > 0) {
            $sql =
                $this->database->prepare(
                    "
                    SELECT
                        id,
                        name,
                        internal_reference,
                        base_sku,
                        default_price,
                        original_price,
                        cost_price,
                        status,
                        archived_at
                    FROM {$this->productsTable}
                    WHERE archived_at IS NULL
                        OR id = %d
                    ORDER BY name ASC,
                        id ASC
                    ",
                    $includeProductId
                );
        } else {
            $sql = "
                SELECT
                    id,
                    name,
                    internal_reference,
                    base_sku,
                    default_price,
                    original_price,
                    cost_price,
                    status,
                    archived_at
                FROM {$this->productsTable}
                WHERE archived_at IS NULL
                ORDER BY name ASC,
                    id ASC
            ";
        }

        if (!is_string($sql)) {
            return [];
        }

        $products =
            $this->database->get_results(
                $sql,
                ARRAY_A
            );

        return is_array($products)
            ? $products
            : [];
    }

    /**
     * @param array<string, mixed> $product
     */
    private function buildProductLabel(
        array $product
    ): string {
        $label =
            trim(
                (string) (
                    $product['name']
                    ?? ''
                )
            );

        $reference =
            trim(
                (string) (
                    $product[
                        'internal_reference'
                    ]
                    ?? ''
                )
            );

        $sku =
            trim(
                (string) (
                    $product['base_sku']
                    ?? ''
                )
            );

        if ($reference !== '') {
            $label .=
                ' — '
                . $reference;
        } elseif ($sku !== '') {
            $label .=
                ' — '
                . $sku;
        }

        return $label;
    }

    /**
     * @return array<string, string>
     */
    private function getConditions(): array
    {
        return [
            'new_with_tags' =>
                __('Nuevo con etiquetas', 'dsm-catalogo'),

            'new_without_tags' =>
                __('Nuevo sin etiquetas', 'dsm-catalogo'),

            'very_good' =>
                __('Muy buen estado', 'dsm-catalogo'),

            'good' =>
                __('Buen estado', 'dsm-catalogo'),

            'satisfactory' =>
                __('Estado satisfactorio', 'dsm-catalogo'),
        ];
    }

    private function getRequestedAction(): string
    {
        $action =
            isset($_GET['action'])
                ? sanitize_key(
                    wp_unslash(
                        (string) $_GET[
                            'action'
                        ]
                    )
                )
                : '';

        return in_array(
            $action,
            [
                'new',
                'edit',
            ],
            true
        )
            ? $action
            : '';
    }

    private function getRequestedProductId(): int
    {
        return isset($_GET['product_id'])
            ? absint(
                wp_unslash(
                    (string) $_GET[
                        'product_id'
                    ]
                )
            )
            : 0;
    }

    private function getRequestedVariantId(): int
    {
        return isset($_GET['variant_id'])
            ? absint(
                wp_unslash(
                    (string) $_GET[
                        'variant_id'
                    ]
                )
            )
            : 0;
    }

    private function getProductEditUrl(
        int $productId
    ): string {
        return add_query_arg(
            [
                'page' =>
                    ProductAdminController::
                        PAGE_SLUG,

                'view' =>
                    'edit',

                'product_id' =>
                    $productId,
            ],
            admin_url('admin.php')
        );
    }

    private function formatDecimal(
        mixed $value
    ): string {
        if (
            $value === null
            || $value === ''
            || !is_numeric($value)
        ) {
            return '';
        }

        return number_format(
            (float) $value,
            2,
            '.',
            ''
        );
    }

    private function formatDate(
        mixed $value
    ): string {
        if (
            !is_string($value)
            || trim($value) === ''
        ) {
            return '—';
        }

        return get_date_from_gmt(
            $value,
            'd/m/Y H:i'
        );
    }

    private function assertPermission(): void
    {
        if (
            current_user_can(
                self::CAPABILITY
            )
        ) {
            return;
        }

        wp_die(
            esc_html__(
                'No tienes permisos para administrar variantes.',
                'dsm-catalogo'
            ),
            esc_html__(
                'Acceso denegado',
                'dsm-catalogo'
            ),
            [
                'response' => 403,
            ]
        );
    }
}