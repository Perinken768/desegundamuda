<?php

declare(strict_types=1);

use DSM\Catalogo\Image\ProductImage;
use DSM\Catalogo\Product\Product;
use DSM\Catalogo\Variant\ProductVariant;
use DSM\Multitienda\Frontend\StoreProductImageController;
use DSM\Multitienda\Frontend\StoreProductVariantController;
use DSM\Multitienda\Frontend\StoreStockController;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * @var Product $editProduct
 * @var array<int, ProductVariant> $productVariants
 * @var string $variantNotice
 * @var string $variantError
 * @var string $stockNotice
 * @var string $stockError
 */

?>

<article class="dsm-card">

    <h2>
        Imágenes del producto
    </h2>

    <?php if (
        $imageNotice === 'uploaded'
    ) : ?>

        <div class="dsm-account-notice dsm-account-notice--success">
            Las imágenes se añadieron correctamente.
        </div>

    <?php elseif (
        $imageNotice === 'cover-updated'
    ) : ?>

        <div class="dsm-account-notice dsm-account-notice--success">
            La imagen de portada se actualizó correctamente.
        </div>

    <?php elseif (
        $imageNotice === 'deleted'
    ) : ?>

        <div class="dsm-account-notice dsm-account-notice--success">
            La imagen se eliminó correctamente.
        </div>

    <?php elseif (
        $imageNotice === 'error'
        && $imageError !== ''
    ) : ?>

        <div class="dsm-account-notice dsm-account-notice--error">
            <?php echo esc_html($imageError); ?>
        </div>

    <?php endif; ?>

    <?php if ($productImages === []) : ?>

        <p>
            Este producto todavía no tiene imágenes.
        </p>

    <?php else : ?>

        <div
            style="
                display:grid;
                grid-template-columns:
                    repeat(auto-fill,minmax(180px,1fr));
                gap:20px;
                margin-bottom:25px;
            "
        >

            <?php foreach (
                $productImages
                as $productImage
            ) : ?>

                <?php
                $attachmentId =
                    $productImage
                        ->getAttachmentId();

                $imageUrl =
                    wp_get_attachment_image_url(
                        $attachmentId,
                        'medium'
                    );

                if (!is_string($imageUrl)) {
                    $imageUrl = '';
                }
                ?>

                <div
                    style="
                        border:1px solid #ddd;
                        padding:12px;
                        border-radius:8px;
                    "
                >

                    <?php if ($imageUrl !== '') : ?>

                        <img
                            src="<?php
                            echo esc_url(
                                $imageUrl
                            );
                            ?>"
                            alt=""
                            style="
                                display:block;
                                width:100%;
                                aspect-ratio:1/1;
                                object-fit:cover;
                                margin-bottom:12px;
                                border-radius:6px;
                            "
                        >

                    <?php endif; ?>

                    <?php if (
                        $productImage->isCover()
                    ) : ?>

                        <p>
                            <strong>
                                Portada actual
                            </strong>
                        </p>

                    <?php else : ?>

                        <form
                            method="post"
                            action="<?php
                            echo esc_url(
                                admin_url(
                                    'admin-post.php'
                                )
                            );
                            ?>"
                            style="margin-bottom:10px;"
                        >

                            <input
                                type="hidden"
                                name="action"
                                value="<?php
                                echo esc_attr(
                                    StoreProductImageController::
                                        COVER_ACTION
                                );
                                ?>"
                            >

                            <input
                                type="hidden"
                                name="product_id"
                                value="<?php
                                echo esc_attr(
                                    (string)
                                    $editProduct->getId()
                                );
                                ?>"
                            >

                            <input
                                type="hidden"
                                name="image_id"
                                value="<?php
                                echo esc_attr(
                                    (string)
                                    $productImage->getId()
                                );
                                ?>"
                            >

                            <?php
                            wp_nonce_field(
                                StoreProductImageController::
                                    getCoverNonceAction(
                                        $editProduct
                                            ->getId(),
                                        $productImage
                                            ->getId()
                                    ),
                                StoreProductImageController::
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
                                Usar como portada
                            </button>

                        </form>

                    <?php endif; ?>

                    <form
                        method="post"
                        action="<?php
                        echo esc_url(
                            admin_url(
                                'admin-post.php'
                            )
                        );
                        ?>"
                    >

                        <input
                            type="hidden"
                            name="action"
                            value="<?php
                            echo esc_attr(
                                StoreProductImageController::
                                    DELETE_ACTION
                            );
                            ?>"
                        >

                        <input
                            type="hidden"
                            name="product_id"
                            value="<?php
                            echo esc_attr(
                                (string)
                                $editProduct->getId()
                            );
                            ?>"
                        >

                        <input
                            type="hidden"
                            name="image_id"
                            value="<?php
                            echo esc_attr(
                                (string)
                                $productImage->getId()
                            );
                            ?>"
                        >

                        <?php
                        wp_nonce_field(
                            StoreProductImageController::
                                getDeleteNonceAction(
                                    $editProduct
                                        ->getId(),
                                    $productImage
                                        ->getId()
                                ),
                            StoreProductImageController::
                                NONCE_FIELD
                        );
                        ?>

                        <button
                            type="submit"
                            class="
                                dsm-button
                                dsm-button--secondary
                            "
                            onclick="return confirm(
                                '¿Eliminar esta imagen?'
                            );"
                        >
                            Eliminar
                        </button>

                    </form>

                </div>

            <?php endforeach; ?>

        </div>

    <?php endif; ?>

    <form
        method="post"
        enctype="multipart/form-data"
        action="<?php
        echo esc_url(
            admin_url(
                'admin-post.php'
            )
        );
        ?>"
    >

        <input
            type="hidden"
            name="action"
            value="<?php
            echo esc_attr(
                StoreProductImageController::
                    UPLOAD_ACTION
            );
            ?>"
        >

        <input
            type="hidden"
            name="product_id"
            value="<?php
            echo esc_attr(
                (string)
                $editProduct->getId()
            );
            ?>"
        >

        <?php
        wp_nonce_field(
            StoreProductImageController::
                getUploadNonceAction(
                    $editProduct->getId()
                ),
            StoreProductImageController::
                NONCE_FIELD
        );
        ?>

        <p>
            <label>
                <strong>
                    Añadir imágenes
                </strong>
            </label>

            <br>

            <input
                type="file"
                name="product_images[]"
                accept="image/*"
                multiple
            >
        </p>

        <p>
            <small>
                Máximo 10 imágenes por producto.
            </small>
        </p>

        <button
            type="submit"
            class="
                dsm-button
                dsm-button--primary
            "
        >
            Subir imágenes
        </button>

    </form>

