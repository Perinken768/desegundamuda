<?php

declare(strict_types=1);

namespace DSM\Catalogo\Admin\Variant;

use DSM\Catalogo\Admin\VariantAdminController;
use DSM\Catalogo\Application\CreateProductVariant;
use DSM\Catalogo\Application\UpdateProductVariant;
use DSM\Catalogo\Inventory\StockMovementRepository;
use DSM\Catalogo\Product\ProductRepository;
use DSM\Catalogo\Stock\StockService;
use DSM\Catalogo\Variant\ProductVariantRepository;
use RuntimeException;
use Throwable;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Guarda variantes comerciales.
 *
 * DSM Catálogo no administra stock.
 *
 * Las columnas de inventario permanecen temporalmente en la tabla
 * por compatibilidad, pero toda variante creada desde esta pantalla
 * nace con:
 *
 * - stock_quantity = 0;
 * - stock_reserved = 0;
 * - track_stock = false.
 *
 * DSM Multitienda será quien active y gestione el inventario.
 */
final class VariantSaveAction
{
    private const ERROR_TRANSIENT_PREFIX =
        'dsm_catalogo_variant_error_';

    private ProductRepository $productRepository;

    private ProductVariantRepository $variantRepository;

    private CreateProductVariant $createVariant;

    private UpdateProductVariant $updateVariant;

    public function __construct()
    {
        $this->productRepository =
            new ProductRepository();

        $this->variantRepository =
            new ProductVariantRepository();

        /*
         * Dependencia temporal.
         *
         * CreateProductVariant todavía requiere StockService.
         * Se eliminará cuando la lógica de inventario sea extraída
         * definitivamente a DSM Multitienda.
         */
        $stockService =
            new StockService(
                new StockMovementRepository()
            );

        $this->createVariant =
            new CreateProductVariant(
                $this->productRepository,
                $this->variantRepository,
                $stockService
            );

        $this->updateVariant =
            new UpdateProductVariant(
                $this->productRepository,
                $this->variantRepository
            );
    }

    public function handle(): void
    {
        $this->assertPermission();

        check_admin_referer(
            VariantAdminController::
                getNonceAction(),

            VariantAdminController::
                getNonceField()
        );

        $variantId =
            isset($_POST['variant_id'])
                ? absint(
                    wp_unslash(
                        (string) $_POST[
                            'variant_id'
                        ]
                    )
                )
                : 0;

        $productId =
            isset($_POST['product_id'])
                ? absint(
                    wp_unslash(
                        (string) $_POST[
                            'product_id'
                        ]
                    )
                )
                : 0;

        try {
            if ($variantId > 0) {
                $savedVariantId =
                    $this->update(
                        $variantId
                    );

                $status =
                    'variant_updated';
            } else {
                $savedVariantId =
                    $this->create(
                        $productId
                    );

                $status =
                    'variant_created';
            }

            $this->clearLastError();

            $redirectUrl =
                VariantAdminController::
                    getEditUrl(
                        $savedVariantId
                    );

            $redirectUrl =
                add_query_arg(
                    'dsm_status',
                    $status,
                    $redirectUrl
                );
        } catch (Throwable $exception) {
            $this->storeLastError(
                $exception->getMessage()
            );

            error_log(
                '[DSM Catálogo] Error guardando variante: '
                . $exception->getMessage()
            );

            $arguments = [
                'action' =>
                    $variantId > 0
                        ? 'edit'
                        : 'new',

                'dsm_status' =>
                    'variant_error',
            ];

            if ($variantId > 0) {
                $arguments['variant_id'] =
                    $variantId;
            } elseif ($productId > 0) {
                $arguments['product_id'] =
                    $productId;
            }

            $redirectUrl =
                VariantAdminController::
                    getListUrl(
                        $arguments
                    );
        }

        wp_safe_redirect(
            $redirectUrl
        );

        exit;
    }

    private function create(
        int $productId
    ): int {
        if ($productId <= 0) {
            throw new RuntimeException(
                'Debes seleccionar un producto válido.'
            );
        }

        $product =
            $this->productRepository
                ->findById(
                    $productId
                );

        if ($product === null) {
            throw new RuntimeException(
                'No se encontró el producto seleccionado.'
            );
        }

        $result =
            $this->createVariant
                ->execute(
                    storeId:
                        $product->getStoreId(),

                    customerId:
                        $product
                            ->getCreatedByCustomerId(),

                    productId:
                        $productId,

                    data:
                        $this->getCreateData()
                );

        return $result['variant']
            ->getId();
    }

    private function update(
        int $variantId
    ): int {
        $variant =
            $this->variantRepository
                ->findById(
                    $variantId
                );

        if ($variant === null) {
            throw new RuntimeException(
                'No se encontró la variante.'
            );
        }

        $product =
            $this->productRepository
                ->findById(
                    $variant->getProductId()
                );

        if ($product === null) {
            throw new RuntimeException(
                'No se encontró el producto asociado.'
            );
        }

        $this->updateVariant
            ->execute(
                storeId:
                    $product->getStoreId(),

                customerId:
                    $product
                        ->getCreatedByCustomerId(),

                variantId:
                    $variantId,

                data:
                    $this->getUpdateData()
            );

        return $variantId;
    }

