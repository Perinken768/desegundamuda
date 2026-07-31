<?php

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

/**
 * @var string|null $errorCode
 */

$errorMessages = [
    'invalid_email' => __(
        'El correo electrónico no es válido.',
        'dsm-clientes'
    ),
    'password_too_short' => __(
        'La contraseña debe tener al menos 10 caracteres.',
        'dsm-clientes'
    ),
    'password_mismatch' => __(
        'Las contraseñas no coinciden.',
        'dsm-clientes'
    ),
    'invalid_display_name' => __(
        'El nombre visible no es válido.',
        'dsm-clientes'
    ),
    'email_exists' => __(
        'Ya existe una cuenta con ese correo electrónico.',
        'dsm-clientes'
    ),
    'registration_failed' => __(
        'No se pudo completar el registro. Inténtalo nuevamente.',
        'dsm-clientes'
    ),
];

$errorMessage = $errorCode !== null
    && isset($errorMessages[$errorCode])
        ? $errorMessages[$errorCode]
        : null;
?>

<section class="dsm-auth">
    <div class="dsm-auth__card">

        <header class="dsm-auth__header">
            <h1 class="dsm-auth__title">
                <?php
                esc_html_e(
                    'Crear una cuenta',
                    'dsm-clientes'
                );
                ?>
            </h1>

            <p class="dsm-auth__description">
                <?php
                esc_html_e(
                    'Regístrate para publicar y gestionar tus anuncios.',
                    'dsm-clientes'
                );
                ?>
            </p>
        </header>

        <?php if ($errorMessage !== null) : ?>
            <div
                class="dsm-alert dsm-alert--error"
                role="alert"
            >
                <?php echo esc_html($errorMessage); ?>
            </div>
        <?php endif; ?>

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
                value="dsm_customer_register"
            >

            <?php
            wp_nonce_field(
                'dsm_customer_register',
                'dsm_register_nonce'
            );
            ?>

            <div class="dsm-form__field">
                <label
                    class="dsm-form__label"
                    for="dsm-register-display-name"
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
                    id="dsm-register-display-name"
                    name="display_name"
                    type="text"
                    maxlength="150"
                    autocomplete="nickname"
                    required
                >
            </div>

            <div class="dsm-form__field">
                <label
                    class="dsm-form__label"
                    for="dsm-register-email"
                >
                    <?php
                    esc_html_e(
                        'Correo electrónico',
                        'dsm-clientes'
                    );
                    ?>
                </label>

                <input
                    class="dsm-form__input"
                    id="dsm-register-email"
                    name="email"
                    type="email"
                    autocomplete="email"
                    required
                >
            </div>

            <div class="dsm-form__field">
                <label
                    class="dsm-form__label"
                    for="dsm-register-password"
                >
                    <?php
                    esc_html_e(
                        'Contraseña',
                        'dsm-clientes'
                    );
                    ?>
                </label>

                <input
                    class="dsm-form__input"
                    id="dsm-register-password"
                    name="password"
                    type="password"
                    minlength="10"
                    autocomplete="new-password"
                    required
                >

                <small class="dsm-form__help">
                    <?php
                    esc_html_e(
                        'Debe tener al menos 10 caracteres.',
                        'dsm-clientes'
                    );
                    ?>
                </small>
            </div>

            <div class="dsm-form__field">
                <label
                    class="dsm-form__label"
                    for="dsm-register-password-confirmation"
                >
                    <?php
                    esc_html_e(
                        'Repetir contraseña',
                        'dsm-clientes'
                    );
                    ?>
                </label>

                <input
                    class="dsm-form__input"
                    id="dsm-register-password-confirmation"
                    name="password_confirmation"
                    type="password"
                    minlength="10"
                    autocomplete="new-password"
                    required
                >
            </div>

            <button
                class="dsm-button dsm-button--primary dsm-button--block"
                type="submit"
            >
                <?php
                esc_html_e(
                    'Crear mi cuenta',
                    'dsm-clientes'
                );
                ?>
            </button>
        </form>

        <footer class="dsm-auth__footer">
            <p>
                <?php
                esc_html_e(
                    '¿Ya tienes una cuenta?',
                    'dsm-clientes'
                );
                ?>

                <a href="<?php echo esc_url(
                    home_url('/iniciar-sesion/')
                ); ?>">
                    <?php
                    esc_html_e(
                        'Inicia sesión',
                        'dsm-clientes'
                    );
                    ?>
                </a>
            </p>
        </footer>

    </div>
</section>