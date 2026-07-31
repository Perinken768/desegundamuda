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
?>

<section class="dsm-account">
    <div class="dsm-container">

        <header class="dsm-account__header">
            <h1 class="dsm-account__title">
                <?php esc_html_e(
                    'Mi cuenta',
                    'dsm-clientes'
                ); ?>
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

        <div class="dsm-account__grid">

            <article class="dsm-card">
                <h2 class="dsm-card__title">
                    <?php esc_html_e(
                        'Perfil',
                        'dsm-clientes'
                    ); ?>
                </h2>

                <dl class="dsm-definition-list">
                    <div>
                        <dt>
                            <?php esc_html_e(
                                'Nombre visible',
                                'dsm-clientes'
                            ); ?>
                        </dt>

                        <dd>
                            <?php echo esc_html(
                                $profile?->getDisplayName()
                                    ?? 'Sin nombre'
                            ); ?>
                        </dd>
                    </div>

                    <div>
                        <dt>
                            <?php esc_html_e(
                                'Correo electrónico',
                                'dsm-clientes'
                            ); ?>
                        </dt>

                        <dd>
                            <?php echo esc_html(
                                $customer->getEmail()
                            ); ?>
                        </dd>
                    </div>

                    <div>
                        <dt>
                            <?php esc_html_e(
                                'Estado',
                                'dsm-clientes'
                            ); ?>
                        </dt>

                        <dd>
                            <?php echo esc_html(
                                $customer->getStatus()
                            ); ?>
                        </dd>
                    </div>
                </dl>
            </article>

            <article class="dsm-card">
                <h2 class="dsm-card__title">
                    <?php esc_html_e(
                        'Acciones',
                        'dsm-clientes'
                    ); ?>
                </h2>

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
                        <?php esc_html_e(
                            'Cerrar sesión',
                            'dsm-clientes'
                        ); ?>
                    </button>
                </form>
            </article>

        </div>

    </div>
</section>