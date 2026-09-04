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
        /*
         * Directorio de tiendas.
         *
         * Solo se solicita cuando el visitante está
         * expresamente en la pestaña Tiendas.
         */
        $homeStores = [];

        if ($contentType === 'tiendas') {
            $homeStores =
                apply_filters(
                    'dsm_theme_home_stores',
                    [],
                    [
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
                    ]
                );

            if (!is_array($homeStores)) {
                $homeStores = [];
            }
        }
        ?>

        <?php if (
            $contentType === 'tiendas'
            && $homeStores !== []
        ) : ?>

            <div
                class="dsm-home-stores"
                data-dsm-store-carousel
            >

                <div class="dsm-home-stores__header">

                    <div class="dsm-home-stores__heading">

                        <h3>
                            Tiendas activas
                        </h3>

                        <p>
                            Descubre nuestras tiendas
                            y entra directamente
                            en su catálogo.
                        </p>

                    </div>

                    <div
                        class="dsm-home-stores__controls"
                        aria-label="Navegar por las tiendas"
                    >

                        <button
                            type="button"
                            class="
                                dsm-home-stores__arrow
                                dsm-home-stores__arrow--previous
                            "
                            data-dsm-store-previous
                            aria-label="Tiendas anteriores"
                        >
                            <span aria-hidden="true">
                                ←
                            </span>
                        </button>

                        <button
                            type="button"
                            class="
                                dsm-home-stores__arrow
                                dsm-home-stores__arrow--next
                            "
                            data-dsm-store-next
                            aria-label="Tiendas siguientes"
                        >
                            <span aria-hidden="true">
                                →
                            </span>
                        </button>

                    </div>

                </div>

                <div class="dsm-home-stores__viewport">

                    <div
                        class="dsm-home-stores__track"
                        data-dsm-store-track
                    >

                    <?php foreach (
                        $homeStores
                        as $homeStore
                    ) : ?>

                        <?php
                        if (!is_array($homeStore)) {
                            continue;
                        }

                        $storeName =
                            trim(
                                (string) (
                                    $homeStore['name']
                                    ?? ''
                                )
                            );

                        $storeUrl =
                            trim(
                                (string) (
                                    $homeStore['url']
                                    ?? ''
                                )
                            );

                        $storeLogo =
                            trim(
                                (string) (
                                    $homeStore[
                                        'logo_url'
                                    ]
                                    ?? ''
                                )
                            );

                        $storeIsland =
                            trim(
                                (string) (
                                    $homeStore['island']
                                    ?? ''
                                )
                            );

                        $storeLocation =
                            trim(
                                (string) (
                                    $homeStore[
                                        'location'
                                    ]
                                    ?? ''
                                )
                            );

                        if (
                            $storeName === ''
                            || $storeUrl === ''
                        ) {
                            continue;
                        }
                        ?>

                        <article
                            class="dsm-home-store-card"
                        >

                            <a
                                class="
                                    dsm-home-store-card__link
                                "
                                href="<?php
                                echo esc_url(
                                    $storeUrl
                                );
                                ?>"
                            >

                                <div
                                    class="
                                        dsm-home-store-card__logo
                                    "
                                >

                                    <?php if (
                                        $storeLogo !== ''
                                    ) : ?>

                                        <img
                                            src="<?php
                                            echo esc_url(
                                                $storeLogo
                                            );
                                            ?>"
                                            alt="<?php
                                            echo esc_attr(
                                                $storeName
                                            );
                                            ?>"
                                            loading="lazy"
                                        >

                                    <?php else : ?>

                                        <span>
                                            <?php
                                            echo esc_html(
                                                mb_strtoupper(
                                                    mb_substr(
                                                        $storeName,
                                                        0,
                                                        1
                                                    )
                                                )
                                            );
                                            ?>
                                        </span>

                                    <?php endif; ?>

                                </div>

                                <div
                                    class="
                                        dsm-home-store-card__body
                                    "
                                >

                                    <h4>
                                        <?php
                                        echo esc_html(
                                            $storeName
                                        );
                                        ?>
                                    </h4>

                                    <?php if (
                                        $storeLocation !== ''
                                    ) : ?>

                                        <p>
                                            <?php
                                            echo esc_html(
                                                $storeLocation
                                            );
                                            ?>
                                        </p>

                                    <?php elseif (
                                        $storeIsland !== ''
                                    ) : ?>

                                        <p>
                                            <?php
                                            echo esc_html(
                                                $storeIsland
                                            );
                                            ?>
                                        </p>

                                    <?php endif; ?>

                                    <span
                                        class="
                                            dsm-home-store-card__action
                                        "
                                    >
                                        Ver catálogo
                                    </span>

                                </div>

                            </a>

                        </article>

                    <?php endforeach; ?>

                    </div>

                </div>

            </div>

        <?php endif; ?>

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
