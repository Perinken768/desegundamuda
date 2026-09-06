<?php

declare(strict_types=1);

namespace DSM\Anuncios\Admin;

use DSM\Anuncios\Advertisement\AdvertisementStatus;
use DSM\Anuncios\Report\AdvertisementReport;
use DSM\Anuncios\Report\AdvertisementReportRepository;

if (!defined('ABSPATH')) {
    exit;
}

final class AdvertisementReportsPage
{
    public const MENU_SLUG =
        'dsm-anuncios-reports';

    private const CAPABILITY =
        'manage_options';

    public function __construct(
        private readonly AdvertisementReportRepository $repository
    ) {
    }

    public function register(): void
    {
        add_action(
            'admin_menu',
            [
                $this,
                'registerMenu',
            ],
            20
        );
    }

    public function registerMenu(): void
    {
        $activeCount =
            $this->repository
                ->countActiveGroups();

        $menuTitle =
            __('Denuncias', 'dsm-anuncios');

        if ($activeCount > 0) {
            $menuTitle .= sprintf(
                ' <span class="awaiting-mod">%d</span>',
                $activeCount
            );
        }

        add_submenu_page(
            AdvertisementsPage::MENU_SLUG,
            __('Denuncias', 'dsm-anuncios'),
            $menuTitle,
            self::CAPABILITY,
            self::MENU_SLUG,
            [
                $this,
                'render',
            ]
        );
    }

    public function render(): void
    {
        if (
            !current_user_can(
                self::CAPABILITY
            )
        ) {
            wp_die(
                esc_html__(
                    'No tienes permisos para acceder a esta página.',
                    'dsm-anuncios'
                )
            );
        }

        $advertisementId =
            isset($_GET['advertisement_id'])
                ? max(
                    0,
                    absint(
                        wp_unslash(
                            (string) $_GET[
                                'advertisement_id'
                            ]
                        )
                    )
                )
                : 0;

        if ($advertisementId > 0) {
            $this->renderAdvertisementReports(
                $advertisementId
            );

            return;
        }

        $this->renderGroups();
    }

