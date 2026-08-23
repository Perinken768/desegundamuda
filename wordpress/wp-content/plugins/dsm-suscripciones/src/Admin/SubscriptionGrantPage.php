<?php

declare(strict_types=1);

namespace DSM\Suscripciones\Admin;

use DSM\Clientes\Customer\CustomerLookupRepository;
use DSM\Suscripciones\Application\GrantSubscription;
use DSM\Suscripciones\Subscription\SubscriptionPlanRepository;
use RuntimeException;
use Throwable;

if (!defined('ABSPATH')) {
    exit;
}

final class SubscriptionGrantPage
{
    public const MENU_SLUG =
        'dsm-suscripciones-grant';

    public const CUSTOMER_SEARCH_ACTION =
        'dsm_subscription_customer_search';

    public const CUSTOMER_SEARCH_NONCE_ACTION =
        'dsm_subscription_customer_search';

    private const PARENT_SLUG =
        'dsm-suscripciones';

    private const CAPABILITY =
        'manage_options';

    private const GRANT_ACTION =
        'dsm_subscription_admin_grant';

    private const NONCE_ACTION =
        'dsm_subscription_admin_grant';

    private const NONCE_NAME =
        'dsm_subscription_admin_grant_nonce';

    public static function register(): void
    {
        $page =
            new self();

        add_action(
            'admin_menu',
            [
                $page,
                'registerMenu',
            ],
            20
        );

        add_action(
            'admin_post_'
            . self::GRANT_ACTION,
            [
                $page,
                'handleGrant',
            ]
        );

        add_action(
            'wp_ajax_'
            . self::CUSTOMER_SEARCH_ACTION,
            [
                $page,
                'handleCustomerSearch',
            ]
        );
    }

    public function registerMenu(): void
    {
        
        add_submenu_page(
            parent_slug:
                self::PARENT_SLUG,

            page_title:
                __(
                    'Conceder suscripción',
                    'dsm-suscripciones'
                ),

            menu_title:
                __(
                    'Conceder suscripción',
                    'dsm-suscripciones'
                ),

            capability:
                self::CAPABILITY,

            menu_slug:
                self::MENU_SLUG,

            callback:
                [
                    $this,
                    'render',
                ]
        );
    }

    public function render(): void
    {
        $this->assertPermission();

        $plans =
            (
                new SubscriptionPlanRepository()
            )->findActive();

        /*
         * Free no necesita suscripción explícita.
         */
        $plans =
            array_values(
                array_filter(
                    $plans,
                    static fn ($plan): bool =>
                        !$plan->isFree()
                )
            );

        $notice =
            isset($_GET['notice'])
                ? sanitize_key(
                    wp_unslash(
                        (string) $_GET['notice']
                    )
                )
                : '';

        $error =
            isset($_GET['error'])
                ? sanitize_text_field(
                    wp_unslash(
                        (string) $_GET['error']
                    )
                )
                : '';

        $subscriptionId =
            isset($_GET['subscription_id'])
                ? absint(
                    wp_unslash(
                        (string) $_GET['subscription_id']
                    )
                )
                : 0;

        $customerSearchNonce =
            wp_create_nonce(
                self::CUSTOMER_SEARCH_NONCE_ACTION
            );

        $template =
            DSM_SUSCRIPCIONES_PATH
            . 'templates/admin/subscription-grant.php';

        if (!is_file($template)) {
            throw new RuntimeException(
                'No se encontró la plantilla de concesión de suscripciones.'
            );
        }

        require $template;
    }

    public function handleCustomerSearch(): void
    {
        $this->assertPermission();

        check_ajax_referer(
            self::CUSTOMER_SEARCH_NONCE_ACTION,
            'nonce'
        );

        $search =
            isset($_GET['search'])
                ? sanitize_text_field(
                    wp_unslash(
                        (string) $_GET['search']
                    )
                )
                : '';

        if (
            mb_strlen(
                trim(
                    $search
                )
            ) < 2
        ) {
            wp_send_json_success(
                [
                    'customers' =>
                        [],
                ]
            );
        }

        try {
            $customers =
                (
                    new CustomerLookupRepository()
                )->searchActive(
                    $search,
                    10
                );

            wp_send_json_success(
                [
                    'customers' =>
                        $customers,
                ]
            );
        } catch (Throwable $exception) {
            wp_send_json_error(
                [
                    'message' =>
                        $exception->getMessage(),
                ],
                500
            );
        }
    }

    public function handleGrant(): void
    {
        $this->assertPermission();

        check_admin_referer(
            self::NONCE_ACTION,
            self::NONCE_NAME
        );

        try {
            $customerId =
                isset($_POST['customer_id'])
                    ? absint(
                        wp_unslash(
                            (string) $_POST['customer_id']
                        )
                    )
                    : 0;

            $planId =
                isset($_POST['plan_id'])
                    ? absint(
                        wp_unslash(
                            (string) $_POST['plan_id']
                        )
                    )
                    : 0;

            $startsAt =
                isset($_POST['starts_at'])
                    ? sanitize_text_field(
                        wp_unslash(
                            (string) $_POST['starts_at']
                        )
                    )
                    : '';

            $endsAt =
                isset($_POST['ends_at'])
                    ? sanitize_text_field(
                        wp_unslash(
                            (string) $_POST['ends_at']
                        )
                    )
                    : '';

            $reason =
                isset($_POST['reason'])
                    ? sanitize_text_field(
                        wp_unslash(
                            (string) $_POST['reason']
                        )
                    )
                    : '';

            if ($customerId <= 0) {
                throw new RuntimeException(
                    'Debes seleccionar un cliente.'
                );
            }

            if ($planId <= 0) {
                throw new RuntimeException(
                    'Debes seleccionar un plan.'
                );
            }

            $subscription =
                (
                    new GrantSubscription()
                )->execute(
                    customerId:
                        $customerId,

                    planId:
                        $planId,

                    startsAt:
                        $startsAt,

                    endsAt:
                        $endsAt !== ''
                            ? $endsAt
                            : null,

                    reason:
                        $reason
                );

            $this->redirect(
                [
                    'notice' =>
                        'granted',

                    'subscription_id' =>
                        $subscription->getId(),
                ]
            );
        } catch (Throwable $exception) {
            $this->redirect(
                [
                    'error' =>
                        $exception->getMessage(),
                ]
            );
        }
    }

    private function assertPermission(): void
    {
        if (
            !current_user_can(
                self::CAPABILITY
            )
        ) {
            wp_die(
                esc_html__(
                    'No tienes permisos para gestionar suscripciones.',
                    'dsm-suscripciones'
                )
            );
        }
    }

    /**
     * @param array<string, scalar> $arguments
     */
    private function redirect(
        array $arguments = []
    ): never {
        $url =
            add_query_arg(
                $arguments,
                admin_url(
                    'admin.php?page='
                    . self::MENU_SLUG
                )
            );

        wp_safe_redirect(
            $url
        );

        exit;
    }

    public static function getGrantAction(): string
    {
        return self::GRANT_ACTION;
    }

    public static function getNonceAction(): string
    {
        return self::NONCE_ACTION;
    }

    public static function getNonceName(): string
    {
        return self::NONCE_NAME;
    }

    private function __construct()
    {
    }
}
