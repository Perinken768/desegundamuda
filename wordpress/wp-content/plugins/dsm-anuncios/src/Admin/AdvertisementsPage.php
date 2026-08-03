<?php

declare(strict_types=1);

namespace DSM\Anuncios\Admin;

use DSM\Anuncios\Advertisement\AdvertisementStatus;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Página administrativa de anuncios.
 *
 * Responsabilidades:
 *
 * - Mostrar el listado de anuncios.
 * - Aplicar búsqueda, filtros y paginación.
 * - Mostrar contadores por estado.
 * - Mostrar el detalle completo de un anuncio.
 * - Preparar las acciones administrativas.
 *
 * Las modificaciones se delegan en AdvertisementAdminController.
 */
final class AdvertisementsPage
{
    public const MENU_SLUG =
        'dsm-anuncios';

    private const CAPABILITY =
        'manage_options';

    private const DEFAULT_PER_PAGE =
        20;

    private string $hookSuffix = '';

    public function __construct(
        private readonly AdvertisementAdminRepository $repository
    ) {
    }

    /**
     * Registra la página y sus recursos.
     */
    public function register(): void
    {
        add_action(
            'admin_menu',
            [$this, 'registerMenu'],
            5
        );

        add_action(
            'admin_enqueue_scripts',
            [$this, 'enqueueAssets']
        );
    }

    /**
     * Registra el menú principal de DSM Anuncios.
     */
    public function registerMenu(): void
    {
        $this->hookSuffix =
            (string) add_menu_page(
                page_title:
                    __(
                        'DSM Anuncios',
                        'dsm-anuncios'
                    ),

                menu_title:
                    __(
                        'DSM Anuncios',
                        'dsm-anuncios'
                    ),

                capability:
                    self::CAPABILITY,

                menu_slug:
                    self::MENU_SLUG,

                callback:
                    [$this, 'render'],

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
                    'Anuncios',
                    'dsm-anuncios'
                ),

            menu_title:
                __(
                    'Anuncios',
                    'dsm-anuncios'
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
     * Carga CSS y JavaScript administrativos.
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
            && $page !== self::MENU_SLUG
        ) {
            return;
        }

        $cssRelativePath =
            'assets/admin/css/advertisements.css';

        $cssFilePath =
            DSM_ANUNCIOS_PATH
            . $cssRelativePath;

        $cssVersion =
            is_file($cssFilePath)
                ? (string) filemtime(
                    $cssFilePath
                )
                : DSM_ANUNCIOS_VERSION;

        wp_enqueue_style(
            'dsm-anuncios-admin',
            DSM_ANUNCIOS_URL
            . $cssRelativePath,
            [],
            $cssVersion
        );

        $jsRelativePath =
            'assets/admin/js/advertisements.js';

        $jsFilePath =
            DSM_ANUNCIOS_PATH
            . $jsRelativePath;

        $jsVersion =
            is_file($jsFilePath)
                ? (string) filemtime(
                    $jsFilePath
                )
                : DSM_ANUNCIOS_VERSION;

        wp_enqueue_script(
            'dsm-anuncios-admin',
            DSM_ANUNCIOS_URL
            . $jsRelativePath,
            [],
            $jsVersion,
            true
        );
    }

    /**
     * Renderiza listado o detalle.
     */
    public function render(): void
    {
        $this->assertPermission();

        $view =
            isset($_GET['view'])
                ? sanitize_key(
                    wp_unslash(
                        (string) $_GET['view']
                    )
                )
                : '';

        if ($view === 'detail') {
            $this->renderDetail();

            return;
        }

        $this->renderList();
    }

    /**
     * Renderiza el listado principal.
     */
    private function renderList(): void
    {
        $filters =
            $this->getFilters();

        $page =
            isset($_GET['paged'])
                ? max(
                    1,
                    absint(
                        wp_unslash(
                            (string) $_GET['paged']
                        )
                    )
                )
                : 1;

        $perPage =
            self::DEFAULT_PER_PAGE;

        $result =
            $this->repository->paginate(
                $filters,
                $page,
                $perPage
            );

        $counts =
            $this->repository->countByStatus();

        $categories =
            $this->repository->findCategories();

        ?>
        <div class="wrap dsm-anuncios-admin">
            <div class="dsm-admin-header">
                <div>
                    <h1>
                        <?php
                        esc_html_e(
                            'Anuncios',
                            'dsm-anuncios'
                        );
                        ?>
                    </h1>

                    <p class="description">
                        <?php
                        esc_html_e(
                            'Revisa, modera y gestiona los anuncios publicados por los clientes de DeSegundaMuda.',
                            'dsm-anuncios'
                        );
                        ?>
                    </p>
                </div>
            </div>

            <hr class="wp-header-end">

            <?php
            $this->renderNotice();

            $this->renderCounters(
                $counts
            );

            $this->renderFilters(
                $filters,
                $categories
            );

            $this->renderAdvertisementsTable(
                $result['items']
            );

            $this->renderPagination(
                $result['page'],
                $result['total_pages'],
                $result['total'],
                $filters
            );
            ?>
        </div>
        <?php
    }

