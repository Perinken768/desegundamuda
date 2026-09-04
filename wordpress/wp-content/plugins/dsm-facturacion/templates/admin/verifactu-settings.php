<?php

declare(strict_types=1);

use DSM\Facturacion\Admin\VerifactuSettingsPage;
use DSM\Facturacion\Verifactu\VerifactuSettings;

if (!defined('ABSPATH')) {
    exit;
}

$enabled =
    (int) (
        $settings['enabled']
        ?? 0
    ) === 1;

$environment =
    (string) (
        $settings['environment']
        ?? VerifactuSettings::ENVIRONMENT_TEST
    );

$certificateType =
    (string) (
        $settings['certificate_type']
        ?? VerifactuSettings::CERTIFICATE_STANDARD
    );

$queue =
    is_array(
        $health['queue']
        ?? null
    )
        ? $health['queue']
        : [];

$incidence =
    is_array(
        $health['incidence']
        ?? null
    )
        ? $health['incidence']
        : [];

$wait =
    is_array(
        $health['aeat_wait']
        ?? null
    )
        ? $health['aeat_wait']
        : [];

$chain =
    is_array(
        $health['chain']
        ?? null
    )
        ? $health['chain']
        : null;

$cron =
    is_array(
        $health['cron']
        ?? null
    )
        ? $health['cron']
        : [];

$phpStatus =
    is_array(
        $health['php']
        ?? null
    )
        ? $health['php']
        : [];

$certificateStatus =
    (string) (
        $health['certificate_status']
        ?? 'unknown'
    );

$certificateLabels = [
    'configured' =>
        'Configurado',

    'missing_path' =>
        'No configurado',

    'file_missing' =>
        'Fichero no encontrado',

    'not_readable' =>
        'Sin permisos de lectura',

    'openssl_missing' =>
        'OpenSSL no disponible',

    'invalid_p12' =>
        'PKCS#12 o contraseña no válidos',

    'expired' =>
        'Certificado caducado',

    'not_yet_valid' =>
        'Certificado todavía no válido',

    'private_key_error' =>
        'Error de clave privada',

    'key_mismatch' =>
        'La clave no corresponde al certificado',

    'invalid_certificate' =>
        'Certificado no válido',

    'invalid' =>
        'Configuración no válida',
];

$certificateLabel =
    $certificateLabels[
        $certificateStatus
    ]
    ?? $certificateStatus;

$statusClass =
    static function (
        bool $ok
    ): string {
        return $ok
            ? 'dsm-verifactu-status dsm-verifactu-status--ok'
            : 'dsm-verifactu-status dsm-verifactu-status--warning';
    };
?>

