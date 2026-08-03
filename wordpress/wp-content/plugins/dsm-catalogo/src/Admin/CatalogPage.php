<?php

declare(strict_types=1);

namespace DSM\Catalogo\Admin;

use wpdb;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Página principal de administración de DSM Catálogo.
 *
 * Registra el menú superior y muestra un dashboard informativo.
 * No realiza redirecciones, evitando modificar cabeceras después
 * de que WordPress haya comenzado a generar la respuesta.
 */
final class CatalogPage
{
    public const MENU_SLUG =
        'dsm-catalogo';

    private const CAPABILITY =
        'manage_options';

    private string $hookSuffix = '';

    private wpdb $database;

    private string $productsTable;

    private string $variantsTable;

    private string $brandsTable;

    public function __construct()
    {
        global $wpdb;

        $this->database = $wpdb;

        $this->productsTable =
            $wpdb->prefix
            . 'dsm_products';

        $this->variantsTable =
            $wpdb->prefix
            . 'dsm_product_variants';

        $this->brandsTable =
            $wpdb->prefix
            . 'dsm_brands';
    }

    public static function register(): void
    {
        $page = new self();

        add_action(
            'admin_menu',
            [$page, 'registerMenu'],
            5
        );

        add_action(
            'admin_enqueue_scripts',
            [$page, 'enqueueAssets']
        );
    }

    /**
     * Registra el menú principal y la entrada Inicio.
     */
    public function registerMenu(): void
    {
        $this->hookSuffix =
            (string) add_menu_page(
                page_title:
                    __(
                        'DSM Catálogo',
                        'dsm-catalogo'
                    ),

                menu_title:
                    __(
                        'DSM Catálogo',
                        'dsm-catalogo'
                    ),

                capability:
                    self::CAPABILITY,

                menu_slug:
                    self::MENU_SLUG,

                callback:
                    [$this, 'render'],

                icon_url:
                    'dashicons-products',

                position:
                    26
            );

        add_submenu_page(
            parent_slug:
                self::MENU_SLUG,

            page_title:
                __(
                    'Inicio',
                    'dsm-catalogo'
                ),

            menu_title:
                __(
                    'Inicio',
                    'dsm-catalogo'
                ),

            capability:
                self::CAPABILITY,

            menu_slug:
                self::MENU_SLUG,

            callback:
                [$this, 'render']
        );
    }

    /**
     * Carga los assets únicamente en el dashboard.
     */
    public function enqueueAssets(
        string $hookSuffix
    ): void {
        if (
            $hookSuffix
            !== $this->hookSuffix
        ) {
            return;
        }

        $relativePath =
            'assets/admin/css/catalog.css';

        $filePath =
            DSM_CATALOGO_PATH
            . $relativePath;

        $version =
            is_file($filePath)
                ? (string) filemtime(
                    $filePath
                )
                : DSM_CATALOGO_VERSION;

        wp_enqueue_style(
            'dsm-catalogo-admin',
            DSM_CATALOGO_URL
            . $relativePath,
            [],
            $version
        );
    }

    /**
     * Renderiza el dashboard.
     */
    public function render(): void
    {
        $this->assertPermission();

        $summary =
            $this->getSummary();

        ?>
        <div class="wrap dsm-catalogo-admin">
            <div class="dsm-admin-header">
                <div>
                    <h1>
                        <?php
                        esc_html_e(
                            'DSM Catálogo',
                            'dsm-catalogo'
                        );
                        ?>
                    </h1>

                    <p class="description">
                        <?php
                        esc_html_e(
                            'Gestión central de productos, variantes, marcas y datos comerciales de DeSegundaMuda.',
                            'dsm-catalogo'
                        );
                        ?>
                    </p>
                </div>
            </div>

            <hr class="wp-header-end">

            <div class="dsm-summary-grid">
                <?php
                $this->renderSummaryCard(
                    __(
                        'Productos',
                        'dsm-catalogo'
                    ),
                    $summary['products'],
                    'dashicons-products'
                );

                $this->renderSummaryCard(
                    __(
                        'Variantes',
                        'dsm-catalogo'
                    ),
                    $summary['variants'],
                    'dashicons-screenoptions'
                );

                $this->renderSummaryCard(
                    __(
                        'Marcas',
                        'dsm-catalogo'
                    ),
                    $summary['brands'],
                    'dashicons-tag'
                );

                $this->renderSummaryCard(
                    __(
                        'Archivados',
                        'dsm-catalogo'
                    ),
                    $summary['archived'],
                    'dashicons-archive'
                );
                ?>
            </div>

            <div class="dsm-form-layout">
                <main class="dsm-form-main">
                    <section class="dsm-panel">
                        <div class="dsm-panel-header">
                            <h2>
                                <?php
                                esc_html_e(
                                    'Gestión del catálogo',
                                    'dsm-catalogo'
                                );
                                ?>
                            </h2>
                        </div>

                        <div class="dsm-panel-body">
                            <p>
                                <?php
                                esc_html_e(
                                    'Utiliza estas secciones para mantener la información comercial reutilizada por los demás módulos de la plataforma.',
                                    'dsm-catalogo'
                                );
                                ?>
                            </p>

                            <div class="dsm-admin-header-actions">
                                <a
                                    class="button button-primary"
                                    href="<?php echo esc_url(
                                        add_query_arg(
                                            [
                                                'page' =>
                                                    ProductAdminController::
                                                        PAGE_SLUG,
                                            ],
                                            admin_url(
                                                'admin.php'
                                            )
                                        )
                                    ); ?>"
                                >
                                    <?php
                                    esc_html_e(
                                        'Gestionar productos',
                                        'dsm-catalogo'
                                    );
                                    ?>
                                </a>

