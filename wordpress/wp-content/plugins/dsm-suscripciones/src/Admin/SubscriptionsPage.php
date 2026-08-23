<?php

declare(strict_types=1);

namespace DSM\Suscripciones\Admin;

use DSM\Clientes\Customer\CustomerRepository;
use DSM\Suscripciones\Subscription\SubscriptionPlanRepository;
use DSM\Suscripciones\Subscription\SubscriptionRepository;
use RuntimeException;
use Throwable;

if (!defined('ABSPATH')) {
    exit;
}

final class SubscriptionsPage
{
    public const MENU_SLUG =
        'dsm-suscripciones';

    private const CAPABILITY =
        'manage_options';

    private const CANCEL_ACTION =
        'dsm_subscription_admin_cancel';

    private const NONCE_ACTION_PREFIX =
        'dsm_subscription_admin_cancel_';

    private const NONCE_NAME =
        'dsm_subscription_cancel_nonce';

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
            5
        );

        add_action(
            'admin_post_'
            . self::CANCEL_ACTION,
            [
                $page,
                'handleCancel',
            ]
        );
    }

    public function registerMenu(): void
    {
        add_menu_page(
            page_title:
                __(
                    'DSM Suscripciones',
                    'dsm-suscripciones'
                ),

            menu_title:
                __(
                    'DSM Suscripciones',
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
                ],

            icon_url:
                'dashicons-groups',

            position:
                29
        );

        add_submenu_page(
            parent_slug:
                self::MENU_SLUG,

            page_title:
                __(
                    'Suscripciones',
                    'dsm-suscripciones'
                ),

            menu_title:
                __(
                    'Suscripciones',
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

        $subscriptionRepository =
            new SubscriptionRepository();

        $planRepository =
            new SubscriptionPlanRepository();

        $customerRepository =
            new CustomerRepository();

        $subscriptions =
            $subscriptionRepository
                ->findAll();

        $rows = [];

        foreach ($subscriptions as $subscription) {
            $plan =
                $planRepository
                    ->findById(
                        $subscription->getPlanId()
                    );

            $customer =
                $customerRepository
                    ->findById(
                        $subscription->getCustomerId()
                    );

            $rows[] = [
                'subscription' =>
                    $subscription,

                'plan' =>
                    $plan,

                'customer' =>
                    $customer,
            ];
        }

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

        $template =
            DSM_SUSCRIPCIONES_PATH
            . 'templates/admin/subscriptions-list.php';

        if (!is_file($template)) {
            throw new RuntimeException(
                'No se encontró la plantilla de suscripciones.'
            );
        }

        require $template;
    }

    public function handleCancel(): void
    {
        $this->assertPermission();

        $subscriptionId =
            isset($_POST['subscription_id'])
                ? absint(
                    wp_unslash(
                        (string) $_POST['subscription_id']
                    )
                )
                : 0;

        if ($subscriptionId <= 0) {
            $this->redirect(
                [
                    'error' =>
                        'La suscripción indicada no es válida.',
                ]
            );
        }

        check_admin_referer(
            self::getNonceAction(
                $subscriptionId
            ),
            self::NONCE_NAME
        );

        try {
            (
                new SubscriptionRepository()
            )->cancel(
                $subscriptionId
            );

            $this->redirect(
                [
                    'notice' =>
                        'cancelled',
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

    public static function getCancelAction(): string
    {
        return self::CANCEL_ACTION;
    }

    public static function getNonceAction(
        int $subscriptionId
    ): string {
        return self::NONCE_ACTION_PREFIX
            . $subscriptionId;
    }

    public static function getNonceName(): string
    {
        return self::NONCE_NAME;
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

    private function __construct()
    {
    }
}
