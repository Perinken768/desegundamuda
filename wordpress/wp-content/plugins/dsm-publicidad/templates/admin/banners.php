<?php

declare(strict_types=1);

use DSM\Publicidad\Admin\AdvertisingAdminController;

if (!defined('ABSPATH')) {
    exit;
}

$currentId =
    $editingBanner !== null
        ? $editingBanner->getId()
        : 0;

$currentImageId =
    $editingBanner !== null
        ? $editingBanner
            ->getImageAttachmentId()
        : 0;

$currentImageUrl =
    $currentImageId > 0
        ? wp_get_attachment_image_url(
            $currentImageId,
            'medium'
        )
        : false;

$notice =
    isset($_GET['dsm_publicidad_notice'])
        ? sanitize_key(
            wp_unslash(
                (string) $_GET[
                    'dsm_publicidad_notice'
                ]
            )
        )
        : '';

$error =
    isset($_GET['dsm_publicidad_error'])
        ? sanitize_text_field(
            wp_unslash(
                (string) $_GET[
                    'dsm_publicidad_error'
                ]
            )
        )
        : '';
?>

<div class="wrap">

    <h1>DSM Publicidad</h1>

    <?php if ($notice === 'created') : ?>
        <div class="notice notice-success is-dismissible">
            <p>Banner creado correctamente.</p>
        </div>
    <?php elseif ($notice === 'updated') : ?>
        <div class="notice notice-success is-dismissible">
            <p>Banner actualizado correctamente.</p>
        </div>
    <?php elseif ($notice === 'deleted') : ?>
        <div class="notice notice-success is-dismissible">
            <p>Banner eliminado correctamente.</p>
        </div>
    <?php elseif ($notice === 'error') : ?>
        <div class="notice notice-error">
            <p>
                <?php echo esc_html(
                    $error
                ); ?>
            </p>
        </div>
    <?php endif; ?>

    <h2>
        <?php
        echo $editingBanner !== null
            ? 'Editar banner'
            : 'Nuevo banner';
        ?>
    </h2>

    <form
        method="post"
        action="<?php
        echo esc_url(
            admin_url(
                'admin-post.php'
            )
        );
        ?>"
    >

        <input
            type="hidden"
            name="action"
            value="dsm_publicidad_save_banner"
        >

        <input
            type="hidden"
            name="banner_id"
            value="<?php
            echo esc_attr(
                (string) $currentId
            );
            ?>"
        >

        <?php
        wp_nonce_field(
            'dsm_publicidad_save_banner',
            'dsm_publicidad_nonce'
        );
        ?>

        <table class="form-table">

            <tr>
                <th>
                    <label for="dsm-publicidad-title">
                        Título interno
                    </label>
                </th>

                <td>
                    <input
                        id="dsm-publicidad-title"
                        class="regular-text"
                        type="text"
                        name="title"
                        required
                        value="<?php
                        echo esc_attr(
                            $editingBanner !== null
                                ? $editingBanner
                                    ->getTitle()
                                : ''
                        );
                        ?>"
                    >
                </td>
            </tr>

            <tr>
                <th>Imagen</th>

                <td>
                    <input
                        id="dsm-publicidad-image-id"
                        type="hidden"
                        name="image_attachment_id"
                        value="<?php
                        echo esc_attr(
                            (string) $currentImageId
                        );
                        ?>"
                    >

                    <div
                        id="dsm-publicidad-image-preview"
                        style="margin-bottom:10px;"
                    >
                        <?php if (
                            is_string(
                                $currentImageUrl
                            )
                        ) : ?>

                            <img
                                src="<?php
                                echo esc_url(
                                    $currentImageUrl
                                );
                                ?>"
                                alt=""
                                style="
                                    max-width:320px;
                                    height:auto;
                                    display:block;
                                "
                            >

                        <?php endif; ?>
                    </div>

                    <button
                        type="button"
                        class="button"
                        id="dsm-publicidad-select-image"
                    >
                        Seleccionar imagen
                    </button>
                </td>
            </tr>

            <tr>
                <th>
                    <label for="dsm-publicidad-url">
                        URL destino
                    </label>
                </th>

                <td>
                    <input
                        id="dsm-publicidad-url"
                        class="regular-text"
                        type="url"
                        name="target_url"
                        required
                        value="<?php
                        echo esc_attr(
                            $editingBanner !== null
                                ? $editingBanner
                                    ->getTargetUrl()
                                : ''
                        );
                        ?>"
                    >
                </td>
            </tr>

            <tr>
                <th>
                    <label for="dsm-publicidad-area">
                        Isla
                    </label>
                </th>

                <td>
                    <select
                        id="dsm-publicidad-area"
                        name="area_id"
                    >
                        <option value="0">
                            Global / todas las islas
                        </option>

                        <?php foreach (
                            $areas
                            as $area
                        ) : ?>

                            <?php
                            if (!is_array($area)) {
                                continue;
                            }

                            $areaId =
                                (int) (
                                    $area['id']
                                    ?? 0
                                );

                            $areaName =
                                trim(
                                    (string) (
                                        $area['name']
                                        ?? ''
                                    )
                                );

                            if (
                                $areaId <= 0
                                || $areaName === ''
                            ) {
                                continue;
                            }
                            ?>

                            <option
                                value="<?php
                                echo esc_attr(
                                    (string) $areaId
                                );
                                ?>"
                                <?php
                                selected(
                                    $editingBanner !== null
                                        ? (
                                            $editingBanner
                                                ->getAreaId()
                                            ?? 0
                                        )
                                        : 0,
                                    $areaId
                                );
                                ?>
                            >
                                <?php
                                echo esc_html(
                                    $areaName
                                );
                                ?>
                            </option>

                        <?php endforeach; ?>
                    </select>
                </td>
            </tr>

            <tr>
                <th>
                    <label for="dsm-publicidad-priority">
                        Prioridad
                    </label>
                </th>

                <td>
                    <input
                        id="dsm-publicidad-priority"
                        type="number"
                        name="priority"
                        value="<?php
                        echo esc_attr(
                            (string) (
                                $editingBanner !== null
                                    ? $editingBanner
                                        ->getPriority()
                                    : 0
                            )
                        );
                        ?>"
                    >
                </td>
            </tr>

            <tr>
                <th>
                    <label for="dsm-publicidad-status">
                        Estado
                    </label>
                </th>

                <td>
                    <select
                        id="dsm-publicidad-status"
                        name="status"
                    >
                        <?php
                        $statuses = [
                            'draft' =>
                                'Borrador',

                            'active' =>
                                'Activo',

                            'inactive' =>
                                'Inactivo',
                        ];

                        foreach (
                            $statuses
                            as $value => $label
                        ) :
                            ?>

                            <option
                                value="<?php
                                echo esc_attr(
                                    $value
                                );
                                ?>"
                                <?php
                                selected(
                                    $editingBanner !== null
                                        ? $editingBanner
                                            ->getStatus()
                                        : 'draft',
                                    $value
                                );
                                ?>
                            >
                                <?php
                                echo esc_html(
                                    $label
                                );
                                ?>
                            </option>

                        <?php endforeach; ?>
                    </select>
                </td>
            </tr>

            <tr>
                <th>
                    <label for="dsm-publicidad-starts">
                        Inicio
                    </label>
                </th>

                <td>
                    <input
                        id="dsm-publicidad-starts"
                        type="datetime-local"
                        name="starts_at"
                        value="<?php
                        echo esc_attr(
                            $editingBanner !== null
                            && $editingBanner
                                ->getStartsAt() !== null
                                ? $editingBanner
                                    ->getStartsAt()
                                    ->format(
                                        'Y-m-d\TH:i'
                                    )
                                : ''
                        );
                        ?>"
                    >
                </td>
            </tr>

            <tr>
                <th>
                    <label for="dsm-publicidad-ends">
                        Fin
                    </label>
                </th>

                <td>
                    <input
                        id="dsm-publicidad-ends"
                        type="datetime-local"
                        name="ends_at"
                        value="<?php
                        echo esc_attr(
                            $editingBanner !== null
                            && $editingBanner
                                ->getEndsAt() !== null
                                ? $editingBanner
                                    ->getEndsAt()
                                    ->format(
                                        'Y-m-d\TH:i'
                                    )
                                : ''
                        );
                        ?>"
                    >
                </td>
            </tr>

        </table>

        <?php
        submit_button(
            $editingBanner !== null
                ? 'Guardar cambios'
                : 'Crear banner'
        );
        ?>

    </form>

    <hr>

    <h2>Banners existentes</h2>

    <table class="widefat striped">

        <thead>
            <tr>
                <th>ID</th>
                <th>Título</th>
                <th>Isla</th>
                <th>Estado</th>
                <th>Prioridad</th>
                <th>Acciones</th>
            </tr>
        </thead>

        <tbody>

            <?php if ($banners === []) : ?>

                <tr>
                    <td colspan="6">
                        No hay banners creados.
                    </td>
                </tr>

            <?php else : ?>

                <?php foreach (
                    $banners
                    as $banner
                ) : ?>

                    <?php
                    $areaLabel =
                        'Global';

                    foreach ($areas as $area) {
                        if (
                            !is_array($area)
                            || (int) (
                                $area['id']
                                ?? 0
                            ) !== (
                                $banner
                                    ->getAreaId()
                                ?? 0
                            )
                        ) {
                            continue;
                        }

                        $areaLabel =
                            (string) (
                                $area['name']
                                ?? 'Global'
                            );

                        break;
                    }
                    ?>

                    <tr>
                        <td>
                            <?php echo esc_html(
                                (string) $banner
                                    ->getId()
                            ); ?>
                        </td>

                        <td>
                            <?php echo esc_html(
                                $banner
                                    ->getTitle()
                            ); ?>
                        </td>

                        <td>
                            <?php echo esc_html(
                                $areaLabel
                            ); ?>
                        </td>

                        <td>
                            <?php echo esc_html(
                                $banner
                                    ->getStatus()
                            ); ?>
                        </td>

                        <td>
                            <?php echo esc_html(
                                (string) $banner
                                    ->getPriority()
                            ); ?>
                        </td>

                        <td>
                            <a
                                class="button button-small"
                                href="<?php
                                echo esc_url(
                                    add_query_arg(
                                        [
                                            'page' =>
                                                'dsm-publicidad',

                                            'banner_id' =>
                                                $banner
                                                    ->getId(),
                                        ],
                                        admin_url(
                                            'admin.php'
                                        )
                                    )
                                );
                                ?>"
                            >
                                Editar
                            </a>

                            <form
                                method="post"
                                action="<?php
                                echo esc_url(
                                    admin_url(
                                        'admin-post.php'
                                    )
                                );
                                ?>"
                                style="display:inline;"
                            >
                                <input
                                    type="hidden"
                                    name="action"
                                    value="dsm_publicidad_delete_banner"
                                >

                                <input
                                    type="hidden"
                                    name="banner_id"
                                    value="<?php
                                    echo esc_attr(
                                        (string) $banner
                                            ->getId()
                                    );
                                    ?>"
                                >

                                <?php
                                wp_nonce_field(
                                    'dsm_publicidad_delete_banner_'
                                    . $banner->getId(),
                                    'dsm_publicidad_nonce'
                                );
                                ?>

                                <button
                                    type="submit"
                                    class="button button-small"
                                    onclick="return confirm('¿Eliminar este banner?');"
                                >
                                    Eliminar
                                </button>
                            </form>
                        </td>
                    </tr>

                <?php endforeach; ?>

            <?php endif; ?>

        </tbody>

    </table>

</div>

<script>
document.addEventListener(
    'DOMContentLoaded',
    function () {
        const button =
            document.getElementById(
                'dsm-publicidad-select-image'
            );

        if (!button || !window.wp || !wp.media) {
            return;
        }

        button.addEventListener(
            'click',
            function () {
                const frame =
                    wp.media({
                        title:
                            'Seleccionar banner',

                        button: {
                            text:
                                'Usar esta imagen'
                        },

                        multiple:
                            false,

                        library: {
                            type:
                                'image'
                        }
                    });

                frame.on(
                    'select',
                    function () {
                        const attachment =
                            frame
                                .state()
                                .get('selection')
                                .first()
                                .toJSON();

                        document
                            .getElementById(
                                'dsm-publicidad-image-id'
                            )
                            .value =
                                attachment.id;

                        const preview =
                            document
                                .getElementById(
                                    'dsm-publicidad-image-preview'
                                );

                        preview.innerHTML =
                            '<img src="'
                            + attachment.url
                            + '" alt="" '
                            + 'style="max-width:320px;'
                            + 'height:auto;display:block;">';
                    }
                );

                frame.open();
            }
        );
    }
);
</script>
