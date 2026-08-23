<?php

declare(strict_types=1);

namespace DSM\Pagos\Webhook;

use DSM\Pagos\Application\ConfirmPayment;
use DSM\Pagos\Payment\PaymentRepository;
use RuntimeException;
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

                /*
                 * Stripe no está autenticado como
                 * usuario WordPress.
                 *
                 * La autenticidad se valida mediante
                 * Stripe-Signature.
                 */
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

            /*
             * Verificación criptográfica oficial
             * de Stripe.
             */
            $event =
                Webhook::constructEvent(
                    $payload,
                    $signature,
                    $webhookSecret
                );

            if (
                $event->type
                !== 'checkout.session.completed'
            ) {
                return new WP_REST_Response(
                    [
                        'received' =>
                            true,

                        'processed' =>
                            false,

                        'event_type' =>
                            $event->type,
                    ],
                    200
                );
            }

            $session =
                $event->data->object;

            /*
             * Con mode=payment queremos confirmar
             * únicamente sesiones realmente pagadas.
             */
            $paymentStatus =
                (string) (
                    $session->payment_status
                    ?? ''
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
                    ],
                    200
                );
            }

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

            /*
             * Protección adicional:
             * comprobamos importe y moneda recibidos
             * desde Stripe contra nuestro pago interno.
             */
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
                    (string) (
                        $session->currency
                        ?? ''
                    )
                );

            $expectedCurrency =
                strtolower(
                    $payment->getCurrency()
                );

            if (
                $stripeCurrency === ''
                || $stripeCurrency !== $expectedCurrency
            ) {
                throw new RuntimeException(
                    'La moneda recibida desde Stripe no coincide con el pago DSM.'
                );
            }

            /*
             * Referencia estable del proveedor.
             *
             * La Session ID es única en Stripe.
             */
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

            return new WP_REST_Response(
                [
                    'received' =>
                        true,

                    'processed' =>
                        true,

                    'payment_id' =>
                        $confirmedPayment
                            ->getId(),

                    'status' =>
                        $confirmedPayment
                            ->getStatus(),
                ],
                200
            );
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

    private function __construct()
    {
    }
}
