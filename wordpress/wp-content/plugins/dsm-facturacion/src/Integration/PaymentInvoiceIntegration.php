<?php

declare(strict_types=1);

namespace DSM\Facturacion\Integration;

use DSM\Facturacion\Invoice\InvoiceIssuer;
use DSM\Pagos\Payment\Payment;
use Throwable;

if (!defined('ABSPATH')) {
    exit;
}

final class PaymentInvoiceIntegration
{
    public static function register(): void
    {
        add_action(
            'dsm_payment_paid',
            [
                self::class,
                'handlePaymentPaid',
            ],
            30,
            1
        );
    }

    public static function handlePaymentPaid(
        Payment $payment
    ): void {
        $paymentId =
            $payment->getId();

        if ($paymentId <= 0) {
            return;
        }

        /*
         * Facturación nunca debe impedir que el flujo
         * principal del pago continúe.
         *
         * Si faltan datos fiscales o se produce cualquier
         * incidencia, registramos el error. La factura
         * podrá emitirse posteriormente.
         */
        try {
            $issuer =
                new InvoiceIssuer();

            $invoice =
                $issuer->issueByPaymentId(
                    $paymentId
                );

            do_action(
                'dsm_invoice_issued',
                $invoice,
                $payment
            );
        } catch (Throwable $exception) {
            error_log(
                sprintf(
                    '[DSM Facturación] No se pudo emitir la factura del pago %d: %s',
                    $paymentId,
                    $exception->getMessage()
                )
            );

            do_action(
                'dsm_invoice_issue_failed',
                $payment,
                $exception
            );
        }
    }

    private function __construct()
    {
    }
}
