<?php

declare(strict_types=1);

namespace DSM\Directos\Frontend;

use DSM\Directos\Application\DirectSelectionService;
use Throwable;

if (!defined('ABSPATH')) {
    exit;
}

final class DirectSelectionController
{
    public const ADVERTISEMENT_ACTION =
        'dsm_directos_toggle_advertisement';

    public const VARIANT_ACTION =
        'dsm_directos_toggle_variant';

    public const NONCE_FIELD =
        'dsm_directos_selection_nonce';

    public static function register(): void
    {
        $controller =
            new self();

        foreach (
            [
                self::ADVERTISEMENT_ACTION =>
                    'handleAdvertisement',

                self::VARIANT_ACTION =>
                    'handleVariant',
            ]
            as $action => $method
        ) {
            add_action(
                'admin_post_'
                . $action,
                [
                    $controller,
                    $method,
                ]
            );

            add_action(
                'admin_post_nopriv_'
                . $action,
                [
                    $controller,
                    $method,
                ]
            );

            add_action(
                'wp_ajax_'
                . $action,
                [
                    $controller,
                    $method,
                ]
            );

            add_action(
                'wp_ajax_nopriv_'
                . $action,
                [
                    $controller,
                    $method,
                ]
            );
        }
    }

    public function handleAdvertisement(): never
    {
        try {
            $advertisementId =
                isset($_POST['advertisement_id'])
                    ? absint(
                        wp_unslash(
                            (string) $_POST[
                                'advertisement_id'
                            ]
                        )
                    )
                    : 0;

            check_admin_referer(
                self::getAdvertisementNonceAction(
                    $advertisementId
                ),
                self::NONCE_FIELD
            );

            $customerId =
                $this->resolveCustomerId();

            $selected =
                (
                    new DirectSelectionService()
                )->toggleAdvertisement(
                    $customerId,
                    $advertisementId
                );

            if ($this->isAjaxRequest()) {
                wp_send_json_success(
                    [
                        'selected' =>
                            $selected,
                    ]
                );
            }

            $this->redirect();
        } catch (Throwable $exception) {
            if ($this->isAjaxRequest()) {
                wp_send_json_error(
                    [
                        'message' =>
                            $exception->getMessage(),
                    ],
                    400
                );
            }

            $this->redirect(
                $exception->getMessage()
            );
        }
    }

    public function handleVariant(): never
    {
        try {
            $variantId =
                isset($_POST['variant_id'])
                    ? absint(
                        wp_unslash(
                            (string) $_POST[
                                'variant_id'
                            ]
                        )
                    )
                    : 0;

            check_admin_referer(
                self::getVariantNonceAction(
                    $variantId
                ),
                self::NONCE_FIELD
            );

            $customerId =
                $this->resolveCustomerId();

            $selected =
                (
                    new DirectSelectionService()
                )->toggleVariant(
                    $customerId,
                    $variantId
                );

            if ($this->isAjaxRequest()) {
                wp_send_json_success(
                    [
                        'selected' =>
                            $selected,
                    ]
                );
            }

            $this->redirect();
        } catch (Throwable $exception) {
            if ($this->isAjaxRequest()) {
                wp_send_json_error(
                    [
                        'message' =>
                            $exception->getMessage(),
                    ],
                    400
                );
            }

            $this->redirect(
                $exception->getMessage()
            );
        }
    }

    public static function getAdvertisementNonceAction(
        int $advertisementId
    ): string {
        return 'dsm_directos_advertisement_'
            . max(
                0,
                $advertisementId
            );
    }

    public static function getVariantNonceAction(
        int $variantId
    ): string {
        return 'dsm_directos_variant_'
            . max(
                0,
                $variantId
            );
    }

    private function resolveCustomerId(): int
    {
        $customer =
            apply_filters(
                'dsm_current_customer_context',
                null
            );

        return is_array($customer)
            ? max(
                0,
                (int) (
                    $customer['id']
                    ?? 0
                )
            )
            : 0;
    }

    private function isAjaxRequest(): bool
    {
        return isset(
            $_POST['dsm_directos_ajax']
        )
        && (string) wp_unslash(
            $_POST['dsm_directos_ajax']
        ) === '1';
    }

    private function redirect(
        string $error = ''
    ): never {
        $redirect =
            isset($_POST['redirect_to'])
                ? wp_validate_redirect(
                    wp_unslash(
                        (string) $_POST[
                            'redirect_to'
                        ]
                    ),
                    ''
                )
                : '';

        if ($redirect === '') {
            $redirect =
                home_url(
                    '/mis-directos/'
                );
        }

        if ($error !== '') {
            $redirect =
                add_query_arg(
                    'dsm_direct_error',
                    $error,
                    $redirect
                );
        }

        wp_safe_redirect(
            $redirect
        );

        exit;
    }

    private function __construct()
    {
    }
}