<div class="wrap dsm-billing-admin">

    <h1>VERI*FACTU</h1>

    <p class="dsm-billing-admin__intro">
        Estado, configuración y diagnóstico del sistema
        VERI*FACTU de DeSegundaMuda.
    </p>

    <?php if ($notice === 'saved') : ?>
        <div class="notice notice-success is-dismissible">
            <p>
                La configuración VERI*FACTU se ha guardado correctamente.
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

    <style>
        .dsm-verifactu-summary {
            display: grid;
            grid-template-columns: repeat(
                auto-fit,
                minmax(220px, 1fr)
            );
            gap: 16px;
            margin: 20px 0;
        }

        .dsm-verifactu-summary__item {
            padding: 18px;
            background: #fff;
            border: 1px solid #dcdcde;
            border-radius: 6px;
        }

        .dsm-verifactu-summary__label {
            display: block;
            margin-bottom: 8px;
            color: #646970;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
        }

        .dsm-verifactu-summary__value {
            font-size: 18px;
            font-weight: 600;
        }

        .dsm-verifactu-status {
            display: inline-block;
            padding: 4px 9px;
            border-radius: 999px;
            font-size: 12px;
            font-weight: 600;
        }

        .dsm-verifactu-status--ok {
            color: #005c12;
            background: #edfaef;
        }

        .dsm-verifactu-status--warning {
            color: #8a4b00;
            background: #fff4df;
        }

        .dsm-verifactu-status--error {
            color: #8a1616;
            background: #fce8e8;
        }

        .dsm-verifactu-code {
            font-family: monospace;
            word-break: break-all;
        }

        .dsm-verifactu-warning {
            padding: 14px 16px;
            margin: 16px 0;
            border-left: 4px solid #dba617;
            background: #fff8e5;
        }

        .dsm-verifactu-production {
            padding: 14px 16px;
            margin-top: 15px;
            border: 1px solid #d63638;
            background: #fff5f5;
        }
    </style>

    <div class="dsm-verifactu-summary">

        <div class="dsm-verifactu-summary__item">
            <span class="dsm-verifactu-summary__label">
                Estado local
            </span>

            <span
                class="<?php
                echo esc_attr(
                    $statusClass(
                        (bool) (
                            $health[
                                'ready_local'
                            ]
                            ?? false
                        )
                    )
                );
                ?>"
            >
                <?php
                echo !empty(
                    $health['ready_local']
                )
                    ? 'Preparado'
                    : 'Requiere revisión';
                ?>
            </span>
        </div>

        <div class="dsm-verifactu-summary__item">
            <span class="dsm-verifactu-summary__label">
                Transmisión
            </span>

            <span
                class="<?php
                echo esc_attr(
                    $statusClass(
                        $enabled
                    )
                );
                ?>"
            >
                <?php
                echo $enabled
                    ? 'Activada'
                    : 'Desactivada';
                ?>
            </span>
        </div>

        <div class="dsm-verifactu-summary__item">
            <span class="dsm-verifactu-summary__label">
                Entorno
            </span>

            <span class="dsm-verifactu-summary__value">
                <?php
                echo $environment
                    === VerifactuSettings::ENVIRONMENT_PRODUCTION
                        ? 'Producción'
                        : 'Pruebas';
                ?>
            </span>
        </div>

        <div class="dsm-verifactu-summary__item">
            <span class="dsm-verifactu-summary__label">
                Certificado
            </span>

            <span
                class="<?php
                echo esc_attr(
                    $statusClass(
                        $certificateStatus
                        === 'configured'
                    )
                );
                ?>"
            >
                <?php echo esc_html($certificateLabel); ?>
            </span>
        </div>

    </div>

    <div class="dsm-billing-admin__grid">

        <div class="dsm-billing-card">

            <h2>Diagnóstico</h2>

            <table class="widefat striped">

                <tbody>

                    <tr>
                        <td>Endpoint</td>
                        <td class="dsm-verifactu-code">
                            <?php
                            echo esc_html(
                                (string) (
                                    $health[
                                        'endpoint'
                                    ]
                                    ?? ''
                                )
                            );
                            ?>
                        </td>
                    </tr>

                    <tr>
                        <td>Cron</td>
                        <td>
                            <?php
                            echo !empty(
                                $cron['scheduled']
                            )
                                ? 'Activo'
                                : 'No programado';
                            ?>
                        </td>
                    </tr>

                    <tr>
                        <td>Próxima ejecución UTC</td>
                        <td>
                            <?php
                            echo esc_html(
                                (string) (
                                    $cron[
                                        'next_utc'
                                    ]
                                    ?? '—'
                                )
                            );
                            ?>
                        </td>
                    </tr>

                    <tr>
                        <td>cURL</td>
                        <td>
                            <?php
                            echo !empty(
                                $phpStatus['curl']
                            )
                                ? 'OK'
                                : 'NO';
                            ?>
                        </td>
                    </tr>

                    <tr>
                        <td>OpenSSL</td>
                        <td>
                            <?php
                            echo !empty(
                                $phpStatus['openssl']
                            )
                                ? 'OK'
                                : 'NO';
                            ?>
                        </td>
                    </tr>

                    <tr>
                        <td>DOM</td>
                        <td>
                            <?php
                            echo !empty(
                                $phpStatus['dom']
                            )
                                ? 'OK'
                                : 'NO';
                            ?>
                        </td>
                    </tr>

                </tbody>

            </table>

        </div>

        <div class="dsm-billing-card">

            <h2>Cola VERI*FACTU</h2>

            <table class="widefat striped">

                <tbody>

                    <tr>
                        <td>Registros fiscales</td>
                        <td>
                            <?php echo (int) ($queue['records_total'] ?? 0); ?>
                        </td>
                    </tr>

                    <tr>
                        <td>Pendientes AEAT</td>
                        <td>
                            <?php echo (int) ($queue['records_pending_aeat'] ?? 0); ?>
                        </td>
                    </tr>

                    <tr>
                        <td>Preparados para remisión</td>
                        <td>
                            <?php echo (int) ($queue['records_ready_for_submission'] ?? 0); ?>
                        </td>
                    </tr>

                    <tr>
                        <td>Sin payload congelado</td>
                        <td>
                            <?php echo (int) ($queue['records_without_payload'] ?? 0); ?>
                        </td>
                    </tr>

                    <tr>
                        <td>Huérfanos recuperables</td>
                        <td>
                            <?php echo (int) ($queue['orphaned_ready'] ?? 0); ?>
                        </td>
                    </tr>

                    <tr>
                        <td>Remisiones</td>
                        <td>
                            <?php echo (int) ($queue['submissions_total'] ?? 0); ?>
                        </td>
                    </tr>

                    <tr>
                        <td>Pending</td>
                        <td>
                            <?php echo (int) ($queue['pending'] ?? 0); ?>
                        </td>
                    </tr>

                    <tr>
                        <td>Sending</td>
                        <td>
                            <?php echo (int) ($queue['sending'] ?? 0); ?>
                        </td>
                    </tr>

                    <tr>
                        <td>Error transporte</td>
                        <td>
                            <?php echo (int) ($queue['transport_error'] ?? 0); ?>
                        </td>
                    </tr>

                    <tr>
                        <td>Error respuesta</td>
                        <td>
                            <?php echo (int) ($queue['response_error'] ?? 0); ?>
                        </td>
                    </tr>

                    <tr>
                        <td>Reintentables</td>
                        <td>
                            <?php echo (int) ($queue['retryable'] ?? 0); ?>
                        </td>
                    </tr>

                </tbody>

            </table>

        </div>

        <div class="dsm-billing-card">

            <h2>Cadena fiscal</h2>

            <?php if ($chain === null) : ?>

                <p>
                    Todavía no existe una cadena VERI*FACTU.
                </p>

            <?php else : ?>

                <table class="widefat striped">
                    <tbody>

                        <tr>
                            <td>Secuencia</td>
                            <td>
                                <?php
                                echo (int) (
                                    $chain[
                                        'last_sequence'
                                    ]
                                    ?? 0
                                );
                                ?>
                            </td>
                        </tr>

                        <tr>
                            <td>Último registro</td>
                            <td>
                                <?php
                                echo (int) (
                                    $chain[
                                        'last_record_id'
                                    ]
                                    ?? 0
                                );
                                ?>
                            </td>
                        </tr>

                        <tr>
                            <td>Última huella</td>
                            <td class="dsm-verifactu-code">
                                <?php
                                echo esc_html(
                                    (string) (
                                        $chain[
                                            'last_hash'
                                        ]
                                        ?? ''
                                    )
                                );
                                ?>
                            </td>
                        </tr>

                    </tbody>
                </table>

            <?php endif; ?>

        </div>

        <div class="dsm-billing-card">

            <h2>Incidencia / espera AEAT</h2>

            <table class="widefat striped">
                <tbody>

                    <tr>
                        <td>Incidencia</td>
                        <td>
                            <?php
                            echo !empty(
                                $incidence['active']
                            )
                                ? 'ACTIVA'
                                : 'Inactiva';
                            ?>
                        </td>
                    </tr>

                    <tr>
                        <td>Motivo</td>
                        <td>
                            <?php
                            echo esc_html(
                                (string) (
                                    $incidence['reason']
                                    ?? '—'
                                )
                            );
                            ?>
                        </td>
                    </tr>

                    <tr>
                        <td>Espera AEAT</td>
                        <td>
                            <?php
                            echo !empty(
                                $wait['blocked']
                            )
                                ? 'Activa'
                                : 'No';
                            ?>
                        </td>
                    </tr>

                    <tr>
                        <td>Segundos restantes</td>
                        <td>
                            <?php
                            echo (int) (
                                $wait[
                                    'remaining_seconds'
                                ]
                                ?? 0
                            );
                            ?>
                        </td>
                    </tr>

                </tbody>
            </table>

        </div>

    </div>

    <form
        method="post"
        action="<?php echo esc_url(admin_url('admin-post.php')); ?>"
    >

        <input
            type="hidden"
            name="action"
            value="<?php echo esc_attr(VerifactuSettingsPage::getSaveAction()); ?>"
        >

        <?php
        wp_nonce_field(
            VerifactuSettingsPage::getNonceAction(),
            VerifactuSettingsPage::getNonceName()
        );
        ?>

        <div class="dsm-billing-card">

            <h2>Configuración</h2>

            <table class="form-table" role="presentation">

                <tr>
                    <th scope="row">
                        Transmisión
                    </th>

                    <td>
                        <label>
                            <input
                                type="checkbox"
                                name="verifactu_enabled"
                                value="1"
                                <?php checked($enabled); ?>
                            >

                            Activar envío automático a AEAT
                        </label>

                        <p class="description">
                            No podrá activarse mientras el
                            certificado del servidor no sea válido.
                        </p>
                    </td>
                </tr>

                <tr>
                    <th scope="row">
                        <label for="verifactu_environment">
                            Entorno
                        </label>
                    </th>

                    <td>
                        <select
                            id="verifactu_environment"
                            name="verifactu_environment"
                        >
                            <option
                                value="test"
                                <?php selected($environment, 'test'); ?>
                            >
                                Pruebas
                            </option>

                            <option
                                value="production"
                                <?php selected($environment, 'production'); ?>
                            >
                                Producción
                            </option>
                        </select>
                    </td>
                </tr>

                <tr>
                    <th scope="row">
                        <label for="verifactu_certificate_type">
                            Tipo de certificado
                        </label>
                    </th>

                    <td>
                        <select
                            id="verifactu_certificate_type"
                            name="verifactu_certificate_type"
                        >
                            <option
                                value="standard"
                                <?php selected($certificateType, 'standard'); ?>
                            >
                                Certificado estándar
                            </option>

                            <option
                                value="seal"
                                <?php selected($certificateType, 'seal'); ?>
                            >
                                Sello electrónico
                            </option>
                        </select>
                    </td>
                </tr>

                <tr>
                    <th scope="row">
                        <label for="verifactu_system_name">
                            Sistema
                        </label>
                    </th>

                    <td>
                        <input
                            type="text"
                            id="verifactu_system_name"
                            name="verifactu_system_name"
                            class="regular-text"
                            required
                            value="<?php
                            echo esc_attr(
                                (string) (
                                    $settings[
                                        'system_name'
                                    ]
                                    ?? ''
                                )
                            );
                            ?>"
                        >
                    </td>
                </tr>

                <tr>
                    <th scope="row">
                        <label for="verifactu_system_id">
                            ID sistema
                        </label>
                    </th>

                    <td>
                        <input
                            type="text"
                            id="verifactu_system_id"
                            name="verifactu_system_id"
                            maxlength="2"
                            class="small-text"
                            required
                            value="<?php
                            echo esc_attr(
                                (string) (
                                    $settings[
                                        'system_id'
                                    ]
                                    ?? ''
                                )
                            );
                            ?>"
                        >
                    </td>
                </tr>

                <tr>
                    <th scope="row">
                        Installation ID
                    </th>

                    <td>
                        <code>
                            <?php
                            echo esc_html(
                                (string) (
                                    $settings[
                                        'installation_id'
                                    ]
                                    ?? ''
                                )
                            );
                            ?>
                        </code>

                        <p class="description">
                            Identificador estable de esta instalación.
                            No se modifica desde el panel.
                        </p>
                    </td>
                </tr>

                <tr>
                    <th scope="row">
                        Versión sistema
                    </th>

                    <td>
                        <code>
                            <?php
                            echo esc_html(
                                (string) (
                                    $settings[
                                        'system_version'
                                    ]
                                    ?? ''
                                )
                            );
                            ?>
                        </code>
                    </td>
                </tr>

            </table>

            <div class="dsm-verifactu-production">
                <label>
                    <input
                        type="checkbox"
                        name="verifactu_confirm_production"
                        value="1"
                    >

                    Confirmo que deseo utilizar el entorno
                    de producción de AEAT.
                </label>

                <p class="description">
                    Esta confirmación solo es necesaria
                    cuando el entorno seleccionado sea Producción.
                </p>
            </div>

            <?php submit_button('Guardar VERI*FACTU'); ?>

        </div>

    </form>

    <div class="dsm-billing-card">

        <h2>Certificado del servidor</h2>

        <p>
            Estado:
            <strong>
                <?php echo esc_html($certificateLabel); ?>
            </strong>
        </p>

        <?php if (is_array($certificate)) : ?>

            <table class="widefat striped">
                <tbody>

                    <tr>
                        <td>Fichero</td>
                        <td>
                            <?php
                            echo esc_html(
                                (string) (
                                    $certificate[
                                        'filename'
                                    ]
                                    ?? ''
                                )
                            );
                            ?>
                        </td>
                    </tr>

                    <tr>
                        <td>Formato</td>
                        <td>
                            <?php
                            echo esc_html(
                                (string) (
                                    $certificate[
                                        'format'
                                    ]
                                    ?? ''
                                )
                            );
                            ?>
                        </td>
                    </tr>

                    <tr>
                        <td>Titular</td>
                        <td>
                            <?php
                            echo esc_html(
                                (string) (
                                    $certificate[
                                        'subject'
                                    ]
                                    ?? ''
                                )
                            );
                            ?>
                        </td>
                    </tr>

                    <tr>
                        <td>Emisor</td>
                        <td>
                            <?php
                            echo esc_html(
                                (string) (
                                    $certificate[
                                        'issuer'
                                    ]
                                    ?? ''
                                )
                            );
                            ?>
                        </td>
                    </tr>

                    <tr>
                        <td>Válido desde</td>
                        <td>
                            <?php
                            echo esc_html(
                                (string) (
                                    $certificate[
                                        'valid_from'
                                    ]
                                    ?? ''
                                )
                            );
                            ?>
                        </td>
                    </tr>

                    <tr>
                        <td>Válido hasta</td>
                        <td>
                            <?php
                            echo esc_html(
                                (string) (
                                    $certificate[
                                        'valid_to'
                                    ]
                                    ?? ''
                                )
                            );
                            ?>
                        </td>
                    </tr>

                    <tr>
                        <td>Días restantes</td>
                        <td>
                            <?php
                            echo esc_html(
                                (string) (
                                    $certificate[
                                        'days_remaining'
                                    ]
                                    ?? ''
                                )
                            );
                            ?>
                        </td>
                    </tr>

                </tbody>
            </table>

        <?php else : ?>

            <div class="dsm-verifactu-warning">
                El certificado no se administra desde la
                base de datos de WordPress.

                Debe configurarse en el servidor mediante
                las constantes seguras de VERI*FACTU.
            </div>

            <p>
                Constantes soportadas:
            </p>

            <p>
                <code>DSM_VERIFACTU_CERT_PATH</code><br>
                <code>DSM_VERIFACTU_CERT_PASSWORD</code><br>
                <code>DSM_VERIFACTU_KEY_PATH</code><br>
                <code>DSM_VERIFACTU_KEY_PASSWORD</code>
            </p>

        <?php endif; ?>

    </div>

</div>
