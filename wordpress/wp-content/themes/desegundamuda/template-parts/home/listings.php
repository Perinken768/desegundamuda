<?php

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

$contentType =
    isset($_GET['tipo'])
        ? sanitize_key(
            wp_unslash(
                (string) $_GET['tipo']
            )
        )
        : 'todos';

if (
    !in_array(
        $contentType,
        [
            'todos',
            'anuncios',
            'tiendas',
        ],
        true
    )
) {
    $contentType =
        'todos';
}

$locationContext =
    dsm_theme_home_location_context();

/*
 * Contrato neutral de resultados.
 *
 * Cada plugin puede añadir elementos sin que
 * el tema conozca sus repositorios internos.
 */
$listingItems =
    apply_filters(
        'dsm_theme_home_listing_items',
        [],
        [
            'type' =>
                $contentType,

            'area_id' =>
                (int) (
                    $locationContext[
                        'area_id'
                    ]
                    ?? 0
                ),

            'area_name' =>
                (string) (
                    $locationContext[
                        'area_name'
                    ]
                    ?? ''
                ),

            'area_type' =>
                (string) (
                    $locationContext[
                        'area_type'
                    ]
                    ?? ''
                ),
        ]
    );

if (!is_array($listingItems)) {
    $listingItems = [];
}

$orderBy =
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
        $orderBy,
        [
            'newest',
            'price_low',
            'price_high',
        ],
        true
    )
) {
    $orderBy =
        'newest';
}

/*
 * Los destacados conservan prioridad.
 *
 * Después aplicamos el orden elegido de forma
 * neutral sobre anuncios y productos de tienda.
 */
usort(
    $listingItems,
    static function (
        array $left,
        array $right
    ) use (
        $orderBy
    ): int {
        $leftPromoted =
            !empty(
                $left['is_promoted']
            );

        $rightPromoted =
            !empty(
                $right['is_promoted']
            );

        if (
            $leftPromoted
            !== $rightPromoted
        ) {
            return $leftPromoted
                ? -1
                : 1;
        }

        $leftPrice =
            isset($left['price'])
                ? (float) $left['price']
                : 0.0;

        $rightPrice =
            isset($right['price'])
                ? (float) $right['price']
                : 0.0;

        if (
            $orderBy
            === 'price_low'
        ) {
            return $leftPrice
                <=> $rightPrice;
        }

        if (
            $orderBy
            === 'price_high'
        ) {
            return $rightPrice
                <=> $leftPrice;
        }

        $leftTime =
            strtotime(
                (string) (
                    $left['published_at']
                    ?? ''
                )
            )
            ?: 0;

        $rightTime =
            strtotime(
                (string) (
                    $right['published_at']
                    ?? ''
                )
            )
            ?: 0;

        return $rightTime
            <=> $leftTime;
    }
);

$baseUrl =
    home_url('/');
?>

<section class="dsm-home-section dsm-home-listings">

    <div class="dsm-container">

        <div class="dsm-section-heading">

            <h2>
                <?php
                echo esc_html(
                    dsm_theme_home_results_title()
                );
                ?>
            </h2>

        </div>

        <nav
            class="dsm-listing-tabs"
            aria-label="Tipo de contenido"
        >

            <?php
            $tabs = [
                'todos' =>
                    'Todos',

                'anuncios' =>
                    'Anuncios',

                'tiendas' =>
                    'Tiendas',
            ];

            foreach (
                $tabs
                as $tabValue => $tabLabel
            ) :
                $tabArguments = [];

                foreach (
                    [
                        'dsm_search',
                        'dsm_area',
                        'dsm_category',
                        'dsm_min_price',
                        'dsm_max_price',
                        'dsm_orderby',
                    ]
                    as $parameter
                ) {
                    if (
                        !isset($_GET[$parameter])
                        || $_GET[$parameter] === ''
                    ) {
                        continue;
                    }

                    $tabArguments[$parameter] =
                        sanitize_text_field(
                            wp_unslash(
                                (string) $_GET[
                                    $parameter
                                ]
                            )
                        );
                }

                $tabArguments['tipo'] =
                    $tabValue;

                $tabUrl =
                    add_query_arg(
                        $tabArguments,
                        $baseUrl
                    );
                ?>

                <a
                    href="<?php
                    echo esc_url(
                        $tabUrl
                    );
                    ?>"
                    <?php if (
                        $contentType
                        === $tabValue
                    ) : ?>
                        aria-current="page"
                        class="is-active"
                    <?php endif; ?>
                >
                    <?php
                    echo esc_html(
                        $tabLabel
                    );
                    ?>
                </a>

            <?php endforeach; ?>

        </nav>

        <?php
        $listingColumns =
            dsm_theme_home_listing_columns();
        ?>

        <div
            class="<?php
            echo esc_attr(
                'dsm-listing-grid '
                . 'dsm-listing-grid--columns-'
                . $listingColumns
            );
            ?>"
        >

            <?php if (
                $listingItems === []
            ) : ?>

                <div class="dsm-empty-state">
                    No se han encontrado resultados.
                </div>

            <?php else : ?>

                <?php foreach (
                    $listingItems
                    as $listingItem
                ) : ?>

                    <?php
                    if (!is_array($listingItem)) {
                        continue;
                    }

                    get_template_part(
                        'template-parts/cards/listing',
                        null,
                        [
                            'item' =>
                                $listingItem,
                        ]
                    );
                    ?>

                <?php endforeach; ?>

            <?php endif; ?>

        </div>

    </div>

</section>
