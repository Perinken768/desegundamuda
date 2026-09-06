<?php

declare(strict_types=1);

use DSM\Directos\Frontend\DirectFormController;

if (!defined('ABSPATH')) {
    exit;
}

$baseUrl =
    home_url(
        '/mis-directos/'
    );

$dashboardUrl =
    remove_query_arg(
        [
            'direct_section',
            'direct_id',
            'notice',
            'error',
        ],
        $baseUrl
    );

$configureUrl =
    add_query_arg(
        'direct_section',
        'configure',
        $baseUrl
    );

$itemsUrl =
    add_query_arg(
        'direct_section',
        'items',
        $baseUrl
    );

$status =
    is_array($direct)
        ? sanitize_key(
            (string) (
                $direct['status']
                ?? 'setup'
            )
        )
        : '';

$platformLabels = [
    'instagram' =>
        'Instagram',

    'tiktok' =>
        'TikTok',

    'youtube' =>
        'YouTube',

    'twitch' =>
        'Twitch',

    'facebook' =>
        'Facebook',

    'other' =>
        'Otra',
];

$platform =
    is_array($direct)
        ? sanitize_key(
            (string) (
                $direct['platform']
                ?? ''
            )
        )
        : '';
?>

<section class="dsm-customer-directs">

    <header class="dsm-account-section-header">

        <div class="dsm-account-section-header__content">

            <h1>Mi directo</h1>

            <p>
                Configura, prepara y gestiona tu espacio
                de venta en directo.
            </p>

        </div>

    </header>


    <?php if (!$hasAccess) : ?>

        <section class="dsm-account-empty-state">

            <h2>
                DSM Directos no está incluido
                en tu suscripción
            </h2>

            <p>
                Consulta los planes disponibles para acceder
                a esta funcionalidad.
            </p>

            <a
                class="dsm-button dsm-button--primary"
                href="<?php
                echo esc_url(
                    home_url('/suscripciones/')
                );
                ?>"
            >
                Ver suscripciones
            </a>

        </section>

        <?php return; ?>

    <?php endif; ?>


    <?php if ($error !== '') : ?>

        <div class="dsm-account-notice dsm-account-notice--error">
            <?php echo esc_html($error); ?>
        </div>

    <?php endif; ?>


    <?php if ($directSection === 'configure') : ?>

        <section class="dsm-card">

            <header class="dsm-account-section-header">

                <div class="dsm-account-section-header__content">

                    <h2>Configurar Mi directo</h2>

                    <p>
                        Indica dónde vas a emitir y cómo
                        quieres presentar la próxima emisión.
                    </p>

                </div>

            </header>

            <form
                class="dsm-account-form"
                method="post"
                action="<?php
                echo esc_url(
                    admin_url('admin-post.php')
                );
                ?>"
            >

                <input
                    type="hidden"
                    name="action"
                    value="<?php
                    echo esc_attr(
                        DirectFormController::SAVE_ACTION
                    );
                    ?>"
                >

                <?php
                wp_nonce_field(
                    DirectFormController::NONCE_ACTION,
                    DirectFormController::NONCE_NAME
                );
                ?>

                <div class="dsm-account-form__field">

                    <label for="dsm-direct-title">
                        Título de la emisión
                    </label>

                    <input
                        id="dsm-direct-title"
                        type="text"
                        name="title"
                        maxlength="190"
                        required
                        value="<?php
                        echo esc_attr(
                            is_array($direct)
                                ? (string) (
                                    $direct['title']
                                    ?? ''
                                )
                                : ''
                        );
                        ?>"
                        placeholder="Ej. Novedades del sábado"
                    >

                </div>


                <div class="dsm-account-form__field">

                    <label for="dsm-direct-platform">
                        Plataforma
                    </label>

                    <select
                        id="dsm-direct-platform"
                        name="platform"
                        required
                    >

                        <option value="">
                            Selecciona una plataforma
                        </option>

                        <?php foreach (
                            $platformLabels
                            as $platformKey => $platformLabel
                        ) : ?>

                            <option
                                value="<?php
                                echo esc_attr(
                                    $platformKey
                                );
                                ?>"
                                <?php
                                selected(
                                    $platform,
                                    $platformKey
                                );
                                ?>
                            >
                                <?php
                                echo esc_html(
                                    $platformLabel
                                );
                                ?>
                            </option>

                        <?php endforeach; ?>

                    </select>

                </div>


                <div class="dsm-account-form__field">

                    <label for="dsm-direct-url">
                        URL del directo
                    </label>

                    <input
                        id="dsm-direct-url"
                        type="url"
                        name="platform_url"
                        required
                        value="<?php
                        echo esc_attr(
                            is_array($direct)
                                ? (string) (
                                    $direct['platform_url']
                                    ?? ''
                                )
                                : ''
                        );
                        ?>"
                        placeholder="https://..."
                    >

                    <small>
                        El comprador podrá abrir la emisión
                        directamente desde DSM.
                    </small>

                </div>


                <div class="dsm-account-form__field">

                    <label for="dsm-direct-description">
                        Descripción
                    </label>

                    <textarea
                        id="dsm-direct-description"
                        name="description"
                        rows="4"
                        placeholder="Opcional"
                    ><?php
                    echo esc_textarea(
                        is_array($direct)
                            ? (string) (
                                $direct['description']
                                ?? ''
                            )
                            : ''
                    );
                    ?></textarea>

                </div>


                <div class="dsm-account-form__actions">

                    <button
                        type="submit"
                        class="dsm-button dsm-button--primary"
                    >
                        Guardar configuración
                    </button>

                    <?php if (is_array($direct)) : ?>

                        <a
                            class="dsm-button dsm-button--secondary"
                            href="<?php
                            echo esc_url(
                                $dashboardUrl
                            );
                            ?>"
                        >
                            Cancelar
                        </a>

                    <?php endif; ?>

                </div>

            </form>

        </section>

        <?php return; ?>

    <?php endif; ?>


    <?php if (
        $directSection === 'items'
        && is_array($direct)
    ) : ?>

        <?php
        $selectedAdvertisementIds = [];
        $selectedInventory = [];

        foreach ($selectedItems as $selectedItem) {
            $sourceType =
                sanitize_key(
                    (string) (
                        $selectedItem['source_type']
                        ?? ''
                    )
                );

            if ($sourceType === 'advertisement') {
                $advertisementId =
                    (int) (
                        $selectedItem['advertisement_id']
                        ?? 0
                    );

                if ($advertisementId > 0) {
                    $selectedAdvertisementIds[] =
                        $advertisementId;
                }
            }

            if ($sourceType === 'inventory') {
                $variantId =
                    (int) (
                        $selectedItem['variant_id']
                        ?? 0
                    );

                if ($variantId > 0) {
                    $selectedInventory[$variantId] =
                        max(
                            1,
                            (int) (
                                $selectedItem[
                                    'allocated_quantity'
                                ]
                                ?? 1
                            )
                        );
                }
            }
        }
        ?>

        <section class="dsm-card">

            <h2>Preparar prendas</h2>

            <p>
                Selecciona lo que vas a ofrecer durante
                esta emisión. El número #01, #02, #03…
                se asignará automáticamente.
            </p>

            <?php if (
                $notice === 'items-saved'
            ) : ?>

                <div
                    class="
                        dsm-account-notice
                        dsm-account-notice--success
                    "
                >
                    Prendas guardadas correctamente.
                </div>

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
                        \DSM\Directos\Frontend\DirectItemsController::ACTION
                    );
                    ?>"
                >

                <?php
                wp_nonce_field(
                    \DSM\Directos\Frontend\DirectItemsController::NONCE_ACTION,
                    \DSM\Directos\Frontend\DirectItemsController::NONCE_NAME
                );
                ?>


                <h3>Mis anuncios</h3>

                <?php if (
                    $availableAdvertisements === []
                ) : ?>

                    <p>
                        No tienes anuncios activos disponibles.
                    </p>

                <?php else : ?>

                    <div class="dsm-direct-items-grid">

                        <?php foreach (
                            $availableAdvertisements
                            as $advertisement
                        ) : ?>

                            <label class="dsm-direct-select-card">

                                <input
                                    type="checkbox"
                                    name="advertisement_ids[]"
                                    value="<?php
                                    echo esc_attr(
                                        (string)
                                        $advertisement
                                            ->getId()
                                    );
                                    ?>"
                                    <?php
                                    checked(
                                        in_array(
                                            $advertisement
                                                ->getId(),
                                            $selectedAdvertisementIds,
                                            true
                                        )
                                    );
                                    ?>
                                >

                                <strong>
                                    <?php
                                    echo esc_html(
                                        $advertisement
                                            ->getTitle()
                                    );
                                    ?>
                                </strong>

                                <span>
                                    <?php
                                    echo esc_html(
                                        number_format_i18n(
                                            $advertisement
                                                ->getPrice(),
                                            2
                                        )
                                    );
                                    ?>
                                    €
                                </span>

                                <small>
                                    Anuncio · 1 unidad
                                </small>

                            </label>

                        <?php endforeach; ?>

                    </div>

                <?php endif; ?>


                <?php if ($canUseInventory) : ?>

                    <h3>Inventario de mi tienda</h3>

                    <?php if (
                        $inventoryRows === []
                    ) : ?>

                        <p>
                            No tienes variantes con stock
                            disponible.
                        </p>

                    <?php else : ?>

                        <div class="dsm-direct-items-grid">

                            <?php foreach (
                                $inventoryRows
                                as $inventoryRow
                            ) : ?>

                                <?php
                                $variantId =
                                    (int) $inventoryRow[
                                        'variant_id'
                                    ];

                                $availableStock =
                                    (int) $inventoryRow[
                                        'available_stock'
                                    ];

                                $selectedQuantity =
                                    (int) (
                                        $selectedInventory[
                                            $variantId
                                        ]
                                        ?? 0
                                    );
                                ?>

                                <div class="dsm-direct-select-card">

                                    <strong>
                                        <?php
                                        echo esc_html(
                                            (string) (
                                                $inventoryRow[
                                                    'product_name'
                                                ]
                                                ?? ''
                                            )
                                        );
                                        ?>
                                    </strong>

                                    <span>
                                        <?php
                                        $details = [];

                                        if (
                                            trim(
                                                (string) (
                                                    $inventoryRow[
                                                        'size_value'
                                                    ]
                                                    ?? ''
                                                )
                                            ) !== ''
                                        ) {
                                            $details[] =
                                                'Talla '
                                                . $inventoryRow[
                                                    'size_value'
                                                ];
                                        }

                                        if (
                                            trim(
                                                (string) (
                                                    $inventoryRow[
                                                        'color_value'
                                                    ]
                                                    ?? ''
                                                )
                                            ) !== ''
                                        ) {
                                            $details[] =
                                                $inventoryRow[
                                                    'color_value'
                                                ];
                                        }

                                        echo esc_html(
                                            $details !== []
                                                ? implode(
                                                    ' · ',
                                                    $details
                                                )
                                                : 'Variante'
                                        );
                                        ?>
                                    </span>

                                    <small>
                                        Disponible:
                                        <?php
                                        echo esc_html(
                                            (string)
                                            $availableStock
                                        );
                                        ?>
                                    </small>

                                    <label>
                                        Unidades para el directo

                                        <input
                                            type="number"
                                            name="<?php
                                            echo esc_attr(
                                                'inventory_quantities['
                                                . $variantId
                                                . ']'
                                            );
                                            ?>"
                                            min="0"
                                            max="<?php
                                            echo esc_attr(
                                                (string)
                                                $availableStock
                                            );
                                            ?>"
                                            value="<?php
                                            echo esc_attr(
                                                (string)
                                                $selectedQuantity
                                            );
                                            ?>"
                                        >
                                    </label>

                                </div>

                            <?php endforeach; ?>

                        </div>

                    <?php endif; ?>

                <?php endif; ?>


                <div
                    class="dsm-account-form__actions"
                    style="margin-top: 24px;"
                >

                    <button
                        type="submit"
                        class="
                            dsm-button
                            dsm-button--primary
                        "
                    >
                        Guardar selección
                    </button>

                    <a
                        class="
                            dsm-button
                            dsm-button--secondary
                        "
                        href="<?php
                        echo esc_url(
                            $dashboardUrl
                        );
                        ?>"
                    >
                        Volver a Mi directo
                    </a>

                </div>

            </form>

        </section>


        <?php if ($selectedItems !== []) : ?>

            <section class="dsm-card">

                <h2>Prendas preparadas</h2>

                <?php foreach (
                    $selectedItems
                    as $selectedItem
                ) : ?>

                    <p>
                        <strong>
                            #<?php
                            echo esc_html(
                                str_pad(
                                    (string) (
                                        $selectedItem[
                                            'live_number'
                                        ]
                                        ?? 0
                                    ),
                                    2,
                                    '0',
                                    STR_PAD_LEFT
                                )
                            );
                            ?>
                        </strong>

                        <?php
                        echo esc_html(
                            (
                                $selectedItem[
                                    'source_type'
                                ]
                                ?? ''
                            ) === 'advertisement'
                                ? 'Anuncio'
                                : 'Inventario'
                        );
                        ?>

                        ·

                        <?php
                        echo esc_html(
                            (string) (
                                $selectedItem[
                                    'allocated_quantity'
                                ]
                                ?? 1
                            )
                        );
                        ?>
                        ud.
                    </p>

                <?php endforeach; ?>

            </section>

        <?php endif; ?>

        <?php return; ?>

    <?php endif; ?>


    <?php if (!is_array($direct)) : ?>

        <section class="dsm-account-empty-state">

            <h2>Configura tu espacio de directo</h2>

            <p>
                Indica la plataforma y la URL donde emitirás.
                Después podrás preparar las prendas.
            </p>

            <a
                class="dsm-button dsm-button--primary"
                href="<?php
                echo esc_url(
                    $configureUrl
                );
                ?>"
            >
                Configurar Mi directo
            </a>

        </section>

        <?php return; ?>

    <?php endif; ?>


    <section class="dsm-card">

        <header class="dsm-account-section-header">

            <div class="dsm-account-section-header__content">

                <h2>
                    <?php
                    echo esc_html(
                        (string) $direct['title']
                    );
                    ?>
                </h2>

                <div
                    class="<?php
                    echo esc_attr(
                        'dsm-direct-status '
                        . (
                            $status === 'live'
                                ? 'dsm-direct-status--live'
                                : ''
                        )
                    );
                    ?>"
                >
                    <?php if ($status === 'live') : ?>
                        🔴 En directo
                    <?php else : ?>
                        Directo cerrado
                    <?php endif; ?>
                </div>

            </div>

        </header>


        <p class="dsm-direct-platform">
            <strong>Plataforma:</strong>

            <?php
            echo esc_html(
                $platformLabels[$platform]
                ?? ucfirst($platform)
            );
            ?>
        </p>


        <div class="dsm-direct-dashboard-actions">

            <div class="dsm-direct-dashboard-actions__row">

                <a
                    class="dsm-button dsm-button--secondary"
                    href="<?php
                    echo esc_url(
                        home_url(
                            '/directo/'
                        )
                    );
                    ?>"
                >
                    Ir al directo
                </a>

                <a
                    class="dsm-button dsm-button--secondary"
                    href="<?php
                    echo esc_url(
                        $configureUrl
                    );
                    ?>"
                >
                    Configurar
                </a>

            </div>


            <div class="dsm-direct-dashboard-actions__row">

                <a
                    class="dsm-button dsm-button--secondary"
                    href="<?php
                    echo esc_url(
                        home_url(
                            '/mis-anuncios/'
                        )
                    );
                    ?>"
                >
                    Preparar anuncios
                </a>

                <?php if ($canUseInventory) : ?>

                    <a
                        class="dsm-button dsm-button--secondary"
                        href="<?php
                        echo esc_url(
                            add_query_arg(
                                'store_section',
                                'inventory',
                                home_url(
                                    '/mi-tienda/'
                                )
                            )
                        );
                        ?>"
                    >
                        Preparar productos
                    </a>

                <?php endif; ?>

            </div>

        </div>

    </section>


    <section class="dsm-card">

        <strong>
            Fuentes disponibles
        </strong>

        <p>
            ✓ Tus anuncios

            <?php if ($canUseInventory) : ?>
                &nbsp;&nbsp; ✓ Inventario de tu tienda
            <?php endif; ?>
        </p>

    </section>

</section>
