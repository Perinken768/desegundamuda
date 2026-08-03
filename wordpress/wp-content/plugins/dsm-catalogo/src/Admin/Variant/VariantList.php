<?php

declare(strict_types=1);

namespace DSM\Catalogo\Admin\Variant;

use DSM\Catalogo\Admin\VariantAdminController;
use WP_List_Table;
use wpdb;

if (!defined('ABSPATH')) {
    exit;
}

if (!class_exists(WP_List_Table::class)) {
    require_once ABSPATH
        . 'wp-admin/includes/class-wp-list-table.php';
}

/**
 * Tabla administrativa de variantes.
 *
 * DSM Catálogo administra únicamente datos comerciales:
 *
 * - producto;
 * - talla;
 * - color;
 * - condición;
 * - SKU;
 * - código de barras;
 * - precios;
 * - estado;
 * - variante predeterminada;
 * - orden.
 *
 * Stock, movimientos y reservas pertenecerán a DSM Multitienda.
 */
final class VariantList extends WP_List_Table
{
    private const DEFAULT_PER_PAGE = 20;

    /**
     * @var array<string, string>
     */
    private const ORDER_COLUMNS = [
        'id' =>
            'variants.id',

        'product' =>
            'products.name',

        'sku' =>
            'variants.sku',

        'size' =>
            'variants.size_value',

        'color' =>
            'variants.color_value',

        'price' =>
            'variants.price',

        'is_default' =>
            'variants.is_default',

        'is_active' =>
            'variants.is_active',

        'sort_order' =>
            'variants.sort_order',

        'updated_at' =>
            'variants.updated_at',
    ];

    private wpdb $database;

    private string $variantsTable;

    private string $productsTable;

    private ?int $productId;

    public function __construct(
        ?int $productId = null
    ) {
        global $wpdb;

        $this->database = $wpdb;

        $this->variantsTable =
            $wpdb->prefix
            . 'dsm_product_variants';

        $this->productsTable =
            $wpdb->prefix
            . 'dsm_products';

        $this->productId =
            $productId !== null
            && $productId > 0
                ? $productId
                : null;

        parent::__construct([
            'singular' =>
                'dsm_catalogo_variant',

            'plural' =>
                'dsm_catalogo_variants',

            'ajax' =>
                false,
        ]);
    }

    public function prepare_items(): void
    {
        $perPage =
            $this->get_items_per_page(
                'dsm_catalogo_variants_per_page',
                self::DEFAULT_PER_PAGE
            );

        $currentPage =
            max(
                1,
                $this->get_pagenum()
            );

        $offset =
            ($currentPage - 1)
            * $perPage;

        $filters =
            $this->getFilters();

        $totalItems =
            $this->countItems(
                $filters
            );

        $this->items =
            $this->findItems(
                $filters,
                $perPage,
                $offset
            );

        $this->_column_headers = [
            $this->get_columns(),
            $this->get_hidden_columns(),
            $this->get_sortable_columns(),
        ];

        $this->set_pagination_args([
            'total_items' =>
                $totalItems,

            'per_page' =>
                $perPage,

            'total_pages' =>
                $perPage > 0
                    ? (int) ceil(
                        $totalItems
                        / $perPage
                    )
                    : 1,
        ]);
    }

    /**
     * @return array<string, string>
     */
    public function get_columns(): array
    {
        return [
            'cb' =>
                sprintf(
                    '<input type="checkbox" aria-label="%s">',
                    esc_attr__(
                        'Seleccionar todas las variantes',
                        'dsm-catalogo'
                    )
                ),

            'id' =>
                __('ID', 'dsm-catalogo'),

            'product' =>
                __('Producto', 'dsm-catalogo'),

            'variant' =>
                __('Variante', 'dsm-catalogo'),

            'sku' =>
                __('SKU', 'dsm-catalogo'),

            'price' =>
                __('Precio', 'dsm-catalogo'),

            'is_default' =>
                __('Predeterminada', 'dsm-catalogo'),

            'is_active' =>
                __('Estado', 'dsm-catalogo'),

            'sort_order' =>
                __('Orden', 'dsm-catalogo'),

            'updated_at' =>
                __('Actualizada', 'dsm-catalogo'),
        ];
    }

