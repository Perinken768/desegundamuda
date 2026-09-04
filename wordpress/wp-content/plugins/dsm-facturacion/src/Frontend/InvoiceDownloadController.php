<?php

declare(strict_types=1);

namespace DSM\Facturacion\Frontend;

use DSM\Clientes\Authentication\AuthenticatedCustomer;
use DSM\Clientes\Authentication\CustomerSessionRepository;
use DSM\Clientes\Customer\CustomerRepository;
use DSM\Clientes\Impersonation\CustomerImpersonationCookie;
use DSM\Facturacion\Invoice\Invoice;
use DSM\Facturacion\Invoice\InvoiceRepository;
use DSM\Facturacion\Pdf\InvoicePdfGenerator;
use RuntimeException;
use Throwable;

if (!defined('ABSPATH')) {
    exit;
}

final class InvoiceDownloadController
{
    private const ACTION =
        'dsm_facturacion_download_invoice';

    private const NONCE_ACTION =
        'dsm_facturacion_download_invoice';

    public static function register(): void
    {
        add_action(
            'admin_post_nopriv_'
                . self::ACTION,
            [
                self::class,
                'handleDownload',
            ]
        );

        add_action(
            'admin_post_'
                . self::ACTION,
            [
                self::class,
                'handleDownload',
            ]
        );
    }

    public static function handleDownload(): never
    {
        try {
            $invoiceId =
                isset($_GET['invoice_id'])
                    ? absint(
                        wp_unslash(
                            (string) $_GET[
                                'invoice_id'
                            ]
                        )
                    )
                    : 0;

            if ($invoiceId <= 0) {
                throw new RuntimeException(
                    'La factura solicitada no es válida.'
                );
            }

            $nonce =
                isset($_GET['_wpnonce'])
                    ? sanitize_text_field(
                        wp_unslash(
                            (string) $_GET[
                                '_wpnonce'
                            ]
                        )
                    )
                    : '';

            if (
                $nonce === ''
                || !wp_verify_nonce(
                    $nonce,
                    self::nonceAction(
                        $invoiceId
                    )
                )
            ) {
                throw new RuntimeException(
                    'La solicitud de descarga no es válida.'
                );
            }

            if (
                class_exists(
                    CustomerImpersonationCookie::class
                )
                && CustomerImpersonationCookie::isActive()
            ) {
                throw new RuntimeException(
                    'La descarga de facturas está bloqueada durante una sesión administrativa temporal.'
                );
            }

            $authenticatedCustomer =
                new AuthenticatedCustomer(
                    new CustomerSessionRepository(),
                    new CustomerRepository()
                );

            $customer =
                $authenticatedCustomer
                    ->resolve();

            if ($customer === null) {
                self::redirectToLogin();
            }

            $invoice =
                self::findOwnedInvoice(
                    $invoiceId,
                    $customer->getId()
                );

            if ($invoice === null) {
                self::forbidden();
            }

            $generator =
                new InvoicePdfGenerator();

            $path =
                $generator->getExistingPath(
                    $invoice->getId()
                );

            if ($path === null) {
                $path =
                    $generator->generate(
                        $invoice->getId()
                    );
            }

            if (
                !is_file($path)
                || !is_readable($path)
            ) {
                throw new RuntimeException(
                    'El PDF de la factura no está disponible.'
                );
            }

            self::streamPdf(
                $invoice,
                $path
            );
        } catch (Throwable $exception) {
            error_log(
                '[DSM Facturación] Error descargando factura: '
                . $exception->getMessage()
            );

            wp_die(
                esc_html__(
                    'No se ha podido descargar la factura.',
                    'dsm-facturacion'
                ),
                esc_html__(
                    'Factura no disponible',
                    'dsm-facturacion'
                ),
                [
                    'response' =>
                        400,
                ]
            );
        }
    }

    public static function getDownloadUrl(
        Invoice $invoice
    ): string {
        $url =
            add_query_arg(
                [
                    'action' =>
                        self::ACTION,

                    'invoice_id' =>
                        $invoice->getId(),
                ],
                admin_url(
                    'admin-post.php'
                )
            );

        return wp_nonce_url(
            $url,
            self::nonceAction(
                $invoice->getId()
            )
        );
    }

    public static function findOwnedInvoice(
        int $invoiceId,
        int $customerId
    ): ?Invoice {
        if (
            $invoiceId <= 0
            || $customerId <= 0
        ) {
            return null;
        }

        $repository =
            new InvoiceRepository();

        $invoice =
            $repository->findById(
                $invoiceId
            );

        if ($invoice === null) {
            return null;
        }

        if (
            $invoice->getCustomerId()
            !== $customerId
        ) {
            return null;
        }

        return $invoice;
    }

    public static function getAction(): string
    {
        return self::ACTION;
    }

    private static function nonceAction(
        int $invoiceId
    ): string {
        return self::NONCE_ACTION
            . '_'
            . $invoiceId;
    }

    private static function streamPdf(
        Invoice $invoice,
        string $path
    ): never {
        if (headers_sent()) {
            throw new RuntimeException(
                'Las cabeceras HTTP ya han sido enviadas.'
            );
        }

        while (
            ob_get_level() > 0
        ) {
            ob_end_clean();
        }

        $filename =
            sanitize_file_name(
                $invoice->getFullNumber()
                . '.pdf'
            );

        $size =
            filesize(
                $path
            );

        if ($size === false) {
            throw new RuntimeException(
                'No se pudo obtener el tamaño del PDF.'
            );
        }

        header(
            'Content-Type: application/pdf'
        );

        header(
            'Content-Disposition: attachment; filename="'
            . $filename
            . '"'
        );

        header(
            'Content-Length: '
            . $size
        );

        header(
            'X-Content-Type-Options: nosniff'
        );

        header(
            'Cache-Control: private, no-store, no-cache, must-revalidate, max-age=0'
        );

        header(
            'Pragma: no-cache'
        );

        header(
            'Expires: 0'
        );

        $result =
            readfile(
                $path
            );

        if ($result === false) {
            throw new RuntimeException(
                'No se pudo enviar el PDF.'
            );
        }

        exit;
    }

    private static function forbidden(): never
    {
        wp_die(
            esc_html__(
                'No tienes permiso para acceder a esta factura.',
                'dsm-facturacion'
            ),
            esc_html__(
                'Acceso denegado',
                'dsm-facturacion'
            ),
            [
                'response' =>
                    403,
            ]
        );
    }

    private static function redirectToLogin(): never
    {
        wp_safe_redirect(
            add_query_arg(
                'redirect_to',
                home_url(
                    '/mis-facturas/'
                ),
                home_url(
                    '/iniciar-sesion/'
                )
            )
        );

        exit;
    }

    private function __construct()
    {
    }
}