    /**
     * Renderiza el detalle.
     */
    private function renderDetail(): void
    {
        $advertisementId =
            isset($_GET['advertisement_id'])
                ? absint(
                    wp_unslash(
                        (string) $_GET[
                            'advertisement_id'
                        ]
                    )
                )
                : 0;

        if ($advertisementId <= 0) {
            $this->renderErrorPage(
                __(
                    'No se ha indicado un anuncio válido.',
                    'dsm-anuncios'
                )
            );

            return;
        }

        $advertisement =
            $this->repository->findById(
                $advertisementId
            );

        if ($advertisement === null) {
            $this->renderErrorPage(
                __(
                    'El anuncio solicitado no existe.',
                    'dsm-anuncios'
                )
            );

            return;
        }

        ?>
        <div class="wrap dsm-anuncios-admin">
            <div class="dsm-admin-header">
                <div>
                    <h1>
                        <?php
                        echo esc_html(
                            (string) $advertisement[
                                'title'
                            ]
                        );
                        ?>
                    </h1>

                    <p class="description">
                        <?php
                        printf(
                            esc_html__(
                                'Anuncio #%d',
                                'dsm-anuncios'
                            ),
                            (int) $advertisement['id']
                        );
                        ?>
                    </p>
                </div>

                <div class="dsm-admin-header-actions">
                    <a
                        class="button button-secondary"
                        href="<?php echo esc_url(
                            $this->getListUrl()
                        ); ?>"
                    >
                        <?php
                        esc_html_e(
                            'Volver al listado',
                            'dsm-anuncios'
                        );
                        ?>
                    </a>
                </div>
            </div>

            <hr class="wp-header-end">

            <?php
            $this->renderNotice();
            ?>

            <div class="dsm-admin-detail-grid">
                <main class="dsm-admin-detail-main">
                    <?php
                    $this->renderImageGallery(
                        $advertisement['images']
                            ?? []
                    );

                    $this->renderAdvertisementInformation(
                        $advertisement
                    );

                    $this->renderDescription(
                        (string) (
                            $advertisement[
                                'description'
                            ]
                            ?? ''
                        )
                    );

                    $this->renderHistory(
                        $advertisement['history']
                            ?? []
                    );
                    ?>
                </main>

                <aside class="dsm-admin-detail-sidebar">
                    <?php
                    $this->renderStatusCard(
                        $advertisement
                    );

                    $this->renderCustomerCard(
                        $advertisement
                    );

