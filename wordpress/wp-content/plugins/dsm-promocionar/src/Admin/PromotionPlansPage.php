<?php

declare(strict_types=1);

namespace DSM\Promocionar\Admin;

use DSM\Promocionar\Promotion\PromotionPlanRepository;
use RuntimeException;
use Throwable;

if (!defined('ABSPATH')) {
    exit;
}

final class PromotionPlansPage
{
    public const MENU_SLUG =
        'dsm-promocionar';

    private const CAPABILITY =
        'manage_options';

    private const SAVE_ACTION =
        'dsm_promotion_plan_save';

    private const NONCE_ACTION =
        'dsm_promotion_plan_save';

    private const NONCE_NAME =
        'dsm_promotion_plan_nonce';

    public function __construct(
        private readonly PromotionPlanRepository $planRepository =
            new PromotionPlanRepository()
    ) {
    }

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
                    'DSM Promocionar',
                    'dsm-promocionar'
                ),

            menu_title:
                __(
                    'DSM Promocionar',
                    'dsm-promocionar'
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
                'dashicons-megaphone',

            position:
                27
        );

        add_submenu_page(
            parent_slug:
                self::MENU_SLUG,

            page_title:
                __(
                    'Planes de promoción',
                    'dsm-promocionar'
                ),

            menu_title:
                __(
                    'Planes',
                    'dsm-promocionar'
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
            $this->planRepository
                ->findAll();

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
            DSM_PROMOCIONAR_PATH
            . 'templates/admin/promotion-plans.php';

        if (!is_file($template)) {
            throw new RuntimeException(
                'No se encontró la plantilla de planes de promoción.'
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

        $planId =
            isset($_POST['plan_id'])
                ? absint(
                    wp_unslash(
                        (string) $_POST['plan_id']
                    )
                )
                : 0;

        try {
            if ($planId <= 0) {
                throw new RuntimeException(
                    'El identificador del plan no es válido.'
                );
            }

            $name =
                isset($_POST['name'])
                    ? sanitize_text_field(
                        wp_unslash(
                            (string) $_POST['name']
                        )
                    )
                    : '';

            $durationDays =
                isset($_POST['duration_days'])
                    ? absint(
                        wp_unslash(
                            (string) $_POST['duration_days']
                        )
                    )
                    : 0;

            $priceRaw =
                isset($_POST['price'])
                    ? trim(
                        wp_unslash(
                            (string) $_POST['price']
                        )
                    )
                    : '';

            /*
             * Permitimos tanto coma como punto en administración.
             */
            $priceRaw =
                str_replace(
                    ',',
                    '.',
                    $priceRaw
                );

            if (
                $priceRaw === ''
                || !is_numeric($priceRaw)
            ) {
                throw new RuntimeException(
                    'El precio indicado no es válido.'
                );
            }

            $price =
                (float) $priceRaw;

            $currency =
                isset($_POST['currency'])
                    ? strtoupper(
                        sanitize_text_field(
                            wp_unslash(
                                (string) $_POST['currency']
                            )
                        )
                    )
                    : 'EUR';

            $sortOrder =
                isset($_POST['sort_order'])
                    ? absint(
                        wp_unslash(
                            (string) $_POST['sort_order']
                        )
                    )
                    : 0;

            $isActive =
                isset($_POST['is_active']);

            if ($name === '') {
                throw new RuntimeException(
                    'El nombre del plan es obligatorio.'
                );
            }

            if ($durationDays <= 0) {
                throw new RuntimeException(
                    'La duración debe ser de al menos un día.'
                );
            }

            if ($price < 0) {
                throw new RuntimeException(
                    'El precio no puede ser negativo.'
                );
            }

            if (
                strlen($currency) !== 3
                || !ctype_alpha($currency)
            ) {
                throw new RuntimeException(
                    'La moneda debe contener tres letras.'
                );
            }

            $this->planRepository
                ->update(
                    $planId,
                    [
                        'name' =>
                            $name,

                        'duration_seconds' =>
                            $durationDays
                            * DAY_IN_SECONDS,

                        'price' =>
                            $price,

                        'currency' =>
                            $currency,

                        'is_active' =>
                            $isActive,

                        'sort_order' =>
                            $sortOrder,
                    ]
                );

            $this->redirect(
                [
                    'notice' =>
                        'plan_updated',
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

    /**
     * @param array<string, scalar> $arguments
     */
    private function redirect(
        array $arguments = []
    ): never {
        $url =
            add_query_arg(
                array_merge(
                    [
                        'page' =>
                            self::MENU_SLUG,
                    ],
                    $arguments
                ),
                admin_url(
                    'admin.php'
                )
            );

        wp_safe_redirect(
            $url
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
                    'No tienes permisos para gestionar los planes de promoción.',
                    'dsm-promocionar'
                )
            );
        }
    }
}
