<?php

declare(strict_types=1);

namespace DSM\Catalogo\Admin;

use DSM\Catalogo\Application\CreateProduct;
use DSM\Catalogo\Application\UpdateProduct;
use DSM\Catalogo\Brand\BrandRepository;
use DSM\Catalogo\Product\Product;
use DSM\Catalogo\Product\ProductRepository;
use DSM\Catalogo\Product\ProductStatus;
use RuntimeException;
use Throwable;

if (!defined('ABSPATH')) {
    exit;
}

final class ProductAdminController
{
    public const PAGE_SLUG =
        'dsm-catalogo-products';

    private const PARENT_SLUG =
        'dsm-catalogo';

    private const CAPABILITY =
        'manage_options';

    private const SAVE_ACTION =
        'dsm_catalogo_save_product';

    private const CHANGE_STATUS_ACTION =
        'dsm_catalogo_change_product_status';

    private const SAVE_NONCE_ACTION =
        'dsm_catalogo_save_product';

    private const SAVE_NONCE_FIELD =
        'dsm_catalogo_product_nonce';

    private const STATUS_NONCE_ACTION =
        'dsm_catalogo_change_product_status';

    private ProductRepository $productRepository;

    private BrandRepository $brandRepository;

    private CreateProduct $createProduct;

    private UpdateProduct $updateProduct;

    public function __construct(
        ?ProductRepository $productRepository = null,
        ?BrandRepository $brandRepository = null,
        ?CreateProduct $createProduct = null,
        ?UpdateProduct $updateProduct = null
    ) {
        $this->productRepository =
            $productRepository
            ?? new ProductRepository();

        $this->brandRepository =
            $brandRepository
            ?? new BrandRepository();

        $this->createProduct =
            $createProduct
            ?? new CreateProduct(
                $this->productRepository,
                $this->brandRepository
            );

        $this->updateProduct =
            $updateProduct
            ?? new UpdateProduct(
                $this->productRepository,
                $this->brandRepository
            );
    }

    public static function register(): void
    {
        $controller = new self();

        add_action(
            'admin_menu',
            [$controller, 'registerMenu']
        );

        add_action(
            'admin_post_' . self::SAVE_ACTION,
            [$controller, 'handleSave']
        );

        add_action(
            'admin_post_' . self::CHANGE_STATUS_ACTION,
            [$controller, 'handleChangeStatus']
        );

        add_action(
            'admin_enqueue_scripts',
            [$controller, 'enqueueAssets']
        );
    }

    public function registerMenu(): void
    {
        add_submenu_page(
            parent_slug:
                self::PARENT_SLUG,

            page_title:
                __('Productos', 'dsm-catalogo'),

            menu_title:
                __('Productos', 'dsm-catalogo'),

            capability:
                self::CAPABILITY,

            menu_slug:
                self::PAGE_SLUG,

            callback:
                [$this, 'renderPage']
        );
    }

    public function renderPage(): void
    {
        $this->assertPermission();

        $view = isset($_GET['view'])
            ? sanitize_key(
                wp_unslash(
                    (string) $_GET['view']
                )
            )
            : 'list';

        if (
            $view === 'new'
            || $view === 'edit'
        ) {
            $this->renderForm($view);

            return;
        }

        $this->renderList();
    }

