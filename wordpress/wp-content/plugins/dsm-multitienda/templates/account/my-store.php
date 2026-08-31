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

                <?php

                $erpSections = [
                    '' => 'Resumen',
                    'products' => 'Productos',
                    'inventory' => 'Inventario',
                    'reservations' => 'Reservas',
                    'movements' => 'Movimientos',
                ];

                foreach (
                    $erpSections
                    as $sectionKey => $sectionLabel
                ) :

                    $sectionUrl =
                        $sectionKey === ''
                            ? home_url(
                                '/mi-tienda/'
                            )
                            : add_query_arg(
                                'store_section',
                                $sectionKey,
                                home_url(
                                    '/mi-tienda/'
                                )
                            );

                    $isActive =
                        $sectionKey === ''
                            ? $storeSection === ''
                            : $storeSection ===
                                $sectionKey;

                    ?>

                    <a
                        class="dsm-multistore-erp-nav__item<?php
                        echo $isActive
                            ? ' is-active'
                            : '';
                        ?>"
                        href="<?php
                        echo esc_url(
                            $sectionUrl
                        );
                        ?>"
                        <?php if ($isActive) : ?>
                            aria-current="page"
                        <?php endif; ?>
                    >
                        <?php
                        echo esc_html(
                            $sectionLabel
                        );
                        ?>
                    </a>

                <?php endforeach; ?>

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

            <article class="dsm-card dsm-store-profile">

                <div class="dsm-store-profile__header">

                    <h2>
                        Datos de la tienda
                    </h2>

                    <p>
                        Configura la información pública
                        y la identidad de tu tienda.
                    </p>

                </div>

                <form
                    class="dsm-store-profile__form"
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

                    <div class="dsm-store-profile__grid">

                        <div class="dsm-store-profile__field">

                            <label for="dsm-store-name">
                                Nombre
                            </label>

                            <input
                                id="dsm-store-name"
                                type="text"
                                name="name"
                                value="<?php
                                echo esc_attr(
                                    $store->getName()
                                );
                                ?>"
                                required
                            >

                        </div>

                        <div class="dsm-store-profile__field">

                            <label for="dsm-store-slug">
                                Slug
                            </label>

                            <input
                                id="dsm-store-slug"
                                type="text"
                                name="slug"
                                value="<?php
                                echo esc_attr(
                                    $store->getSlug()
                                );
                                ?>"
                                required
                            >

                        </div>

                        <div
                            class="
                                dsm-store-profile__field
                                dsm-store-profile__field--full
                            "
                        >

                            <label for="dsm-store-logo">
                                Logo
                            </label>

                            <div class="dsm-store-profile__upload">

                                <input
                                    id="dsm-store-logo"
                                    type="file"
                                    name="store_logo"
                                    accept="
                                        image/jpeg,
                                        image/png,
                                        image/webp
                                    "
                                >

                                <small>
                                    JPG, PNG o WEBP.
                                    Máximo 5 MB.
                                </small>

                            </div>

                            <?php if (
                                $store->getLogoAttachmentId()
                                !== null
                            ) : ?>

                                <label
                                    class="
                                        dsm-store-profile__remove-logo
                                    "
                                >

                                    <input
                                        type="checkbox"
                                        name="remove_logo"
                                        value="1"
                                    >

                                    <span>
                                        Eliminar logo actual
                                    </span>

                                </label>

                            <?php endif; ?>

                        </div>

                        <div
                            class="
                                dsm-store-profile__field
                                dsm-store-profile__field--full
                            "
                        >

                            <label for="dsm-store-description">
                                Descripción
                            </label>

                            <textarea
                                id="dsm-store-description"
                                name="description"
                                rows="6"
                            ><?php
                            echo esc_textarea(
                                $store->getDescription()
                                ?? ''
                            );
                            ?></textarea>

                        </div>

                        <div class="dsm-store-profile__field">

                            <label for="dsm-store-island">
                                Isla
                            </label>

                            <input
                                id="dsm-store-island"
                                type="text"
                                name="island"
                                value="<?php
                                echo esc_attr(
                                    $store->getIsland()
                                    ?? ''
                                );
                                ?>"
                            >

                        </div>

                        <div class="dsm-store-profile__field">

                            <label for="dsm-store-location">
                                Ubicación
                            </label>

                            <input
                                id="dsm-store-location"
                                type="text"
                                name="location_text"
                                value="<?php
                                echo esc_attr(
                                    $store->getLocationText()
                                    ?? ''
                                );
                                ?>"
                            >

                        </div>

                    </div>

                    <div class="dsm-store-profile__actions">

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

            </article>

            <article class="dsm-card dsm-store-dashboard">

                <h2>
                    Resumen ERP
                </h2>

                <?php

                $storeBaseUrl =
                    home_url(
                        '/mi-tienda/'
                    );

                $productsUrl =
                    add_query_arg(
                        'store_section',
                        'products',
                        $storeBaseUrl
                    );

                $inventoryUrl =
                    add_query_arg(
                        'store_section',
                        'inventory',
                        $storeBaseUrl
                    );

                $reservationsUrl =
                    add_query_arg(
                        'store_section',
                        'reservations',
                        $storeBaseUrl
                    );

                ?>

                <div class="dsm-store-dashboard__grid">

                    <a
                        class="dsm-store-dashboard__card"
                        href="<?php
                        echo esc_url(
                            $productsUrl
                        );
                        ?>"
                    >
                        <span class="dsm-store-dashboard__label">
                            Productos
                        </span>

                        <strong class="dsm-store-dashboard__value">
                            <?php
                            echo esc_html(
                                (string)
                                $dashboardProductCount
                            );
                            ?>
                        </strong>

                        <span class="dsm-store-dashboard__link">
                            Ver productos
                            <span aria-hidden="true">→</span>
                        </span>
                    </a>

                    <a
                        class="dsm-store-dashboard__card"
                        href="<?php
                        echo esc_url(
                            $productsUrl
                        );
                        ?>"
                    >
                        <span class="dsm-store-dashboard__label">
                            Variantes
                        </span>

                        <strong class="dsm-store-dashboard__value">
                            <?php
                            echo esc_html(
                                (string)
                                $dashboardVariantCount
                            );
                            ?>
                        </strong>

                        <span class="dsm-store-dashboard__link">
                            Ver variantes
                            <span aria-hidden="true">→</span>
                        </span>
                    </a>

                    <a
                        class="dsm-store-dashboard__card"
                        href="<?php
                        echo esc_url(
                            $inventoryUrl
                        );
                        ?>"
                    >
                        <span class="dsm-store-dashboard__label">
                            Stock físico
                        </span>

                        <strong class="dsm-store-dashboard__value">
                            <?php
                            echo esc_html(
                                (string)
                                $dashboardPhysicalStock
                            );
                            ?>
                        </strong>

                        <span class="dsm-store-dashboard__link">
                            Ver inventario
                            <span aria-hidden="true">→</span>
                        </span>
                    </a>

                    <a
                        class="dsm-store-dashboard__card"
                        href="<?php
                        echo esc_url(
                            $inventoryUrl
                        );
                        ?>"
                    >
                        <span class="dsm-store-dashboard__label">
                            Stock reservado
                        </span>

                        <strong class="dsm-store-dashboard__value">
                            <?php
                            echo esc_html(
                                (string)
                                $dashboardReservedStock
                            );
                            ?>
                        </strong>

                        <span class="dsm-store-dashboard__link">
                            Ver inventario
                            <span aria-hidden="true">→</span>
                        </span>
                    </a>

                    <a
                        class="dsm-store-dashboard__card"
                        href="<?php
                        echo esc_url(
                            $inventoryUrl
                        );
                        ?>"
                    >
                        <span class="dsm-store-dashboard__label">
                            Stock disponible
                        </span>

                        <strong class="dsm-store-dashboard__value">
                            <?php
                            echo esc_html(
                                (string)
                                $dashboardAvailableStock
                            );
                            ?>
                        </strong>

                        <span class="dsm-store-dashboard__link">
                            Ver inventario
                            <span aria-hidden="true">→</span>
                        </span>
                    </a>

                    <a
                        class="dsm-store-dashboard__card"
                        href="<?php
                        echo esc_url(
                            $reservationsUrl
                        );
                        ?>"
                    >
                        <span class="dsm-store-dashboard__label">
                            Reservas activas
                        </span>

                        <strong class="dsm-store-dashboard__value">
                            <?php
                            echo esc_html(
                                (string)
                                $dashboardActiveReservations
                            );
                            ?>
                        </strong>

                        <span class="dsm-store-dashboard__link">
                            Ver reservas
                            <span aria-hidden="true">→</span>
                        </span>
                    </a>

                    <a
                        class="dsm-store-dashboard__card"
                        href="<?php
                        echo esc_url(
                            $reservationsUrl
                        );
                        ?>"
                    >
                        <span class="dsm-store-dashboard__label">
                            Ventas completadas
                        </span>

                        <strong class="dsm-store-dashboard__value">
                            <?php
                            echo esc_html(
                                (string)
                                $dashboardCompletedReservations
                            );
                            ?>
                        </strong>

                        <span class="dsm-store-dashboard__link">
                            Ver ventas
                            <span aria-hidden="true">→</span>
                        </span>
                    </a>

                </div>

            </article>

        <?php endif; ?>

    <?php endif; ?>

</section>