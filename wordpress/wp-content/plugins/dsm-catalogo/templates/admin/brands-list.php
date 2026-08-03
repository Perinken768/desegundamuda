<?php

declare(strict_types=1);

use DSM\Catalogo\Admin\BrandAdminController;
use DSM\Catalogo\Brand\Brand;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Variables recibidas desde BrandAdminController:
 *
 * @var array<int, Brand> $brands
 * @var string|null $notice
 * @var string|null $error
 * @var string $createUrl
 */

$totalBrands =
    count($brands);

$activeBrands =
    count(
        array_filter(
            $brands,
            static fn (Brand $brand): bool =>
                $brand->isActive()
        )
    );

$verifiedBrands =
    count(
        array_filter(
            $brands,
            static fn (Brand $brand): bool =>
                $brand->isVerified()
        )
    );

$selectableBrands =
    count(
        array_filter(
            $brands,
            static fn (Brand $brand): bool =>
                $brand->canBeSelected()
        )
    );
?>

<div class="wrap dsm-catalogo-admin">
    <div class="dsm-admin-header">
        <div>
            <h1 class="wp-heading-inline">
                <?php
                echo esc_html__(
                    'Marcas',
                    'dsm-catalogo'
                );
                ?>
            </h1>

            <p class="description">
                <?php
                echo esc_html__(
                    'Gestiona las marcas que podrán utilizarse en los productos y anuncios de las tiendas.',
                    'dsm-catalogo'
                );
                ?>
            </p>
        </div>

        <div class="dsm-admin-header-actions">
            <a
                href="<?php echo esc_url($createUrl); ?>"
                class="page-title-action"
            >
                <?php
                echo esc_html__(
                    'Añadir marca',
                    'dsm-catalogo'
                );
                ?>
            </a>
        </div>
    </div>

    <hr class="wp-header-end">

    <?php if ($notice !== null): ?>
        <div
            class="notice notice-success is-dismissible"
        >
            <p>
                <?php echo esc_html($notice); ?>
            </p>
        </div>
    <?php endif; ?>

    <?php if ($error !== null): ?>
        <div
            class="notice notice-error is-dismissible"
        >
            <p>
                <?php echo esc_html($error); ?>
            </p>
        </div>
    <?php endif; ?>

    <div class="dsm-summary-grid">
        <div class="dsm-summary-card">
            <span class="dsm-summary-label">
                <?php
                echo esc_html__(
                    'Total',
                    'dsm-catalogo'
                );
                ?>
            </span>

            <strong class="dsm-summary-value">
                <?php echo esc_html((string) $totalBrands); ?>
            </strong>
        </div>

        <div class="dsm-summary-card">
            <span class="dsm-summary-label">
                <?php
                echo esc_html__(
                    'Activas',
                    'dsm-catalogo'
                );
                ?>
            </span>

            <strong class="dsm-summary-value">
                <?php echo esc_html((string) $activeBrands); ?>
            </strong>
        </div>

        <div class="dsm-summary-card">
            <span class="dsm-summary-label">
                <?php
                echo esc_html__(
                    'Verificadas',
                    'dsm-catalogo'
                );
                ?>
            </span>

            <strong class="dsm-summary-value">
                <?php echo esc_html((string) $verifiedBrands); ?>
            </strong>
        </div>

        <div class="dsm-summary-card">
            <span class="dsm-summary-label">
                <?php
                echo esc_html__(
                    'Seleccionables',
                    'dsm-catalogo'
                );
                ?>
            </span>

            <strong class="dsm-summary-value">
                <?php echo esc_html((string) $selectableBrands); ?>
            </strong>
        </div>
    </div>

    <?php if ($brands === []): ?>
        <div class="dsm-empty-state">
            <span
                class="dashicons dashicons-tag"
                aria-hidden="true"
            ></span>

            <h2>
                <?php
                echo esc_html__(
                    'Todavía no hay marcas',
                    'dsm-catalogo'
                );
                ?>
            </h2>

            <p>
                <?php
                echo esc_html__(
                    'Crea la primera marca para comenzar a utilizarla en productos y variantes.',
                    'dsm-catalogo'
                );
                ?>
            </p>

            <a
                href="<?php echo esc_url($createUrl); ?>"
                class="button button-primary"
            >
                <?php
                echo esc_html__(
                    'Crear primera marca',
                    'dsm-catalogo'
                );
                ?>
            </a>
        </div>
    <?php else: ?>
        <div class="dsm-table-wrapper">
            <table
                class="wp-list-table widefat fixed striped table-view-list"
            >
                <thead>
                    <tr>
                        <th
                            scope="col"
                            class="column-primary"
                        >
                            <?php
                            echo esc_html__(
                                'Marca',
                                'dsm-catalogo'
                            );
                            ?>
                        </th>

                        <th scope="col">
                            <?php
                            echo esc_html__(
                                'Slug',
                                'dsm-catalogo'
                            );
                            ?>
                        </th>

                        <th scope="col">
                            <?php
                            echo esc_html__(
                                'Web',
                                'dsm-catalogo'
                            );
                            ?>
                        </th>

                        <th scope="col">
                            <?php
                            echo esc_html__(
                                'Estado',
                                'dsm-catalogo'
                            );
                            ?>
                        </th>

                        <th scope="col">
                            <?php
                            echo esc_html__(
                                'Verificación',
                                'dsm-catalogo'
                            );
                            ?>
                        </th>

                        <th scope="col">
                            <?php
                            echo esc_html__(
                                'Orden',
                                'dsm-catalogo'
                            );
                            ?>
                        </th>

                        <th scope="col">
                            <?php
                            echo esc_html__(
                                'Actualizada',
                                'dsm-catalogo'
                            );
                            ?>
                        </th>
                    </tr>
                </thead>

                <tbody>
                    <?php foreach ($brands as $brand): ?>
                        <?php
                        $editUrl =
                            BrandAdminController::getEditUrl(
                                $brand
                            );

                        $toggleActiveUrl =
                            BrandAdminController::getToggleActiveUrl(
                                $brand
                            );

                        $toggleVerifiedUrl =
                            BrandAdminController::getToggleVerifiedUrl(
                                $brand
                            );

                        $website =
                            $brand->getWebsite();

                        $updatedAt =
                            $brand->getUpdatedAt();
                        ?>

                        <tr>
                            <td
                                class="column-primary"
                                data-colname="<?php
                                echo esc_attr__(
                                    'Marca',
                                    'dsm-catalogo'
                                );
                                ?>"
                            >
                                <div class="dsm-brand-cell">
                                    <div class="dsm-brand-logo">
                                        <?php if ($brand->hasLogo()): ?>
                                            <?php
                                            echo wp_get_attachment_image(
                                                $brand->getLogoId(),
                                                [
                                                    48,
                                                    48,
                                                ],
                                                false,
                                                [
                                                    'alt' =>
                                                        $brand->getName(),

                                                    'loading' =>
                                                        'lazy',
                                                ]
                                            );
                                            ?>
                                        <?php else: ?>
                                            <span
                                                class="dashicons dashicons-tag"
                                                aria-hidden="true"
                                            ></span>
                                        <?php endif; ?>
                                    </div>

                                    <div class="dsm-brand-main">
                                        <strong>
                                            <a
                                                href="<?php
                                                echo esc_url($editUrl);
                                                ?>"
                                            >
                                                <?php
                                                echo esc_html(
                                                    $brand->getName()
                                                );
                                                ?>
                                            </a>
                                        </strong>

                                        <?php
                                        $description =
                                            $brand->getDescription();
                                        ?>

                                        <?php if ($description !== null): ?>
                                            <p class="description">
                                                <?php
                                                echo esc_html(
                                                    wp_trim_words(
                                                        $description,
                                                        14
                                                    )
                                                );
                                                ?>
                                            </p>
                                        <?php endif; ?>

                                        <div class="row-actions">
                                            <span class="edit">
                                                <a
                                                    href="<?php
                                                    echo esc_url($editUrl);
                                                    ?>"
                                                >
                                                    <?php
                                                    echo esc_html__(
                                                        'Editar',
                                                        'dsm-catalogo'
                                                    );
                                                    ?>
                                                </a>
                                            </span>

                                            <span aria-hidden="true">
                                                |
                                            </span>

                                            <span class="toggle-active">
                                                <a
                                                    href="<?php
                                                    echo esc_url(
                                                        $toggleActiveUrl
                                                    );
                                                    ?>"
                                                >
                                                    <?php
                                                    echo esc_html(
                                                        $brand->isActive()
                                                            ? __(
                                                                'Desactivar',
                                                                'dsm-catalogo'
                                                            )
                                                            : __(
                                                                'Activar',
                                                                'dsm-catalogo'
                                                            )
                                                    );
                                                    ?>
                                                </a>
                                            </span>

                                            <span aria-hidden="true">
                                                |
                                            </span>

                                            <span class="toggle-verified">
                                                <a
                                                    href="<?php
                                                    echo esc_url(
                                                        $toggleVerifiedUrl
                                                    );
                                                    ?>"
                                                >
                                                    <?php
                                                    echo esc_html(
                                                        $brand->isVerified()
                                                            ? __(
                                                                'Quitar verificación',
                                                                'dsm-catalogo'
                                                            )
                                                            : __(
                                                                'Verificar',
                                                                'dsm-catalogo'
                                                            )
                                                    );
                                                    ?>
                                                </a>
                                            </span>
                                        </div>
                                    </div>
                                </div>

                                <button
                                    type="button"
                                    class="toggle-row"
                                >
                                    <span class="screen-reader-text">
                                        <?php
                                        echo esc_html__(
                                            'Mostrar más detalles',
                                            'dsm-catalogo'
                                        );
                                        ?>
                                    </span>
                                </button>
                            </td>

                            <td
                                data-colname="<?php
                                echo esc_attr__(
                                    'Slug',
                                    'dsm-catalogo'
                                );
                                ?>"
                            >
                                <code>
                                    <?php
                                    echo esc_html(
                                        $brand->getSlug()
                                    );
                                    ?>
                                </code>
                            </td>

                            <td
                                data-colname="<?php
                                echo esc_attr__(
                                    'Web',
                                    'dsm-catalogo'
                                );
                                ?>"
                            >
                                <?php if ($website !== null): ?>
                                    <a
                                        href="<?php
                                        echo esc_url($website);
                                        ?>"
                                        target="_blank"
                                        rel="noopener noreferrer"
                                    >
                                        <?php
                                        echo esc_html(
                                            wp_parse_url(
                                                $website,
                                                PHP_URL_HOST
                                            )
                                            ?: $website
                                        );
                                        ?>
                                    </a>
                                <?php else: ?>
                                    <span aria-hidden="true">
                                        —
                                    </span>
                                <?php endif; ?>
                            </td>

                            <td
                                data-colname="<?php
                                echo esc_attr__(
                                    'Estado',
                                    'dsm-catalogo'
                                );
                                ?>"
                            >
                                <?php if ($brand->isActive()): ?>
                                    <span class="dsm-status dsm-status-success">
                                        <?php
                                        echo esc_html__(
                                            'Activa',
                                            'dsm-catalogo'
                                        );
                                        ?>
                                    </span>
                                <?php else: ?>
                                    <span class="dsm-status dsm-status-muted">
                                        <?php
                                        echo esc_html__(
                                            'Inactiva',
                                            'dsm-catalogo'
                                        );
                                        ?>
                                    </span>
                                <?php endif; ?>
                            </td>

                            <td
                                data-colname="<?php
                                echo esc_attr__(
                                    'Verificación',
                                    'dsm-catalogo'
                                );
                                ?>"
                            >
                                <?php if ($brand->isVerified()): ?>
                                    <span class="dsm-status dsm-status-info">
                                        <?php
                                        echo esc_html__(
                                            'Verificada',
                                            'dsm-catalogo'
                                        );
                                        ?>
                                    </span>
                                <?php else: ?>
                                    <span class="dsm-status dsm-status-warning">
                                        <?php
                                        echo esc_html__(
                                            'Pendiente',
                                            'dsm-catalogo'
                                        );
                                        ?>
                                    </span>
                                <?php endif; ?>
                            </td>

                            <td
                                data-colname="<?php
                                echo esc_attr__(
                                    'Orden',
                                    'dsm-catalogo'
                                );
                                ?>"
                            >
                                <?php
                                echo esc_html(
                                    (string) $brand->getSortOrder()
                                );
                                ?>
                            </td>

                            <td
                                data-colname="<?php
                                echo esc_attr__(
                                    'Actualizada',
                                    'dsm-catalogo'
                                );
                                ?>"
                            >
                                <?php
                                echo esc_html(
                                    $updatedAt->format(
                                        'd/m/Y H:i'
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
</div>