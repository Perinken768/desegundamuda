<?php

declare(strict_types=1);

use DSM\Mail\Admin\MailTestController;
use DSM\Mail\Config\MailSettings;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * @var MailSettings $settings
 * @var bool $hasStoredPassword
 */

$status = isset($_GET['dsm_mail_status'])
    ? sanitize_key(
        wp_unslash($_GET['dsm_mail_status'])
    )
    : '';

$testError = MailTestController::getLastError();
?>

<div class="wrap">
    <h1>
        <?php esc_html_e('DSM Mail', 'dsm-mail'); ?>
    </h1>

    <p>
        <?php
        esc_html_e(
            'Configura el servidor SMTP utilizado por todos los plugins de DeSegundaMuda.',
            'dsm-mail'
        );
        ?>
    </p>

    <?php if ($status === 'saved') : ?>
        <div class="notice notice-success is-dismissible">
            <p>
                <?php
                esc_html_e(
                    'La configuración se ha guardado correctamente.',
                    'dsm-mail'
                );
                ?>
            </p>
        </div>

    <?php elseif ($status === 'error') : ?>
        <div class="notice notice-error is-dismissible">
            <p>
                <?php
                esc_html_e(
                    'No se pudo guardar la configuración.',
                    'dsm-mail'
                );
                ?>
            </p>
        </div>

    <?php elseif ($status === 'test_sent') : ?>
        <div class="notice notice-success is-dismissible">
            <p>
                <?php
                esc_html_e(
                    'El correo de prueba se ha enviado correctamente.',
                    'dsm-mail'
                );
                ?>
            </p>
        </div>

    <?php elseif ($status === 'test_error') : ?>
        <div class="notice notice-error is-dismissible">
            <p>
                <strong>
                    <?php
                    esc_html_e(
                        'No se pudo enviar el correo de prueba.',
                        'dsm-mail'
                    );
                    ?>
                </strong>
            </p>

            <?php if ($testError !== '') : ?>
                <p>
                    <?php echo esc_html($testError); ?>
                </p>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <form
        method="post"
        action="<?php echo esc_url(
            admin_url('admin-post.php')
        ); ?>"
    >
        <input
            type="hidden"
            name="action"
            value="dsm_mail_save_settings"
        >

        <?php
        wp_nonce_field(
            'dsm_mail_save_settings',
            'dsm_mail_nonce'
        );
        ?>

        <table class="form-table" role="presentation">
            <tr>
                <th scope="row">
                    <?php
                    esc_html_e(
                        'Activar SMTP',
                        'dsm-mail'
                    );
                    ?>
                </th>

                <td>
                    <label>
                        <input
                            type="checkbox"
                            name="enabled"
                            value="1"
                            <?php checked(
                                $settings->isEnabled()
                            ); ?>
                        >

                        <?php
                        esc_html_e(
                            'Utilizar SMTP para los correos de la plataforma',
                            'dsm-mail'
                        );
                        ?>
                    </label>
                </td>
            </tr>

            <tr>
                <th scope="row">
                    <label for="dsm-mail-host">
                        <?php
                        esc_html_e(
                            'Servidor SMTP',
                            'dsm-mail'
                        );
                        ?>
                    </label>
                </th>

                <td>
                    <input
                        class="regular-text"
                        id="dsm-mail-host"
                        name="host"
                        type="text"
                        value="<?php echo esc_attr(
                            $settings->getHost()
                        ); ?>"
                        placeholder="smtp.gmail.com"
                        autocomplete="off"
                    >
                </td>
            </tr>

            <tr>
                <th scope="row">
                    <label for="dsm-mail-port">
                        <?php
                        esc_html_e(
                            'Puerto',
                            'dsm-mail'
                        );
                        ?>
                    </label>
                </th>

                <td>
                    <input
                        class="small-text"
                        id="dsm-mail-port"
                        name="port"
                        type="number"
                        min="1"
                        max="65535"
                        value="<?php echo esc_attr(
                            (string) $settings->getPort()
                        ); ?>"
                    >
                </td>
            </tr>

            <tr>
                <th scope="row">
                    <?php
                    esc_html_e(
                        'Cifrado',
                        'dsm-mail'
                    );
                    ?>
                </th>

                <td>
                    <select name="encryption">
                        <option
                            value="none"
                            <?php selected(
                                $settings->getEncryption(),
                                'none'
                            ); ?>
                        >
                            <?php
                            esc_html_e(
                                'Ninguno',
                                'dsm-mail'
                            );
                            ?>
                        </option>

                        <option
                            value="tls"
                            <?php selected(
                                $settings->getEncryption(),
                                'tls'
                            ); ?>
                        >
                            TLS
                        </option>

                        <option
                            value="ssl"
                            <?php selected(
                                $settings->getEncryption(),
                                'ssl'
                            ); ?>
                        >
                            SSL
                        </option>
                    </select>
                </td>
            </tr>

            <tr>
                <th scope="row">
                    <?php
                    esc_html_e(
                        'Autenticación',
                        'dsm-mail'
                    );
                    ?>
                </th>

                <td>
                    <label>
                        <input
                            type="checkbox"
                            name="authentication_enabled"
                            value="1"
                            <?php checked(
                                $settings
                                    ->isAuthenticationEnabled()
                            ); ?>
                        >

                        <?php
                        esc_html_e(
                            'El servidor requiere autenticación',
                            'dsm-mail'
                        );
                        ?>
                    </label>
                </td>
            </tr>

            <tr>
                <th scope="row">
                    <label for="dsm-mail-username">
                        <?php
                        esc_html_e(
                            'Usuario SMTP',
                            'dsm-mail'
                        );
                        ?>
                    </label>
                </th>

                <td>
                    <input
                        class="regular-text"
                        id="dsm-mail-username"
                        name="username"
                        type="text"
                        autocomplete="off"
                        value="<?php echo esc_attr(
                            $settings->getUsername()
                        ); ?>"
                    >
                </td>
            </tr>

            <tr>
                <th scope="row">
                    <label for="dsm-mail-password">
                        <?php
                        esc_html_e(
                            'Contraseña SMTP',
                            'dsm-mail'
                        );
                        ?>
                    </label>
                </th>

                <td>
                    <input
                        class="regular-text"
                        id="dsm-mail-password"
                        name="password"
                        type="password"
                        autocomplete="new-password"
                        value=""
                    >

                    <p class="description">
                        <?php if ($hasStoredPassword) : ?>
                            <?php
                            esc_html_e(
                                'Hay una contraseña guardada. Déjalo vacío para conservarla.',
                                'dsm-mail'
                            );
                            ?>
                        <?php else : ?>
                            <?php
                            esc_html_e(
                                'Introduce la contraseña o contraseña de aplicación del proveedor.',
                                'dsm-mail'
                            );
                            ?>
                        <?php endif; ?>
                    </p>
                </td>
            </tr>

            <tr>
                <th scope="row">
                    <label for="dsm-mail-from-email">
                        <?php
                        esc_html_e(
                            'Correo remitente',
                            'dsm-mail'
                        );
                        ?>
                    </label>
                </th>

                <td>
                    <input
                        class="regular-text"
                        id="dsm-mail-from-email"
                        name="from_email"
                        type="email"
                        value="<?php echo esc_attr(
                            $settings->getFromEmail()
                        ); ?>"
                    >
                </td>
            </tr>

            <tr>
                <th scope="row">
                    <label for="dsm-mail-from-name">
                        <?php
                        esc_html_e(
                            'Nombre remitente',
                            'dsm-mail'
                        );
                        ?>
                    </label>
                </th>

                <td>
                    <input
                        class="regular-text"
                        id="dsm-mail-from-name"
                        name="from_name"
                        type="text"
                        value="<?php echo esc_attr(
                            $settings->getFromName()
                        ); ?>"
                    >
                </td>
            </tr>
        </table>

        <?php
        submit_button(
            __('Guardar configuración', 'dsm-mail')
        );
        ?>
    </form>

    <hr>

    <h2>
        <?php
        esc_html_e(
            'Enviar correo de prueba',
            'dsm-mail'
        );
        ?>
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
            value="dsm_mail_send_test"
        >

        <?php
        wp_nonce_field(
            'dsm_mail_send_test',
            'dsm_mail_test_nonce'
        );
        ?>

        <input
            class="regular-text"
            name="test_email"
            type="email"
            placeholder="destinatario@ejemplo.com"
            required
        >

        <?php
        submit_button(
            __('Enviar prueba', 'dsm-mail'),
            'secondary',
            'submit',
            false
        );
        ?>
    </form>
</div>