<?php

declare(strict_types=1);

namespace DSM\Clientes\Admin;

use DSM\Clientes\Authentication\CustomerSessionRepository;

if (!defined('ABSPATH')) {
    exit;
}

final class CustomersPage
{
    private const MENU_SLUG = 'dsm-clientes';

    private const PER_PAGE = 20;

    private string $hookSuffix = '';

    public function __construct(
        private readonly CustomerAdminRepository $repository
    ) {
    }

    public function register(): void
    {
        add_action(
            'admin_menu',
            [$this, 'registerMenu']
        );

        add_action(
            'admin_enqueue_scripts',
            [$this, 'enqueueAssets']
        );
    }

    public function registerMenu(): void
    {
        $this->hookSuffix = (string) add_menu_page(
            __('DSM Clientes', 'dsm-clientes'),
            __('DSM Clientes', 'dsm-clientes'),
            'manage_options',
            self::MENU_SLUG,
            [$this, 'render'],
            'dashicons-groups',
            30
        );

        add_submenu_page(
            self::MENU_SLUG,
            __('Clientes', 'dsm-clientes'),
            __('Clientes', 'dsm-clientes'),
            'manage_options',
            self::MENU_SLUG,
            [$this, 'render']
        );
    }

    public function enqueueAssets(
        string $hookSuffix
    ): void {
        if ($hookSuffix !== $this->hookSuffix) {
            return;
        }

        $relativePath =
            'assets/admin/css/customers.css';

        $filePath =
            DSM_CLIENTES_PATH . $relativePath;

        $version = is_file($filePath)
            ? (string) filemtime($filePath)
            : DSM_CLIENTES_VERSION;

        wp_enqueue_style(
            'dsm-clientes-admin',
            DSM_CLIENTES_URL . $relativePath,
            [],
            $version
        );
    }

    public function render(): void
    {
        if (!current_user_can('manage_options')) {
            wp_die(
                esc_html__(
                    'No tienes permisos para acceder a esta página.',
                    'dsm-clientes'
                )
            );
        }

        $action = isset($_GET['action'])
            ? sanitize_key(
                wp_unslash($_GET['action'])
            )
            : '';

        if ($action === 'view') {
            $this->renderDetail();

            return;
        }

        $this->renderList();
    }

    private function renderDetail(): void
    {
        $customerId = isset($_GET['customer_id'])
            ? absint($_GET['customer_id'])
            : 0;

        $customer = $this->repository->findDetailById(
            $customerId
        );

        if ($customer === null) {
            wp_die(
                esc_html__(
                    'No se encontró el cliente solicitado.',
                    'dsm-clientes'
                )
            );
        }

        $sessionRepository =
            new CustomerSessionRepository();

        $sessions =
            $sessionRepository->findByCustomerId(
                $customerId
            );

        $adminStatus = isset($_GET['admin_status'])
            ? sanitize_key(
                wp_unslash($_GET['admin_status'])
            )
            : '';

        $templateFile = DSM_CLIENTES_PATH
            . 'templates/admin/customer-detail.php';

        require $templateFile;
    }

    private function renderList(): void
    {
        $currentPage = isset($_GET['paged'])
            ? max(
                1,
                absint($_GET['paged'])
            )
            : 1;

        $search = isset($_GET['s'])
            ? sanitize_text_field(
                wp_unslash($_GET['s'])
            )
            : '';

        $status = isset($_GET['customer_status'])
            ? sanitize_key(
                wp_unslash($_GET['customer_status'])
            )
            : '';

        $allowedStatuses = [
            '',
            'pending',
            'active',
            'suspended',
            'blocked',
        ];

        if (
            !in_array(
                $status,
                $allowedStatuses,
                true
            )
        ) {
            $status = '';
        }

        $customers = $this->repository->findAll(
            $currentPage,
            self::PER_PAGE,
            $search,
            $status
        );

        $totalItems = $this->repository->count(
            $search,
            $status
        );

        $totalPages = max(
            1,
            (int) ceil(
                $totalItems / self::PER_PAGE
            )
        );

        $counters =
            $this->repository->getCounters();

        $templateFile = DSM_CLIENTES_PATH
            . 'templates/admin/customers-list.php';

        require $templateFile;
    }
}