<?php

declare(strict_types=1);

namespace DSM\Catalogo\Admin;

use DSM\Catalogo\Brand\Brand;
use DSM\Catalogo\Brand\BrandRepository;
use RuntimeException;
use Throwable;

if (!defined('ABSPATH')) {
    exit;
}

final class BrandAdminController
{
    public const PARENT_SLUG =
        'dsm-catalogo';

    public const PAGE_SLUG =
        'dsm-catalogo-brands';

    private const CAPABILITY =
        'manage_options';

    private const SAVE_ACTION =
        'dsm_catalogo_save_brand';

    private const TOGGLE_ACTIVE_ACTION =
        'dsm_catalogo_toggle_brand_active';

    private const TOGGLE_VERIFIED_ACTION =
        'dsm_catalogo_toggle_brand_verified';

    private const SAVE_NONCE_ACTION =
        'dsm_catalogo_save_brand';

    private const SAVE_NONCE_FIELD =
        'dsm_catalogo_brand_nonce';

    private const TOGGLE_ACTIVE_NONCE_ACTION =
        'dsm_catalogo_toggle_brand_active';

    private const TOGGLE_VERIFIED_NONCE_ACTION =
        'dsm_catalogo_toggle_brand_verified';

    private BrandRepository $repository;

    public function __construct(
        ?BrandRepository $repository = null
    ) {
        $this->repository =
            $repository
            ?? new BrandRepository();
    }

    public static function register(): void
    {
        $controller =
            new self();

        add_action(
            'admin_menu',
            [$controller, 'registerMenu']
        );

        add_action(
            'admin_post_' . self::SAVE_ACTION,
            [$controller, 'handleSave']
        );

        add_action(
            'admin_post_' . self::TOGGLE_ACTIVE_ACTION,
            [$controller, 'handleToggleActive']
        );

        add_action(
            'admin_post_' . self::TOGGLE_VERIFIED_ACTION,
            [$controller, 'handleToggleVerified']
        );

        add_action(
            'admin_enqueue_scripts',
            [$controller, 'enqueueAssets']
        );
    }

    public function registerMenu(): void
    {
        add_submenu_page(
            parent_slug:
                self::PARENT_SLUG,

            page_title:
                __(
                    'Marcas',
                    'dsm-catalogo'
                ),

            menu_title:
                __(
                    'Marcas',
                    'dsm-catalogo'
                ),

            capability:
                self::CAPABILITY,

            menu_slug:
                self::PAGE_SLUG,

            callback:
                [$this, 'renderPage']
        );
    }

    public function renderPage(): void
    {
        $this->assertPermission();

        $view = isset($_GET['view'])
            ? sanitize_key(
                wp_unslash(
                    (string) $_GET['view']
                )
            )
            : 'list';

        if (
            $view === 'new'
            || $view === 'edit'
        ) {
            $this->renderForm(
                $view
            );

            return;
        }

        $this->renderList();
    }

    public function handleSave(): void
    {
        $this->assertPermission();

        check_admin_referer(
            self::SAVE_NONCE_ACTION,
            self::SAVE_NONCE_FIELD
        );

        $brandId = isset($_POST['brand_id'])
            ? absint(
                wp_unslash(
                    (string) $_POST['brand_id']
                )
            )
            : 0;

        try {
            $data =
                $this->sanitizeBrandInput(
                    $_POST
                );

            if ($brandId > 0) {
                $this->repository->update(
                    $brandId,
                    $data
                );

                $this->redirectWithNotice(
                    notice:
                        'brand_updated',

                    additionalArguments: [
                        'view' =>
                            'edit',

                        'brand_id' =>
                            $brandId,
                    ]
                );
            }

            $createdBrandId =
                $this->repository->create(
                    $data
                );

            $this->redirectWithNotice(
                notice:
                    'brand_created',

                additionalArguments: [
                    'view' =>
                        'edit',

                    'brand_id' =>
                        $createdBrandId,
                ]
            );
        } catch (Throwable $exception) {
            $this->redirectWithError(
                $exception->getMessage(),
                [
                    'view' =>
                        $brandId > 0
                            ? 'edit'
                            : 'new',

                    'brand_id' =>
                        $brandId > 0
                            ? $brandId
                            : null,
                ]
            );
        }
    }

    public function handleToggleActive(): void
    {
        $this->assertPermission();

        $brandId = isset($_GET['brand_id'])
            ? absint(
                wp_unslash(
                    (string) $_GET['brand_id']
                )
            )
            : 0;

        check_admin_referer(
            self::TOGGLE_ACTIVE_NONCE_ACTION
            . '_'
            . $brandId
        );

        try {
            $brand =
                $this->getRequiredBrand(
                    $brandId
                );

            $this->repository->setActive(
                $brandId,
                !$brand->isActive()
            );

            $this->redirectWithNotice(
                $brand->isActive()
                    ? 'brand_deactivated'
                    : 'brand_activated'
            );
        } catch (Throwable $exception) {
            $this->redirectWithError(
                $exception->getMessage()
            );
        }
    }

