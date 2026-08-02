<?php

declare(strict_types=1);

namespace DSM\Anuncios\Admin;

use DSM\Anuncios\Category\CategoryRepository;

if (!defined('ABSPATH')) {
    exit;
}

final class CategoriesPage
{
    private const MENU_SLUG =
        'dsm-anuncios';

    private const CATEGORIES_SLUG =
        'dsm-anuncios-categories';

    public function __construct(
        private readonly CategoryRepository $categoryRepository
    ) {
    }

    public function register(): void
    {
        add_action(
            'admin_menu',
            [$this, 'registerMenu']
        );

        add_action(
            'admin_enqueue_scripts',
            [$this, 'enqueueAssets']
        );
    }

    public function registerMenu(): void
    {
        add_menu_page(
            __('DSM Anuncios', 'dsm-anuncios'),
            __('DSM Anuncios', 'dsm-anuncios'),
            'manage_options',
            self::MENU_SLUG,
            [$this, 'renderDashboard'],
            'dashicons-megaphone',
            26
        );

        add_submenu_page(
            self::MENU_SLUG,
            __('Anuncios', 'dsm-anuncios'),
            __('Anuncios', 'dsm-anuncios'),
            'manage_options',
            self::MENU_SLUG,
            [$this, 'renderDashboard']
        );

        add_submenu_page(
            self::MENU_SLUG,
            __('Categorías', 'dsm-anuncios'),
            __('Categorías', 'dsm-anuncios'),
            'manage_options',
            self::CATEGORIES_SLUG,
            [$this, 'renderCategories']
        );
    }

    public function renderDashboard(): void
    {
        if (!current_user_can('manage_options')) {
            wp_die(
                esc_html__(
                    'No tienes permisos para acceder a esta página.',
                    'dsm-anuncios'
                )
            );
        }

        ?>
        <div class="wrap">
            <h1>
                <?php
                esc_html_e(
                    'DSM Anuncios',
                    'dsm-anuncios'
                );
                ?>
            </h1>

            <p>
                <?php
                esc_html_e(
                    'Gestión de anuncios, categorías, imágenes y moderación de DeSegundaMuda.',
                    'dsm-anuncios'
                );
                ?>
            </p>

            <div class="notice notice-info inline">
                <p>
                    <?php
                    esc_html_e(
                        'La gestión completa de anuncios se añadirá en los siguientes bloques de desarrollo.',
                        'dsm-anuncios'
                    );
                    ?>
                </p>
            </div>
        </div>
        <?php
    }

    public function renderCategories(): void
    {
        if (!current_user_can('manage_options')) {
            wp_die(
                esc_html__(
                    'No tienes permisos para administrar categorías.',
                    'dsm-anuncios'
                )
            );
        }

        $action = isset($_GET['action'])
            ? sanitize_key(
                wp_unslash($_GET['action'])
            )
            : '';

        $categoryId = isset($_GET['category_id'])
            ? absint($_GET['category_id'])
            : 0;

        if (
            in_array(
                $action,
                [
                    'create',
                    'edit',
                ],
                true
            )
        ) {
            $category = $categoryId > 0
                ? $this->categoryRepository
                    ->findById($categoryId)
                : null;

            $categories =
                $this->categoryRepository
                    ->findAll();

            $template =
                DSM_ANUNCIOS_PATH
                . 'templates/admin/'
                . 'category-form.php';

            if (is_file($template)) {
                require $template;
            }

            return;
        }

        $categories =
            $this->categoryRepository
                ->findAll();

        $template =
            DSM_ANUNCIOS_PATH
            . 'templates/admin/'
            . 'categories-list.php';

        if (is_file($template)) {
            require $template;
        }
    }

    public function enqueueAssets(
        string $hookSuffix
    ): void {
        if (
            !str_contains(
                $hookSuffix,
                'dsm-anuncios'
            )
        ) {
            return;
        }

        wp_enqueue_style(
            'dsm-anuncios-admin',
            DSM_ANUNCIOS_URL
                . 'assets/admin/css/'
                . 'advertisements.css',
            [],
            DSM_ANUNCIOS_VERSION
        );

        wp_enqueue_script(
            'dsm-anuncios-admin',
            DSM_ANUNCIOS_URL
                . 'assets/admin/js/'
                . 'advertisements.js',
            [],
            DSM_ANUNCIOS_VERSION,
            true
        );
    }
}