<?php

declare(strict_types=1);

use DSM\Ubicaciones\Admin\LocationsPage;
use DSM\Ubicaciones\Area\Area;
use DSM\Ubicaciones\Country\Country;
use DSM\Ubicaciones\Municipality\Municipality;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Variables proporcionadas por LocationsPage:
 *
 * @var array<int, Country>      $countries
 * @var array<int, Area>         $areas
 * @var array<int, Municipality> $municipalities
 * @var array<int, Country>      $countriesById
 * @var array<int, Area>         $areasById
 * @var array<int, array<int, Area>> $areasGroupedByCountry
 * @var array<int, array<int, Municipality>> $municipalitiesGroupedByArea
 * @var string $selectedTab
 * @var string $notice
 * @var string $error
 */

$adminPostUrl =
    admin_url(
        'admin-post.php'
    );

$pageUrl =
    admin_url(
        'admin.php'
    );

$basePageUrl =
    add_query_arg(
        [
            'page' =>
                LocationsPage::MENU_SLUG,
        ],
        $pageUrl
    );

$tabs = [
    'countries' => [
        'label' =>
            __(
                'Países',
                'dsm-ubicaciones'
            ),

        'url' =>
            add_query_arg(
                [
                    'tab' =>
                        'countries',
                ],
                $basePageUrl
            ),
    ],

    'areas' => [
        'label' =>
            __(
                'Áreas',
                'dsm-ubicaciones'
            ),

        'url' =>
            add_query_arg(
                [
                    'tab' =>
                        'areas',
                ],
                $basePageUrl
            ),
    ],

    'municipalities' => [
        'label' =>
            __(
                'Municipios',
                'dsm-ubicaciones'
            ),

        'url' =>
            add_query_arg(
                [
                    'tab' =>
                        'municipalities',
                ],
                $basePageUrl
            ),
    ],
];

$noticeMessages = [
    'country_created' =>
        __(
            'El país se ha creado correctamente.',
            'dsm-ubicaciones'
        ),

    'country_updated' =>
        __(
            'El país se ha actualizado correctamente.',
            'dsm-ubicaciones'
        ),

    'country_activated' =>
        __(
            'El país se ha activado.',
            'dsm-ubicaciones'
        ),

    'country_deactivated' =>
        __(
            'El país se ha desactivado.',
            'dsm-ubicaciones'
        ),

    'area_created' =>
        __(
            'El área se ha creado correctamente.',
            'dsm-ubicaciones'
        ),

    'area_updated' =>
        __(
            'El área se ha actualizado correctamente.',
            'dsm-ubicaciones'
        ),

    'area_activated' =>
        __(
            'El área se ha activado.',
            'dsm-ubicaciones'
        ),

    'area_deactivated' =>
        __(
            'El área se ha desactivado.',
            'dsm-ubicaciones'
        ),

    'municipality_created' =>
        __(
            'El municipio se ha creado correctamente.',
            'dsm-ubicaciones'
        ),

    'municipality_updated' =>
        __(
            'El municipio se ha actualizado correctamente.',
            'dsm-ubicaciones'
        ),

    'municipality_activated' =>
        __(
            'El municipio se ha activado.',
            'dsm-ubicaciones'
        ),

    'municipality_deactivated' =>
        __(
            'El municipio se ha desactivado.',
            'dsm-ubicaciones'
        ),
];

$errorMessages = [
    'country_save_failed' =>
        __(
            'No se pudo guardar el país. Revisa sus datos.',
            'dsm-ubicaciones'
        ),

    'country_toggle_failed' =>
        __(
            'No se pudo cambiar el estado del país.',
            'dsm-ubicaciones'
        ),

    'area_save_failed' =>
        __(
            'No se pudo guardar el área. Revisa su país, jerarquía y tipo.',
            'dsm-ubicaciones'
        ),

    'area_toggle_failed' =>
        __(
            'No se pudo cambiar el estado del área.',
            'dsm-ubicaciones'
        ),

    'municipality_save_failed' =>
        __(
            'No se pudo guardar el municipio. Revisa el área seleccionada.',
            'dsm-ubicaciones'
        ),

    'municipality_toggle_failed' =>
        __(
            'No se pudo cambiar el estado del municipio.',
            'dsm-ubicaciones'
        ),
];

$areaTypeLabels = [
    Area::TYPE_REGION =>
        __(
            'Región',
            'dsm-ubicaciones'
        ),

    Area::TYPE_ISLAND =>
        __(
            'Isla',
            'dsm-ubicaciones'
        ),

    Area::TYPE_PROVINCE =>
        __(
            'Provincia',
            'dsm-ubicaciones'
        ),

    Area::TYPE_COUNTY =>
        __(
            'Comarca',
            'dsm-ubicaciones'
        ),

    Area::TYPE_COMMERCIAL_ZONE =>
        __(
            'Zona comercial',
            'dsm-ubicaciones'
        ),

    Area::TYPE_OTHER =>
        __(
            'Otra',
            'dsm-ubicaciones'
        ),
];

$noticeMessage =
    $noticeMessages[$notice]
    ?? '';

$errorMessage =
    $errorMessages[$error]
    ?? '';

?>

