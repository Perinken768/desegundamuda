<?php

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

$locationContext =
    dsm_theme_home_location_context();

$islands =
    dsm_theme_home_islands();

$currentAreaId =
    max(
        0,
        (int) (
            $locationContext[
                'area_id'
            ]
            ?? 0
        )
    );

$categories =
    apply_filters(
        'dsm_public_categories',
        []
    );

if (!is_array($categories)) {
    $categories = [];
}

$currentType =
    isset($_GET['tipo'])
        ? sanitize_key(
            wp_unslash(
                (string) $_GET['tipo']
            )
        )
        : 'todos';

$currentCategory =
    isset($_GET['dsm_category'])
        ? absint(
            wp_unslash(
                (string) $_GET[
                    'dsm_category'
                ]
            )
        )
        : 0;

$currentMinPrice =
    isset($_GET['dsm_min_price'])
        ? sanitize_text_field(
            wp_unslash(
                (string) $_GET[
                    'dsm_min_price'
                ]
            )
        )
        : '';

$currentMaxPrice =
    isset($_GET['dsm_max_price'])
        ? sanitize_text_field(
            wp_unslash(
                (string) $_GET[
                    'dsm_max_price'
                ]
            )
        )
        : '';

$currentOrder =
    isset($_GET['dsm_orderby'])
        ? sanitize_key(
            wp_unslash(
                (string) $_GET[
                    'dsm_orderby'
                ]
            )
        )
        : 'newest';

if (
    !in_array(
        $currentOrder,
        [
            'newest',
            'price_low',
            'price_high',
        ],
        true
    )
) {
    $currentOrder =
        'newest';
}
?>

<section class="dsm-home-filters">

    <div class="dsm-container">

        <div class="dsm-section-heading">
            <h2>
                <?php
                echo esc_html(
                    dsm_theme_home_filters_title()
                );
                ?>
            </h2>
        </div>

        <form
            class="dsm-filter-bar"
            method="get"
            action="<?php
            echo esc_url(
                home_url('/')
            );
            ?>"
        >

            <input
                type="hidden"
                name="tipo"
                value="<?php
                echo esc_attr(
                    $currentType
                );
                ?>"
            >

            <?php if (
                isset($_GET['dsm_search'])
                && trim(
                    (string) $_GET[
                        'dsm_search'
                    ]
                ) !== ''
            ) : ?>

                <input
                    type="hidden"
                    name="dsm_search"
                    value="<?php
                    echo esc_attr(
                        sanitize_text_field(
                            wp_unslash(
                                (string) $_GET[
                                    'dsm_search'
                                ]
                            )
                        )
                    );
                    ?>"
                >

            <?php endif; ?>

            <div class="dsm-filter-field">

                <label for="dsm-filter-area">
                    Isla
                </label>

                <select
                    id="dsm-filter-area"
                    name="dsm_area"
                >

                    <option value="0">
                        Todas las islas
                    </option>

                    <?php foreach (
                        $islands
                        as $island
                    ) : ?>

                        <?php
                        if (!is_array($island)) {
                            continue;
                        }

                        $islandId =
                            max(
                                0,
                                (int) (
                                    $island['id']
                                    ?? 0
                                )
                            );

                        $islandName =
                            trim(
                                (string) (
                                    $island['name']
                                    ?? ''
                                )
                            );

                        if (
                            $islandId <= 0
                            || $islandName === ''
                        ) {
                            continue;
                        }
                        ?>

                        <option
                            value="<?php
                            echo esc_attr(
                                (string) $islandId
                            );
                            ?>"
                            <?php
                            selected(
                                $currentAreaId,
                                $islandId
                            );
                            ?>
                        >
                            <?php
                            echo esc_html(
                                $islandName
                            );
                            ?>
                        </option>

                    <?php endforeach; ?>

                </select>

            </div>

            <div class="dsm-filter-field">

                <label for="dsm-filter-category">
                    Categoría
                </label>

                <select
                    id="dsm-filter-category"
                    name="dsm_category"
                >

                    <option value="">
                        Todas
                    </option>

                    <?php foreach (
                        $categories
                        as $category
                    ) : ?>

                        <?php
                        if (!is_array($category)) {
                            continue;
                        }

                        $categoryId =
                            max(
                                0,
                                (int) (
                                    $category['id']
                                    ?? 0
                                )
                            );

                        $categoryName =
                            trim(
                                (string) (
                                    $category['name']
                                    ?? ''
                                )
                            );

                        $parentId =
                            isset(
                                $category[
                                    'parent_id'
                                ]
                            )
                                ? max(
                                    0,
                                    (int) $category[
                                        'parent_id'
                                    ]
                                )
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
                                (string)
                                $categoryId
                            );
                            ?>"
                            <?php
                            selected(
                                $currentCategory,
                                $categoryId
                            );
                            ?>
                        >
                            <?php
                            echo esc_html(
                                $parentId > 0
                                    ? '— '
                                        . $categoryName
                                    : $categoryName
                            );
                            ?>
                        </option>

                    <?php endforeach; ?>

                </select>

            </div>

            <div class="dsm-filter-field">

                <label for="dsm-filter-min-price">
                    Precio mínimo
                </label>

                <input
                    id="dsm-filter-min-price"
                    type="number"
                    name="dsm_min_price"
                    min="0"
                    step="0.01"
                    value="<?php
                    echo esc_attr(
                        $currentMinPrice
                    );
                    ?>"
                    placeholder="0 €"
                >

            </div>

            <div class="dsm-filter-field">

                <label for="dsm-filter-max-price">
                    Precio máximo
                </label>

                <input
                    id="dsm-filter-max-price"
                    type="number"
                    name="dsm_max_price"
                    min="0"
                    step="0.01"
                    value="<?php
                    echo esc_attr(
                        $currentMaxPrice
                    );
                    ?>"
                    placeholder="Sin límite"
                >

            </div>

            <div class="dsm-filter-field">

                <label for="dsm-filter-order">
                    Ordenar
                </label>

                <select
                    id="dsm-filter-order"
                    name="dsm_orderby"
                >

                    <option
                        value="newest"
                        <?php
                        selected(
                            $currentOrder,
                            'newest'
                        );
                        ?>
                    >
                        Más recientes
                    </option>

                    <option
                        value="price_low"
                        <?php
                        selected(
                            $currentOrder,
                            'price_low'
                        );
                        ?>
                    >
                        Precio: menor a mayor
                    </option>

                    <option
                        value="price_high"
                        <?php
                        selected(
                            $currentOrder,
                            'price_high'
                        );
                        ?>
                    >
                        Precio: mayor a menor
                    </option>

                </select>

            </div>

            <div class="dsm-filter-actions">

                <button
                    type="submit"
                    class="
                        dsm-button
                        dsm-button--primary
                    "
                >
                    Aplicar filtros
                </button>

                <a
                    class="
                        dsm-button
                        dsm-button--secondary
                    "
                    href="<?php
                    echo esc_url(
                        home_url('/')
                    );
                    ?>"
                >
                    Limpiar
                </a>

            </div>

        </form>

    </div>

</section>
