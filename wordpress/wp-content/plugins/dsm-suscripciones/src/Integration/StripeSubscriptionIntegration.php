<?php

declare(strict_types=1);

namespace DSM\Suscripciones\Integration;

use DSM\Pagos\Application\ConfirmPayment;
use DSM\Pagos\Payment\Payment;
use DSM\Pagos\Payment\PaymentRepository;
use DSM\Suscripciones\Application\CreateSubscriptionFromPlan;
use DSM\Suscripciones\Subscription\Subscription;
use DSM\Suscripciones\Subscription\SubscriptionPaymentRepository;
use DSM\Suscripciones\Subscription\SubscriptionRepository;
use RuntimeException;

if (!defined('ABSPATH')) {
    exit;
}

final class StripeSubscriptionIntegration
{
    private const CHECKOUT_COMPLETED_ACTION =
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
            self::CHECKOUT_COMPLETED_ACTION,
            [
                self::class,
                'handleCheckoutCompleted',
            ],
            10,
            2
        );

        add_action(
            self::INVOICE_PAID_ACTION,
            [
                self::class,
                'handleInvoicePaid',
            ],
            10,
            1
        );

        add_action(
            self::INVOICE_PAYMENT_FAILED_ACTION,
            [
                self::class,
                'handleInvoicePaymentFailed',
            ],
            10,
            1
        );

        add_action(
            self::SUBSCRIPTION_UPDATED_ACTION,
            [
                self::class,
                'handleSubscriptionUpdated',
            ],
            10,
            1
        );

        add_action(
            self::SUBSCRIPTION_DELETED_ACTION,
            [
                self::class,
                'handleSubscriptionDeleted',
            ],
            10,
            1
        );
    }

    /**
     * @param array<string, mixed> $stripeData
     */
    public static function handleCheckoutCompleted(
        Payment $payment,
        array $stripeData
    ): void {
        if (!$payment->isPaid()) {
            throw new RuntimeException(
                'El pago DSM todavía no está confirmado.'
            );
        }

        if (
            $payment->getPurpose()
            !== 'subscription'
        ) {
            return;
        }

        if (
            $payment->getSourceType()
            !== 'subscription_plan'
        ) {
            return;
        }

        $providerSubscriptionId =
            trim(
                (string) (
                    $stripeData[
                        'subscription_id'
                    ]
                    ?? ''
                )
            );

        $periodStart =
            trim(
                (string) (
                    $stripeData[
                        'period_start'
                    ]
                    ?? ''
                )
            );

        $periodEnd =
            trim(
                (string) (
                    $stripeData[
                        'period_end'
                    ]
                    ?? ''
                )
            );

        $invoiceId =
            isset(
                $stripeData['invoice_id']
            )
            && $stripeData['invoice_id'] !== null
                ? trim(
                    (string) $stripeData[
                        'invoice_id'
                    ]
                )
                : null;

        if ($invoiceId === '') {
            $invoiceId = null;
        }

        if ($providerSubscriptionId === '') {
            throw new RuntimeException(
                'No se recibió la suscripción de Stripe.'
            );
        }

        if (
            $periodStart === ''
            || $periodEnd === ''
        ) {
            throw new RuntimeException(
                'No se recibió el período pagado de Stripe.'
            );
        }

        $subscription =
            self::resolveOrCreateInitialSubscription(
                $payment
            );

        $subscriptionRepository =
            new SubscriptionRepository();

        $subscription =
            $subscriptionRepository
                ->activateRecurringProvider(
                    subscriptionId:
                        $subscription->getId(),

                    provider:
                        'stripe',

                    providerSubscriptionId:
                        $providerSubscriptionId,

                    endsAt:
                        $periodEnd
                );

        if ($invoiceId !== null) {
            $historyRepository =
                new SubscriptionPaymentRepository();

            $historyRepository->create(
                subscriptionId:
                    $subscription->getId(),

                paymentId:
                    $payment->getId(),

                provider:
                    'stripe',

                providerInvoiceReference:
                    $invoiceId,

                periodStart:
                    $periodStart,

                periodEnd:
                    $periodEnd,

                amount:
                    $payment->getAmount(),

                currency:
                    $payment->getCurrency()
            );
        }
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function handleInvoicePaid(
        array $data
    ): void {
        $invoiceId =
            trim(
                (string) (
                    $data['invoice_id']
                    ?? ''
                )
            );

        $providerSubscriptionId =
            trim(
                (string) (
                    $data['subscription_id']
                    ?? ''
                )
            );

        if (
            $invoiceId === ''
            || $providerSubscriptionId === ''
        ) {
            throw new RuntimeException(
                'La factura Stripe no contiene referencias válidas.'
            );
        }

        $periodStart =
            isset($data['period_start'])
            && $data['period_start'] !== null
                ? trim(
                    (string) $data[
                        'period_start'
                    ]
                )
                : '';

        $periodEnd =
            isset($data['period_end'])
            && $data['period_end'] !== null
                ? trim(
                    (string) $data[
                        'period_end'
                    ]
                )
                : '';

        if (
            $periodStart === ''
            || $periodEnd === ''
        ) {
            throw new RuntimeException(
                'La factura Stripe no contiene un período de servicio válido.'
            );
        }

        $historyRepository =
            new SubscriptionPaymentRepository();

        $existingHistory =
            $historyRepository
                ->findByProviderInvoiceReference(
                    'stripe',
                    $invoiceId
                );

        $subscriptionRepository =
            new SubscriptionRepository();

        if ($existingHistory !== null) {
            $subscription =
                $subscriptionRepository
                    ->findById(
                        $existingHistory
                            ->getSubscriptionId()
                    );

            if ($subscription !== null) {
                $subscriptionRepository
                    ->updatePeriodEnd(
                        $subscription->getId(),
                        $periodEnd
                    );
            }

            return;
        }

        $subscription =
            $subscriptionRepository
                ->findByProviderSubscriptionId(
                    'stripe',
                    $providerSubscriptionId
                );

        if ($subscription === null) {
            $initialPaymentId =
                isset(
                    $data[
                        'initial_payment_id'
                    ]
                )
                    ? (int) $data[
                        'initial_payment_id'
                    ]
                    : 0;

            if ($initialPaymentId <= 0) {
                throw new RuntimeException(
                    'No se encontró la suscripción DSM y Stripe no proporcionó el pago inicial.'
                );
            }

            $paymentRepository =
                new PaymentRepository();

            $initialPayment =
                $paymentRepository
                    ->findById(
                        $initialPaymentId
                    );

            if ($initialPayment === null) {
                throw new RuntimeException(
                    'No se encontró el pago inicial de la suscripción.'
                );
            }

            if ($initialPayment->isPending()) {
                $confirmPayment =
                    new ConfirmPayment();

                $initialPayment =
                    $confirmPayment->execute(
                        $initialPaymentId,
                        'stripe',
                        $invoiceId
                    );
            }

            if (!$initialPayment->isPaid()) {
                throw new RuntimeException(
                    'El pago inicial DSM no está confirmado.'
                );
            }

            $subscription =
                self::resolveOrCreateInitialSubscription(
                    $initialPayment
                );

            $subscription =
                $subscriptionRepository
                    ->activateRecurringProvider(
                        subscriptionId:
                            $subscription->getId(),

                        provider:
                            'stripe',

                        providerSubscriptionId:
                            $providerSubscriptionId,

                        endsAt:
                            $periodEnd
                    );
        }

        self::validateInvoiceOwnership(
            $subscription,
            $data
        );

        $initialPaymentId =
            isset(
                $data['initial_payment_id']
            )
                ? (int) $data[
                    'initial_payment_id'
                ]
                : 0;

        $billingReason =
            trim(
                (string) (
                    $data['billing_reason']
                    ?? ''
                )
            );

        $isInitialInvoice =
            $initialPaymentId > 0
            && $subscription->getPaymentId()
                === $initialPaymentId
            && (
                $billingReason
                === 'subscription_create'
                || $subscription
                    ->getProviderSubscriptionId()
                    === $providerSubscriptionId
            );

        if ($isInitialInvoice) {
            $paymentRepository =
                new PaymentRepository();

            $payment =
                $paymentRepository
                    ->findById(
                        $initialPaymentId
                    );

            if ($payment === null) {
                throw new RuntimeException(
                    'No se encontró el pago inicial DSM.'
                );
            }

            if ($payment->isPending()) {
                $confirmPayment =
                    new ConfirmPayment();

                $payment =
                    $confirmPayment->execute(
                        $payment->getId(),
                        'stripe',
                        $invoiceId
                    );
            }

            if (!$payment->isPaid()) {
                throw new RuntimeException(
                    'El pago inicial no está confirmado.'
                );
            }
        } else {
            $amount =
                isset($data['amount'])
                    ? (float) $data[
                        'amount'
                    ]
                    : 0.0;

            $currency =
                strtoupper(
                    trim(
                        (string) (
                            $data['currency']
                            ?? ''
                        )
                    )
                );

            if ($amount <= 0) {
                throw new RuntimeException(
                    'El importe de la renovación no es válido.'
                );
            }

            if (
                strlen($currency) !== 3
                || !ctype_alpha($currency)
            ) {
                throw new RuntimeException(
                    'La moneda de la renovación no es válida.'
                );
            }

            $paymentRepository =
                new PaymentRepository();

            $payment =
                $paymentRepository->create(
                    customerId:
                        $subscription
                            ->getCustomerId(),

                    purpose:
                        'subscription',

                    amount:
                        $amount,

                    currency:
                        $currency,

                    provider:
                        'stripe',

                    providerReference:
                        $invoiceId,

                    sourceType:
                        'subscription_renewal',

                    sourceId:
                        $subscription
                            ->getId(),

                    sourceReference:
                        $invoiceId
                );

            $confirmPayment =
                new ConfirmPayment();

            $payment =
                $confirmPayment->execute(
                    $payment->getId(),
                    'stripe',
                    $invoiceId
                );
        }

        $historyRepository->create(
            subscriptionId:
                $subscription->getId(),

            paymentId:
                $payment->getId(),

            provider:
                'stripe',

            providerInvoiceReference:
                $invoiceId,

            periodStart:
                $periodStart,

            periodEnd:
                $periodEnd,

            amount:
                $payment->getAmount(),

            currency:
                $payment->getCurrency()
        );

        $subscriptionRepository
            ->updatePeriodEnd(
                $subscription->getId(),
                $periodEnd
            );
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function handleInvoicePaymentFailed(
        array $data
    ): void {
        $invoiceId =
            trim(
                (string) (
                    $data['invoice_id']
                    ?? ''
                )
            );

        $providerSubscriptionId =
            trim(
                (string) (
                    $data['subscription_id']
                    ?? ''
                )
            );

        if (
            $invoiceId === ''
            || $providerSubscriptionId === ''
        ) {
            return;
        }

        $repository =
            new SubscriptionRepository();

        $subscription =
            $repository
                ->findByProviderSubscriptionId(
                    'stripe',
                    $providerSubscriptionId
                );

        if ($subscription === null) {
            error_log(
                sprintf(
                    '[DSM Suscripciones][Stripe] Pago fallido de factura %s para suscripción Stripe %s todavía no vinculada.',
                    $invoiceId,
                    $providerSubscriptionId
                )
            );

            return;
        }

        error_log(
            sprintf(
                '[DSM Suscripciones][Stripe] Renovación fallida. DSM subscription=%d Stripe subscription=%s invoice=%s',
                $subscription->getId(),
                $providerSubscriptionId,
                $invoiceId
            )
        );

        do_action(
            'dsm_subscription_renewal_payment_failed',
            $subscription,
            $data
        );
    }

    /**
     * Sincroniza cambios realizados tanto desde DSM
     * como directamente desde Stripe.
     *
     * @param array<string, mixed> $data
     */
    public static function handleSubscriptionUpdated(
        array $data
    ): void {
        $providerSubscriptionId =
            trim(
                (string) (
                    $data['subscription_id']
                    ?? ''
                )
            );

        if ($providerSubscriptionId === '') {
            return;
        }

        $repository =
            new SubscriptionRepository();

        $subscription =
            $repository
                ->findByProviderSubscriptionId(
                    'stripe',
                    $providerSubscriptionId
                );

        /*
         * Puede ser una suscripción Stripe ajena a DSM.
         */
        if ($subscription === null) {
            return;
        }

        self::validateSubscriptionOwnership(
            $subscription,
            $data
        );

        $periodEnd =
            isset($data['period_end'])
            && $data['period_end'] !== null
                ? trim(
                    (string) $data[
                        'period_end'
                    ]
                )
                : '';

        if ($periodEnd !== '') {
            $repository
                ->updatePeriodEnd(
                    $subscription->getId(),
                    $periodEnd
                );

            /*
             * Recargamos después de modificar ends_at.
             */
            $subscription =
                $repository
                    ->findById(
                        $subscription->getId()
                    )
                ?? $subscription;
        }

        $cancelAtPeriodEnd =
            (bool) (
                $data[
                    'cancel_at_period_end'
                ]
                ?? false
            );

        if ($cancelAtPeriodEnd) {
            /*
             * No cancelamos status.
             *
             * El período actual sigue estando pagado.
             */
            if (
                !$subscription
                    ->hasCancellationRequested()
                || $subscription
                    ->isAutoRenew()
            ) {
                $repository
                    ->requestCancellationAtPeriodEnd(
                        $subscription->getId()
                    );
            }

            return;
        }

        /*
         * cancel_at_period_end=false significa que
         * Stripe tiene habilitada nuevamente la
         * renovación.
         */
        if (
            $subscription
                ->hasCancellationRequested()
            || !$subscription
                ->isAutoRenew()
        ) {
            $repository
                ->reactivateRenewal(
                    $subscription->getId()
                );
        }
    }

    /**
     * Baja definitiva comunicada por Stripe.
     *
     * @param array<string, mixed> $data
     */
    public static function handleSubscriptionDeleted(
        array $data
    ): void {
        $providerSubscriptionId =
            trim(
                (string) (
                    $data['subscription_id']
                    ?? ''
                )
            );

        if ($providerSubscriptionId === '') {
            return;
        }

        $repository =
            new SubscriptionRepository();

        $subscription =
            $repository
                ->findByProviderSubscriptionId(
                    'stripe',
                    $providerSubscriptionId
                );

        if ($subscription === null) {
            return;
        }

        self::validateSubscriptionOwnership(
            $subscription,
            $data
        );

        $periodEnd =
            isset($data['period_end'])
            && $data['period_end'] !== null
                ? trim(
                    (string) $data[
                        'period_end'
                    ]
                )
                : '';

        if ($periodEnd !== '') {
            $repository
                ->updatePeriodEnd(
                    $subscription->getId(),
                    $periodEnd
                );
        }

        $cancelledAt =
            isset($data['cancelled_at'])
            && $data['cancelled_at'] !== null
                ? trim(
                    (string) $data[
                        'cancelled_at'
                    ]
                )
                : null;

        if ($cancelledAt === '') {
            $cancelledAt = null;
        }

        $repository
            ->markCancelled(
                $subscription->getId(),
                $cancelledAt
            );
    }

    private static function resolveOrCreateInitialSubscription(
        Payment $payment
    ): Subscription {
        $repository =
            new SubscriptionRepository();

        $subscription =
            $repository->findByPaymentId(
                $payment->getId()
            );

        if ($subscription !== null) {
            return $subscription;
        }

        $planId =
            $payment->getSourceId()
            ?? 0;

        if ($planId <= 0) {
            throw new RuntimeException(
                'El pago inicial no contiene un plan válido.'
            );
        }

        $createSubscription =
            new CreateSubscriptionFromPlan();

        return $createSubscription->execute(
            customerId:
                $payment->getCustomerId(),

            planId:
                $planId,

            paymentId:
                $payment->getId(),

            pricePaid:
                $payment->getAmount(),

            currency:
                $payment->getCurrency()
        );
    }

    /**
     * @param array<string, mixed> $data
     */
    private static function validateInvoiceOwnership(
        Subscription $subscription,
        array $data
    ): void {
        $customerId =
            isset($data['customer_id'])
                ? (int) $data[
                    'customer_id'
                ]
                : 0;

        if (
            $customerId > 0
            && $customerId
                !== $subscription
                    ->getCustomerId()
        ) {
            throw new RuntimeException(
                'El cliente de la factura Stripe no coincide con la suscripción DSM.'
            );
        }

        $planId =
            isset($data['plan_id'])
                ? (int) $data[
                    'plan_id'
                ]
                : 0;

        if (
            $planId > 0
            && $planId
                !== $subscription
                    ->getPlanId()
        ) {
            throw new RuntimeException(
                'El plan de la factura Stripe no coincide con la suscripción DSM.'
            );
        }
    }

    /**
     * @param array<string, mixed> $data
     */
    private static function validateSubscriptionOwnership(
        Subscription $subscription,
        array $data
    ): void {
        $customerId =
            isset($data['customer_id'])
                ? (int) $data[
                    'customer_id'
                ]
                : 0;

        if (
            $customerId > 0
            && $customerId
                !== $subscription
                    ->getCustomerId()
        ) {
            throw new RuntimeException(
                'El cliente de la suscripción Stripe no coincide con DSM.'
            );
        }

        $planId =
            isset($data['plan_id'])
                ? (int) $data[
                    'plan_id'
                ]
                : 0;

        if (
            $planId > 0
            && $planId
                !== $subscription
                    ->getPlanId()
        ) {
            throw new RuntimeException(
                'El plan de la suscripción Stripe no coincide con DSM.'
            );
        }
    }

    private function __construct()
    {
    }
}
