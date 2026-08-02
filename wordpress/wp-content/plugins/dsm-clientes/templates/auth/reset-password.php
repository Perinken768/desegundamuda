<?php

declare(strict_types=1);

use DSM\Clientes\Authentication\PasswordResetToken;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * @var string $token
 */

$resetStatus = isset($_GET['reset_status'])
    ? sanitize_key(
        wp_unslash($_GET['reset_status'])
    )
    : '';

$validTokenFormat =
    PasswordResetToken::isValidFormat($token);
?>

<section class="dsm-auth">
    <div class="dsm-auth__card">

        <header class="dsm-auth__header">
            <h1 class="dsm-auth__title">
                <?php
                esc_html_e(
                    'Establecer contraseña nueva',
                    'dsm-clientes'
                );
                ?>
            </h1>

            <p class="dsm-auth__description">
                <?php
                esc_html_e(
                    'La contraseña debe tener al menos 12 caracteres.',
                    'dsm-clientes'
                );
                ?>
            </p>
        </header>

        <?php if (
            !$validTokenFormat
            || $resetStatus === 'error'
        ) : ?>
            <div
                class="dsm-alert dsm-alert--error"
                role="alert"
            >
                <?php
                esc_html_e(
                    'El enlace de recuperación no es válido, ha caducado o ya fue utilizado.',
                    'dsm-clientes'
                );
                ?>
            </div>
        <?php endif; ?>

        <?php if ($validTokenFormat) : ?>
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
                    value="dsm_customer_reset_password"
                >

                <input
                    type="hidden"
                    name="token"
                    value="<?php echo esc_attr($token); ?>"
                >

                <?php
                wp_nonce_field(
                    'dsm_customer_reset_password',
                    'dsm_reset_password_nonce'
                );
                ?>

                <div class="dsm-form__field">
                    <label
                        class="dsm-form__label"
                        for="dsm-reset-password"
                    >
                        <?php
                        esc_html_e(
                            'Contraseña nueva',
                            'dsm-clientes'
                        );
                        ?>
                    </label>

                    <input
                        id="dsm-reset-password"
                        class="dsm-form__input"
                        name="password"
                        type="password"
                        autocomplete="new-password"
                        minlength="12"
                        required
                    >
                </div>

                <div class="dsm-form__field">
                    <label
                        class="dsm-form__label"
                        for="dsm-reset-password-confirmation"
                    >
                        <?php
                        esc_html_e(
                            'Repetir contraseña nueva',
                            'dsm-clientes'
                        );
                        ?>
                    </label>

                    <input
                        id="dsm-reset-password-confirmation"
                        class="dsm-form__input"
                        name="password_confirmation"
                        type="password"
                        autocomplete="new-password"
                        minlength="12"
                        required
                    >
                </div>

                <button
                    class="dsm-button dsm-button--primary dsm-button--block"
                    type="submit"
                >
                    <?php
                    esc_html_e(
                        'Guardar contraseña nueva',
                        'dsm-clientes'
                    );
                    ?>
                </button>
            </form>
        <?php endif; ?>

        <footer class="dsm-auth__footer">
            <p>
                <a href="<?php echo esc_url(
                    home_url('/recuperar-contrasena/')
                ); ?>">
                    <?php
                    esc_html_e(
                        'Solicitar un enlace nuevo',
                        'dsm-clientes'
                    );
                    ?>
                </a>
            </p>
        </footer>

    </div>
</section>
