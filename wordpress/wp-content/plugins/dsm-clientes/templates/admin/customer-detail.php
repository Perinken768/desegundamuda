<?php

declare(strict_types=1);

use DSM\Clientes\Authentication\CustomerSession;
use DSM\Clientes\Customer\CustomerStatus;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * @var array<string, mixed> $customer
 * @var array<int, CustomerSession> $sessions
 * @var string $adminStatus
 */

$customerId = (int) $customer['id'];

$listUrl = admin_url(
    'admin.php?page=dsm-clientes'
);

$verified =
    $customer['email_verified_at'] !== null;

$activeSessions = array_filter(
    $sessions,
    static fn (CustomerSession $session): bool =>
        $session->isValid()
);

$displayName = trim(
    (string) (
        $customer['display_name'] ?? ''
    )
);

$customerStatus =
    (string) $customer['status'];

$statusClass = match ($customerStatus) {
    CustomerStatus::ACTIVE =>
        'dsm-admin-status--active',

    CustomerStatus::INACTIVE =>
        'dsm-admin-status--inactive',

    CustomerStatus::SUSPENDED =>
        'dsm-admin-status--suspended',

    CustomerStatus::BLOCKED =>
        'dsm-admin-status--blocked',

    CustomerStatus::DELETION_PENDING =>
        'dsm-admin-status--deletion-pending',

    default =>
        'dsm-admin-status--pending',
};
?>

