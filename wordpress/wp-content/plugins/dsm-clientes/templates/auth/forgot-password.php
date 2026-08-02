<?php

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

$resetStatus = isset($_GET['reset_status'])
    ? sanitize_key(
        wp_unslash($_GET['reset_status'])
    )
    : '';
?>

<section class="dsm-auth">
    <div class="dsm-auth__card">

        <header class="dsm-auth__header">
            <h1 class="dsm-auth__title">
                <?php
                esc_html_e(
                    'Recuperar contraseña',
                    'dsm-clientes'
                );
                ?>
            </h1>

            <p class="dsm-auth__description">
                <?php
                esc_html_e(
                    'Introduce tu correo electrónico y te enviaremos un enlace para establecer una contraseña nueva.',
                    'dsm-clientes'
                );
                ?>
            </p>
        </header>

        <?php if ($resetStatus === 'sent') : ?>
            <div
                class="dsm-alert dsm-alert--success"
                role="status"
            >
                <?php
                esc_html_e(
                    'Si existe una cuenta disponible asociada al correo, recibirás un enlace de recuperación.',
                    'dsm-clientes'
                );
                ?>
            </div>

        <?php elseif ($resetStatus === 'error') : ?>
            <div
                class="dsm-alert dsm-alert--error"
                role="alert"
            >
                <?php
                esc_html_e(
                    'No se pudo procesar la solicitud. Inténtalo de nuevo más tarde.',
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
                value="dsm_customer_request_password_reset"
            >

            <?php
            wp_nonce_field(
                'dsm_customer_request_password_reset',
                'dsm_password_reset_nonce'
            );
            ?>

            <div class="dsm-form__field">
                <label
                    class="dsm-form__label"
                    for="dsm-forgot-email"
                >
                    <?php
                    esc_html_e(
                        'Correo electrónico',
                        'dsm-clientes'
                    );
                    ?>
                </label>

                <input
                    id="dsm-forgot-email"
                    class="dsm-form__input"
                    name="email"
                    type="email"
                    autocomplete="email"
                    required
                >
            </div>

            <button
                class="dsm-button dsm-button--primary dsm-button--block"
                type="submit"
            >
                <?php
                esc_html_e(
                    'Enviar enlace de recuperación',
                    'dsm-clientes'
                );
                ?>
            </button>
        </form>

        <footer class="dsm-auth__footer">
            <p>
                <a href="<?php echo esc_url(
                    home_url('/iniciar-sesion/')
                ); ?>">
                    <?php
                    esc_html_e(
                        'Volver a iniciar sesión',
                        'dsm-clientes'
                    );
                    ?>
                </a>
            </p>
        </footer>

    </div>
</section>