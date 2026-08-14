<?php

declare(strict_types=1);

namespace DSM\Promocionar\Frontend;

use DSM\Clientes\Authentication\AuthenticatedCustomer;
use DSM\Clientes\Authentication\CustomerSessionRepository;
use DSM\Clientes\Customer\CustomerRepository;
use DSM\Pagos\Application\CreatePromotionPayment;
use DSM\Promocionar\Promotion\PromotionPlanRepository;
use DSM\Promocionar\Support\AdvertisementContext;
use RuntimeException;
use Throwable;

if (!defined('ABSPATH')) {
    exit;
}

final class PromotionPurchaseController
{
    public const ACTION =
        'dsm_promotion_purchase';

    public const NONCE_FIELD =
        'dsm_promotion_purchase_nonce';

    public static function register(): void
    {
        add_action(
            'admin_post_'
            . self::ACTION,
            [
                self::class,
                'handle',
            ]
        );

        /*
         * Los clientes DSM no son usuarios WordPress.
         *
         * Aunque tengan una sesión válida de DSM Clientes,
         * WordPress procesa admin-post.php mediante nopriv.
         */
        add_action(
            'admin_post_nopriv_'
            . self::ACTION,
            [
                self::class,
                'handle',
            ]
        );
    }

    public static function handle(): never
    {
        $customerId =
            self::resolveAuthenticatedCustomerId();

        if ($customerId <= 0) {
            self::redirectToLogin();
        }

        $advertisementId =
            self::getPostId(
                'advertisement_id'
            );

        $planId =
            self::getPostId(
                'plan_id'
            );

        check_admin_referer(
            self::getNonceAction(
                $advertisementId,
                $planId
            ),
            self::NONCE_FIELD
        );

        try {
            if ($advertisementId <= 0) {
                throw new RuntimeException(
                    'El identificador del anuncio no es válido.'
                );
            }

            if ($planId <= 0) {
                throw new RuntimeException(
                    'El identificador del plan no es válido.'
                );
            }

            /*
             * Verifica que:
             *
             * - el anuncio existe;
             * - pertenece al cliente;
             * - está en un estado promocionable.
             */
            AdvertisementContext::requirePromotable(
                $advertisementId,
                $customerId
            );

            /*
             * El plan se carga siempre desde BD.
             *
             * El navegador NO decide precio, moneda,
             * duración ni código comercial.
             */
            $planRepository =
                new PromotionPlanRepository();

            $plan =
                $planRepository->findById(
                    $planId
                );

            if ($plan === null) {
                throw new RuntimeException(
                    'No se encontró el plan de promoción indicado.'
                );
            }

            if (!$plan->isActive()) {
                throw new RuntimeException(
                    'El plan de promoción seleccionado no está disponible.'
                );
            }

            if ($plan->getPrice() <= 0) {
                throw new RuntimeException(
                    'El plan seleccionado no tiene un precio válido para pago.'
                );
            }

            $useCase =
                new CreatePromotionPayment();

            $payment =
                $useCase->execute(
                    customerId:
                        $customerId,

                    planId:
                        $plan->getId(),

                    planCode:
                        $plan->getCode(),

                    amount:
                        $plan->getPrice(),

                    currency:
                        $plan->getCurrency()
                );

            self::redirectToCheckout(
                $payment->getId(),
                $advertisementId
            );
        } catch (Throwable $exception) {
            self::redirectToAdvertisement(
                $advertisementId,
                [
                    'promotion_status' =>
                        'purchase_error',

                    'promotion_error' =>
                        $exception->getMessage(),
                ]
            );
        }
    }

    public static function getNonceAction(
        int $advertisementId,
        int $planId
    ): string {
        return self::ACTION
            . '_'
            . $advertisementId
            . '_'
            . $planId;
    }

    private static function resolveAuthenticatedCustomerId(): int
    {
        try {
            $authenticatedCustomer =
                new AuthenticatedCustomer(
                    new CustomerSessionRepository(),
                    new CustomerRepository()
                );

            $customer =
                $authenticatedCustomer->resolve();

            return $customer !== null
                ? $customer->getId()
                : 0;
        } catch (Throwable) {
            return 0;
        }
    }

    private static function getPostId(
        string $field
    ): int {
        if (!isset($_POST[$field])) {
            return 0;
        }

        return absint(
            wp_unslash(
                (string) $_POST[$field]
            )
        );
    }

    private static function redirectToLogin(): never
    {
        wp_safe_redirect(
            home_url(
                '/iniciar-sesion/'
            )
        );

        exit;
    }

    private static function redirectToCheckout(
        int $paymentId,
        int $advertisementId
    ): never {
        $url =
            add_query_arg(
                [
                    'payment_id' =>
                        $paymentId,

                    'advertisement_id' =>
                        $advertisementId,
                ],
                home_url(
                    '/checkout-pago/'
                )
            );

        wp_safe_redirect(
            $url
        );

        exit;
    }

    /**
     * @param array<string, scalar> $arguments
     */
    private static function redirectToAdvertisement(
        int $advertisementId,
        array $arguments = []
    ): never {
        $url =
            add_query_arg(
                array_merge(
                    [
                        'advertisement_id' =>
                            $advertisementId,
                    ],
                    $arguments
                ),
                home_url(
                    '/promocionar-anuncio/'
                )
            );

        wp_safe_redirect(
            $url
        );

        exit;
    }

    private function __construct()
    {
    }
}
