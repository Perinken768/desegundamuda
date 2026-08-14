<?php

declare(strict_types=1);

namespace DSM\Promocionar\Frontend;

use DSM\Clientes\Authentication\AuthenticatedCustomer;
use DSM\Clientes\Authentication\CustomerSessionRepository;
use DSM\Clientes\Customer\CustomerRepository;
use DSM\Promocionar\Application\StartPromotion;
use DSM\Promocionar\Application\StopPromotion;
use DSM\Promocionar\Support\CustomerContext;
use Throwable;

if (!defined('ABSPATH')) {
    exit;
}

final class PromotionActionController
{
    public const ACTION_START =
        'dsm_promotion_start';

    public const ACTION_STOP =
        'dsm_promotion_stop';

    public const NONCE_FIELD =
        'dsm_promotion_nonce';

    public static function register(): void
    {
        add_action(
            'admin_post_'
            . self::ACTION_START,
            [
                self::class,
                'handleStart',
            ]
        );

        add_action(
            'admin_post_nopriv_'
            . self::ACTION_START,
            [
                self::class,
                'handleStart',
            ]
        );

        add_action(
            'admin_post_'
            . self::ACTION_STOP,
            [
                self::class,
                'handleStop',
            ]
        );

        add_action(
            'admin_post_nopriv_'
            . self::ACTION_STOP,
            [
                self::class,
                'handleStop',
            ]
        );
    }

    public static function handleStart(): never
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

        $walletId =
            self::getPostId(
                'wallet_id'
            );

        check_admin_referer(
            self::getStartNonceAction(
                $advertisementId,
                $walletId
            ),
            self::NONCE_FIELD
        );

        try {
            CustomerContext::requireActive(
                $customerId
            );

            $useCase =
                new StartPromotion();

            $useCase->execute(
                $customerId,
                $walletId,
                $advertisementId
            );

            self::redirect(
                $advertisementId,
                [
                    'promotion_status' =>
                        'started',
                ]
            );
        } catch (Throwable $exception) {
            self::redirect(
                $advertisementId,
                [
                    'promotion_status' =>
                        'error',

                    'promotion_error' =>
                        $exception->getMessage(),
                ]
            );
        }
    }

    public static function handleStop(): never
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

        $assignmentId =
            self::getPostId(
                'assignment_id'
            );

        check_admin_referer(
            self::getStopNonceAction(
                $advertisementId,
                $assignmentId
            ),
            self::NONCE_FIELD
        );

        try {
            CustomerContext::requireActive(
                $customerId
            );

            $useCase =
                new StopPromotion();

            $assignment =
                $useCase->execute(
                    $customerId,
                    $assignmentId
                );

            if (
                $assignment->getAdvertisementId()
                !== $advertisementId
            ) {
                throw new \RuntimeException(
                    'La promoción no pertenece al anuncio indicado.'
                );
            }

            self::redirect(
                $advertisementId,
                [
                    'promotion_status' =>
                        'stopped',
                ]
            );
        } catch (Throwable $exception) {
            self::redirect(
                $advertisementId,
                [
                    'promotion_status' =>
                        'error',

                    'promotion_error' =>
                        $exception->getMessage(),
                ]
            );
        }
    }

    public static function getNonceAction(
        int $advertisementId,
        int $walletId
    ): string {
        return self::getStartNonceAction(
            $advertisementId,
            $walletId
        );
    }

    public static function getStartNonceAction(
        int $advertisementId,
        int $walletId
    ): string {
        return self::ACTION_START
            . '_'
            . $advertisementId
            . '_'
            . $walletId;
    }

    public static function getStopNonceAction(
        int $advertisementId,
        int $assignmentId
    ): string {
        return self::ACTION_STOP
            . '_'
            . $advertisementId
            . '_'
            . $assignmentId;
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

    /**
     * @param array<string, scalar> $arguments
     */
    private static function redirect(
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