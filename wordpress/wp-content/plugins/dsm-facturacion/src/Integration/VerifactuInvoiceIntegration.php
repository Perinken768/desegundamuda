<?php

declare(strict_types=1);

namespace DSM\Facturacion\Integration;

use DSM\Facturacion\Invoice\Invoice;
use DSM\Facturacion\Verifactu\VerifactuRegistrationGenerator;
use DSM\Facturacion\Verifactu\VerifactuSubmissionPreparer;
use DSM\Facturacion\Verifactu\VerifactuSubmissionRepository;
use DSM\Facturacion\Verifactu\VerifactuXmlGenerator;
use RuntimeException;
use Throwable;

if (!defined('ABSPATH')) {
    exit;
}

final class VerifactuInvoiceIntegration
{
    public static function register(): void
    {
        add_action(
            'dsm_invoice_issued',
            [
                self::class,
                'handleInvoiceIssued',
            ],
            40,
            1
        );
    }

    /**
     * Integra una factura definitiva de DSM en
     * el pipeline VERI*FACTU.
     *
     * Flujo:
     *
     * factura
     *   -> RegistroAlta
     *   -> payload fiscal congelado
     *   -> submission
     *   -> cola VERI*FACTU
     *
     * La operación es idempotente:
     *
     * - RegistrationGenerator devuelve el registro
     *   existente si ya fue generado.
     *
     * - XmlGenerator reutiliza el payload congelado.
     *
     * - Si ya existe cualquier submission para ese
     *   registro, no creamos otra.
     *
     * Esta integración nunca debe romper el flujo
     * principal de facturación/pago.
     */
    public static function handleInvoiceIssued(
        Invoice $invoice
    ): void {
        $invoiceId =
            $invoice->getId();

        if ($invoiceId <= 0) {
            return;
        }

        try {
            /*
             * =============================================
             * 1. REGISTRO FISCAL
             * =============================================
             */
            $registrationGenerator =
                new VerifactuRegistrationGenerator();

            $record =
                $registrationGenerator
                    ->generateForInvoice(
                        $invoiceId
                    );

            $recordId =
                $record->getId();

            if ($recordId <= 0) {
                throw new RuntimeException(
                    sprintf(
                        'No se obtuvo un RegistroAlta VERI*FACTU válido para la factura %d.',
                        $invoiceId
                    )
                );
            }

            /*
             * =============================================
             * 2. PAYLOAD FISCAL CONGELADO
             * =============================================
             *
             * generate() es idempotente.
             * Si ya existe payload, devuelve exactamente
             * el mismo XML almacenado.
             */
            $xmlGenerator =
                new VerifactuXmlGenerator();

            $payloadXml =
                $xmlGenerator->generate(
                    $recordId
                );

            if (
                trim(
                    $payloadXml
                ) === ''
            ) {
                throw new RuntimeException(
                    sprintf(
                        'El RegistroAlta VERI*FACTU %d no generó payload XML.',
                        $recordId
                    )
                );
            }

            /*
             * =============================================
             * 3. IDEMPOTENCIA DE REMISION
             * =============================================
             *
             * Si ya hubo una submission:
             *
             * - pending
             * - sending
             * - sent
             * - transport_error
             * - response_error
             *
             * no creamos otra.
             *
             * Los retries pertenecen a la cola.
             */
            $submissionRepository =
                new VerifactuSubmissionRepository();

            $submission =
                $submissionRepository
                    ->findLatestForRecord(
                        $recordId
                    );

            if ($submission === null) {
                /*
                 * prepare() decide automáticamente si
                 * corresponde una remisión normal o una
                 * remisión con Incidencia=S.
                 */
                $submission =
                    (new VerifactuSubmissionPreparer())
                        ->prepare(
                            $recordId
                        );
            }

            do_action(
                'dsm_verifactu_invoice_prepared',
                $invoice,
                $record,
                $submission
            );
        } catch (Throwable $exception) {
            error_log(
                sprintf(
                    '[DSM Facturación][VERI*FACTU] No se pudo preparar la factura %d para VERI*FACTU: %s',
                    $invoiceId,
                    $exception->getMessage()
                )
            );

            do_action(
                'dsm_verifactu_invoice_prepare_failed',
                $invoice,
                $exception
            );
        }
    }

    private function __construct()
    {
    }
}
