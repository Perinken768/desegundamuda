<?php

declare(strict_types=1);

namespace DSM\Directos\Admin;

use DSM\Suscripciones\Subscription\SubscriptionPlanRepository;
use RuntimeException;
use Throwable;

if (!defined('ABSPATH')) {
    exit;
}

final class DirectSettingsPage
{
    public const MENU_SLUG =
        'dsm-directos';

    private const CAPABILITY =
        'manage_options';

    private const SAVE_ACTION =
        'dsm_directos_save_settings';

    private const NONCE_ACTION =
        'dsm_directos_save_settings';

    private const NONCE_NAME =
        'dsm_directos_settings_nonce';

    private const FEATURE_KEY =
        'directos';

    public static function register(): void
    {
        $page =
            new self();

        add_action(
            'admin_menu',
            [
                $page,
                'registerMenu',
            ]
        );

        add_action(
            'admin_post_'
            . self::SAVE_ACTION,
            [
                $page,
                'handleSave',
            ]
        );
    }

    public function registerMenu(): void
    {
        add_menu_page(
            page_title:
                __(
                    'DSM Directos',
                    'dsm-directos'
                ),

            menu_title:
                __(
                    'DSM Directos',
                    'dsm-directos'
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
                'dashicons-video-alt3',

            position:
                29
        );

        add_submenu_page(
            parent_slug:
                self::MENU_SLUG,

            page_title:
                __(
                    'Configuración',
                    'dsm-directos'
                ),

            menu_title:
                __(
                    'Configuración',
                    'dsm-directos'
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

        $repository =
            new SubscriptionPlanRepository();

        $plans =
            $repository->findAll();

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
            DSM_DIRECTOS_PATH
            . 'templates/admin/settings.php';

        if (!is_file($template)) {
            throw new RuntimeException(
                'No se encontró la plantilla de configuración de DSM Directos.'
            );
        }

        require $template;
    }

    public function handleSave(): never
    {
        $this->assertPermission();

        check_admin_referer(
            self::NONCE_ACTION,
            self::NONCE_NAME
        );

        try {
            $repository =
                new SubscriptionPlanRepository();

            $plans =
                $repository->findAll();

            $selectedPlanIds =
                isset($_POST['direct_plan_ids'])
                && is_array(
                    $_POST['direct_plan_ids']
                )
                    ? array_values(
                        array_unique(
                            array_filter(
                                array_map(
                                    'absint',
                                    wp_unslash(
                                        $_POST['direct_plan_ids']
                                    )
                                )
                            )
                        )
                    )
                    : [];

            foreach ($plans as $plan) {
                $planId =
                    $plan->getId();

                if (
                    in_array(
                        $planId,
                        $selectedPlanIds,
                        true
                    )
                ) {
                    $repository->setFeature(
                        $planId,
                        self::FEATURE_KEY,
                        '1'
                    );

                    continue;
                }

                $repository->deleteFeature(
                    $planId,
                    self::FEATURE_KEY
                );
            }

            $this->redirect(
                [
                    'notice' =>
                        'saved',
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
                    'No tienes permisos para acceder a DSM Directos.',
                    'dsm-directos'
                )
            );
        }
    }

    /**
     * @param array<string, scalar> $args
     */
    private function redirect(
        array $args = []
    ): never {
        wp_safe_redirect(
            add_query_arg(
                $args,
                admin_url(
                    'admin.php?page='
                    . self::MENU_SLUG
                )
            )
        );

        exit;
    }

    public static function getSaveAction(): string
    {
        return self::SAVE_ACTION;
    }

    public static function getNonceAction(): string
    {
        return self::NONCE_ACTION;
    }

    public static function getNonceName(): string
    {
        return self::NONCE_NAME;
    }
}
