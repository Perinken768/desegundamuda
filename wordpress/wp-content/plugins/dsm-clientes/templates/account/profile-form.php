<?php

declare(strict_types=1);

use DSM\Clientes\Customer\Customer;
use DSM\Clientes\Profile\CustomerProfile;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * @var Customer $customer
 * @var CustomerProfile $profile
 * @var bool $updated
 * @var bool $hasError
 * @var bool $hasValidContactMethod
 * @var bool $allowsPhoneCalls
 * @var bool $allowsWhatsapp
 * @var string $normalizedPhone
 * @var array<int, array<string, mixed>> $countries
 * @var array<int, array<string, mixed>> $areas
 * @var array<int, array<string, mixed>> $municipalities
 * @var int|null $selectedAreaId
 * @var int|null $selectedMunicipalityId
 * @var bool $locationsAvailable
 */

$phoneValue =
    isset($normalizedPhone)
        ? trim($normalizedPhone)
        : (
            $profile->getPhone()
            ?? ''
        );

$phoneCallsEnabled =
    isset($allowsPhoneCalls)
        ? $allowsPhoneCalls
        : $profile->allowsPhoneCalls();

$whatsappEnabled =
    isset($allowsWhatsapp)
        ? $allowsWhatsapp
        : $profile->allowsWhatsapp();

$contactIsValid =
    isset($hasValidContactMethod)
        ? $hasValidContactMethod
        : $profile->hasValidContactMethod();

$countries =
    isset($countries)
    && is_array($countries)
        ? $countries
        : [];

$areas =
    isset($areas)
    && is_array($areas)
        ? $areas
        : [];

$municipalities =
    isset($municipalities)
    && is_array($municipalities)
        ? $municipalities
        : [];

$locationsAvailable =
    isset($locationsAvailable)
        ? (bool) $locationsAvailable
        : (
            $countries !== []
            && $areas !== []
        );

$selectedAreaId =
    isset($selectedAreaId)
    && $selectedAreaId !== null
        ? max(
            0,
            (int) $selectedAreaId
        )
        : 0;

$selectedMunicipalityId =
    isset($selectedMunicipalityId)
    && $selectedMunicipalityId !== null
        ? max(
            0,
            (int) $selectedMunicipalityId
        )
        : 0;

/*
 * Determina el país correspondiente al área seleccionada.
 */
$selectedCountryId =
    0;

foreach ($areas as $area) {
    if (!is_array($area)) {
        continue;
    }

    if (
        (int) (
            $area['id']
            ?? 0
        ) !== $selectedAreaId
    ) {
        continue;
    }

    $selectedCountryId =
        max(
            0,
            (int) (
                $area['country_id']
                ?? 0
            )
        );

    break;
}

/*
 * Si todavía no existe selección, utiliza el primer país activo.
 */
if (
    $selectedCountryId <= 0
    && isset($countries[0])
    && is_array($countries[0])
) {
    $selectedCountryId =
        max(
            0,
            (int) (
                $countries[0]['id']
                ?? 0
            )
        );
}

/*
 * Las regiones organizativas, como Canarias, no se ofrecen
 * como ubicación final del perfil. Se muestran las áreas
 * concretas: islas, provincias, comarcas, etc.
 */
$selectableAreas = [];

foreach ($areas as $area) {
    if (!is_array($area)) {
        continue;
    }

    $areaId =
        max(
            0,
            (int) (
                $area['id']
                ?? 0
            )
        );

    $countryId =
        max(
            0,
            (int) (
                $area['country_id']
                ?? 0
            )
        );

    $areaName =
        trim(
            (string) (
                $area['name']
                ?? ''
            )
        );

    $areaType =
        sanitize_key(
            (string) (
                $area['area_type']
                ?? 'other'
            )
        );

    if (
        $areaId <= 0
        || $countryId <= 0
        || $areaName === ''
        || $areaType === 'region'
    ) {
        continue;
    }

    $selectableAreas[] =
        $area;
}

?>

