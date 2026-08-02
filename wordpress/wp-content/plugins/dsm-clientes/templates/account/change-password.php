<?php

declare(strict_types=1);

use DSM\Clientes\Customer\Customer;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * @var Customer $customer
 * @var bool $isImpersonating
 */

$accountError = isset($_GET['account_error'])
    ? sanitize_key(
        wp_unslash($_GET['account_error'])
    )
    : '';
?>

<section class="dsm-account">
    <div class="dsm-container">

        <header class="dsm-account__header">
            <h1 class="dsm-account__title">
                <?php
                esc_html_e(
                    'Cambiar contraseña',
                    'dsm-clientes'
                );
                ?>
            </h1>

            <p class="dsm-account__description">
                <?php
                printf(
                    esc_html__(
                        'Cuenta: %s',
                        'dsm-clientes'
                    ),
                    esc_html($customer->getEmail())
                );
                ?>
            </p>
        </header>

        <?php if (
            $accountError === 'password_change_failed'
        ) : ?>
            <div
                class="dsm-alert dsm-alert--error"
                role="alert"
            >
                <?php
                esc_html_e(
                    'No se pudo cambiar la contraseña. Comprueba la contraseña actual, que las nuevas coincidan y que tengan al menos 12 caracteres.',
                    'dsm-clientes'
                );
                ?>
            </div>
        <?php endif; ?>

        <article class="dsm-card">
            <?php if ($isImpersonating) : ?>
                <div
                    class="dsm-alert dsm-alert--warning"
                    role="alert"
                >
                    <?php
                    esc_html_e(
                        'No puedes cambiar la contraseña durante una sesión administrativa temporal.',
                        'dsm-clientes'
                    );
                    ?>
                </div>
            <?php else : ?>
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
                        value="dsm_customer_change_password"
                    >

                    <?php
                    wp_nonce_field(
                        'dsm_customer_change_password',
                        'dsm_change_password_nonce'
                    );
                    ?>

                    <div class="dsm-form__field">
                        <label
                            class="dsm-form__label"
                            for="dsm-current-password"
                        >
                            <?php
                            esc_html_e(
                                'Contraseña actual',
                                'dsm-clientes'
                            );
                            ?>
                        </label>

                        <input
                            id="dsm-current-password"
                            class="dsm-form__input"
                            name="current_password"
                            type="password"
                            autocomplete="current-password"
                            required
                        >
                    </div>

                    <div class="dsm-form__field">
                        <label
                            class="dsm-form__label"
                            for="dsm-new-password"
                        >
                            <?php
                            esc_html_e(
                                'Contraseña nueva',
                                'dsm-clientes'
                            );
                            ?>
                        </label>

                        <input
                            id="dsm-new-password"
                            class="dsm-form__input"
                            name="new_password"
                            type="password"
                            autocomplete="new-password"
                            minlength="12"
                            required
                        >
                    </div>

                    <div class="dsm-form__field">
                        <label
                            class="dsm-form__label"
                            for="dsm-new-password-confirmation"
                        >
                            <?php
                            esc_html_e(
                                'Repetir contraseña nueva',
                                'dsm-clientes'
                            );
                            ?>
                        </label>

                        <input
                            id="dsm-new-password-confirmation"
                            class="dsm-form__input"
                            name="new_password_confirmation"
                            type="password"
                            autocomplete="new-password"
                            minlength="12"
                            required
                        >
                    </div>

                    <button
                        class="dsm-button dsm-button--primary"
                        type="submit"
                    >
                        <?php
                        esc_html_e(
                            'Cambiar contraseña',
                            'dsm-clientes'
                        );
                        ?>
                    </button>
                </form>
            <?php endif; ?>

            <p>
                <a href="<?php echo esc_url(
                    home_url('/mi-cuenta/')
                ); ?>">
                    <?php
                    esc_html_e(
                        'Volver a Mi cuenta',
                        'dsm-clientes'
                    );
                    ?>
                </a>
            </p>
        </article>

    </div>
</section>