    private function renderGroups(): void
    {
        $bucket =
            isset($_GET['report_bucket'])
                ? sanitize_key(
                    wp_unslash(
                        (string) $_GET[
                            'report_bucket'
                        ]
                    )
                )
                : 'active';

        if (
            !in_array(
                $bucket,
                [
                    'active',
                    'dismissed',
                    'actioned',
                ],
                true
            )
        ) {
            $bucket = 'active';
        }

        $groups =
            $this->repository
                ->findAdvertisementGroups(
                    $bucket,
                    100,
                    0
                );

        ?>
        <div class="wrap">

            <h1>
                <?php esc_html_e(
                    'Denuncias de anuncios',
                    'dsm-anuncios'
                ); ?>
            </h1>

            <?php $this->renderNotice(); ?>

            <nav class="nav-tab-wrapper">
                <?php
                $tabs = [
                    'active' =>
                        'Activas',

                    'dismissed' =>
                        'Ignoradas',

                    'actioned' =>
                        'Retiradas',
                ];

                foreach (
                    $tabs
                    as $key => $label
                ) :
                    ?>
                    <a
                        class="<?php
                        echo esc_attr(
                            'nav-tab '
                            . (
                                $bucket === $key
                                    ? 'nav-tab-active'
                                    : ''
                            )
                        );
                        ?>"
                        href="<?php
                        echo esc_url(
                            add_query_arg(
                                [
                                    'page' =>
                                        self::MENU_SLUG,

                                    'report_bucket' =>
                                        $key,
                                ],
                                admin_url(
                                    'admin.php'
                                )
                            )
                        );
                        ?>"
                    >
                        <?php echo esc_html($label); ?>
                    </a>
                <?php endforeach; ?>
            </nav>

            <table class="widefat striped">
                <thead>
                    <tr>
                        <th>Anuncio</th>
                        <th>Vendedor</th>
                        <th>Estado</th>
                        <th>Denuncias</th>
                        <th>Activas</th>
                        <th>Última denuncia</th>
                        <th>Acciones</th>
                    </tr>
                </thead>

                <tbody>

                <?php if ($groups === []) : ?>

                    <tr>
                        <td colspan="7">
                            No hay anuncios en esta sección.
                        </td>
                    </tr>

                <?php else : ?>

                    <?php foreach ($groups as $group) : ?>

                        <?php
                        $advertisementId =
                            max(
                                0,
                                (int) (
                                    $group[
                                        'advertisement_id'
                                    ]
                                    ?? 0
                                )
                            );

                        $reportedCustomerId =
                            max(
                                0,
                                (int) (
                                    $group[
                                        'reported_customer_id'
                                    ]
                                    ?? 0
                                )
                            );

                        $customer =
                            $this->resolveCustomer(
                                $reportedCustomerId
                            );

                        $detailUrl =
                            add_query_arg(
                                [
                                    'page' =>
                                        self::MENU_SLUG,

                                    'advertisement_id' =>
                                        $advertisementId,
                                ],
                                admin_url(
                                    'admin.php'
                                )
                            );

                        $publicUrl =
                            $this->getAdvertisementPublicUrl(
                                (string) (
                                    $group['slug']
                                    ?? ''
                                )
                            );
                        ?>

                        <tr>

                            <td>
                                <strong>
                                    <?php echo esc_html(
                                        (string) (
                                            $group['title']
                                            ?? ''
                                        )
                                    ); ?>
                                </strong>

                                <div>
                                    ID:
                                    <?php echo esc_html(
                                        (string)
                                        $advertisementId
                                    ); ?>
                                </div>
                            </td>

                            <td>
                                <?php echo esc_html(
                                    $this->getCustomerLabel(
                                        $customer,
                                        $reportedCustomerId
                                    )
                                ); ?>
                            </td>

                            <td>
                                <?php
                                $this->renderStatusBadge(
                                    (string) (
                                        $group[
                                            'advertisement_status'
                                        ]
                                        ?? ''
                                    )
                                );
                                ?>
                            </td>

                            <td>
                                <a
                                    href="<?php
                                    echo esc_url(
                                        $detailUrl
                                    );
                                    ?>"
                                >
                                    <strong>
                                        <?php echo esc_html(
                                            (string) (
                                                $group[
                                                    'total_reports'
                                                ]
                                                ?? 0
                                            )
                                        ); ?>
                                    </strong>
                                </a>
                            </td>

                            <td>
                                <strong>
                                    <?php echo esc_html(
                                        (string) (
                                            $group[
                                                'active_reports'
                                            ]
                                            ?? 0
                                        )
                                    ); ?>
                                </strong>
                            </td>

                            <td>
                                <?php echo esc_html(
                                    $this->formatDate(
                                        (string) (
                                            $group[
                                                'last_reported_at'
                                            ]
                                            ?? ''
                                        )
                                    )
                                ); ?>
                            </td>

                            <td>
                                <a
                                    class="button button-small"
                                    href="<?php
                                    echo esc_url(
                                        $detailUrl
                                    );
                                    ?>"
                                >
                                    Ver denuncias
                                </a>

                                <?php if (
                                    $publicUrl !== ''
                                ) : ?>
                                    <a
                                        class="button button-small"
                                        href="<?php
                                        echo esc_url(
                                            $publicUrl
                                        );
                                        ?>"
                                        target="_blank"
                                        rel="noopener noreferrer"
                                    >
                                        Ver anuncio
                                    </a>
                                <?php endif; ?>
                            </td>

                        </tr>

                    <?php endforeach; ?>

                <?php endif; ?>

                </tbody>
            </table>

        </div>
        <?php
    }