    /**
     * @return array<int, string>
     */
    public function get_hidden_columns(): array
    {
        return [];
    }

    /**
     * @return array<string, array{0:string,1:bool}>
     */
    protected function get_sortable_columns(): array
    {
        return [
            'id' => [
                'id',
                false,
            ],

            'product' => [
                'product',
                false,
            ],

            'sku' => [
                'sku',
                false,
            ],

            'price' => [
                'price',
                false,
            ],

            'is_default' => [
                'is_default',
                false,
            ],

            'is_active' => [
                'is_active',
                false,
            ],

            'sort_order' => [
                'sort_order',
                true,
            ],

            'updated_at' => [
                'updated_at',
                false,
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function get_bulk_actions(): array
    {
        return [];
    }

    public function no_items(): void
    {
        esc_html_e(
            'No se encontraron variantes.',
            'dsm-catalogo'
        );
    }

    /**
     * @param array<string, mixed> $item
     */
    protected function column_cb(
        $item
    ): string {
        $variantId =
            (int) (
                $item['id']
                ?? 0
            );

        return sprintf(
            '<input type="checkbox" name="variant_ids[]" value="%1$d" aria-label="%2$s">',
            $variantId,
            esc_attr(
                sprintf(
                    __('Seleccionar variante %d', 'dsm-catalogo'),
                    $variantId
                )
            )
        );
    }

    /**
     * @param array<string, mixed> $item
     */
    protected function column_variant(
        $item
    ): string {
        $variantId =
            (int) (
                $item['id']
                ?? 0
            );

        $isActive =
            (int) (
                $item['is_active']
                ?? 0
            ) === 1;

        $isDefault =
            (int) (
                $item['is_default']
                ?? 0
            ) === 1;

        $isArchived =
            !empty(
                $item['archived_at']
            );

        $editUrl =
            VariantAdminController::getEditUrl(
                $variantId
            );

        $actions = [
            'edit' =>
                sprintf(
                    '<a href="%1$s">%2$s</a>',
                    esc_url($editUrl),
                    esc_html__(
                        'Editar',
                        'dsm-catalogo'
                    )
                ),
        ];

        if (!$isArchived) {
            $actions[
                $isActive
                    ? 'deactivate'
                    : 'activate'
            ] =
                sprintf(
                    '<a href="%1$s">%2$s</a>',
                    esc_url(
                        $this->buildStatusUrl(
                            $variantId,
                            !$isActive
                        )
                    ),
                    esc_html(
                        $isActive
                            ? __(
                                'Desactivar',
                                'dsm-catalogo'
                            )
                            : __(
                                'Activar',
                                'dsm-catalogo'
                            )
                    )
                );

            if (!$isDefault) {
                $actions['default'] =
                    sprintf(
                        '<a href="%1$s">%2$s</a>',
                        esc_url(
                            $this->buildDefaultUrl(
                                $variantId
                            )
                        ),
                        esc_html__(
                            'Marcar como predeterminada',
                            'dsm-catalogo'
                        )
                    );
            }

            $actions['archive'] =
                sprintf(
                    '<a class="submitdelete" href="%1$s" data-dsm-confirm="%2$s">%3$s</a>',
                    esc_url(
                        $this->buildArchiveUrl(
                            $variantId
                        )
                    ),
                    esc_attr__(
                        '¿Seguro que quieres archivar esta variante?',
                        'dsm-catalogo'
                    ),
                    esc_html__(
                        'Archivar',
                        'dsm-catalogo'
                    )
                );
        }

        return sprintf(
            '<strong><a class="row-title" href="%1$s">%2$s</a></strong>%3$s',
            esc_url($editUrl),
            esc_html(
                $this->buildVariantLabel(
                    $item
                )
            ),
            $this->row_actions(
                $actions
            )
        );
    }

    /**
     * @param array<string, mixed> $item
     */
    protected function column_product(
        $item
    ): string {
        $productId =
            (int) (
                $item['product_id']
                ?? 0
            );

        $productName =
            trim(
                (string) (
                    $item['product_name']
                    ?? ''
                )
            );

        if ($productName === '') {
            $productName =
                sprintf(
                    __('Producto #%d', 'dsm-catalogo'),
                    $productId
                );
        }

        return sprintf(
            '<a href="%1$s">%2$s</a>',
            esc_url(
                VariantAdminController::getListUrl([
                    'product_id' =>
                        $productId,
                ])
            ),
            esc_html($productName)
        );
    }

    /**
     * @param array<string, mixed> $item
     */
    protected function column_sku(
        $item
    ): string {
        $sku =
            trim(
                (string) (
                    $item['sku']
                    ?? ''
                )
            );

        $barcode =
            trim(
                (string) (
                    $item['barcode']
                    ?? ''
                )
            );

        if (
            $sku === ''
            && $barcode === ''
        ) {
            return '<span aria-hidden="true">—</span>';
        }

        $output = '';

        if ($sku !== '') {
            $output =
                sprintf(
                    '<code>%s</code>',
                    esc_html($sku)
                );
        }

        if ($barcode !== '') {
            $output .=
                sprintf(
                    '%1$s<small>%2$s: %3$s</small>',
                    $output !== ''
                        ? '<br>'
                        : '',
                    esc_html__(
                        'Código',
                        'dsm-catalogo'
                    ),
                    esc_html($barcode)
                );
        }

        return $output;
    }

    /**
     * @param array<string, mixed> $item
     */
    protected function column_price(
        $item
    ): string {
        $price =
            $item['price']
            ?? null;

        if (
            $price === null
            || $price === ''
        ) {
            return '<span aria-hidden="true">—</span>';
        }

        $output =
            '<strong>'
            . esc_html(
                number_format_i18n(
                    (float) $price,
                    2
                )
                . ' €'
            )
            . '</strong>';

        $originalPrice =
            $item['original_price']
            ?? null;

        if (
            $originalPrice !== null
            && $originalPrice !== ''
        ) {
            $output .= sprintf(
                '<br><small>%1$s: %2$s</small>',
                esc_html__(
                    'Original',
                    'dsm-catalogo'
                ),
                esc_html(
                    number_format_i18n(
                        (float) $originalPrice,
                        2
                    )
                    . ' €'
                )
            );
        }

        return $output;
    }

    /**
     * @param array<string, mixed> $item
     */
    protected function column_is_default(
        $item
    ): string {
        if (
            (int) (
                $item['is_default']
                ?? 0
            ) !== 1
        ) {
            return '<span aria-hidden="true">—</span>';
        }

        return sprintf(
            '<span class="dsm-status dsm-status-info">%s</span>',
            esc_html__(
                'Sí',
                'dsm-catalogo'
            )
        );
    }

    /**
     * @param array<string, mixed> $item
     */
    protected function column_is_active(
        $item
    ): string {
        if (
            !empty(
                $item['archived_at']
            )
        ) {
            return sprintf(
                '<span class="dsm-status dsm-status-muted">%s</span>',
                esc_html__(
                    'Archivada',
                    'dsm-catalogo'
                )
            );
        }

        $isActive =
            (int) (
                $item['is_active']
                ?? 0
            ) === 1;

        return sprintf(
            '<span class="dsm-status %1$s">%2$s</span>',
            esc_attr(
                $isActive
                    ? 'dsm-status-success'
                    : 'dsm-status-warning'
            ),
            esc_html(
                $isActive
                    ? __('Activa', 'dsm-catalogo')
                    : __('Inactiva', 'dsm-catalogo')
            )
        );
    }

    /**
     * @param array<string, mixed> $item
     */
    protected function column_updated_at(
        $item
    ): string {
        $updatedAt =
            trim(
                (string) (
                    $item['updated_at']
                    ?? ''
                )
            );

        if ($updatedAt === '') {
            return '<span aria-hidden="true">—</span>';
        }

        return esc_html(
            get_date_from_gmt(
                $updatedAt,
                'd/m/Y H:i'
            )
        );
    }

    /**
     * @param array<string, mixed> $item
     */
    protected function column_default(
        $item,
        $columnName
    ): string {
        if ($columnName === 'id') {
            return esc_html(
                (string) (
                    (int) (
                        $item['id']
                        ?? 0
                    )
                )
            );
        }

        if ($columnName === 'sort_order') {
            return esc_html(
                (string) (
                    (int) (
                        $item['sort_order']
                        ?? 0
                    )
                )
            );
        }

        $value =
            $item[$columnName]
            ?? '';

        return is_scalar($value)
            ? esc_html(
                (string) $value
            )
            : '';
    }

    protected function extra_tablenav(
        $which
    ): void {
        if ($which !== 'top') {
            return;
        }

        $status =
            isset($_GET['variant_status'])
                ? sanitize_key(
                    wp_unslash(
                        (string) $_GET[
                            'variant_status'
                        ]
                    )
                )
                : '';

        $productId =
            $this->productId
            ?? (
                isset($_GET['product_id'])
                    ? absint(
                        wp_unslash(
                            (string) $_GET[
                                'product_id'
                            ]
                        )
                    )
                    : 0
            );

        ?>
        <div class="alignleft actions">
            <select name="product_id">
                <option value="">
                    <?php
                    esc_html_e(
                        'Todos los productos',
                        'dsm-catalogo'
                    );
                    ?>
                </option>

                <?php foreach (
                    $this->findProducts()
                    as $product
                ) : ?>
                    <option
                        value="<?php echo esc_attr(
                            (string) $product['id']
                        ); ?>"
                        <?php selected(
                            $productId,
                            (int) $product['id']
                        ); ?>
                    >
                        <?php echo esc_html(
                            (string) $product['name']
                        ); ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <select name="variant_status">
                <option value="">
                    <?php
                    esc_html_e(
                        'Todos los estados',
                        'dsm-catalogo'
                    );
                    ?>
                </option>

                <option
                    value="active"
                    <?php selected(
                        $status,
                        'active'
                    ); ?>
                >
                    <?php
                    esc_html_e(
                        'Activas',
                        'dsm-catalogo'
                    );
                    ?>
                </option>

                <option
                    value="inactive"
                    <?php selected(
                        $status,
                        'inactive'
                    ); ?>
                >
                    <?php
                    esc_html_e(
                        'Inactivas',
                        'dsm-catalogo'
                    );
                    ?>
                </option>

                <option
                    value="archived"
                    <?php selected(
                        $status,
                        'archived'
                    ); ?>
                >
                    <?php
                    esc_html_e(
                        'Archivadas',
                        'dsm-catalogo'
                    );
                    ?>
                </option>
            </select>

            <?php
            submit_button(
                __('Filtrar', 'dsm-catalogo'),
                '',
                'filter_action',
                false
            );
            ?>
        </div>
        <?php
    }

    /**
     * @return array{
     *     search:string,
     *     product_id:int,
     *     status:string,
     *     orderby:string,
     *     order:string
     * }
     */
    private function getFilters(): array
    {
        $search =
            isset($_REQUEST['s'])
                ? sanitize_text_field(
                    wp_unslash(
                        (string) $_REQUEST['s']
                    )
                )
                : '';

        $productId =
            $this->productId
            ?? (
                isset($_REQUEST['product_id'])
                    ? absint(
                        wp_unslash(
                            (string) $_REQUEST[
                                'product_id'
                            ]
                        )
                    )
                    : 0
            );

        $status =
            isset($_REQUEST['variant_status'])
                ? sanitize_key(
                    wp_unslash(
                        (string) $_REQUEST[
                            'variant_status'
                        ]
                    )
                )
                : '';

        if (
            !in_array(
                $status,
                [
                    '',
                    'active',
                    'inactive',
                    'archived',
                ],
                true
            )
        ) {
            $status = '';
        }

        $orderby =
            isset($_REQUEST['orderby'])
                ? sanitize_key(
                    wp_unslash(
                        (string) $_REQUEST[
                            'orderby'
                        ]
                    )
                )
                : 'sort_order';

        if (
            !isset(
                self::ORDER_COLUMNS[
                    $orderby
                ]
            )
        ) {
            $orderby = 'sort_order';
        }

        $order =
            isset($_REQUEST['order'])
                ? strtoupper(
                    sanitize_key(
                        wp_unslash(
                            (string) $_REQUEST[
                                'order'
                            ]
                        )
                    )
                )
                : 'ASC';

        if (
            !in_array(
                $order,
                [
                    'ASC',
                    'DESC',
                ],
                true
            )
        ) {
            $order = 'ASC';
        }

        return [
            'search' =>
                $search,

            'product_id' =>
                $productId,

            'status' =>
                $status,

            'orderby' =>
                $orderby,

            'order' =>
                $order,
        ];
    }

    /**
     * @param array{
     *     search:string,
     *     product_id:int,
     *     status:string,
     *     orderby:string,
     *     order:string
     * } $filters
     *
     * @return array<int, array<string, mixed>>
     */
    private function findItems(
        array $filters,
        int $limit,
        int $offset
    ): array {
        $parameters = [];

        $where =
            $this->buildWhereSql(
                $filters,
                $parameters
            );

        $orderColumn =
            self::ORDER_COLUMNS[
                $filters['orderby']
            ];

        $order =
            $filters['order'];

        $sql = "
            SELECT
                variants.id,
                variants.product_id,
                variants.sku,
                variants.barcode,
                variants.size_value,
                variants.color_value,
                variants.condition_code,
                variants.price,
                variants.original_price,
                variants.cost_price,
                variants.is_default,
                variants.is_active,
                variants.sort_order,
                variants.created_at,
                variants.updated_at,
                variants.archived_at,
                products.name AS product_name
            FROM {$this->variantsTable} AS variants
            INNER JOIN {$this->productsTable} AS products
                ON products.id = variants.product_id
            {$where}
            ORDER BY {$orderColumn} {$order},
                variants.id ASC
            LIMIT %d
            OFFSET %d
        ";

        $parameters[] =
            max(
                1,
                $limit
            );

        $parameters[] =
            max(
                0,
                $offset
            );

        $prepared =
            $this->database->prepare(
                $sql,
                ...$parameters
            );

        if (!is_string($prepared)) {
            return [];
        }

        $items =
            $this->database->get_results(
                $prepared,
                ARRAY_A
            );

        return is_array($items)
            ? $items
            : [];
    }

    /**
     * @param array{
     *     search:string,
     *     product_id:int,
     *     status:string,
     *     orderby:string,
     *     order:string
     * } $filters
     */
    private function countItems(
        array $filters
    ): int {
        $parameters = [];

        $where =
            $this->buildWhereSql(
                $filters,
                $parameters
            );

        $sql = "
            SELECT COUNT(*)
            FROM {$this->variantsTable} AS variants
            INNER JOIN {$this->productsTable} AS products
                ON products.id = variants.product_id
            {$where}
        ";

        if ($parameters !== []) {
            $prepared =
                $this->database->prepare(
                    $sql,
                    ...$parameters
                );

            if (!is_string($prepared)) {
                return 0;
            }

            $sql = $prepared;
        }

        return (int)
            $this->database->get_var(
                $sql
            );
    }

    /**
     * @param array{
     *     search:string,
     *     product_id:int,
     *     status:string,
     *     orderby:string,
     *     order:string
     * } $filters
     * @param array<int, int|string> $parameters
     */
    private function buildWhereSql(
        array $filters,
        array &$parameters
    ): string {
        $conditions = [];

        if ($filters['product_id'] > 0) {
            $conditions[] =
                'variants.product_id = %d';

            $parameters[] =
                $filters['product_id'];
        }

        if ($filters['status'] === 'active') {
            $conditions[] =
                'variants.archived_at IS NULL';

            $conditions[] =
                'variants.is_active = 1';
        } elseif (
            $filters['status']
            === 'inactive'
        ) {
            $conditions[] =
                'variants.archived_at IS NULL';

            $conditions[] =
                'variants.is_active = 0';
        } elseif (
            $filters['status']
            === 'archived'
        ) {
            $conditions[] =
                'variants.archived_at IS NOT NULL';
        }

        if ($filters['search'] !== '') {
            $like =
                '%'
                . $this->database->esc_like(
                    $filters['search']
                )
                . '%';

            $conditions[] =
                '(
                    products.name LIKE %s
                    OR variants.sku LIKE %s
                    OR variants.barcode LIKE %s
                    OR variants.size_value LIKE %s
                    OR variants.color_value LIKE %s
                    OR variants.condition_code LIKE %s
                )';

            for ($index = 0; $index < 6; $index++) {
                $parameters[] = $like;
            }
        }

        return $conditions === []
            ? ''
            : 'WHERE '
                . implode(
                    ' AND ',
                    $conditions
                );
    }

    /**
     * @return array<int, array{id:int,name:string}>
     */
    private function findProducts(): array
    {
        $products =
            $this->database->get_results(
                "
                SELECT
                    id,
                    name
                FROM {$this->productsTable}
                WHERE archived_at IS NULL
                ORDER BY name ASC,
                    id ASC
                ",
                ARRAY_A
            );

        return is_array($products)
            ? $products
            : [];
    }

    /**
     * @param array<string, mixed> $item
     */
    private function buildVariantLabel(
        array $item
    ): string {
        $parts = [];

        $size =
            trim(
                (string) (
                    $item['size_value']
                    ?? ''
                )
            );

        $color =
            trim(
                (string) (
                    $item['color_value']
                    ?? ''
                )
            );

        $condition =
            trim(
                (string) (
                    $item['condition_code']
                    ?? ''
                )
            );

        if ($size !== '') {
            $parts[] =
                sprintf(
                    '%s: %s',
                    __('Talla', 'dsm-catalogo'),
                    $size
                );
        }

        if ($color !== '') {
            $parts[] =
                sprintf(
                    '%s: %s',
                    __('Color', 'dsm-catalogo'),
                    $color
                );
        }

        if ($condition !== '') {
            $parts[] =
                sprintf(
                    '%s: %s',
                    __('Estado', 'dsm-catalogo'),
                    $this->getConditionLabel(
                        $condition
                    )
                );
        }

        return $parts !== []
            ? implode(
                ' · ',
                $parts
            )
            : sprintf(
                __('Variante #%d', 'dsm-catalogo'),
                (int) (
                    $item['id']
                    ?? 0
                )
            );
    }

    private function getConditionLabel(
        string $condition
    ): string {
        $labels = [
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

        return $labels[$condition]
            ?? $condition;
    }

    private function buildStatusUrl(
        int $variantId,
        bool $active
    ): string {
        return wp_nonce_url(
            add_query_arg(
                [
                    'action' =>
                        VariantAdminController::
                            getStatusAction(),

                    'variant_id' =>
                        $variantId,

                    'is_active' =>
                        $active
                            ? 1
                            : 0,
                ],
                admin_url('admin-post.php')
            ),
            VariantAdminController::
                getNonceAction()
                . '_status_'
                . $variantId,
            VariantAdminController::
                getNonceField()
        );
    }

    private function buildDefaultUrl(
        int $variantId
    ): string {
        return wp_nonce_url(
            add_query_arg(
                [
                    'action' =>
                        VariantAdminController::
                            getDefaultAction(),

                    'variant_id' =>
                        $variantId,
                ],
                admin_url('admin-post.php')
            ),
            VariantAdminController::
                getNonceAction()
                . '_default_'
                . $variantId,
            VariantAdminController::
                getNonceField()
        );
    }

    private function buildArchiveUrl(
        int $variantId
    ): string {
        return wp_nonce_url(
            add_query_arg(
                [
                    'action' =>
                        VariantAdminController::
                            getArchiveAction(),

                    'variant_id' =>
                        $variantId,
                ],
                admin_url('admin-post.php')
            ),
            VariantAdminController::
                getNonceAction()
                . '_archive_'
                . $variantId,
            VariantAdminController::
                getNonceField()
        );
    }
}