<?php

declare(strict_types=1);

namespace DSM\Pagos\Webhook;

use DSM\Pagos\Application\ConfirmPayment;
use DSM\Pagos\Payment\Payment;
use DSM\Pagos\Payment\PaymentRepository;
use RuntimeException;
use Stripe\StripeClient;
use Stripe\Webhook;
use Throwable;
use WP_REST_Request;
use WP_REST_Response;

if (!defined('ABSPATH')) {
    exit;
}

final class StripeWebhookController
{
    private const REST_NAMESPACE =
        'dsm-pagos/v1';

    private const REST_ROUTE =
        '/stripe/webhook';

    private const SUBSCRIPTION_COMPLETED_ACTION =
        'dsm_stripe_subscription_checkout_completed';

    private const INVOICE_PAID_ACTION =
        'dsm_stripe_subscription_invoice_paid';

    private const INVOICE_PAYMENT_FAILED_ACTION =
        'dsm_stripe_subscription_invoice_payment_failed';

    private const SUBSCRIPTION_UPDATED_ACTION =
        'dsm_stripe_subscription_updated';

    private const SUBSCRIPTION_DELETED_ACTION =
        'dsm_stripe_subscription_deleted';

    public static function register(): void
    {
        add_action(
            'rest_api_init',
            [
                self::class,
                'registerRoutes',
            ]
        );
    }

    public static function registerRoutes(): void
    {
        register_rest_route(
            self::REST_NAMESPACE,
            self::REST_ROUTE,
            [
                'methods' =>
                    'POST',

                'callback' =>
                    [
                        self::class,
                        'handle',
                    ],

                'permission_callback' =>
                    '__return_true',
            ]
        );
    }

    public static function handle(
        WP_REST_Request $request
    ): WP_REST_Response {
        try {
            $payload =
                $request->get_body();

            if ($payload === '') {
                throw new RuntimeException(
                    'El webhook de Stripe no contiene payload.'
                );
            }

            $signature =
                trim(
                    (string) $request->get_header(
                        'stripe-signature'
                    )
                );

            if ($signature === '') {
                throw new RuntimeException(
                    'Falta la firma del webhook de Stripe.'
                );
            }

            $event =
                Webhook::constructEvent(
                    $payload,
                    $signature,
                    self::getWebhookSecret()
                );

            return match (
                (string) $event->type
            ) {
                'checkout.session.completed' =>
                    self::processCheckoutCompleted(
                        $event->data->object
                    ),

                'invoice.paid' =>
                    self::processInvoicePaid(
                        $event->data->object
                    ),

                'invoice.payment_failed' =>
                    self::processInvoicePaymentFailed(
                        $event->data->object
                    ),

                'customer.subscription.updated' =>
                    self::processSubscriptionUpdated(
                        $event->data->object
                    ),

                'customer.subscription.deleted' =>
                    self::processSubscriptionDeleted(
                        $event->data->object
                    ),

                default =>
                    new WP_REST_Response(
                        [
                            'received' =>
                                true,

                            'processed' =>
                                false,

                            'event_type' =>
                                $event->type,
                        ],
                        200
                    ),
            };
        } catch (Throwable $exception) {
            error_log(
                sprintf(
                    '[DSM Pagos][Stripe Webhook] %s',
                    $exception->getMessage()
                )
            );

            return new WP_REST_Response(
                [
                    'received' =>
                        false,

                    'error' =>
                        $exception->getMessage(),
                ],
                400
            );
        }
    }

