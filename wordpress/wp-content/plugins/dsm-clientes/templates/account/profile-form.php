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
 */
?>

<section class="dsm-account">
    <div class="dsm-container">

        <header class="dsm-account__header">
            <h1 class="dsm-account__title">
                <?php esc_html_e(
                    'Editar perfil',
                    'dsm-clientes'
                ); ?>
            </h1>

            <p class="dsm-account__description">
                <?php esc_html_e(
                    'Completa los datos que usarás en DeSegundaMuda.',
                    'dsm-clientes'
                ); ?>
            </p>
        </header>

        <?php if ($updated) : ?>
            <div class="dsm-alert dsm-alert--success">
                <?php esc_html_e(
                    'Tu perfil se ha actualizado correctamente.',
                    'dsm-clientes'
                ); ?>
            </div>
        <?php endif; ?>

        <?php if ($hasError) : ?>
            <div class="dsm-alert dsm-alert--error">
                <?php esc_html_e(
                    'No se pudo actualizar el perfil. Revisa los datos.',
                    'dsm-clientes'
                ); ?>
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
                        <?php esc_html_e(
                            'Nombre visible',
                            'dsm-clientes'
                        ); ?>
                    </label>

                    <input
                        class="dsm-form__input"
                        id="dsm-profile-display-name"
                        name="display_name"
                        type="text"
                        maxlength="150"
                        value="<?php echo esc_attr(
                            $profile->getDisplayName()
                        ); ?>"
                        required
                    >
                </div>

                <div class="dsm-form__field">
                    <label
                        class="dsm-form__label"
                        for="dsm-profile-phone"
                    >
                        <?php esc_html_e(
                            'Teléfono',
                            'dsm-clientes'
                        ); ?>
                    </label>

                    <input
                        class="dsm-form__input"
                        id="dsm-profile-phone"
                        name="phone"
                        type="tel"
                        maxlength="30"
                        value="<?php echo esc_attr(
                            $profile->getPhone() ?? ''
                        ); ?>"
                    >
                </div>

                <div class="dsm-form__field">
                    <label
                        class="dsm-form__label"
                        for="dsm-profile-whatsapp"
                    >
                        <?php esc_html_e(
                            'WhatsApp',
                            'dsm-clientes'
                        ); ?>
                    </label>

                    <input
                        class="dsm-form__input"
                        id="dsm-profile-whatsapp"
                        name="whatsapp_phone"
                        type="tel"
                        maxlength="30"
                        value="<?php echo esc_attr(
                            $profile->getWhatsappPhone() ?? ''
                        ); ?>"
                    >
                </div>

                <div class="dsm-form__field">
                    <label
                        class="dsm-form__label"
                        for="dsm-profile-bio"
                    >
                        <?php esc_html_e(
                            'Biografía',
                            'dsm-clientes'
                        ); ?>
                    </label>

                    <textarea
                        class="dsm-form__input dsm-form__textarea"
                        id="dsm-profile-bio"
                        name="bio"
                        maxlength="2000"
                        rows="7"
                    ><?php echo esc_textarea(
                        $profile->getBio() ?? ''
                    ); ?></textarea>
                </div>

                <button
                    class="dsm-button dsm-button--primary"
                    type="submit"
                >
                    <?php esc_html_e(
                        'Guardar cambios',
                        'dsm-clientes'
                    ); ?>
                </button>
            </form>
        </article>

    </div>
</section>