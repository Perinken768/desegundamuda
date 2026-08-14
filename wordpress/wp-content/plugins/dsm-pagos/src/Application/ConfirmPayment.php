<?php

declare(strict_types=1);

namespace DSM\Pagos\Application;

use DSM\Pagos\Payment\Payment;
use DSM\Pagos\Payment\PaymentRepository;
use RuntimeException;
use Throwable;

if (!defined('ABSPATH')) {
    exit;
}

final class ConfirmPayment
{
    public function __construct(
        private readonly PaymentRepository $paymentRepository =
            new PaymentRepository()
    ) {
    }

    public function execute(
        int $paymentId,
        ?string $provider = null,
        ?string $providerReference = null
    ): Payment {
        global $wpdb;

        if ($paymentId <= 0) {
            throw new RuntimeException(
                'El identificador del pago no es válido.'
            );
        }

        $wpdb->query(
            'START TRANSACTION'
        );

        $paymentWasConfirmed = false;

        try {
            $payment =
                $this->paymentRepository
                    ->findByIdForUpdate(
                        $paymentId
                    );

            if ($payment === null) {
                throw new RuntimeException(
                    'No se encontró el pago indicado.'
                );
            }

            /*
             * Idempotencia:
             *
             * Si una pasarela repite la notificación
             * de un pago que ya estaba confirmado,
             * devolvemos el pago existente y NO
             * volveremos a emitir dsm_payment_paid.
             */
            if ($payment->isPaid()) {
                $wpdb->query(
                    'COMMIT'
                );

                return $payment;
            }

            if (!$payment->isPending()) {
                throw new RuntimeException(
                    'Solo un pago pendiente puede confirmarse.'
                );
            }

            $confirmedPayment =
                $this->paymentRepository
                    ->markPaid(
                        $paymentId,
                        $provider,
                        $providerReference
                    );

            $paymentWasConfirmed = true;

            $wpdb->query(
                'COMMIT'
            );
        } catch (Throwable $exception) {
            $wpdb->query(
                'ROLLBACK'
            );

            throw $exception;
        }

        /*
         * El evento se emite después del COMMIT.
         *
         * Así los consumidores reciben siempre
         * un pago que ya está confirmado en BD.
         */
        if ($paymentWasConfirmed) {
            do_action(
                'dsm_payment_paid',
                $confirmedPayment
            );
        }

        return $confirmedPayment;
    }
}