    public function handleSave(): void
    {
        $this->assertPermission();

        check_admin_referer(
            self::SAVE_NONCE_ACTION,
            self::SAVE_NONCE_FIELD
        );

        $productId =
            self::postPositiveInt(
                'product_id'
            )
            ?? 0;

        $storeId =
            self::postPositiveInt(
                'store_id'
            )
            ?? 0;

        $customerId =
            self::postPositiveInt(
                'customer_id'
            )
            ?? 0;

        try {
            if ($storeId <= 0) {
                throw new RuntimeException(
                    'Debes indicar una tienda válida.'
                );
            }

            if ($customerId <= 0) {
                throw new RuntimeException(
                    'Debes indicar un cliente administrador válido.'
                );
            }

            $data =
                $this->sanitizeProductInput(
                    $_POST
                );

            if ($productId > 0) {
                $product =
                    $this->updateProduct->execute(
                        storeId:
                            $storeId,

                        customerId:
                            $customerId,

                        productId:
                            $productId,

                        data:
                            $data
                    );

                $this->redirectWithNotice(
                    notice:
                        'product_updated',

                    additionalArguments: [
                        'view' =>
                            'edit',

                        'product_id' =>
                            $product->getId(),

                        'store_id' =>
                            $storeId,

                        'customer_id' =>
                            $customerId,
                    ]
                );
            }

            $product =
                $this->createProduct->execute(
                    storeId:
                        $storeId,

                    customerId:
                        $customerId,

                    data:
                        $data
                );

            $this->redirectWithNotice(
                notice:
                    'product_created',

                additionalArguments: [
                    'view' =>
                        'edit',

                    'product_id' =>
                        $product->getId(),

                    'store_id' =>
                        $storeId,

                    'customer_id' =>
                        $customerId,
                ]
            );
        } catch (Throwable $exception) {
            $this->redirectWithError(
                message:
                    $exception->getMessage(),

                additionalArguments: [
                    'view' =>
                        $productId > 0
                            ? 'edit'
                            : 'new',

                    'product_id' =>
                        $productId > 0
                            ? $productId
                            : null,

                    'store_id' =>
                        $storeId > 0
                            ? $storeId
                            : null,

                    'customer_id' =>
                        $customerId > 0
                            ? $customerId
                            : null,
                ]
            );
        }
    }

    public function handleChangeStatus(): void
    {
        $this->assertPermission();

        $productId =
            self::queryPositiveInt(
                'product_id'
            )
            ?? 0;

        $storeId =
            self::queryPositiveInt(
                'store_id'
            )
            ?? 0;

        $customerId =
            self::queryPositiveInt(
                'customer_id'
            )
            ?? 0;

        $status = isset($_GET['status'])
            ? sanitize_key(
                wp_unslash(
                    (string) $_GET['status']
                )
            )
            : '';

        check_admin_referer(
            self::STATUS_NONCE_ACTION
            . '_'
            . $productId
            . '_'
            . $status
        );

        try {
            if ($productId <= 0) {
                throw new RuntimeException(
                    'El identificador del producto no es válido.'
                );
            }

            if ($storeId <= 0) {
                throw new RuntimeException(
                    'El identificador de la tienda no es válido.'
                );
            }

            if ($customerId <= 0) {
                throw new RuntimeException(
                    'El identificador del cliente no es válido.'
                );
            }

            if (
                !ProductStatus::isValid(
                    $status
                )
            ) {
                throw new RuntimeException(
                    'El estado solicitado no es válido.'
                );
            }

            if (
                !$this->productRepository
                    ->belongsToStore(
                        $productId,
                        $storeId
                    )
            ) {
                throw new RuntimeException(
                    'El producto no pertenece a la tienda indicada.'
                );
            }

            $this->productRepository
                ->updateStatus(
                    productId:
                        $productId,

                    status:
                        $status,

                    updatedByCustomerId:
                        $customerId
                );

            $this->redirectWithNotice(
                notice:
                    'product_status_updated',

                additionalArguments: [
                    'store_id' =>
                        $storeId,

                    'customer_id' =>
                        $customerId,
                ]
            );
        } catch (Throwable $exception) {
            $this->redirectWithError(
                message:
                    $exception->getMessage(),

                additionalArguments: [
                    'store_id' =>
                        $storeId > 0
                            ? $storeId
                            : null,

                    'customer_id' =>
                        $customerId > 0
                            ? $customerId
                            : null,
                ]
            );
        }
    }