                    $this->renderMetadataCard(
                        $advertisement
                    );
                    ?>
                </aside>
            </div>
        </div>
        <?php
    }

    /**
     * Contadores superiores.
     *
     * @param array<string, int> $counts
     */
    private function renderCounters(
        array $counts
    ): void {
        $cards = [
            AdvertisementStatus::DRAFT => [
                __('Borradores', 'dsm-anuncios'),
                'muted',
            ],

            AdvertisementStatus::PENDING => [
                __('Pendientes', 'dsm-anuncios'),
                'warning',
            ],

            AdvertisementStatus::ACTIVE => [
                __('Activos', 'dsm-anuncios'),
                'success',
            ],

            AdvertisementStatus::RESERVED => [
                __('Reservados', 'dsm-anuncios'),
                'warning',
            ],

            AdvertisementStatus::CLOSED => [
                __('Cerrados', 'dsm-anuncios'),
                'muted',
            ],

            AdvertisementStatus::REJECTED => [
                __('Rechazados', 'dsm-anuncios'),
                'danger',
            ],
        ];

        ?>
        <div class="dsm-admin-counter-grid">
            <?php foreach (
                $cards as $status => [$label, $style]
            ) : ?>
                <a
                    class="<?php echo esc_attr(
                        'dsm-admin-counter-card '
                        . 'dsm-admin-counter-card--'
                        . $style
                    ); ?>"
                    href="<?php echo esc_url(
                        $this->getListUrl([
                            'status' => $status,
                        ])
                    ); ?>"
                >
                    <strong>
                        <?php
                        echo esc_html(
                            number_format_i18n(
                                (int) (
                                    $counts[$status]
                                    ?? 0
                                )
                            )
                        );
                        ?>
                    </strong>

                    <span>
                        <?php echo esc_html($label); ?>
                    </span>
                </a>
            <?php endforeach; ?>
        </div>
        <?php
    }

    /**
     * Formulario de filtros.
     *
     * @param array<string, mixed> $filters
     * @param array<int, array{id:int,name:string}> $categories
     */
    private function renderFilters(
        array $filters,
        array $categories
    ): void {
        ?>
        <section class="dsm-admin-card">
            <form
                method="get"
                class="dsm-filter-form"
            >
                <input
                    type="hidden"
                    name="page"
                    value="<?php echo esc_attr(
                        self::MENU_SLUG
                    ); ?>"
                >

                <div class="dsm-filter-grid">
                    <div class="dsm-filter-field">
                        <label for="dsm-ad-search">
                            <?php
                            esc_html_e(
                                'Buscar',
                                'dsm-anuncios'
                            );
                            ?>
                        </label>

                        <input
                            id="dsm-ad-search"
                            type="search"
                            name="search"
                            value="<?php echo esc_attr(
                                (string) $filters[
                                    'search'
                                ]
                            ); ?>"
                            placeholder="<?php echo esc_attr__(
                                'Título, marca, cliente…',
                                'dsm-anuncios'
                            ); ?>"
                        >
                    </div>

                    <div class="dsm-filter-field">
                        <label for="dsm-ad-status">
                            <?php
                            esc_html_e(
                                'Estado',
                                'dsm-anuncios'
                            );
                            ?>
                        </label>

                        <select
                            id="dsm-ad-status"
                            name="status"
                        >
                            <option value="">
                                <?php
                                esc_html_e(
                                    'Todos los estados',
                                    'dsm-anuncios'
                                );
                                ?>
                            </option>

                            <?php foreach (
                                AdvertisementStatus::all()
                                as $status
                            ) : ?>
                                <option
                                    value="<?php echo esc_attr(
                                        $status
                                    ); ?>"
                                    <?php selected(
                                        $filters['status'],
                                        $status
                                    ); ?>
                                >
                                    <?php
                                    echo esc_html(
                                        $this->getStatusLabel(
                                            $status
                                        )
                                    );
                                    ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="dsm-filter-field">
                        <label for="dsm-ad-category">
                            <?php
                            esc_html_e(
                                'Categoría',
                                'dsm-anuncios'
                            );
                            ?>
                        </label>

                        <select
                            id="dsm-ad-category"
                            name="category_id"
                        >
                            <option value="">
                                <?php
                                esc_html_e(
                                    'Todas las categorías',
                                    'dsm-anuncios'
                                );
                                ?>
                            </option>

                            <?php foreach (
                                $categories as $category
                            ) : ?>
                                <option
                                    value="<?php echo esc_attr(
                                        (string) $category['id']
                                    ); ?>"
                                    <?php selected(
                                        (int) $filters[
                                            'category_id'
                                        ],
                                        (int) $category['id']
                                    ); ?>
                                >
                                    <?php echo esc_html(
                                        $category['name']
                                    ); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="dsm-filter-actions">
                        <button
                            type="submit"
                            class="button button-primary"
                        >
                            <?php
                            esc_html_e(
                                'Aplicar filtros',
                                'dsm-anuncios'
                            );
                            ?>
                        </button>

                        <a
                            class="button"
                            href="<?php echo esc_url(
                                $this->getListUrl()
                            ); ?>"
                        >
                            <?php
                            esc_html_e(
                                'Limpiar',
                                'dsm-anuncios'
                            );
                            ?>
                        </a>
                    </div>
                </div>
            </form>
        </section>
        <?php
    }

    /**
     * Tabla de anuncios.
     *
     * @param array<int, array<string, mixed>> $advertisements
     */
    private function renderAdvertisementsTable(
        array $advertisements
    ): void {
        ?>
        <div class="dsm-admin-table-scroll">
            <table class="widefat fixed striped">
                <thead>
                    <tr>
                        <th class="column-id">
                            <?php
                            esc_html_e(
                                'ID',
                                'dsm-anuncios'
                            );
                            ?>
                        </th>

                        <th>
                            <?php
                            esc_html_e(
                                'Anuncio',
                                'dsm-anuncios'
                            );
                            ?>
                        </th>

                        <th>
                            <?php
                            esc_html_e(
                                'Cliente',
                                'dsm-anuncios'
                            );
                            ?>
                        </th>

                        <th>
                            <?php
                            esc_html_e(
                                'Categoría',
                                'dsm-anuncios'
                            );
                            ?>
                        </th>

                        <th>
                            <?php
                            esc_html_e(
                                'Precio',
                                'dsm-anuncios'
                            );
                            ?>
                        </th>

                        <th>
                            <?php
                            esc_html_e(
                                'Estado',
                                'dsm-anuncios'
                            );
                            ?>
                        </th>

                        <th>
                            <?php
                            esc_html_e(
                                'Creado',
                                'dsm-anuncios'
                            );
                            ?>
                        </th>

                        <th>
                            <?php
                            esc_html_e(
                                'Acciones',
                                'dsm-anuncios'
                            );
                            ?>
                        </th>
                    </tr>
                </thead>

                <tbody>
                    <?php if (
                        $advertisements === []
                    ) : ?>
                        <tr>
                            <td colspan="8">
                                <?php
                                esc_html_e(
                                    'No se encontraron anuncios.',
                                    'dsm-anuncios'
                                );
                                ?>
                            </td>
                        </tr>

                    <?php else : ?>
                        <?php foreach (
                            $advertisements as $advertisement
                        ) : ?>
                            <?php
                            $advertisementId =
                                (int) $advertisement[
                                    'id'
                                ];
                            ?>

                            <tr>
                                <td>
                                    <?php echo esc_html(
                                        (string) $advertisementId
                                    ); ?>
                                </td>

                                <td>
                                    <div class="dsm-advertisement-cell">
                                        <?php
                                        $coverUrl =
                                            (string) (
                                                $advertisement[
                                                    'cover_thumbnail_url'
                                                ]
                                                ?? ''
                                            );

                                        if ($coverUrl !== '') :
                                            ?>
                                            <img
                                                class="dsm-advertisement-thumbnail"
                                                src="<?php echo esc_url(
                                                    $coverUrl
                                                ); ?>"
                                                alt=""
                                            >
                                        <?php else : ?>
                                            <span
                                                class="dsm-advertisement-thumbnail dsm-advertisement-thumbnail--empty"
                                            >
                                                <span
                                                    class="dashicons dashicons-format-image"
                                                    aria-hidden="true"
                                                ></span>
                                            </span>
                                        <?php endif; ?>

                                        <div>
                                            <strong>
                                                <a
                                                    href="<?php echo esc_url(
                                                        $this->getDetailUrl(
                                                            $advertisementId
                                                        )
                                                    ); ?>"
                                                >
                                                    <?php
                                                    echo esc_html(
                                                        (string) $advertisement[
                                                            'title'
                                                        ]
                                                    );
                                                    ?>
                                                </a>
                                            </strong>

                                            <?php if (
                                                !empty(
                                                    $advertisement[
                                                        'brand'
                                                    ]
                                                )
                                            ) : ?>
                                                <br>

                                                <small>
                                                    <?php
                                                    echo esc_html(
                                                        (string) $advertisement[
                                                            'brand'
                                                        ]
                                                    );
                                                    ?>
                                                </small>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </td>

                                <td>
                                    <?php
                                    echo esc_html(
                                        $this->getCustomerLabel(
                                            $advertisement
                                        )
                                    );
                                    ?>

                                    <br>

                                    <small>
                                        <?php echo esc_html(
                                            (string) (
                                                $advertisement[
                                                    'customer_email'
                                                ]
                                                ?? ''
                                            )
                                        ); ?>
                                    </small>
                                </td>

                                <td>
                                    <?php
                                    echo esc_html(
                                        (string) (
                                            $advertisement[
                                                'category_name'
                                            ]
                                            ?? '—'
                                        )
                                    );
                                    ?>
                                </td>

                                <td>
                                    <strong>
                                        <?php
                                        echo esc_html(
                                            number_format_i18n(
                                                (float) $advertisement[
                                                    'price'
                                                ],
                                                2
                                            )
                                            . ' €'
                                        );
                                        ?>
                                    </strong>
                                </td>

                                <td>
                                    <?php
                                    echo $this->renderStatusBadge(
                                        (string) $advertisement[
                                            'status'
                                        ]
                                    );
                                    ?>
                                </td>

                                <td>
                                    <?php
                                    echo esc_html(
                                        $this->formatDate(
                                            $advertisement[
                                                'created_at'
                                            ]
                                            ?? null
                                        )
                                    );
                                    ?>
                                </td>

                                <td>
                                    <a
                                        class="button button-small"
                                        href="<?php echo esc_url(
                                            $this->getDetailUrl(
                                                $advertisementId
                                            )
                                        ); ?>"
                                    >
                                        <?php
                                        esc_html_e(
                                            'Revisar',
                                            'dsm-anuncios'
                                        );
                                        ?>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <?php
    }

    /**
     * Paginación.
     *
     * @param array<string, mixed> $filters
     */
    private function renderPagination(
        int $currentPage,
        int $totalPages,
        int $totalItems,
        array $filters
    ): void {
        if ($totalPages <= 1) {
            return;
        }

        $baseArguments = [
            'page' =>
                self::MENU_SLUG,

            'search' =>
                $filters['search'],

            'status' =>
                $filters['status'],

            'category_id' =>
                $filters['category_id'],

            'paged' =>
                '%#%',
        ];

        $baseUrl =
            add_query_arg(
                $baseArguments,
                admin_url('admin.php')
            );

        ?>
        <div class="tablenav bottom">
            <div class="tablenav-pages">
                <span class="displaying-num">
                    <?php
                    printf(
                        esc_html__(
                            '%s elementos',
                            'dsm-anuncios'
                        ),
                        esc_html(
                            number_format_i18n(
                                $totalItems
                            )
                        )
                    );
                    ?>
                </span>

                <?php
                echo wp_kses_post(
                    paginate_links([
                        'base' =>
                            $baseUrl,

                        'format' =>
                            '',

                        'current' =>
                            $currentPage,

                        'total' =>
                            $totalPages,

                        'prev_text' =>
                            '‹',

                        'next_text' =>
                            '›',
                    ])
                );
                ?>
            </div>
        </div>
        <?php
    }

    /**
     * Galería administrativa.
     *
     * @param array<int, array<string, mixed>> $images
     */
    private function renderImageGallery(
        array $images
    ): void {
        ?>
        <section class="dsm-admin-card">
            <h2>
                <?php
                esc_html_e(
                    'Imágenes',
                    'dsm-anuncios'
                );
                ?>
            </h2>

            <?php if ($images === []) : ?>
                <p class="description">
                    <?php
                    esc_html_e(
                        'El anuncio no tiene imágenes.',
                        'dsm-anuncios'
                    );
                    ?>
                </p>

            <?php else : ?>
                <div class="dsm-advertisement-gallery">
                    <?php foreach (
                        $images as $image
                    ) : ?>
                        <?php
                        $imageUrl =
                            (string) (
                                $image['medium_url']
                                ?? ''
                            );

                        if ($imageUrl === '') {
                            continue;
                        }
                        ?>

                        <a
                            href="<?php echo esc_url(
                                (string) (
                                    $image['full_url']
                                    ?? $imageUrl
                                )
                            ); ?>"
                            target="_blank"
                            rel="noopener noreferrer"
                        >
                            <img
                                src="<?php echo esc_url(
                                    $imageUrl
                                ); ?>"
                                alt=""
                            >

                            <?php if (
                                !empty(
                                    $image['is_cover']
                                )
                            ) : ?>
                                <span class="dsm-gallery-cover-label">
                                    <?php
                                    esc_html_e(
                                        'Portada',
                                        'dsm-anuncios'
                                    );
                                    ?>
                                </span>
                            <?php endif; ?>
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>
        <?php
    }

    /**
     * Datos principales.
     *
     * @param array<string, mixed> $advertisement
     */
    private function renderAdvertisementInformation(
        array $advertisement
    ): void {
        ?>
        <section class="dsm-admin-card">
            <h2>
                <?php
                esc_html_e(
                    'Información del anuncio',
                    'dsm-anuncios'
                );
                ?>
            </h2>

            <table class="form-table" role="presentation">
                <tbody>
                    <?php
                    $this->renderInformationRow(
                        __('Categoría', 'dsm-anuncios'),
                        (string) (
                            $advertisement[
                                'category_name'
                            ]
                            ?? '—'
                        )
                    );

                    $this->renderInformationRow(
                        __('Marca', 'dsm-anuncios'),
                        (string) (
                            $advertisement['brand']
                            ?? '—'
                        )
                    );

                    $this->renderInformationRow(
                        __('Condición', 'dsm-anuncios'),
                        $this->getConditionLabel(
                            (string) (
                                $advertisement[
                                    'condition_code'
                                ]
                                ?? ''
                            )
                        )
                    );

                    $this->renderInformationRow(
                        __('Precio', 'dsm-anuncios'),
                        number_format_i18n(
                            (float) $advertisement[
                                'price'
                            ],
                            2
                        )
                        . ' €'
                    );

                    if (
                        $advertisement[
                            'original_price'
                        ] !== null
                    ) {
                        $this->renderInformationRow(
                            __(
                                'Precio original',
                                'dsm-anuncios'
                            ),
                            number_format_i18n(
                                (float) $advertisement[
                                    'original_price'
                                ],
                                2
                            )
                            . ' €'
                        );
                    }

                    if (
                        !empty(
                            $advertisement[
                                'purchase_date'
                            ]
                        )
                    ) {
                        $this->renderInformationRow(
                            __(
                                'Fecha de compra',
                                'dsm-anuncios'
                            ),
                            (string) $advertisement[
                                'purchase_date'
                            ]
                        );
                    }
                    ?>
                </tbody>
            </table>
        </section>
        <?php
    }

    private function renderDescription(
        string $description
    ): void {
        ?>
        <section class="dsm-admin-card">
            <h2>
                <?php
                esc_html_e(
                    'Descripción',
                    'dsm-anuncios'
                );
                ?>
            </h2>

            <div class="dsm-advertisement-description">
                <?php
                echo wp_kses_post(
                    wpautop(
                        esc_html($description)
                    )
                );
                ?>
            </div>
        </section>
        <?php
    }

    /**
     * Historial administrativo.
     *
     * @param array<int, array<string, mixed>> $history
     */
    private function renderHistory(
        array $history
    ): void {
        ?>
        <section class="dsm-admin-card">
            <h2>
                <?php
                esc_html_e(
                    'Historial de estados',
                    'dsm-anuncios'
                );
                ?>
            </h2>

            <?php if ($history === []) : ?>
                <p class="description">
                    <?php
                    esc_html_e(
                        'Todavía no existe historial.',
                        'dsm-anuncios'
                    );
                    ?>
                </p>

            <?php else : ?>
                <div class="dsm-admin-table-scroll">
                    <table class="widefat striped">
                        <thead>
                            <tr>
                                <th>
                                    <?php
                                    esc_html_e(
                                        'Fecha',
                                        'dsm-anuncios'
                                    );
                                    ?>
                                </th>

                                <th>
                                    <?php
                                    esc_html_e(
                                        'Estado anterior',
                                        'dsm-anuncios'
                                    );
                                    ?>
                                </th>

                                <th>
                                    <?php
                                    esc_html_e(
                                        'Nuevo estado',
                                        'dsm-anuncios'
                                    );
                                    ?>
                                </th>

                                <th>
                                    <?php
                                    esc_html_e(
                                        'Responsable',
                                        'dsm-anuncios'
                                    );
                                    ?>
                                </th>

                                <th>
                                    <?php
                                    esc_html_e(
                                        'Notas',
                                        'dsm-anuncios'
                                    );
                                    ?>
                                </th>
                            </tr>
                        </thead>

                        <tbody>
                            <?php foreach (
                                $history as $entry
                            ) : ?>
                                <tr>
                                    <td>
                                        <?php
                                        echo esc_html(
                                            $this->formatDate(
                                                $entry[
                                                    'created_at'
                                                ]
                                                ?? null
                                            )
                                        );
                                        ?>
                                    </td>

                                    <td>
                                        <?php
                                        echo esc_html(
                                            $this->getStatusLabel(
                                                (string) (
                                                    $entry[
                                                        'previous_status'
                                                    ]
                                                    ?? ''
                                                )
                                            )
                                        );
                                        ?>
                                    </td>

                                    <td>
                                        <?php
                                        echo $this->renderStatusBadge(
                                            (string) (
                                                $entry[
                                                    'new_status'
                                                ]
                                                ?? ''
                                            )
                                        );
                                        ?>
                                    </td>

                                    <td>
                                        <?php
                                        echo esc_html(
                                            $this->getHistoryActorLabel(
                                                $entry
                                            )
                                        );
                                        ?>
                                    </td>

                                    <td>
                                        <?php
                                        echo esc_html(
                                            (string) (
                                                $entry['notes']
                                                ?? '—'
                                            )
                                        );
                                        ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </section>
        <?php
    }

    /**
     * Tarjeta de estado y acciones.
     *
     * @param array<string, mixed> $advertisement
     */
    private function renderStatusCard(
        array $advertisement
    ): void {
        $status =
            (string) $advertisement[
                'status'
            ];

        $advertisementId =
            (int) $advertisement['id'];

        ?>
        <section class="dsm-admin-card">
            <h2>
                <?php
                esc_html_e(
                    'Moderación',
                    'dsm-anuncios'
                );
                ?>
            </h2>

            <p>
                <?php
                echo $this->renderStatusBadge(
                    $status
                );
                ?>
            </p>

            <?php if (
                $status
                === AdvertisementStatus::REJECTED
                && !empty(
                    $advertisement[
                        'rejection_reason'
                    ]
                )
            ) : ?>
                <div class="notice notice-error inline">
                    <p>
                        <strong>
                            <?php
                            esc_html_e(
                                'Motivo del rechazo:',
                                'dsm-anuncios'
                            );
                            ?>
                        </strong>
                    </p>

                    <p>
                        <?php
                        echo esc_html(
                            (string) $advertisement[
                                'rejection_reason'
                            ]
                        );
                        ?>
                    </p>
                </div>
            <?php endif; ?>

            <div class="dsm-admin-actions">
                <?php
                if (
                    AdvertisementStatus::canBePublished(
                        $status
                    )
                ) {
                    $this->renderSimpleActionForm(
                        $advertisementId,
                        AdvertisementAdminController::
                            ACTION_PUBLISH,
                        __(
                            'Publicar',
                            'dsm-anuncios'
                        ),
                        'button-primary'
                    );

                    $this->renderRejectForm(
                        $advertisementId
                    );
                }

                if (
                    AdvertisementStatus::canBeReserved(
                        $status
                    )
                ) {
                    $this->renderSimpleActionForm(
                        $advertisementId,
                        AdvertisementAdminController::
                            ACTION_RESERVE,
                        __(
                            'Marcar como reservado',
                            'dsm-anuncios'
                        )
                    );
                }

                if (
                    AdvertisementStatus::canBeReleased(
                        $status
                    )
                ) {
                    $this->renderSimpleActionForm(
                        $advertisementId,
                        AdvertisementAdminController::
                            ACTION_RELEASE,
                        __(
                            'Liberar reserva',
                            'dsm-anuncios'
                        )
                    );
                }

                if (
                    AdvertisementStatus::canBeClosed(
                        $status
                    )
                ) {
                    $this->renderSimpleActionForm(
                        $advertisementId,
                        AdvertisementAdminController::
                            ACTION_CLOSE,
                        __(
                            'Cerrar anuncio',
                            'dsm-anuncios'
                        ),
                        'button-link-delete',
                        __(
                            '¿Seguro que quieres cerrar este anuncio?',
                            'dsm-anuncios'
                        )
                    );
                }
                ?>
            </div>
        </section>
        <?php
    }

    /**
     * Información del cliente.
     *
     * @param array<string, mixed> $advertisement
     */
    private function renderCustomerCard(
        array $advertisement
    ): void {
        ?>
        <section class="dsm-admin-card">
            <h2>
                <?php
                esc_html_e(
                    'Cliente',
                    'dsm-anuncios'
                );
                ?>
            </h2>

            <p>
                <strong>
                    <?php
                    echo esc_html(
                        $this->getCustomerLabel(
                            $advertisement
                        )
                    );
                    ?>
                </strong>
            </p>

            <p>
                <?php echo esc_html(
                    (string) (
                        $advertisement[
                            'customer_email'
                        ]
                        ?? ''
                    )
                ); ?>
            </p>

            <?php if (
                !empty(
                    $advertisement[
                        'customer_phone'
                    ]
                )
            ) : ?>
                <p>
                    <?php
                    echo esc_html(
                        (string) $advertisement[
                            'customer_phone'
                        ]
                    );
                    ?>
                </p>
            <?php endif; ?>

            <?php if (
                !empty(
                    $advertisement[
                        'customer_whatsapp_phone'
                    ]
                )
            ) : ?>
                <p>
                    <strong>
                        WhatsApp:
                    </strong>

                    <?php
                    echo esc_html(
                        (string) $advertisement[
                            'customer_whatsapp_phone'
                        ]
                    );
                    ?>
                </p>
            <?php endif; ?>
        </section>
        <?php
    }

    /**
     * Metadatos.
     *
     * @param array<string, mixed> $advertisement
     */
    private function renderMetadataCard(
        array $advertisement
    ): void {
        ?>
        <section class="dsm-admin-card">
            <h2>
                <?php
                esc_html_e(
                    'Información',
                    'dsm-anuncios'
                );
                ?>
            </h2>

            <dl class="dsm-meta-list">
                <?php
                $this->renderMetaItem(
                    __('ID', 'dsm-anuncios'),
                    (string) $advertisement['id']
                );

                $this->renderMetaItem(
                    __('Slug', 'dsm-anuncios'),
                    (string) $advertisement['slug']
                );

                $this->renderMetaItem(
                    __('Creado', 'dsm-anuncios'),
                    $this->formatDate(
                        $advertisement[
                            'created_at'
                        ]
                        ?? null
                    )
                );

                $this->renderMetaItem(
                    __('Actualizado', 'dsm-anuncios'),
                    $this->formatDate(
                        $advertisement[
                            'updated_at'
                        ]
                        ?? null
                    )
                );

                $this->renderMetaItem(
                    __('Publicado', 'dsm-anuncios'),
                    $this->formatDate(
                        $advertisement[
                            'published_at'
                        ]
                        ?? null
                    )
                );

                $this->renderMetaItem(
                    __('Reservado', 'dsm-anuncios'),
                    $this->formatDate(
                        $advertisement[
                            'reserved_at'
                        ]
                        ?? null
                    )
                );

                $this->renderMetaItem(
                    __('Cerrado', 'dsm-anuncios'),
                    $this->formatDate(
                        $advertisement[
                            'closed_at'
                        ]
                        ?? null
                    )
                );
                ?>
            </dl>
        </section>
        <?php
    }

    private function renderSimpleActionForm(
        int $advertisementId,
        string $action,
        string $label,
        string $buttonClass = '',
        string $confirmation = ''
    ): void {
        ?>
        <form
            method="post"
            action="<?php echo esc_url(
                admin_url('admin-post.php')
            ); ?>"
            <?php if ($confirmation !== '') : ?>
                data-dsm-confirm-form="<?php echo esc_attr(
                    $confirmation
                ); ?>"
            <?php endif; ?>
        >
            <input
                type="hidden"
                name="action"
                value="<?php echo esc_attr(
                    $action
                ); ?>"
            >

            <input
                type="hidden"
                name="advertisement_id"
                value="<?php echo esc_attr(
                    (string) $advertisementId
                ); ?>"
            >

            <?php
            wp_nonce_field(
                AdvertisementAdminController::
                    getNonceAction(
                        $action,
                        $advertisementId
                    ),
                AdvertisementAdminController::
                    NONCE_FIELD
            );
            ?>

            <button
                type="submit"
                class="<?php echo esc_attr(
                    trim(
                        'button '
                        . $buttonClass
                    )
                ); ?>"
            >
                <?php echo esc_html($label); ?>
            </button>
        </form>
        <?php
    }

    private function renderRejectForm(
        int $advertisementId
    ): void {
        ?>
        <form
            method="post"
            action="<?php echo esc_url(
                admin_url('admin-post.php')
            ); ?>"
            class="dsm-rejection-form"
        >
            <input
                type="hidden"
                name="action"
                value="<?php echo esc_attr(
                    AdvertisementAdminController::
                        ACTION_REJECT
                ); ?>"
            >

            <input
                type="hidden"
                name="advertisement_id"
                value="<?php echo esc_attr(
                    (string) $advertisementId
                ); ?>"
            >

            <?php
            wp_nonce_field(
                AdvertisementAdminController::
                    getNonceAction(
                        AdvertisementAdminController::
                            ACTION_REJECT,
                        $advertisementId
                    ),
                AdvertisementAdminController::
                    NONCE_FIELD
            );
            ?>

            <label
                class="screen-reader-text"
                for="dsm-rejection-reason"
            >
                <?php
                esc_html_e(
                    'Motivo del rechazo',
                    'dsm-anuncios'
                );
                ?>
            </label>

            <textarea
                id="dsm-rejection-reason"
                name="rejection_reason"
                rows="4"
                required
                placeholder="<?php echo esc_attr__(
                    'Indica el motivo del rechazo',
                    'dsm-anuncios'
                ); ?>"
            ></textarea>

            <button
                type="submit"
                class="button button-link-delete"
            >
                <?php
                esc_html_e(
                    'Rechazar',
                    'dsm-anuncios'
                );
                ?>
            </button>
        </form>
        <?php
    }

    private function renderInformationRow(
        string $label,
        string $value
    ): void {
        ?>
        <tr>
            <th scope="row">
                <?php echo esc_html($label); ?>
            </th>

            <td>
                <?php echo esc_html(
                    $value !== ''
                        ? $value
                        : '—'
                ); ?>
            </td>
        </tr>
        <?php
    }

    private function renderMetaItem(
        string $label,
        string $value
    ): void {
        ?>
        <div>
            <dt>
                <?php echo esc_html($label); ?>
            </dt>

            <dd>
                <?php echo esc_html($value); ?>
            </dd>
        </div>
        <?php
    }

    /**
     * Avisos después de acciones administrativas.
     */
    private function renderNotice(): void
    {
        $status =
            isset($_GET['dsm_ad_status'])
                ? sanitize_key(
                    wp_unslash(
                        (string) $_GET[
                            'dsm_ad_status'
                        ]
                    )
                )
                : '';

        $notices = [
            'published' => [
                'success',
                __(
                    'El anuncio se publicó correctamente.',
                    'dsm-anuncios'
                ),
            ],

            'rejected' => [
                'success',
                __(
                    'El anuncio se rechazó correctamente.',
                    'dsm-anuncios'
                ),
            ],

            'reserved' => [
                'success',
                __(
                    'El anuncio se marcó como reservado.',
                    'dsm-anuncios'
                ),
            ],

            'released' => [
                'success',
                __(
                    'La reserva del anuncio se liberó.',
                    'dsm-anuncios'
                ),
            ],

            'closed' => [
                'success',
                __(
                    'El anuncio se cerró correctamente.',
                    'dsm-anuncios'
                ),
            ],

            'error' => [
                'error',
                __(
                    'No se pudo completar la acción.',
                    'dsm-anuncios'
                ),
            ],
        ];

        if (!isset($notices[$status])) {
            return;
        }

        [$type, $message] =
            $notices[$status];

        $lastError =
            AdvertisementAdminController::
                getLastError();

        if (
            $status === 'error'
            && $lastError !== ''
        ) {
            $message =
                $lastError;
        }

        ?>
        <div
            class="<?php echo esc_attr(
                'notice notice-'
                . $type
                . ' is-dismissible'
            ); ?>"
        >
            <p>
                <?php echo esc_html($message); ?>
            </p>
        </div>
        <?php
    }

    private function renderErrorPage(
        string $message
    ): void {
        ?>
        <div class="wrap dsm-anuncios-admin">
            <h1>
                <?php
                esc_html_e(
                    'Anuncios',
                    'dsm-anuncios'
                );
                ?>
            </h1>

            <div class="notice notice-error">
                <p>
                    <?php echo esc_html($message); ?>
                </p>
            </div>

            <a
                class="button button-primary"
                href="<?php echo esc_url(
                    $this->getListUrl()
                ); ?>"
            >
                <?php
                esc_html_e(
                    'Volver al listado',
                    'dsm-anuncios'
                );
                ?>
            </a>
        </div>
        <?php
    }

    /**
     * @return array{
     *     search:string,
     *     status:string,
     *     category_id:int,
     *     customer_id:int,
     *     orderby:string,
     *     order:string
     * }
     */
    private function getFilters(): array
    {
        return [
            'search' =>
                isset($_GET['search'])
                    ? sanitize_text_field(
                        wp_unslash(
                            (string) $_GET['search']
                        )
                    )
                    : '',

            'status' =>
                isset($_GET['status'])
                    ? sanitize_key(
                        wp_unslash(
                            (string) $_GET['status']
                        )
                    )
                    : '',

            'category_id' =>
                isset($_GET['category_id'])
                    ? absint(
                        wp_unslash(
                            (string) $_GET[
                                'category_id'
                            ]
                        )
                    )
                    : 0,

            'customer_id' =>
                isset($_GET['customer_id'])
                    ? absint(
                        wp_unslash(
                            (string) $_GET[
                                'customer_id'
                            ]
                        )
                    )
                    : 0,

            'orderby' =>
                isset($_GET['orderby'])
                    ? sanitize_key(
                        wp_unslash(
                            (string) $_GET[
                                'orderby'
                            ]
                        )
                    )
                    : 'created_at',

            'order' =>
                isset($_GET['order'])
                    ? strtoupper(
                        sanitize_key(
                            wp_unslash(
                                (string) $_GET[
                                    'order'
                                ]
                            )
                        )
                    )
                    : 'DESC',
        ];
    }

    /**
     * @param array<string, mixed> $advertisement
     */
    private function getCustomerLabel(
        array $advertisement
    ): string {
        $displayName =
            trim(
                (string) (
                    $advertisement[
                        'customer_display_name'
                    ]
                    ?? ''
                )
            );

        if ($displayName !== '') {
            return $displayName;
        }

        $email =
            trim(
                (string) (
                    $advertisement[
                        'customer_email'
                    ]
                    ?? ''
                )
            );

        if ($email !== '') {
            return $email;
        }

        return sprintf(
            __(
                'Cliente #%d',
                'dsm-anuncios'
            ),
            (int) (
                $advertisement[
                    'customer_id'
                ]
                ?? 0
            )
        );
    }

    /**
     * @param array<string, mixed> $entry
     */
    private function getHistoryActorLabel(
        array $entry
    ): string {
        $userName =
            trim(
                (string) (
                    $entry[
                        'changed_by_user_name'
                    ]
                    ?? ''
                )
            );

        if ($userName !== '') {
            return $userName;
        }

        $customerName =
            trim(
                (string) (
                    $entry[
                        'changed_by_customer_name'
                    ]
                    ?? ''
                )
            );

        if ($customerName !== '') {
            return $customerName;
        }

        $customerEmail =
            trim(
                (string) (
                    $entry[
                        'changed_by_customer_email'
                    ]
                    ?? ''
                )
            );

        if ($customerEmail !== '') {
            return $customerEmail;
        }

        return __(
            'Sistema',
            'dsm-anuncios'
        );
    }

    private function renderStatusBadge(
        string $status
    ): string {
        $class =
            match ($status) {
                AdvertisementStatus::ACTIVE =>
                    'success',

                AdvertisementStatus::PENDING,
                AdvertisementStatus::RESERVED =>
                    'warning',

                AdvertisementStatus::REJECTED =>
                    'danger',

                default =>
                    'muted',
            };

        return sprintf(
            '<span class="dsm-admin-status dsm-admin-status--%1$s">%2$s</span>',
            esc_attr($class),
            esc_html(
                $this->getStatusLabel(
                    $status
                )
            )
        );
    }

    private function getStatusLabel(
        string $status
    ): string {
        return match ($status) {
            AdvertisementStatus::DRAFT =>
                __('Borrador', 'dsm-anuncios'),

            AdvertisementStatus::PENDING =>
                __('Pendiente', 'dsm-anuncios'),

            AdvertisementStatus::ACTIVE =>
                __('Activo', 'dsm-anuncios'),

            AdvertisementStatus::RESERVED =>
                __('Reservado', 'dsm-anuncios'),

            AdvertisementStatus::CLOSED =>
                __('Cerrado', 'dsm-anuncios'),

            AdvertisementStatus::REJECTED =>
                __('Rechazado', 'dsm-anuncios'),

            default =>
                $status !== ''
                    ? $status
                    : '—',
        };
    }

    private function getConditionLabel(
        string $condition
    ): string {
        return match ($condition) {
            'new',
            'new_with_tags' =>
                __(
                    'Nuevo con etiquetas',
                    'dsm-anuncios'
                ),

            'new_without_tags' =>
                __(
                    'Nuevo sin etiquetas',
                    'dsm-anuncios'
                ),

            'very_good' =>
                __(
                    'Muy buen estado',
                    'dsm-anuncios'
                ),

            'good' =>
                __(
                    'Buen estado',
                    'dsm-anuncios'
                ),

            'satisfactory' =>
                __(
                    'Estado satisfactorio',
                    'dsm-anuncios'
                ),

            default =>
                $condition !== ''
                    ? $condition
                    : '—',
        };
    }

    private function formatDate(
        mixed $value
    ): string {
        if (
            !is_string($value)
            || trim($value) === ''
        ) {
            return '—';
        }

        return get_date_from_gmt(
            $value,
            'd/m/Y H:i'
        );
    }

    /**
     * @param array<string, int|string> $arguments
     */
    private function getListUrl(
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

    private function getDetailUrl(
        int $advertisementId
    ): string {
        return $this->getListUrl([
            'view' =>
                'detail',

            'advertisement_id' =>
                $advertisementId,
        ]);
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
                'No tienes permisos para administrar anuncios.',
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