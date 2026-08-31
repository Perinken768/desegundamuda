<?php

declare(strict_types=1);

use DSM\Catalogo\Product\Product;
use DSM\Catalogo\Variant\ProductVariant;
use DSM\Multitienda\Frontend\StoreProductFormController;
use DSM\Multitienda\Frontend\StoreProductImageController;
use DSM\Multitienda\Frontend\StoreProductVariantController;
use DSM\Multitienda\Frontend\StoreStockController;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * @var Product $editProduct
 * @var array<int, ProductVariant> $productVariants
 * @var array<int, \DSM\Catalogo\Image\ProductImage> $productImages
 * @var string $variantNotice
 * @var string $variantError
 * @var string $stockNotice
 * @var string $stockError
 * @var string $imageNotice
 * @var string $imageError
 */

/*
 * ==========================================================
 * PORTADA ACTUAL
 * ==========================================================
 */

$coverImageUrl = '';

foreach ($productImages as $productImage) {
    if (!$productImage->isCover()) {
        continue;
    }

    $candidate =
        wp_get_attachment_image_url(
            $productImage->getAttachmentId(),
            'large'
        );

    if (is_string($candidate)) {
        $coverImageUrl = $candidate;
    }

    break;
}


/*
 * ==========================================================
 * FILTROS DE VARIANTES
 * ==========================================================
 */

$selectedVariantId =
    isset($_GET['variant_id'])
        ? absint(
            wp_unslash(
                (string) $_GET['variant_id']
            )
        )
        : 0;

$variantColorFilter =
    isset($_GET['variant_color'])
        ? sanitize_text_field(
            wp_unslash(
                (string) $_GET['variant_color']
            )
        )
        : '';

$variantSizeFilter =
    isset($_GET['variant_size'])
        ? sanitize_text_field(
            wp_unslash(
                (string) $_GET['variant_size']
            )
        )
        : '';

$variantSkuFilter =
    isset($_GET['variant_sku'])
        ? sanitize_text_field(
            wp_unslash(
                (string) $_GET['variant_sku']
            )
        )
        : '';

$variantColors = [];
$variantSizes = [];

foreach ($productVariants as $variant) {
    $color =
        trim(
            (string) (
                $variant->getColorValue()
                ?? ''
            )
        );

    $size =
        trim(
            (string) (
                $variant->getSizeValue()
                ?? ''
            )
        );

    if ($color !== '') {
        $variantColors[$color] =
            $color;
    }

    if ($size !== '') {
        $variantSizes[$size] =
            $size;
    }
}

natcasesort($variantColors);
natcasesort($variantSizes);


/*
 * Variante seleccionada.
 *
 * variant_id tiene prioridad absoluta.
 * Si no existe, usamos los filtros.
 * Si tampoco hay filtros, mostramos la primera.
 */

$selectedVariant = null;

if ($selectedVariantId > 0) {
    foreach ($productVariants as $variant) {
        if (
            $variant->getId()
            === $selectedVariantId
        ) {
            $selectedVariant =
                $variant;

            break;
        }
    }
}

if (
    $selectedVariant === null
    && (
        $variantColorFilter !== ''
        || $variantSizeFilter !== ''
        || $variantSkuFilter !== ''
    )
) {
    foreach ($productVariants as $variant) {
        $matches = true;

        if (
            $variantColorFilter !== ''
            && strcasecmp(
                (string) (
                    $variant->getColorValue()
                    ?? ''
                ),
                $variantColorFilter
            ) !== 0
        ) {
            $matches = false;
        }

        if (
            $matches
            && $variantSizeFilter !== ''
            && strcasecmp(
                (string) (
                    $variant->getSizeValue()
                    ?? ''
                ),
                $variantSizeFilter
            ) !== 0
        ) {
            $matches = false;
        }

        if (
            $matches
            && $variantSkuFilter !== ''
        ) {
            $sku =
                (string) (
                    $variant->getSku()
                    ?? ''
                );

            if (
                stripos(
                    $sku,
                    $variantSkuFilter
                ) === false
            ) {
                $matches = false;
            }
        }

        if ($matches) {
            $selectedVariant =
                $variant;

            break;
        }
    }
}

if (
    $selectedVariant === null
    && $variantColorFilter === ''
    && $variantSizeFilter === ''
    && $variantSkuFilter === ''
    && $productVariants !== []
) {
    $selectedVariant =
        $productVariants[0];
}

?>


<!-- ========================================================
     INFORMACIÓN DEL PRODUCTO
     ======================================================== -->

<article
    class="
        dsm-card
        dsm-product-editor-info
    "
