<?php

declare(strict_types=1);

namespace DSM\Catalogo\Admin;

use DSM\Catalogo\Admin\Variant\VariantArchiveAction;
use DSM\Catalogo\Admin\Variant\VariantDefaultAction;
use DSM\Catalogo\Admin\Variant\VariantSaveAction;
use DSM\Catalogo\Admin\Variant\VariantStatusAction;
use DSM\Catalogo\Admin\Variant\VariantView;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Registra la administración de variantes.
 *
 * Centraliza:
 *
 * - submenú administrativo;
 * - vista de listado y formulario;
 * - acciones admin-post;
 * - URLs internas;
 * - nombres de acciones y nonces;
 * - assets de administración.
 */
final class VariantAdminController
{
    public const MENU_SLUG =
        'dsm-catalogo-variants';

    public const SAVE_ACTION =
        'dsm_catalogo_save_variant';

    public const STATUS_ACTION =
        'dsm_catalogo_toggle_variant';

    public const DEFAULT_ACTION =
        'dsm_catalogo_default_variant';

    public const ARCHIVE_ACTION =
        'dsm_catalogo_archive_variant';

    public const NONCE_ACTION =
        'dsm_catalogo_variant';

    public const NONCE_FIELD =
        '_dsm_catalogo_variant_nonce';

    private static ?VariantView $view =
        null;

    private static ?VariantSaveAction $save =
        null;

    private static ?VariantStatusAction $status =
        null;

    private static ?VariantDefaultAction $default =
        null;

    private static ?VariantArchiveAction $archive =
        null;

    /**
     * Registra todos los hooks del módulo.
     */
    public static function register(): void
    {
        add_action(
            'admin_menu',
            [
                self::class,
                'registerMenu',
            ]
        );

        add_action(
            'admin_enqueue_scripts',
            [
                self::class,
                'enqueueAssets',
            ]
        );

        add_action(
            'admin_post_'
            . self::SAVE_ACTION,
            [
                self::save(),
                'handle',
            ]
        );

        add_action(
            'admin_post_'
            . self::STATUS_ACTION,
            [
                self::status(),
                'handle',
            ]
        );

        add_action(
            'admin_post_'
            . self::DEFAULT_ACTION,
            [
                self::default(),
                'handle',
            ]
        );

        add_action(
            'admin_post_'
            . self::ARCHIVE_ACTION,
            [
                self::archive(),
                'handle',
            ]
        );
    }

    /**
     * Añade Variantes debajo del menú DSM Catálogo.
     */
    public static function registerMenu(): void
    {
        add_submenu_page(
            'dsm-catalogo',
            __(
                'Variantes',
                'dsm-catalogo'
            ),
            __(
                'Variantes',
                'dsm-catalogo'
            ),
            'manage_options',
            self::MENU_SLUG,
            [
                self::class,
                'render',
            ]
        );
    }

    /**
     * Renderiza la pantalla.
     */
    public static function render(): void
    {
        self::view()->render();
    }

    /**
     * Carga los estilos y scripts del catálogo únicamente
     * en la pantalla de variantes.
     */
    public static function enqueueAssets(
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

        if ($page !== self::MENU_SLUG) {
            return;
        }

        wp_enqueue_style(
            'dsm-catalogo-admin',
            DSM_CATALOGO_URL
            . 'assets/admin/css/catalog.css',
            [],
            DSM_CATALOGO_VERSION
        );

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

    /**
     * URL del listado.
     *
     * @param array<string, int|string> $arguments
     */
    public static function getListUrl(
        array $arguments = []
    ): string {
        return add_query_arg(
            array_merge(
                [
                    'page' =>
                        self::MENU_SLUG,
                ],
                $arguments
            ),
            admin_url('admin.php')
        );
    }

    /**
     * URL para crear una variante.
     */
    public static function getCreateUrl(
        int $productId = 0
    ): string {
        $arguments = [
            'action' =>
                'new',
        ];

        if ($productId > 0) {
            $arguments['product_id'] =
                $productId;
        }

        return self::getListUrl(
            $arguments
        );
    }

    /**
     * URL para editar una variante.
     */
    public static function getEditUrl(
        int $variantId
    ): string {
        return self::getListUrl([
            'action' =>
                'edit',

            'variant_id' =>
                $variantId,
        ]);
    }

    public static function getMenuSlug(): string
    {
        return self::MENU_SLUG;
    }

    public static function getSaveAction(): string
    {
        return self::SAVE_ACTION;
    }

    public static function getStatusAction(): string
    {
        return self::STATUS_ACTION;
    }

    public static function getDefaultAction(): string
    {
        return self::DEFAULT_ACTION;
    }

    public static function getArchiveAction(): string
    {
        return self::ARCHIVE_ACTION;
    }

    public static function getNonceAction(): string
    {
        return self::NONCE_ACTION;
    }

    public static function getNonceField(): string
    {
        return self::NONCE_FIELD;
    }

    /**
     * Instancia única de la vista durante la petición.
     */
    private static function view(): VariantView
    {
        if (self::$view === null) {
            self::$view =
                new VariantView();
        }

        return self::$view;
    }

    /**
     * Instancia única de guardado durante la petición.
     */
    private static function save(): VariantSaveAction
    {
        if (self::$save === null) {
            self::$save =
                new VariantSaveAction();
        }

        return self::$save;
    }

    /**
     * Instancia única de cambio de estado.
     */
    private static function status(): VariantStatusAction
    {
        if (self::$status === null) {
            self::$status =
                new VariantStatusAction();
        }

        return self::$status;
    }

    /**
     * Instancia única de variante predeterminada.
     */
    private static function default(): VariantDefaultAction
    {
        if (self::$default === null) {
            self::$default =
                new VariantDefaultAction();
        }

        return self::$default;
    }

    /**
     * Instancia única de archivado.
     */
    private static function archive(): VariantArchiveAction
    {
        if (self::$archive === null) {
            self::$archive =
                new VariantArchiveAction();
        }

        return self::$archive;
    }

    private function __construct()
    {
    }
}