<?php

declare(strict_types=1);

use DSM\Pagos\Admin\PaymentProvidersPage;
use DSM\Pagos\Provider\PaymentProvider;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * @var array<string, PaymentProvider> $providers
 * @var array<string, array<string, mixed>> $settings
 * @var string $notice
 * @var string $error
 */

?>

<div class="wrap">

    <h1>
        <?php
        esc_html_e(
            'DSM Pagos',
            'dsm-pagos'
        );
        ?>
    </h1>

    <p class="description">
        <?php
        esc_html_e(
            'Configura los métodos de pago disponibles para los clientes de DeSegundaMuda.',
            'dsm-pagos'
        );
        ?>
    </p>

    <hr class="wp-header-end">

    <?php if ($notice === 'saved') : ?>
        <div class="notice notice-success is-dismissible">
            <p>
                <?php
                esc_html_e(
                    'La configuración de pagos se guardó correctamente.',
                    'dsm-pagos'
                );
                ?>
            </p>
        </div>
    <?php endif; ?>

    <?php if ($error !== '') : ?>
        <div class="notice notice-error">
            <p>
                <?php echo esc_html($error); ?>
            </p>
        </div>
    <?php endif; ?>

    <form
        method="post"
        action="<?php
        echo esc_url(
            admin_url(
                'admin-post.php'
            )
        );
        ?>"
    >

        <input
            type="hidden"
            name="action"
            value="<?php
            echo esc_attr(
                PaymentProvidersPage::
                    getSaveAction()
            );
            ?>"
        >

        <?php
        wp_nonce_field(
            PaymentProvidersPage::
                getNonceAction(),
            PaymentProvidersPage::
                getNonceName()
        );
        ?>

        <h2>Stripe</h2>

        <table class="form-table" role="presentation">

            <tr>
                <th scope="row">
                    Habilitado
                </th>
                <td>
                    <label>
                        <input
                            type="checkbox"
                            name="stripe_enabled"
                            value="1"
                            <?php
                            checked(
                                $settings['stripe']['enabled']
                            );
                            ?>
                        >
                        Permitir pagos mediante Stripe
                    </label>
                </td>
            </tr>

            <tr>
                <th scope="row">
                    Entorno
                </th>
                <td>
                    <select name="stripe_mode">
                        <option
                            value="test"
                            <?php
                            selected(
                                $settings['stripe']['mode'],
                                'test'
                            );
                            ?>
                        >
                            Pruebas
                        </option>

                        <option
                            value="live"
                            <?php
                            selected(
                                $settings['stripe']['mode'],
                                'live'
                            );
                            ?>
                        >
                            Producción
                        </option>
                    </select>
                </td>
            </tr>

            <tr>
                <th scope="row">
                    Secret key
                </th>
                <td>
                    <input
                        type="password"
                        name="stripe_secret_key"
                        value=""
                        class="regular-text"
                        autocomplete="new-password"
                    >

                    <?php if (
                        $settings['stripe']['has_secret_key']
                    ) : ?>
                        <p class="description">
                            Hay una Secret Key guardada.
                            Déjalo vacío para conservarla.
                        </p>
                    <?php endif; ?>
                </td>
            </tr>

            <tr>
                <th scope="row">
                    Webhook secret
                </th>
                <td>
                    <input
                        type="password"
                        name="stripe_webhook_secret"
                        value=""
                        class="regular-text"
                        autocomplete="new-password"
                    >

                    <?php if (
                        $settings['stripe']['has_webhook_secret']
                    ) : ?>
                        <p class="description">
                            Hay un Webhook Secret guardado.
                            Déjalo vacío para conservarlo.
                        </p>
                    <?php endif; ?>
                </td>
            </tr>

            <tr>
                <th scope="row">
                    Estado
                </th>
                <td>
                    <?php if (
                        $providers['stripe']->isAvailable()
                    ) : ?>
                        <strong>Disponible</strong>
                    <?php elseif (
                        $providers['stripe']->isConfigured()
                    ) : ?>
                        Configurado, pero deshabilitado.
                    <?php else : ?>
                        Falta configuración.
                    <?php endif; ?>
                </td>
            </tr>

        </table>

        <hr>

        <h2>Redsys</h2>

        <table class="form-table" role="presentation">

            <tr>
                <th scope="row">
                    Habilitado
                </th>
                <td>
                    <label>
                        <input
                            type="checkbox"
                            name="redsys_enabled"
                            value="1"
                            <?php
                            checked(
                                $settings['redsys']['enabled']
                            );
                            ?>
                        >
                        Permitir pagos mediante Redsys
                    </label>
                </td>
            </tr>

            <tr>
                <th scope="row">
                    Entorno
                </th>
                <td>
                    <select name="redsys_mode">
                        <option
                            value="test"
                            <?php
                            selected(
                                $settings['redsys']['mode'],
                                'test'
                            );
                            ?>
                        >
                            Pruebas
                        </option>

                        <option
                            value="live"
                            <?php
                            selected(
                                $settings['redsys']['mode'],
                                'live'
                            );
                            ?>
                        >
                            Producción
                        </option>
                    </select>
                </td>
            </tr>

            <tr>
                <th scope="row">
                    Código de comercio
                </th>
                <td>
                    <input
                        type="text"
                        name="redsys_merchant_code"
                        value="<?php
                        echo esc_attr(
                            $settings['redsys']['merchant_code']
                        );
                        ?>"
                        class="regular-text"
                    >
                </td>
            </tr>

            <tr>
                <th scope="row">
                    Terminal
                </th>
                <td>
                    <input
                        type="text"
                        name="redsys_terminal"
                        value="<?php
                        echo esc_attr(
                            $settings['redsys']['terminal']
                        );
                        ?>"
                        class="small-text"
                    >
                </td>
            </tr>

            <tr>
                <th scope="row">
                    Clave de firma
                </th>
                <td>
                    <input
                        type="password"
                        name="redsys_secret_key"
                        value=""
                        class="regular-text"
                        autocomplete="new-password"
                    >

                    <?php if (
                        $settings['redsys']['has_secret_key']
                    ) : ?>
                        <p class="description">
                            Hay una clave de firma guardada.
                            Déjalo vacío para conservarla.
                        </p>
                    <?php endif; ?>
                </td>
            </tr>

            <tr>
                <th scope="row">
                    Estado
                </th>
                <td>
                    <?php if (
                        $providers['redsys']->isAvailable()
                    ) : ?>
                        <strong>Disponible</strong>
                    <?php elseif (
                        $providers['redsys']->isConfigured()
                    ) : ?>
                        Configurado, pero deshabilitado.
                    <?php else : ?>
                        Falta configuración.
                    <?php endif; ?>
                </td>
            </tr>

        </table>

        <hr>

        <h2>PayPal</h2>

        <table class="form-table" role="presentation">

            <tr>
                <th scope="row">
                    Habilitado
                </th>
                <td>
                    <label>
                        <input
                            type="checkbox"
                            name="paypal_enabled"
                            value="1"
                            <?php
                            checked(
                                $settings['paypal']['enabled']
                            );
                            ?>
                        >
                        Permitir pagos mediante PayPal
                    </label>
                </td>
            </tr>

            <tr>
                <th scope="row">
                    Entorno
                </th>
                <td>
                    <select name="paypal_mode">
                        <option
                            value="sandbox"
                            <?php
                            selected(
                                $settings['paypal']['mode'],
                                'sandbox'
                            );
                            ?>
                        >
                            Sandbox
                        </option>

                        <option
                            value="live"
                            <?php
                            selected(
                                $settings['paypal']['mode'],
                                'live'
                            );
                            ?>
                        >
                            Producción
                        </option>
                    </select>
                </td>
            </tr>

            <tr>
                <th scope="row">
                    Client ID
                </th>
                <td>
                    <input
                        type="text"
                        name="paypal_client_id"
                        value="<?php
                        echo esc_attr(
                            $settings['paypal']['client_id']
                        );
                        ?>"
                        class="regular-text"
                    >
                </td>
            </tr>

            <tr>
                <th scope="row">
                    Client Secret
                </th>
                <td>
                    <input
                        type="password"
                        name="paypal_client_secret"
                        value=""
                        class="regular-text"
                        autocomplete="new-password"
                    >

                    <?php if (
                        $settings['paypal']['has_client_secret']
                    ) : ?>
                        <p class="description">
                            Hay un Client Secret guardado.
                            Déjalo vacío para conservarlo.
                        </p>
                    <?php endif; ?>
                </td>
            </tr>

            <tr>
                <th scope="row">
                    Webhook ID
                </th>
                <td>
                    <input
                        type="text"
                        name="paypal_webhook_id"
                        value="<?php
                        echo esc_attr(
                            $settings['paypal']['webhook_id']
                        );
                        ?>"
                        class="regular-text"
                    >
                </td>
            </tr>

            <tr>
                <th scope="row">
                    Estado
                </th>
                <td>
                    <?php if (
                        $providers['paypal']->isAvailable()
                    ) : ?>
                        <strong>Disponible</strong>
                    <?php elseif (
                        $providers['paypal']->isConfigured()
                    ) : ?>
                        Configurado, pero deshabilitado.
                    <?php else : ?>
                        Falta configuración.
                    <?php endif; ?>
                </td>
            </tr>

        </table>

        <?php
        submit_button(
            __(
                'Guardar configuración',
                'dsm-pagos'
            )
        );
        ?>

    </form>

</div>