</article>

<article class="dsm-card">

    <h2>
        Variantes de
        <?php
        echo esc_html(
            $editProduct->getName()
        );
        ?>
    </h2>

    <?php if (
        $variantNotice === 'created'
    ) : ?>

        <div class="dsm-account-notice dsm-account-notice--success">
            La variante se creó correctamente.
        </div>

    <?php elseif (
        $variantNotice === 'error'
        && $variantError !== ''
    ) : ?>

        <div class="dsm-account-notice dsm-account-notice--error">
            <?php echo esc_html($variantError); ?>
        </div>

    <?php endif; ?>

    <?php if (
        $stockNotice === 'replenished'
    ) : ?>

        <div class="dsm-account-notice dsm-account-notice--success">
            El stock se repuso correctamente.
        </div>

    <?php elseif (
        $stockNotice === 'adjusted'
    ) : ?>

        <div class="dsm-account-notice dsm-account-notice--success">
            El stock se ajustó correctamente.
        </div>

    <?php elseif (
        $stockNotice === 'error'
        && $stockError !== ''
    ) : ?>

        <div class="dsm-account-notice dsm-account-notice--error">
            <?php echo esc_html($stockError); ?>
        </div>

    <?php endif; ?>

    <?php if ($productVariants === []) : ?>

        <p>
            Este producto todavía no tiene variantes.
        </p>

    <?php else : ?>

        <?php foreach (
            $productVariants
            as $variant
        ) : ?>

            <section
                style="
                    border:1px solid #ddd;
                    padding:20px;
                    margin-bottom:20px;
                "
            >

                <h3>

                    <?php
                    echo esc_html(
                        $variant->getColorValue()
                        ?? 'Sin color'
                    );
                    ?>

                    /

                    <?php
                    echo esc_html(
                        $variant->getSizeValue()
                        ?? 'Sin talla'
                    );
                    ?>

                </h3>

                <p>
                    <strong>SKU:</strong>

                    <?php
                    echo esc_html(
                        $variant->getSku()
                        ?? '—'
                    );
                    ?>
                </p>

                <table
                    style="
                        width:100%;
                        max-width:700px;
                        margin-bottom:20px;
                    "
                >

                    <thead>

                        <tr>
                            <th>Stock físico</th>
                            <th>Reservado</th>
                            <th>Disponible</th>
                            <th>Stock bajo</th>
                        </tr>

                    </thead>

                    <tbody>

                        <tr>

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
                                <?php
                                echo esc_html(
                                    $variant
                                        ->getLowStockThreshold()
                                    !== null
                                        ? (string)
                                            $variant
                                                ->getLowStockThreshold()
                                        : '—'
                                );
                                ?>
                            </td>

                        </tr>

                    </tbody>

                </table>

                <?php if (
                    $variant->isActive()
                    && !$variant->isArchived()
                    && $variant->tracksStock()
                ) : ?>

                    <div
                        style="
                            display:flex;
                            gap:30px;
                            flex-wrap:wrap;
                        "
                    >

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
                                border:1px solid #ddd;
                                padding:15px;
                            "
                        >

                            <h4>
                                Reponer stock
                            </h4>

                            <input
                                type="hidden"
                                name="action"
                                value="<?php
                                echo esc_attr(
                                    StoreStockController::
                                        REPLENISH_ACTION
                                );
                                ?>"
                            >

                            <input
                                type="hidden"
                                name="product_id"
                                value="<?php
                                echo esc_attr(
                                    (string)
                                    $editProduct->getId()
                                );
                                ?>"
                            >

                            <input
                                type="hidden"
                                name="variant_id"
                                value="<?php
                                echo esc_attr(
                                    (string)
                                    $variant->getId()
                                );
                                ?>"
                            >

                            <?php
                            wp_nonce_field(
                                StoreStockController::
                                    getReplenishNonceAction(
                                        $variant->getId()
                                    ),
                                StoreStockController::
                                    NONCE_FIELD
                            );
                            ?>

                            <p>
                                <label>
                                    <strong>
                                        Unidades
                                    </strong>
                                </label>

                                <br>

                                <input
                                    type="number"
                                    name="quantity"
                                    min="1"
                                    step="1"
                                    required
                                >
                            </p>

                            <p>
                                <label>
                                    <strong>
                                        Motivo
                                    </strong>
                                </label>

                                <br>

                                <textarea
                                    name="notes"
                                    rows="3"
                                    required
                                    placeholder="Ej. Entrada de mercancía"
                                ></textarea>
                            </p>

                            <button
                                type="submit"
                                class="
                                    dsm-button
                                    dsm-button--primary
                                "
                            >
                                Reponer
                            </button>

                        </form>

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
                                border:1px solid #ddd;
                                padding:15px;
                            "
                        >

                            <h4>
                                Ajustar stock
                            </h4>

                            <input
                                type="hidden"
                                name="action"
                                value="<?php
                                echo esc_attr(
                                    StoreStockController::
                                        ADJUST_ACTION
                                );
                                ?>"
                            >

                            <input
                                type="hidden"
                                name="product_id"
                                value="<?php
                                echo esc_attr(
                                    (string)
                                    $editProduct->getId()
                                );
                                ?>"
                            >

                            <input
                                type="hidden"
                                name="variant_id"
                                value="<?php
                                echo esc_attr(
                                    (string)
                                    $variant->getId()
                                );
                                ?>"
                            >

                            <?php
                            wp_nonce_field(
                                StoreStockController::
                                    getAdjustNonceAction(
                                        $variant->getId()
                                    ),
                                StoreStockController::
                                    NONCE_FIELD
                            );
                            ?>

                            <p>
                                <label>
                                    <strong>
                                        Ajuste
                                    </strong>
                                </label>

                                <br>

                                <input
                                    type="number"
                                    name="quantity_delta"
                                    step="1"
                                    required
                                    placeholder="-2 o 3"
                                >
                            </p>

                            <p>
                                <small>
                                    Usa un número negativo para
                                    retirar unidades y positivo
                                    para añadirlas.
                                </small>
                            </p>

                            <p>
                                <label>
                                    <strong>
                                        Motivo
                                    </strong>
                                </label>

                                <br>

                                <textarea
                                    name="notes"
                                    rows="3"
                                    required
                                    placeholder="Ej. Venta en tienda física"
                                ></textarea>
                            </p>

                            <button
                                type="submit"
                                class="
                                    dsm-button
                                    dsm-button--secondary
                                "
                            >
                                Ajustar
                            </button>

                        </form>

                    </div>

                <?php endif; ?>

            </section>

        <?php endforeach; ?>

    <?php endif; ?>

