<?php

declare(strict_types=1);

namespace DSM\Publicidad\Frontend;

use DSM\Publicidad\Advertising\AdvertisingBannerRepository;
use DSM\Suscripciones\Application\CustomerEntitlementService;
use Throwable;

if (!defined('ABSPATH')) {
    exit;
}

final class CustomerAdvertisingShortcode
{
    public const SHORTCODE =
        'dsm_customer_advertising';

    public static function register(): void
    {
        add_shortcode(
            self::SHORTCODE,
            [
                self::class,
                'render',
            ]
        );
    }

    public static function render(): string
    {
        $context =
            apply_filters(
                'dsm_current_customer_context',
                null
            );

        if (!is_array($context)) {
            return self::renderLoginRequired();
        }

        $customerId =
            max(
                0,
                (int) (
                    $context['id']
                    ?? 0
                )
            );

        $status =
            sanitize_key(
                (string) (
                    $context['status']
                    ?? ''
                )
            );

        if (
            $customerId <= 0
            || $status !== 'active'
        ) {
            return self::renderLoginRequired();
        }

        $action =
            isset($_GET['advertising_action'])
                ? sanitize_key(
                    wp_unslash(
                        (string) $_GET[
                            'advertising_action'
                        ]
                    )
                )
                : '';

        if ($action === 'new') {
            $repository =
                new AdvertisingBannerRepository();

            if (
                $repository->findByCustomer(
                    $customerId
                ) !== []
            ) {
                return self::renderDashboard(
                    $customerId
                );
            }

            return self::renderCreateForm(
                $customerId
            );
        }

        if ($action === 'edit') {
            return self::renderEditForm(
                $customerId
            );
        }

        return self::renderDashboard(
            $customerId
        );
    }

    private static function renderDashboard(
        int $customerId
    ): string {
        try {
            $repository =
                new AdvertisingBannerRepository();

            $banners =
                $repository->findByCustomer(
                    $customerId
                );

            $entitlementService =
                new CustomerEntitlementService();

            $hasAdvertisingAccess =
                $entitlementService->hasAdvertising(
                    $customerId
                );

            $notice =
                isset($_GET['advertising_notice'])
                    ? sanitize_key(
                        wp_unslash(
                            (string) $_GET[
                                'advertising_notice'
                            ]
                        )
                    )
                    : '';

            ob_start();
            ?>

            <section class="dsm-customer-advertising">

                <?php if (
                    $notice === 'created'
                ) : ?>

                    <div class="dsm-alert dsm-alert--success">
                        Publicidad creada y activada
                        correctamente.
                    </div>

                <?php endif; ?>

                <header class="dsm-customer-advertising__header">

                    <div>
                        <h1>Mi publicidad</h1>

                        <p>
                            Gestiona tus campañas publicitarias
                            en DeSegundaMuda.
                        </p>
                    </div>

                    <?php if (
                        $banners === []
                        && $hasAdvertisingAccess
                    ) : ?>

                        <div class="dsm-customer-advertising__actions">
                            <a
                                class="dsm-button dsm-button--primary"
                                href="<?php
                                echo esc_url(
                                    add_query_arg(
                                        [
                                            'advertising_action' =>
                                                'new',
                                        ],
                                        home_url(
                                            '/mi-publicidad/'
                                        )
                                    )
                                );
                                ?>"
                            >
                                Añadir publicidad
                            </a>
                        </div>

                    <?php endif; ?>

                </header>

                <?php if ($banners === []) : ?>

                    <div class="dsm-empty-state">
                        <p>
                            Todavía no tienes campañas
                            publicitarias creadas.
                        </p>
                    </div>

                <?php else : ?>

                    <div class="dsm-customer-advertising__list">

                        <?php foreach (
                            $banners
                            as $banner
                        ) : ?>

                            <?php
                            $imageUrl =
                                wp_get_attachment_image_url(
                                    $banner
                                        ->getImageAttachmentId(),
                                    'medium'
                                );
                            ?>

                            <article
                                class="dsm-customer-advertising__item"
                            >

                                <?php if (
                                    is_string(
                                        $imageUrl
                                    )
                                ) : ?>

                                    <img
                                        src="<?php
                                        echo esc_url(
                                            $imageUrl
                                        );
                                        ?>"
                                        alt=""
                                        loading="lazy"
                                    >

                                <?php endif; ?>

                                <div>
                                    <h2>
                                        <?php
                                        echo esc_html(
                                            $banner
                                                ->getTitle()
                                        );
                                        ?>
                                    </h2>

                                    <p>
                                        Estado:
                                        <strong>
                                            <?php
                                            echo esc_html(
                                                self::statusLabel(
                                                    $banner
                                                        ->getStatus()
                                                )
                                            );
                                            ?>
                                        </strong>
                                    </p>

                                    <p>
                                        Ámbito:
                                        <?php
                                        echo esc_html(
                                            self::areaLabel(
                                                $banner
                                                    ->getAreaId()
                                            )
                                        );
                                        ?>
                                    </p>

                                    <p>
                                        <a
                                            href="<?php
                                            echo esc_url(
                                                $banner
                                                    ->getTargetUrl()
                                            );
                                            ?>"
                                            target="_blank"
                                            rel="noopener"
                                        >
                                            Ver destino
                                        </a>
                                    </p>

                                    <div class="dsm-card__actions">
                                        <a
                                            class="dsm-button dsm-button--primary"
                                            href="<?php
                                            echo esc_url(
                                                add_query_arg(
                                                    [
                                                        'advertising_action' =>
                                                            'edit',

                                                        'banner_id' =>
                                                            $banner
                                                                ->getId(),
                                                    ],
                                                    home_url(
                                                        '/mi-publicidad/'
                                                    )
                                                )
                                            );
                                            ?>"
                                        >
                                            Editar mi publicidad
                                        </a>
                                    </div>
                                </div>

                            </article>

                        <?php endforeach; ?>

                    </div>

                <?php endif; ?>

            </section>

            <?php

            $output =
                ob_get_clean();

            return is_string($output)
                ? $output
                : '';
        } catch (Throwable $exception) {
            error_log(
                '[DSM Publicidad] No se pudo cargar Mi publicidad: '
                . $exception->getMessage()
            );

            return '<div class="dsm-empty-state">'
                . 'No se pudo cargar tu publicidad.'
                . '</div>';
        }
    }

