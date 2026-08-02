<?php

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Variables disponibles:
 *
 * @var bool $hasError
 */

$accountStatus = isset($_GET['account_status'])
    ? sanitize_key(
        wp_unslash($_GET['account_status'])
    )
    : '';
?>

<section class="dsm-auth">
    <div class="dsm-auth__card">

        <header class="dsm-auth__header">
            <h1 class="dsm-auth__title">
                <?php
                esc_html_e(
                    'Iniciar sesión',
                    'dsm-clientes'
                );
                ?>
            </h1>

            <p class="dsm-auth__description">
                <?php
                esc_html_e(
                    'Accede a tu cuenta de DeSegundaMuda.',
                    'dsm-clientes'
                );
                ?>
            </p>
        </header>

        <?php if (
            $accountStatus === 'deactivated'
        ) : ?>
            <div
                class="dsm-alert dsm-alert--success"
                role="status"
            >
                <?php
                esc_html_e(
                    'Tu cuenta se ha cerrado temporalmente y todas las sesiones se han cerrado.',
                    'dsm-clientes'
                );
                ?>
            </div>
        <?php endif; ?>

        <?php if (
            $accountStatus === 'reactivation_sent'
        ) : ?>
            <div
                class="dsm-alert dsm-alert--success"
                role="status"
            >
                <?php
                esc_html_e(
                    'Si existe una cuenta inactiva asociada al correo, recibirás un enlace de reactivación.',
                    'dsm-clientes'
                );
                ?>
            </div>

        <?php elseif (
            $accountStatus === 'reactivated'
        ) : ?>
            <div
                class="dsm-alert dsm-alert--success"
                role="status"
            >
                <?php
                esc_html_e(
                    'Tu cuenta se ha reactivado. Ya puedes iniciar sesión.',
                    'dsm-clientes'
                );
                ?>
            </div>

        <?php elseif (
            $accountStatus === 'reactivation_invalid'
        ) : ?>
            <div
                class="dsm-alert dsm-alert--error"
                role="alert"
            >
                <?php
                esc_html_e(
                    'El enlace de reactivación no es válido o ha caducado.',
                    'dsm-clientes'
                );
                ?>
            </div>

        <?php elseif (
            $accountStatus === 'reactivation_error'
        ) : ?>
            <div
                class="dsm-alert dsm-alert--error"
                role="alert"
            >
                <?php
                esc_html_e(
                    'No se pudo solicitar la reactivación. Inténtalo de nuevo más tarde.',
                    'dsm-clientes'
                );
                ?>
            </div>
        <?php endif; ?>

        <?php if (
            $accountStatus === 'deletion_scheduled'
        ) : ?>
            <div
                class="dsm-alert dsm-alert--success"
                role="status"
            >
                <?php
                esc_html_e(
                    'La eliminación de tu cuenta ha sido programada. Dispones de 30 días para cancelarla desde el enlace enviado por correo.',
                    'dsm-clientes'
                );
                ?>
            </div>

        <?php elseif (
            $accountStatus === 'deletion_cancelled'
        ) : ?>
            <div
                class="dsm-alert dsm-alert--success"
                role="status"
            >
                <?php
                esc_html_e(
                    'La eliminación se ha cancelado y tu cuenta vuelve a estar activa.',
                    'dsm-clientes'
                );
                ?>
            </div>

        <?php elseif (
            $accountStatus === 'deletion_invalid'
        ) : ?>
            <div
                class="dsm-alert dsm-alert--error"
                role="alert"
            >
                <?php
                esc_html_e(
                    'El enlace de eliminación no es válido o la solicitud ya no puede modificarse.',
                    'dsm-clientes'
                );
                ?>
            </div>
        <?php endif; ?>

        <?php if (
            $accountStatus === 'password_reset'
        ) : ?>
            <div
                class="dsm-alert dsm-alert--success"
                role="status"
            >
                <?php
                esc_html_e(
                    'La contraseña se ha restablecido correctamente. Ya puedes iniciar sesión.',
                    'dsm-clientes'
                );
                ?>
            </div>

        <?php elseif (
            $accountStatus === 'password_changed'
        ) : ?>
            <div
                class="dsm-alert dsm-alert--success"
                role="status"
            >
                <?php
                esc_html_e(
                    'La contraseña se ha cambiado correctamente. Inicia sesión de nuevo.',
                    'dsm-clientes'
                );
                ?>
            </div>
        <?php endif; ?>

        <?php if ($hasError) : ?>
            <div
                class="dsm-alert dsm-alert--error"
                role="alert"
            >
                <?php
                esc_html_e(
                    'El correo electrónico o la contraseña no son correctos, o la cuenta no está disponible.',
                    'dsm-clientes'
                );
                ?>
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
                value="dsm_customer_login"
            >

            <?php
            wp_nonce_field(
                'dsm_customer_login',
                'dsm_login_nonce'
            );
            ?>

            <div class="dsm-form__field">
                <label
                    class="dsm-form__label"
                    for="dsm-login-email"
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
                    id="dsm-login-email"
                    name="email"
                    type="email"
                    autocomplete="email"
                    required
                >
            </div>

            <div class="dsm-form__field">
                <label
                    class="dsm-form__label"
                    for="dsm-login-password"
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
                    id="dsm-login-password"
                    name="password"
                    type="password"
                    autocomplete="current-password"
                    required
                >
            </div>

            <button
                class="dsm-button dsm-button--primary dsm-button--block"
                type="submit"
            >
                <?php
                esc_html_e(
                    'Entrar',
                    'dsm-clientes'
                );
                ?>
            </button>
        </form>

        <p class="dsm-auth__password-help">
            <a href="<?php echo esc_url(
                home_url('/recuperar-contrasena/')
            ); ?>">
                <?php
                esc_html_e(
                    '¿Has olvidado tu contraseña?',
                    'dsm-clientes'
                );
                ?>
            </a>
        </p>

        <details class="dsm-auth__reactivation">
            <summary>
                <?php
                esc_html_e(
                    'Reactivar una cuenta cerrada',
                    'dsm-clientes'
                );
                ?>
            </summary>

            <div class="dsm-danger-action__content">
                <p>
                    <?php
                    esc_html_e(
                        'Introduce el correo de la cuenta cerrada temporalmente y te enviaremos un enlace de reactivación.',
                        'dsm-clientes'
                    );
                    ?>
                </p>

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
                        value="dsm_customer_request_reactivation"
                    >

                    <?php
                    wp_nonce_field(
                        'dsm_customer_request_reactivation',
                        'dsm_reactivation_nonce'
                    );
                    ?>

                    <div class="dsm-form__field">
                        <label
                            class="dsm-form__label"
                            for="dsm-reactivation-email"
                        >
                            <?php
                            esc_html_e(
                                'Correo electrónico',
                                'dsm-clientes'
                            );
                            ?>
                        </label>

                        <input
                            id="dsm-reactivation-email"
                            class="dsm-form__input"
                            name="email"
                            type="email"
                            autocomplete="email"
                            required
                        >
                    </div>

                    <button
                        class="dsm-button dsm-button--secondary dsm-button--block"
                        type="submit"
                    >
                        <?php
                        esc_html_e(
                            'Enviar enlace de reactivación',
                            'dsm-clientes'
                        );
                        ?>
                    </button>
                </form>
            </div>
        </details>

        <footer class="dsm-auth__footer">
            <p>
                <?php
                esc_html_e(
                    '¿Todavía no tienes cuenta?',
                    'dsm-clientes'
                );
                ?>

                <a href="<?php echo esc_url(
                    home_url('/registro/')
                ); ?>">
                    <?php
                    esc_html_e(
                        'Regístrate',
                        'dsm-clientes'
                    );
                    ?>
                </a>
            </p>
        </footer>

    </div>
</section>