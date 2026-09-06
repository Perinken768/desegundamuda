<?php

declare(strict_types=1);

namespace DSM\Directos\Frontend;

use DSM\Directos\Application\SaveDirectItems;
use Throwable;

if (!defined('ABSPATH')) {
    exit;
}

final class DirectItemsController
{
    public const ACTION =
        'dsm_directos_save_items';

    public const NONCE_ACTION =
        'dsm_directos_save_items';

    public const NONCE_NAME =
        'dsm_directos_items_nonce';

    public static function register(): void
    {
        $controller =
            new self();

        add_action(
            'admin_post_'
            . self::ACTION,
            [
                $controller,
                'handleSave',
            ]
        );

        add_action(
            'admin_post_nopriv_'
            . self::ACTION,
            [
                $controller,
                'handleSave',
            ]
        );
    }

    public function handleSave(): never
    {
        check_admin_referer(
            self::NONCE_ACTION,
            self::NONCE_NAME
        );

        try {
            $customerContext =
                apply_filters(
                    'dsm_current_customer_context',
                    null
                );

            $customerId =
                is_array(
                    $customerContext
                )
                    ? max(
                        0,
                        (int) (
                            $customerContext['id']
                            ?? 0
                        )
                    )
                    : 0;

            $advertisementIds =
                isset($_POST['advertisement_ids'])
                && is_array(
                    $_POST['advertisement_ids']
                )
                    ? array_map(
                        'absint',
                        wp_unslash(
                            $_POST['advertisement_ids']
                        )
                    )
                    : [];

            $inventoryQuantities =
                isset($_POST['inventory_quantities'])
                && is_array(
                    $_POST['inventory_quantities']
                )
                    ? wp_unslash(
                        $_POST['inventory_quantities']
                    )
                    : [];

            (
                new SaveDirectItems()
            )->execute(
                $customerId,
                $advertisementIds,
                $inventoryQuantities
            );

            $this->redirect(
                [
                    'direct_section' =>
                        'items',

                    'notice' =>
                        'items-saved',
                ]
            );
        } catch (Throwable $exception) {
            $this->redirect(
                [
                    'direct_section' =>
                        'items',

                    'error' =>
                        $exception->getMessage(),
                ]
            );
        }
    }

    /**
     * @param array<string, scalar> $arguments
     */
    private function redirect(
        array $arguments
    ): never {
        wp_safe_redirect(
            add_query_arg(
                $arguments,
                home_url(
                    '/mis-directos/'
                )
            )
        );

        exit;
    }

    private function __construct()
    {
    }
}
