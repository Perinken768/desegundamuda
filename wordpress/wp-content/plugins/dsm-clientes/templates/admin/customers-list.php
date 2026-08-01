<?php

declare(strict_types=1);

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

    <div
        style="
            display:grid;
            grid-template-columns:repeat(auto-fit,minmax(150px,1fr));
            gap:16px;
            max-width:950px;
            margin:20px 0;
        "
    >
        <div class="card">
            <strong>
                <?php echo esc_html((string) $counters['total']); ?>
            </strong>
            <p>Total</p>
        </div>

        <div class="card">
            <strong>
                <?php echo esc_html((string) $counters['active']); ?>
            </strong>
            <p>Activos</p>
        </div>

        <div class="card">
            <strong>
                <?php echo esc_html((string) $counters['pending']); ?>
            </strong>
            <p>Pendientes</p>
        </div>

        <div class="card">
            <strong>
                <?php echo esc_html((string) $counters['verified']); ?>
            </strong>
            <p>Verificados</p>
        </div>

        <div class="card">
            <strong>
                <?php echo esc_html((string) $counters['blocked']); ?>
            </strong>
            <p>Bloqueados</p>
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

                <option
                    value="pending"
                    <?php selected($status, 'pending'); ?>
                >
                    Pendientes
                </option>

                <option
                    value="active"
                    <?php selected($status, 'active'); ?>
                >
                    Activos
                </option>

                <option
                    value="blocked"
                    <?php selected($status, 'blocked'); ?>
                >
                    Bloqueados
                </option>
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

    <table class="wp-list-table widefat fixed striped">
        <thead>
            <tr>
                <th style="width:65px;">ID</th>
                <th>Cliente</th>
                <th>Correo</th>
                <th>Estado</th>
                <th>Verificación</th>
                <th>Alta</th>
                <th>Última actividad</th>
                <th style="width:100px;">Sesiones</th>
                <th style="width:100px;">Acciones</th>
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
                        $customer['email_verified_at'] !== null;

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
                                <a href="<?php echo esc_url($detailUrl); ?>">
                                    <?php
                                    echo esc_html(
                                        $displayName !== ''
                                            ? $displayName
                                            : 'Sin nombre'
                                    );
                                    ?>
                                </a>
                            </strong>

                            <?php if (!empty($customer['phone'])) : ?>
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
                            <?php if ($customerStatus === 'active') : ?>
                                <span style="color:#008a20;font-weight:700;">
                                    Activo
                                </span>
                            <?php elseif ($customerStatus === 'blocked') : ?>
                                <span style="color:#b32d2e;font-weight:700;">
                                    Bloqueado
                                </span>
                            <?php else : ?>
                                <span style="color:#996800;font-weight:700;">
                                    Pendiente
                                </span>
                            <?php endif; ?>
                        </td>

                        <td>
                            <?php if ($verified) : ?>
                                <span style="color:#008a20;font-weight:700;">
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
                                <span style="color:#996800;font-weight:700;">
                                    Pendiente
                                </span>
                            <?php endif; ?>
                        </td>

                        <td>
                            <?php echo esc_html(
                                get_date_from_gmt(
                                    (string) $customer['created_at'],
                                    'd/m/Y H:i'
                                )
                            ); ?>
                        </td>

                        <td>
                            <?php if ($lastActivity !== null) : ?>
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
                                href="<?php echo esc_url($detailUrl); ?>"
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