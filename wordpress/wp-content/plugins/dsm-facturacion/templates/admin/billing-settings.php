<?php

declare(strict_types=1);

use DSM\Facturacion\Admin\BillingSettingsPage;
use DSM\Facturacion\Verifactu\VerifactuSettings;

if (!defined('ABSPATH')) {
    exit;
}

$logoAttachmentId =
    max(
        0,
        (int) (
            $settings[
                'logo_attachment_id'
            ]
            ?? 0
        )
    );

$logoUrl =
    $logoAttachmentId > 0
        ? wp_get_attachment_image_url(
            $logoAttachmentId,
            'medium'
        )
        : false;

$verifactuEnabled =
    (int) (
        $verifactuSettings[
            'enabled'
        ]
        ?? 0
    ) === 1;

$verifactuEnvironment =
    (string) (
        $verifactuSettings[
            'environment'
        ]
        ?? VerifactuSettings
            ::ENVIRONMENT_TEST
    );

$verifactuCertificateType =
    (string) (
        $verifactuSettings[
            'certificate_type'
        ]
        ?? VerifactuSettings
            ::CERTIFICATE_STANDARD
    );
?>

<div class="wrap dsm-billing-admin">

    <h1>
        <?php
        echo esc_html__(
            'DSM Facturación',
            'dsm-facturacion'
        );
        ?>
    </h1>

    <p class="dsm-billing-admin__intro">
        Configura los datos fiscales, el diseño de las facturas
        y la integración VERI*FACTU de DeSegundaMuda.
    </p>

    <?php if ($notice === 'saved') : ?>
        <div class="notice notice-success is-dismissible">
            <p>
                La configuración de facturación se ha guardado correctamente.
            </p>
        </div>
    <?php endif; ?>

    <?php if ($error !== '') : ?>
        <div class="notice notice-error">
            <p>
                <?php echo esc_html($error); ?>
            </p>
        </div>
    <?php endif; ?>

    <form
        method="post"
        action="<?php echo esc_url(admin_url('admin-post.php')); ?>"
    >

        <input
            type="hidden"
            name="action"
            value="<?php
            echo esc_attr(
                BillingSettingsPage
                    ::getSaveAction()
            );
            ?>"
        >

        <?php
        wp_nonce_field(
            BillingSettingsPage
                ::getNonceAction(),

            BillingSettingsPage
                ::getNonceName()
        );
        ?>

        <div class="dsm-billing-admin__grid">

            <div class="dsm-billing-card">

                <h2>
                    Datos fiscales del emisor
                </h2>

                <table class="form-table" role="presentation">

                    <tr>
                        <th scope="row">
                            <label for="fiscal_name">
                                Razón social / nombre fiscal
                            </label>
                        </th>

                        <td>
                            <input
                                type="text"
                                id="fiscal_name"
                                name="fiscal_name"
                                class="regular-text"
                                required
                                value="<?php
                                echo esc_attr(
                                    (string) $settings[
                                        'fiscal_name'
                                    ]
                                );
                                ?>"
                            >
                        </td>
                    </tr>

                    <tr>
                        <th scope="row">
                            <label for="tax_id">
                                NIF / CIF
                            </label>
                        </th>

                        <td>
                            <input
                                type="text"
                                id="tax_id"
                                name="tax_id"
                                class="regular-text"
                                required
                                value="<?php
                                echo esc_attr(
                                    (string) $settings[
                                        'tax_id'
                                    ]
                                );
                                ?>"
                            >
                        </td>
                    </tr>

                    <tr>
                        <th scope="row">
                            <label for="address_line_1">
                                Dirección
                            </label>
                        </th>

                        <td>
                            <input
                                type="text"
                                id="address_line_1"
                                name="address_line_1"
                                class="regular-text"
                                required
                                value="<?php
                                echo esc_attr(
                                    (string) $settings[
                                        'address_line_1'
                                    ]
                                );
                                ?>"
                            >
                        </td>
                    </tr>

                    <tr>
                        <th scope="row">
                            <label for="address_line_2">
                                Dirección adicional
                            </label>
                        </th>

                        <td>
                            <input
                                type="text"
                                id="address_line_2"
                                name="address_line_2"
                                class="regular-text"
                                value="<?php
                                echo esc_attr(
                                    (string) $settings[
                                        'address_line_2'
                                    ]
                                );
                                ?>"
                            >
                        </td>
                    </tr>

                    <tr>
                        <th scope="row">
                            <label for="postal_code">
                                Código postal
                            </label>
                        </th>

                        <td>
                            <input
                                type="text"
                                id="postal_code"
                                name="postal_code"
                                class="regular-text"
                                required
                                value="<?php
                                echo esc_attr(
                                    (string) $settings[
                                        'postal_code'
                                    ]
                                );
                                ?>"
                            >
                        </td>
                    </tr>

                    <tr>
                        <th scope="row">
                            <label for="city">
                                Población
                            </label>
                        </th>

                        <td>
                            <input
                                type="text"
                                id="city"
                                name="city"
                                class="regular-text"
                                required
                                value="<?php
                                echo esc_attr(
                                    (string) $settings[
                                        'city'
                                    ]
                                );
                                ?>"
                            >
                        </td>
                    </tr>

                    <tr>
                        <th scope="row">
                            <label for="province">
                                Provincia
                            </label>
                        </th>

                        <td>
                            <input
                                type="text"
                                id="province"
                                name="province"
                                class="regular-text"
                                required
                                value="<?php
                                echo esc_attr(
                                    (string) $settings[
                                        'province'
                                    ]
                                );
                                ?>"
                            >
                        </td>
                    </tr>

                    <tr>
                        <th scope="row">
                            <label for="country_code">
                                País
                            </label>
                        </th>

                        <td>
                            <input
                                type="text"
                                id="country_code"
                                name="country_code"
                                maxlength="2"
                                class="small-text"
                                value="<?php
                                echo esc_attr(
                                    (string) $settings[
                                        'country_code'
                                    ]
                                );
                                ?>"
                            >

                            <p class="description">
                                Código ISO de dos letras. Para España: ES.
                            </p>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row">
                            <label for="email">
                                Email fiscal
                            </label>
                        </th>

                        <td>
                            <input
                                type="email"
                                id="email"
                                name="email"
                                class="regular-text"
                                value="<?php
                                echo esc_attr(
                                    (string) $settings[
                                        'email'
                                    ]
                                );
                                ?>"
                            >
                        </td>
                    </tr>

                </table>

            </div>

            <div class="dsm-billing-card">

                <h2>
                    Configuración de facturas
                </h2>

                <table class="form-table" role="presentation">

                    <tr>
                        <th scope="row">
                            <label for="series">
                                Serie
                            </label>
                        </th>

                        <td>
                            <input
                                type="text"
                                id="series"
                                name="series"
                                maxlength="30"
                                class="regular-text"
                                required
                                value="<?php
                                echo esc_attr(
                                    (string) $settings[
                                        'series'
                                    ]
                                );
                                ?>"
                            >

                            <p class="description">
                                Ejemplo: DSM.
                            </p>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row">
                            Impuesto
                        </th>

                        <td>
                            <strong>IGIC</strong>

                            <p class="description">
                                DSM factura únicamente los
                                servicios prestados por la plataforma.
                            </p>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row">
                            <label for="tax_rate">
                                Tipo de IGIC
                            </label>
                        </th>

                        <td>
                            <input
                                type="number"
                                id="tax_rate"
                                name="tax_rate"
                                min="0"
                                max="100"
                                step="0.0001"
                                value="<?php
                                echo esc_attr(
                                    (string) $settings[
                                        'tax_rate'
                                    ]
                                );
                                ?>"
                            >
                            %
                        </td>
                    </tr>

                    <tr>
                        <th scope="row">
                            Precios
                        </th>

                        <td>
                            <label>
                                <input
                                    type="checkbox"
                                    name="prices_include_tax"
                                    value="1"
                                    <?php
                                    checked(
                                        (int) $settings[
                                            'prices_include_tax'
                                        ],
                                        1
                                    );
                                    ?>
                                >

                                Los precios publicados incluyen IGIC
                            </label>
                        </td>
                    </tr>

                </table>

            </div>

            <div class="dsm-billing-card">

                <h2>
                    Diseño de factura
                </h2>

                <table class="form-table" role="presentation">

                    <tr>
                        <th scope="row">
                            Logo
                        </th>

                        <td>

                            <input
                                type="hidden"
                                id="logo_attachment_id"
                                name="logo_attachment_id"
                                value="<?php
                                echo esc_attr(
                                    (string) $logoAttachmentId
                                );
                                ?>"
                            >

                            <div
                                id="dsm-billing-logo-preview"
                                class="dsm-billing-logo-preview"
                            >
                                <?php
                                if (
                                    is_string(
                                        $logoUrl
                                    )
                                    && $logoUrl !== ''
                                ) :
                                    ?>
                                    <img
                                        src="<?php
                                        echo esc_url(
                                            $logoUrl
                                        );
                                        ?>"
                                        alt=""
                                    >
                                <?php endif; ?>
                            </div>

                            <button
                                type="button"
                                class="button"
                                id="dsm-billing-select-logo"
                            >
                                Seleccionar logo
                            </button>

                            <button
                                type="button"
                                class="button"
                                id="dsm-billing-remove-logo"
                            >
                                Quitar logo
                            </button>

                        </td>
                    </tr>

                    <tr>
                        <th scope="row">
                            <label for="footer_text">
                                Texto del pie
                            </label>
                        </th>

                        <td>
                            <textarea
                                id="footer_text"
                                name="footer_text"
                                rows="5"
                                class="large-text"
                            ><?php
                            echo esc_textarea(
                                (string) $settings[
                                    'footer_text'
                                ]
                            );
                            ?></textarea>

                            <p class="description">
                                Texto comercial, contacto o información
                                que quieras mostrar al final de la factura.
                            </p>
                        </td>
                    </tr>

                </table>

            </div>

            <div class="dsm-billing-card">

                <h2>
                    VERI*FACTU
                </h2>

                <p>
                    Configuración de la comunicación de
                    DeSegundaMuda con la Agencia Tributaria.
                </p>

                <table class="form-table" role="presentation">

                    <tr>
                        <th scope="row">
                            Activar VERI*FACTU
                        </th>

                        <td>
                            <label>
                                <input
                                    type="checkbox"
                                    name="verifactu_enabled"
                                    value="1"
                                    <?php
                                    checked(
                                        $verifactuEnabled
                                    );
                                    ?>
                                >

                                Activar la integración VERI*FACTU
                            </label>

                            <p class="description">
                                Durante el desarrollo debe permanecer
                                desactivado hasta completar las pruebas.
                            </p>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row">
                            <label for="verifactu_environment">
                                Entorno
                            </label>
                        </th>

                        <td>
                            <select
                                id="verifactu_environment"
                                name="verifactu_environment"
                            >

                                <option
                                    value="test"
                                    <?php
                                    selected(
                                        $verifactuEnvironment,
                                        VerifactuSettings
                                            ::ENVIRONMENT_TEST
                                    );
                                    ?>
                                >
                                    Pruebas
                                </option>

                                <option
                                    value="production"
                                    <?php
                                    selected(
                                        $verifactuEnvironment,
                                        VerifactuSettings
                                            ::ENVIRONMENT_PRODUCTION
                                    );
                                    ?>
                                >
                                    Producción
                                </option>

                            </select>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row">
                            <label for="verifactu_certificate_type">
                                Tipo de certificado
                            </label>
                        </th>

                        <td>
                            <select
                                id="verifactu_certificate_type"
                                name="verifactu_certificate_type"
                            >

                                <option
                                    value="standard"
                                    <?php
                                    selected(
                                        $verifactuCertificateType,
                                        VerifactuSettings
                                            ::CERTIFICATE_STANDARD
                                    );
                                    ?>
                                >
                                    Certificado electrónico
                                </option>

                                <option
                                    value="seal"
                                    <?php
                                    selected(
                                        $verifactuCertificateType,
                                        VerifactuSettings
                                            ::CERTIFICATE_SEAL
                                    );
                                    ?>
                                >
                                    Certificado de sello electrónico
                                </option>

                            </select>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row">
                            Servicio AEAT
                        </th>

                        <td>
                            <code
                                style="
                                    display:block;
                                    max-width:100%;
                                    padding:8px;
                                    overflow-wrap:anywhere;
                                "
                            ><?php
                            echo esc_html(
                                $verifactuEndpoint
                            );
                            ?></code>

                            <p class="description">
                                La dirección se selecciona
                                automáticamente según el entorno
                                y el tipo de certificado.
                            </p>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row">
                            WSDL oficial
                        </th>

                        <td>
                            <code
                                style="
                                    display:block;
                                    max-width:100%;
                                    padding:8px;
                                    overflow-wrap:anywhere;
                                "
                            ><?php
                            echo esc_html(
                                $verifactuWsdl
                            );
                            ?></code>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row">
                            Sistema
                        </th>

                        <td>
                            <strong>
                                <?php
                                echo esc_html(
                                    (string) $verifactuSettings[
                                        'system_name'
                                    ]
                                );
                                ?>
                            </strong>

                            <br>

                            ID:
                            <code>
                                <?php
                                echo esc_html(
                                    (string) $verifactuSettings[
                                        'system_id'
                                    ]
                                );
                                ?>
                            </code>

                            <br>

                            Versión:
                            <code>
                                <?php
                                echo esc_html(
                                    (string) $verifactuSettings[
                                        'system_version'
                                    ]
                                );
                                ?>
                            </code>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row">
                            Instalación
                        </th>

                        <td>
                            <code>
                                <?php
                                echo esc_html(
                                    (string) $verifactuSettings[
                                        'installation_id'
                                    ]
                                );
                                ?>
                            </code>

                            <p class="description">
                                Identificador interno único de esta
                                instalación de DSM Facturación.
                            </p>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row">
                            Certificado
                        </th>

                        <td>
                            <strong>
                                Pendiente de configurar
                            </strong>

                            <p class="description">
                                El certificado se almacenará fuera
                                del directorio público de WordPress.
                                No se guardará en wp_options.
                            </p>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row">
                            Estado
                        </th>

                        <td>

                            <?php
                            if (
                                $verifactuStatus
                                === 'disabled'
                            ) :
                                ?>

                                <strong>
                                    VERI*FACTU desactivado
                                </strong>

                            <?php
                            elseif (
                                $verifactuStatus
                                === 'certificate_missing'
                            ) :
                                ?>

                                <strong>
                                    Falta configurar el certificado
                                </strong>

                            <?php else : ?>

                                <strong>
                                    Configuración preparada
                                </strong>

                            <?php endif; ?>

                        </td>
                    </tr>

                </table>

            </div>

        </div>

        <?php
        submit_button(
            'Guardar configuración'
        );
        ?>

    </form>