    private function renderAdvertisementReports(
        int $advertisementId
    ): void {
        $reports =
            $this->repository
                ->findByAdvertisementId(
                    $advertisementId
                );

        $firstReport =
            $reports !== []
            && is_array($reports[0])
                ? $reports[0]
                : [];

        $advertisementTitle =
            trim(
                (string) (
                    $firstReport[
                        'advertisement_title'
                    ]
                    ?? $firstReport['title']
                    ?? ''
                )
            );

        $reportedCustomerId =
            max(
                0,
                (int) (
                    $firstReport[
                        'reported_customer_id'
                    ]
                    ?? 0
                )
            );

        $seller =
            $this->resolveCustomer(
                $reportedCustomerId
            );

        $advertisementStatus =
            sanitize_key(
                (string) (
                    $firstReport[
                        'advertisement_status'
                    ]
                    ?? ''
                )
            );

        $advertisementSlug =
            trim(
                (string) (
                    $firstReport[
                        'advertisement_slug'
                    ]
                    ?? $firstReport['slug']
                    ?? ''
                )
            );

        $publicUrl =
            $this->getAdvertisementPublicUrl(
                $advertisementSlug
            );

        ?>
        <div class="wrap">

            <h1>
                <?php
                printf(
                    esc_html__(
                        'Denuncias del anuncio #%d',
                        'dsm-anuncios'
                    ),
                    $advertisementId
                );
                ?>
            </h1>

            <p>
                <a
                    href="<?php
                    echo esc_url(
                        add_query_arg(
                            [
                                'page' =>
                                    self::MENU_SLUG,
                            ],
                            admin_url(
                                'admin.php'
                            )
                        )
                    );
                    ?>"
                >
                    ← Volver a denuncias
                </a>
            </p>

            <?php if ($reports === []) : ?>

                <div class="notice notice-warning">
                    <p>
                        No existen denuncias para este anuncio.
                    </p>
                </div>

            <?php else : ?>

                <div
                    style="
                        background:#fff;
                        border:1px solid #dcdcde;
                        padding:16px 20px;
                        margin:16px 0 20px;
                        max-width:900px;
                    "
                >
                    <h2
                        style="
                            margin-top:0;
                            margin-bottom:12px;
                        "
                    >
                        <?php echo esc_html(
                            $advertisementTitle !== ''
                                ? $advertisementTitle
                                : 'Anuncio #'
                                    . $advertisementId
                        ); ?>
                    </h2>

                    <p>
                        <strong>Vendedor:</strong>
                        <?php echo esc_html(
                            $this->getCustomerLabel(
                                $seller,
                                $reportedCustomerId
                            )
                        ); ?>
                    </p>

                    <?php if (
                        $advertisementStatus !== ''
                    ) : ?>
                        <p>
                            <strong>Estado:</strong>
                            <?php
                            $this->renderStatusBadge(
                                $advertisementStatus
                            );
                            ?>
                        </p>
                    <?php endif; ?>

                    <?php if ($publicUrl !== '') : ?>
                        <p>
                            <a
                                class="button"
                                href="<?php
                                echo esc_url(
                                    $publicUrl
                                );
                                ?>"
                                target="_blank"
                                rel="noopener noreferrer"
                            >
                                Ver anuncio
                            </a>
                        </p>
                    <?php endif; ?>
                </div>

                <table class="widefat striped">

                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Denunciante</th>
                            <th>Motivo</th>
                            <th>Detalles</th>
                            <th>Estado</th>
                            <th>Fecha</th>
                        </tr>
                    </thead>

                    <tbody>

                    <?php foreach ($reports as $report) : ?>

                        <?php
                        $reporterCustomerId =
                            max(
                                0,
                                (int) (
                                    $report[
                                        'reporter_customer_id'
                                    ]
                                    ?? 0
                                )
                            );

                        $reporter =
                            $this->resolveCustomer(
                                $reporterCustomerId
                            );
                        ?>

                        <tr>
                            <td>
                                #<?php echo esc_html(
                                    (string) (
                                        $report['id']
                                        ?? 0
                                    )
                                ); ?>
                            </td>

                            <td>
                                <?php echo esc_html(
                                    $this->getCustomerLabel(
                                        $reporter,
                                        $reporterCustomerId
                                    )
                                ); ?>
                            </td>

                            <td>
                                <?php echo esc_html(
                                    AdvertisementReport::
                                        getReasonLabel(
                                            (string) (
                                                $report[
                                                    'reason_code'
                                                ]
                                                ?? ''
                                            )
                                        )
                                ); ?>
                            </td>

