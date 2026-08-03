<?php

declare(strict_types=1);

use DSM\Catalogo\Product\Product;
use DSM\Catalogo\Product\ProductStatus;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Variables heredadas de product-form.php:
 *
 * @var Product|null $product
 * @var bool $isEditing
 * @var int|null $storeId
 * @var int|null $customerId
 * @var string $saveButtonText
 */

$trackStock =
    $isEditing
        ? $product->tracksStock()
        : true;

$statusLabels = [
    ProductStatus::DRAFT =>
        __('Borrador', 'dsm-catalogo'),

    ProductStatus::ACTIVE =>
        __('Activo', 'dsm-catalogo'),

    ProductStatus::INACTIVE =>
        __('Inactivo', 'dsm-catalogo'),

    ProductStatus::ARCHIVED =>
        __('Archivado', 'dsm-catalogo'),
];

$currentStatus =
    $isEditing
        ? $product->getStatus()
        : ProductStatus::DRAFT;
?>

<div class="dsm-panel">
    <div class="dsm-panel-header">
        <h2>
            <?php
            echo esc_html__(
                'Configuración',
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
                    'Configuración de inventario',
                    'dsm-catalogo'
                );
                ?>
            </legend>

            <label>
                <input
                    type="checkbox"
                    name="track_stock"
                    value="1"
                    <?php
                    checked($trackStock);
                    ?>
                >

                <span>
                    <strong>
                        <?php
                        echo esc_html__(
                            'Controlar existencias',
                            'dsm-catalogo'
                        );
                        ?>
                    </strong>

                    <small>
                        <?php
                        echo esc_html__(
                            'Las unidades se administrarán desde las variantes y los movimientos de inventario.',
                            'dsm-catalogo'
                        );
                        ?>
                    </small>
                </span>
            </label>
        </fieldset>

        <hr>

        <dl class="dsm-meta-list">
            <div>
                <dt>
                    <?php
                    echo esc_html__(
                        'Tienda',
                        'dsm-catalogo'
                    );
                    ?>
                </dt>

                <dd>
                    <?php
                    echo esc_html(
                        $storeId !== null
                            ? (string) $storeId
                            : '—'
                    );
                    ?>
                </dd>
            </div>

            <div>
                <dt>
                    <?php
                    echo esc_html__(
                        'Cliente responsable',
                        'dsm-catalogo'
                    );
                    ?>
                </dt>

                <dd>
                    <?php
                    echo esc_html(
                        $customerId !== null
                            ? (string) $customerId
                            : '—'
                    );
                    ?>
                </dd>
            </div>

            <div>
                <dt>
                    <?php
                    echo esc_html__(
                        'Estado',
                        'dsm-catalogo'
                    );
                    ?>
                </dt>

                <dd>
                    <?php
                    echo esc_html(
                        $statusLabels[$currentStatus]
                        ?? $currentStatus
                    );
                    ?>
                </dd>
            </div>
        </dl>

        <?php if (!$isEditing): ?>
            <p class="description">
                <?php
                echo esc_html__(
                    'Los productos nuevos se crearán inicialmente como borradores.',
                    'dsm-catalogo'
                );
                ?>
            </p>
        <?php endif; ?>
    </div>

    <div class="dsm-panel-footer">
        <?php
        submit_button(
            $saveButtonText,
            'primary',
            'submit',
            false,
            [
                'disabled' =>
                    (
                        $storeId === null
                        || $customerId === null
                    ),
            ]
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
                            (string) $product->getId()
                        );
                        ?>
                    </dd>
                </div>

                <div>
                    <dt>
                        <?php
                        echo esc_html__(
                            'Slug',
                            'dsm-catalogo'
                        );
                        ?>
                    </dt>

                    <dd>
                        <code>
                            <?php
                            echo esc_html(
                                $product->getSlug()
                            );
                            ?>
                        </code>
                    </dd>
                </div>

                <div>
                    <dt>
                        <?php
                        echo esc_html__(
                            'Creado',
                            'dsm-catalogo'
                        );
                        ?>
                    </dt>

                    <dd>
                        <?php
                        echo esc_html(
                            $product
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
                            'Actualizado',
                            'dsm-catalogo'
                        );
                        ?>
                    </dt>

                    <dd>
                        <?php
                        echo esc_html(
                            $product
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
                            'Genera anuncios',
                            'dsm-catalogo'
                        );
                        ?>
                    </dt>

                    <dd>
                        <?php
                        echo esc_html(
                            $product->generatesAdvertisements()
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