</article>

<article class="dsm-card">

    <h2>
        Nueva variante
    </h2>

    <form
        method="post"
        action="<?php
        echo esc_url(
            admin_url(
                'admin-post.php'
            )
        );
        ?>"
    >

        <input
            type="hidden"
            name="action"
            value="<?php
            echo esc_attr(
                StoreProductVariantController::
                    CREATE_ACTION
            );
            ?>"
        >

        <input
            type="hidden"
            name="product_id"
            value="<?php
            echo esc_attr(
                (string)
                $editProduct->getId()
            );
            ?>"
        >

        <?php
        wp_nonce_field(
            StoreProductVariantController::
                getCreateNonceAction(
                    $editProduct->getId()
                ),
            StoreProductVariantController::
                NONCE_FIELD
        );
        ?>

        <p>
            <label><strong>Talla</strong></label><br>

            <input
                type="text"
                name="size_value"
                maxlength="80"
                placeholder="Ej. M"
            >
        </p>

        <p>
            <label><strong>Color</strong></label><br>

            <input
                type="text"
                name="color_value"
                maxlength="100"
                placeholder="Ej. Negro"
            >
        </p>

        <p>
            <label><strong>SKU</strong></label><br>

            <input
                type="text"
                name="sku"
                maxlength="120"
            >
        </p>

        <p>
            <label><strong>Código de barras</strong></label><br>

            <input
                type="text"
                name="barcode"
                maxlength="120"
            >
        </p>

        <p>
            <label><strong>Estado de la prenda</strong></label><br>

            <select name="condition_code">

                <option value="">
                    Sin especificar
                </option>

                <option value="new_with_tags">
                    Nuevo con etiquetas
                </option>

                <option value="new_without_tags">
                    Nuevo sin etiquetas
                </option>

                <option value="very_good">
                    Muy buen estado
                </option>

                <option value="good">
                    Buen estado
                </option>

                <option value="satisfactory">
                    Estado satisfactorio
                </option>

            </select>
        </p>

        <p>
            <label><strong>Precio específico</strong></label><br>

            <input
                type="number"
                name="price"
                min="0"
                step="0.01"
            >
            €
        </p>

        <p>
            <label><strong>Precio original</strong></label><br>

            <input
                type="number"
                name="original_price"
                min="0"
                step="0.01"
            >
            €
        </p>

        <p>
            <label><strong>Coste</strong></label><br>

            <input
                type="number"
                name="cost_price"
                min="0"
                step="0.01"
            >
            €
        </p>

        <hr>

        <h3>
            Inventario inicial
        </h3>

        <p>
            <label>
                <strong>
                    Stock físico inicial
                </strong>
            </label>

            <br>

            <input
                type="number"
                name="initial_stock"
                min="0"
                step="1"
                value="0"
                required
            >
        </p>

        <p>
            <label>
                <strong>
                    Aviso de stock bajo
                </strong>
            </label>

            <br>

            <input
                type="number"
                name="low_stock_threshold"
                min="0"
                step="1"
            >
        </p>

        <p>
            <label>

                <input
                    type="checkbox"
                    name="is_default"
                    value="1"
                >

                Variante principal del producto

            </label>
        </p>

        <input
            type="hidden"
            name="sort_order"
            value="0"
        >

        <button
            type="submit"
            class="
                dsm-button
                dsm-button--primary
            "
        >
            Crear variante
        </button>

    </form>

</article>