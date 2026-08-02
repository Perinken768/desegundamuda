<?php

declare(strict_types=1);

use DSM\Clientes\Customer\Customer;
use DSM\Clientes\Profile\CustomerProfile;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * @var Customer $customer
 * @var CustomerProfile|null $profile
 */

$verificationStatus = isset($_GET['verification_status'])
    ? sanitize_key(
        wp_unslash($_GET['verification_status'])
    )
    : '';

$accountError = isset($_GET['account_error'])
    ? sanitize_key(
        wp_unslash($_GET['account_error'])
    )
    : '';

$emailVerified = $customer->getEmailVerifiedAt() !== null;
?>

<section class="dsm-account">
    <div class="dsm-container">

        <header class="dsm-account__header">
            <h1 class="dsm-account__title">
                <?php
                esc_html_e(
                    'Mi cuenta',
                    'dsm-clientes'
                );
                ?>
            </h1>

            <p class="dsm-account__description">
                <?php
                printf(
                    esc_html__(
                        'Has iniciado sesión como %s.',
                        'dsm-clientes'
                    ),
                    esc_html($customer->getEmail())
                );
                ?>
            </p>
        </header>

        <?php if ($verificationStatus === 'resent') : ?>
            <div class="dsm-alert dsm-alert--success">
                <?php
                esc_html_e(
                    'Hemos enviado un nuevo enlace de verificación a tu correo electrónico.',
                    'dsm-clientes'
                );
                ?>
            </div>

        <?php elseif ($verificationStatus === 'resend_error') : ?>
            <div class="dsm-alert dsm-alert--error">
                <?php
                esc_html_e(
                    'No se pudo enviar el correo de verificación. Inténtalo de nuevo más tarde.',
                    'dsm-clientes'
                );
                ?>
            </div>

        <?php elseif ($verificationStatus === 'already_verified') : ?>
            <div class="dsm-alert dsm-alert--success">
                <?php
                esc_html_e(
                    'Tu correo electrónico ya está verificado.',
                    'dsm-clientes'
                );
                ?>
            </div>
        <?php endif; ?>

        <?php if ($accountError === 'deactivation_failed') : ?>
            <div class="dsm-alert dsm-alert--error">
                <?php
                esc_html_e(
                    'No se pudo cerrar temporalmente la cuenta. Comprueba la contraseña e inténtalo nuevamente.',
                    'dsm-clientes'
                );
                ?>
            </div>
        <?php endif; ?>

        <?php if (!$emailVerified) : ?>
            <aside class="dsm-alert dsm-alert--warning">
                <div class="dsm-alert__content">
                    <div>
                        <strong>
                            <?php
                            esc_html_e(
                                'Tu correo electrónico está pendiente de verificación.',
                                'dsm-clientes'
                            );
                            ?>
                        </strong>

                        <p>
                            <?php
                            printf(
                                esc_html__(
                                    'Revisa la bandeja de entrada de %s y pulsa el enlace que te hemos enviado.',
                                    'dsm-clientes'
                                ),
                                esc_html($customer->getEmail())
                            );
                            ?>
                        </p>
                    </div>

                    <form
                        method="post"
                        action="<?php echo esc_url(
                            admin_url('admin-post.php')
                        ); ?>"
                    >
                        <input
                            type="hidden"
                            name="action"
                            value="dsm_customer_resend_verification"
                        >

                        <?php
                        wp_nonce_field(
                            'dsm_customer_resend_verification',
                            'dsm_resend_verification_nonce'
                        );
                        ?>

                        <button
                            class="dsm-button dsm-button--secondary"
                            type="submit"
                        >
                            <?php
                            esc_html_e(
                                'Reenviar correo',
                                'dsm-clientes'
                            );
                            ?>
                        </button>
                    </form>
                </div>
            </aside>
        <?php endif; ?>

        <div class="dsm-account__grid">

            <article class="dsm-card">
                <h2 class="dsm-card__title">
                    <?php
                    esc_html_e(
                        'Perfil',
                        'dsm-clientes'
                    );
                    ?>
                </h2>

                <dl class="dsm-definition-list">
                    <div>
                        <dt>
                            <?php
                            esc_html_e(
                                'Nombre visible',
                                'dsm-clientes'
                            );
                            ?>
                        </dt>

                        <dd>
                            <?php
                            echo esc_html(
                                $profile?->getDisplayName()
                                    ?? __(
                                        'Sin nombre',
                                        'dsm-clientes'
                                    )
                            );
                            ?>
                        </dd>
                    </div>

                    <div>
                        <dt>
                            <?php
                            esc_html_e(
                                'Correo electrónico',
                                'dsm-clientes'
                            );
                            ?>
                        </dt>

                        <dd>
                            <?php
                            echo esc_html(
                                $customer->getEmail()
                            );
                            ?>
                        </dd>
                    </div>

                    <div>
                        <dt>
                            <?php
                            esc_html_e(
                                'Verificación del correo',
                                'dsm-clientes'
                            );
                            ?>
                        </dt>

                        <dd>
                            <?php if ($emailVerified) : ?>
                                <span class="dsm-status dsm-status--success">
                                    <?php
                                    esc_html_e(
                                        'Verificado',
                                        'dsm-clientes'
                                    );
                                    ?>
                                </span>
                            <?php else : ?>
                                <span class="dsm-status dsm-status--warning">
                                    <?php
                                    esc_html_e(
                                        'Pendiente',
                                        'dsm-clientes'
                                    );
                                    ?>
                                </span>
                            <?php endif; ?>
                        </dd>
                    </div>

                    <div>
                        <dt>
                            <?php
                            esc_html_e(
                                'Teléfono',
                                'dsm-clientes'
                            );
                            ?>
                        </dt>

                        <dd>
                            <?php
                            echo esc_html(
                                $profile?->getPhone()
                                    ?? __(
                                        'No indicado',
                                        'dsm-clientes'
                                    )
                            );
                            ?>
                        </dd>
                    </div>

                    <div>
                        <dt>
                            <?php
                            esc_html_e(
                                'WhatsApp',
                                'dsm-clientes'
                            );
                            ?>
                        </dt>

                        <dd>
                            <?php
                            echo esc_html(
                                $profile?->getWhatsappPhone()
                                    ?? __(
                                        'No indicado',
                                        'dsm-clientes'
                                    )
                            );
                            ?>
                        </dd>
                    </div>

                    <div>
                        <dt>
                            <?php
                            esc_html_e(
                                'Estado de la cuenta',
                                'dsm-clientes'
                            );
                            ?>
                        </dt>

                        <dd>
                            <?php
                            echo esc_html(
                                $customer->getStatus()
                            );
                            ?>
                        </dd>
                    </div>
                </dl>
            </article>

            <article class="dsm-card">
                <h2 class="dsm-card__title">
                    <?php
                    esc_html_e(
                        'Acciones',
                        'dsm-clientes'
                    );
                    ?>
                </h2>

                <div class="dsm-card__actions">
                    <a
                        class="dsm-button dsm-button--secondary"
                        href="<?php echo esc_url(
                            home_url('/editar-perfil/')
                        ); ?>"
                    >
                        <?php
                        esc_html_e(
                            'Editar perfil',
                            'dsm-clientes'
                        );
                        ?>
                    </a>

                    <form
                        method="post"
                        action="<?php echo esc_url(
                            admin_url('admin-post.php')
                        ); ?>"
                    >
                        <input
                            type="hidden"
                            name="action"
                            value="dsm_customer_logout"
                        >

                        <?php
                        wp_nonce_field(
                            'dsm_customer_logout',
                            'dsm_logout_nonce'
                        );
                        ?>

                        <button
                            class="dsm-button dsm-button--primary"
                            type="submit"
                        >
                            <?php
                            esc_html_e(
                                'Cerrar sesión',
                                'dsm-clientes'
                            );
                            ?>
                        </button>
                    </form>
                </div>
            </article>

        </div>

        <section class="dsm-account__danger-zone">
            <article class="dsm-card dsm-card--danger">
                <h2 class="dsm-card__title">
                    <?php
                    esc_html_e(
                        'Zona de seguridad',
                        'dsm-clientes'
                    );
                    ?>
                </h2>

                <p>
                    <?php
                    esc_html_e(
                        'Puedes cerrar temporalmente tu cuenta. Tus datos no se eliminarán, pero no podrás iniciar sesión hasta reactivarla.',
                        'dsm-clientes'
                    );
                    ?>
                </p>

                <details class="dsm-danger-action">
                    <summary class="dsm-button dsm-button--danger-outline">
                        <?php
                        esc_html_e(
                            'Cerrar temporalmente mi cuenta',
                            'dsm-clientes'
                        );
                        ?>
                    </summary>

                    <div class="dsm-danger-action__content">
                        <p>
                            <strong>
                                <?php
                                esc_html_e(
                                    'Confirma tu contraseña para continuar.',
                                    'dsm-clientes'
                                );
                                ?>
                            </strong>
                        </p>

                        <form
                            class="dsm-form"
                            method="post"
                            action="<?php echo esc_url(
                                admin_url('admin-post.php')
                            ); ?>"
                            onsubmit="return confirm(
                                '¿Seguro que quieres cerrar temporalmente tu cuenta?'
                            );"
                        >
                            <input
                                type="hidden"
                                name="action"
                                value="dsm_customer_deactivate_account"
                            >

                            <?php
                            wp_nonce_field(
                                'dsm_customer_deactivate_account',
                                'dsm_deactivate_account_nonce'
                            );
                            ?>

                            <div class="dsm-form__field">
                                <label
                                    class="dsm-form__label"
                                    for="dsm-deactivate-password"
                                >
                                    <?php
                                    esc_html_e(
                                        'Contraseña actual',
                                        'dsm-clientes'
                                    );
                                    ?>
                                </label>

                                <input
                                    id="dsm-deactivate-password"
                                    class="dsm-form__input"
                                    name="password"
                                    type="password"
                                    autocomplete="current-password"
                                    required
                                >
                            </div>

                            <button
                                class="dsm-button dsm-button--danger"
                                type="submit"
                            >
                                <?php
                                esc_html_e(
                                    'Confirmar cierre temporal',
                                    'dsm-clientes'
                                );
                                ?>
                            </button>
                        </form>
                    </div>
                </details>
            </article>
        </section>

    </div>
</section>