    /**
     * @return array<string, mixed>
     */
    private function getCreateData(): array
    {
        return array_merge(
            $this->getCommercialData(),
            [
                /*
                 * Compatibilidad temporal con la tabla y el servicio
                 * actuales. DSM Catálogo no administrará estos campos.
                 */
                'stock_quantity' =>
                    0,

                'track_stock' =>
                    false,

                'low_stock_threshold' =>
                    null,

                'notes' =>
                    null,

                'user_id' =>
                    get_current_user_id(),
            ]
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function getUpdateData(): array
    {
        /*
         * No se envían stock_quantity ni stock_reserved.
         *
         * UpdateProductVariant ya bloquea expresamente cualquier
         * modificación directa de esos campos. :contentReference[oaicite:2]{index=2}
         */
        return $this->getCommercialData();
    }

    /**
     * @return array<string, mixed>
     */
    private function getCommercialData(): array
    {
        return [
            'sku' =>
                $this->getNullableText(
                    'sku'
                ),

            'barcode' =>
                $this->getNullableText(
                    'barcode'
                ),

            'size_value' =>
                $this->getNullableText(
                    'size_value'
                ),

            'color_value' =>
                $this->getNullableText(
                    'color_value'
                ),

            'condition_code' =>
                $this->getNullableKey(
                    'condition_code'
                ),

            'price' =>
                $this->getNullableDecimal(
                    'price'
                ),

            'original_price' =>
                $this->getNullableDecimal(
                    'original_price'
                ),

            'cost_price' =>
                $this->getNullableDecimal(
                    'cost_price'
                ),

            'is_default' =>
                isset(
                    $_POST['is_default']
                ),

            'is_active' =>
                isset(
                    $_POST['is_active']
                ),

            'sort_order' =>
                $this->getNonNegativeInteger(
                    'sort_order'
                ),
        ];
    }

    private function getNullableText(
        string $field
    ): ?string {
        if (!isset($_POST[$field])) {
            return null;
        }

        $value =
            trim(
                sanitize_text_field(
                    wp_unslash(
                        (string) $_POST[
                            $field
                        ]
                    )
                )
            );

        return $value !== ''
            ? $value
            : null;
    }

    private function getNullableKey(
        string $field
    ): ?string {
        if (!isset($_POST[$field])) {
            return null;
        }

        $value =
            sanitize_key(
                wp_unslash(
                    (string) $_POST[
                        $field
                    ]
                )
            );

        return $value !== ''
            ? $value
            : null;
    }

    private function getNullableDecimal(
        string $field
    ): ?string {
        if (!isset($_POST[$field])) {
            return null;
        }

        $value =
            trim(
                wp_unslash(
                    (string) $_POST[
                        $field
                    ]
                )
            );

        if ($value === '') {
            return null;
        }

        $value =
            str_replace(
                ',',
                '.',
                $value
            );

        if (
            !preg_match(
                '/^\d+(?:\.\d{1,2})?$/',
                $value
            )
        ) {
            throw new RuntimeException(
                sprintf(
                    'El campo %s no contiene un importe válido.',
                    $field
                )
            );
        }

        return number_format(
            (float) $value,
            2,
            '.',
            ''
        );
    }

    private function getNonNegativeInteger(
        string $field
    ): int {
        if (!isset($_POST[$field])) {
            return 0;
        }

        $value =
            filter_var(
                wp_unslash(
                    $_POST[$field]
                ),
                FILTER_VALIDATE_INT,
                [
                    'options' => [
                        'min_range' => 0,
                    ],
                ]
            );

        if ($value === false) {
            throw new RuntimeException(
                sprintf(
                    'El campo %s debe ser un número entero igual o superior a cero.',
                    $field
                )
            );
        }

        return (int) $value;
    }

    private function assertPermission(): void
    {
        if (
            current_user_can(
                'manage_options'
            )
        ) {
            return;
        }

        wp_die(
            esc_html__(
                'No tienes permisos para guardar variantes.',
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

    private function storeLastError(
        string $message
    ): void {
        $message =
            sanitize_text_field(
                $message
            );

        if ($message === '') {
            $message =
                __(
                    'No se pudo guardar la variante.',
                    'dsm-catalogo'
                );
        }

        set_transient(
            self::ERROR_TRANSIENT_PREFIX
            . get_current_user_id(),
            $message,
            5 * MINUTE_IN_SECONDS
        );
    }

    private function clearLastError(): void
    {
        delete_transient(
            self::ERROR_TRANSIENT_PREFIX
            . get_current_user_id()
        );
    }

    public static function getLastError(): string
    {
        $error =
            get_transient(
                self::ERROR_TRANSIENT_PREFIX
                . get_current_user_id()
            );

        return is_string($error)
            ? $error
            : '';
    }
}