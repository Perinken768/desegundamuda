<?php

declare(strict_types=1);

namespace DSM\Facturacion\Frontend;

use DSM\Clientes\Authentication\AuthenticatedCustomer;
use DSM\Clientes\Authentication\CustomerSessionRepository;
use DSM\Clientes\Customer\CustomerRepository;
use DSM\Facturacion\Invoice\InvoiceRepository;
use Throwable;

if (!defined('ABSPATH')) {
    exit;
}

final class InvoicesShortcode
{
    private const PER_PAGE =
        20;

    public static function register(): void
    {
        add_shortcode(
            'dsm_invoices',
            [
                self::class,
                'render',
            ]
        );
    }

    public static function render(): string
    {
        try {
            $authenticatedCustomer =
                new AuthenticatedCustomer(
                    new CustomerSessionRepository(),
                    new CustomerRepository()
                );

            $customer =
                $authenticatedCustomer
                    ->resolve();

            if ($customer === null) {
                return sprintf(
                    '<div class="dsm-invoices-login-required">'
                    . '<p>Debes iniciar sesión para consultar tus facturas.</p>'
                    . '<p><a class="dsm-button dsm-button--primary" href="%s">'
                    . 'Iniciar sesión'
                    . '</a></p>'
                    . '</div>',
                    esc_url(
                        home_url(
                            '/iniciar-sesion/'
                        )
                    )
                );
            }

            self::enqueueAssets();

            $repository =
                new InvoiceRepository();

            $allInvoices =
                $repository
                    ->findByCustomerId(
                        $customer->getId()
                    );

            $totalInvoices =
                count(
                    $allInvoices
                );

            $totalPages =
                max(
                    1,
                    (int) ceil(
                        $totalInvoices
                        / self::PER_PAGE
                    )
                );

            $currentPage =
                self::currentPage();

            if ($currentPage > $totalPages) {
                $currentPage =
                    $totalPages;
            }

            $offset =
                ($currentPage - 1)
                * self::PER_PAGE;

            $invoices =
                array_slice(
                    $allInvoices,
                    $offset,
                    self::PER_PAGE
                );

            $firstInvoiceNumber =
                $totalInvoices > 0
                    ? $offset + 1
                    : 0;

            $lastInvoiceNumber =
                $totalInvoices > 0
                    ? min(
                        $offset
                        + count($invoices),
                        $totalInvoices
                    )
                    : 0;

            ob_start();

            $template =
                DSM_FACTURACION_PATH
                . 'templates/account/invoices.php';

            if (!is_file($template)) {
                return '';
            }

            require $template;

            return (string) ob_get_clean();
        } catch (Throwable $exception) {
            error_log(
                '[DSM Facturación] Error mostrando las facturas del cliente: '
                . $exception->getMessage()
            );

            return '<p>No se han podido cargar tus facturas.</p>';
        }
    }

    private static function currentPage(): int
    {
        if (
            !isset(
                $_GET[
                    'facturas_page'
                ]
            )
        ) {
            return 1;
        }

        return max(
            1,
            absint(
                wp_unslash(
                    (string) $_GET[
                        'facturas_page'
                    ]
                )
            )
        );
    }

    private static function enqueueAssets(): void
    {
        wp_enqueue_style(
            'dsm-facturacion-invoices',
            DSM_FACTURACION_URL
                . 'assets/frontend/css/invoices.css',
            [],
            DSM_FACTURACION_VERSION
        );
    }

    private function __construct()
    {
    }
}
