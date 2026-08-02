<?php

declare(strict_types=1);

use DSM\Anuncios\Category\Category;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * @var array<int, Category> $categories
 */

$adminStatus = isset($_GET['admin_status'])
    ? sanitize_key(
        wp_unslash($_GET['admin_status'])
    )
    : '';

$createUrl = add_query_arg(
    [
        'page' => 'dsm-anuncios-categories',
        'action' => 'create',
    ],
    admin_url('admin.php')
);
?>

<div class="wrap dsm-anuncios-admin">
    <h1 class="wp-heading-inline">
        <?php
        esc_html_e(
            'Categorías de anuncios',
            'dsm-anuncios'
        );
        ?>
    </h1>

    <a
        class="page-title-action"
        href="<?php echo esc_url($createUrl); ?>"
    >
        <?php
        esc_html_e(
            'Añadir categoría',
            'dsm-anuncios'
        );
        ?>
    </a>

    <hr class="wp-header-end">

    <?php if ($adminStatus === 'category_created') : ?>
        <div
            class="notice notice-success is-dismissible"
            role="alert"
        >
            <p>
                <?php
                esc_html_e(
                    'La categoría se creó correctamente.',
                    'dsm-anuncios'
                );
                ?>
            </p>
        </div>

    <?php elseif ($adminStatus === 'category_updated') : ?>
        <div
            class="notice notice-success is-dismissible"
            role="alert"
        >
            <p>
                <?php
                esc_html_e(
                    'La categoría se actualizó correctamente.',
                    'dsm-anuncios'
                );
                ?>
            </p>
        </div>

    <?php elseif ($adminStatus === 'category_activated') : ?>
        <div
            class="notice notice-success is-dismissible"
            role="alert"
        >
            <p>
                <?php
                esc_html_e(
                    'La categoría se activó correctamente.',
                    'dsm-anuncios'
                );
                ?>
            </p>
        </div>

    <?php elseif ($adminStatus === 'category_deactivated') : ?>
        <div
            class="notice notice-warning is-dismissible"
            role="alert"
        >
            <p>
                <?php
                esc_html_e(
                    'La categoría se desactivó correctamente.',
                    'dsm-anuncios'
                );
                ?>
            </p>
        </div>

    <?php elseif ($adminStatus === 'category_error') : ?>
        <div
            class="notice notice-error is-dismissible"
            role="alert"
        >
            <p>
                <?php
                esc_html_e(
                    'No se pudo completar la acción sobre la categoría. Revisa los datos e inténtalo de nuevo.',
                    'dsm-anuncios'
                );
                ?>
            </p>
        </div>
    <?php endif; ?>

    <p>
        <?php
        esc_html_e(
            'Desde esta pantalla puedes crear, editar, ordenar, activar o desactivar las categorías disponibles para los anuncios.',
            'dsm-anuncios'
        );
        ?>
    </p>

    <div class="dsm-admin-table-scroll">
        <table class="wp-list-table widefat striped dsm-categories-table">
            <thead>
                <tr>
                    <th>
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
                            'Categoría',
                            'dsm-anuncios'
                        );
                        ?>
                    </th>

                    <th>
                        <?php
                        esc_html_e(
                            'Categoría superior',
                            'dsm-anuncios'
                        );
                        ?>
                    </th>

                    <th>
                        <?php
                        esc_html_e(
                            'Slug',
                            'dsm-anuncios'
                        );
                        ?>
                    </th>

                    <th>
                        <?php
                        esc_html_e(
                            'Marketplace',
                            'dsm-anuncios'
                        );
                        ?>
                    </th>

                    <th>
                        <?php
                        esc_html_e(
                            'Tiendas',
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
                            'Orden',
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
                <?php if ($categories === []) : ?>
                    <tr>
                        <td colspan="9">
                            <?php
                            esc_html_e(
                                'Todavía no hay categorías creadas.',
                                'dsm-anuncios'
                            );
                            ?>
                        </td>
                    </tr>
                <?php else : ?>
                    <?php foreach ($categories as $category) : ?>
                        <?php
                        $categoryId =
                            $category->getId();

                        $parentName = '—';

                        if ($category->getParentId() !== null) {
                            foreach (
                                $categories
                                as $possibleParent
                            ) {
                                if (
                                    $possibleParent->getId()
                                    === $category->getParentId()
                                ) {
                                    $parentName =
                                        $possibleParent->getName();

                                    break;
                                }
                            }
                        }

                        $editUrl = add_query_arg(
                            [
                                'page' =>
                                    'dsm-anuncios-categories',

                                'action' =>
                                    'edit',

                                'category_id' =>
                                    $categoryId,
                            ],
                            admin_url('admin.php')
                        );
                        ?>

                        <tr>
                            <td>
                                <?php
                                echo esc_html(
                                    (string) $categoryId
                                );
                                ?>
                            </td>

                            <td>
                                <strong>
                                    <a
                                        href="<?php echo esc_url(
                                            $editUrl
                                        ); ?>"
                                    >
                                        <?php
                                        echo esc_html(
                                            $category->getName()
                                        );
                                        ?>
                                    </a>
                                </strong>

                                <?php
                                if (
                                    $category->getDescription()
                                    !== null
                                ) :
                                    ?>
                                    <br>

                                    <small>
                                        <?php
                                        echo esc_html(
                                            wp_trim_words(
                                                $category
                                                    ->getDescription(),
                                                18,
                                                '…'
                                            )
                                        );
                                        ?>
                                    </small>
                                <?php endif; ?>
                            </td>

                            <td>
                                <?php
                                echo esc_html(
                                    $parentName
                                );
                                ?>
                            </td>

                            <td>
                                <code>
                                    <?php
                                    echo esc_html(
                                        $category->getSlug()
                                    );
                                    ?>
                                </code>
                            </td>

                            <td>
                                <?php
                                if (
                                    $category
                                        ->isMarketplaceAllowed()
                                ) :
                                    ?>
                                    <span
                                        class="dsm-admin-status dsm-admin-status--success"
                                    >
                                        <?php
                                        esc_html_e(
                                            'Permitida',
                                            'dsm-anuncios'
                                        );
                                        ?>
                                    </span>
                                <?php else : ?>
                                    <span
                                        class="dsm-admin-status dsm-admin-status--muted"
                                    >
                                        <?php
                                        esc_html_e(
                                            'No permitida',
                                            'dsm-anuncios'
                                        );
                                        ?>
                                    </span>
                                <?php endif; ?>
                            </td>

                            <td>
                                <?php
                                if (
                                    $category
                                        ->isStoreAllowed()
                                ) :
                                    ?>
                                    <span
                                        class="dsm-admin-status dsm-admin-status--success"
                                    >
                                        <?php
                                        esc_html_e(
                                            'Permitida',
                                            'dsm-anuncios'
                                        );
                                        ?>
                                    </span>
                                <?php else : ?>
                                    <span
                                        class="dsm-admin-status dsm-admin-status--muted"
                                    >
                                        <?php
                                        esc_html_e(
                                            'No permitida',
                                            'dsm-anuncios'
                                        );
                                        ?>
                                    </span>
                                <?php endif; ?>
                            </td>

                            <td>
                                <?php if ($category->isActive()) : ?>
                                    <span
                                        class="dsm-admin-status dsm-admin-status--success"
                                    >
                                        <?php
                                        esc_html_e(
                                            'Activa',
                                            'dsm-anuncios'
                                        );
                                        ?>
                                    </span>
                                <?php else : ?>
                                    <span
                                        class="dsm-admin-status dsm-admin-status--warning"
                                    >
                                        <?php
                                        esc_html_e(
                                            'Inactiva',
                                            'dsm-anuncios'
                                        );
                                        ?>
                                    </span>
                                <?php endif; ?>
                            </td>

                            <td>
                                <?php
                                echo esc_html(
                                    (string) $category
                                        ->getSortOrder()
                                );
                                ?>
                            </td>

                            <td>
                                <div class="dsm-admin-actions">
                                    <a
                                        class="button button-small"
                                        href="<?php echo esc_url(
                                            $editUrl
                                        ); ?>"
                                    >
                                        <?php
                                        esc_html_e(
                                            'Editar',
                                            'dsm-anuncios'
                                        );
                                        ?>
                                    </a>

                                    <form
                                        method="post"
                                        action="<?php echo esc_url(
                                            admin_url(
                                                'admin-post.php'
                                            )
                                        ); ?>"
                                    >
                                        <input
                                            type="hidden"
                                            name="action"
                                            value="dsm_anuncios_admin_toggle_category"
                                        >

                                        <input
                                            type="hidden"
                                            name="category_id"
                                            value="<?php echo esc_attr(
                                                (string) $categoryId
                                            ); ?>"
                                        >

                                        <input
                                            type="hidden"
                                            name="active"
                                            value="<?php echo esc_attr(
                                                $category->isActive()
                                                    ? '0'
                                                    : '1'
                                            ); ?>"
                                        >

                                        <?php
                                        wp_nonce_field(
                                            'dsm_anuncios_admin_toggle_category',
                                            'dsm_category_nonce'
                                        );
                                        ?>

                                        <button
                                            class="<?php echo esc_attr(
                                                $category->isActive()
                                                    ? 'button button-small'
                                                    : 'button button-primary button-small'
                                            ); ?>"
                                            type="submit"
                                        >
                                            <?php
                                            echo esc_html(
                                                $category->isActive()
                                                    ? __(
                                                        'Desactivar',
                                                        'dsm-anuncios'
                                                    )
                                                    : __(
                                                        'Activar',
                                                        'dsm-anuncios'
                                                    )
                                            );
                                            ?>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>

            <tfoot>
                <tr>
                    <th>
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
                            'Categoría',
                            'dsm-anuncios'
                        );
                        ?>
                    </th>

                    <th>
                        <?php
                        esc_html_e(
                            'Categoría superior',
                            'dsm-anuncios'
                        );
                        ?>
                    </th>

                    <th>
                        <?php
                        esc_html_e(
                            'Slug',
                            'dsm-anuncios'
                        );
                        ?>
                    </th>

                    <th>
                        <?php
                        esc_html_e(
                            'Marketplace',
                            'dsm-anuncios'
                        );
                        ?>
                    </th>

                    <th>
                        <?php
                        esc_html_e(
                            'Tiendas',
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
                            'Orden',
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
            </tfoot>
        </table>
    </div>
</div>