<section class="dsm-account">
    <div class="dsm-container">

        <header class="dsm-account__header">
            <h1 class="dsm-account__title">
                <?php
                esc_html_e(
                    'Editar perfil',
                    'dsm-clientes'
                );
                ?>
            </h1>

            <p class="dsm-account__description">
                <?php
                esc_html_e(
                    'Completa los datos que usarás en DeSegundaMuda.',
                    'dsm-clientes'
                );
                ?>
            </p>
        </header>

        <?php if ($updated) : ?>
            <div class="dsm-alert dsm-alert--success">
                <?php
                esc_html_e(
                    'Tu perfil se ha actualizado correctamente.',
                    'dsm-clientes'
                );
                ?>
            </div>
        <?php endif; ?>

        <?php if ($hasError) : ?>
            <div class="dsm-alert dsm-alert--error">
                <?php
                esc_html_e(
                    'No se pudo actualizar el perfil. Revisa los datos introducidos.',
                    'dsm-clientes'
                );
                ?>
            </div>
        <?php endif; ?>

        <?php if (!$contactIsValid) : ?>
            <div class="dsm-alert dsm-alert--warning">
                <strong>
                    <?php
                    esc_html_e(
                        'Configura una forma de contacto',
                        'dsm-clientes'
                    );
                    ?>
                </strong>

                <p>
                    <?php
                    esc_html_e(
                        'Para publicar anuncios debes indicar un teléfono y permitir al menos llamadas o WhatsApp.',
                        'dsm-clientes'
                    );
                    ?>
                </p>
            </div>
        <?php endif; ?>

        <article class="dsm-card dsm-card--form">
            <form
                class="dsm-form"
                method="post"
                action="<?php echo esc_url(
                    admin_url('admin-post.php')
                ); ?>"
                data-dsm-profile-form
            >
                <input
                    type="hidden"
                    name="action"
                    value="dsm_customer_profile_update"
                >

                <?php
                wp_nonce_field(
                    'dsm_customer_profile_update',
                    'dsm_profile_nonce'
                );
                ?>

                <div class="dsm-form__field">
                    <label
                        class="dsm-form__label"
                        for="dsm-profile-display-name"
                    >
                        <?php
                        esc_html_e(
                            'Nombre visible',
                            'dsm-clientes'
                        );
                        ?>
                    </label>

                    <input
                        class="dsm-form__input"
                        id="dsm-profile-display-name"
                        name="display_name"
                        type="text"
                        maxlength="150"
                        autocomplete="name"
                        value="<?php echo esc_attr(
                            $profile->getDisplayName()
                        ); ?>"
                        required
                    >

                    <p class="dsm-form__help">
                        <?php
                        esc_html_e(
                            'Este es el nombre que verán otros clientes.',
                            'dsm-clientes'
                        );
                        ?>
                    </p>
                </div>

                <fieldset class="dsm-form__fieldset">
                    <legend class="dsm-form__legend">
                        <?php
                        esc_html_e(
                            'Ubicación',
                            'dsm-clientes'
                        );
                        ?>
                    </legend>

                    <p class="dsm-form__help">
                        <?php
                        esc_html_e(
                            'La ubicación se utilizará para personalizar el marketplace y facilitar las búsquedas cercanas.',
                            'dsm-clientes'
                        );
                        ?>
                    </p>

                    <?php if (!$locationsAvailable) : ?>
                        <div class="dsm-alert dsm-alert--warning">
                            <?php
                            esc_html_e(
                                'El catálogo de ubicaciones no está disponible en este momento.',
                                'dsm-clientes'
                            );
                            ?>
                        </div>
                    <?php else : ?>
                        <div class="dsm-form__field">
                            <label
                                class="dsm-form__label"
                                for="dsm-profile-country"
                            >
                                <?php
                                esc_html_e(
                                    'País',
                                    'dsm-clientes'
                                );
                                ?>
                            </label>

                            <select
                                class="dsm-form__input"
                                id="dsm-profile-country"
                                data-dsm-profile-country
                            >
                                <?php foreach (
                                    $countries
                                    as $country
                                ) : ?>
                                    <?php
                                    if (!is_array($country)) {
                                        continue;
                                    }

                                    $countryId =
                                        max(
                                            0,
                                            (int) (
                                                $country['id']
                                                ?? 0
                                            )
                                        );

                                    $countryName =
                                        trim(
                                            (string) (
                                                $country['name']
                                                ?? ''
                                            )
                                        );

                                    if (
                                        $countryId <= 0
                                        || $countryName === ''
                                    ) {
                                        continue;
                                    }

                                    $isoCode =
                                        trim(
                                            (string) (
                                                $country['iso_code']
                                                ?? ''
                                            )
                                        );

                                    $countryLabel =
                                        $countryName;

                                    if ($isoCode !== '') {
                                        $countryLabel .=
                                            ' ('
                                            . strtoupper($isoCode)
                                            . ')';
                                    }
                                    ?>

                                    <option
                                        value="<?php echo esc_attr(
                                            (string) $countryId
                                        ); ?>"
                                        <?php selected(
                                            $selectedCountryId,
                                            $countryId
                                        ); ?>
                                    >
                                        <?php echo esc_html(
                                            $countryLabel
                                        ); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="dsm-form__field">
                            <label
                                class="dsm-form__label"
                                for="dsm-profile-area"
                            >
                                <?php
                                esc_html_e(
                                    'Área',
                                    'dsm-clientes'
                                );
                                ?>
                            </label>

                            <select
                                class="dsm-form__input"
                                id="dsm-profile-area"
                                name="area_id"
                                data-dsm-profile-area
                            >
                                <option value="0">
                                    <?php
                                    esc_html_e(
                                        'Selecciona un área',
                                        'dsm-clientes'
                                    );
                                    ?>
                                </option>

                                <?php foreach (
                                    $selectableAreas
                                    as $area
                                ) : ?>
                                    <?php
                                    $areaId =
                                        max(
                                            0,
                                            (int) (
                                                $area['id']
                                                ?? 0
                                            )
                                        );

                                    $countryId =
                                        max(
                                            0,
                                            (int) (
                                                $area['country_id']
                                                ?? 0
                                            )
                                        );

                                    $areaName =
                                        trim(
                                            (string) (
                                                $area['name']
                                                ?? ''
                                            )
                                        );

                                    $areaTypeLabel =
                                        trim(
                                            (string) (
                                                $area[
                                                    'area_type_label'
                                                ]
                                                ?? ''
                                            )
                                        );

                                    $areaLabel =
                                        $areaName;

                                    if ($areaTypeLabel !== '') {
                                        $areaLabel .=
                                            ' — '
                                            . $areaTypeLabel;
                                    }
                                    ?>

                                    <option
                                        value="<?php echo esc_attr(
                                            (string) $areaId
                                        ); ?>"
                                        data-country-id="<?php echo esc_attr(
                                            (string) $countryId
                                        ); ?>"
                                        <?php selected(
                                            $selectedAreaId,
                                            $areaId
                                        ); ?>
                                    >
                                        <?php echo esc_html(
                                            $areaLabel
                                        ); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>

                            <p class="dsm-form__help">
                                <?php
                                esc_html_e(
                                    'En Canarias, el área corresponde a la isla. En otras zonas podrá representar una provincia, comarca u otra división territorial.',
                                    'dsm-clientes'
                                );
                                ?>
                            </p>
                        </div>

                        <div class="dsm-form__field">
                            <label
                                class="dsm-form__label"
                                for="dsm-profile-municipality"
                            >
                                <?php
                                esc_html_e(
                                    'Municipio',
                                    'dsm-clientes'
                                );
                                ?>
                            </label>

                            <select
                                class="dsm-form__input"
                                id="dsm-profile-municipality"
                                name="municipality_id"
                                data-dsm-profile-municipality
                            >
                                <option value="0">
                                    <?php
                                    esc_html_e(
                                        'Selecciona un municipio',
                                        'dsm-clientes'
                                    );
                                    ?>
                                </option>

                                <?php foreach (
                                    $municipalities
                                    as $municipality
                                ) : ?>
                                    <?php
                                    if (!is_array($municipality)) {
                                        continue;
                                    }

                                    $municipalityId =
                                        max(
                                            0,
                                            (int) (
                                                $municipality['id']
                                                ?? 0
                                            )
                                        );

                                    $municipalityAreaId =
                                        max(
                                            0,
                                            (int) (
                                                $municipality['area_id']
                                                ?? 0
                                            )
                                        );

                                    $municipalityName =
                                        trim(
                                            (string) (
                                                $municipality['name']
                                                ?? ''
                                            )
                                        );

                                    if (
                                        $municipalityId <= 0
                                        || $municipalityAreaId <= 0
                                        || $municipalityName === ''
                                    ) {
                                        continue;
                                    }
                                    ?>

                                    <option
                                        value="<?php echo esc_attr(
                                            (string) $municipalityId
                                        ); ?>"
                                        data-area-id="<?php echo esc_attr(
                                            (string) $municipalityAreaId
                                        ); ?>"
                                        <?php selected(
                                            $selectedMunicipalityId,
                                            $municipalityId
                                        ); ?>
                                    >
                                        <?php echo esc_html(
                                            $municipalityName
                                        ); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>

                            <p
                                class="dsm-form__help"
                                data-dsm-profile-municipality-help
                            >
                                <?php
                                esc_html_e(
                                    'Primero selecciona un área para ver sus municipios.',
                                    'dsm-clientes'
                                );
                                ?>
                            </p>
                        </div>
                    <?php endif; ?>
                </fieldset>

                <fieldset class="dsm-form__fieldset">
                    <legend class="dsm-form__legend">
                        <?php
                        esc_html_e(
                            'Contacto',
                            'dsm-clientes'
                        );
                        ?>
                    </legend>

                    <p class="dsm-form__help">
                        <?php
                        esc_html_e(
                            'Utilizamos un único número para llamadas y WhatsApp. Los números españoles se guardan automáticamente con el prefijo +34.',
                            'dsm-clientes'
                        );
                        ?>
                    </p>

                    <div class="dsm-form__field">
                        <label
                            class="dsm-form__label"
                            for="dsm-profile-phone"
                        >
                            <?php
                            esc_html_e(
                                'Número de teléfono',
                                'dsm-clientes'
                            );
                            ?>
                        </label>

                        <input
                            class="dsm-form__input"
                            id="dsm-profile-phone"
                            name="phone"
                            type="tel"
                            maxlength="30"
                            inputmode="tel"
                            autocomplete="tel"
                            placeholder="+34 600 123 456"
                            value="<?php echo esc_attr(
                                $phoneValue
                            ); ?>"
                        >

                        <p class="dsm-form__help">
                            <?php
                            esc_html_e(
                                'Puedes escribir 600123456, +34 600 123 456 o 0034 600 123 456.',
                                'dsm-clientes'
                            );
                            ?>
                        </p>
                    </div>

                    <div class="dsm-form__field">
                        <label class="dsm-form__checkbox">
                            <input
                                type="checkbox"
                                name="allow_phone_calls"
                                value="1"
                                <?php checked(
                                    $phoneCallsEnabled
                                ); ?>
                            >

                            <span>
                                <?php
                                esc_html_e(
                                    'Permitir que otros clientes me llamen',
                                    'dsm-clientes'
                                );
                                ?>
                            </span>
                        </label>
                    </div>

                    <div class="dsm-form__field">
                        <label class="dsm-form__checkbox">
                            <input
                                type="checkbox"
                                name="allow_whatsapp"
                                value="1"
                                <?php checked(
                                    $whatsappEnabled
                                ); ?>
                            >

                            <span>
                                <?php
                                esc_html_e(
                                    'Permitir que otros clientes contacten conmigo por WhatsApp',
                                    'dsm-clientes'
                                );
                                ?>
                            </span>
                        </label>
                    </div>

                    <div class="dsm-alert dsm-alert--info">
                        <?php
                        esc_html_e(
                            'Tu número no se mostrará como texto en el anuncio. Se utilizará para generar los botones de llamada y WhatsApp que hayas autorizado.',
                            'dsm-clientes'
                        );
                        ?>
                    </div>
                </fieldset>

                <div class="dsm-form__field">
                    <label
                        class="dsm-form__label"
                        for="dsm-profile-bio"
                    >
                        <?php
                        esc_html_e(
                            'Biografía',
                            'dsm-clientes'
                        );
                        ?>
                    </label>

                    <textarea
                        class="dsm-form__input dsm-form__textarea"
                        id="dsm-profile-bio"
                        name="bio"
                        maxlength="2000"
                        rows="7"
                    ><?php echo esc_textarea(
                        $profile->getBio()
                        ?? ''
                    ); ?></textarea>

                    <p class="dsm-form__help">
                        <?php
                        esc_html_e(
                            'Cuenta brevemente quién eres o qué tipo de artículos vendes.',
                            'dsm-clientes'
                        );
                        ?>
                    </p>
                </div>

                <button
                    class="dsm-button dsm-button--primary"
                    type="submit"
                >
                    <?php
                    esc_html_e(
                        'Guardar cambios',
                        'dsm-clientes'
                    );
                    ?>
                </button>
            </form>
        </article>

    </div>
