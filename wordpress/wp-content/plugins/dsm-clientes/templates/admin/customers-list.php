<?php

declare(strict_types=1);

use DSM\Clientes\Customer\CustomerStatus;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * @var array<int, array<string, mixed>> $customers
 * @var array<string, int> $counters
 * @var int $currentPage
 * @var int $totalPages
 * @var int $totalItems
 * @var string $search
 * @var string $status
 */

$baseUrl = admin_url('admin.php?page=dsm-clientes');

/**
 * @return string
 */
$getStatusClass = static function (
    string $customerStatus
): string {
    return match ($customerStatus) {
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
};
?>

<div class="wrap">
    <h1 class="wp-heading-inline">
        <?php esc_html_e('Clientes', 'dsm-clientes'); ?>
    </h1>

    <p>
        <?php
        esc_html_e(
            'Gestiona los clientes registrados en DeSegundaMuda.',
            'dsm-clientes'
        );
        ?>
    </p>

    <div class="dsm-admin-counter-grid">
        <div class="dsm-admin-counter-card">
            <strong>
                <?php echo esc_html(
                    (string) $counters['total']
                ); ?>
            </strong>
            <span>Total</span>
        </div>

        <div class="dsm-admin-counter-card">
            <strong>
                <?php echo esc_html(
                    (string) $counters['active']
                ); ?>
            </strong>
            <span>Activos</span>
        </div>

        <div class="dsm-admin-counter-card">
            <strong>
                <?php echo esc_html(
                    (string) $counters['pending']
                ); ?>
            </strong>
            <span>Pendientes</span>
        </div>

        <div class="dsm-admin-counter-card">
            <strong>
                <?php echo esc_html(
                    (string) $counters['inactive']
                ); ?>
            </strong>
            <span>Inactivos</span>
        </div>

        <div class="dsm-admin-counter-card">
            <strong>
                <?php echo esc_html(
                    (string) $counters['suspended']
                ); ?>
            </strong>
            <span>Suspendidos</span>
        </div>

        <div class="dsm-admin-counter-card">
            <strong>
                <?php echo esc_html(
                    (string) $counters['blocked']
                ); ?>
            </strong>
            <span>Bloqueados</span>
        </div>

        <div class="dsm-admin-counter-card">
            <strong>
                <?php echo esc_html(
                    (string) $counters['deletion_pending']
                ); ?>
            </strong>
            <span>Eliminación pendiente</span>
        </div>

        <div class="dsm-admin-counter-card">
            <strong>
                <?php echo esc_html(
                    (string) $counters['verified']
                ); ?>
            </strong>
            <span>Verificados</span>
        </div>
    </div>

    <form method="get">
        <input
            type="hidden"
            name="page"
            value="dsm-clientes"
        >

        <p class="search-box">
            <label
                class="screen-reader-text"
                for="dsm-customer-search"
            >
                Buscar clientes
            </label>

            <input
                id="dsm-customer-search"
                name="s"
                type="search"
                value="<?php echo esc_attr($search); ?>"
                placeholder="Nombre, correo o teléfono"
            >

            <select name="customer_status">
                <option value="">
                    Todos los estados
                </option>

                <?php foreach (
                    CustomerStatus::all() as $availableStatus
                ) : ?>
                    <option
                        value="<?php echo esc_attr(
                            $availableStatus
                        ); ?>"
                        <?php selected(
                            $status,
                            $availableStatus
                        ); ?>
                    >
                        <?php echo esc_html(
                            CustomerStatus::label(
                                $availableStatus
                            )
                        ); ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <?php
            submit_button(
                __('Filtrar', 'dsm-clientes'),
                'secondary',
                '',
                false
            );
            ?>
        </p>
    </form>

    <p>
        <?php
        printf(
            esc_html(
                _n(
                    '%s cliente encontrado.',
                    '%s clientes encontrados.',
                    $totalItems,
                    'dsm-clientes'
                )
            ),
            esc_html(
                number_format_i18n($totalItems)
            )
        );
        ?>
    </p>

    <div class="dsm-admin-table-scroll">
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th class="dsm-admin-column-id">ID</th>
                    <th>Cliente</th>
                    <th>Correo</th>
                    <th>Estado</th>
                    <th>Verificación</th>
                    <th>Alta</th>
                    <th>Última actividad</th>
                    <th class="dsm-admin-column-small">
                        Sesiones
                    </th>
                    <th class="dsm-admin-column-small">
                        Acciones
                    </th>
                </tr>
            </thead>

            <tbody>
                <?php if ($customers === []) : ?>
                    <tr>
                        <td colspan="9">
                            No se encontraron clientes.
                        </td>
                    </tr>
                <?php else : ?>
                    <?php foreach ($customers as $customer) : ?>
                        <?php
                        $customerId = (int) $customer['id'];

                        $displayName = trim(
                            (string) (
                                $customer['display_name']
                                ?? ''
                            )
                        );

                        $customerStatus =
                            (string) $customer['status'];

                        $verified =
                            $customer['email_verified_at']
                            !== null;

                        $lastActivity =
                            $customer['last_session_activity']
                            ?? $customer['last_login_at']
                            ?? null;

                        $detailUrl = add_query_arg(
                            [
                                'page' => 'dsm-clientes',
                                'action' => 'view',
                                'customer_id' => $customerId,
                            ],
                            admin_url('admin.php')
                        );
                        ?>

                        <tr>
                            <td>
                                <?php echo esc_html(
                                    (string) $customerId
                                ); ?>
                            </td>

                            <td>
                                <strong>
                                    <a href="<?php echo esc_url(
                                        $detailUrl
                                    ); ?>">
                                        <?php
                                        echo esc_html(
                                            $displayName !== ''
                                                ? $displayName
                                                : 'Sin nombre'
                                        );
                                        ?>
                                    </a>
                                </strong>

                                <?php if (
                                    !empty($customer['phone'])
                                ) : ?>
                                    <br>

                                    <small>
                                        <?php echo esc_html(
                                            (string) $customer['phone']
                                        ); ?>
                                    </small>
                                <?php endif; ?>
                            </td>

                            <td>
                                <a
                                    href="mailto:<?php echo esc_attr(
                                        (string) $customer['email']
                                    ); ?>"
                                >
                                    <?php echo esc_html(
                                        (string) $customer['email']
                                    ); ?>
                                </a>
                            </td>

                            <td>
                                <span
                                    class="<?php echo esc_attr(
                                        'dsm-admin-status '
                                        . $getStatusClass(
                                            $customerStatus
                                        )
                                    ); ?>"
                                >
                                    <?php echo esc_html(
                                        CustomerStatus::label(
                                            $customerStatus
                                        )
                                    ); ?>
                                </span>
                            </td>

                            <td>
                                <?php if ($verified) : ?>
                                    <span
                                        class="
                                            dsm-admin-status
                                            dsm-admin-status--active
                                        "
                                    >
                                        Verificado
                                    </span>

                                    <br>

                                    <small>
                                        <?php echo esc_html(
                                            get_date_from_gmt(
                                                (string) $customer[
                                                    'email_verified_at'
                                                ],
                                                'd/m/Y H:i'
                                            )
                                        ); ?>
                                    </small>
                                <?php else : ?>
                                    <span
                                        class="
                                            dsm-admin-status
                                            dsm-admin-status--pending
                                        "
                                    >
                                        Pendiente
                                    </span>
                                <?php endif; ?>
                            </td>

                            <td>
                                <?php echo esc_html(
                                    get_date_from_gmt(
                                        (string) $customer[
                                            'created_at'
                                        ],
                                        'd/m/Y H:i'
                                    )
                                ); ?>
                            </td>

                            <td>
                                <?php if (
                                    $lastActivity !== null
                                ) : ?>
                                    <?php echo esc_html(
                                        get_date_from_gmt(
                                            (string) $lastActivity,
                                            'd/m/Y H:i'
                                        )
                                    ); ?>
                                <?php else : ?>
                                    Nunca
                                <?php endif; ?>
                            </td>

                            <td>
                                <?php echo esc_html(
                                    (string) (
                                        $customer['active_sessions']
                                        ?? 0
                                    )
                                ); ?>
                            </td>

                            <td>
                                <a
                                    class="button button-small"
                                    href="<?php echo esc_url(
                                        $detailUrl
                                    ); ?>"
                                >
                                    Ver
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>

            <tfoot>
                <tr>
                    <th>ID</th>
                    <th>Cliente</th>
                    <th>Correo</th>
                    <th>Estado</th>
                    <th>Verificación</th>
                    <th>Alta</th>
                    <th>Última actividad</th>
                    <th>Sesiones</th>
                    <th>Acciones</th>
                </tr>
            </tfoot>
        </table>
    </div>

    <?php if ($totalPages > 1) : ?>
        <div class="tablenav">
            <div class="tablenav-pages">
                <?php
                echo wp_kses_post(
                    paginate_links(
                        [
                            'base' => add_query_arg(
                                'paged',
                                '%#%',
                                $baseUrl
                            ),
                            'format' => '',
                            'current' => $currentPage,
                            'total' => $totalPages,
                            'prev_text' => '‹',
                            'next_text' => '›',
                            'add_args' => array_filter(
                                [
                                    's' => $search,
                                    'customer_status' => $status,
                                ]
                            ),
                        ]
                    )
                );
                ?>
            </div>
        </div>
    <?php endif; ?>
</div>