    private static function renderCreateForm(
        int $customerId
    ): string
    {
        /*
         * La creación de publicidad solo está disponible
         * para clientes cuya suscripción incluya la
         * prestación advertising.
         */
        $entitlementService =
            new CustomerEntitlementService();

        if (
            !$entitlementService->hasAdvertising(
                $customerId
            )
        ) {
            return sprintf(
                '<section class="dsm-customer-advertising">'
                . '<article class="dsm-card">'
                . '<h1>%1$s</h1>'
                . '<p>%2$s</p>'
                . '<a class="dsm-button dsm-button--primary" href="%3$s">%4$s</a>'
                . '</article>'
                . '</section>',
                esc_html(
                    'Publicidad no incluida'
                ),
                esc_html(
                    'Tu suscripción actual no incluye publicidad.'
                ),
                esc_url(
                    home_url(
                        '/suscripciones/'
                    )
                ),
                esc_html(
                    'Ver suscripciones'
                )
            );
        }

        $areas =
            apply_filters(
                'dsm_location_areas',
                [],
                null,
                'island'
            );

        if (!is_array($areas)) {
            $areas = [];
        }

        $notice =
            isset($_GET['advertising_notice'])
                ? sanitize_key(
                    wp_unslash(
                        (string) $_GET[
                            'advertising_notice'
                        ]
                    )
                )
                : '';

        $error =
            isset($_GET['advertising_error'])
                ? sanitize_text_field(
                    wp_unslash(
                        (string) $_GET[
                            'advertising_error'
                        ]
                    )
                )
                : '';

        ob_start();
        ?>

        <section
            class="
                dsm-customer-advertising
                dsm-customer-advertising--form
            "
        >

            <header class="dsm-customer-advertising__header">

                <div>
                    <h1>Añadir publicidad</h1>

                    <p>
                        Crea una nueva campaña publicitaria.
                    </p>
                </div>

                <div class="dsm-customer-advertising__actions">
                    <a
                        class="dsm-button dsm-button--secondary"
                        href="<?php
                        echo esc_url(
                            home_url(
                                '/mi-publicidad/'
                            )
                        );
                        ?>"
                    >
                        Volver
                    </a>
                </div>

            </header>

            <?php if (
                $notice === 'error'
                && $error !== ''
            ) : ?>

                <div class="dsm-alert dsm-alert--error">
                    <?php
                    echo esc_html(
                        $error
                    );
                    ?>
                </div>

            <?php endif; ?>

            <article class="dsm-card dsm-card--form">

                <form
                    class="dsm-form"
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
                            CustomerAdvertisingController::CREATE_ACTION
                        );
                        ?>"
                    >