    public function enqueueAssets(
        string $hookSuffix
    ): void {
        unset($hookSuffix);

        $page = isset($_GET['page'])
            ? sanitize_key(
                wp_unslash(
                    (string) $_GET['page']
                )
            )
            : '';

        if ($page !== self::PAGE_SLUG) {
            return;
        }

        $cssFile =
            DSM_CATALOGO_PATH
            . 'assets/admin/css/catalog.css';

        if (is_file($cssFile)) {
            wp_enqueue_style(
                'dsm-catalogo-admin',
                DSM_CATALOGO_URL
                    . 'assets/admin/css/catalog.css',
                [],
                DSM_CATALOGO_VERSION
            );
        }

        $jsFile =
            DSM_CATALOGO_PATH
            . 'assets/admin/js/catalog.js';

        if (is_file($jsFile)) {
            wp_enqueue_media();

            wp_enqueue_script(
                'dsm-catalogo-admin',
                DSM_CATALOGO_URL
                    . 'assets/admin/js/catalog.js',
                [
                    'jquery',
                ],
                DSM_CATALOGO_VERSION,
                true
            );
        }
    }

    private function renderList(): void
    {
        $storeId =
            self::queryPositiveInt(
                'store_id'
            );

        $customerId =
            self::queryPositiveInt(
                'customer_id'
            );

        $status = isset($_GET['status'])
            ? sanitize_key(
                wp_unslash(
                    (string) $_GET['status']
                )
            )
            : '';

        if (
            $status !== ''
            && !ProductStatus::isValid(
                $status
            )
        ) {
            $status = '';
        }

        $products = [];

        if ($storeId !== null) {
            $products =
                $this->productRepository
                    ->findByStore(
                        storeId:
                            $storeId,

                        limit:
                            250,

                        offset:
                            0,

                        status:
                            $status !== ''
                                ? $status
                                : null
                    );
        }

        $brands =
            $this->indexBrands();

        $notice =
            $this->getNotice();

        $error =
            $this->getError();

        $createUrl =
            $this->getCreateUrl(
                $storeId,
                $customerId
            );

        $template =
            DSM_CATALOGO_PATH
            . 'templates/admin/products-list.php';

        if (!is_file($template)) {
            throw new RuntimeException(
                sprintf(
                    'No se encontró la plantilla de productos: %s',
                    $template
                )
            );
        }

        require $template;
    }

    private function renderForm(
        string $view
    ): void {
        $storeId =
            self::queryPositiveInt(
                'store_id'
            );

        $customerId =
            self::queryPositiveInt(
                'customer_id'
            );

        $product = null;

        if ($view === 'edit') {
            $productId =
                self::queryPositiveInt(
                    'product_id'
                )
                ?? 0;

            try {
                $product =
                    $this->getRequiredProduct(
                        $productId
                    );

                if (
                    $storeId !== null
                    && !$product->belongsToStore(
                        $storeId
                    )
                ) {
                    throw new RuntimeException(
                        'El producto no pertenece a la tienda indicada.'
                    );
                }

                $storeId =
                    $product->getStoreId();
            } catch (Throwable $exception) {
                $this->redirectWithError(
                    $exception->getMessage()
                );
            }
        }

        $brands =
            $this->brandRepository
                ->findSelectable();

        $notice =
            $this->getNotice();

        $error =
            $this->getError();

        $formAction =
            admin_url('admin-post.php');

        $listUrl =
            $this->getListUrl(
                $storeId,
                $customerId
            );

        $template =
            DSM_CATALOGO_PATH
            . 'templates/admin/product-form.php';

        if (!is_file($template)) {
            throw new RuntimeException(
                sprintf(
                    'No se encontró la plantilla del formulario de producto: %s',
                    $template
                )
            );
        }

        require $template;
    }