<div class="wrap dsm-locations-admin">
    <h1 class="wp-heading-inline">
        <?php
        esc_html_e(
            'Ubicaciones',
            'dsm-ubicaciones'
        );
        ?>
    </h1>

    <p class="description">
        <?php
        esc_html_e(
            'Gestiona los países, áreas territoriales y municipios utilizados por DeSegundaMuda.',
            'dsm-ubicaciones'
        );
        ?>
    </p>

    <?php if ($noticeMessage !== '') : ?>
        <div
            class="notice notice-success is-dismissible"
            role="status"
        >
            <p>
                <?php echo esc_html(
                    $noticeMessage
                ); ?>
            </p>
        </div>
    <?php endif; ?>

    <?php if ($errorMessage !== '') : ?>
        <div
            class="notice notice-error is-dismissible"
            role="alert"
        >
            <p>
                <?php echo esc_html(
                    $errorMessage
                ); ?>
            </p>
        </div>
    <?php endif; ?>

    <nav
        class="nav-tab-wrapper"
        aria-label="<?php echo esc_attr__(
            'Secciones de ubicaciones',
            'dsm-ubicaciones'
        ); ?>"
    >
        <?php foreach (
            $tabs
            as $tabKey => $tab
        ) : ?>
            <a
                class="<?php echo esc_attr(
                    'nav-tab'
                    . (
                        $selectedTab === $tabKey
                            ? ' nav-tab-active'
                            : ''
                    )
                ); ?>"
                href="<?php echo esc_url(
                    $tab['url']
                ); ?>"
            >
                <?php echo esc_html(
                    $tab['label']
                ); ?>
            </a>
        <?php endforeach; ?>
    </nav>

    <?php if (
        $selectedTab === 'countries'
    ) : ?>
        <section class="dsm-locations-section">
            <header class="dsm-locations-section__header">
                <div>
                    <h2>
                        <?php
                        esc_html_e(
                            'Países',
                            'dsm-ubicaciones'
                        );
                        ?>
                    </h2>

                    <p>
                        <?php
                        esc_html_e(
                            'Los países agrupan todas las áreas territoriales disponibles.',
                            'dsm-ubicaciones'
                        );
                        ?>
                    </p>
                </div>

                <span class="dsm-locations-count">
                    <?php
                    printf(
                        esc_html(
                            _n(
                                '%s país',
                                '%s países',
                                count($countries),
                                'dsm-ubicaciones'
                            )
                        ),
                        esc_html(
                            number_format_i18n(
                                count($countries)
                            )
                        )
                    );
                    ?>
                </span>
            </header>

            <div class="dsm-locations-card">
                <h3>
                    <?php
                    esc_html_e(
                        'Añadir país',
                        'dsm-ubicaciones'
                    );
                    ?>
                </h3>

                <form
                    class="dsm-locations-create-form"
                    method="post"
                    action="<?php echo esc_url(
                        $adminPostUrl
                    ); ?>"
                >
                    <input
                        type="hidden"
                        name="action"
                        value="dsm_location_save_country"
                    >

                    <input
                        type="hidden"
                        name="country_id"
                        value="0"
                    >

                    <?php
                    wp_nonce_field(
                        'dsm_location_save_country',
                        'dsm_location_nonce'
                    );
                    ?>

                    <div class="dsm-locations-form-grid">
                        <label>
                            <span>
                                <?php
                                esc_html_e(
                                    'Nombre',
                                    'dsm-ubicaciones'
                                );
                                ?>
                            </span>

                            <input
                                type="text"
                                name="name"
                                maxlength="150"
                                required
                            >
                        </label>

                        <label>
                            <span>
                                <?php
                                esc_html_e(
                                    'Código ISO',
                                    'dsm-ubicaciones'
                                );
                                ?>
                            </span>

                            <input
                                type="text"
                                name="iso_code"
                                maxlength="3"
                                placeholder="ES"
                                required
                            >
                        </label>

                        <label>
                            <span>
                                <?php
                                esc_html_e(
                                    'Prefijo telefónico',
                                    'dsm-ubicaciones'
                                );
                                ?>
                            </span>

                            <input
                                type="text"
                                name="phone_prefix"
                                maxlength="10"
                                placeholder="+34"
                            >
                        </label>

                        <label>
                            <span>
                                <?php
                                esc_html_e(
                                    'Orden',
                                    'dsm-ubicaciones'
                                );
                                ?>
                            </span>

                            <input
                                type="number"
                                name="sort_order"
                                min="0"
                                value="0"
                            >
                        </label>

                        <label class="dsm-locations-checkbox">
                            <input
                                type="checkbox"
                                name="is_active"
                                value="1"
                                checked
                            >

                            <span>
                                <?php
                                esc_html_e(
                                    'Activo',
                                    'dsm-ubicaciones'
                                );
                                ?>
                            </span>
                        </label>
                    </div>

                    <?php
                    submit_button(
                        __(
                            'Añadir país',
                            'dsm-ubicaciones'
                        ),
                        'primary',
                        'submit',
                        false
                    );
                    ?>
                </form>
            </div>

            <div class="dsm-locations-table-wrapper">
                <table class="widefat fixed striped dsm-locations-table">
                    <thead>
                        <tr>
                            <th scope="col">
                                <?php
                                esc_html_e(
                                    'Nombre',
                                    'dsm-ubicaciones'
                                );
                                ?>
                            </th>

                            <th scope="col">
                                <?php
                                esc_html_e(
                                    'ISO',
                                    'dsm-ubicaciones'
                                );
                                ?>
                            </th>

                            <th scope="col">
                                <?php
                                esc_html_e(
                                    'Prefijo',
                                    'dsm-ubicaciones'
                                );
                                ?>
                            </th>

                            <th scope="col">
                                <?php
                                esc_html_e(
                                    'Orden',
                                    'dsm-ubicaciones'
                                );
                                ?>
                            </th>

                            <th scope="col">
                                <?php
                                esc_html_e(
                                    'Estado',
                                    'dsm-ubicaciones'
                                );
                                ?>
                            </th>

                            <th scope="col">
                                <?php
                                esc_html_e(
                                    'Acciones',
                                    'dsm-ubicaciones'
                                );
                                ?>
                            </th>
                        </tr>
                    </thead>

                    <tbody>
                        <?php if (
                            $countries === []
                        ) : ?>
                            <tr>
                                <td colspan="6">
                                    <?php
                                    esc_html_e(
                                        'No hay países registrados.',
                                        'dsm-ubicaciones'
                                    );
                                    ?>
                                </td>
                            </tr>
                        <?php else : ?>
                            <?php foreach (
                                $countries
                                as $country
                            ) : ?>
                                <?php
                                $countryId =
                                    $country->getId();

                                $saveFormId =
                                    'dsm-country-save-'
                                    . $countryId;

                                $toggleFormId =
                                    'dsm-country-toggle-'
                                    . $countryId;
                                ?>

                                <form
                                    id="<?php echo esc_attr(
                                        $saveFormId
                                    ); ?>"
                                    method="post"
                                    action="<?php echo esc_url(
                                        $adminPostUrl
                                    ); ?>"
                                >
                                    <input
                                        type="hidden"
                                        name="action"
                                        value="dsm_location_save_country"
                                    >

                                    <input
                                        type="hidden"
                                        name="country_id"
                                        value="<?php echo esc_attr(
                                            (string) $countryId
                                        ); ?>"
                                    >

                                    <?php
                                    wp_nonce_field(
                                        'dsm_location_save_country',
                                        'dsm_location_nonce'
                                    );
                                    ?>
                                </form>

                                <form
                                    id="<?php echo esc_attr(
                                        $toggleFormId
                                    ); ?>"
                                    method="post"
                                    action="<?php echo esc_url(
                                        $adminPostUrl
                                    ); ?>"
                                >
                                    <input
                                        type="hidden"
                                        name="action"
                                        value="dsm_location_toggle_country"
                                    >

                                    <input
                                        type="hidden"
                                        name="country_id"
                                        value="<?php echo esc_attr(
                                            (string) $countryId
                                        ); ?>"
                                    >

                                    <input
                                        type="hidden"
                                        name="is_active"
                                        value="<?php echo esc_attr(
                                            $country->isActive()
                                                ? '0'
                                                : '1'
                                        ); ?>"
                                    >

                                    <?php
                                    wp_nonce_field(
                                        'dsm_location_toggle_country',
                                        'dsm_location_nonce'
                                    );
                                    ?>
                                </form>

                                <tr>
                                    <td>
                                        <input
                                            form="<?php echo esc_attr(
                                                $saveFormId
                                            ); ?>"
                                            type="text"
                                            name="name"
                                            maxlength="150"
                                            value="<?php echo esc_attr(
                                                $country->getName()
                                            ); ?>"
                                            required
                                        >

                                        <code>
                                            <?php echo esc_html(
                                                $country->getSlug()
                                            ); ?>
                                        </code>
                                    </td>

                                    <td>
                                        <input
                                            form="<?php echo esc_attr(
                                                $saveFormId
                                            ); ?>"
                                            type="text"
                                            name="iso_code"
                                            maxlength="3"
                                            value="<?php echo esc_attr(
                                                $country->getIsoCode()
                                            ); ?>"
                                            required
                                        >
                                    </td>

                                    <td>
                                        <input
                                            form="<?php echo esc_attr(
                                                $saveFormId
                                            ); ?>"
                                            type="text"
                                            name="phone_prefix"
                                            maxlength="10"
                                            value="<?php echo esc_attr(
                                                $country->getPhonePrefix()
                                                ?? ''
                                            ); ?>"
                                        >
                                    </td>

                                    <td>
                                        <input
                                            form="<?php echo esc_attr(
                                                $saveFormId
                                            ); ?>"
                                            type="number"
                                            name="sort_order"
                                            min="0"
                                            value="<?php echo esc_attr(
                                                (string) $country
                                                    ->getSortOrder()
                                            ); ?>"
                                        >
                                    </td>

                                    <td>
                                        <label class="dsm-locations-checkbox">
                                            <input
                                                form="<?php echo esc_attr(
                                                    $saveFormId
                                                ); ?>"
                                                type="checkbox"
                                                name="is_active"
                                                value="1"
                                                <?php checked(
                                                    $country->isActive()
                                                ); ?>
                                            >

                                            <span class="<?php echo esc_attr(
                                                $country->isActive()
                                                    ? 'dsm-status dsm-status--active'
                                                    : 'dsm-status dsm-status--inactive'
                                            ); ?>">
                                                <?php
                                                echo esc_html(
                                                    $country->isActive()
                                                        ? __(
                                                            'Activo',
                                                            'dsm-ubicaciones'
                                                        )
                                                        : __(
                                                            'Inactivo',
                                                            'dsm-ubicaciones'
                                                        )
                                                );
                                                ?>
                                            </span>
                                        </label>
                                    </td>

                                    <td>
                                        <div class="dsm-locations-row-actions">
                                            <button
                                                form="<?php echo esc_attr(
                                                    $saveFormId
                                                ); ?>"
                                                class="button button-primary"
                                                type="submit"
                                            >
                                                <?php
                                                esc_html_e(
                                                    'Guardar',
                                                    'dsm-ubicaciones'
                                                );
                                                ?>
                                            </button>

                                            <button
                                                form="<?php echo esc_attr(
                                                    $toggleFormId
                                                ); ?>"
                                                class="button"
                                                type="submit"
                                            >
                                                <?php
                                                echo esc_html(
                                                    $country->isActive()
                                                        ? __(
                                                            'Desactivar',
                                                            'dsm-ubicaciones'
                                                        )
                                                        : __(
                                                            'Activar',
                                                            'dsm-ubicaciones'
                                                        )
                                                );
                                                ?>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>
    <?php endif; ?>

    <?php if (
        $selectedTab === 'areas'
    ) : ?>
        <section class="dsm-locations-section">
            <header class="dsm-locations-section__header">
                <div>
                    <h2>
                        <?php
                        esc_html_e(
                            'Áreas territoriales',
                            'dsm-ubicaciones'
                        );
                        ?>
                    </h2>

                    <p>
                        <?php
                        esc_html_e(
                            'Una zona puede representar una región, isla, provincia, comarca o zona comercial.',
                            'dsm-ubicaciones'
                        );
                        ?>
                    </p>
                </div>

                <span class="dsm-locations-count">
                    <?php
                    printf(
                        esc_html(
                            _n(
                                '%s área',
                                '%s áreas',
                                count($areas),
                                'dsm-ubicaciones'
                            )
                        ),
                        esc_html(
                            number_format_i18n(
                                count($areas)
                            )
                        )
                    );
                    ?>
                </span>
            </header>

            <div class="dsm-locations-card">
                <h3>
                    <?php
                    esc_html_e(
                        'Añadir área',
                        'dsm-ubicaciones'
                    );
                    ?>
                </h3>

                <?php if ($countries === []) : ?>
                    <div class="notice notice-warning inline">
                        <p>
                            <?php
                            esc_html_e(
                                'Primero debes crear al menos un país.',
                                'dsm-ubicaciones'
                            );
                            ?>
                        </p>
                    </div>
                <?php else : ?>
                    <form
                        class="dsm-locations-create-form"
                        method="post"
                        action="<?php echo esc_url(
                            $adminPostUrl
                        ); ?>"
                        data-dsm-area-form
                    >
                        <input
                            type="hidden"
                            name="action"
                            value="dsm_location_save_area"
                        >

                        <input
                            type="hidden"
                            name="area_id"
                            value="0"
                        >

                        <?php
                        wp_nonce_field(
                            'dsm_location_save_area',
                            'dsm_location_nonce'
                        );
                        ?>

                        <div class="dsm-locations-form-grid">
                            <label>
                                <span>
                                    <?php
                                    esc_html_e(
                                        'País',
                                        'dsm-ubicaciones'
                                    );
                                    ?>
                                </span>

                                <select
                                    name="country_id"
                                    required
                                    data-dsm-country-select
                                >
                                    <?php foreach (
                                        $countries
                                        as $country
                                    ) : ?>
                                        <option
                                            value="<?php echo esc_attr(
                                                (string) $country->getId()
                                            ); ?>"
                                        >
                                            <?php echo esc_html(
                                                $country->getDisplayLabel()
                                            ); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </label>

                            <label>
                                <span>
                                    <?php
                                    esc_html_e(
                                        'Área superior',
                                        'dsm-ubicaciones'
                                    );
                                    ?>
                                </span>

                                <select
                                    name="parent_id"
                                    data-dsm-parent-area-select
                                >
                                    <option value="0">
                                        <?php
                                        esc_html_e(
                                            'Sin área superior',
                                            'dsm-ubicaciones'
                                        );
                                        ?>
                                    </option>

                                    <?php foreach (
                                        $areas
                                        as $possibleParent
                                    ) : ?>
                                        <option
                                            value="<?php echo esc_attr(
                                                (string) $possibleParent
                                                    ->getId()
                                            ); ?>"
                                            data-country-id="<?php echo esc_attr(
                                                (string) $possibleParent
                                                    ->getCountryId()
                                            ); ?>"
                                        >
                                            <?php echo esc_html(
                                                $possibleParent->getName()
                                                . ' — '
                                                . $possibleParent
                                                    ->getAreaTypeLabel()
                                            ); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </label>

                            <label>
                                <span>
                                    <?php
                                    esc_html_e(
                                        'Nombre',
                                        'dsm-ubicaciones'
                                    );
                                    ?>
                                </span>

                                <input
                                    type="text"
                                    name="name"
                                    maxlength="150"
                                    required
                                >
                            </label>

                            <label>
                                <span>
                                    <?php
                                    esc_html_e(
                                        'Tipo',
                                        'dsm-ubicaciones'
                                    );
                                    ?>
                                </span>

                                <select
                                    name="area_type"
                                    required
                                >
                                    <?php foreach (
                                        $areaTypeLabels
                                        as $areaType => $areaTypeLabel
                                    ) : ?>
                                        <option
                                            value="<?php echo esc_attr(
                                                $areaType
                                            ); ?>"
                                        >
                                            <?php echo esc_html(
                                                $areaTypeLabel
                                            ); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </label>

                            <label>
                                <span>
                                    <?php
                                    esc_html_e(
                                        'Código',
                                        'dsm-ubicaciones'
                                    );
                                    ?>
                                </span>

                                <input
                                    type="text"
                                    name="code"
                                    maxlength="30"
                                >
                            </label>

                            <label>
                                <span>
                                    <?php
                                    esc_html_e(
                                        'Orden',
                                        'dsm-ubicaciones'
                                    );
                                    ?>
                                </span>

                                <input
                                    type="number"
                                    name="sort_order"
                                    min="0"
                                    value="0"
                                >
                            </label>

                            <label class="dsm-locations-checkbox">
                                <input
                                    type="checkbox"
                                    name="is_active"
                                    value="1"
                                    checked
                                >

                                <span>
                                    <?php
                                    esc_html_e(
                                        'Activa',
                                        'dsm-ubicaciones'
                                    );
                                    ?>
                                </span>
                            </label>
                        </div>

                        <?php
                        submit_button(
                            __(
                                'Añadir área',
                                'dsm-ubicaciones'
                            ),
                            'primary',
                            'submit',
                            false
                        );
                        ?>
                    </form>
                <?php endif; ?>
            </div>

            <div class="dsm-locations-table-wrapper">
                <table class="widefat fixed striped dsm-locations-table">
                    <thead>
                        <tr>
                            <th scope="col">
                                <?php
                                esc_html_e(
                                    'País',
                                    'dsm-ubicaciones'
                                );
                                ?>
                            </th>

                            <th scope="col">
                                <?php
                                esc_html_e(
                                    'Superior',
                                    'dsm-ubicaciones'
                                );
                                ?>
                            </th>

                            <th scope="col">
                                <?php
                                esc_html_e(
                                    'Nombre',
                                    'dsm-ubicaciones'
                                );
                                ?>
                            </th>

                            <th scope="col">
                                <?php
                                esc_html_e(
                                    'Tipo',
                                    'dsm-ubicaciones'
                                );
                                ?>
                            </th>

                            <th scope="col">
                                <?php
                                esc_html_e(
                                    'Código',
                                    'dsm-ubicaciones'
                                );
                                ?>
                            </th>

                            <th scope="col">
                                <?php
                                esc_html_e(
                                    'Orden',
                                    'dsm-ubicaciones'
                                );
                                ?>
                            </th>

                            <th scope="col">
                                <?php
                                esc_html_e(
                                    'Estado',
                                    'dsm-ubicaciones'
                                );
                                ?>
                            </th>

                            <th scope="col">
                                <?php
                                esc_html_e(
                                    'Acciones',
                                    'dsm-ubicaciones'
                                );
                                ?>
                            </th>
                        </tr>
                    </thead>

                    <tbody>
                        <?php if ($areas === []) : ?>
                            <tr>
                                <td colspan="8">
                                    <?php
                                    esc_html_e(
                                        'No hay áreas registradas.',
                                        'dsm-ubicaciones'
                                    );
                                    ?>
                                </td>
                            </tr>
                        <?php else : ?>
                            <?php foreach (
                                $areas
                                as $area
                            ) : ?>
                                <?php
                                $areaId =
                                    $area->getId();

                                $saveFormId =
                                    'dsm-area-save-'
                                    . $areaId;

                                $toggleFormId =
                                    'dsm-area-toggle-'
                                    . $areaId;

                                $country =
                                    $countriesById[
                                        $area->getCountryId()
                                    ]
                                    ?? null;

                                $possibleParents =
                                    LocationsPage::
                                        filterPossibleParents(
                                            $areas,
                                            $area->getCountryId(),
                                            $areaId
                                        );
                                ?>

                                <form
                                    id="<?php echo esc_attr(
                                        $saveFormId
                                    ); ?>"
                                    method="post"
                                    action="<?php echo esc_url(
                                        $adminPostUrl
                                    ); ?>"
                                >
                                    <input
                                        type="hidden"
                                        name="action"
                                        value="dsm_location_save_area"
                                    >

                                    <input
                                        type="hidden"
                                        name="area_id"
                                        value="<?php echo esc_attr(
                                            (string) $areaId
                                        ); ?>"
                                    >

                                    <?php
                                    wp_nonce_field(
                                        'dsm_location_save_area',
                                        'dsm_location_nonce'
                                    );
                                    ?>
                                </form>

                                <form
                                    id="<?php echo esc_attr(
                                        $toggleFormId
                                    ); ?>"
                                    method="post"
                                    action="<?php echo esc_url(
                                        $adminPostUrl
                                    ); ?>"
                                >
                                    <input
                                        type="hidden"
                                        name="action"
                                        value="dsm_location_toggle_area"
                                    >

                                    <input
                                        type="hidden"
                                        name="area_id"
                                        value="<?php echo esc_attr(
                                            (string) $areaId
                                        ); ?>"
                                    >

                                    <input
                                        type="hidden"
                                        name="is_active"
                                        value="<?php echo esc_attr(
                                            $area->isActive()
                                                ? '0'
                                                : '1'
                                        ); ?>"
                                    >

                                    <?php
                                    wp_nonce_field(
                                        'dsm_location_toggle_area',
                                        'dsm_location_nonce'
                                    );
                                    ?>
                                </form>

                                <tr>
                                    <td>
                                        <select
                                            form="<?php echo esc_attr(
                                                $saveFormId
                                            ); ?>"
                                            name="country_id"
                                            required
                                        >
                                            <?php foreach (
                                                $countries
                                                as $countryOption
                                            ) : ?>
                                                <option
                                                    value="<?php echo esc_attr(
                                                        (string) $countryOption
                                                            ->getId()
                                                    ); ?>"
                                                    <?php selected(
                                                        $area->getCountryId(),
                                                        $countryOption->getId()
                                                    ); ?>
                                                >
                                                    <?php echo esc_html(
                                                        $countryOption
                                                            ->getName()
                                                    ); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </td>

                                    <td>
                                        <select
                                            form="<?php echo esc_attr(
                                                $saveFormId
                                            ); ?>"
                                            name="parent_id"
                                        >
                                            <option value="0">
                                                <?php
                                                esc_html_e(
                                                    'Sin superior',
                                                    'dsm-ubicaciones'
                                                );
                                                ?>
                                            </option>

                                            <?php foreach (
                                                $possibleParents
                                                as $possibleParent
                                            ) : ?>
                                                <option
                                                    value="<?php echo esc_attr(
                                                        (string) $possibleParent
                                                            ->getId()
                                                    ); ?>"
                                                    <?php selected(
                                                        $area->getParentId(),
                                                        $possibleParent
                                                            ->getId()
                                                    ); ?>
                                                >
                                                    <?php echo esc_html(
                                                        $possibleParent
                                                            ->getName()
                                                    ); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </td>

                                    <td>
                                        <input
                                            form="<?php echo esc_attr(
                                                $saveFormId
                                            ); ?>"
                                            type="text"
                                            name="name"
                                            maxlength="150"
                                            value="<?php echo esc_attr(
                                                $area->getName()
                                            ); ?>"
                                            required
                                        >

                                        <code>
                                            <?php echo esc_html(
                                                $area->getSlug()
                                            ); ?>
                                        </code>
                                    </td>

                                    <td>
                                        <select
                                            form="<?php echo esc_attr(
                                                $saveFormId
                                            ); ?>"
                                            name="area_type"
                                            required
                                        >
                                            <?php foreach (
                                                $areaTypeLabels
                                                as $areaType => $areaTypeLabel
                                            ) : ?>
                                                <option
                                                    value="<?php echo esc_attr(
                                                        $areaType
                                                    ); ?>"
                                                    <?php selected(
                                                        $area->getAreaType(),
                                                        $areaType
                                                    ); ?>
                                                >
                                                    <?php echo esc_html(
                                                        $areaTypeLabel
                                                    ); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </td>

                                    <td>
                                        <input
                                            form="<?php echo esc_attr(
                                                $saveFormId
                                            ); ?>"
                                            type="text"
                                            name="code"
                                            maxlength="30"
                                            value="<?php echo esc_attr(
                                                $area->getCode()
                                                ?? ''
                                            ); ?>"
                                        >
                                    </td>

                                    <td>
                                        <input
                                            form="<?php echo esc_attr(
                                                $saveFormId
                                            ); ?>"
                                            type="number"
                                            name="sort_order"
                                            min="0"
                                            value="<?php echo esc_attr(
                                                (string) $area
                                                    ->getSortOrder()
                                            ); ?>"
                                        >
                                    </td>

                                    <td>
                                        <label class="dsm-locations-checkbox">
                                            <input
                                                form="<?php echo esc_attr(
                                                    $saveFormId
                                                ); ?>"
                                                type="checkbox"
                                                name="is_active"
                                                value="1"
                                                <?php checked(
                                                    $area->isActive()
                                                ); ?>
                                            >

                                            <span class="<?php echo esc_attr(
                                                $area->isActive()
                                                    ? 'dsm-status dsm-status--active'
                                                    : 'dsm-status dsm-status--inactive'
                                            ); ?>">
                                                <?php
                                                echo esc_html(
                                                    $area->isActive()
                                                        ? __(
                                                            'Activa',
                                                            'dsm-ubicaciones'
                                                        )
                                                        : __(
                                                            'Inactiva',
                                                            'dsm-ubicaciones'
                                                        )
                                                );
                                                ?>
                                            </span>
                                        </label>
                                    </td>

                                    <td>
                                        <div class="dsm-locations-row-actions">
                                            <button
                                                form="<?php echo esc_attr(
                                                    $saveFormId
                                                ); ?>"
                                                class="button button-primary"
                                                type="submit"
                                            >
                                                <?php
                                                esc_html_e(
                                                    'Guardar',
                                                    'dsm-ubicaciones'
                                                );
                                                ?>
                                            </button>

                                            <button
                                                form="<?php echo esc_attr(
                                                    $toggleFormId
                                                ); ?>"
                                                class="button"
                                                type="submit"
                                            >
                                                <?php
                                                echo esc_html(
                                                    $area->isActive()
                                                        ? __(
                                                            'Desactivar',
                                                            'dsm-ubicaciones'
                                                        )
                                                        : __(
                                                            'Activar',
                                                            'dsm-ubicaciones'
                                                        )
                                                );
                                                ?>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>
    <?php endif; ?>

    <?php if (
        $selectedTab === 'municipalities'
    ) : ?>
        <section class="dsm-locations-section">
            <header class="dsm-locations-section__header">
                <div>
                    <h2>
                        <?php
                        esc_html_e(
                            'Municipios',
                            'dsm-ubicaciones'
                        );
                        ?>
                    </h2>

                    <p>
                        <?php
                        esc_html_e(
                            'Cada municipio debe pertenecer a un área territorial.',
                            'dsm-ubicaciones'
                        );
                        ?>
                    </p>
                </div>

                <span class="dsm-locations-count">
                    <?php
                    printf(
                        esc_html(
                            _n(
                                '%s municipio',
                                '%s municipios',
                                count($municipalities),
                                'dsm-ubicaciones'
                            )
                        ),
                        esc_html(
                            number_format_i18n(
                                count($municipalities)
                            )
                        )
                    );
                    ?>
                </span>
            </header>

            <div class="dsm-locations-card">
                <h3>
                    <?php
                    esc_html_e(
                        'Añadir municipio',
                        'dsm-ubicaciones'
                    );
                    ?>
                </h3>

                <?php if ($areas === []) : ?>
                    <div class="notice notice-warning inline">
                        <p>
                            <?php
                            esc_html_e(
                                'Primero debes crear al menos un área.',
                                'dsm-ubicaciones'
                            );
                            ?>
                        </p>
                    </div>
                <?php else : ?>
                    <form
                        class="dsm-locations-create-form"
                        method="post"
                        action="<?php echo esc_url(
                            $adminPostUrl
                        ); ?>"
                    >
                        <input
                            type="hidden"
                            name="action"
                            value="dsm_location_save_municipality"
                        >

                        <input
                            type="hidden"
                            name="municipality_id"
                            value="0"
                        >

                        <?php
                        wp_nonce_field(
                            'dsm_location_save_municipality',
                            'dsm_location_nonce'
                        );
                        ?>

                        <div class="dsm-locations-form-grid">
                            <label>
                                <span>
                                    <?php
                                    esc_html_e(
                                        'Área',
                                        'dsm-ubicaciones'
                                    );
                                    ?>
                                </span>

                                <select
                                    name="area_id"
                                    required
                                >
                                    <?php foreach (
                                        $countries
                                        as $country
                                    ) : ?>
                                        <?php
                                        $countryAreas =
                                            $areasGroupedByCountry[
                                                $country->getId()
                                            ]
                                            ?? [];

                                        if ($countryAreas === []) {
                                            continue;
                                        }
                                        ?>

                                        <optgroup
                                            label="<?php echo esc_attr(
                                                $country->getName()
                                            ); ?>"
                                        >
                                            <?php foreach (
                                                $countryAreas
                                                as $area
                                            ) : ?>
                                                <option
                                                    value="<?php echo esc_attr(
                                                        (string) $area->getId()
                                                    ); ?>"
                                                >
                                                    <?php echo esc_html(
                                                        $area->getName()
                                                        . ' — '
                                                        . $area
                                                            ->getAreaTypeLabel()
                                                    ); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </optgroup>
                                    <?php endforeach; ?>
                                </select>
                            </label>

                            <label>
                                <span>
                                    <?php
                                    esc_html_e(
                                        'Nombre',
                                        'dsm-ubicaciones'
                                    );
                                    ?>
                                </span>

                                <input
                                    type="text"
                                    name="name"
                                    maxlength="150"
                                    required
                                >
                            </label>

                            <label>
                                <span>
                                    <?php
                                    esc_html_e(
                                        'Código',
                                        'dsm-ubicaciones'
                                    );
                                    ?>
                                </span>

                                <input
                                    type="text"
                                    name="code"
                                    maxlength="30"
                                >
                            </label>

                            <label>
                                <span>
                                    <?php
                                    esc_html_e(
                                        'Código postal',
                                        'dsm-ubicaciones'
                                    );
                                    ?>
                                </span>

                                <input
                                    type="text"
                                    name="postal_code"
                                    maxlength="20"
                                >
                            </label>

                            <label>
                                <span>
                                    <?php
                                    esc_html_e(
                                        'Orden',
                                        'dsm-ubicaciones'
                                    );
                                    ?>
                                </span>

                                <input
                                    type="number"
                                    name="sort_order"
                                    min="0"
                                    value="0"
                                >
                            </label>

                            <label class="dsm-locations-checkbox">
                                <input
                                    type="checkbox"
                                    name="is_active"
                                    value="1"
                                    checked
                                >

                                <span>
                                    <?php
                                    esc_html_e(
                                        'Activo',
                                        'dsm-ubicaciones'
                                    );
                                    ?>
                                </span>
                            </label>
                        </div>

                        <?php
                        submit_button(
                            __(
                                'Añadir municipio',
                                'dsm-ubicaciones'
                            ),
                            'primary',
                            'submit',
                            false
                        );
                        ?>
                    </form>
                <?php endif; ?>
            </div>

            <div class="dsm-locations-toolbar">
                <label>
                    <span class="screen-reader-text">
                        <?php
                        esc_html_e(
                            'Filtrar municipios',
                            'dsm-ubicaciones'
                        );
                        ?>
                    </span>

                    <input
                        type="search"
                        placeholder="<?php echo esc_attr__(
                            'Buscar municipio o área…',
                            'dsm-ubicaciones'
                        ); ?>"
                        data-dsm-municipality-search
                    >
                </label>
            </div>

            <div class="dsm-locations-table-wrapper">
                <table class="widefat fixed striped dsm-locations-table">
                    <thead>
                        <tr>
                            <th scope="col">
                                <?php
                                esc_html_e(
                                    'Área',
                                    'dsm-ubicaciones'
                                );
                                ?>
                            </th>

                            <th scope="col">
                                <?php
                                esc_html_e(
                                    'Nombre',
                                    'dsm-ubicaciones'
                                );
                                ?>
                            </th>

                            <th scope="col">
                                <?php
                                esc_html_e(
                                    'Código',
                                    'dsm-ubicaciones'
                                );
                                ?>
                            </th>

                            <th scope="col">
                                <?php
                                esc_html_e(
                                    'Código postal',
                                    'dsm-ubicaciones'
                                );
                                ?>
                            </th>

                            <th scope="col">
                                <?php
                                esc_html_e(
                                    'Orden',
                                    'dsm-ubicaciones'
                                );
                                ?>
                            </th>

                            <th scope="col">
                                <?php
                                esc_html_e(
                                    'Estado',
                                    'dsm-ubicaciones'
                                );
                                ?>
                            </th>

                            <th scope="col">
                                <?php
                                esc_html_e(
                                    'Acciones',
                                    'dsm-ubicaciones'
                                );
                                ?>
                            </th>
                        </tr>
                    </thead>

                    <tbody data-dsm-municipality-list>
                        <?php if (
                            $municipalities === []
                        ) : ?>
                            <tr>
                                <td colspan="7">
                                    <?php
                                    esc_html_e(
                                        'No hay municipios registrados.',
                                        'dsm-ubicaciones'
                                    );
                                    ?>
                                </td>
                            </tr>
                        <?php else : ?>
                            <?php foreach (
                                $municipalities
                                as $municipality
                            ) : ?>
                                <?php
                                $municipalityId =
                                    $municipality->getId();

                                $saveFormId =
                                    'dsm-municipality-save-'
                                    . $municipalityId;

                                $toggleFormId =
                                    'dsm-municipality-toggle-'
                                    . $municipalityId;

                                $currentArea =
                                    $areasById[
                                        $municipality->getAreaId()
                                    ]
                                    ?? null;

                                $searchValue =
                                    strtolower(
                                        trim(
                                            $municipality->getName()
                                            . ' '
                                            . (
                                                $currentArea?->getName()
                                                ?? ''
                                            )
                                        )
                                    );
                                ?>

                                <form
                                    id="<?php echo esc_attr(
                                        $saveFormId
                                    ); ?>"
                                    method="post"
                                    action="<?php echo esc_url(
                                        $adminPostUrl
                                    ); ?>"
                                >
                                    <input
                                        type="hidden"
                                        name="action"
                                        value="dsm_location_save_municipality"
                                    >

                                    <input
                                        type="hidden"
                                        name="municipality_id"
                                        value="<?php echo esc_attr(
                                            (string) $municipalityId
                                        ); ?>"
                                    >

                                    <?php
                                    wp_nonce_field(
                                        'dsm_location_save_municipality',
                                        'dsm_location_nonce'
                                    );
                                    ?>
                                </form>

                                <form
                                    id="<?php echo esc_attr(
                                        $toggleFormId
                                    ); ?>"
                                    method="post"
                                    action="<?php echo esc_url(
                                        $adminPostUrl
                                    ); ?>"
                                >
                                    <input
                                        type="hidden"
                                        name="action"
                                        value="dsm_location_toggle_municipality"
                                    >

                                    <input
                                        type="hidden"
                                        name="municipality_id"
                                        value="<?php echo esc_attr(
                                            (string) $municipalityId
                                        ); ?>"
                                    >

                                    <input
                                        type="hidden"
                                        name="is_active"
                                        value="<?php echo esc_attr(
                                            $municipality->isActive()
                                                ? '0'
                                                : '1'
                                        ); ?>"
                                    >

                                    <?php
                                    wp_nonce_field(
                                        'dsm_location_toggle_municipality',
                                        'dsm_location_nonce'
                                    );
                                    ?>
                                </form>

                                <tr
                                    data-dsm-municipality-row
                                    data-search="<?php echo esc_attr(
                                        $searchValue
                                    ); ?>"
                                >
                                    <td>
                                        <select
                                            form="<?php echo esc_attr(
                                                $saveFormId
                                            ); ?>"
                                            name="area_id"
                                            required
                                        >
                                            <?php foreach (
                                                $countries
                                                as $country
                                            ) : ?>
                                                <?php
                                                $countryAreas =
                                                    $areasGroupedByCountry[
                                                        $country->getId()
                                                    ]
                                                    ?? [];

                                                if ($countryAreas === []) {
                                                    continue;
                                                }
                                                ?>

                                                <optgroup
                                                    label="<?php echo esc_attr(
                                                        $country->getName()
                                                    ); ?>"
                                                >
                                                    <?php foreach (
                                                        $countryAreas
                                                        as $area
                                                    ) : ?>
                                                        <option
                                                            value="<?php echo esc_attr(
                                                                (string) $area
                                                                    ->getId()
                                                            ); ?>"
                                                            <?php selected(
                                                                $municipality
                                                                    ->getAreaId(),
                                                                $area->getId()
                                                            ); ?>
                                                        >
                                                            <?php echo esc_html(
                                                                $area->getName()
                                                            ); ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </optgroup>
                                            <?php endforeach; ?>
                                        </select>
                                    </td>

                                    <td>
                                        <input
                                            form="<?php echo esc_attr(
                                                $saveFormId
                                            ); ?>"
                                            type="text"
                                            name="name"
                                            maxlength="150"
                                            value="<?php echo esc_attr(
                                                $municipality->getName()
                                            ); ?>"
                                            required
                                        >

                                        <code>
                                            <?php echo esc_html(
                                                $municipality->getSlug()
                                            ); ?>
                                        </code>
                                    </td>

                                    <td>
                                        <input
                                            form="<?php echo esc_attr(
                                                $saveFormId
                                            ); ?>"
                                            type="text"
                                            name="code"
                                            maxlength="30"
                                            value="<?php echo esc_attr(
                                                $municipality->getCode()
                                                ?? ''
                                            ); ?>"
                                        >
                                    </td>

                                    <td>
                                        <input
                                            form="<?php echo esc_attr(
                                                $saveFormId
                                            ); ?>"
                                            type="text"
                                            name="postal_code"
                                            maxlength="20"
                                            value="<?php echo esc_attr(
                                                $municipality
                                                    ->getPostalCode()
                                                ?? ''
                                            ); ?>"
                                        >
                                    </td>

                                    <td>
                                        <input
                                            form="<?php echo esc_attr(
                                                $saveFormId
                                            ); ?>"
                                            type="number"
                                            name="sort_order"
                                            min="0"
                                            value="<?php echo esc_attr(
                                                (string) $municipality
                                                    ->getSortOrder()
                                            ); ?>"
                                        >
                                    </td>

                                    <td>
                                        <label class="dsm-locations-checkbox">
                                            <input
                                                form="<?php echo esc_attr(
                                                    $saveFormId
                                                ); ?>"
                                                type="checkbox"
                                                name="is_active"
                                                value="1"
                                                <?php checked(
                                                    $municipality->isActive()
                                                ); ?>
                                            >

                                            <span class="<?php echo esc_attr(
                                                $municipality->isActive()
                                                    ? 'dsm-status dsm-status--active'
                                                    : 'dsm-status dsm-status--inactive'
                                            ); ?>">
                                                <?php
                                                echo esc_html(
                                                    $municipality->isActive()
                                                        ? __(
                                                            'Activo',
                                                            'dsm-ubicaciones'
                                                        )
                                                        : __(
                                                            'Inactivo',
                                                            'dsm-ubicaciones'
                                                        )
                                                );
                                                ?>
                                            </span>
                                        </label>
                                    </td>

                                    <td>
                                        <div class="dsm-locations-row-actions">
                                            <button
                                                form="<?php echo esc_attr(
                                                    $saveFormId
                                                ); ?>"
                                                class="button button-primary"
                                                type="submit"
                                            >
                                                <?php
                                                esc_html_e(
                                                    'Guardar',
                                                    'dsm-ubicaciones'
                                                );
                                                ?>
                                            </button>

                                            <button
                                                form="<?php echo esc_attr(
                                                    $toggleFormId
                                                ); ?>"
                                                class="button"
                                                type="submit"
                                            >
                                                <?php
                                                echo esc_html(
                                                    $municipality->isActive()
                                                        ? __(
                                                            'Desactivar',
                                                            'dsm-ubicaciones'
                                                        )
                                                        : __(
                                                            'Activar',
                                                            'dsm-ubicaciones'
                                                        )
                                                );
                                                ?>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>
    <?php endif; ?>
</div>