                    <?php
                    wp_nonce_field(
                        CustomerAdvertisingController::
                            getNonceAction(),

                        CustomerAdvertisingController::
                            NONCE_FIELD
                    );
                    ?>

                    <div class="dsm-form__field">
                        <label
                            class="dsm-form__label"
                            for="dsm-advertising-title"
                        >
                            Nombre de la publicidad
                        </label>

                        <input
                            id="dsm-advertising-title"
                            class="dsm-form__input"
                            type="text"
                            name="title"
                            maxlength="180"
                            required
                        >

                        <small>
                            Nombre interno para identificar
                            tu campaña.
                        </small>
                    </div>

                    <div class="dsm-form__field">
                        <label
                            class="dsm-form__label"
                            for="dsm-advertising-image"
                        >
                            Imagen del banner
                        </label>

                        <input
                            id="dsm-advertising-image"
                            class="dsm-form__input"
                            type="file"
                            name="banner_image"
                            accept="image/jpeg,image/png,image/webp"
                            required
                        >

                        <small>
                            Tamaño recomendado: 1180 × 360 px.
                            Tamaño mínimo: 900 × 275 px.
                            Se admiten imágenes JPG, PNG y WebP.
                        </small>
                    </div>

                    <div class="dsm-form__field">
                        <label
                            class="dsm-form__label"
                            for="dsm-advertising-url"
                        >
                            URL de destino
                        </label>

                        <input
                            id="dsm-advertising-url"
                            class="dsm-form__input"
                            type="url"
                            name="target_url"
                            placeholder="https://..."
                            required
                        >
                    </div>

                    <div class="dsm-form__field">
                        <label
                            class="dsm-form__label"
                            for="dsm-advertising-area"
                        >
                            Isla
                        </label>

                        <select
                            id="dsm-advertising-area"
                            class="dsm-form__input"
                            name="area_id"
                            required
                        >

                            <option value="">
                                Selecciona una isla
                            </option>

                            <?php foreach (
                                $areas
                                as $area
                            ) : ?>

                                <?php
                                if (!is_array($area)) {
                                    continue;
                                }

                                $areaId =
                                    (int) (
                                        $area['id']
                                        ?? 0
                                    );

                                $areaName =
                                    trim(
                                        (string) (
                                            $area['name']
                                            ?? ''
                                        )
                                    );

                                if (
                                    $areaId <= 0
                                    || $areaName === ''
                                ) {
                                    continue;
                                }
                                ?>

                                <option
                                    value="<?php
                                    echo esc_attr(
                                        (string) $areaId
                                    );
                                    ?>"
                                >
                                    <?php
                                    echo esc_html(
                                        $areaName
                                    );
                                    ?>
                                </option>

                            <?php endforeach; ?>

                        </select>
                    </div>

                    <div class="dsm-alert dsm-alert--warning">
                        Tu publicidad quedará activa al
                        guardarla mientras tu suscripción
                        incluya publicidad.
                    </div>

                    <div class="dsm-card__actions">
                        <button
                            class="dsm-button dsm-button--primary"
                            type="submit"
                        >
                            Crear publicidad
                        </button>

                        <a
                            class="dsm-button dsm-button--secondary"
                            href="<?php
                            echo esc_url(
                                home_url(
                                    '/mi-publicidad/'
                                )
                            );
                            ?>"
                        >
                            Cancelar
                        </a>
                    </div>

                </form>

            </article>

        </section>

        <?php

        $output =
            ob_get_clean();

