<?php

declare(strict_types=1);

namespace DSM\Anuncios\Admin;

use DSM\Anuncios\Category\CategoryRepository;
use RuntimeException;
use Throwable;

if (!defined('ABSPATH')) {
    exit;
}

final class CategoryAdminController
{
    private const PAGE_SLUG =
        'dsm-anuncios-categories';

    private const SAVE_ACTION =
        'dsm_anuncios_admin_save_category';

    private const TOGGLE_ACTION =
        'dsm_anuncios_admin_toggle_category';

    public function __construct(
        private readonly CategoryRepository $categoryRepository
    ) {
    }

    public function register(): void
    {
        add_action(
            'admin_post_'
                . self::SAVE_ACTION,
            [$this, 'handleSave']
        );

        add_action(
            'admin_post_'
                . self::TOGGLE_ACTION,
            [$this, 'handleToggle']
        );
    }

    public function handleSave(): void
    {
        $this->assertAdministrator();

        check_admin_referer(
            self::SAVE_ACTION,
            'dsm_category_nonce'
        );

        $categoryId = isset($_POST['category_id'])
            ? absint($_POST['category_id'])
            : 0;

        $parentId = isset($_POST['parent_id'])
            ? absint($_POST['parent_id'])
            : 0;

        $name = isset($_POST['name'])
            ? sanitize_text_field(
                wp_unslash($_POST['name'])
            )
            : '';

        $slug = isset($_POST['slug'])
            ? sanitize_title(
                wp_unslash($_POST['slug'])
            )
            : '';

        $description = isset($_POST['description'])
            ? sanitize_textarea_field(
                wp_unslash($_POST['description'])
            )
            : '';

        $marketplaceAllowed =
            isset($_POST['marketplace_allowed']);

        $storeAllowed =
            isset($_POST['store_allowed']);

        $active =
            isset($_POST['is_active']);

        $sortOrder = isset($_POST['sort_order'])
            ? max(
                0,
                (int) $_POST['sort_order']
            )
            : 0;

        try {
            if ($name === '') {
                throw new RuntimeException(
                    'El nombre de la categoría es obligatorio.'
                );
            }

            $data = [
                'parent_id' =>
                    $parentId > 0
                        ? $parentId
                        : null,

                'name' =>
                    $name,

                'slug' =>
                    $slug,

                'description' =>
                    $description,

                'marketplace_allowed' =>
                    $marketplaceAllowed,

                'store_allowed' =>
                    $storeAllowed,

                'is_active' =>
                    $active,

                'sort_order' =>
                    $sortOrder,
            ];

            if ($categoryId > 0) {
                $this->categoryRepository
                    ->update(
                        $categoryId,
                        $data
                    );

                $status =
                    'category_updated';
            } else {
                $categoryId =
                    $this->categoryRepository
                        ->create($data);

                $status =
                    'category_created';
            }

            $this->redirectToList(
                $status
            );
        } catch (Throwable $exception) {
            $this->logError(
                'guardando la categoría',
                $exception
            );

            $this->redirectToForm(
                $categoryId,
                'category_error'
            );
        }
    }

    public function handleToggle(): void
    {
        $this->assertAdministrator();

        check_admin_referer(
            self::TOGGLE_ACTION,
            'dsm_category_nonce'
        );

        $categoryId = isset($_POST['category_id'])
            ? absint($_POST['category_id'])
            : 0;

        $active = isset($_POST['active'])
            && (string) $_POST['active'] === '1';

        try {
            if ($categoryId <= 0) {
                throw new RuntimeException(
                    'El identificador de la categoría no es válido.'
                );
            }

            $this->categoryRepository
                ->setActive(
                    $categoryId,
                    $active
                );

            $this->redirectToList(
                $active
                    ? 'category_activated'
                    : 'category_deactivated'
            );
        } catch (Throwable $exception) {
            $this->logError(
                'cambiando el estado de la categoría',
                $exception
            );

            $this->redirectToList(
                'category_error'
            );
        }
    }

    private function assertAdministrator(): void
    {
        if (!current_user_can('manage_options')) {
            wp_die(
                esc_html__(
                    'No tienes permisos para realizar esta acción.',
                    'dsm-anuncios'
                )
            );
        }
    }

    private function redirectToList(
        string $status
    ): never {
        wp_safe_redirect(
            add_query_arg(
                [
                    'page' =>
                        self::PAGE_SLUG,

                    'admin_status' =>
                        $status,
                ],
                admin_url('admin.php')
            )
        );

        exit;
    }

    private function redirectToForm(
        int $categoryId,
        string $status
    ): never {
        $arguments = [
            'page' =>
                self::PAGE_SLUG,

            'action' =>
                $categoryId > 0
                    ? 'edit'
                    : 'create',

            'admin_status' =>
                $status,
        ];

        if ($categoryId > 0) {
            $arguments['category_id'] =
                $categoryId;
        }

        wp_safe_redirect(
            add_query_arg(
                $arguments,
                admin_url('admin.php')
            )
        );

        exit;
    }

    private function logError(
        string $action,
        Throwable $exception
    ): void {
        error_log(
            sprintf(
                '[DSM Anuncios] Error %s: %s',
                $action,
                $exception->getMessage()
            )
        );
    }
}