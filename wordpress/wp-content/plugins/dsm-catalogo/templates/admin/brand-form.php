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
 * @var Brand|null $brand
 * @var string|null $notice
 * @var string|null $error
 * @var string $formAction
 * @var string $listUrl
 */

$isEditing =
    $brand instanceof Brand;

$pageTitle =
    $isEditing
        ? __('Editar marca', 'dsm-catalogo')
        : __('Añadir marca', 'dsm-catalogo');

$brandId =
    $isEditing
        ? $brand->getId()
        : 0;

$name =
    $isEditing
        ? $brand->getName()
        : '';

$slug =
    $isEditing
        ? $brand->getSlug()
        : '';

$description =
    $isEditing
        ? ($brand->getDescription() ?? '')
        : '';

$website =
    $isEditing
        ? ($brand->getWebsite() ?? '')
        : '';

$logoId =
    $isEditing
        ? ($brand->getLogoId() ?? 0)
        : 0;

$isActive =
    $isEditing
        ? $brand->isActive()
        : true;

$isVerified =
    $isEditing
        ? $brand->isVerified()
        : true;

$sortOrder =
    $isEditing
        ? $brand->getSortOrder()
        : 0;

$saveButtonText =
    $isEditing
        ? __('Guardar cambios', 'dsm-catalogo')
        : __('Crear marca', 'dsm-catalogo');
?>

