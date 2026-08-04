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
                                <?php
                                checked(
                                    $phoneCallsEnabled
                                );
                                ?>
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
                                <?php
                                checked(
                                    $whatsappEnabled
                                );
                                ?>
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