</section>

<script>
(function () {
    'use strict';

    function initializeProfileLocations() {
        const form =
            document.querySelector(
                '[data-dsm-profile-form]'
            );

        if (!(form instanceof HTMLFormElement)) {
            return;
        }

        const countrySelect =
            form.querySelector(
                '[data-dsm-profile-country]'
            );

        const areaSelect =
            form.querySelector(
                '[data-dsm-profile-area]'
            );

        const municipalitySelect =
            form.querySelector(
                '[data-dsm-profile-municipality]'
            );

        if (
            !(countrySelect instanceof HTMLSelectElement)
            || !(areaSelect instanceof HTMLSelectElement)
            || !(municipalitySelect instanceof HTMLSelectElement)
        ) {
            return;
        }

        const areaOptions =
            Array.from(
                areaSelect.options
            ).map(
                function (option) {
                    return {
                        value:
                            option.value,

                        label:
                            option.textContent
                            || '',

                        countryId:
                            option.getAttribute(
                                'data-country-id'
                            )
                            || '',

                        isPlaceholder:
                            option.value === '0',
                    };
                }
            );

        const municipalityOptions =
            Array.from(
                municipalitySelect.options
            ).map(
                function (option) {
                    return {
                        value:
                            option.value,

                        label:
                            option.textContent
                            || '',

                        areaId:
                            option.getAttribute(
                                'data-area-id'
                            )
                            || '',

                        isPlaceholder:
                            option.value === '0',
                    };
                }
            );

        function rebuildSelect(
            select,
            options,
            filterKey,
            filterValue,
            previousValue
        ) {
            select.innerHTML = '';

            options.forEach(
                function (optionData) {
                    if (
                        !optionData.isPlaceholder
                        && optionData[filterKey]
                            !== filterValue
                    ) {
                        return;
                    }

                    const option =
                        document.createElement(
                            'option'
                        );

                    option.value =
                        optionData.value;

                    option.textContent =
                        optionData.label;

                    select.appendChild(
                        option
                    );
                }
            );

            const valueExists =
                Array.from(
                    select.options
                ).some(
                    function (option) {
                        return option.value
                            === previousValue;
                    }
                );

            select.value =
                valueExists
                    ? previousValue
                    : '0';
        }

        function rebuildMunicipalities() {
            const previousMunicipality =
                municipalitySelect.value;

            const selectedArea =
                areaSelect.value;

            rebuildSelect(
                municipalitySelect,
                municipalityOptions,
                'areaId',
                selectedArea,
                previousMunicipality
            );

            municipalitySelect.disabled =
                selectedArea === '0'
                || municipalitySelect.options.length <= 1;
        }

        function rebuildAreas() {
            const previousArea =
                areaSelect.value;

            rebuildSelect(
                areaSelect,
                areaOptions,
                'countryId',
                countrySelect.value,
                previousArea
            );

            rebuildMunicipalities();
        }

        countrySelect.addEventListener(
            'change',
            rebuildAreas
        );

        areaSelect.addEventListener(
            'change',
            rebuildMunicipalities
        );

        rebuildAreas();
    }

    if (
        document.readyState === 'loading'
    ) {
        document.addEventListener(
            'DOMContentLoaded',
            initializeProfileLocations
        );
    } else {
        initializeProfileLocations();
    }
})();
</script>