<div class="wrap">
    <p>
        <a href="<?php echo esc_url($listUrl); ?>">
            ← Volver al listado
        </a>
    </p>

    <h1>
        <?php
        echo esc_html(
            $displayName !== ''
                ? $displayName
                : (string) $customer['email']
        );
        ?>
    </h1>

    <?php if ($adminStatus === 'status_updated') : ?>
        <div class="notice notice-success is-dismissible">
            <p>
                El estado del cliente se actualizó correctamente.
            </p>
        </div>

    <?php elseif ($adminStatus === 'email_verified') : ?>
        <div class="notice notice-success is-dismissible">
            <p>
                El correo se marcó como verificado.
            </p>
        </div>

    <?php elseif (
        $adminStatus === 'verification_resent'
    ) : ?>
        <div class="notice notice-success is-dismissible">
            <p>
                El correo de verificación se reenvió correctamente.
            </p>
        </div>

    <?php elseif (
        $adminStatus === 'sessions_revoked'
    ) : ?>
        <div class="notice notice-success is-dismissible">
            <p>
                Las sesiones del cliente fueron revocadas.
            </p>
        </div>

    <?php elseif (
        $adminStatus === 'password_updated'
    ) : ?>
        <div class="notice notice-success is-dismissible">
            <p>
                La contraseña se actualizó y las sesiones
                quedaron cerradas.
            </p>
        </div>

    <?php elseif (
        $adminStatus === 'password_error'
    ) : ?>
        <div class="notice notice-error is-dismissible">
            <p>
                No se pudo actualizar la contraseña.
                Debe tener al menos 12 caracteres.
            </p>
        </div>

    <?php elseif (
        $adminStatus === 'action_error'
    ) : ?>
        <div class="notice notice-error is-dismissible">
            <p>
                No se pudo completar la operación.
            </p>
        </div>
    <?php endif; ?>

    <div class="dsm-admin-customer-grid">

        <div class="dsm-admin-customer-main">

            <section class="dsm-admin-card">
                <h2>Datos del cliente</h2>

                <table class="widefat striped">
                    <tbody>
                        <tr>
                            <th scope="row">
                                ID
                            </th>

                            <td>
                                <?php
                                echo esc_html(
                                    (string) $customerId
                                );
                                ?>
                            </td>
                        </tr>

                        <tr>
                            <th scope="row">
                                Nombre visible
                            </th>

                            <td>
                                <?php
                                echo esc_html(
                                    $displayName !== ''
                                        ? $displayName
                                        : 'Sin nombre'
                                );
                                ?>
                            </td>
                        </tr>

                        <tr>
                            <th scope="row">
                                Correo
                            </th>

                            <td>
                                <a
                                    href="mailto:<?php echo esc_attr(
                                        (string) $customer['email']
                                    ); ?>"
                                >
                                    <?php
                                    echo esc_html(
                                        (string) $customer['email']
                                    );
                                    ?>
                                </a>
                            </td>
                        </tr>

                        <tr>
                            <th scope="row">
                                Estado
                            </th>

                            <td>
                                <span
                                    class="<?php echo esc_attr(
                                        'dsm-admin-status '
                                        . $statusClass
                                    ); ?>"
                                >
                                    <?php
                                    echo esc_html(
                                        CustomerStatus::label(
                                            $customerStatus
                                        )
                                    );
                                    ?>
                                </span>
                            </td>
                        </tr>

                        <tr>
                            <th scope="row">
                                Correo verificado
                            </th>

                            <td>
                                <?php if ($verified) : ?>
                                    Sí —

                                    <?php
                                    echo esc_html(
                                        get_date_from_gmt(
                                            (string) $customer[
                                                'email_verified_at'
                                            ],
                                            'd/m/Y H:i'
                                        )
                                    );
                                    ?>
                                <?php else : ?>
                                    No
                                <?php endif; ?>
                            </td>
                        </tr>

                        <tr>
                            <th scope="row">
                                Teléfono
                            </th>

                            <td>
                                <?php
                                echo esc_html(
                                    (string) (
                                        $customer['phone']
                                        ?: 'No indicado'
                                    )
                                );
                                ?>
                            </td>
                        </tr>

                        <tr>
                            <th scope="row">
                                WhatsApp
                            </th>

                            <td>
                                <?php
                                echo esc_html(
                                    (string) (
                                        $customer[
                                            'whatsapp_phone'
                                        ]
                                        ?: 'No indicado'
                                    )
                                );
                                ?>
                            </td>
                        </tr>

                        <tr>
                            <th scope="row">
                                Biografía
                            </th>

                            <td>
                                <?php
                                echo nl2br(
                                    esc_html(
                                        (string) (
                                            $customer['bio']
                                            ?: 'Sin biografía'
                                        )
                                    )
                                );
                                ?>
                            </td>
                        </tr>

                        <tr>
                            <th scope="row">
                                Fecha de alta
                            </th>

                            <td>
                                <?php
                                echo esc_html(
                                    get_date_from_gmt(
                                        (string) $customer[
                                            'created_at'
                                        ],
                                        'd/m/Y H:i'
                                    )
                                );
                                ?>
                            </td>
                        </tr>

                        <tr>
                            <th scope="row">
                                Última actualización
                            </th>

                            <td>
                                <?php
                                echo esc_html(
                                    get_date_from_gmt(
                                        (string) $customer[
                                            'updated_at'
                                        ],
                                        'd/m/Y H:i'
                                    )
                                );
                                ?>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </section>

            <section class="dsm-admin-card">
                <h2>Sesiones</h2>

                <p>
                    Sesiones activas:

                    <strong>
                        <?php
                        echo esc_html(
                            (string) count(
                                $activeSessions
                            )
                        );
                        ?>
                    </strong>
                </p>

                <div class="dsm-admin-table-scroll">
                    <table class="widefat striped">
                        <thead>
                            <tr>
                                <th
                                    class="dsm-admin-session-id"
                                >
                                    ID
                                </th>

                                <th
                                    class="dsm-admin-session-ip"
                                >
                                    IP
                                </th>

                                <th
                                    class="dsm-admin-session-agent"
                                >
                                    Agente
                                </th>

                                <th
                                    class="dsm-admin-session-date"
                                >
                                    Creada
                                </th>

                                <th
                                    class="
                                        dsm-admin-session-activity
                                    "
                                >
                                    Última actividad
                                </th>

                                <th
                                    class="dsm-admin-session-date"
                                >
                                    Caduca
                                </th>

                                <th
                                    class="
                                        dsm-admin-session-status
                                    "
                                >
                                    Estado
                                </th>
                            </tr>
                        </thead>

                        <tbody>
                            <?php if ($sessions === []) : ?>
                                <tr>
                                    <td colspan="7">
                                        No existen sesiones.
                                    </td>
                                </tr>

                            <?php else : ?>
                                <?php foreach (
                                    $sessions as $session
                                ) : ?>
                                    <tr>
                                        <td>
                                            <?php
                                            echo esc_html(
                                                (string)
                                                $session->getId()
                                            );
                                            ?>
                                        </td>

                                        <td>
                                            <?php
                                            echo esc_html(
                                                $session
                                                    ->getIpAddress()
                                                ?? 'No registrada'
                                            );
                                            ?>
                                        </td>

                                        <td>
                                            <?php
                                            $userAgent =
                                                $session
                                                    ->getUserAgent()
                                                ?? 'No registrado';

                                            echo esc_html(
                                                mb_strimwidth(
                                                    $userAgent,
                                                    0,
                                                    120,
                                                    '…'
                                                )
                                            );
                                            ?>
                                        </td>

                                        <td>
                                            <?php
                                            echo esc_html(
                                                get_date_from_gmt(
                                                    $session
                                                        ->getCreatedAt(),
                                                    'd/m/Y H:i'
                                                )
                                            );
                                            ?>
                                        </td>

                                        <td>
                                            <?php
                                            echo esc_html(
                                                get_date_from_gmt(
                                                    $session
                                                        ->getLastActivityAt(),
                                                    'd/m/Y H:i'
                                                )
                                            );
                                            ?>
                                        </td>

                                        <td>
                                            <?php
                                            echo esc_html(
                                                get_date_from_gmt(
                                                    $session
                                                        ->getExpiresAt(),
                                                    'd/m/Y H:i'
                                                )
                                            );
                                            ?>
                                        </td>

                                        <td>
                                            <?php if (
                                                $session->isRevoked()
                                            ) : ?>
                                                <span
                                                    class="
                                                        dsm-admin-status
                                                        dsm-admin-status--revoked
                                                    "
                                                >
                                                    Revocada
                                                </span>

                                            <?php elseif (
                                                $session->isExpired()
                                            ) : ?>
                                                <span
                                                    class="
                                                        dsm-admin-status
                                                        dsm-admin-status--expired
                                                    "
                                                >
                                                    Caducada
                                                </span>

                                            <?php else : ?>
                                                <span
                                                    class="
                                                        dsm-admin-status
                                                        dsm-admin-status--active
                                                    "
                                                >
                                                    Activa
                                                </span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </section>

        </div>

        <aside class="dsm-admin-customer-sidebar">

            <section class="dsm-admin-card">
                <h2>Cambiar estado</h2>

                <form
                    method="post"
                    action="<?php echo esc_url(
                        admin_url('admin-post.php')
                    ); ?>"
                >
                    <input
                        type="hidden"
                        name="action"
                        value="dsm_customer_admin_update_status"
                    >

                    <input
                        type="hidden"
                        name="customer_id"
                        value="<?php echo esc_attr(
                            (string) $customerId
                        ); ?>"
                    >

                    <?php
                    wp_nonce_field(
                        'dsm_customer_admin_update_status',
                        'dsm_customer_admin_nonce'
                    );
                    ?>

                    <p>
                        <select
                            class="dsm-admin-field"
                            name="status"
                        >
                            <?php foreach (
                                CustomerStatus::all()
                                as $availableStatus
                            ) : ?>
                                <option
                                    value="<?php echo esc_attr(
                                        $availableStatus
                                    ); ?>"
                                    <?php selected(
                                        $customerStatus,
                                        $availableStatus
                                    ); ?>
                                >
                                    <?php
                                    echo esc_html(
                                        CustomerStatus::label(
                                            $availableStatus
                                        )
                                    );
                                    ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </p>

                    <?php
                    submit_button(
                        'Guardar estado',
                        'primary',
                        'submit',
                        false
                    );
                    ?>
                </form>
            </section>

            <section class="dsm-admin-card">
                <h2>Correo electrónico</h2>

                <?php if (!$verified) : ?>
                    <form
                        class="dsm-admin-action-form"
                        method="post"
                        action="<?php echo esc_url(
                            admin_url('admin-post.php')
                        ); ?>"
                    >
                        <input
                            type="hidden"
                            name="action"
                            value="dsm_customer_admin_verify_email"
                        >

                        <input
                            type="hidden"
                            name="customer_id"
                            value="<?php echo esc_attr(
                                (string) $customerId
                            ); ?>"
                        >

                        <?php
                        wp_nonce_field(
                            'dsm_customer_admin_verify_email',
                            'dsm_customer_admin_nonce'
                        );
                        ?>

                        <?php
                        submit_button(
                            'Verificar manualmente',
                            'secondary',
                            'submit',
                            false
                        );
                        ?>
                    </form>

                    <form
                        class="dsm-admin-action-form"
                        method="post"
                        action="<?php echo esc_url(
                            admin_url('admin-post.php')
                        ); ?>"
                    >
                        <input
                            type="hidden"
                            name="action"
                            value="dsm_customer_admin_resend_verification"
                        >

                        <input
                            type="hidden"
                            name="customer_id"
                            value="<?php echo esc_attr(
                                (string) $customerId
                            ); ?>"
                        >

                        <?php
                        wp_nonce_field(
                            'dsm_customer_admin_resend_verification',
                            'dsm_customer_admin_nonce'
                        );
                        ?>

                        <?php
                        submit_button(
                            'Reenviar verificación',
                            'secondary',
                            'submit',
                            false
                        );
                        ?>
                    </form>
                <?php else : ?>
                    <p>
                        El correo ya está verificado.
                    </p>
                <?php endif; ?>
            </section>

            <section class="dsm-admin-card">
                <h2>Sesiones</h2>

                <form
                    method="post"
                    action="<?php echo esc_url(
                        admin_url('admin-post.php')
                    ); ?>"
                    onsubmit="return confirm(
                        '¿Cerrar todas las sesiones de este cliente?'
                    );"
                >
                    <input
                        type="hidden"
                        name="action"
                        value="dsm_customer_admin_revoke_sessions"
                    >

                    <input
                        type="hidden"
                        name="customer_id"
                        value="<?php echo esc_attr(
                            (string) $customerId
                        ); ?>"
                    >

                    <?php
                    wp_nonce_field(
                        'dsm_customer_admin_revoke_sessions',
                        'dsm_customer_admin_nonce'
                    );
                    ?>

                    <?php
                    submit_button(
                        'Cerrar todas las sesiones',
                        'secondary',
                        'submit',
                        false
                    );
                    ?>
                </form>
            </section>

            <section class="dsm-admin-card">
                <h2>Contraseña temporal</h2>

                <p class="dsm-admin-description">
                    La contraseña debe tener al menos 12
                    caracteres. Al cambiarla se cerrarán
                    todas las sesiones.
                </p>

                <form
                    method="post"
                    action="<?php echo esc_url(
                        admin_url('admin-post.php')
                    ); ?>"
                >
                    <input
                        type="hidden"
                        name="action"
                        value="dsm_customer_admin_update_password"
                    >

                    <input
                        type="hidden"
                        name="customer_id"
                        value="<?php echo esc_attr(
                            (string) $customerId
                        ); ?>"
                    >

                    <?php
                    wp_nonce_field(
                        'dsm_customer_admin_update_password',
                        'dsm_customer_admin_nonce'
                    );
                    ?>

                    <p>
                        <input
                            class="dsm-admin-field"
                            name="temporary_password"
                            type="password"
                            minlength="12"
                            autocomplete="new-password"
                            required
                        >
                    </p>

                    <?php
                    submit_button(
                        'Establecer contraseña',
                        'secondary',
                        'submit',
                        false
                    );
                    ?>
                </form>
            </section>

        </aside>

    </div>
</div>