    private static function processCheckoutCompleted(
        object $session
    ): WP_REST_Response {
        $mode =
            strtolower(
                trim(
                    (string) (
                        $session->mode
                        ?? 'payment'
                    )
                )
            );

        if (
            !in_array(
                $mode,
                [
                    'payment',
                    'subscription',
                ],
                true
            )
        ) {
            return new WP_REST_Response(
                [
                    'received' =>
                        true,

                    'processed' =>
                        false,

                    'reason' =>
                        'unsupported_checkout_mode',

                    'mode' =>
                        $mode,
                ],
                200
            );
        }

        $paymentStatus =
            strtolower(
                trim(
                    (string) (
                        $session->payment_status
                        ?? ''
                    )
                )
            );

        if ($paymentStatus !== 'paid') {
            return new WP_REST_Response(
                [
                    'received' =>
                        true,

                    'processed' =>
                        false,

                    'reason' =>
                        'payment_not_paid',

                    'mode' =>
                        $mode,
                ],
                200
            );
        }

        $paymentId =
            self::extractPaymentId(
                $session
            );

        $paymentRepository =
            new PaymentRepository();

        $payment =
            $paymentRepository
                ->findById(
                    $paymentId
                );

        if ($payment === null) {
            throw new RuntimeException(
                'No se encontró el pago DSM asociado.'
            );
        }

        self::validateAmountAndCurrency(
            $session,
            $payment
        );

        $providerReference =
            trim(
                (string) (
                    $session->id
                    ?? ''
                )
            );

        if ($providerReference === '') {
            throw new RuntimeException(
                'Stripe no devolvió una referencia de sesión válida.'
            );
        }

        $confirmPayment =
            new ConfirmPayment();

        $confirmedPayment =
            $confirmPayment->execute(
                $paymentId,
                'stripe',
                $providerReference
            );

        if ($mode === 'subscription') {
            $subscriptionData =
                self::resolveStripeSubscriptionData(
                    $session
                );

            do_action(
                self::SUBSCRIPTION_COMPLETED_ACTION,
                $confirmedPayment,
                $subscriptionData
            );

            return new WP_REST_Response(
                [
                    'received' =>
                        true,

                    'processed' =>
                        true,

                    'mode' =>
                        'subscription',

                    'payment_id' =>
                        $confirmedPayment
                            ->getId(),

                    'status' =>
                        $confirmedPayment
                            ->getStatus(),

                    'stripe_subscription_id' =>
                        $subscriptionData[
                            'subscription_id'
                        ],

                    'period_start' =>
                        $subscriptionData[
                            'period_start'
                        ],

                    'period_end' =>
                        $subscriptionData[
                            'period_end'
                        ],
                ],
                200
            );
        }

        return new WP_REST_Response(
            [
                'received' =>
                    true,

                'processed' =>
                    true,

                'mode' =>
                    'payment',

                'payment_id' =>
                    $confirmedPayment
                        ->getId(),

                'status' =>
                    $confirmedPayment
                        ->getStatus(),
            ],
            200
        );
    }

    private static function processInvoicePaid(
        object $invoice
    ): WP_REST_Response {
        $data =
            self::normalizeSubscriptionInvoice(
                $invoice,
                true
            );

        if ($data === null) {
            return new WP_REST_Response(
                [
                    'received' =>
                        true,

                    'processed' =>
                        false,

                    'reason' =>
                        'invoice_not_from_subscription',
                ],
                200
            );
        }

        do_action(
            self::INVOICE_PAID_ACTION,
            $data
        );

        return new WP_REST_Response(
            [
                'received' =>
                    true,

                'processed' =>
                    true,

                'event_type' =>
                    'invoice.paid',

                'invoice_id' =>
                    $data['invoice_id'],

                'stripe_subscription_id' =>
                    $data['subscription_id'],
            ],
            200
        );
    }

    private static function processInvoicePaymentFailed(
        object $invoice
    ): WP_REST_Response {
        $data =
            self::normalizeSubscriptionInvoice(
                $invoice,
                false
            );

        if ($data === null) {
            return new WP_REST_Response(
                [
                    'received' =>
                        true,

                    'processed' =>
                        false,

                    'reason' =>
                        'invoice_not_from_subscription',
                ],
                200
            );
        }

        do_action(
            self::INVOICE_PAYMENT_FAILED_ACTION,
            $data
        );

        return new WP_REST_Response(
            [
                'received' =>
                    true,

                'processed' =>
                    true,

                'event_type' =>
                    'invoice.payment_failed',

                'invoice_id' =>
                    $data['invoice_id'],

                'stripe_subscription_id' =>
                    $data['subscription_id'],
            ],
            200
        );
    }

