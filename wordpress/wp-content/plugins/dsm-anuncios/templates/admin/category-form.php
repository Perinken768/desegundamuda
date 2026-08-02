<?php

declare(strict_types=1);

use DSM\Anuncios\Category\Category;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * @var Category|null $category
 * @var array<int, Category> $categories
 */

$isEditing =
    $category instanceof Category;

$categoryId = $isEditing
    ? $category->getId()
    : 0;

$adminStatus = isset($_GET['admin_status'])
    ? sanitize_key(
        wp_unslash($_GET['admin_status'])
    )
    : '';

$listUrl = add_query_arg(
    [
        'page' =>
            'dsm-anuncios-categories',
    ],
    admin_url('admin.php')
);

$pageTitle = $isEditing
    ? __(
        'Editar categoría',
        'dsm-anuncios'
    )
    : __(
        'Añadir categoría',
        'dsm-anuncios'
    );

$name = $isEditing
    ? $category->getName()
    : '';

$slug = $isEditing
    ? $category->getSlug()
    : '';

$description = $isEditing
    ? (
        $category->getDescription()
        ?? ''
    )
    : '';

$parentId = $isEditing
    ? $category->getParentId()
    : null;

$marketplaceAllowed = $isEditing
    ? $category->isMarketplaceAllowed()
    : true;

$storeAllowed = $isEditing
    ? $category->isStoreAllowed()
    : true;

$active = $isEditing
    ? $category->isActive()
    : true;

$sortOrder = $isEditing
    ? $category->getSortOrder()
    : 0;
?>

