<?php

declare(strict_types=1);

namespace DSM\Ofertas\Admin;

use DSM\Ofertas\Application\AssignOfferToCustomer;
use DSM\Ofertas\Application\SaveOffer;
use DSM\Ofertas\Offer\OfferCustomerRepository;
use DSM\Ofertas\Offer\OfferRedemptionRepository;
use DSM\Ofertas\Offer\OfferRepository;
use DSM\Suscripciones\Subscription\SubscriptionPlanRepository;
use RuntimeException;
use Throwable;

if (!defined('ABSPATH')) {
    exit;
}

final class OffersPage
{
    public const MENU_SLUG =
        'dsm-ofertas';

    private const CAPABILITY =
        'manage_options';

    private const SAVE_ACTION =
        'dsm_offer_save';

    private const NONCE_ACTION =
        'dsm_offer_save';

    private const NONCE_NAME =
        'dsm_offer_nonce';

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
            30
        );

        add_action(
            'admin_post_'
            . self::SAVE_ACTION,
            [
                $page,
                'handleSave',
            ]
        );

        add_action(
            'admin_enqueue_scripts',
            [
                $page,
                'enqueueAssets',
            ]
        );

        add_action(
            'admin_post_dsm_offer_assign_customer',
            [
                $page,
                'handleAssignCustomer',
            ]
        );

        add_action(
            'admin_post_dsm_offer_revoke_customer',
            [
                $page,
                'handleRevokeCustomer',
            ]
        );
    }

    public function registerMenu(): void
    {
        add_menu_page(
            page_title:
                __(
                    'DSM Ofertas',
                    'dsm-ofertas'
                ),

            menu_title:
                __(
                    'DSM Ofertas',
                    'dsm-ofertas'
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
                'dashicons-tickets-alt',

            position:
                29
        );
    }

    public function enqueueAssets(
        string $hookSuffix
    ): void {
        if (
            $hookSuffix
            !== 'toplevel_page_'
                . self::MENU_SLUG
        ) {
            return;
        }

        wp_enqueue_style(
            'dsm-ofertas-admin',
            DSM_OFERTAS_URL
                . 'assets/admin/css/offers.css',
            [],
            DSM_OFERTAS_VERSION
        );
    }

    public function render(): void
    {
        $this->assertPermission();

        $repository =
            new OfferRepository();

        $offerId =
            isset($_GET['offer_id'])
                ? max(
                    0,
                    (int) $_GET['offer_id']
                )
                : 0;

        $offer =
            $offerId > 0
                ? $repository
                    ->findById(
                        $offerId
                    )
                : null;

        $rules =
            $offerId > 0
                ? $repository
                    ->findRules(
                        $offerId
                    )
                : [];

        $offers =
            $repository
                ->findAll();

        $assignedCustomers =
            $offerId > 0
                ? (
                    new OfferCustomerRepository()
                )->findByOfferId(
                    $offerId
                )
                : [];

        $redemptions =
            (
                new OfferRedemptionRepository()
            )->findRecent(
                100
            );

        $planRepository =
            new SubscriptionPlanRepository();

        $plans =
            $planRepository
                ->findAll();

        $notice =
            isset($_GET['notice'])
                ? sanitize_key(
                    wp_unslash(
                        (string)
                        $_GET['notice']
                    )
                )
                : '';

        $error =
            isset($_GET['error'])
                ? sanitize_text_field(
                    wp_unslash(
                        (string)
                        $_GET['error']
                    )
                )
                : '';

        $template =
            DSM_OFERTAS_PATH
            . 'templates/admin/offers.php';

        if (!is_file($template)) {
            throw new RuntimeException(
                'No se encontró la plantilla de DSM Ofertas.'
            );
        }

        require $template;
    }

    public function handleSave(): void
    {
        $this->assertPermission();

        check_admin_referer(
            self::NONCE_ACTION,
            self::NONCE_NAME
        );

        try {
            $offerId =
                isset($_POST['offer_id'])
                    ? max(
                        0,
                        (int)
                        $_POST['offer_id']
                    )
                    : 0;

            $rules =
                isset($_POST['rules'])
                && is_array(
                    $_POST['rules']
                )
                    ? wp_unslash(
                        $_POST['rules']
                    )
                    : [];

            $saveOffer =
                new SaveOffer();

            $savedOfferId =
                $saveOffer
                    ->execute(
                        $offerId > 0
                            ? $offerId
                            : null,

                        [
                            'code' =>
                                wp_unslash(
                                    $_POST['code']
                                    ?? ''
                                ),

                            'name' =>
                                wp_unslash(
                                    $_POST['name']
                                    ?? ''
                                ),

                            'description' =>
                                wp_unslash(
                                    $_POST['description']
                                    ?? ''
                                ),

                            'scope' =>
                                wp_unslash(
                                    $_POST['scope']
                                    ?? 'general'
                                ),

                            'status' =>
                                wp_unslash(
                                    $_POST['status']
                                    ?? 'draft'
                                ),

                            'starts_at' =>
                                wp_unslash(
                                    $_POST['starts_at']
                                    ?? ''
                                ),

                            'ends_at' =>
                                wp_unslash(
                                    $_POST['ends_at']
                                    ?? ''
                                ),

                            'priority' =>
                                wp_unslash(
                                    $_POST['priority']
                                    ?? 0
                                ),

                            'stackable' =>
                                isset(
                                    $_POST['stackable']
                                ),
                        ],

                        $rules
                    );

            $this->redirect(
                [
                    'notice' =>
                        'saved',

                    'offer_id' =>
                        $savedOfferId,
                ]
            );
        } catch (Throwable $exception) {
            $this->redirect(
                [
                    'error' =>
                        $exception
                            ->getMessage(),
                ]
            );
        }
    }

    public function handleAssignCustomer(): void
    {
        $this->assertPermission();

        $offerId =
            isset($_POST['offer_id'])
                ? absint(
                    wp_unslash(
                        (string)
                        $_POST['offer_id']
                    )
                )
                : 0;

        check_admin_referer(
            'dsm_offer_assign_customer_'
            . $offerId,
            'dsm_offer_customer_nonce'
        );

        try {
            (
                new AssignOfferToCustomer()
            )->execute(
                $offerId,
                wp_unslash(
                    (string) (
                        $_POST[
                            'customer_reference'
                        ]
                        ?? ''
                    )
                ),
                wp_unslash(
                    (string) (
                        $_POST[
                            'internal_note'
                        ]
                        ?? ''
                    )
                )
            );

            $this->redirect(
                [
                    'offer_id' =>
                        $offerId,

                    'notice' =>
                        'customer_assigned',
                ]
            );
        } catch (Throwable $exception) {
            $this->redirect(
                [
                    'offer_id' =>
                        $offerId,

                    'error' =>
                        $exception
                            ->getMessage(),
                ]
            );
        }
    }

    public function handleRevokeCustomer(): void
    {
        $this->assertPermission();

        $offerId =
            isset($_POST['offer_id'])
                ? absint(
                    wp_unslash(
                        (string)
                        $_POST['offer_id']
                    )
                )
                : 0;

        $customerId =
            isset($_POST['customer_id'])
                ? absint(
                    wp_unslash(
                        (string)
                        $_POST['customer_id']
                    )
                )
                : 0;

        check_admin_referer(
            'dsm_offer_revoke_customer_'
            . $offerId
            . '_'
            . $customerId,
            'dsm_offer_customer_nonce'
        );

        try {
            (
                new OfferCustomerRepository()
            )->revoke(
                $offerId,
                $customerId
            );

            $this->redirect(
                [
                    'offer_id' =>
                        $offerId,

                    'notice' =>
                        'customer_revoked',
                ]
            );
        } catch (Throwable $exception) {
            $this->redirect(
                [
                    'offer_id' =>
                        $offerId,

                    'error' =>
                        $exception
                            ->getMessage(),
                ]
            );
        }
    }

    private function redirect(
        array $arguments = []
    ): never {
        wp_safe_redirect(
            add_query_arg(
                $arguments,
                admin_url(
                    'admin.php?page='
                    . self::MENU_SLUG
                )
            )
        );

        exit;
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
                    'No tienes permisos para acceder a DSM Ofertas.',
                    'dsm-ofertas'
                )
            );
        }
    }
}