    public function handleToggleVerified(): void
    {
        $this->assertPermission();

        $brandId = isset($_GET['brand_id'])
            ? absint(
                wp_unslash(
                    (string) $_GET['brand_id']
                )
            )
            : 0;

        check_admin_referer(
            self::TOGGLE_VERIFIED_NONCE_ACTION
            . '_'
            . $brandId
        );

        try {
            $brand =
                $this->getRequiredBrand(
                    $brandId
                );

            $this->repository->setVerified(
                $brandId,
                !$brand->isVerified()
            );

            $this->redirectWithNotice(
                $brand->isVerified()
                    ? 'brand_unverified'
                    : 'brand_verified'
            );
        } catch (Throwable $exception) {
            $this->redirectWithError(
                $exception->getMessage()
            );
        }
    }

    public function enqueueAssets(
        string $hookSuffix
    ): void {
        $page = isset($_GET['page'])
            ? sanitize_key(
                wp_unslash(
                    (string) $_GET['page']
                )
            )
            : '';

        if (
            $page !== self::PAGE_SLUG
            && $page !== self::PARENT_SLUG
        ) {
            return;
        }

        $cssFile =
            DSM_CATALOGO_PATH
            . 'assets/admin/css/catalog.css';

        if (is_file($cssFile)) {
            wp_enqueue_style(
                'dsm-catalogo-admin',
                DSM_CATALOGO_URL
                    . 'assets/admin/css/catalog.css',
                [],
                DSM_CATALOGO_VERSION
            );
        }

        $jsFile =
            DSM_CATALOGO_PATH
            . 'assets/admin/js/catalog.js';

        if (is_file($jsFile)) {
            wp_enqueue_media();

            wp_enqueue_script(
                'dsm-catalogo-admin',
                DSM_CATALOGO_URL
                    . 'assets/admin/js/catalog.js',
                [
                    'jquery',
                ],
                DSM_CATALOGO_VERSION,
                true
            );
        }

        unset($hookSuffix);
    }

    private function renderList(): void
    {
        $brands =
            $this->repository->findAll();

        $notice =
            $this->getNotice();

        $error =
            $this->getError();

        $createUrl = add_query_arg(
            [
                'page' =>
                    self::PAGE_SLUG,

                'view' =>
                    'new',
            ],
            admin_url('admin.php')
        );

        $template =
            DSM_CATALOGO_PATH
            . 'templates/admin/brands-list.php';

        if (!is_file($template)) {
            throw new RuntimeException(
                sprintf(
                    'No se encontró la plantilla de marcas: %s',
                    $template
                )
            );
        }

        require $template;
    }

    private function renderForm(
        string $view
    ): void {
        $brand = null;

        if ($view === 'edit') {
            $brandId = isset($_GET['brand_id'])
                ? absint(
                    wp_unslash(
                        (string) $_GET['brand_id']
                    )
                )
                : 0;

            try {
                $brand =
                    $this->getRequiredBrand(
                        $brandId
                    );
            } catch (Throwable $exception) {
                $this->redirectWithError(
                    $exception->getMessage()
                );
            }
        }

        $notice =
            $this->getNotice();

        $error =
            $this->getError();

        $formAction =
            admin_url('admin-post.php');

        $listUrl =
            $this->getListUrl();

        $template =
            DSM_CATALOGO_PATH
            . 'templates/admin/brand-form.php';

        if (!is_file($template)) {
            throw new RuntimeException(
                sprintf(
                    'No se encontró la plantilla del formulario de marca: %s',
                    $template
                )
            );
        }

        require $template;
    }

    /**
     * @param array<string, mixed> $source
     *
     * @return array<string, mixed>
     */
    private function sanitizeBrandInput(
        array $source
    ): array {
        $name = isset($source['name'])
            ? sanitize_text_field(
                wp_unslash(
                    (string) $source['name']
                )
            )
            : '';

        $slug = isset($source['slug'])
            ? sanitize_title(
                wp_unslash(
                    (string) $source['slug']
                )
            )
            : '';

        $description =
            isset($source['description'])
                ? sanitize_textarea_field(
                    wp_unslash(
                        (string) $source['description']
                    )
                )
                : null;

        $website =
            isset($source['website'])
                ? esc_url_raw(
                    wp_unslash(
                        (string) $source['website']
                    )
                )
                : null;

        $logoId = isset($source['logo_id'])
            ? absint(
                wp_unslash(
                    (string) $source['logo_id']
                )
            )
            : null;

        $sortOrder = isset($source['sort_order'])
            ? max(
                0,
                (int) wp_unslash(
                    (string) $source['sort_order']
                )
            )
            : 0;

        return [
            'name' =>
                $name,

            'slug' =>
                $slug !== ''
                    ? $slug
                    : $name,

            'description' =>
                $description !== ''
                    ? $description
                    : null,

            'website' =>
                $website !== ''
                    ? $website
                    : null,

            'logo_id' =>
                $logoId > 0
                    ? $logoId
                    : null,

            'is_active' =>
                isset($source['is_active'])
                && (string) $source['is_active']
                    === '1',

            'is_verified' =>
                isset($source['is_verified'])
                && (string) $source['is_verified']
                    === '1',

            'sort_order' =>
                $sortOrder,
        ];
    }