<div class="wrap dsm-catalogo-admin">
    <div class="dsm-admin-header">
        <div>
            <h1 class="wp-heading-inline">
                <?php echo esc_html($pageTitle); ?>
            </h1>

            <p class="description">
                <?php
                echo esc_html__(
                    'Las marcas son opcionales en los productos, pero solo las marcas activas y verificadas estarán disponibles para seleccionar.',
                    'dsm-catalogo'
                );
                ?>
            </p>
        </div>

        <div class="dsm-admin-header-actions">
            <a
                href="<?php echo esc_url($listUrl); ?>"
                class="page-title-action"
            >
                <?php
                echo esc_html__(
                    'Volver a marcas',
                    'dsm-catalogo'
                );
                ?>
            </a>
        </div>
    </div>

    <hr class="wp-header-end">

    <?php if ($notice !== null): ?>
        <div class="notice notice-success is-dismissible">
            <p>
                <?php echo esc_html($notice); ?>
            </p>
        </div>
    <?php endif; ?>

    <?php if ($error !== null): ?>
        <div class="notice notice-error is-dismissible">
            <p>
                <?php echo esc_html($error); ?>
            </p>
        </div>
    <?php endif; ?>

    <form
        method="post"
        action="<?php echo esc_url($formAction); ?>"
        class="dsm-admin-form"
    >
        <input
            type="hidden"
            name="action"
            value="<?php
            echo esc_attr(
                BrandAdminController::getSaveAction()
            );
            ?>"
        >

        <input
            type="hidden"
            name="brand_id"
            value="<?php echo esc_attr((string) $brandId); ?>"
        >

        <?php
        wp_nonce_field(
            BrandAdminController::getSaveNonceAction(),
            BrandAdminController::getSaveNonceField()
        );
        ?>

        <div class="dsm-form-layout">
            <main class="dsm-form-main">
                <div class="dsm-panel">
                    <div class="dsm-panel-header">
                        <h2>
                            <?php
                            echo esc_html__(
                                'Información de la marca',
                                'dsm-catalogo'
                            );
                            ?>
                        </h2>
                    </div>

                    <div class="dsm-panel-body">
                        <table
                            class="form-table"
                            role="presentation"
                        >
                            <tbody>
                                <tr>
                                    <th scope="row">
                                        <label for="dsm-brand-name">
                                            <?php
                                            echo esc_html__(
                                                'Nombre',
                                                'dsm-catalogo'
                                            );
                                            ?>
                                        </label>
                                    </th>

                                    <td>
                                        <input
                                            type="text"
                                            id="dsm-brand-name"
                                            name="name"
                                            value="<?php
                                            echo esc_attr($name);
                                            ?>"
                                            class="regular-text"
                                            maxlength="120"
                                            autocomplete="off"
                                            required
                                        >

                                        <p class="description">
                                            <?php
                                            echo esc_html__(
                                                'Nombre visible de la marca. Debe ser único.',
                                                'dsm-catalogo'
                                            );
                                            ?>
                                        </p>
                                    </td>
                                </tr>

                                <tr>
                                    <th scope="row">
                                        <label for="dsm-brand-slug">
                                            <?php
                                            echo esc_html__(
                                                'Slug',
                                                'dsm-catalogo'
                                            );
                                            ?>
                                        </label>
                                    </th>

                                    <td>
                                        <input
                                            type="text"
                                            id="dsm-brand-slug"
                                            name="slug"
                                            value="<?php
                                            echo esc_attr($slug);
                                            ?>"
                                            class="regular-text"
                                            maxlength="140"
                                            autocomplete="off"
                                        >

                                        <p class="description">
                                            <?php
                                            echo esc_html__(
                                                'Identificador utilizado en las URLs. Si se deja vacío, se generará automáticamente a partir del nombre.',
                                                'dsm-catalogo'
                                            );
                                            ?>
                                        </p>
                                    </td>
                                </tr>

                                <tr>
                                    <th scope="row">
                                        <label for="dsm-brand-description">
                                            <?php
                                            echo esc_html__(
                                                'Descripción',
                                                'dsm-catalogo'
                                            );
                                            ?>
                                        </label>
                                    </th>

                                    <td>
                                        <textarea
                                            id="dsm-brand-description"
                                            name="description"
                                            rows="6"
                                            class="large-text"
                                        ><?php
                                        echo esc_textarea(
                                            $description
                                        );
                                        ?></textarea>

                                        <p class="description">
                                            <?php
                                            echo esc_html__(
                                                'Información interna o pública sobre la marca.',
                                                'dsm-catalogo'
                                            );
                                            ?>
                                        </p>
                                    </td>
                                </tr>

                                <tr>
                                    <th scope="row">
                                        <label for="dsm-brand-website">
                                            <?php
                                            echo esc_html__(
                                                'Página web',
                                                'dsm-catalogo'
                                            );
                                            ?>
                                        </label>
                                    </th>

                                    <td>
                                        <input
                                            type="url"
                                            id="dsm-brand-website"
                                            name="website"
                                            value="<?php
                                            echo esc_attr($website);
                                            ?>"
                                            class="regular-text"
                                            placeholder="https://"
                                            autocomplete="url"
                                        >

                                        <p class="description">
                                            <?php
                                            echo esc_html__(
                                                'Dirección oficial de la marca. Es opcional.',
                                                'dsm-catalogo'
                                            );
                                            ?>
                                        </p>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="dsm-panel">
                    <div class="dsm-panel-header">
                        <h2>
                            <?php
                            echo esc_html__(
                                'Logotipo',
                                'dsm-catalogo'
                            );
                            ?>
                        </h2>
                    </div>

                    <div class="dsm-panel-body">
                        <div class="dsm-media-field">
                            <input
                                type="hidden"
                                id="dsm-brand-logo-id"
                                name="logo_id"
                                value="<?php
                                echo esc_attr(
                                    $logoId > 0
                                        ? (string) $logoId
                                        : ''
                                );
                                ?>"
                            >

                            <div
                                id="dsm-brand-logo-preview"
                                class="dsm-media-preview"
                            >
                                <?php if ($logoId > 0): ?>
                                    <?php
                                    echo wp_get_attachment_image(
                                        $logoId,
                                        'medium',
                                        false,
                                        [
                                            'alt' =>
                                                $name,

                                            'class' =>
                                                'dsm-brand-logo-image',
                                        ]
                                    );
                                    ?>
                                <?php else: ?>
                                    <div class="dsm-media-placeholder">
                                        <span
                                            class="dashicons dashicons-format-image"
                                            aria-hidden="true"
                                        ></span>

                                        <span>
                                            <?php
                                            echo esc_html__(
                                                'Sin logotipo seleccionado',
                                                'dsm-catalogo'
                                            );
                                            ?>
                                        </span>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <div class="dsm-media-actions">
                                <button
                                    type="button"
                                    id="dsm-select-brand-logo"
                                    class="button"
                                >
                                    <?php
                                    echo esc_html__(
                                        'Seleccionar logotipo',
                                        'dsm-catalogo'
                                    );
                                    ?>
                                </button>

                                <button
                                    type="button"
                                    id="dsm-remove-brand-logo"
                                    class="button"
                                    <?php
                                    disabled(
                                        $logoId <= 0
                                    );
                                    ?>
                                >
                                    <?php
                                    echo esc_html__(
                                        'Quitar logotipo',
                                        'dsm-catalogo'
                                    );
                                    ?>
                                </button>
                            </div>

                            <p class="description">
                                <?php
                                echo esc_html__(
                                    'El logotipo es opcional. Se recomienda utilizar una imagen cuadrada, limpia y con fondo transparente.',
                                    'dsm-catalogo'
                                );
                                ?>
                            </p>

                            <noscript>
                                <p class="description">
                                    <?php
                                    echo esc_html__(
                                        'La selección desde la biblioteca multimedia requiere JavaScript. También puedes escribir directamente el identificador del adjunto.',
                                        'dsm-catalogo'
                                    );
                                    ?>
                                </p>

                                <label for="dsm-brand-logo-id-fallback">
                                    <?php
                                    echo esc_html__(
                                        'ID del adjunto',
                                        'dsm-catalogo'
                                    );
                                    ?>
                                </label>

                                <input
                                    type="number"
                                    id="dsm-brand-logo-id-fallback"
                                    name="logo_id"
                                    value="<?php
                                    echo esc_attr(
                                        $logoId > 0
                                            ? (string) $logoId
                                            : ''
                                    );
                                    ?>"
                                    min="1"
                                    step="1"
                                >
                            </noscript>
                        </div>
                    </div>
                </div>
            </main>

            <aside class="dsm-form-sidebar">
                <div class="dsm-panel">
                    <div class="dsm-panel-header">
                        <h2>
                            <?php
                            echo esc_html__(
                                'Publicación',
                                'dsm-catalogo'
                            );
                            ?>
                        </h2>
                    </div>

                    <div class="dsm-panel-body">
                        <fieldset class="dsm-checkbox-group">
                            <legend class="screen-reader-text">
                                <?php
                                echo esc_html__(
                                    'Estado de la marca',
                                    'dsm-catalogo'
                                );
                                ?>
                            </legend>

                            <label>
                                <input
                                    type="checkbox"
                                    name="is_active"
                                    value="1"
                                    <?php
                                    checked(
                                        $isActive
                                    );
                                    ?>
                                >

                                <span>
                                    <strong>
                                        <?php
                                        echo esc_html__(
                                            'Marca activa',
                                            'dsm-catalogo'
                                        );
                                        ?>
                                    </strong>

                                    <small>
                                        <?php
                                        echo esc_html__(
                                            'La marca podrá utilizarse si también está verificada.',
                                            'dsm-catalogo'
                                        );
                                        ?>
                                    </small>
                                </span>
                            </label>

                            <label>
                                <input
                                    type="checkbox"
                                    name="is_verified"
                                    value="1"
                                    <?php
                                    checked(
                                        $isVerified
                                    );
                                    ?>
                                >

                                <span>
                                    <strong>
                                        <?php
                                        echo esc_html__(
                                            'Marca verificada',
                                            'dsm-catalogo'
                                        );
                                        ?>
                                    </strong>

                                    <small>
                                        <?php
                                        echo esc_html__(
                                            'Confirma que la marca ha sido revisada por un administrador.',
                                            'dsm-catalogo'
                                        );
                                        ?>
                                    </small>
                                </span>
                            </label>
                        </fieldset>

                        <hr>

                        <label for="dsm-brand-sort-order">
                            <strong>
                                <?php
                                echo esc_html__(
                                    'Orden',
                                    'dsm-catalogo'
                                );
                                ?>
                            </strong>
                        </label>

                        <input
                            type="number"
                            id="dsm-brand-sort-order"
                            name="sort_order"
                            value="<?php
                            echo esc_attr(
                                (string) $sortOrder
                            );
                            ?>"
                            class="small-text"
                            min="0"
                            step="1"
                        >

                        <p class="description">
                            <?php
                            echo esc_html__(
                                'Las marcas con un número menor aparecerán primero.',
                                'dsm-catalogo'
                            );
                            ?>
                        </p>
                    </div>

                    <div class="dsm-panel-footer">
                        <?php
                        submit_button(
                            $saveButtonText,
                            'primary',
                            'submit',
                            false
                        );
                        ?>
                    </div>
                </div>

                <?php if ($isEditing): ?>
                    <div class="dsm-panel">
                        <div class="dsm-panel-header">
                            <h2>
                                <?php
                                echo esc_html__(
                                    'Información técnica',
                                    'dsm-catalogo'
                                );
                                ?>
                            </h2>
                        </div>

                        <div class="dsm-panel-body">
                            <dl class="dsm-meta-list">
                                <div>
                                    <dt>
                                        <?php
                                        echo esc_html__(
                                            'Identificador',
                                            'dsm-catalogo'
                                        );
                                        ?>
                                    </dt>

                                    <dd>
                                        <?php
                                        echo esc_html(
                                            (string) $brand->getId()
                                        );
                                        ?>
                                    </dd>
                                </div>

                                <div>
                                    <dt>
                                        <?php
                                        echo esc_html__(
                                            'Creada',
                                            'dsm-catalogo'
                                        );
                                        ?>
                                    </dt>

                                    <dd>
                                        <?php
                                        echo esc_html(
                                            $brand
                                                ->getCreatedAt()
                                                ->format(
                                                    'd/m/Y H:i'
                                                )
                                        );
                                        ?>
                                    </dd>
                                </div>

                                <div>
                                    <dt>
                                        <?php
                                        echo esc_html__(
                                            'Actualizada',
                                            'dsm-catalogo'
                                        );
                                        ?>
                                    </dt>

                                    <dd>
                                        <?php
                                        echo esc_html(
                                            $brand
                                                ->getUpdatedAt()
                                                ->format(
                                                    'd/m/Y H:i'
                                                )
                                        );
                                        ?>
                                    </dd>
                                </div>

                                <div>
                                    <dt>
                                        <?php
                                        echo esc_html__(
                                            'Seleccionable',
                                            'dsm-catalogo'
                                        );
                                        ?>
                                    </dt>

                                    <dd>
                                        <?php
                                        echo esc_html(
                                            $brand->canBeSelected()
                                                ? __(
                                                    'Sí',
                                                    'dsm-catalogo'
                                                )
                                                : __(
                                                    'No',
                                                    'dsm-catalogo'
                                                )
                                        );
                                        ?>
                                    </dd>
                                </div>
                            </dl>
                        </div>
                    </div>
                <?php endif; ?>
            </aside>
        </div>
    </form>
</div>