                                <a
                                    class="button"
                                    href="<?php echo esc_url(
                                        VariantAdminController::
                                            getListUrl()
                                    ); ?>"
                                >
                                    <?php
                                    esc_html_e(
                                        'Gestionar variantes',
                                        'dsm-catalogo'
                                    );
                                    ?>
                                </a>

                                <a
                                    class="button"
                                    href="<?php echo esc_url(
                                        add_query_arg(
                                            [
                                                'page' =>
                                                    BrandAdminController::
                                                        PAGE_SLUG,
                                            ],
                                            admin_url(
                                                'admin.php'
                                            )
                                        )
                                    ); ?>"
                                >
                                    <?php
                                    esc_html_e(
                                        'Gestionar marcas',
                                        'dsm-catalogo'
                                    );
                                    ?>
                                </a>
                            </div>
                        </div>
                    </section>

                    <section class="dsm-panel">
                        <div class="dsm-panel-header">
                            <h2>
                                <?php
                                esc_html_e(
                                    'Responsabilidad del módulo',
                                    'dsm-catalogo'
                                );
                                ?>
                            </h2>
                        </div>

                        <div class="dsm-panel-body">
                            <p>
                                <?php
                                esc_html_e(
                                    'DSM Catálogo mantiene la definición comercial de productos y variantes.',
                                    'dsm-catalogo'
                                );
                                ?>
                            </p>

                            <ul>
                                <li>
                                    <?php
                                    esc_html_e(
                                        'Productos base y referencias.',
                                        'dsm-catalogo'
                                    );
                                    ?>
                                </li>

                                <li>
                                    <?php
                                    esc_html_e(
                                        'Tallas, colores y condiciones.',
                                        'dsm-catalogo'
                                    );
                                    ?>
                                </li>

                                <li>
                                    <?php
                                    esc_html_e(
                                        'Precios y códigos comerciales.',
                                        'dsm-catalogo'
                                    );
                                    ?>
                                </li>

                                <li>
                                    <?php
                                    esc_html_e(
                                        'Marcas y organización del catálogo.',
                                        'dsm-catalogo'
                                    );
                                    ?>
                                </li>
                            </ul>
                        </div>
                    </section>
                </main>

                <aside class="dsm-form-sidebar">
                    <section class="dsm-panel">
                        <div class="dsm-panel-header">
                            <h2>
                                <?php
                                esc_html_e(
                                    'DSM Multitienda',
                                    'dsm-catalogo'
                                );
                                ?>
                            </h2>
                        </div>

                        <div class="dsm-panel-body">
                            <p>
                                <?php
                                esc_html_e(
                                    'El inventario, el stock, los movimientos y las reservas se gestionarán desde DSM Multitienda.',
                                    'dsm-catalogo'
                                );
                                ?>
                            </p>
                        </div>
                    </section>

                    <section class="dsm-panel">
                        <div class="dsm-panel-header">
                            <h2>
                                <?php
                                esc_html_e(
                                    'DSM Anuncios',
                                    'dsm-catalogo'
                                );
                                ?>
                            </h2>
                        </div>

                        <div class="dsm-panel-body">
                            <p>
                                <?php
                                esc_html_e(
                                    'Los anuncios particulares representan prendas únicas y no utilizan control de stock.',
                                    'dsm-catalogo'
                                );
                                ?>
                            </p>
                        </div>
                    </section>
                </aside>
            </div>
        </div>
        <?php
    }

    /**
     * @return array{
     *     products:int,
     *     variants:int,
     *     brands:int,
     *     archived:int
     * }
     */
    private function getSummary(): array
    {
        return [
            'products' =>
                $this->countRows(
                    $this->productsTable,
                    'archived_at IS NULL'
                ),

            'variants' =>
                $this->countRows(
                    $this->variantsTable,
                    'archived_at IS NULL'
                ),

            'brands' =>
                $this->countRows(
                    $this->brandsTable,
                    'is_active = 1'
                ),

            'archived' =>
                $this->countRows(
                    $this->productsTable,
                    'archived_at IS NOT NULL'
                )
                +
                $this->countRows(
                    $this->variantsTable,
                    'archived_at IS NOT NULL'
                ),
        ];
    }

    private function countRows(
        string $table,
        string $where
    ): int {
        /*
         * Los nombres de tabla proceden únicamente de propiedades
         * internas construidas con $wpdb->prefix.
         *
         * La condición también está definida internamente.
         */
        $result =
            $this->database->get_var(
                "
                SELECT COUNT(*)
                FROM {$table}
                WHERE {$where}
                "
            );

        return max(
            0,
            (int) $result
        );
    }

    private function renderSummaryCard(
        string $label,
        int $value,
        string $icon
    ): void {
        ?>
        <article class="dsm-summary-card">
            <span
                class="dashicons <?php echo esc_attr(
                    $icon
                ); ?>"
                aria-hidden="true"
            ></span>

            <span class="dsm-summary-label">
                <?php echo esc_html($label); ?>
            </span>

            <strong class="dsm-summary-value">
                <?php echo esc_html(
                    number_format_i18n(
                        $value
                    )
                ); ?>
            </strong>
        </article>
        <?php
    }

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
                'No tienes permisos para acceder al catálogo.',
                'dsm-catalogo'
            ),
            esc_html__(
                'Acceso denegado',
                'dsm-catalogo'
            ),
            [
                'response' => 403,
            ]
        );
    }
}