</div>

<script>
document.addEventListener(
    'DOMContentLoaded',
    function () {
        const selectButton =
            document.getElementById(
                'dsm-billing-select-logo'
            );

        const removeButton =
            document.getElementById(
                'dsm-billing-remove-logo'
            );

        const input =
            document.getElementById(
                'logo_attachment_id'
            );

        const preview =
            document.getElementById(
                'dsm-billing-logo-preview'
            );

        if (
            !selectButton
            || !removeButton
            || !input
            || !preview
            || typeof wp === 'undefined'
            || !wp.media
        ) {
            return;
        }

        let frame =
            null;

        selectButton.addEventListener(
            'click',
            function () {
                if (frame) {
                    frame.open();
                    return;
                }

                frame =
                    wp.media({
                        title:
                            'Seleccionar logo de factura',

                        button: {
                            text:
                                'Usar este logo'
                        },

                        multiple:
                            false,

                        library: {
                            type:
                                'image'
                        }
                    });

                frame.on(
                    'select',
                    function () {
                        const attachment =
                            frame
                                .state()
                                .get(
                                    'selection'
                                )
                                .first()
                                .toJSON();

                        input.value =
                            attachment.id
                            || '';

                        const url =
                            attachment.sizes
                            && attachment
                                .sizes
                                .medium
                                ? attachment
                                    .sizes
                                    .medium
                                    .url
                                : attachment.url;

                        preview.innerHTML =
                            '';

                        const image =
                            document
                                .createElement(
                                    'img'
                                );

                        image.src =
                            url;

                        image.alt =
                            '';

                        preview.appendChild(
                            image
                        );
                    }
                );

                frame.open();
            }
        );

        removeButton.addEventListener(
            'click',
            function () {
                input.value =
                    '';

                preview.innerHTML =
                    '';
            }
        );
    }
);
</script>