    private function getRequiredBrand(
        int $brandId
    ): Brand {
        if ($brandId <= 0) {
            throw new RuntimeException(
                'El identificador de la marca no es válido.'
            );
        }

        $brand =
            $this->repository->findById(
                $brandId
            );

        if ($brand === null) {
            throw new RuntimeException(
                'No se encontró la marca.'
            );
        }

        return $brand;
    }

    private function getListUrl(): string
    {
        return add_query_arg(
            [
                'page' =>
                    self::PAGE_SLUG,
            ],
            admin_url('admin.php')
        );
    }

    /**
     * @param array<string, mixed> $additionalArguments
     */
    private function redirectWithNotice(
        string $notice,
        array $additionalArguments = []
    ): never {
        $arguments = array_merge(
            [
                'page' =>
                    self::PAGE_SLUG,

                'dsm_notice' =>
                    $notice,
            ],
            array_filter(
                $additionalArguments,
                static fn (mixed $value): bool =>
                    $value !== null
            )
        );

        wp_safe_redirect(
            add_query_arg(
                $arguments,
                admin_url('admin.php')
            )
        );

        exit;
    }

    /**
     * @param array<string, mixed> $additionalArguments
     */
    private function redirectWithError(
        string $message,
        array $additionalArguments = []
    ): never {
        $arguments = array_merge(
            [
                'page' =>
                    self::PAGE_SLUG,

                'dsm_error' =>
                    rawurlencode(
                        $message
                    ),
            ],
            array_filter(
                $additionalArguments,
                static fn (mixed $value): bool =>
                    $value !== null
            )
        );

        wp_safe_redirect(
            add_query_arg(
                $arguments,
                admin_url('admin.php')
            )
        );

        exit;
    }

    private function getNotice(): ?string
    {
        $notice = isset($_GET['dsm_notice'])
            ? sanitize_key(
                wp_unslash(
                    (string) $_GET['dsm_notice']
                )
            )
            : '';

        $messages = [
            'brand_created' =>
                __('Marca creada correctamente.', 'dsm-catalogo'),

            'brand_updated' =>
                __('Marca actualizada correctamente.', 'dsm-catalogo'),

            'brand_activated' =>
                __('Marca activada correctamente.', 'dsm-catalogo'),

            'brand_deactivated' =>
                __('Marca desactivada correctamente.', 'dsm-catalogo'),

            'brand_verified' =>
                __('Marca verificada correctamente.', 'dsm-catalogo'),

            'brand_unverified' =>
                __('Se retiró la verificación de la marca.', 'dsm-catalogo'),
        ];

        return $messages[$notice]
            ?? null;
    }

    private function getError(): ?string
    {
        if (!isset($_GET['dsm_error'])) {
            return null;
        }

        $error = rawurldecode(
            wp_unslash(
                (string) $_GET['dsm_error']
            )
        );

        $error = sanitize_text_field(
            $error
        );

        return $error !== ''
            ? $error
            : null;
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
                    'No tienes permisos para administrar las marcas.',
                    'dsm-catalogo'
                ),
                esc_html__(
                    'Acceso denegado',
                    'dsm-catalogo'
                ),
                [
                    'response' =>
                        403,
                ]
            );
        }
    }

    private function menuExists(
        string $menuSlug
    ): bool {
        global $menu;

        if (!is_array($menu)) {
            return false;
        }

        foreach ($menu as $item) {
            if (
                isset($item[2])
                && (string) $item[2]
                    === $menuSlug
            ) {
                return true;
            }
        }

        return false;
    }

    public static function getSaveAction(): string
    {
        return self::SAVE_ACTION;
    }

    public static function getSaveNonceAction(): string
    {
        return self::SAVE_NONCE_ACTION;
    }

    public static function getSaveNonceField(): string
    {
        return self::SAVE_NONCE_FIELD;
    }

    public static function getToggleActiveUrl(
        Brand $brand
    ): string {
        $url = add_query_arg(
            [
                'action' =>
                    self::TOGGLE_ACTIVE_ACTION,

                'brand_id' =>
                    $brand->getId(),
            ],
            admin_url('admin-post.php')
        );

        return wp_nonce_url(
            $url,
            self::TOGGLE_ACTIVE_NONCE_ACTION
                . '_'
                . $brand->getId()
        );
    }

    public static function getToggleVerifiedUrl(
        Brand $brand
    ): string {
        $url = add_query_arg(
            [
                'action' =>
                    self::TOGGLE_VERIFIED_ACTION,

                'brand_id' =>
                    $brand->getId(),
            ],
            admin_url('admin-post.php')
        );

        return wp_nonce_url(
            $url,
            self::TOGGLE_VERIFIED_NONCE_ACTION
                . '_'
                . $brand->getId()
        );
    }

    public static function getEditUrl(
        Brand $brand
    ): string {
        return add_query_arg(
            [
                'page' =>
                    self::PAGE_SLUG,

                'view' =>
                    'edit',

                'brand_id' =>
                    $brand->getId(),
            ],
            admin_url('admin.php')
        );
    }
}