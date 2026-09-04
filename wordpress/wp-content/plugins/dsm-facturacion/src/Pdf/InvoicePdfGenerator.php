<?php

declare(strict_types=1);

namespace DSM\Facturacion\Pdf;

use Dompdf\Dompdf;
use Dompdf\Options;
use RuntimeException;

if (!defined('ABSPATH')) {
    exit;
}

final class InvoicePdfGenerator
{
    private string $invoicesTable;

    private string $itemsTable;

    public function __construct()
    {
        global $wpdb;

        $this->invoicesTable =
            $wpdb->prefix
            . 'dsm_invoices';

        $this->itemsTable =
            $wpdb->prefix
            . 'dsm_invoice_items';
    }

    public function generate(
        int $invoiceId,
        bool $force = false
    ): string {
        global $wpdb;

        if ($invoiceId <= 0) {
            throw new RuntimeException(
                'La factura no es válida.'
            );
        }

        $invoice =
            $this->findInvoice(
                $invoiceId
            );

        if ($invoice === null) {
            throw new RuntimeException(
                'La factura no existe.'
            );
        }

        $existingPath =
            trim(
                (string) (
                    $invoice[
                        'pdf_relative_path'
                    ]
                    ?? ''
                )
            );

        if (
            !$force
            && $existingPath !== ''
        ) {
            $absoluteExisting =
                $this->absolutePath(
                    $existingPath
                );

            if (is_file($absoluteExisting)) {
                return $absoluteExisting;
            }
        }

        $items =
            $this->findItems(
                $invoiceId
            );

        if ($items === []) {
            throw new RuntimeException(
                'La factura no contiene líneas.'
            );
        }

        $design =
            $this->decodeDesignSnapshot(
                $invoice[
                    'design_snapshot'
                ]
                ?? null
            );

        $logoDataUri =
            $this->resolveLogoDataUri(
                (int) (
                    $design[
                        'logo_attachment_id'
                    ]
                    ?? 0
                )
            );

        $footerText =
            trim(
                (string) (
                    $design[
                        'footer_text'
                    ]
                    ?? ''
                )
            );

        $html =
            $this->renderHtml(
                $invoice,
                $items,
                $logoDataUri,
                $footerText
            );

        $options =
            new Options();

        $options->set(
            'defaultFont',
            'DejaVu Sans'
        );

        $options->set(
            'isRemoteEnabled',
            false
        );

        $options->set(
            'isHtml5ParserEnabled',
            true
        );

        $dompdf =
            new Dompdf(
                $options
            );

        $dompdf->loadHtml(
            $html,
            'UTF-8'
        );

        $dompdf->setPaper(
            'A4',
            'portrait'
        );

        $dompdf->render();

        $pdf =
            $dompdf->output();

        if (
            !is_string($pdf)
            || $pdf === ''
        ) {
            throw new RuntimeException(
                'Dompdf no generó contenido.'
            );
        }

        $issuedTimestamp =
            strtotime(
                (string) $invoice[
                    'issued_at'
                ]
                . ' UTC'
            );

        if ($issuedTimestamp === false) {
            throw new RuntimeException(
                'La fecha de la factura no es válida.'
            );
        }

        $year =
            gmdate(
                'Y',
                $issuedTimestamp
            );

        $filename =
            sanitize_file_name(
                (string) $invoice[
                    'full_number'
                ]
                . '.pdf'
            );

        $relativePath =
            $year
            . '/'
            . $filename;

        $absolutePath =
            $this->absolutePath(
                $relativePath
            );

        $directory =
            dirname(
                $absolutePath
            );

        if (
            !is_dir($directory)
            && !wp_mkdir_p($directory)
        ) {
            throw new RuntimeException(
                'No se pudo crear el directorio privado de facturas.'
            );
        }

        @chmod(
            $directory,
            02770
        );

        $written =
            file_put_contents(
                $absolutePath,
                $pdf,
                LOCK_EX
            );

        if (
            $written === false
            || $written <= 0
        ) {
            throw new RuntimeException(
                'No se pudo guardar el PDF de la factura.'
            );
        }

        @chmod(
            $absolutePath,
            0660
        );

        $updated =
            $wpdb->update(
                $this->invoicesTable,
                [
                    'pdf_relative_path' =>
                        $relativePath,

                    'pdf_generated_at' =>
                        current_time(
                            'mysql',
                            true
                        ),

                    'updated_at' =>
                        current_time(
                            'mysql',
                            true
                        ),
                ],
                [
                    'id' =>
                        $invoiceId,
                ]
            );

        if ($updated === false) {
            @unlink(
                $absolutePath
            );

            throw new RuntimeException(
                'El PDF se generó pero no se pudo registrar en la factura: '
                . $wpdb->last_error
            );
        }

        return $absolutePath;
    }

