<?php

declare(strict_types=1);

namespace DSM\Ofertas\Integration;

use DSM\Ofertas\Application\AcceptRetentionOffer;
use DSM\Suscripciones\Support\CustomerContext;
use Throwable;

if (!defined('ABSPATH')) {
    exit;
}

final class RetentionOfferController
{
    public const ACTION =
        'dsm_offer_accept_retention';

    public const NONCE_FIELD =
        'dsm_offer_retention_nonce';

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
        try {
            $context =
                CustomerContext::requireCurrentActive();

            $customerId =
                (int) (
                    $context['id']
                    ?? 0
                );

            $subscriptionId =
                isset(
                    $_POST[
                        'subscription_id'
                    ]
                )
                    ? absint(
                        wp_unslash(
                            (string)
                            $_POST[
                                'subscription_id'
                            ]
                        )
                    )
                    : 0;

            check_admin_referer(
                self::getNonceAction(
                    $subscriptionId
                ),
                self::NONCE_FIELD
            );

            $redemptionId =
                (
                    new AcceptRetentionOffer()
                )->execute(
                    $customerId,
                    $subscriptionId
                );

            self::redirect(
                [
                    'subscription_status' =>
                        'retention_offer_accepted',

                    'offer_redemption_id' =>
                        $redemptionId,
                ]
            );
        } catch (Throwable $exception) {
            self::redirect(
                [
                    'subscription_status' =>
                        'subscription_action_error',

                    'subscription_error' =>
                        $exception->getMessage(),
                ]
            );
        }
    }

    public static function getNonceAction(
        int $subscriptionId
    ): string {
        return self::ACTION
            . '_'
            . $subscriptionId;
    }

    /**
     * @param array<string, scalar> $arguments
     */
    private static function redirect(
        array $arguments
    ): never {
        wp_safe_redirect(
            add_query_arg(
                $arguments,
                home_url(
                    '/suscripciones/'
                )
            )
        );

        exit;
    }

    private function __construct()
    {
    }
}