>

    <h2>
        Información del producto
    </h2>

    <?php if (
        isset($productNotice)
        && $productNotice === 'updated'
    ) : ?>

        <div
            class="
                dsm-account-notice
                dsm-account-notice--success
            "
        >
            El producto se actualizó correctamente.
        </div>

    <?php elseif (
        isset($productNotice)
        && $productNotice === 'error'
        && isset($productError)
        && $productError !== ''
    ) : ?>

        <div
            class="
                dsm-account-notice
                dsm-account-notice--error
            "
        >
            <?php
            echo esc_html(
                $productError
            );
            ?>
        </div>

    <?php endif; ?>

    <div class="dsm-product-editor-info__layout">

        <form
            class="dsm-product-editor-info__form"
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
                    StoreProductFormController::
                        UPDATE_ACTION
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
                StoreProductFormController::
                    getUpdateNonceAction(
                        $editProduct->getId()
                    ),
                StoreProductFormController::
                    NONCE_FIELD
            );
            ?>

            <div class="dsm-product-editor-field">

                <label for="dsm-product-name">
                    Nombre del producto
                </label>

                <input
                    id="dsm-product-name"
                    type="text"
                    name="name"
                    maxlength="180"
                    value="<?php
                    echo esc_attr(
                        $editProduct->getName()
                    );
                    ?>"
                    required
                >

            </div>

            <div class="dsm-product-editor-field">

                <label for="dsm-product-description">
                    Descripción
                </label>

                <textarea
                    id="dsm-product-description"
                    name="description"
                    rows="7"
                ><?php
                echo esc_textarea(
                    $editProduct->getDescription()
                    ?? ''
                );
                ?></textarea>

            </div>

            <div class="dsm-product-editor-info__actions">

                <button
                    type="submit"
                    class="
                        dsm-button
                        dsm-button--primary
                    "
                >
                    Guardar cambios
                </button>

            </div>

        </form>


        <aside class="dsm-product-editor-cover">

            <span class="dsm-product-editor-cover__title">
                Portada actual
            </span>

            <?php if (
                $coverImageUrl !== ''
            ) : ?>

                <img
                    src="<?php
                    echo esc_url(
                        $coverImageUrl
                    );
                    ?>"
                    alt="<?php
                    echo esc_attr(
                        $editProduct->getName()
                    );
                    ?>"
                >

            <?php else : ?>

                <div class="dsm-product-editor-cover__empty">
                    Sin imagen de portada
                </div>

            <?php endif; ?>

        </aside>

    </div>

</article>


<!-- ========================================================
     IMÁGENES
     ======================================================== -->

<article
    class="
        dsm-card
        dsm-product-editor-images
    "
>

    <header class="dsm-product-editor-section-header">

        <div>

            <h2>
                Imágenes del producto
            </h2>

            <p>
                Gestiona la galería y elige
                qué imagen se utilizará como portada.
            </p>

        </div>

    </header>

    <?php if (
        $imageNotice === 'uploaded'
    ) : ?>

        <div
            class="
                dsm-account-notice
                dsm-account-notice--success
            "
        >
            Las imágenes se añadieron correctamente.
        </div>

    <?php elseif (
        $imageNotice === 'cover-updated'
    ) : ?>

        <div
            class="
                dsm-account-notice
                dsm-account-notice--success
            "
        >
            La imagen de portada se actualizó correctamente.
        </div>

    <?php elseif (
        $imageNotice === 'deleted'
    ) : ?>

        <div
            class="
                dsm-account-notice
                dsm-account-notice--success
            "
        >
            La imagen se eliminó correctamente.
        </div>

    <?php elseif (
        $imageNotice === 'error'
        && $imageError !== ''
    ) : ?>

        <div
            class="
                dsm-account-notice
                dsm-account-notice--error
            "
        >
            <?php
            echo esc_html(
                $imageError
            );
            ?>
        </div>

    <?php endif; ?>


    <?php if (
        $productImages !== []
    ) : ?>

        <div class="dsm-product-editor-gallery">

            <?php foreach (
                $productImages
                as $productImage
            ) : ?>

                <?php

                $imageUrl =
                    wp_get_attachment_image_url(
                        $productImage
                            ->getAttachmentId(),
                        'medium'
                    );

                if (!is_string($imageUrl)) {
                    $imageUrl = '';
                }

                ?>

                <div
                    class="<?php
                    echo esc_attr(
                        'dsm-product-editor-gallery__item'
                        . (
                            $productImage->isCover()
                                ? ' is-cover'
                                : ''
                        )
                    );
                    ?>"
                >

                    <?php if (
                        $imageUrl !== ''
                    ) : ?>

                        <img
                            src="<?php
                            echo esc_url(
                                $imageUrl
                            );
                            ?>"
                            alt=""
                        >

                    <?php endif; ?>

                    <?php if (
                        $productImage->isCover()
                    ) : ?>

                        <span
                            class="
                                dsm-product-editor-gallery__cover
                            "
                        >
                            Portada
                        </span>

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
                                        $editProduct->getId(),
                                        $productImage->getId()
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
                                    dsm-product-editor-gallery__button
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
                                    $editProduct->getId(),
                                    $productImage->getId()
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
                                dsm-product-editor-gallery__button
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

    <?php else : ?>

        <div
            class="
                dsm-account-notice
                dsm-account-notice--info
            "
        >
            Este producto todavía no tiene imágenes.
        </div>

    <?php endif; ?>


    <form
        class="dsm-product-editor-upload"
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

        <label for="dsm-product-images">
            Añadir imágenes
        </label>

        <input
            id="dsm-product-images"
            type="file"
            name="product_images[]"
            accept="image/*"
            multiple
        >

        <small>
            Máximo 10 imágenes por producto.
        </small>

        <div>

            <button
                type="submit"
                class="
                    dsm-button
                    dsm-button--primary
                "
            >
                Subir imágenes
            </button>

        </div>

    </form>

