<?php

declare(strict_types=1);

namespace DSM\Facturacion\Invoice;

use DSM\Facturacion\Billing\BillingProfileRepository;
use DSM\Facturacion\Billing\BillingSettings;
use DSM\Pagos\Payment\Payment;
use DSM\Pagos\Payment\PaymentRepository;
use DSM\Pagos\Payment\PaymentStatus;
use RuntimeException;

if (!defined('ABSPATH')) {
    exit;
}

final class InvoiceIssuer
{
    private PaymentRepository $paymentRepository;

    private BillingProfileRepository $billingProfileRepository;

    private InvoiceRepository $invoiceRepository;

    private InvoicePaymentContextResolver $contextResolver;

    public function __construct(
        ?PaymentRepository $paymentRepository = null,
        ?BillingProfileRepository $billingProfileRepository = null,
        ?InvoiceRepository $invoiceRepository = null,
        ?InvoicePaymentContextResolver $contextResolver = null
    ) {
        $this->paymentRepository =
            $paymentRepository
            ?? new PaymentRepository();

        $this->billingProfileRepository =
            $billingProfileRepository
            ?? new BillingProfileRepository();

        $this->invoiceRepository =
            $invoiceRepository
            ?? new InvoiceRepository();

        $this->contextResolver =
            $contextResolver
            ?? new InvoicePaymentContextResolver();
    }

    /**
     * Prepara todos los datos de una factura,
     * pero NO la guarda.
     *
     * @return array{
     *     invoice: array<string, mixed>,
     *     items: array<int, array<string, mixed>>
     * }
     */
    public function prepareByPaymentId(
        int $paymentId
    ): array {
        if ($paymentId <= 0) {
            throw new RuntimeException(
                'El identificador del pago no es válido.'
            );
        }

        $payment =
            $this->paymentRepository
                ->findById(
                    $paymentId
                );

        if ($payment === null) {
            throw new RuntimeException(
                sprintf(
                    'No existe el pago %d.',
                    $paymentId
                )
            );
        }

        return $this->prepare(
            $payment
        );
    }

    public function issueByPaymentId(
        int $paymentId
    ): Invoice {
        /*
         * Idempotencia:
         *
         * Si ya existe una factura para ese pago,
         * simplemente devolvemos la existente.
         */
        $existing =
            $this->invoiceRepository
                ->findByPaymentId(
                    $paymentId
                );

        if ($existing !== null) {
            return $existing;
        }

        $prepared =
            $this->prepareByPaymentId(
                $paymentId
            );

        return $this->invoiceRepository
            ->create(
                $prepared['invoice'],
                $prepared['items']
            );
    }

