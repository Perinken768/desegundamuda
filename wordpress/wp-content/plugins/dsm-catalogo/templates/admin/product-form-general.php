<?php

declare(strict_types=1);

use DSM\Catalogo\Brand\Brand;
use DSM\Catalogo\Product\Product;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Variables heredadas de product-form.php:
 *
 * @var Product|null $product
 * @var array<int, Brand> $brands
 * @var array<int, array<string, mixed>> $categories
 * @var bool $isEditing
 */

$name =
    $isEditing
        ? $product->getName()
        : '';

$slug =
    $isEditing
        ? $product->getSlug()
        : '';

$description =
    $isEditing
        ? ($product->getDescription() ?? '')
        : '';

$selectedBrandId =
    $isEditing
        ? $product->getBrandId()
        : null;

$selectedCategoryId =
    $isEditing
        ? $product->getCategoryId()
        : null;
?>

<div class="dsm-panel">
    <div class="dsm-panel-header">
        <h2>
            <?php
            echo esc_html__(
                'Información general',
                'dsm-catalogo'
            );
            ?>
        </h2>
    </div>

    <div class="dsm-panel-body">
        <table
            class="form-table"
            role="presentation"
        >
            <tbody>
                <tr>
                    <th scope="row">
                        <label for="dsm-product-name">
                            <?php
                            echo esc_html__(
                                'Nombre',
                                'dsm-catalogo'
                            );
                            ?>
                        </label>
                    </th>

                    <td>
                        <input
                            type="text"
                            id="dsm-product-name"
                            name="name"
                            value="<?php
                            echo esc_attr($name);
                            ?>"
                            class="regular-text"
                            maxlength="180"
                            autocomplete="off"
                            required
                        >

                        <p class="description">
                            <?php
                            echo esc_html__(
                                'Nombre base del producto. Las variantes añadirán posteriormente talla, color u otras características.',
                                'dsm-catalogo'
                            );
                            ?>
                        </p>
                    </td>
                </tr>

                <tr>
                    <th scope="row">
                        <label for="dsm-product-category">
                            <?php
                            echo esc_html__(
                                'Categoría',
                                'dsm-catalogo'
                            );
                            ?>
                        </label>
                    </th>

                    <td>
                        <select
                            id="dsm-product-category"
                            name="category_id"
                            class="regular-text"
                            required
                        >
                            <option value="">
                                <?php
                                echo esc_html__(
                                    'Selecciona una categoría',
                                    'dsm-catalogo'
                                );
                                ?>
                            </option>

                            <?php foreach ($categories as $category): ?>
                                <?php
                                $categoryId =
                                    isset($category['id'])
                                        ? (int) $category['id']
                                        : 0;

                                $categoryName =
                                    isset($category['name'])
                                        ? (string) $category['name']
                                        : '';

                                $parentId =
                                    isset($category['parent_id'])
                                        ? (int) $category['parent_id']
                                        : 0;

                                if (
                                    $categoryId <= 0
                                    || $categoryName === ''
                                ) {
                                    continue;
                                }
                                ?>

                                <option
                                    value="<?php
                                    echo esc_attr(
                                        (string) $categoryId
                                    );
                                    ?>"
                                    <?php
                                    selected(
                                        $selectedCategoryId,
                                        $categoryId
                                    );
                                    ?>
                                >
                                    <?php
                                    echo esc_html(
                                        $parentId > 0
                                            ? '— ' . $categoryName
                                            : $categoryName
                                    );
                                    ?>
                                </option>
                            <?php endforeach; ?>
                        </select>

                        <p class="description">
                            <?php
                            echo esc_html__(
                                'Categoría del producto. Solo aparecen categorías activas y disponibles para tiendas.',
                                'dsm-catalogo'
                            );
                            ?>
                        </p>

                        <?php if ($categories === []): ?>
                            <p class="description">
                                <strong>
                                    <?php
                                    echo esc_html__(
                                        'No hay categorías disponibles para productos de tienda.',
                                        'dsm-catalogo'
                                    );
                                    ?>
                                </strong>
                            </p>
                        <?php endif; ?>
                    </td>
                </tr>

                <tr>
                    <th scope="row">
                        <label for="dsm-product-brand">
                            <?php
                            echo esc_html__(
                                'Marca',
                                'dsm-catalogo'
                            );
                            ?>
                        </label>
                    </th>

                    <td>
                        <select
                            id="dsm-product-brand"
                            name="brand_id"
                            class="regular-text"
                        >
                            <option value="">
                                <?php
                                echo esc_html__(
                                    'Sin marca',
                                    'dsm-catalogo'
                                );
                                ?>
                            </option>

                            <?php foreach ($brands as $brand): ?>
                                <option
                                    value="<?php
                                    echo esc_attr(
                                        (string) $brand->getId()
                                    );
                                    ?>"
                                    <?php
                                    selected(
                                        $selectedBrandId,
                                        $brand->getId()
                                    );
                                    ?>
                                >
                                    <?php
                                    echo esc_html(
                                        $brand->getName()
                                    );
                                    ?>
                                </option>
                            <?php endforeach; ?>
                        </select>

                        <p class="description">
                            <?php
                            echo esc_html__(
                                'La marca es opcional. Solo aparecen marcas activas y verificadas.',
                                'dsm-catalogo'
                            );
                            ?>
                        </p>
                    </td>
                </tr>

                <tr>
                    <th scope="row">
                        <label for="dsm-product-slug">
                            <?php
                            echo esc_html__(
                                'Slug',
                                'dsm-catalogo'
                            );
                            ?>
                        </label>
                    </th>

                    <td>
                        <input
                            type="text"
                            id="dsm-product-slug"
                            name="slug"
                            value="<?php
                            echo esc_attr($slug);
                            ?>"
                            class="regular-text"
                            maxlength="200"
                            autocomplete="off"
                        >

                        <p class="description">
                            <?php
                            echo esc_html__(
                                'Identificador utilizado en las URLs. Si se deja vacío, se generará automáticamente.',
                                'dsm-catalogo'
                            );
                            ?>
                        </p>
                    </td>
                </tr>

                <tr>
                    <th scope="row">
                        <label for="dsm-product-description">
                            <?php
                            echo esc_html__(
                                'Descripción',
                                'dsm-catalogo'
                            );
                            ?>
                        </label>
                    </th>

                    <td>
                        <textarea
                            id="dsm-product-description"
                            name="description"
                            rows="10"
                            class="large-text"
                        ><?php
                        echo esc_textarea(
                            $description
                        );
                        ?></textarea>

                        <p class="description">
                            <?php
                            echo esc_html__(
                                'Descripción general del producto. No incluyas aquí la talla o el color de una variante concreta.',
                                'dsm-catalogo'
                            );
                            ?>
                        </p>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</div>