<div class="wrap dsm-anuncios-admin">
    <h1>
        <?php
        echo esc_html(
            $pageTitle
        );
        ?>
    </h1>

    <?php if ($adminStatus === 'category_error') : ?>
        <div
            class="notice notice-error"
            role="alert"
        >
            <p>
                <?php
                esc_html_e(
                    'No se pudo guardar la categoría. Revisa los datos e inténtalo de nuevo.',
                    'dsm-anuncios'
                );
                ?>
            </p>
        </div>
    <?php endif; ?>

    <p>
        <?php
        esc_html_e(
            'Las categorías determinan dónde pueden publicar los clientes y las futuras tiendas profesionales.',
            'dsm-anuncios'
        );
        ?>
    </p>

    <form
        method="post"
        action="<?php echo esc_url(
            admin_url('admin-post.php')
        ); ?>"
    >
        <input
            type="hidden"
            name="action"
            value="dsm_anuncios_admin_save_category"
        >

        <input
            type="hidden"
            name="category_id"
            value="<?php echo esc_attr(
                (string) $categoryId
            ); ?>"
        >

        <?php
        wp_nonce_field(
            'dsm_anuncios_admin_save_category',
            'dsm_category_nonce'
        );
        ?>

        <table class="form-table" role="presentation">
            <tbody>
                <tr>
                    <th scope="row">
                        <label for="dsm-category-name">
                            <?php
                            esc_html_e(
                                'Nombre',
                                'dsm-anuncios'
                            );
                            ?>
                        </label>
                    </th>

                    <td>
                        <input
                            id="dsm-category-name"
                            class="regular-text"
                            name="name"
                            type="text"
                            maxlength="120"
                            value="<?php echo esc_attr(
                                $name
                            ); ?>"
                            required
                        >

                        <p class="description">
                            <?php
                            esc_html_e(
                                'Nombre visible para administradores y clientes.',
                                'dsm-anuncios'
                            );
                            ?>
                        </p>
                    </td>
                </tr>

                <tr>
                    <th scope="row">
                        <label for="dsm-category-slug">
                            <?php
                            esc_html_e(
                                'Slug',
                                'dsm-anuncios'
                            );
                            ?>
                        </label>
                    </th>

                    <td>
                        <input
                            id="dsm-category-slug"
                            class="regular-text code"
                            name="slug"
                            type="text"
                            maxlength="140"
                            value="<?php echo esc_attr(
                                $slug
                            ); ?>"
                        >

                        <p class="description">
                            <?php
                            esc_html_e(
                                'Identificador para URLs y filtros. Si lo dejas vacío se generará desde el nombre.',
                                'dsm-anuncios'
                            );
                            ?>
                        </p>
                    </td>
                </tr>

                <tr>
                    <th scope="row">
                        <label for="dsm-category-parent">
                            <?php
                            esc_html_e(
                                'Categoría superior',
                                'dsm-anuncios'
                            );
                            ?>
                        </label>
                    </th>

                    <td>
                        <select
                            id="dsm-category-parent"
                            name="parent_id"
                        >
                            <option value="0">
                                <?php
                                esc_html_e(
                                    'Sin categoría superior',
                                    'dsm-anuncios'
                                );
                                ?>
                            </option>

                            <?php foreach ($categories as $possibleParent) : ?>
                                <?php
                                if (
                                    $isEditing
                                    && $possibleParent->getId()
                                        === $categoryId
                                ) {
                                    continue;
                                }
                                ?>

                                <option
                                    value="<?php echo esc_attr(
                                        (string) $possibleParent->getId()
                                    ); ?>"
                                    <?php
                                    selected(
                                        $parentId,
                                        $possibleParent->getId()
                                    );
                                    ?>
                                >
                                    <?php
                                    echo esc_html(
                                        $possibleParent->getName()
                                    );
                                    ?>
                                </option>
                            <?php endforeach; ?>
                        </select>

                        <p class="description">
                            <?php
                            esc_html_e(
                                'Permite crear categorías y subcategorías jerárquicas.',
                                'dsm-anuncios'
                            );
                            ?>
                        </p>
                    </td>
                </tr>

                <tr>
                    <th scope="row">
                        <label for="dsm-category-description">
                            <?php
                            esc_html_e(
                                'Descripción',
                                'dsm-anuncios'
                            );
                            ?>
                        </label>
                    </th>

                    <td>
                        <textarea
                            id="dsm-category-description"
                            class="large-text"
                            name="description"
                            rows="5"
                        ><?php
                        echo esc_textarea(
                            $description
                        );
                        ?></textarea>

                        <p class="description">
                            <?php
                            esc_html_e(
                                'Texto interno o descriptivo de la categoría.',
                                'dsm-anuncios'
                            );
                            ?>
                        </p>
                    </td>
                </tr>

                <tr>
                    <th scope="row">
                        <?php
                        esc_html_e(
                            'Ámbitos permitidos',
                            'dsm-anuncios'
                        );
                        ?>
                    </th>

                    <td>
                        <fieldset>
                            <label>
                                <input
                                    name="marketplace_allowed"
                                    type="checkbox"
                                    value="1"
                                    <?php
                                    checked(
                                        $marketplaceAllowed
                                    );
                                    ?>
                                >

                                <?php
                                esc_html_e(
                                    'Permitir en anuncios de clientes',
                                    'dsm-anuncios'
                                );
                                ?>
                            </label>

                            <br>

                            <label>
                                <input
                                    name="store_allowed"
                                    type="checkbox"
                                    value="1"
                                    <?php
                                    checked(
                                        $storeAllowed
                                    );
                                    ?>
                                >

                                <?php
                                esc_html_e(
                                    'Permitir en tiendas profesionales',
                                    'dsm-anuncios'
                                );
                                ?>
                            </label>
                        </fieldset>

                        <p class="description">
                            <?php
                            esc_html_e(
                                'Estas reglas permiten reservar determinadas categorías para marketplace, tiendas o ambos.',
                                'dsm-anuncios'
                            );
                            ?>
                        </p>
                    </td>
                </tr>

                <tr>
                    <th scope="row">
                        <?php
                        esc_html_e(
                            'Estado',
                            'dsm-anuncios'
                        );
                        ?>
                    </th>

                    <td>
                        <label>
                            <input
                                name="is_active"
                                type="checkbox"
                                value="1"
                                <?php
                                checked(
                                    $active
                                );
                                ?>
                            >

                            <?php
                            esc_html_e(
                                'Categoría activa',
                                'dsm-anuncios'
                            );
                            ?>
                        </label>

                        <p class="description">
                            <?php
                            esc_html_e(
                                'Una categoría inactiva no aparecerá en los formularios de publicación.',
                                'dsm-anuncios'
                            );
                            ?>
                        </p>
                    </td>
                </tr>

                <tr>
                    <th scope="row">
                        <label for="dsm-category-sort-order">
                            <?php
                            esc_html_e(
                                'Orden',
                                'dsm-anuncios'
                            );
                            ?>
                        </label>
                    </th>

                    <td>
                        <input
                            id="dsm-category-sort-order"
                            class="small-text"
                            name="sort_order"
                            type="number"
                            min="0"
                            step="1"
                            value="<?php echo esc_attr(
                                (string) $sortOrder
                            ); ?>"
                        >

                        <p class="description">
                            <?php
                            esc_html_e(
                                'Las categorías con un número menor aparecerán primero.',
                                'dsm-anuncios'
                            );
                            ?>
                        </p>
                    </td>
                </tr>
            </tbody>
        </table>

        <?php
        submit_button(
            $isEditing
                ? __(
                    'Guardar cambios',
                    'dsm-anuncios'
                )
                : __(
                    'Crear categoría',
                    'dsm-anuncios'
                )
        );
        ?>

        <a
            class="button button-secondary"
            href="<?php echo esc_url(
                $listUrl
            ); ?>"
        >
            <?php
            esc_html_e(
                'Cancelar',
                'dsm-anuncios'
            );
            ?>
        </a>
    </form>
</div>