                            <td>
                                <?php
                                $details =
                                    trim(
                                        (string) (
                                            $report['details']
                                            ?? ''
                                        )
                                    );

                                echo esc_html(
                                    $details !== ''
                                        ? $details
                                        : '—'
                                );
                                ?>
                            </td>

                            <td>
                                <?php
                                $this->renderReportStatusBadge(
                                    (string) (
                                        $report['status']
                                        ?? ''
                                    )
                                );
                                ?>
                            </td>

                            <td>
                                <?php echo esc_html(
                                    $this->formatDate(
                                        (string) (
                                            $report[
                                                'created_at'
                                            ]
                                            ?? ''
                                        )
                                    )
                                ); ?>
                            </td>
                        </tr>

                    <?php endforeach; ?>

                    </tbody>

                </table>

                <?php
                $hasActive =
                    array_filter(
                        $reports,
                        static fn (
                            array $report
                        ): bool =>
                            in_array(
                                (
                                    $report['status']
                                    ?? ''
                                ),
                                [
                                    AdvertisementReport::
                                        STATUS_PENDING,

                                    AdvertisementReport::
                                        STATUS_REVIEWING,
                                ],
                                true
                            )
                    ) !== [];
                ?>

                <?php if ($hasActive) : ?>

                    <hr>

                    <h2>
                        Decisión de moderación
                    </h2>