    /**
     * @return array{
     *     invoice: array<string, mixed>,
     *     items: array<int, array<string, mixed>>
     * }
     */
    private function prepare(
        Payment $payment
    ): array {
        if (
            $payment->getStatus()
            !== PaymentStatus::PAID
        ) {
            throw new RuntimeException(
                sprintf(
                    'El pago %d no está confirmado.',
                    $payment->getId()
                )
            );
        }

        if ($payment->getAmount() <= 0) {
            throw new RuntimeException(
                'No se emiten facturas para pagos sin importe.'
            );
        }

        if (!BillingSettings::isReady()) {
            throw new RuntimeException(
                'La configuración fiscal de DSM está incompleta.'
            );
        }

        $settings =
            BillingSettings::get();

        $billingProfile =
            $this->billingProfileRepository
                ->findByCustomerId(
                    $payment->getCustomerId()
                );

        if ($billingProfile === null) {
            throw new RuntimeException(
                sprintf(
                    'El cliente %d no tiene datos fiscales configurados.',
                    $payment->getCustomerId()
                )
            );
        }

        if (!$billingProfile->isComplete()) {
            throw new RuntimeException(
                sprintf(
                    'Los datos fiscales del cliente %d están incompletos.',
                    $payment->getCustomerId()
                )
            );
        }

        $taxRate =
            (float) (
                $settings['tax_rate']
                ?? 7.0
            );

        $pricesIncludeTax =
            !empty(
                $settings[
                    'prices_include_tax'
                ]
            );

        /*
         * El MVP trabaja con precios publicados
         * con IGIC incluido.
         */
        if (!$pricesIncludeTax) {
            throw new RuntimeException(
                'El MVP de DSM requiere precios con impuestos incluidos.'
            );
        }

        $tax =
            TaxCalculator::fromTaxIncludedTotal(
                $payment->getAmount(),
                $taxRate
            );

        $context =
            $this->contextResolver
                ->resolve(
                    $payment
                );

        $issuedAt =
            $payment->getPaidAt();

        if ($issuedAt === null) {
            throw new RuntimeException(
                sprintf(
                    'El pago %d está marcado como pagado pero no tiene fecha de pago.',
                    $payment->getId()
                )
            );
        }

        $issuedAtUtc =
            $issuedAt
                ->setTimezone(
                    new \DateTimeZone(
                        'UTC'
                    )
                )
                ->format(
                    'Y-m-d H:i:s'
                );

        $invoice = [
            'customer_id' =>
                $payment->getCustomerId(),

            'payment_id' =>
                $payment->getId(),

            'document_type' =>
                'invoice',

            'status' =>
                'issued',

            'series' =>
                (string) $settings[
                    'series'
                ],

            'issued_at' =>
                $issuedAtUtc,

            'currency' =>
                $payment->getCurrency(),

            'prices_include_tax' =>
                1,

            'subtotal' =>
                $tax['subtotal'],

            'tax_total' =>
                $tax['tax_amount'],

            'total' =>
                $tax['total'],

            'tax_type' =>
                (string) $settings[
                    'tax_type'
                ],

            'tax_rate' =>
                $taxRate,

            'tax_exemption_code' =>
                null,

            'tax_exemption_reason' =>
                null,

            /*
             * Snapshot del emisor.
             */
            'seller_fiscal_name' =>
                (string) $settings[
                    'fiscal_name'
                ],

            'seller_tax_id' =>
                (string) $settings[
                    'tax_id'
                ],

            'seller_address_line_1' =>
                (string) $settings[
                    'address_line_1'
                ],

            'seller_address_line_2' =>
                (string) $settings[
                    'address_line_2'
                ],

            'seller_postal_code' =>
                (string) $settings[
                    'postal_code'
                ],

            'seller_city' =>
                (string) $settings[
                    'city'
                ],

            'seller_province' =>
                (string) $settings[
                    'province'
                ],

            'seller_country_code' =>
                (string) $settings[
                    'country_code'
                ],

            'seller_email' =>
                (string) $settings[
                    'email'
                ],

            /*
             * Snapshot del receptor.
             */
            'customer_type' =>
                $billingProfile
                    ->getCustomerType(),

            'customer_fiscal_name' =>
                $billingProfile
                    ->getFiscalName(),

            'customer_tax_id' =>
                $billingProfile
                    ->getTaxId(),

            'customer_address_line_1' =>
                $billingProfile
                    ->getAddressLine1(),

            'customer_address_line_2' =>
                $billingProfile
                    ->getAddressLine2(),

            'customer_postal_code' =>
                $billingProfile
                    ->getPostalCode(),

            'customer_city' =>
                $billingProfile
                    ->getCity(),

            'customer_province' =>
                $billingProfile
                    ->getProvince(),

            'customer_country_code' =>
                $billingProfile
                    ->getCountryCode(),

            'customer_email' =>
                $billingProfile
                    ->getBillingEmail(),

            /*
             * Snapshot del pago.
             */
            'payment_provider' =>
                $payment->getProvider(),

            'payment_provider_reference' =>
                $payment
                    ->getProviderReference(),

            'payment_purpose' =>
                $payment->getPurpose(),

            'payment_source_type' =>
                $payment->getSourceType(),

            'payment_source_id' =>
                $payment->getSourceId(),

            'payment_source_reference' =>
                $payment
                    ->getSourceReference(),

            /*
             * Snapshot del diseño.
             *
             * El PDF futuro podrá reconstruirse usando
             * exactamente la configuración existente
             * cuando se emitió la factura.
             */
            'design_snapshot' => [
                'logo_attachment_id' =>
                    (int) (
                        $settings[
                            'logo_attachment_id'
                        ]
                        ?? 0
                    ),

                'footer_text' =>
                    (string) (
                        $settings[
                            'footer_text'
                        ]
                        ?? ''
                    ),
            ],
        ];

        $items = [
            [
                'description' =>
                    $context[
                        'description'
                    ],

                'quantity' =>
                    1.0000,

                /*
                 * Como el precio publicado incluye IGIC,
                 * guardamos como precio unitario el total
                 * final contratado.
                 */
                'unit_price' =>
                    $tax['total'],

                'line_subtotal' =>
                    $tax['subtotal'],

                'tax_type' =>
                    (string) $settings[
                        'tax_type'
                    ],

                'tax_rate' =>
                    $taxRate,

                'tax_base' =>
                    $tax['subtotal'],

                'tax_amount' =>
                    $tax['tax_amount'],

                'line_total' =>
                    $tax['total'],

                'sort_order' =>
                    0,
            ],
        ];

        return [
            'invoice' =>
                $invoice,

            'items' =>
                $items,
        ];
    }
}
