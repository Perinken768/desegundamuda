<?php

declare(strict_types=1);

namespace DSM\Suscripciones\Frontend;

use DSM\Suscripciones\Subscription\SubscriptionPlanRepository;
use DSM\Suscripciones\Subscription\SubscriptionRepository;
use DSM\Suscripciones\Support\CustomerContext;
use Throwable;

if (!defined('ABSPATH')) {
    exit;
}

final class SubscriptionPlansShortcode
{
    public const SHORTCODE =
        'dsm_subscription_plans';

    public static function register(): void
    {
        add_shortcode(
            self::SHORTCODE,
            [
                self::class,
                'render',
            ]
        );
    }

    public static function render(): string
    {
        try {
            $planRepository =
                new SubscriptionPlanRepository();

            $plans =
                $planRepository->findActive();

            $customerContext =
                CustomerContext::current();

            $customerId =
                is_array($customerContext)
                    ? max(
                        0,
                        (int) (
                            $customerContext['id']
                            ?? 0
                        )
                    )
                    : 0;

            $activeSubscriptionsByPlan =
                [];

            if ($customerId > 0) {
                $subscriptionRepository =
                    new SubscriptionRepository();

                $activeSubscriptions =
                    $subscriptionRepository
                        ->findActiveByCustomerId(
                            $customerId
                        );

                foreach (
                    $activeSubscriptions
                    as $subscription
                ) {
                    $activeSubscriptionsByPlan[
                        $subscription->getPlanId()
                    ] = $subscription;
                }
            }

            $status =
                isset($_GET['subscription_status'])
                    ? sanitize_key(
                        wp_unslash(
                            (string) $_GET[
                                'subscription_status'
                            ]
                        )
                    )
                    : '';

            $error =
                isset($_GET['subscription_error'])
                    ? sanitize_text_field(
                        wp_unslash(
                            (string) $_GET[
                                'subscription_error'
                            ]
                        )
                    )
                    : '';

            $template =
                DSM_SUSCRIPCIONES_PATH
                . 'templates/account/'
                . 'subscription-plans.php';

            if (!is_file($template)) {
                return self::renderError(
                    __(
                        'No se encontró la plantilla de suscripciones.',
                        'dsm-suscripciones'
                    )
                );
            }

            ob_start();

            include $template;

            $output =
                ob_get_clean();

            return is_string($output)
                ? $output
                : '';
        } catch (Throwable $exception) {
            return self::renderError(
                $exception->getMessage()
            );
        }
    }

    private static function renderError(
        string $message
    ): string {
        return sprintf(
            '<div class="dsm-account-notice dsm-account-notice--error">%s</div>',
            esc_html(
                $message
            )
        );
    }

    private function __construct()
    {
    }
}
