<?php

declare(strict_types=1);

use DSM\Multitienda\Frontend\StoreProfileController;
use DSM\Multitienda\Frontend\StoreStatusController;
use DSM\Multitienda\Store\Store;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * @var array<string, mixed>|null $customerContext
 * @var int $customerId
 * @var bool $hasAccess
 * @var Store|null $store
 * @var string $status
 * @var string $error
 * @var string $storeSection
 * @var array<int, mixed> $products
 * @var int $productCount
 * @var string $productStatusNotice
 * @var string $productStatusError
 * @var array<int, mixed> $reservations
 * @var array<int, mixed> $reservationProducts
 * @var array<int, mixed> $reservationVariants
 * @var array<int, array<string, mixed>> $reservationBuyers
 * @var string $reservationNotice
 * @var string $reservationError
 */

?>

<section class="dsm-multistore-account">

    <header class="dsm-account-header">

        <h1>
            Mi tienda
        </h1>

        <p>
            Gestiona tu tienda profesional
            dentro de DeSegundaMuda.
        </p>

    </header>

    <?php if ($status === 'created') : ?>

        <div
            class="
                dsm-account-notice
                dsm-account-notice--success
            "
        >
            La tienda se creó correctamente.
        </div>

    <?php elseif ($status === 'updated') : ?>

        <div
            class="
                dsm-account-notice
                dsm-account-notice--success
            "
        >
            Los datos de la tienda se actualizaron
            correctamente.
        </div>

    <?php elseif ($status === 'published') : ?>

        <div
            class="
                dsm-account-notice
                dsm-account-notice--success
            "
        >
            La tienda está publicada.
        </div>

    <?php elseif ($status === 'hidden') : ?>

        <div
            class="
                dsm-account-notice
                dsm-account-notice--success
            "
        >
            La tienda se ha ocultado correctamente.
        </div>

    <?php elseif (
        $status === 'error'
        && $error !== ''
    ) : ?>

        <div
            class="
                dsm-account-notice
                dsm-account-notice--error
            "
        >
            <?php
            echo esc_html(
                $error
            );
            ?>
        </div>

    <?php endif; ?>

    <?php if ($customerId <= 0) : ?>

        <article class="dsm-card">

            <p>
                Debes iniciar sesión para acceder
                a tu tienda.
            </p>

            <a
                class="
                    dsm-button
                    dsm-button--primary
                "
                href="<?php
                echo esc_url(
                    add_query_arg(
                        [
                            'redirect_to' =>
                                add_query_arg(
                                    array_filter(
                                        [
                                            'store_section' =>
                                                isset(
                                                    $_GET[
                                                        'store_section'
                                                    ]
                                                )
                                                    ? sanitize_key(
                                                        wp_unslash(
                                                            (string) $_GET[
                                                                'store_section'
                                                            ]
                                                        )
                                                    )
                                                    : '',

                                            'reservation_id' =>
                                                isset(
                                                    $_GET[
                                                        'reservation_id'
                                                    ]
                                                )
                                                    ? absint(
                                                        wp_unslash(
                                                            (string) $_GET[
                                                                'reservation_id'
                                                            ]
                                                        )
                                                    )
                                                    : 0,
                                        ]
                                    ),
                                    home_url(
                                        '/mi-tienda/'
                                    )
                                ),
                        ],
                        home_url(
                            '/iniciar-sesion/'
                        )
                    )
                );
                ?>"
            >
                Iniciar sesión
            </a>

        </article>

    <?php elseif (!$hasAccess) : ?>

        <article class="dsm-card">

            <p>
                Tu cuenta no tiene una suscripción
                Multitienda activa.
            </p>

            <a
                class="
                    dsm-button
                    dsm-button--primary
                "
                href="<?php
                echo esc_url(
                    home_url(
                        '/suscripciones/'
                    )
                );
                ?>"
            >
                Ver suscripciones
            </a>

        </article>

    <?php elseif (
        !$store instanceof Store
    ) : ?>

        <article class="dsm-card">

            <h2>
                Configura tu tienda
            </h2>

            <p>
                Antes de cargar productos,
                crea el perfil básico de tu tienda.
            </p>

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
                        StoreProfileController::
                            CREATE_ACTION
                    );
                    ?>"
                >

                <?php
                wp_nonce_field(
                    StoreProfileController::
                        getCreateNonceAction(
                            $customerId
                        ),
                    StoreProfileController::
                        NONCE_FIELD
                );
                ?>

                <p>
                    <label>
                        <strong>
                            Nombre de la tienda
                        </strong>
                    </label>

                    <br>

                    <input
                        type="text"
                        name="name"
                        class="regular-text"
                        required
                    >
                </p>

                <p>
                    <label>
                        <strong>
                            URL de la tienda
                        </strong>
                    </label>

                    <br>

                    <input
                        type="text"
                        name="slug"
                        class="regular-text"
                        placeholder="mi-tienda"
                    >

                    <br>

                    <small>
                        Si lo dejas vacío se generará
                        automáticamente.
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
                        rows="6"
                        class="large-text"
                    ></textarea>
                </p>

                <p>
                    <label>
                        <strong>
                            Isla
                        </strong>
                    </label>

                    <br>

                    <input
                        type="text"
                        name="island"
                        class="regular-text"
                    >
                </p>

                <p>
                    <label>
                        <strong>
                            Ubicación
                        </strong>
                    </label>

                    <br>

                    <input
                        type="text"
                        name="location_text"
                        class="regular-text"
                    >
                </p>

                <button
                    type="submit"
                    class="
                        dsm-button
                        dsm-button--primary
                    "
                >
                    Crear mi tienda
                </button>

            </form>

        </article>

    <?php else : ?>

        <!-- ==================================================
             CABECERA DE LA TIENDA
        =================================================== -->

        <article class="dsm-card">

            <h2>
                <?php
                echo esc_html(
                    $store->getName()
                );
                ?>
            </h2>

            <?php if (
                $store->getLogoAttachmentId()
                !== null
            ) : ?>

                <div class="dsm-multistore-logo">

                    <?php
                    echo wp_get_attachment_image(
                        $store->getLogoAttachmentId(),
                        'medium',
                        false,
                        [
                            'alt' =>
                                $store->getName(),
                        ]
                    );
                    ?>

                </div>

            <?php endif; ?>

            <p>
                <strong>
                    Estado:
                </strong>

                <?php
                $storeStatusLabels = [
                    'draft' =>
                        'Borrador',

                    'active' =>
                        'Publicada',

                    'hidden' =>
                        'Oculta',

                    'suspended' =>
                        'Suspendida',
                ];

                echo esc_html(
                    $storeStatusLabels[
                        $store->getStatus()
                    ]
                    ?? ucfirst(
                        $store->getStatus()
                    )
                );
                ?>
            </p>

            <p>
                <strong>
                    URL pública:
                </strong>

                <code>
                    <?php
                    echo esc_html(
                        home_url(
                            '/tienda/'
                            . $store->getSlug()
                            . '/'
                        )
                    );
                    ?>
                </code>
            </p>

            <?php if ($store->isActive()) : ?>

                <p>
                    <a
                        class="
                            dsm-button
                            dsm-button--secondary
                        "
                        href="<?php
                        echo esc_url(
                            home_url(
                                '/tienda/'
                                . $store->getSlug()
                                . '/'
                            )
                        );
                        ?>"
                        target="_blank"
                        rel="noopener noreferrer"
                    >
                        Ver tienda pública
                    </a>
                </p>

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
                            StoreStatusController::
                                HIDE_ACTION
                        );
                        ?>"
                    >

                    <?php
                    wp_nonce_field(
                        StoreStatusController::
                            getHideNonceAction(
                                $store->getId()
                            ),
                        StoreStatusController::
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
                        Ocultar tienda
                    </button>

                </form>

            <?php elseif (
                !$store->isSuspended()
            ) : ?>

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
                            StoreStatusController::
                                PUBLISH_ACTION
                        );
                        ?>"
                    >

                    <?php
                    wp_nonce_field(
                        StoreStatusController::
                            getPublishNonceAction(
                                $store->getId()
                            ),
                        StoreStatusController::
                            NONCE_FIELD
                    );
                    ?>

                    <button
                        type="submit"
                        class="
                            dsm-button
                            dsm-button--primary
                        "
                    >
                        Publicar tienda
                    </button>

                </form>

            <?php else : ?>

                <div
                    class="
                        dsm-account-notice
                        dsm-account-notice--error
                    "
                >
                    La tienda está suspendida.
                    Contacta con administración.
                </div>

            <?php endif; ?>

        </article>

        <!-- ==================================================
             NAVEGACIÓN ERP
        =================================================== -->

        <article class="dsm-card">

            <h2>
                Panel ERP
            </h2>

            <nav
                class="dsm-multistore-erp-nav"
                aria-label="Panel Multitienda"
            >

                <a
                    href="<?php
                    echo esc_url(
                        home_url(
                            '/mi-tienda/'
                        )
                    );
                    ?>"
                >
                    Resumen
                </a>

                &nbsp;|&nbsp;

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
                    Productos
                </a>

                &nbsp;|&nbsp;

                <a
                    href="<?php
                    echo esc_url(
                        add_query_arg(
                            [
                                'store_section' =>
                                    'inventory',
                            ],
                            home_url(
                                '/mi-tienda/'
                            )
                        )
                    );
                    ?>"
                >
                    Inventario
                </a>

                &nbsp;|&nbsp;

                <a
                    href="<?php
                    echo esc_url(
                        add_query_arg(
                            [
                                'store_section' =>
                                    'reservations',
                            ],
                            home_url(
                                '/mi-tienda/'
                            )
                        )
                    );
                    ?>"
                >
                    Reservas
                </a>

                &nbsp;|&nbsp;

                <a
                    href="<?php
                    echo esc_url(
                        add_query_arg(
                            [
                                'store_section' =>
                                    'movements',
                            ],
                            home_url(
                                '/mi-tienda/'
                            )
                        )
                    );
                    ?>"
                >
                    Movimientos
                </a>



            </nav>

        </article>

        <?php if (
            $storeSection === 'products'
        ) : ?>

            <?php
            $productsTemplate =
                DSM_MULTITIENDA_PATH
                . 'templates/account/'
                . 'store-products.php';

            if (is_file($productsTemplate)) {
                require $productsTemplate;
            } else {
                ?>

                <div
                    class="
                        dsm-account-notice
                        dsm-account-notice--error
                    "
                >
                    No se encontró la plantilla
                    de productos.
                </div>

                <?php
            }
            ?>

        <?php elseif (
            $storeSection === 'new-product'
        ) : ?>

            <?php
            $productFormTemplate =
                DSM_MULTITIENDA_PATH
                . 'templates/account/'
                . 'store-product-form.php';

            if (is_file($productFormTemplate)) {
                require $productFormTemplate;
            } else {
                ?>

                <div
                    class="
                        dsm-account-notice
                        dsm-account-notice--error
                    "
                >
                    No se encontró la plantilla
                    de creación de productos.
                </div>

                <?php
            }
            ?>

        <?php elseif (
            $storeSection === 'edit-product'
        ) : ?>

            <?php
            $variantsTemplate =
                DSM_MULTITIENDA_PATH
                . 'templates/account/'
                . 'store-product-variants.php';

            if (
                $editProduct !== null
                && is_file(
                    $variantsTemplate
                )
            ) {
                require $variantsTemplate;
            } else {
                ?>

                <div
                    class="
                        dsm-account-notice
                        dsm-account-notice--error
                    "
                >
                    No se pudo cargar la gestión
                    de variantes del producto.
                </div>

                <?php
            }
            ?>

            <p>
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
                    ← Volver a productos
                </a>
            </p>

            <article class="dsm-card">

                <h2>
                    Editar producto
                </h2>

                <p>
                    La edición de productos será
                    integrada con DSM Catálogo
                    en el siguiente bloque.
                </p>

                <p>
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
                        ← Volver a productos
                    </a>
                </p>

            </article>

        <?php elseif (
            $storeSection === 'inventory'
        ) : ?>

            <?php
            $inventoryTemplate =
                DSM_MULTITIENDA_PATH
                . 'templates/account/'
                . 'store-inventory.php';

            if (is_file($inventoryTemplate)) {
                require $inventoryTemplate;
            }
            ?>

        <?php elseif (
            $storeSection === 'movements'
        ) : ?>

            <?php
            $movementsTemplate =
                DSM_MULTITIENDA_PATH
                . 'templates/account/'
                . 'store-movements.php';

            if (is_file($movementsTemplate)) {
                require $movementsTemplate;
            }
            ?>

        <?php elseif (
            $storeSection === 'reservations'
        ) : ?>

            <?php
            $reservationsTemplate =
                DSM_MULTITIENDA_PATH
                . 'templates/account/'
                . 'store-reservations.php';

            if (
                is_file(
                    $reservationsTemplate
                )
            ) {
                require $reservationsTemplate;
            } else {
                ?>

                <div
                    class="
                        dsm-account-notice
                        dsm-account-notice--error
                    "
                >
                    No se encontró la plantilla
                    de reservas.
                </div>

                <?php
            }
            ?>

        <?php else : ?>

            <!-- ==============================================
                 RESUMEN / PERFIL DE TIENDA
            =============================================== -->

            <article class="dsm-card">

                <h2>
                    Datos de la tienda
                </h2>

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
                            StoreProfileController::
                                UPDATE_ACTION
                        );
                        ?>"
                    >

                    <?php
                    wp_nonce_field(
                        StoreProfileController::
                            getUpdateNonceAction(
                                $store->getId()
                            ),
                        StoreProfileController::
                            NONCE_FIELD
                    );
                    ?>

                    <p>
                        <label>
                            <strong>
                                Nombre
                            </strong>
                        </label>

                        <br>

                        <input
                            type="text"
                            name="name"
                            value="<?php
                            echo esc_attr(
                                $store->getName()
                            );
                            ?>"
                            class="regular-text"
                            required
                        >
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
                            value="<?php
                            echo esc_attr(
                                $store->getSlug()
                            );
                            ?>"
                            class="regular-text"
                            required
                        >
                    </p>

                    <p>
                        <label>
                            <strong>
                                Logo
                            </strong>
                        </label>

                        <br>

                        <input
                            type="file"
                            name="store_logo"
                            accept="
                                image/jpeg,
                                image/png,
                                image/webp
                            "
                        >

                        <br>

                        <small>
                            JPG, PNG o WEBP.
                            Máximo 5 MB.
                        </small>
                    </p>

                    <?php if (
                        $store->getLogoAttachmentId()
                        !== null
                    ) : ?>

                        <p>
                            <label>

                                <input
                                    type="checkbox"
                                    name="remove_logo"
                                    value="1"
                                >

                                Eliminar logo actual

                            </label>
                        </p>

                    <?php endif; ?>

                    <p>
                        <label>
                            <strong>
                                Descripción
                            </strong>
                        </label>

                        <br>

                        <textarea
                            name="description"
                            rows="6"
                            class="large-text"
                        ><?php
                        echo esc_textarea(
                            $store->getDescription()
                            ?? ''
                        );
                        ?></textarea>
                    </p>

                    <p>
                        <label>
                            <strong>
                                Isla
                            </strong>
                        </label>

                        <br>

                        <input
                            type="text"
                            name="island"
                            value="<?php
                            echo esc_attr(
                                $store->getIsland()
                                ?? ''
                            );
                            ?>"
                            class="regular-text"
                        >
                    </p>

                    <p>
                        <label>
                            <strong>
                                Ubicación
                            </strong>
                        </label>

                        <br>

                        <input
                            type="text"
                            name="location_text"
                            value="<?php
                            echo esc_attr(
                                $store->getLocationText()
                                ?? ''
                            );
                            ?>"
                            class="regular-text"
                        >
                    </p>

                    <button
                        type="submit"
                        class="
                            dsm-button
                            dsm-button--primary
                        "
                    >
                        Guardar cambios
                    </button>

                </form>

            </article>

            <article class="dsm-card">

                <h2>
                    Resumen ERP
                </h2>

                <div
                    style="
                        display:grid;
                        grid-template-columns:
                            repeat(
                                auto-fit,
                                minmax(160px, 1fr)
                            );
                        gap:16px;
                        margin:20px 0;
                    "
                >

                    <div class="dsm-card">
                        <small>Productos</small>

                        <div
                            style="
                                font-size:2rem;
                                font-weight:700;
                            "
                        >
                            <?php
                            echo esc_html(
                                (string)
                                $dashboardProductCount
                            );
                            ?>
                        </div>
                    </div>

                    <div class="dsm-card">
                        <small>Variantes</small>

                        <div
                            style="
                                font-size:2rem;
                                font-weight:700;
                            "
                        >
                            <?php
                            echo esc_html(
                                (string)
                                $dashboardVariantCount
                            );
                            ?>
                        </div>
                    </div>

                    <div class="dsm-card">
                        <small>Stock físico</small>

                        <div
                            style="
                                font-size:2rem;
                                font-weight:700;
                            "
                        >
                            <?php
                            echo esc_html(
                                (string)
                                $dashboardPhysicalStock
                            );
                            ?>
                        </div>
                    </div>

                    <div class="dsm-card">
                        <small>Stock reservado</small>

                        <div
                            style="
                                font-size:2rem;
                                font-weight:700;
                            "
                        >
                            <?php
                            echo esc_html(
                                (string)
                                $dashboardReservedStock
                            );
                            ?>
                        </div>
                    </div>

                    <div class="dsm-card">
                        <small>Stock disponible</small>

                        <div
                            style="
                                font-size:2rem;
                                font-weight:700;
                            "
                        >
                            <?php
                            echo esc_html(
                                (string)
                                $dashboardAvailableStock
                            );
                            ?>
                        </div>
                    </div>

                    <div class="dsm-card">
                        <small>Reservas activas</small>

                        <div
                            style="
                                font-size:2rem;
                                font-weight:700;
                            "
                        >
                            <?php
                            echo esc_html(
                                (string)
                                $dashboardActiveReservations
                            );
                            ?>
                        </div>
                    </div>

                    <div class="dsm-card">
                        <small>Ventas completadas</small>

                        <div
                            style="
                                font-size:2rem;
                                font-weight:700;
                            "
                        >
                            <?php
                            echo esc_html(
                                (string)
                                $dashboardCompletedReservations
                            );
                            ?>
                        </div>
                    </div>

                </div>

                <p>
                    <a
                        class="dsm-button"
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
                        Productos
                    </a>

                    <a
                        class="dsm-button"
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
                        Inventario
                    </a>

                    <a
                        class="dsm-button"
                        href="<?php
                        echo esc_url(
                            add_query_arg(
                                'store_section',
                                'reservations',
                                home_url(
                                    '/mi-tienda/'
                                )
                            )
                        );
                        ?>"
                    >
                        Reservas
                    </a>

                    <a
                        class="dsm-button"
                        href="<?php
                        echo esc_url(
                            add_query_arg(
                                'store_section',
                                'movements',
                                home_url(
                                    '/mi-tienda/'
                                )
                            )
                        );
                        ?>"
                    >
                        Movimientos
                    </a>
                </p>

            </article>

        <?php endif; ?>

    <?php endif; ?>

</section>