        return is_string($output)
            ? $output
            : '';
    }

    private static function renderEditForm(
        int $customerId
    ): string {
        $entitlementService =
            new CustomerEntitlementService();

        if (
            !$entitlementService->hasAdvertising(
                $customerId
            )
        ) {
            return sprintf(
                '<section class="dsm-customer-advertising">'
                . '<article class="dsm-card">'
                . '<h1>%1$s</h1>'
                . '<p>%2$s</p>'
                . '<a class="dsm-button dsm-button--primary" href="%3$s">%4$s</a>'
                . '</article>'
                . '</section>',
                esc_html(
                    'Publicidad no incluida'
                ),
                esc_html(
                    'Tu suscripción actual no incluye publicidad.'
                ),
                esc_url(
                    home_url(
                        '/suscripciones/'
                    )
                ),
                esc_html(
                    'Ver suscripciones'
                )
            );
        }

        $bannerId =
            isset($_GET['banner_id'])
                ? absint(
                    wp_unslash(
                        (string) $_GET[
                            'banner_id'
                        ]
                    )
                )
                : 0;

        if ($bannerId <= 0) {
            return self::renderDashboard(
                $customerId
            );
        }

        $repository =
            new AdvertisingBannerRepository();

        if (
            !$repository->belongsToCustomer(
                $bannerId,
                $customerId
            )
        ) {
            return self::renderDashboard(
                $customerId
            );
        }

        $banner =
            $repository->findById(
                $bannerId
            );

        if ($banner === null) {
            return self::renderDashboard(
                $customerId
            );
        }

        $areas =
            apply_filters(
                'dsm_location_areas',
                [],
                null,
                'island'
            );

        if (!is_array($areas)) {
            $areas = [];
        }

        $notice =
            isset($_GET['advertising_notice'])
                ? sanitize_key(
                    wp_unslash(
                        (string) $_GET[
                            'advertising_notice'
                        ]
                    )
                )
                : '';

        $error =
            isset($_GET['advertising_error'])
                ? sanitize_text_field(
                    wp_unslash(
                        (string) $_GET[
                            'advertising_error'
                        ]
                    )
                )
                : '';

        $imageUrl =
            wp_get_attachment_image_url(
                $banner->getImageAttachmentId(),
                'medium'
            );

        ob_start();
        ?>

        <section
            class="
                dsm-customer-advertising
                dsm-customer-advertising--form
            "
        >

            <header class="dsm-customer-advertising__header">

                <div>
                    <h1>Editar mi publicidad</h1>

                    <p>
                        Actualiza los datos visibles de tu publicidad.
                    </p>
                </div>

                <div class="dsm-customer-advertising__actions">
                    <a
                        class="dsm-button dsm-button--secondary"
                        href="<?php
                        echo esc_url(
                            home_url(
                                '/mi-publicidad/'
                            )
                        );
                        ?>"
                    >
                        Volver
                    </a>
                </div>

            </header>

            <?php if (
                $notice === 'error'
                && $error !== ''
            ) : ?>

                <div class="dsm-alert dsm-alert--error">
                    <?php
                    echo esc_html(
                        $error
                    );
                    ?>
                </div>

            <?php endif; ?>

            <article class="dsm-card dsm-card--form">

                <form
                    class="dsm-form"
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
                            CustomerAdvertisingController::
                                UPDATE_ACTION
                        );
                        ?>"
                    >

                    <input
                        type="hidden"
                        name="banner_id"
                        value="<?php
                        echo esc_attr(
                            (string) $banner->getId()
                        );
                        ?>"
                    >

                    <?php
                    wp_nonce_field(
                        CustomerAdvertisingController::
                            getUpdateNonceAction(
                                $banner->getId()
                            ),

                        CustomerAdvertisingController::
                            NONCE_FIELD
                    );
                    ?>

                    <div class="dsm-form__field">
                        <label
                            class="dsm-form__label"
                            for="dsm-advertising-title"
                        >
                            Nombre de la publicidad
                        </label>

                        <input
                            id="dsm-advertising-title"
                            class="dsm-form__input"
                            type="text"
                            name="title"
                            maxlength="180"
                            value="<?php
                            echo esc_attr(
                                $banner->getTitle()
                            );
                            ?>"
                            required
                        >
                    </div>

                    <?php if (
                        is_string(
                            $imageUrl
                        )
                    ) : ?>

                        <div class="dsm-form__field">
                            <span class="dsm-form__label">
                                Imagen actual
                            </span>

                            <img
                                src="<?php
                                echo esc_url(
                                    $imageUrl
                                );
                                ?>"
                                alt=""
                                style="max-width:100%;height:auto;"
                            >
                        </div>

                    <?php endif; ?>

                    <div class="dsm-form__field">
                        <label
                            class="dsm-form__label"
                            for="dsm-advertising-image"
                        >
                            Cambiar imagen del banner
                        </label>

                        <input
                            id="dsm-advertising-image"
                            class="dsm-form__input"
                            type="file"
                            name="banner_image"
                            accept="image/jpeg,image/png,image/webp"
                        >

                        <small>
                            Selecciona una nueva imagen solo si
                            quieres actualizar el banner actual.
                            Si lo dejas vacío, se conservará la
                            imagen existente.
                            Tamaño recomendado: 1180 × 360 px.
                            Tamaño mínimo: 900 × 275 px.
                            Se admiten imágenes JPG, PNG y WebP.
                        </small>
                    </div>

                    <div class="dsm-form__field">
                        <label
                            class="dsm-form__label"
                            for="dsm-advertising-url"
                        >
                            URL de destino
                        </label>

                        <input
                            id="dsm-advertising-url"
                            class="dsm-form__input"
                            type="url"
                            name="target_url"
                            value="<?php
                            echo esc_attr(
                                $banner->getTargetUrl()
                            );
                            ?>"
                            required
                        >
                    </div>

                    <div class="dsm-form__field">
                        <label
                            class="dsm-form__label"
                            for="dsm-advertising-area"
                        >
                            Isla
                        </label>

                        <select
                            id="dsm-advertising-area"
                            class="dsm-form__input"
                            name="area_id"
                            required
                        >

                            <?php foreach (
                                $areas
                                as $area
                            ) : ?>

                                <?php
                                if (!is_array($area)) {
                                    continue;
                                }

                                $areaId =
                                    (int) (
                                        $area['id']
                                        ?? 0
                                    );

                                $areaName =
                                    trim(
                                        (string) (
                                            $area['name']
                                            ?? ''
                                        )
                                    );

                                if (
                                    $areaId <= 0
                                    || $areaName === ''
                                ) {
                                    continue;
                                }
                                ?>

                                <option
                                    value="<?php
                                    echo esc_attr(
                                        (string) $areaId
                                    );
                                    ?>"
                                    <?php
                                    selected(
                                        $banner->getAreaId(),
                                        $areaId
                                    );
                                    ?>
                                >
                                    <?php
                                    echo esc_html(
                                        $areaName
                                    );
                                    ?>
                                </option>

                            <?php endforeach; ?>

                        </select>
                    </div>

                    <div class="dsm-card__actions">

                        <button
                            class="dsm-button dsm-button--primary"
                            type="submit"
                        >
                            Guardar cambios
                        </button>

                        <a
                            class="dsm-button dsm-button--secondary"
                            href="<?php
                            echo esc_url(
                                home_url(
                                    '/mi-publicidad/'
                                )
                            );
                            ?>"
                        >
                            Cancelar
                        </a>

                    </div>

                </form>

            </article>

        </section>

        <?php

        $output =
            ob_get_clean();

        return is_string($output)
            ? $output
            : '';
    }

    private static function areaLabel(
        ?int $areaId
    ): string {
        if (
            $areaId === null
            || $areaId <= 0
        ) {
            return 'Todas las islas';
        }

        $areas =
            apply_filters(
                'dsm_location_areas',
                [],
                null,
                'island'
            );

        if (is_array($areas)) {
            foreach ($areas as $area) {
                if (
                    is_array($area)
                    && (int) (
                        $area['id']
                        ?? 0
                    ) === $areaId
                ) {
                    return trim(
                        (string) (
                            $area['name']
                            ?? 'Isla'
                        )
                    );
                }
            }
        }

        return 'Isla';
    }

    private static function statusLabel(
        string $status
    ): string {
        return match ($status) {
            'active' =>
                'Activa',

            'inactive' =>
                'Inactiva',

            'draft' =>
                'Borrador',

            default =>
                ucfirst(
                    $status
                ),
        };
    }

    private static function renderLoginRequired(): string
    {
        $loginUrl =
            add_query_arg(
                [
                    'redirect_to' =>
                        home_url(
                            '/mi-publicidad/'
                        ),
                ],
                home_url(
                    '/iniciar-sesion/'
                )
            );

        return sprintf(
            '<div class="dsm-empty-state"><p>%1$s</p><a href="%2$s">%3$s</a></div>',
            esc_html(
                'Debes iniciar sesión para gestionar tu publicidad.'
            ),
            esc_url(
                $loginUrl
            ),
            esc_html(
                'Iniciar sesión'
            )
        );
    }

    private function __construct()
    {
    }
}