    /**
     * @param array<string, mixed> $source
     *
     * @return array<string, mixed>
     */
    private function sanitizeProductInput(
        array $source
    ): array {
        return [
            'brand_id' =>
                isset($source['brand_id'])
                    ? absint(
                        wp_unslash(
                            (string) $source['brand_id']
                        )
                    )
                    : null,

            'name' =>
                isset($source['name'])
                    ? sanitize_text_field(
                        wp_unslash(
                            (string) $source['name']
                        )
                    )
                    : '',

            'slug' =>
                isset($source['slug'])
                    ? sanitize_title(
                        wp_unslash(
                            (string) $source['slug']
                        )
                    )
                    : '',

            'description' =>
                isset($source['description'])
                    ? sanitize_textarea_field(
                        wp_unslash(
                            (string) $source['description']
                        )
                    )
                    : null,

            'internal_reference' =>
                isset($source['internal_reference'])
                    ? sanitize_text_field(
                        wp_unslash(
                            (string) $source['internal_reference']
                        )
                    )
                    : null,

            'base_sku' =>
                isset($source['base_sku'])
                    ? sanitize_text_field(
                        wp_unslash(
                            (string) $source['base_sku']
                        )
                    )
                    : null,

            'default_price' =>
                self::sanitizeDecimal(
                    $source['default_price']
                    ?? 0
                ),

            'original_price' =>
                self::sanitizeNullableDecimal(
                    $source['original_price']
                    ?? null
                ),

            'cost_price' =>
                self::sanitizeNullableDecimal(
                    $source['cost_price']
                    ?? null
                ),

            'purchase_date' =>
                isset($source['purchase_date'])
                    ? sanitize_text_field(
                        wp_unslash(
                            (string) $source['purchase_date']
                        )
                    )
                    : null,

            'tax_rate' =>
                self::sanitizeNullableDecimal(
                    $source['tax_rate']
                    ?? null
                ),

            'track_stock' =>
                isset($source['track_stock'])
                && (string) $source['track_stock']
                    === '1',
        ];
    }

    /**
     * @return array<int, mixed>
     */
    private function indexBrands(): array
    {
        $indexed = [];

        foreach (
            $this->brandRepository->findAll()
            as $brand
        ) {
            $indexed[$brand->getId()] =
                $brand;
        }

        return $indexed;
    }

    private function getRequiredProduct(
        int $productId
    ): Product {
        if ($productId <= 0) {
            throw new RuntimeException(
                'El identificador del producto no es válido.'
            );
        }

        $product =
            $this->productRepository
                ->findById(
                    $productId
                );

        if ($product === null) {
            throw new RuntimeException(
                'No se encontró el producto.'
            );
        }

        return $product;
    }

    private function getCreateUrl(
        ?int $storeId,
        ?int $customerId
    ): string {
        return add_query_arg(
            array_filter(
                [
                    'page' =>
                        self::PAGE_SLUG,

                    'view' =>
                        'new',

                    'store_id' =>
                        $storeId,

                    'customer_id' =>
                        $customerId,
                ],
                static fn (mixed $value): bool =>
                    $value !== null
            ),
            admin_url('admin.php')
        );
    }

    private function getListUrl(
        ?int $storeId,
        ?int $customerId
    ): string {
        return add_query_arg(
            array_filter(
                [
                    'page' =>
                        self::PAGE_SLUG,

                    'store_id' =>
                        $storeId,

                    'customer_id' =>
                        $customerId,
                ],
                static fn (mixed $value): bool =>
                    $value !== null
            ),
            admin_url('admin.php')
        );
    }

    /**
     * @param array<string, mixed> $additionalArguments
     */
    private function redirectWithNotice(
        string $notice,
        array $additionalArguments = []
    ): never {
        $arguments = array_merge(
            [
                'page' =>
                    self::PAGE_SLUG,

                'dsm_notice' =>
                    $notice,
            ],
            array_filter(
                $additionalArguments,
                static fn (mixed $value): bool =>
                    $value !== null
            )
        );

        wp_safe_redirect(
            add_query_arg(
                $arguments,
                admin_url('admin.php')
            )
        );

        exit;
    }

    /**
     * @param array<string, mixed> $additionalArguments
     */
    private function redirectWithError(
        string $message,
        array $additionalArguments = []
    ): never {
        $arguments = array_merge(
            [
                'page' =>
                    self::PAGE_SLUG,

                'dsm_error' =>
                    rawurlencode(
                        $message
                    ),
            ],
            array_filter(
                $additionalArguments,
                static fn (mixed $value): bool =>
                    $value !== null
            )
        );

        wp_safe_redirect(
            add_query_arg(
                $arguments,
                admin_url('admin.php')
            )
        );

        exit;
    }

