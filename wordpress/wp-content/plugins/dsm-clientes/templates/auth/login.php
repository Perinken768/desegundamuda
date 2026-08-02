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

        <?php if ($accountStatus === 'deactivated') : ?>
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

        <?php if ($hasError) : ?>
            <div
                class="dsm-alert dsm-alert--error"
                role="alert"
            >
                <?php
                esc_html_e(
                    'El correo electrónico o la contraseña no son correctos.',
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