</article>


<!-- ========================================================
     VARIANTES
     ======================================================== -->

<article
    id="dsm-product-variants"
    class="
        dsm-card
        dsm-product-variants-manager
    "
>

    <header class="dsm-product-editor-section-header">

        <div>

            <h2>
                Variantes de
                <?php
                echo esc_html(
                    $editProduct->getName()
                );
                ?>
            </h2>

            <p>
                Selecciona una variante para consultar
                o modificar su inventario.
            </p>

        </div>


        <details class="dsm-new-variant">

            <summary
                class="
                    dsm-button
                    dsm-button--primary
                "
            >
                + Nueva variante
            </summary>

            <div class="dsm-new-variant__panel">

                <h3>
                    Crear nueva variante
                </h3>

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

                    <div class="dsm-new-variant__grid">

                        <div class="dsm-product-editor-field">

                            <label>
                                Talla
                            </label>

                            <input
                                type="text"
                                name="size_value"
                                maxlength="80"
                                placeholder="Ej. M"
                            >

                        </div>

                        <div class="dsm-product-editor-field">

                            <label>
                                Color
                            </label>

                            <input
                                type="text"
                                name="color_value"
                                maxlength="100"
                                placeholder="Ej. Negro"
                            >

                        </div>

                        <div class="dsm-product-editor-field">

                            <label>
                                SKU
                            </label>

                            <input
                                type="text"
                                name="sku"
                                maxlength="120"
                            >

                        </div>

                        <div class="dsm-product-editor-field">

                            <label>
                                Código de barras
                            </label>

                            <input
                                type="text"
                                name="barcode"
                                maxlength="120"
                            >

                        </div>

                        <div class="dsm-product-editor-field">

                            <label>
                                Estado de la prenda
                            </label>

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

                        </div>

                        <div class="dsm-product-editor-field">

                            <label>
                                Precio específico (€)
                            </label>

                            <input
                                type="number"
                                name="price"
                                min="0"
                                step="0.01"
                            >

                        </div>

                        <div class="dsm-product-editor-field">

                            <label>
                                Precio original (€)
                            </label>

                            <input
                                type="number"
                                name="original_price"
                                min="0"
                                step="0.01"
                            >

                        </div>

                        <div class="dsm-product-editor-field">

                            <label>
                                Coste (€)
                            </label>

                            <input
                                type="number"
                                name="cost_price"
                                min="0"
                                step="0.01"
                            >

                        </div>

                    </div>


                    <div class="dsm-new-variant__inventory">

                        <h4>
                            Inventario inicial
                        </h4>

                        <div class="dsm-new-variant__grid">

                            <div class="dsm-product-editor-field">

                                <label>
                                    Stock físico inicial
                                </label>

                                <input
                                    type="number"
                                    name="initial_stock"
                                    min="0"
                                    step="1"
                                    value="0"
                                    required
                                >

                            </div>

                            <div class="dsm-product-editor-field">

                                <label>
                                    Aviso de stock bajo
                                </label>

                                <input
                                    type="number"
                                    name="low_stock_threshold"
                                    min="0"
                                    step="1"
                                >

                            </div>

                        </div>

                        <label class="dsm-new-variant__default">

                            <input
                                type="checkbox"
                                name="is_default"
                                value="1"
                            >

                            Variante principal del producto

                        </label>

                    </div>

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

            </div>

        </details>

    </header>


    <?php if (
        $variantNotice === 'created'
    ) : ?>

        <div
            class="
                dsm-account-notice
                dsm-account-notice--success
            "
        >
            La variante se creó correctamente.
            Ya puedes gestionar su stock.
        </div>

    <?php elseif (
        $variantNotice === 'error'
        && $variantError !== ''
    ) : ?>

        <div
            class="
                dsm-account-notice
                dsm-account-notice--error
            "
        >
            <?php
            echo esc_html(
                $variantError
            );
            ?>
        </div>

    <?php endif; ?>


    <?php if (
        $stockNotice === 'replenished'
    ) : ?>

        <div
            class="
                dsm-account-notice
                dsm-account-notice--success
            "
        >
            El stock se repuso correctamente.
        </div>

    <?php elseif (
        $stockNotice === 'adjusted'
    ) : ?>

        <div
            class="
                dsm-account-notice
                dsm-account-notice--success
            "
        >
            El inventario se ajustó correctamente.
        </div>

    <?php elseif (
        $stockNotice === 'error'
        && $stockError !== ''
    ) : ?>

        <div
            class="
                dsm-account-notice
                dsm-account-notice--error
            "
        >
            <?php
            echo esc_html(
                $stockError
            );
            ?>
        </div>

    <?php endif; ?>


    <?php if (
        $productVariants === []
    ) : ?>

        <div
            class="
                dsm-account-notice
                dsm-account-notice--info
            "
        >
            Este producto todavía no tiene variantes.
            Usa «Nueva variante» para crear la primera.
        </div>

    <?php else : ?>


        <!-- FILTROS Y CARRUSEL DE VARIANTES -->

        <div class="dsm-variant-browser">

            <div class="dsm-variant-browser__filters">

                <div>

                    <label for="dsm-variant-color">
                        Color
                    </label>

                    <select
                        id="dsm-variant-color"
                        data-dsm-variant-color
                    >

                        <option value="">
                            Todos los colores
                        </option>

                        <?php foreach (
                            $variantColors
                            as $color
                        ) : ?>

                            <option
                                value="<?php
                                echo esc_attr(
                                    $color
                                );
                                ?>"
                            >
                                <?php
                                echo esc_html(
                                    $color
                                );
                                ?>
                            </option>

                        <?php endforeach; ?>

                    </select>

                </div>


                <div>

                    <label for="dsm-variant-size">
                        Talla
                    </label>

                    <select
                        id="dsm-variant-size"
                        data-dsm-variant-size
                    >

                        <option value="">
                            Todas las tallas
                        </option>

                        <?php foreach (
                            $variantSizes
                            as $size
                        ) : ?>

                            <option
                                value="<?php
                                echo esc_attr(
                                    $size
                                );
                                ?>"
                            >
                                <?php
                                echo esc_html(
                                    $size
                                );
                                ?>
                            </option>

                        <?php endforeach; ?>

                    </select>

                </div>


                <div class="dsm-variant-browser__sku">

                    <label for="dsm-variant-sku">
                        SKU
                    </label>

                    <input
                        id="dsm-variant-sku"
                        type="search"
                        data-dsm-variant-sku
                        placeholder="Buscar SKU..."
                        autocomplete="off"
                    >

                </div>


                <div class="dsm-variant-browser__reset-wrap">

                    <button
                        type="button"
                        class="
                            dsm-button
                            dsm-button--secondary
                            dsm-variant-browser__reset
                        "
                        data-dsm-variant-reset
                    >
                        Limpiar
                    </button>

                </div>

            </div>


            <div class="dsm-variant-browser__summary">

                <strong data-dsm-variant-count>
                    <?php
                    echo esc_html(
                        (string)
                        count($productVariants)
                    );
                    ?>
                </strong>

                <span data-dsm-variant-count-label>
                    <?php
                    echo count($productVariants) === 1
                        ? 'variante disponible'
                        : 'variantes disponibles';
                    ?>
                </span>

            </div>


            <div class="dsm-variant-carousel">

                <button
                    type="button"
                    class="
                        dsm-variant-carousel__arrow
                        dsm-variant-carousel__arrow--prev
                    "
                    data-dsm-variant-prev
                    aria-label="Ver variantes anteriores"
                >
                    ‹
                </button>


                <div
                    class="dsm-variant-carousel__viewport"
                    data-dsm-variant-viewport
                >

                    <div
                        class="dsm-variant-carousel__track"
                        data-dsm-variant-track
                    >

                        <?php foreach (
                            $productVariants
                            as $carouselVariant
                        ) : ?>

                            <?php

                            $carouselColor =
                                trim(
                                    (string) (
                                        $carouselVariant
                                            ->getColorValue()
                                        ?? ''
                                    )
                                );

                            $carouselSize =
                                trim(
                                    (string) (
                                        $carouselVariant
                                            ->getSizeValue()
                                        ?? ''
                                    )
                                );

                            $carouselSku =
                                trim(
                                    (string) (
                                        $carouselVariant
                                            ->getSku()
                                        ?? ''
                                    )
                                );

                            $carouselStatusLabel =
                                'Disponible';

                            $carouselStatusClass =
                                'available';

                            if (
                                !$carouselVariant->isActive()
                                || $carouselVariant->isArchived()
                            ) {
                                $carouselStatusLabel =
                                    'Inactiva';

                                $carouselStatusClass =
                                    'inactive';
                            } elseif (
                                $carouselVariant->isOutOfStock()
                            ) {
                                $carouselStatusLabel =
                                    'Sin stock';

                                $carouselStatusClass =
                                    'out-of-stock';
                            } elseif (
                                $carouselVariant->isLowStock()
                            ) {
                                $carouselStatusLabel =
                                    'Stock bajo';

                                $carouselStatusClass =
                                    'low-stock';
                            }

                            $isSelectedCarouselVariant =
                                $selectedVariant !== null
                                && $selectedVariant->getId()
                                    === $carouselVariant->getId();

                            $carouselUrl =
                                add_query_arg(
                                    [
                                        'store_section' =>
                                            'edit-product',

                                        'product_id' =>
                                            $editProduct->getId(),

                                        'variant_id' =>
                                            $carouselVariant->getId(),
                                    ],
                                    home_url(
                                        '/mi-tienda/'
                                    )
                                )
                                . '#dsm-product-variants';

                            ?>

                            <a
                                href="<?php
                                echo esc_url(
                                    $carouselUrl
                                );
                                ?>"
                                class="<?php
                                echo esc_attr(
                                    'dsm-variant-card'
                                    . (
                                        $isSelectedCarouselVariant
                                            ? ' is-selected'
                                            : ''
                                    )
                                );
                                ?>"
                                data-dsm-variant-card
                                data-color="<?php
                                echo esc_attr(
                                    $carouselColor
                                );
                                ?>"
                                data-size="<?php
                                echo esc_attr(
                                    $carouselSize
                                );
                                ?>"
                                data-sku="<?php
                                echo esc_attr(
                                    $carouselSku
                                );
                                ?>"
                            >

                                <div class="dsm-variant-card__heading">

                                    <strong>

                                        <?php
                                        echo esc_html(
                                            $carouselColor !== ''
                                                ? $carouselColor
                                                : 'Sin color'
                                        );
                                        ?>

                                        /

                                        <?php
                                        echo esc_html(
                                            $carouselSize !== ''
                                                ? $carouselSize
                                                : 'Sin talla'
                                        );
                                        ?>

                                    </strong>

                                    <?php if (
                                        $isSelectedCarouselVariant
                                    ) : ?>

                                        <span
                                            class="
                                                dsm-variant-card__selected
                                            "
                                        >
                                            ✓ Seleccionada
                                        </span>

                                    <?php endif; ?>

                                </div>


                                <div class="dsm-variant-card__sku">

                                    SKU:

                                    <strong>
                                        <?php
                                        echo esc_html(
                                            $carouselSku !== ''
                                                ? $carouselSku
                                                : '—'
                                        );
                                        ?>
                                    </strong>

                                </div>


                                <dl class="dsm-variant-card__stock">

                                    <div>

                                        <dt>
                                            Físico
                                        </dt>

                                        <dd>
                                            <?php
                                            echo esc_html(
                                                (string)
                                                $carouselVariant
                                                    ->getStockQuantity()
                                            );
                                            ?>
                                        </dd>

                                    </div>

                                    <div>

                                        <dt>
                                            Reservado
                                        </dt>

                                        <dd>
                                            <?php
                                            echo esc_html(
                                                (string)
                                                $carouselVariant
                                                    ->getStockReserved()
                                            );
                                            ?>
                                        </dd>

                                    </div>

                                    <div>

                                        <dt>
                                            Disponible
                                        </dt>

                                        <dd>
                                            <?php
                                            echo esc_html(
                                                $carouselVariant
                                                    ->tracksStock()
                                                    ? (string)
                                                        $carouselVariant
                                                            ->getAvailableStock()
                                                    : '∞'
                                            );
                                            ?>
                                        </dd>

                                    </div>

                                </dl>


                                <span
                                    class="<?php
                                    echo esc_attr(
                                        'dsm-variant-card__status '
                                        . 'dsm-variant-card__status--'
                                        . $carouselStatusClass
                                    );
                                    ?>"
                                >
                                    <?php
                                    echo esc_html(
                                        $carouselStatusLabel
                                    );
                                    ?>
                                </span>

                            </a>

                        <?php endforeach; ?>

                    </div>

                </div>


                <button
                    type="button"
                    class="
                        dsm-variant-carousel__arrow
                        dsm-variant-carousel__arrow--next
                    "
                    data-dsm-variant-next
                    aria-label="Ver más variantes"
                >
                    ›
                </button>

            </div>


            <div
                class="dsm-variant-browser__empty"
                data-dsm-variant-empty
                hidden
            >
                No hay variantes que coincidan
                con esos filtros.
            </div>

        </div>


        <script>
        (() => {
            const root =
                document.querySelector(
                    '#dsm-product-variants'
                );

            if (!root) {
                return;
            }

            const colorSelect =
                root.querySelector(
                    '[data-dsm-variant-color]'
                );

            const sizeSelect =
                root.querySelector(
                    '[data-dsm-variant-size]'
                );

            const skuInput =
                root.querySelector(
                    '[data-dsm-variant-sku]'
                );

            const resetButton =
                root.querySelector(
                    '[data-dsm-variant-reset]'
                );

            const viewport =
                root.querySelector(
                    '[data-dsm-variant-viewport]'
                );

            const cards =
                Array.from(
                    root.querySelectorAll(
                        '[data-dsm-variant-card]'
                    )
                );

            const countElement =
                root.querySelector(
                    '[data-dsm-variant-count]'
                );

            const countLabel =
                root.querySelector(
                    '[data-dsm-variant-count-label]'
                );

            const emptyElement =
                root.querySelector(
                    '[data-dsm-variant-empty]'
                );

            const prevButton =
                root.querySelector(
                    '[data-dsm-variant-prev]'
                );

            const nextButton =
                root.querySelector(
                    '[data-dsm-variant-next]'
                );

            if (
                !colorSelect
                || !sizeSelect
                || !skuInput
                || !viewport
            ) {
                return;
            }


            const normalize = (value) => {
                return String(value || '')
                    .trim()
                    .toLocaleLowerCase('es');
            };


            const allSizes =
                Array.from(sizeSelect.options)
                    .slice(1)
                    .map((option) => option.value);


            const rebuildSizes = () => {
                const currentValue =
                    sizeSelect.value;

                const selectedColor =
                    normalize(
                        colorSelect.value
                    );

                const compatibleSizes =
                    new Set();

                cards.forEach((card) => {
                    const cardColor =
                        normalize(
                            card.dataset.color
                        );

                    if (
                        selectedColor !== ''
                        && cardColor
                            !== selectedColor
                    ) {
                        return;
                    }

                    const size =
                        String(
                            card.dataset.size
                            || ''
                        ).trim();

                    if (size !== '') {
                        compatibleSizes.add(
                            size
                        );
                    }
                });


                sizeSelect.innerHTML = '';

                const allOption =
                    document.createElement(
                        'option'
                    );

                allOption.value = '';
                allOption.textContent =
                    'Todas las tallas';

                sizeSelect.appendChild(
                    allOption
                );


                allSizes.forEach((size) => {
                    if (
                        !compatibleSizes.has(
                            size
                        )
                    ) {
                        return;
                    }

                    const option =
                        document.createElement(
                            'option'
                        );

                    option.value = size;
                    option.textContent = size;

                    sizeSelect.appendChild(
                        option
                    );
                });


                const stillExists =
                    Array.from(
                        sizeSelect.options
                    )
                    .some(
                        (option) =>
                            option.value
                            === currentValue
                    );

                sizeSelect.value =
                    stillExists
                        ? currentValue
                        : '';
            };


            const updateArrows = () => {
                const visibleCards =
                    cards.filter(
                        (card) =>
                            !card.hidden
                    );

                const enoughCards =
                    visibleCards.length > 1
                    && viewport.scrollWidth
                        > viewport.clientWidth + 4;

                prevButton.hidden =
                    !enoughCards;

                nextButton.hidden =
                    !enoughCards;

                if (!enoughCards) {
                    return;
                }

                prevButton.disabled =
                    viewport.scrollLeft <= 4;

                nextButton.disabled =
                    viewport.scrollLeft
                    + viewport.clientWidth
                    >= viewport.scrollWidth
                    - 4;
            };


            const applyFilters = () => {
                const color =
                    normalize(
                        colorSelect.value
                    );

                const size =
                    normalize(
                        sizeSelect.value
                    );

                const sku =
                    normalize(
                        skuInput.value
                    );

                let visibleCount = 0;

                cards.forEach((card) => {
                    const cardColor =
                        normalize(
                            card.dataset.color
                        );

                    const cardSize =
                        normalize(
                            card.dataset.size
                        );

                    const cardSku =
                        normalize(
                            card.dataset.sku
                        );

                    const matchesColor =
                        color === ''
                        || cardColor === color;

                    const matchesSize =
                        size === ''
                        || cardSize === size;

                    const matchesSku =
                        sku === ''
                        || cardSku.includes(
                            sku
                        );

                    const visible =
                        matchesColor
                        && matchesSize
                        && matchesSku;

                    card.hidden =
                        !visible;

                    if (visible) {
                        visibleCount++;
                    }
                });


                countElement.textContent =
                    String(
                        visibleCount
                    );

                countLabel.textContent =
                    visibleCount === 1
                        ? 'variante disponible'
                        : 'variantes disponibles';

                emptyElement.hidden =
                    visibleCount !== 0;

                viewport.hidden =
                    visibleCount === 0;

                viewport.scrollTo({
                    left: 0,
                    behavior: 'smooth'
                });

                window.requestAnimationFrame(
                    updateArrows
                );
            };


            colorSelect.addEventListener(
                'change',
                () => {
                    rebuildSizes();
                    applyFilters();
                }
            );

            sizeSelect.addEventListener(
                'change',
                applyFilters
            );

            skuInput.addEventListener(
                'input',
                applyFilters
            );


            resetButton.addEventListener(
                'click',
                () => {
                    colorSelect.value = '';
                    skuInput.value = '';

                    rebuildSizes();

                    sizeSelect.value = '';

                    applyFilters();

                    colorSelect.focus();
                }
            );


            prevButton.addEventListener(
                'click',
                () => {
                    viewport.scrollBy({
                        left:
                            -Math.max(
                                260,
                                viewport.clientWidth
                                * 0.8
                            ),

                        behavior: 'smooth'
                    });
                }
            );


            nextButton.addEventListener(
                'click',
                () => {
                    viewport.scrollBy({
                        left:
                            Math.max(
                                260,
                                viewport.clientWidth
                                * 0.8
                            ),

                        behavior: 'smooth'
                    });
                }
            );


            viewport.addEventListener(
                'scroll',
                updateArrows,
                {
                    passive: true
                }
            );

            window.addEventListener(
                'resize',
                updateArrows
            );


            rebuildSizes();
            applyFilters();


            const selectedCard =
                root.querySelector(
                    '.dsm-variant-card.is-selected'
                );

            if (selectedCard) {
                window.requestAnimationFrame(
                    () => {
                        selectedCard.scrollIntoView({
                            behavior: 'smooth',
                            block: 'nearest',
                            inline: 'center'
                        });
                    }
                );
            }
        })();
        </script>


        <!-- VARIANTE SELECCIONADA -->

        <?php if (
            $selectedVariant === null
        ) : ?>

            <div
                class="
                    dsm-account-notice
                    dsm-account-notice--info
                "
            >
                No existe ninguna variante que coincida
                con esos filtros.
            </div>

        <?php else : ?>

            <?php

            $variantStatusLabel =
                'Disponible';

            $variantStatusClass =
                'available';

            if (
                !$selectedVariant->isActive()
                || $selectedVariant->isArchived()
            ) {
                $variantStatusLabel =
                    'Inactiva';

                $variantStatusClass =
                    'inactive';
            } elseif (
                $selectedVariant->isOutOfStock()
            ) {
                $variantStatusLabel =
                    'Sin stock';

                $variantStatusClass =
                    'out-of-stock';
            } elseif (
                $selectedVariant->isLowStock()
            ) {
                $variantStatusLabel =
                    'Stock bajo';

                $variantStatusClass =
                    'low-stock';
            }

            ?>

            <section class="dsm-selected-variant">

                <div class="dsm-selected-variant__heading">

                    <div>

                        <h3>

                            <?php
                            echo esc_html(
                                $selectedVariant
                                    ->getColorValue()
                                ?? 'Sin color'
                            );
                            ?>

                            /

                            <?php
                            echo esc_html(
                                $selectedVariant
                                    ->getSizeValue()
                                ?? 'Sin talla'
                            );
                            ?>

                        </h3>

                        <p>
                            <strong>SKU:</strong>

                            <?php
                            echo esc_html(
                                $selectedVariant->hasSku()
                                    ? $selectedVariant
                                        ->getSku()
                                    : '—'
                            );
                            ?>
                        </p>

                    </div>


                    <span
                        class="<?php
                        echo esc_attr(
                            'dsm-selected-variant__status '
                            . 'dsm-selected-variant__status--'
                            . $variantStatusClass
                        );
                        ?>"
                    >
                        <?php
                        echo esc_html(
                            $variantStatusLabel
                        );
                        ?>
                    </span>

                </div>


                <div class="dsm-selected-variant__stock">

                    <div>

                        <span>
                            Stock físico
                        </span>

                        <strong>
                            <?php
                            echo esc_html(
                                (string)
                                $selectedVariant
                                    ->getStockQuantity()
                            );
                            ?>
                        </strong>

                    </div>

                    <div>

                        <span>
                            Reservado
                        </span>

                        <strong>
                            <?php
                            echo esc_html(
                                (string)
                                $selectedVariant
                                    ->getStockReserved()
                            );
                            ?>
                        </strong>

                    </div>

                    <div>

                        <span>
                            Disponible
                        </span>

                        <strong>
                            <?php
                            echo esc_html(
                                (string)
                                $selectedVariant
                                    ->getAvailableStock()
                            );
                            ?>
                        </strong>

                    </div>

                    <div>

                        <span>
                            Aviso stock bajo
                        </span>

                        <strong>
                            <?php
                            echo esc_html(
                                $selectedVariant
                                    ->getLowStockThreshold()
                                !== null
                                    ? (string)
                                        $selectedVariant
                                            ->getLowStockThreshold()
                                    : '—'
                            );
                            ?>
                        </strong>

                    </div>

                </div>


                <?php if (
                    $selectedVariant->isActive()
                    && !$selectedVariant->isArchived()
                    && $selectedVariant->tracksStock()
                ) : ?>

                    <div class="dsm-stock-actions">


                        <!-- REPONER -->

                        <details class="dsm-stock-action">

                            <summary
                                class="
                                    dsm-button
                                    dsm-button--primary
                                "
                            >
                                Reponer stock
                            </summary>

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

                                <h4>
                                    Reponer stock
                                </h4>

                                <p class="dsm-stock-action__help">
                                    Registra la entrada real de
                                    nuevas unidades.
                                </p>

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
                                        $selectedVariant->getId()
                                    );
                                    ?>"
                                >

                                <?php
                                wp_nonce_field(
                                    StoreStockController::
                                        getReplenishNonceAction(
                                            $selectedVariant->getId()
                                        ),
                                    StoreStockController::
                                        NONCE_FIELD
                                );
                                ?>

                                <div class="dsm-product-editor-field">

                                    <label>
                                        Unidades que entran
                                    </label>

                                    <input
                                        type="number"
                                        name="quantity"
                                        min="1"
                                        step="1"
                                        required
                                    >

                                </div>

                                <div class="dsm-product-editor-field">

                                    <label>
                                        Motivo
                                    </label>

                                    <textarea
                                        name="notes"
                                        rows="3"
                                        required
                                        placeholder="Ej. Entrada de mercancía"
                                    ></textarea>

                                </div>

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

                        </details>


                        <!-- AJUSTAR -->

                        <details class="dsm-stock-action">

                            <summary
                                class="
                                    dsm-button
                                    dsm-button--secondary
                                "
                            >
                                Ajustar inventario
                            </summary>

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

                                <h4>
                                    Ajustar inventario
                                </h4>

                                <p class="dsm-stock-action__help">
                                    Corrige diferencias del inventario.
                                    Usa un valor negativo para retirar
                                    unidades y positivo para añadirlas.
                                </p>

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
                                        $selectedVariant->getId()
                                    );
                                    ?>"
                                >

                                <?php
                                wp_nonce_field(
                                    StoreStockController::
                                        getAdjustNonceAction(
                                            $selectedVariant->getId()
                                        ),
                                    StoreStockController::
                                        NONCE_FIELD
                                );
                                ?>

                                <div class="dsm-product-editor-field">

                                    <label>
                                        Ajuste
                                    </label>

                                    <input
                                        type="number"
                                        name="quantity_delta"
                                        step="1"
                                        required
                                        placeholder="-2 o 3"
                                    >

                                </div>

                                <div class="dsm-product-editor-field">

                                    <label>
                                        Motivo
                                    </label>

                                    <textarea
                                        name="notes"
                                        rows="3"
                                        required
                                        placeholder="Ej. Prenda dañada"
                                    ></textarea>

                                </div>

                                <button
                                    type="submit"
                                    class="
                                        dsm-button
                                        dsm-button--secondary
                                    "
                                >
                                    Ajustar inventario
                                </button>

                            </form>

                        </details>

                    </div>

                <?php endif; ?>

            </section>

        <?php endif; ?>

    <?php endif; ?>

</article>


<p class="dsm-product-editor-back">

    <a
        class="
            dsm-button
            dsm-button--secondary
            dsm-product-editor-back__button
        "
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
        ← Volver a productos
    </a>

</p>
