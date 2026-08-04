<?php

declare(strict_types=1);

namespace DSM\Anuncios\Admin;

use DSM\Anuncios\Category\CategoryRepository;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Administración de categorías de anuncios.
 *
 * El menú principal DSM Anuncios lo registra AdvertisementsPage.
 * Esta clase registra únicamente el submenú Categorías.
 */
final class CategoriesPage
{
    public const CATEGORIES_SLUG =
        'dsm-anuncios-categories';

    private const PARENT_SLUG =
        AdvertisementsPage::MENU_SLUG;

    private const CAPABILITY =
        'manage_options';

    private string $hookSuffix = '';

    public function __construct(
        private readonly CategoryRepository $categoryRepository
    ) {
    }

    /**
     * Registra menú y recursos.
     */
    public function register(): void
    {
        add_action(
            'admin_menu',
            [$this, 'registerMenu'],
            10
        );

        add_action(
            'admin_enqueue_scripts',
            [$this, 'enqueueAssets']
        );
    }

    /**
     * Registra únicamente el submenú Categorías.
     */
    public function registerMenu(): void
    {
        $this->hookSuffix =
            (string) add_submenu_page(
                parent_slug:
                    self::PARENT_SLUG,

                page_title:
                    __(
                        'Categorías de anuncios',
                        'dsm-anuncios'
                    ),

                menu_title:
                    __(
                        'Categorías',
                        'dsm-anuncios'
                    ),

                capability:
                    self::CAPABILITY,

                menu_slug:
                    self::CATEGORIES_SLUG,

                callback:
                    [$this, 'renderCategories']
            );
    }

    /**
     * Renderiza el listado o formulario de categorías.
     */
    public function renderCategories(): void
    {
        $this->assertPermission();

        $action =
            isset($_GET['action'])
                ? sanitize_key(
                    wp_unslash(
                        (string) $_GET['action']
                    )
                )
                : '';

        $categoryId =
            isset($_GET['category_id'])
                ? absint(
                    wp_unslash(
                        (string) $_GET[
                            'category_id'
                        ]
                    )
                )
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
            $this->renderForm(
                $categoryId
            );

            return;
        }

        $this->renderList();
    }

    /**
     * Muestra el formulario de creación o edición.
     */
    private function renderForm(
        int $categoryId
    ): void {
        $category =
            $categoryId > 0
                ? $this->categoryRepository
                    ->findById(
                        $categoryId
                    )
                : null;

        if (
            $categoryId > 0
            && $category === null
        ) {
            $this->renderErrorPage(
                __(
                    'La categoría solicitada no existe.',
                    'dsm-anuncios'
                )
            );

            return;
        }

        $categories =
            $this->categoryRepository
                ->findAll();

        $template =
            DSM_ANUNCIOS_PATH
            . 'templates/admin/'
            . 'category-form.php';

        if (!is_file($template)) {
            $this->renderErrorPage(
                __(
                    'No se encontró la plantilla del formulario de categorías.',
                    'dsm-anuncios'
                )
            );

            return;
        }

        require $template;
    }

    /**
     * Muestra el listado.
     */
    private function renderList(): void
    {
        $categories =
            $this->categoryRepository
                ->findAll();

        $template =
            DSM_ANUNCIOS_PATH
            . 'templates/admin/'
            . 'categories-list.php';

        if (!is_file($template)) {
            $this->renderErrorPage(
                __(
                    'No se encontró la plantilla del listado de categorías.',
                    'dsm-anuncios'
                )
            );

            return;
        }

        require $template;
    }

    /**
     * Carga recursos solamente en Categorías.
     */
    public function enqueueAssets(
        string $hookSuffix
    ): void {
        $page =
            isset($_GET['page'])
                ? sanitize_key(
                    wp_unslash(
                        (string) $_GET['page']
                    )
                )
                : '';

        if (
            $hookSuffix !== $this->hookSuffix
            && $page !== self::CATEGORIES_SLUG
        ) {
            return;
        }

        $cssRelativePath =
            'assets/admin/css/advertisements.css';

        $cssFile =
            DSM_ANUNCIOS_PATH
            . $cssRelativePath;

        wp_enqueue_style(
            'dsm-anuncios-admin',
            DSM_ANUNCIOS_URL
            . $cssRelativePath,
            [],
            is_file($cssFile)
                ? (string) filemtime(
                    $cssFile
                )
                : DSM_ANUNCIOS_VERSION
        );

        $jsRelativePath =
            'assets/admin/js/advertisements.js';

        $jsFile =
            DSM_ANUNCIOS_PATH
            . $jsRelativePath;

        wp_enqueue_script(
            'dsm-anuncios-admin',
            DSM_ANUNCIOS_URL
            . $jsRelativePath,
            [],
            is_file($jsFile)
                ? (string) filemtime(
                    $jsFile
                )
                : DSM_ANUNCIOS_VERSION,
            true
        );
    }

    /**
     * Página de error controlada.
     */
    private function renderErrorPage(
        string $message
    ): void {
        ?>
        <div class="wrap dsm-anuncios-admin">
            <h1>
                <?php
                esc_html_e(
                    'Categorías',
                    'dsm-anuncios'
                );
                ?>
            </h1>

            <div class="notice notice-error">
                <p>
                    <?php echo esc_html($message); ?>
                </p>
            </div>

            <p>
                <a
                    class="button button-primary"
                    href="<?php echo esc_url(
                        $this->getListUrl()
                    ); ?>"
                >
                    <?php
                    esc_html_e(
                        'Volver a categorías',
                        'dsm-anuncios'
                    );
                    ?>
                </a>
            </p>
        </div>
        <?php
    }

    /**
     * URL del listado de categorías.
     */
    private function getListUrl(): string
    {
        return add_query_arg(
            [
                'page' =>
                    self::CATEGORIES_SLUG,
            ],
            admin_url('admin.php')
        );
    }

    /**
     * Comprueba permisos administrativos.
     */
    private function assertPermission(): void
    {
        if (
            current_user_can(
                self::CAPABILITY
            )
        ) {
            return;
        }

        wp_die(
            esc_html__(
                'No tienes permisos para administrar categorías.',
                'dsm-anuncios'
            ),
            esc_html__(
                'Acceso denegado',
                'dsm-anuncios'
            ),
            [
                'response' => 403,
            ]
        );
    }
}