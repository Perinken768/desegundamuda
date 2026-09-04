<?php

declare(strict_types=1);

use DSM\Facturacion\Frontend\BillingProfileController;

if (!defined('ABSPATH')) {
    exit;
}

$customerType =
    $billingProfile?->getCustomerType()
    ?? 'individual';

$fiscalName =
    $billingProfile?->getFiscalName()
    ?? '';

$taxId =
    $billingProfile?->getTaxId()
    ?? '';

$addressLine1 =
    $billingProfile?->getAddressLine1()
    ?? '';

$addressLine2 =
    $billingProfile?->getAddressLine2()
    ?? '';

$postalCode =
    $billingProfile?->getPostalCode()
    ?? '';

$city =
    $billingProfile?->getCity()
    ?? '';

$province =
    $billingProfile?->getProvince()
    ?? '';

$countryCode =
    $billingProfile?->getCountryCode()
    ?? 'ES';

$billingEmail =
    $billingProfile?->getBillingEmail()
    ?? $customer->getEmail();
?>

<section class="dsm-account">
    <div class="dsm-container">

        <header class="dsm-account__header">

            <h1 class="dsm-account__title">
                Datos fiscales
            </h1>

            <p class="dsm-account__description">
                Estos datos se utilizarán para emitir tus
                facturas de los servicios contratados en
                DeSegundaMuda.
            </p>

        </header>

        <?php if ($status === 'saved') : ?>

            <div class="dsm-alert dsm-alert--success">
                Tus datos fiscales se han guardado correctamente.
            </div>

        <?php elseif ($status === 'invalid_tax_id') : ?>

            <div class="dsm-alert dsm-alert--error">
                El NIF introducido no es válido.
                Comprueba el número y su letra o carácter de control.
            </div>

        <?php elseif ($status === 'tax_id_required') : ?>

            <div class="dsm-alert dsm-alert--error">
                Debes indicar un NIF válido para la facturación en España.
            </div>

        <?php elseif ($status === 'error') : ?>

            <div class="dsm-alert dsm-alert--error">
                No se pudieron guardar los datos fiscales.
                Revisa la información e inténtalo de nuevo.
            </div>

        <?php endif; ?>

        <article class="dsm-card">

            <form
                class="dsm-form"
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
                        BillingProfileController::getAction()
                    );
                    ?>"
                >

                <?php
                wp_nonce_field(
                    BillingProfileController::getNonceAction(),
                    BillingProfileController::getNonceName()
                );
                ?>

                <div class="dsm-form__field">

                    <label
                        class="dsm-form__label"
                        for="customer_type"
                    >
                        Tipo
                    </label>

                    <select
                        class="dsm-form__input"
                        id="customer_type"
                        name="customer_type"
                    >
                        <option
                            value="individual"
                            <?php selected($customerType, 'individual'); ?>
                        >
                            Particular / autónomo
                        </option>

                        <option
                            value="business"
                            <?php selected($customerType, 'business'); ?>
                        >
                            Empresa
                        </option>
                    </select>

                </div>

                <div class="dsm-form__field">

                    <label
                        class="dsm-form__label"
                        for="fiscal_name"
                    >
                        Nombre o razón social
                    </label>

                    <input
                        class="dsm-form__input"
                        type="text"
                        id="fiscal_name"
                        name="fiscal_name"
                        required
                        value="<?php echo esc_attr($fiscalName); ?>"
                    >

                </div>

                <div class="dsm-form__field">

                    <label
                        class="dsm-form__label"
                        for="tax_id"
                    >
                        NIF
                    </label>

                    <input
                        class="dsm-form__input"
                        type="text"
                        id="tax_id"
                        name="tax_id"
                        maxlength="9"
                        autocomplete="off"
                        value="<?php echo esc_attr($taxId); ?>"
                    >

                    <p class="description">
                        Para España: DNI, NIE o NIF de empresa.
                        Ejemplo de DNI: 12345678Z.
                    </p>

                </div>

                <div class="dsm-form__field">

                    <label
                        class="dsm-form__label"
                        for="address_line_1"
                    >
                        Dirección
                    </label>

                    <input
                        class="dsm-form__input"
                        type="text"
                        id="address_line_1"
                        name="address_line_1"
                        required
                        value="<?php echo esc_attr($addressLine1); ?>"
                    >

                </div>

                <div class="dsm-form__field">

                    <label
                        class="dsm-form__label"
                        for="address_line_2"
                    >
                        Dirección adicional
                    </label>

                    <input
                        class="dsm-form__input"
                        type="text"
                        id="address_line_2"
                        name="address_line_2"
                        value="<?php echo esc_attr($addressLine2); ?>"
                    >

                </div>

                <div class="dsm-form__field">

                    <label
                        class="dsm-form__label"
                        for="postal_code"
                    >
                        Código postal
                    </label>

                    <input
                        class="dsm-form__input"
                        type="text"
                        id="postal_code"
                        name="postal_code"
                        required
                        value="<?php echo esc_attr($postalCode); ?>"
                    >

                </div>

                <div class="dsm-form__field">

                    <label
                        class="dsm-form__label"
                        for="city"
                    >
                        Población
                    </label>

                    <input
                        class="dsm-form__input"
                        type="text"
                        id="city"
                        name="city"
                        required
                        value="<?php echo esc_attr($city); ?>"
                    >

                </div>

                <div class="dsm-form__field">

                    <label
                        class="dsm-form__label"
                        for="province"
                    >
                        Provincia
                    </label>

                    <input
                        class="dsm-form__input"
                        type="text"
                        id="province"
                        name="province"
                        required
                        value="<?php echo esc_attr($province); ?>"
                    >

                </div>

                <div class="dsm-form__field">

                    <label
                        class="dsm-form__label"
                        for="country_code"
                    >
                        País
                    </label>

                    <input
                        class="dsm-form__input"
                        type="text"
                        id="country_code"
                        name="country_code"
                        maxlength="2"
                        required
                        value="<?php echo esc_attr($countryCode); ?>"
                    >

                    <p class="description">
                        Código ISO de dos letras. España: ES.
                    </p>

                </div>

                <div class="dsm-form__field">

                    <label
                        class="dsm-form__label"
                        for="billing_email"
                    >
                        Email de facturación
                    </label>

                    <input
                        class="dsm-form__input"
                        type="email"
                        id="billing_email"
                        name="billing_email"
                        value="<?php echo esc_attr($billingEmail); ?>"
                    >

                </div>

                <div class="dsm-form__actions">

                    <button
                        class="dsm-button dsm-button--primary"
                        type="submit"
                    >
                        Guardar datos fiscales
                    </button>

                    <a
                        class="dsm-button dsm-button--secondary"
                        href="<?php
                        echo esc_url(
                            home_url(
                                '/mi-cuenta/'
                            )
                        );
                        ?>"
                    >
                        Volver a Mi cuenta
                    </a>

                </div>

            </form>

        </article>

    </div>
</section>