    public function getExistingPath(
        int $invoiceId
    ): ?string {
        $invoice =
            $this->findInvoice(
                $invoiceId
            );

        if ($invoice === null) {
            return null;
        }

        $relativePath =
            trim(
                (string) (
                    $invoice[
                        'pdf_relative_path'
                    ]
                    ?? ''
                )
            );

        if ($relativePath === '') {
            return null;
        }

        $absolutePath =
            $this->absolutePath(
                $relativePath
            );

        return is_file($absolutePath)
            ? $absolutePath
            : null;
    }

    public function getStorageRoot(): string
    {
        $default =
            dirname(
                untrailingslashit(
                    ABSPATH
                )
            )
            . '/storage/dsm-facturacion/invoices';

        $filtered =
            apply_filters(
                'dsm_facturacion_invoice_storage_path',
                $default
            );

        $path =
            rtrim(
                (string) $filtered,
                '/\\'
            );

        if ($path === '') {
            throw new RuntimeException(
                'La ruta privada de facturación está vacía.'
            );
        }

        return $path;
    }

    /**
     * @return array<string, mixed>|null
     */
    private function findInvoice(
        int $invoiceId
    ): ?array {
        global $wpdb;

        $row =
            $wpdb->get_row(
                $wpdb->prepare(
                    "
                    SELECT *
                    FROM {$this->invoicesTable}
                    WHERE id = %d
                    LIMIT 1
                    ",
                    $invoiceId
                ),
                ARRAY_A
            );

        return is_array($row)
            ? $row
            : null;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function findItems(
        int $invoiceId
    ): array {
        global $wpdb;

        $rows =
            $wpdb->get_results(
                $wpdb->prepare(
                    "
                    SELECT *
                    FROM {$this->itemsTable}
                    WHERE invoice_id = %d
                    ORDER BY sort_order ASC, id ASC
                    ",
                    $invoiceId
                ),
                ARRAY_A
            );

        return is_array($rows)
            ? $rows
            : [];
    }

    /**
     * @return array<string, mixed>
     */
    private function decodeDesignSnapshot(
        mixed $value
    ): array {
        if (
            !is_string($value)
            || trim($value) === ''
        ) {
            return [];
        }

        $decoded =
            json_decode(
                $value,
                true
            );

        return is_array($decoded)
            ? $decoded
            : [];
    }

    private function resolveLogoDataUri(
        int $attachmentId
    ): ?string {
        if ($attachmentId <= 0) {
            return null;
        }

        $path =
            get_attached_file(
                $attachmentId
            );

        if (
            !is_string($path)
            || $path === ''
            || !is_file($path)
        ) {
            return null;
        }

        $mime =
            get_post_mime_type(
                $attachmentId
            );

        if (
            !is_string($mime)
            || !in_array(
                $mime,
                [
                    'image/png',
                    'image/jpeg',
                    'image/webp',
                ],
                true
            )
        ) {
            return null;
        }

        $contents =
            file_get_contents(
                $path
            );

        if (
            !is_string($contents)
            || $contents === ''
        ) {
            return null;
        }

        return 'data:'
            . $mime
            . ';base64,'
            . base64_encode(
                $contents
            );
    }

    /**
     * @param array<string, mixed> $invoice
     * @param array<int, array<string, mixed>> $items
     */
    private function renderHtml(
        array $invoice,
        array $items,
        ?string $logoDataUri,
        string $footerText
    ): string {
        ob_start();

        $template =
            DSM_FACTURACION_PATH
            . 'templates/pdf/invoice.php';

        if (!is_file($template)) {
            throw new RuntimeException(
                'No existe la plantilla PDF de factura.'
            );
        }

        require $template;

        $html =
            ob_get_clean();

        if (
            !is_string($html)
            || $html === ''
        ) {
            throw new RuntimeException(
                'La plantilla PDF no generó contenido.'
            );
        }

        return $html;
    }

    private function absolutePath(
        string $relativePath
    ): string {
        $relativePath =
            ltrim(
                str_replace(
                    '\\',
                    '/',
                    $relativePath
                ),
                '/'
            );

        if (
            str_contains(
                $relativePath,
                '../'
            )
        ) {
            throw new RuntimeException(
                'La ruta del PDF no es válida.'
            );
        }

        return $this->getStorageRoot()
            . '/'
            . $relativePath;
    }
}