    private static function processSubscriptionUpdated(
        object $stripeSubscription
    ): WP_REST_Response {
        $data =
            self::normalizeStripeSubscription(
                $stripeSubscription
            );

        do_action(
            self::SUBSCRIPTION_UPDATED_ACTION,
            $data
        );

        return new WP_REST_Response(
            [
                'received' =>
                    true,

                'processed' =>
                    true,

                'event_type' =>
                    'customer.subscription.updated',

                'stripe_subscription_id' =>
                    $data['subscription_id'],

                'stripe_status' =>
                    $data['status'],

                'cancel_at_period_end' =>
                    $data['cancel_at_period_end'],
            ],
            200
        );
    }

    private static function processSubscriptionDeleted(
        object $stripeSubscription
    ): WP_REST_Response {
        $data =
            self::normalizeStripeSubscription(
                $stripeSubscription
            );

        do_action(
            self::SUBSCRIPTION_DELETED_ACTION,
            $data
        );

        return new WP_REST_Response(
            [
                'received' =>
                    true,

                'processed' =>
                    true,

                'event_type' =>
                    'customer.subscription.deleted',

                'stripe_subscription_id' =>
                    $data['subscription_id'],

                'stripe_status' =>
                    $data['status'],
            ],
            200
        );
    }

    /**
     * @return array<string, mixed>
     */
    private static function normalizeStripeSubscription(
        object $subscription
    ): array {
        $subscriptionId =
            self::extractStripeId(
                $subscription
            );

        if ($subscriptionId === '') {
            throw new RuntimeException(
                'Stripe no devolvió un identificador de suscripción válido.'
            );
        }

        $status =
            strtolower(
                trim(
                    (string) (
                        $subscription->status
                        ?? ''
                    )
                )
            );

        $cancelAtPeriodEnd =
            (bool) (
                $subscription
                    ->cancel_at_period_end
                ?? false
            );

        $cancelledAtTimestamp =
            isset(
                $subscription->canceled_at
            )
                ? (int) $subscription
                    ->canceled_at
                : 0;

        $periodStart =
            null;

        $periodEnd =
            null;

        try {
            [
                $periodStartTimestamp,
                $periodEndTimestamp,
            ] =
                self::extractBillingPeriod(
                    $subscription
                );

            $periodStart =
                gmdate(
                    'Y-m-d H:i:s',
                    $periodStartTimestamp
                );

            $periodEnd =
                gmdate(
                    'Y-m-d H:i:s',
                    $periodEndTimestamp
                );
        } catch (Throwable) {
            /*
             * En determinados eventos terminales Stripe
             * puede no proporcionar ya un período útil.
             *
             * No impedimos por ello procesar una baja.
             */
        }

        $metadata =
            $subscription->metadata
            ?? null;

        $customerId =
            isset(
                $metadata->dsm_customer_id
            )
                ? (int) $metadata
                    ->dsm_customer_id
                : 0;

        $planId =
            isset(
                $metadata->dsm_plan_id
            )
                ? (int) $metadata
                    ->dsm_plan_id
                : 0;

        $initialPaymentId =
            isset(
                $metadata->dsm_initial_payment_id
            )
                ? (int) $metadata
                    ->dsm_initial_payment_id
                : 0;

        return [
            'provider' =>
                'stripe',

            'subscription_id' =>
                $subscriptionId,

            'status' =>
                $status,

            'cancel_at_period_end' =>
                $cancelAtPeriodEnd,

            'cancelled_at' =>
                $cancelledAtTimestamp > 0
                    ? gmdate(
                        'Y-m-d H:i:s',
                        $cancelledAtTimestamp
                    )
                    : null,

            'period_start' =>
                $periodStart,

            'period_end' =>
                $periodEnd,

            'customer_id' =>
                $customerId,

            'plan_id' =>
                $planId,

            'initial_payment_id' =>
                $initialPaymentId,
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    private static function normalizeSubscriptionInvoice(
        object $invoice,
        bool $paid
    ): ?array {
        $invoiceId =
            trim(
                (string) (
                    $invoice->id
                    ?? ''
                )
            );

        if ($invoiceId === '') {
            throw new RuntimeException(
                'Stripe no devolvió un identificador de factura válido.'
            );
        }

        $subscriptionId =
            self::extractInvoiceSubscriptionId(
                $invoice
            );

        if ($subscriptionId === '') {
            return null;
        }

        $metadata =
            self::extractInvoiceSubscriptionMetadata(
                $invoice
            );

        $initialPaymentId =
            isset(
                $metadata->dsm_initial_payment_id
            )
                ? (int) $metadata
                    ->dsm_initial_payment_id
                : 0;

        $customerId =
            isset(
                $metadata->dsm_customer_id
            )
                ? (int) $metadata
                    ->dsm_customer_id
                : 0;

        $planId =
            isset(
                $metadata->dsm_plan_id
            )
                ? (int) $metadata
                    ->dsm_plan_id
                : 0;

        $planCode =
            isset(
                $metadata->dsm_plan_code
            )
                ? sanitize_key(
                    (string) $metadata
                        ->dsm_plan_code
                )
                : '';

        [
            $periodStart,
            $periodEnd,
        ] =
            self::extractInvoiceServicePeriod(
                $invoice
            );

        $currency =
            strtoupper(
                trim(
                    (string) (
                        $invoice->currency
                        ?? ''
                    )
                )
            );

        if (
            strlen($currency) !== 3
            || !ctype_alpha($currency)
        ) {
            throw new RuntimeException(
                'Stripe no devolvió una moneda válida para la factura.'
            );
        }

        $amountInCents =
            $paid
                ? (
                    isset($invoice->amount_paid)
                        ? (int) $invoice
                            ->amount_paid
                        : 0
                )
                : (
                    isset($invoice->amount_due)
                        ? (int) $invoice
                            ->amount_due
                        : 0
                );

        if ($amountInCents < 0) {
            throw new RuntimeException(
                'Stripe devolvió un importe de factura no válido.'
            );
        }

        return [
            'provider' =>
                'stripe',

            'invoice_id' =>
                $invoiceId,

            'subscription_id' =>
                $subscriptionId,

            'initial_payment_id' =>
                $initialPaymentId,

            'customer_id' =>
                $customerId,

            'plan_id' =>
                $planId,

            'plan_code' =>
                $planCode,

            'billing_reason' =>
                trim(
                    (string) (
                        $invoice->billing_reason
                        ?? ''
                    )
                ),

            'amount' =>
                $amountInCents / 100,

            'currency' =>
                $currency,

            'period_start' =>
                $periodStart !== null
                    ? gmdate(
                        'Y-m-d H:i:s',
                        $periodStart
                    )
                    : null,

            'period_end' =>
                $periodEnd !== null
                    ? gmdate(
                        'Y-m-d H:i:s',
                        $periodEnd
                    )
                    : null,
        ];
    }

    private static function extractInvoiceSubscriptionId(
        object $invoice
    ): string {
        $parent =
            $invoice->parent
            ?? null;

        if (
            is_object($parent)
            && (
                (string) (
                    $parent->type
                    ?? ''
                )
                === 'subscription_details'
            )
        ) {
            $subscriptionDetails =
                $parent->subscription_details
                ?? null;

            if (
                is_object(
                    $subscriptionDetails
                )
            ) {
                $subscriptionId =
                    self::extractStripeId(
                        $subscriptionDetails
                            ->subscription
                        ?? null
                    );

                if ($subscriptionId !== '') {
                    return $subscriptionId;
                }
            }
        }

        return self::extractStripeId(
            $invoice->subscription
            ?? null
        );
    }

    private static function extractInvoiceSubscriptionMetadata(
        object $invoice
    ): object {
        $parent =
            $invoice->parent
            ?? null;

        if (
            is_object($parent)
            && (
                (string) (
                    $parent->type
                    ?? ''
                )
                === 'subscription_details'
            )
        ) {
            $subscriptionDetails =
                $parent->subscription_details
                ?? null;

            if (
                is_object(
                    $subscriptionDetails
                )
                && isset(
                    $subscriptionDetails->metadata
                )
                && is_object(
                    $subscriptionDetails->metadata
                )
            ) {
                return $subscriptionDetails
                    ->metadata;
            }
        }

        $subscriptionDetails =
            $invoice->subscription_details
            ?? null;

        if (
            is_object(
                $subscriptionDetails
            )
            && isset(
                $subscriptionDetails->metadata
            )
            && is_object(
                $subscriptionDetails->metadata
            )
        ) {
            return $subscriptionDetails
                ->metadata;
        }

        return new \stdClass();
    }

    /**
     * @return array{0: ?int, 1: ?int}
     */
    private static function extractInvoiceServicePeriod(
        object $invoice
    ): array {
        $lines =
            $invoice->lines
            ?? null;

        $lineData =
            is_object($lines)
                ? (
                    $lines->data
                    ?? null
                )
                : null;

        if (!is_array($lineData)) {
            return [
                null,
                null,
            ];
        }

        $fallback =
            null;

        foreach ($lineData as $line) {
            if (!is_object($line)) {
                continue;
            }

            $period =
                $line->period
                ?? null;

            if (!is_object($period)) {
                continue;
            }

            $start =
                isset($period->start)
                    ? (int) $period->start
                    : 0;

            $end =
                isset($period->end)
                    ? (int) $period->end
                    : 0;

            if (
                $start <= 0
                || $end <= $start
            ) {
                continue;
            }

            if ($fallback === null) {
                $fallback = [
                    $start,
                    $end,
                ];
            }

            $parent =
                $line->parent
                ?? null;

            if (
                is_object($parent)
                && (
                    (string) (
                        $parent->type
                        ?? ''
                    )
                    === 'subscription_item_details'
                )
            ) {
                return [
                    $start,
                    $end,
                ];
            }

            $legacySubscriptionId =
                self::extractStripeId(
                    $line->subscription
                    ?? null
                );

            if ($legacySubscriptionId !== '') {
                return [
                    $start,
                    $end,
                ];
            }
        }

        if ($fallback !== null) {
            return $fallback;
        }

        return [
            null,
            null,
        ];
    }

    private static function extractPaymentId(
        object $session
    ): int {
        $metadata =
            $session->metadata
            ?? null;

        $paymentId =
            isset(
                $metadata->dsm_payment_id
            )
                ? (int) $metadata
                    ->dsm_payment_id
                : 0;

        if ($paymentId <= 0) {
            throw new RuntimeException(
                'Stripe no devolvió un payment_id DSM válido.'
            );
        }

        return $paymentId;
    }

    private static function validateAmountAndCurrency(
        object $session,
        Payment $payment
    ): void {
        $amountTotal =
            isset($session->amount_total)
                ? (int) $session->amount_total
                : -1;

        $expectedAmount =
            (int) round(
                $payment->getAmount()
                * 100
            );

        if (
            $amountTotal < 0
            || $amountTotal !== $expectedAmount
        ) {
            throw new RuntimeException(
                'El importe recibido desde Stripe no coincide con el pago DSM.'
            );
        }

        $stripeCurrency =
            strtolower(
                trim(
                    (string) (
                        $session->currency
                        ?? ''
                    )
                )
            );

        $expectedCurrency =
            strtolower(
                $payment->getCurrency()
            );

        if (
            $stripeCurrency === ''
            || $stripeCurrency
                !== $expectedCurrency
        ) {
            throw new RuntimeException(
                'La moneda recibida desde Stripe no coincide con el pago DSM.'
            );
        }
    }

    /**
     * @return array{
     *     provider: string,
     *     subscription_id: string,
     *     invoice_id: ?string,
     *     period_start: string,
     *     period_end: string
     * }
     */
    private static function resolveStripeSubscriptionData(
        object $session
    ): array {
        $subscriptionId =
            self::extractStripeId(
                $session->subscription
                ?? null
            );

        if ($subscriptionId === '') {
            throw new RuntimeException(
                'Stripe no devolvió el identificador de la suscripción.'
            );
        }

        $invoiceId =
            self::extractStripeId(
                $session->invoice
                ?? null
            );

        try {
            $stripe =
                new StripeClient(
                    self::getSecretKey()
                );

            $stripeSubscription =
                $stripe
                    ->subscriptions
                    ->retrieve(
                        $subscriptionId,
                        []
                    );
        } catch (Throwable $exception) {
            throw new RuntimeException(
                sprintf(
                    'No se pudo recuperar la suscripción de Stripe: %s',
                    $exception->getMessage()
                ),
                0,
                $exception
            );
        }

        [
            $periodStartTimestamp,
            $periodEndTimestamp,
        ] =
            self::extractBillingPeriod(
                $stripeSubscription
            );

        return [
            'provider' =>
                'stripe',

            'subscription_id' =>
                $subscriptionId,

            'invoice_id' =>
                $invoiceId !== ''
                    ? $invoiceId
                    : null,

            'period_start' =>
                gmdate(
                    'Y-m-d H:i:s',
                    $periodStartTimestamp
                ),

            'period_end' =>
                gmdate(
                    'Y-m-d H:i:s',
                    $periodEndTimestamp
                ),
        ];
    }

    /**
     * @return array{0: int, 1: int}
     */
    private static function extractBillingPeriod(
        object $subscription
    ): array {
        $items =
            $subscription->items
            ?? null;

        $itemData =
            is_object($items)
                ? (
                    $items->data
                    ?? null
                )
                : null;

        if (
            is_array($itemData)
            && isset($itemData[0])
        ) {
            $firstItem =
                $itemData[0];

            $periodStart =
                isset(
                    $firstItem->current_period_start
                )
                    ? (int) $firstItem
                        ->current_period_start
                    : 0;

            $periodEnd =
                isset(
                    $firstItem->current_period_end
                )
                    ? (int) $firstItem
                        ->current_period_end
                    : 0;

            if (
                $periodStart > 0
                && $periodEnd > $periodStart
            ) {
                return [
                    $periodStart,
                    $periodEnd,
                ];
            }
        }

        $periodStart =
            isset(
                $subscription->current_period_start
            )
                ? (int) $subscription
                    ->current_period_start
                : 0;

        $periodEnd =
            isset(
                $subscription->current_period_end
            )
                ? (int) $subscription
                    ->current_period_end
                : 0;

        if (
            $periodStart <= 0
            || $periodEnd <= $periodStart
        ) {
            throw new RuntimeException(
                'Stripe no devolvió un período de facturación válido.'
            );
        }

        return [
            $periodStart,
            $periodEnd,
        ];
    }

    private static function extractStripeId(
        mixed $value
    ): string {
        if (is_string($value)) {
            return trim(
                $value
            );
        }

        if (
            is_object($value)
            && isset($value->id)
        ) {
            return trim(
                (string) $value->id
            );
        }

        return '';
    }

    private static function getSecretKey(): string
    {
        $secretKey =
            trim(
                (string) get_option(
                    'dsm_pagos_provider_stripe_secret_key',
                    ''
                )
            );

        if ($secretKey === '') {
            throw new RuntimeException(
                'No se encontró la clave secreta de Stripe.'
            );
        }

        return $secretKey;
    }

    private static function getWebhookSecret(): string
    {
        $webhookSecret =
            trim(
                (string) get_option(
                    'dsm_pagos_provider_stripe_webhook_secret',
                    ''
                )
            );

        if ($webhookSecret === '') {
            throw new RuntimeException(
                'Stripe no tiene configurado el webhook secret.'
            );
        }

        return $webhookSecret;
    }

    private function __construct()
    {
    }
}
