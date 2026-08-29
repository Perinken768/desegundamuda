<?php

declare(strict_types=1);

namespace DSM\Suscripciones\Admin;

use DSM\Suscripciones\Subscription\SubscriptionPlanRepository;
use RuntimeException;
use Throwable;

if (!defined('ABSPATH')) {
    exit;
}

final class SubscriptionPlansPage
{
    public const MENU_SLUG =
        'dsm-suscripciones-plans';

    public const SAVE_ACTION =
        'dsm_subscription_admin_save_plan';

    public const SAVE_PAGE_COPY_ACTION =
        'dsm_subscription_admin_save_page_copy';

    public const NONCE_NAME =
        'dsm_subscription_plan_nonce';

    private const CAPABILITY =
        'manage_options';

    private const PAGE_COPY_OPTION =
        'dsm_subscription_plans_page_copy';

    private const PLAN_COPY_PREFIX =
        'dsm_subscription_plan_copy_';

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
            . self::SAVE_ACTION,
            [
                $page,
                'handleSavePlan',
            ]
        );

        add_action(
            'admin_post_'
            . self::SAVE_PAGE_COPY_ACTION,
            [
                $page,
                'handleSavePageCopy',
            ]
        );
    }

    public function registerMenu(): void
    {
        add_submenu_page(
            parent_slug:
                SubscriptionsPage::MENU_SLUG,

            page_title:
                __(
                    'Planes',
                    'dsm-suscripciones'
                ),

            menu_title:
                __(
                    'Planes',
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
            )->findAll();

        $pageCopy =
            self::getPageCopy();

        $planCopies = [];

        foreach ($plans as $plan) {
            $planCopies[
                $plan->getId()
            ] =
                self::getPlanCopy(
                    $plan->getId()
                );
        }

        $notice =
            isset($_GET['notice'])
                ? sanitize_key(
                    wp_unslash(
                        (string) $_GET[
                            'notice'
                        ]
                    )
                )
                : '';

        $error =
            isset($_GET['error'])
                ? sanitize_text_field(
                    wp_unslash(
                        (string) $_GET[
                            'error'
                        ]
                    )
                )
                : '';

        $template =
            DSM_SUSCRIPCIONES_PATH
            . 'templates/admin/subscription-plans.php';

        if (!is_file($template)) {
            throw new RuntimeException(
                'No se encontró la plantilla de administración de planes.'
            );
        }

        require $template;
    }

    public function handleSavePageCopy(): never
    {
        $this->assertPermission();

        check_admin_referer(
            self::SAVE_PAGE_COPY_ACTION,
            self::NONCE_NAME
        );

        $title =
            isset($_POST['page_title'])
                ? sanitize_text_field(
                    wp_unslash(
                        (string) $_POST[
                            'page_title'
                        ]
                    )
                )
                : '';

        $description =
            isset($_POST['page_description'])
                ? sanitize_textarea_field(
                    wp_unslash(
                        (string) $_POST[
                            'page_description'
                        ]
                    )
                )
                : '';

        update_option(
            self::PAGE_COPY_OPTION,
            [
                'title' =>
                    $title,

                'description' =>
                    $description,
            ],
            false
        );

        $this->redirect(
            [
                'notice' =>
                    'page-updated',
            ]
        );
    }

    public function handleSavePlan(): never
    {
        $this->assertPermission();

        $planId =
            isset($_POST['plan_id'])
                ? absint(
                    wp_unslash(
                        (string) $_POST[
                            'plan_id'
                        ]
                    )
                )
                : 0;

        if ($planId <= 0) {
            $this->redirect(
                [
                    'error' =>
                        'El plan indicado no es válido.',
                ]
            );
        }

        check_admin_referer(
            self::getSaveNonceAction(
                $planId
            ),
            self::NONCE_NAME
        );

        try {
            $name =
                isset($_POST['name'])
                    ? sanitize_text_field(
                        wp_unslash(
                            (string) $_POST[
                                'name'
                            ]
                        )
                    )
                    : '';

            $description =
                isset($_POST['description'])
                    ? sanitize_textarea_field(
                        wp_unslash(
                            (string) $_POST[
                                'description'
                            ]
                        )
                    )
                    : '';

            (
                new SubscriptionPlanRepository()
            )->updatePresentation(
                $planId,
                $name,
                $description
            );

            $benefitsRaw =
                isset($_POST['benefits'])
                    ? sanitize_textarea_field(
                        wp_unslash(
                            (string) $_POST[
                                'benefits'
                            ]
                        )
                    )
                    : '';

            $benefits =
                array_values(
                    array_filter(
                        array_map(
                            static fn (
                                string $line
                            ): string =>
                                trim($line),

                            preg_split(
                                '/\\R/u',
                                $benefitsRaw
                            )
                            ?: []
                        ),
                        static fn (
                            string $line
                        ): bool =>
                            $line !== ''
                    )
                );

            $ctaLabel =
                isset($_POST['cta_label'])
                    ? sanitize_text_field(
                        wp_unslash(
                            (string) $_POST[
                                'cta_label'
                            ]
                        )
                    )
                    : '';

            update_option(
                self::PLAN_COPY_PREFIX
                . $planId,
                [
                    'benefits' =>
                        $benefits,

                    'cta_label' =>
                        $ctaLabel,
                ],
                false
            );

            $this->redirect(
                [
                    'notice' =>
                        'plan-updated',

                    'plan_id' =>
                        $planId,
                ]
            );
        } catch (Throwable $exception) {
            $this->redirect(
                [
                    'error' =>
                        $exception->getMessage(),

                    'plan_id' =>
                        $planId,
                ]
            );
        }
    }

    public static function getPageCopy(): array
    {
        $stored =
            get_option(
                self::PAGE_COPY_OPTION,
                []
            );

        $stored =
            is_array($stored)
                ? $stored
                : [];

        return [
            'title' =>
                trim(
                    (string) (
                        $stored['title']
                        ?? 'Planes y suscripciones'
                    )
                ),

            'description' =>
                trim(
                    (string) (
                        $stored['description']
                        ?? 'Elige las funcionalidades que necesitas. Cada servicio puede contratarse de forma independiente.'
                    )
                ),
        ];
    }

    public static function getPlanCopy(
        int $planId
    ): array {
        if ($planId <= 0) {
            return [
                'benefits' =>
                    [],

                'cta_label' =>
                    '',
            ];
        }

        $stored =
            get_option(
                self::PLAN_COPY_PREFIX
                . $planId,
                []
            );

        $stored =
            is_array($stored)
                ? $stored
                : [];

        $benefits =
            isset($stored['benefits'])
            && is_array(
                $stored['benefits']
            )
                ? array_values(
                    array_filter(
                        array_map(
                            'strval',
                            $stored['benefits']
                        )
                    )
                )
                : [];

        return [
            'benefits' =>
                $benefits,

            'cta_label' =>
                trim(
                    (string) (
                        $stored['cta_label']
                        ?? ''
                    )
                ),
        ];
    }

    public static function getSaveNonceAction(
        int $planId
    ): string {
        return self::SAVE_ACTION
            . '_'
            . $planId;
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
                    'No tienes permisos para gestionar los planes.',
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

    private function __construct()
    {
    }
}