    private function getNotice(): ?string
    {
        $notice = isset($_GET['dsm_notice'])
            ? sanitize_key(
                wp_unslash(
                    (string) $_GET['dsm_notice']
                )
            )
            : '';

        $messages = [
            'product_created' =>
                __('Producto creado correctamente.', 'dsm-catalogo'),

            'product_updated' =>
                __('Producto actualizado correctamente.', 'dsm-catalogo'),

            'product_status_updated' =>
                __('Estado del producto actualizado.', 'dsm-catalogo'),
        ];

        return $messages[$notice]
            ?? null;
    }

    private function getError(): ?string
    {
        if (!isset($_GET['dsm_error'])) {
            return null;
        }

        $error = rawurldecode(
            wp_unslash(
                (string) $_GET['dsm_error']
            )
        );

        $error = sanitize_text_field(
            $error
        );

        return $error !== ''
            ? $error
            : null;
    }

    private function assertPermission(): void
    {
        if (
            !current_user_can(
                self::CAPABILITY
            )
        ) {
            wp_die(
                esc_html__(
                    'No tienes permisos para administrar productos.',
                    'dsm-catalogo'
                ),
                esc_html__(
                    'Acceso denegado',
                    'dsm-catalogo'
                ),
                [
                    'response' =>
                        403,
                ]
            );
        }
    }

    private static function queryPositiveInt(
        string $key
    ): ?int {
        if (!isset($_GET[$key])) {
            return null;
        }

        $value = absint(
            wp_unslash(
                (string) $_GET[$key]
            )
        );

        return $value > 0
            ? $value
            : null;
    }

    private static function postPositiveInt(
        string $key
    ): ?int {
        if (!isset($_POST[$key])) {
            return null;
        }

        $value = absint(
            wp_unslash(
                (string) $_POST[$key]
            )
        );

        return $value > 0
            ? $value
            : null;
    }

    private static function sanitizeDecimal(
        mixed $value
    ): string {
        return str_replace(
            ',',
            '.',
            sanitize_text_field(
                wp_unslash(
                    (string) $value
                )
            )
        );
    }

    private static function sanitizeNullableDecimal(
        mixed $value
    ): ?string {
        if (
            $value === null
            || trim((string) $value) === ''
        ) {
            return null;
        }

        return self::sanitizeDecimal(
            $value
        );
    }

    public static function getSaveAction(): string
    {
        return self::SAVE_ACTION;
    }

    public static function getSaveNonceAction(): string
    {
        return self::SAVE_NONCE_ACTION;
    }

    public static function getSaveNonceField(): string
    {
        return self::SAVE_NONCE_FIELD;
    }

    public static function getEditUrl(
        Product $product,
        ?int $customerId = null
    ): string {
        return add_query_arg(
            array_filter(
                [
                    'page' =>
                        self::PAGE_SLUG,

                    'view' =>
                        'edit',

                    'product_id' =>
                        $product->getId(),

                    'store_id' =>
                        $product->getStoreId(),

                    'customer_id' =>
                        $customerId,
                ],
                static fn (mixed $value): bool =>
                    $value !== null
            ),
            admin_url('admin.php')
        );
    }

    public static function getStatusUrl(
        Product $product,
        string $status,
        int $customerId
    ): string {
        $url = add_query_arg(
            [
                'action' =>
                    self::CHANGE_STATUS_ACTION,

                'product_id' =>
                    $product->getId(),

                'store_id' =>
                    $product->getStoreId(),

                'customer_id' =>
                    $customerId,

                'status' =>
                    $status,
            ],
            admin_url('admin-post.php')
        );

        return wp_nonce_url(
            $url,
            self::STATUS_NONCE_ACTION
                . '_'
                . $product->getId()
                . '_'
                . $status
        );
    }
}