                    <div
                        style="
                            display:grid;
                            grid-template-columns:
                                repeat(
                                    auto-fit,
                                    minmax(300px,1fr)
                                );
                            gap:20px;
                            max-width:900px;
                        "
                    >

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
                                    AdvertisementReportAdminController::
                                        ACTION_DISMISS
                                );
                                ?>"
                            >

                            <input
                                type="hidden"
                                name="advertisement_id"
                                value="<?php
                                echo esc_attr(
                                    (string)
                                    $advertisementId
                                );
                                ?>"
                            >

                            <?php
                            wp_nonce_field(
                                AdvertisementReportAdminController::
                                    getNonceAction(
                                        AdvertisementReportAdminController::
                                            ACTION_DISMISS,
                                        $advertisementId
                                    ),
                                AdvertisementReportAdminController::
                                    NONCE_FIELD
                            );
                            ?>

                            <h3>Ignorar denuncias</h3>

                            <p>
                                El anuncio seguirá publicado.
                                Las denuncias activas pasarán
                                al histórico de ignoradas.
                            </p>

                            <textarea
                                name="admin_notes"
                                rows="4"
                                class="large-text"
                                placeholder="Notas internas opcionales"
                            ></textarea>

                            <p>
                                <button
                                    type="submit"
                                    class="button"
                                >
                                    Ignorar denuncias
                                </button>
                            </p>
                        </form>

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
                                    AdvertisementReportAdminController::
                                        ACTION_REJECT
                                );
                                ?>"
                            >

                            <input
                                type="hidden"
                                name="advertisement_id"
                                value="<?php
                                echo esc_attr(
                                    (string)
                                    $advertisementId
                                );
                                ?>"
                            >

                            <?php
                            wp_nonce_field(
                                AdvertisementReportAdminController::
                                    getNonceAction(
                                        AdvertisementReportAdminController::
                                            ACTION_REJECT,
                                        $advertisementId
                                    ),
                                AdvertisementReportAdminController::
                                    NONCE_FIELD
                            );
                            ?>

                            <h3>Retirar anuncio</h3>

                            <p>
                                El anuncio se retirará del
                                marketplace por moderación.
                            </p>

                            <textarea
                                name="admin_notes"
                                rows="4"
                                class="large-text"
                                required
                                placeholder="Motivo obligatorio"
                            ></textarea>

                            <p>
                                <button
                                    type="submit"
                                    class="
                                        button
                                        button-primary
                                    "
                                    onclick="return confirm(
                                        '¿Retirar este anuncio?'
                                    );"
                                >
                                    Retirar anuncio
                                </button>
                            </p>

                        </form>

                    </div>

                <?php endif; ?>

            <?php endif; ?>

        </div>
        <?php
    }

    /**
     * @return array<string, mixed>|null
     */
    private function resolveCustomer(
        int $customerId
    ): ?array {
        if ($customerId <= 0) {
            return null;
        }

        $context =
            apply_filters(
                'dsm_customer_context_by_id',
                null,
                $customerId
            );

        return is_array($context)
            ? $context
            : null;
    }

    /**
     * @param array<string, mixed>|null $customer
     */
    private function getCustomerLabel(
        ?array $customer,
        int $customerId
    ): string {
        $displayName =
            is_array($customer)
                ? trim(
                    (string) (
                        $customer[
                            'display_name'
                        ]
                        ?? ''
                    )
                )
                : '';

        if ($displayName !== '') {
            return $displayName;
        }

        return $customerId > 0
            ? 'Cliente #' . $customerId
            : 'Cliente desconocido';
    }

    private function getAdvertisementPublicUrl(
        string $slug
    ): string {
        $slug =
            sanitize_title(
                $slug
            );

        if ($slug === '') {
            return '';
        }

        return home_url(
            '/anuncio/'
            . rawurlencode($slug)
            . '/'
        );
    }

    private function renderStatusBadge(
        string $status
    ): void {
        $status =
            sanitize_key(
                $status
            );

        $label =
            match ($status) {
                AdvertisementStatus::ACTIVE =>
                    'Activo',

                AdvertisementStatus::RESERVED =>
                    'Reservado',

                AdvertisementStatus::CLOSED =>
                    'Cerrado',

                AdvertisementStatus::DRAFT =>
                    'Borrador',

                AdvertisementStatus::PENDING =>
                    'Pendiente',

                AdvertisementStatus::REJECTED =>
                    'Rechazado',

                default =>
                    $status !== ''
                        ? $status
                        : '—',
            };

        printf(
            '<span class="dsm-admin-status dsm-admin-status--%1$s">%2$s</span>',
            esc_attr($status),
            esc_html($label)
        );
    }

    private function renderReportStatusBadge(
        string $status
    ): void {
        $status =
            sanitize_key(
                $status
            );

        $label =
            match ($status) {
                AdvertisementReport::STATUS_PENDING =>
                    'Pendiente',

                AdvertisementReport::STATUS_REVIEWING =>
                    'En revisión',

                AdvertisementReport::STATUS_DISMISSED =>
                    'Ignorada',

                AdvertisementReport::STATUS_ACTIONED =>
                    'Retirada',

                default =>
                    $status !== ''
                        ? $status
                        : '—',
            };

        printf(
            '<span class="dsm-admin-report-status dsm-admin-report-status--%1$s">%2$s</span>',
            esc_attr($status),
            esc_html($label)
        );
    }

    private function formatDate(
        string $date
    ): string {
        $date =
            trim(
                $date
            );

        if ($date === '') {
            return '—';
        }

        $timestamp =
            strtotime(
                $date . ' UTC'
            );

        if ($timestamp === false) {
            return $date;
        }

        return wp_date(
            'd/m/Y H:i',
            $timestamp
        );
    }

    private function renderNotice(): void
    {
        $status =
            isset($_GET['report_status'])
                ? sanitize_key(
                    wp_unslash(
                        (string) $_GET[
                            'report_status'
                        ]
                    )
                )
                : '';

        $messages = [
            'dismissed' =>
                [
                    'success',
                    'Las denuncias se han ignorado.',
                ],

            'rejected' =>
                [
                    'success',
                    'El anuncio se ha retirado por moderación.',
                ],

            'error' =>
                [
                    'error',
                    'No se pudo completar la acción.',
                ],
        ];

        if (!isset($messages[$status])) {
            return;
        }

        [
            $type,
            $message,
        ] = $messages[$status];

        ?>
        <div
            class="<?php
            echo esc_attr(
                'notice notice-'
                . $type
                . ' is-dismissible'
            );
            ?>"
        >
            <p>
                <?php echo esc_html($message); ?>
            </p>
        </div>
        <?php
    }
}
