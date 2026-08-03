<?php

declare(strict_types=1);

use DSM\Catalogo\Product\Product;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Variables heredadas de product-form.php:
 *
 * @var Product|null $product
 * @var bool $isEditing
 */

$internalReference =
    $isEditing
        ? ($product->getInternalReference() ?? '')
        : '';

$baseSku =
    $isEditing
        ? ($product->getBaseSku() ?? '')
        : '';

$defaultPrice =
    $isEditing
        ? number_format(
            $product->getDefaultPrice(),
            2,
            '.',
            ''
        )
        : '0.00';

$originalPrice =
    $isEditing
    && $product->getOriginalPrice() !== null
        ? number_format(
            $product->getOriginalPrice(),
            2,
            '.',
            ''
        )
        : '';

$costPrice =
    $isEditing
    && $product->getCostPrice() !== null
        ? number_format(
            $product->getCostPrice(),
            2,
            '.',
            ''
        )
        : '';

$purchaseDate =
    $isEditing
        ? (
            $product
                ->getPurchaseDate()
                ?->format('Y-m-d')
            ?? ''
        )
        : '';

$taxRate =
    $isEditing
    && $product->getTaxRate() !== null
        ? number_format(
            $product->getTaxRate(),
            2,
            '.',
            ''
        )
        : '';
?>

<div class="dsm-panel">
    <div class="dsm-panel-header">
        <h2>
            <?php
            echo esc_html__(
                'Información comercial',
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
                        <label for="dsm-product-internal-reference">
                            <?php
                            echo esc_html__(
                                'Referencia interna',
                                'dsm-catalogo'
                            );
                            ?>
                        </label>
                    </th>

                    <td>
                        <input
                            type="text"
                            id="dsm-product-internal-reference"
                            name="internal_reference"
                            value="<?php
                            echo esc_attr(
                                $internalReference
                            );
                            ?>"
                            class="regular-text"
                            maxlength="100"
                            autocomplete="off"
                        >

                        <p class="description">
                            <?php
                            echo esc_html__(
                                'Código interno de la tienda. Debe ser único dentro de la misma tienda.',
                                'dsm-catalogo'
                            );
                            ?>
                        </p>
                    </td>
                </tr>

                <tr>
                    <th scope="row">
                        <label for="dsm-product-base-sku">
                            <?php
                            echo esc_html__(
                                'SKU base',
                                'dsm-catalogo'
                            );
                            ?>
                        </label>
                    </th>

                    <td>
                        <input
                            type="text"
                            id="dsm-product-base-sku"
                            name="base_sku"
                            value="<?php
                            echo esc_attr($baseSku);
                            ?>"
                            class="regular-text"
                            maxlength="100"
                            autocomplete="off"
                        >

                        <p class="description">
                            <?php
                            echo esc_html__(
                                'Código base utilizado para construir los SKU de las variantes.',
                                'dsm-catalogo'
                            );
                            ?>
                        </p>
                    </td>
                </tr>

                <tr>
                    <th scope="row">
                        <label for="dsm-product-default-price">
                            <?php
                            echo esc_html__(
                                'Precio de venta',
                                'dsm-catalogo'
                            );
                            ?>
                        </label>
                    </th>

                    <td>
                        <div class="dsm-input-suffix">
                            <input
                                type="number"
                                id="dsm-product-default-price"
                                name="default_price"
                                value="<?php
                                echo esc_attr(
                                    $defaultPrice
                                );
                                ?>"
                                min="0"
                                step="0.01"
                                inputmode="decimal"
                                required
                            >

                            <span aria-hidden="true">
                                €
                            </span>
                        </div>

                        <p class="description">
                            <?php
                            echo esc_html__(
                                'Precio predeterminado. Cada variante podrá definir un precio diferente.',
                                'dsm-catalogo'
                            );
                            ?>
                        </p>
                    </td>
                </tr>

                <tr>
                    <th scope="row">
                        <label for="dsm-product-original-price">
                            <?php
                            echo esc_html__(
                                'Precio original',
                                'dsm-catalogo'
                            );
                            ?>
                        </label>
                    </th>

                    <td>
                        <div class="dsm-input-suffix">
                            <input
                                type="number"
                                id="dsm-product-original-price"
                                name="original_price"
                                value="<?php
                                echo esc_attr(
                                    $originalPrice
                                );
                                ?>"
                                min="0"
                                step="0.01"
                                inputmode="decimal"
                            >

                            <span aria-hidden="true">
                                €
                            </span>
                        </div>

                        <p class="description">
                            <?php
                            echo esc_html__(
                                'Precio anterior o precio recomendado. Es opcional.',
                                'dsm-catalogo'
                            );
                            ?>
                        </p>
                    </td>
                </tr>

                <tr>
                    <th scope="row">
                        <label for="dsm-product-cost-price">
                            <?php
                            echo esc_html__(
                                'Precio de coste',
                                'dsm-catalogo'
                            );
                            ?>
                        </label>
                    </th>

                    <td>
                        <div class="dsm-input-suffix">
                            <input
                                type="number"
                                id="dsm-product-cost-price"
                                name="cost_price"
                                value="<?php
                                echo esc_attr(
                                    $costPrice
                                );
                                ?>"
                                min="0"
                                step="0.01"
                                inputmode="decimal"
                            >

                            <span aria-hidden="true">
                                €
                            </span>
                        </div>

                        <p class="description">
                            <?php
                            echo esc_html__(
                                'Dato interno utilizado para calcular márgenes. No se mostrará al comprador.',
                                'dsm-catalogo'
                            );
                            ?>
                        </p>
                    </td>
                </tr>

                <tr>
                    <th scope="row">
                        <label for="dsm-product-tax-rate">
                            <?php
                            echo esc_html__(
                                'Tipo impositivo',
                                'dsm-catalogo'
                            );
                            ?>
                        </label>
                    </th>

                    <td>
                        <div class="dsm-input-suffix">
                            <input
                                type="number"
                                id="dsm-product-tax-rate"
                                name="tax_rate"
                                value="<?php
                                echo esc_attr($taxRate);
                                ?>"
                                min="0"
                                max="100"
                                step="0.01"
                                inputmode="decimal"
                            >

                            <span aria-hidden="true">
                                %
                            </span>
                        </div>

                        <p class="description">
                            <?php
                            echo esc_html__(
                                'Porcentaje impositivo aplicable al producto. Es opcional.',
                                'dsm-catalogo'
                            );
                            ?>
                        </p>
                    </td>
                </tr>

                <tr>
                    <th scope="row">
                        <label for="dsm-product-purchase-date">
                            <?php
                            echo esc_html__(
                                'Fecha de compra',
                                'dsm-catalogo'
                            );
                            ?>
                        </label>
                    </th>

                    <td>
                        <input
                            type="date"
                            id="dsm-product-purchase-date"
                            name="purchase_date"
                            value="<?php
                            echo esc_attr(
                                $purchaseDate
                            );
                            ?>"
                        >

                        <p class="description">
                            <?php
                            echo esc_html__(
                                'Fecha de compra o entrada original del producto. Es opcional.',
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