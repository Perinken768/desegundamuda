<?php

declare(strict_types=1);

use DSM\Catalogo\Brand\Brand;
use DSM\Multitienda\Frontend\StoreProductFormController;
use DSM\Multitienda\Store\Store;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * @var Store $store
 * @var array<int, array<string, mixed>> $productCategories
 * @var array<int, Brand> $productBrands
 * @var string $productFormNotice
 * @var string $productFormError
 */

?>

<article class="dsm-card">

    <header>

        <h2>
            Nuevo producto
        </h2>

        <p>
            Añade un producto al catálogo
            de tu tienda.
        </p>

    </header>

    <?php if (
        $productFormNotice === 'error'
        && $productFormError !== ''
    ) : ?>

        <div
            class="
                dsm-account-notice
                dsm-account-notice--error
            "
        >
            <?php
            echo esc_html(
                $productFormError
            );
            ?>
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
                StoreProductFormController::
                    CREATE_ACTION
            );
            ?>"
        >

        <?php
        wp_nonce_field(
            StoreProductFormController::
                getCreateNonceAction(
                    $store->getId()
                ),
            StoreProductFormController::
                NONCE_FIELD
        );
        ?>

        <fieldset>

            <legend>
                <strong>
                    Información principal
                </strong>
            </legend>

            <p>
                <label>
                    <strong>
                        Nombre del producto *
                    </strong>
                </label>

                <br>

                <input
                    type="text"
                    name="name"
                    class="regular-text"
                    maxlength="180"
                    required
                >
            </p>

            <p>
                <label>
                    <strong>
                        Categoría *
                    </strong>
                </label>

                <br>

                <select
                    name="category_id"
                    required
                >

                    <option value="">
                        Selecciona una categoría
                    </option>

                    <?php foreach (
                        $productCategories
                        as $category
                    ) : ?>

                        <?php
                        $categoryId =
                            (int) (
                                $category['id']
                                ?? 0
                            );

                        $categoryName =
                            (string) (
                                $category['name']
                                ?? ''
                            );

                        $parentId =
                            isset(
                                $category['parent_id']
                            )
                            && $category[
                                'parent_id'
                            ] !== null
                                ? (int) $category[
                                    'parent_id'
                                ]
                                : null;
                        ?>

                        <?php if (
                            $categoryId > 0
                            && $categoryName !== ''
                        ) : ?>

                            <option
                                value="<?php
                                echo esc_attr(
                                    (string) $categoryId
                                );
                                ?>"
                            >
                                <?php
                                echo esc_html(
                                    (
                                        $parentId !== null
                                            ? '↳ '
                                            : ''
                                    )
                                    . $categoryName
                                );
                                ?>
                            </option>

                        <?php endif; ?>

                    <?php endforeach; ?>

                </select>
            </p>

            <p>
                <label>
                    <strong>
                        Marca
                    </strong>
                </label>

                <br>

                <select name="brand_id">

                    <option value="">
                        Sin marca
                    </option>

                    <?php foreach (
                        $productBrands
                        as $brand
                    ) : ?>

                        <option
                            value="<?php
                            echo esc_attr(
                                (string)
                                $brand->getId()
                            );
                            ?>"
                        >
                            <?php
                            echo esc_html(
                                $brand->getName()
                            );
                            ?>
                        </option>

                    <?php endforeach; ?>

                </select>

                <br>

                <small>
                    La marca es opcional.
                </small>
            </p>

            <p>
                <label>
                    <strong>
                        Slug
                    </strong>
                </label>

                <br>

                <input
                    type="text"
                    name="slug"
                    class="regular-text"
                    placeholder="camiseta-basica"
                >

                <br>

                <small>
                    Puedes dejarlo vacío para
                    generarlo desde el nombre.
                </small>
            </p>

            <p>
                <label>
                    <strong>
                        Descripción
                    </strong>
                </label>

                <br>

                <textarea
                    name="description"
                    rows="7"
                    class="large-text"
                ></textarea>
            </p>

        </fieldset>

        <hr>

        <fieldset>

            <legend>
                <strong>
                    Imágenes
                </strong>
            </legend>

            <p>
                <label>
                    <strong>
                        Fotografías del producto
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
                    Puedes subir hasta 10 imágenes.
                    La primera se utilizará como portada.
                </small>
            </p>

        </fieldset>

        <hr>

        <fieldset>

            <legend>
                <strong>
                    Referencias
                </strong>
            </legend>

            <p>
                <label>
                    <strong>
                        Referencia interna
                    </strong>
                </label>

                <br>

                <input
                    type="text"
                    name="internal_reference"
                    class="regular-text"
                    maxlength="100"
                >
            </p>

            <p>
                <label>
                    <strong>
                        SKU base
                    </strong>
                </label>

                <br>

                <input
                    type="text"
                    name="base_sku"
                    class="regular-text"
                    maxlength="100"
                >
            </p>

        </fieldset>

        <hr>

        <fieldset>

            <legend>
                <strong>
                    Información comercial
                </strong>
            </legend>

            <p>
                <label>
                    <strong>
                        Precio de venta *
                    </strong>
                </label>

                <br>

                <input
                    type="number"
                    name="default_price"
                    min="0"
                    step="0.01"
                    value="0.00"
                    required
                >

                €
            </p>

            <p>
                <label>
                    <strong>
                        Precio original
                    </strong>
                </label>

                <br>

                <input
                    type="number"
                    name="original_price"
                    min="0"
                    step="0.01"
                >

                €
            </p>

            <p>
                <small>
                    Si lo indicas, no puede ser
                    inferior al precio de venta.
                </small>
            </p>

            <p>
                <label>
                    <strong>
                        Precio de coste
                    </strong>
                </label>

                <br>

                <input
                    type="number"
                    name="cost_price"
                    min="0"
                    step="0.01"
                >

                €
            </p>

            <p>
                <label>
                    <strong>
                        Impuesto
                    </strong>
                </label>

                <br>

                <input
                    type="number"
                    name="tax_rate"
                    min="0"
                    step="0.01"
                >

                %
            </p>

            <p>
                <label>
                    <strong>
                        Fecha de compra
                    </strong>
                </label>

                <br>

                <input
                    type="date"
                    name="purchase_date"
                >
            </p>

        </fieldset>

        <hr>

        <fieldset>

            <legend>
                <strong>
                    Inventario
                </strong>
            </legend>

            <p>
                <label>

                    <input
                        type="checkbox"
                        name="track_stock"
                        value="1"
                        checked
                    >

                    <strong>
                        Controlar stock
                    </strong>

                </label>
            </p>

            <p>
                <small>
                    El stock se gestionará mediante
                    las variantes del producto.
                    Podrás indicar las unidades en
                    el siguiente paso.
                </small>
            </p>

        </fieldset>

        <hr>

        <p>

            <button
                type="submit"
                class="
                    dsm-button
                    dsm-button--primary
                "
            >
                Crear producto
            </button>

            &nbsp;

            <a
                href="<?php
                echo esc_url(
                    add_query_arg(
                        [
                            'store_section' =>
                                'products',
                        ],
                        home_url(
                            '/mi-tienda/'
                        )
                    )
                );
                ?>"
            >
                Cancelar
            </a>

        </p>

    </form>

</article>
