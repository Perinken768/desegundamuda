<?php

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Plantilla de creación y edición de anuncios.
 *
 * Variables proporcionadas por AdvertisementFormShortcode:
 *
 * @var array<string, mixed>       $currentCustomer
 * @var array<string, mixed>|null  $advertisement
 * @var int                        $advertisementId
 * @var array<int, mixed>          $categories
 * @var array<string, mixed>       $locations
 * @var array<string, mixed>       $formConfiguration
 * @var array<string, mixed>       $publicationAvailability
 * @var bool                       $isEditing
 */

$currentCustomer =
    isset($currentCustomer)
    && is_array($currentCustomer)
        ? $currentCustomer
        : [];

$advertisement =
    isset($advertisement)
    && is_array($advertisement)
        ? $advertisement
        : null;

$advertisementId =
    isset($advertisementId)
        ? max(
            0,
            (int) $advertisementId
        )
        : 0;

$isEditing =
    isset($isEditing)
        ? (bool) $isEditing
        : $advertisementId > 0;

$advertisementStatus =
    $advertisement !== null
        ? sanitize_key(
            (string) (
                $advertisement[
                    'status'
                ]
                ?? ''
            )
        )
        : '';

$isActiveEditing =
    $isEditing
    && $advertisementStatus === 'active';

$categories =
    isset($categories)
    && is_array($categories)
        ? $categories
        : [];

$locations =
    isset($locations)
    && is_array($locations)
        ? $locations
        : [];

$formConfiguration =
    isset($formConfiguration)
    && is_array($formConfiguration)
        ? $formConfiguration
        : [];

$publicationAvailability =
    isset($publicationAvailability)
    && is_array($publicationAvailability)
        ? $publicationAvailability
        : [];

/*
 * Ubicaciones.
 */
$countries =
    isset($locations['countries'])
    && is_array(
        $locations['countries']
    )
        ? $locations['countries']
        : [];

$areas =
    isset($locations['areas'])
    && is_array(
        $locations['areas']
    )
        ? $locations['areas']
        : [];

$municipalities =
    isset($locations['municipalities'])
    && is_array(
        $locations['municipalities']
    )
        ? $locations['municipalities']
        : [];

/*
 * Configuración.
 */
$titleMaxLength =
    max(
        1,
        (int) (
            $formConfiguration[
                'title_max_length'
            ]
            ?? 180
        )
    );

$descriptionMaxLength =
    max(
        1,
        (int) (
            $formConfiguration[
                'description_max_length'
            ]
            ?? 10000
        )
    );

$minimumImages =
    max(
        0,
        (int) (
            $formConfiguration[
                'minimum_images'
            ]
            ?? 0
        )
    );

$maximumImages =
    max(
        $minimumImages,
        (int) (
            $formConfiguration[
                'maximum_images'
            ]
            ?? 10
        )
    );

$minimumPrice =
    isset(
        $formConfiguration[
            'minimum_price'
        ]
    )
    && is_numeric(
        $formConfiguration[
            'minimum_price'
        ]
    )
        ? max(
            0,
            (float) $formConfiguration[
                'minimum_price'
            ]
        )
        : 0.01;

$maximumPrice =
    isset(
        $formConfiguration[
            'maximum_price'
        ]
    )
    && $formConfiguration[
        'maximum_price'
    ] !== null
    && is_numeric(
        $formConfiguration[
            'maximum_price'
        ]
    )
        ? max(
            $minimumPrice,
            (float) $formConfiguration[
                'maximum_price'
            ]
        )
        : null;

$autoPublishEnabled =
    !empty(
        $formConfiguration[
            'auto_publish_enabled'
        ]
    );

/*
 * Valores actuales del anuncio.
 */
$categoryId =
    $advertisement !== null
        ? max(
            0,
            (int) (
                $advertisement[
                    'category_id'
                ]
                ?? 0
            )
        )
        : 0;

$customerCountryId =
    max(
        0,
        (int) (
            $currentCustomer[
                'country_id'
            ]
            ?? 0
        )
    );

$customerAreaId =
    max(
        0,
        (int) (
            $currentCustomer[
                'area_id'
            ]
            ?? 0
        )
    );

$customerMunicipalityId =
    max(
        0,
        (int) (
            $currentCustomer[
                'municipality_id'
            ]
            ?? 0
        )
    );

$advertisementAreaId =
    $advertisement !== null
        ? max(
            0,
            (int) (
                $advertisement[
                    'area_id'
                ]
                ?? 0
            )
        )
        : 0;

$advertisementMunicipalityId =
    $advertisement !== null
        ? max(
            0,
            (int) (
                $advertisement[
                    'municipality_id'
                ]
                ?? 0
            )
        )
        : 0;

/*
 * Los anuncios antiguos pueden no tener todavía
 * ubicación almacenada.
 *
 * En ese caso usamos como valor inicial la ubicación
 * actual configurada por el cliente.
 */
$countryId =
    $customerCountryId;

$areaId =
    $advertisementAreaId > 0
        ? $advertisementAreaId
        : $customerAreaId;

$municipalityId =
    $advertisementMunicipalityId > 0
        ? $advertisementMunicipalityId
        : $customerMunicipalityId;

$title =
    trim(
        (string) (
            $advertisement[
                'title'
            ]
            ?? ''
        )
    );

$description =
    trim(
        (string) (
            $advertisement[
                'description'
            ]
            ?? ''
        )
    );

$brand =
    trim(
        (string) (
            $advertisement[
                'brand'
            ]
            ?? ''
        )
    );

$price =
    isset($advertisement['price'])
    && is_numeric(
        $advertisement['price']
    )
        ? number_format(
            (float) $advertisement['price'],
            2,
            '.',
            ''
        )
        : '';

$originalPrice =
    isset(
        $advertisement[
            'original_price'
        ]
    )
    && $advertisement[
        'original_price'
    ] !== null
    && is_numeric(
        $advertisement[
            'original_price'
        ]
    )
        ? number_format(
            (float) $advertisement[
                'original_price'
            ],
            2,
            '.',
            ''
        )
        : '';

$purchaseDate =
    trim(
        (string) (
            $advertisement[
                'purchase_date'
            ]
            ?? ''
        )
    );

$conditionCode =
    sanitize_key(
        (string) (
            $advertisement[
                'condition_code'
            ]
            ?? ''
        )
    );

$existingImages =
    isset(
        $advertisement['images']
    )
    && is_array(
        $advertisement['images']
    )
        ? $advertisement['images']
        : [];

/*
 * URLs.
 */
$formAction =
    admin_url(
        'admin-post.php'
    );

$cancelUrl =
    (string) apply_filters(
        'dsm_customer_advertisements_url',
        home_url(
            '/mis-anuncios/'
        )
    );

/*
 * El controlador POST se implementará como siguiente pieza.
 *
 * La misma acción procesa creación y edición.
 */
$formActionName =
    'dsm_customer_advertisement_save';

$nonceAction =
    $formActionName
    . '_'
    . (
        $advertisementId > 0
            ? $advertisementId
            : 'new'
    );

$nonceField =
    'dsm_customer_advertisement_nonce';

/*
 * Estados de conservación.
 */
$conditions = [
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
];

/*
 * Mensajes después de redirecciones.
 */
$formStatus =
    isset($_GET['dsm_form_status'])
        ? sanitize_key(
            wp_unslash(
                (string) $_GET[
                    'dsm_form_status'
                ]
            )
        )
        : '';

$formError =
    isset($_GET['dsm_form_error'])
        ? sanitize_key(
            wp_unslash(
                (string) $_GET[
                    'dsm_form_error'
                ]
            )
        )
        : '';
?>

<section
    class="<?php echo esc_attr(
        'dsm-advertisement-form-page'
        . (
            $isEditing
                ? ' is-editing'
                : ' is-creating'
        )
    ); ?>"
>
    <header class="dsm-advertisement-form-header">
        <div>
            <h1>
                <?php
                if ($isEditing) {
                    esc_html_e(
                        'Editar anuncio',
                        'dsm-anuncios'
                    );
                } else {
                    esc_html_e(
                        'Publicar un anuncio',
                        'dsm-anuncios'
                    );
                }
                ?>
            </h1>

            <p>
                <?php
                if ($isEditing) {
                    esc_html_e(
                        'Actualiza los datos de tu anuncio antes de volver a enviarlo a revisión.',
                        'dsm-anuncios'
                    );
                } else {
                    esc_html_e(
                        'Añade la información de la prenda que quieres vender.',
                        'dsm-anuncios'
                    );
                }
                ?>
            </p>
        </div>
    </header>

    <?php if (
        $formStatus === 'saved'
    ) : ?>
        <div
            class="dsm-account-notice dsm-account-notice--success"
            role="status"
        >
            <?php
            esc_html_e(
                'El anuncio se guardó correctamente.',
                'dsm-anuncios'
            );
            ?>
        </div>
    <?php elseif (
        $formStatus === 'submitted'
    ) : ?>
        <div
            class="dsm-account-notice dsm-account-notice--success"
            role="status"
        >
            <?php
            esc_html_e(
                'El anuncio se guardó y se envió a revisión.',
                'dsm-anuncios'
            );
            ?>
        </div>
    <?php endif; ?>

    <?php if ($formError !== '') : ?>
        <div
            class="dsm-account-notice dsm-account-notice--error"
            role="alert"
        >
            <?php
            $errorMessage =
                match ($formError) {
                    'invalid_request' =>
                        __(
                            'La solicitud no es válida.',
                            'dsm-anuncios'
                        ),

                    'invalid_category' =>
                        __(
                            'Selecciona una categoría válida.',
                            'dsm-anuncios'
                        ),

                    'invalid_location' =>
                        __(
                            'Selecciona una ubicación válida.',
                            'dsm-anuncios'
                        ),

                    'invalid_price' =>
                        __(
                            'Indica un precio válido.',
                            'dsm-anuncios'
                        ),

                    'not_editable' =>
                        __(
                            'Este anuncio no puede editarse.',
                            'dsm-anuncios'
                        ),

                    'limit_reached' =>
                        __(
                            'Has alcanzado el límite de anuncios disponibles.',
                            'dsm-anuncios'
                        ),

                    default =>
                        __(
                            'No se pudo guardar el anuncio. Revisa los datos e inténtalo nuevamente.',
                            'dsm-anuncios'
                        ),
                };

            echo esc_html(
                $errorMessage
            );
            ?>
        </div>
    <?php endif; ?>

    <?php if (
        !$isEditing
        && isset(
            $publicationAvailability[
                'remaining'
            ]
        )
        && $publicationAvailability[
            'remaining'
        ] !== null
    ) : ?>
        <div class="dsm-advertisement-form-availability">
            <?php
            printf(
                esc_html__(
                    'Puedes abrir %d anuncios más con tu configuración actual.',
                    'dsm-anuncios'
                ),
                max(
                    0,
                    (int) $publicationAvailability[
                        'remaining'
                    ]
                )
            );
            ?>
        </div>
    <?php endif; ?>

    <form
        class="dsm-advertisement-form"
        method="post"
        action="<?php echo esc_url(
            $formAction
        ); ?>"
        enctype="multipart/form-data"
        data-dsm-advertisement-form
        novalidate
    >
        <input
            type="hidden"
            name="action"
            value="<?php echo esc_attr(
                $formActionName
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
            $nonceAction,
            $nonceField
        );
        ?>

        <section class="dsm-advertisement-form-section">
            <header class="dsm-advertisement-form-section__header">
                <span class="dsm-advertisement-form-section__number">
                    1
                </span>

                <div>
                    <h2>
                        <?php
                        esc_html_e(
                            '¿Qué estás vendiendo?',
                            'dsm-anuncios'
                        );
                        ?>
                    </h2>

                    <p>
                        <?php
                        esc_html_e(
                            'Describe la prenda de forma clara para que sea fácil encontrarla.',
                            'dsm-anuncios'
                        );
                        ?>
                    </p>
                </div>
            </header>

            <div class="dsm-advertisement-form-grid">
                <div class="dsm-form-field dsm-form-field--full">
                    <label for="dsm-advertisement-category">
                        <?php
                        esc_html_e(
                            'Categoría',
                            'dsm-anuncios'
                        );
                        ?>

                        <span aria-hidden="true">*</span>
                    </label>

                    <select
                        id="dsm-advertisement-category"
                        name="category_id"
                        required
                    >
                        <option value="">
                            <?php
                            esc_html_e(
                                'Selecciona una categoría',
                                'dsm-anuncios'
                            );
                            ?>
                        </option>

                        <?php foreach (
                            $categories
                            as $category
                        ) : ?>
                            <?php
                            if (!is_array($category)) {
                                continue;
                            }

                            $currentCategoryId =
                                max(
                                    0,
                                    (int) (
                                        $category['id']
                                        ?? 0
                                    )
                                );

                            $categoryName =
                                trim(
                                    (string) (
                                        $category['name']
                                        ?? ''
                                    )
                                );

                            if (
                                $currentCategoryId <= 0
                                || $categoryName === ''
                            ) {
                                continue;
                            }
                            ?>

                            <option
                                value="<?php echo esc_attr(
                                    (string) $currentCategoryId
                                ); ?>"
                                <?php selected(
                                    $categoryId,
                                    $currentCategoryId
                                ); ?>
                            >
                                <?php echo esc_html(
                                    $categoryName
                                ); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="dsm-form-field dsm-form-field--full">
                    <label for="dsm-advertisement-title">
                        <?php
                        esc_html_e(
                            'Título',
                            'dsm-anuncios'
                        );
                        ?>

                        <span aria-hidden="true">*</span>
                    </label>

                    <input
                        id="dsm-advertisement-title"
                        name="title"
                        type="text"
                        value="<?php echo esc_attr(
                            $title
                        ); ?>"
                        maxlength="<?php echo esc_attr(
                            (string) $titleMaxLength
                        ); ?>"
                        required
                        autocomplete="off"
                        placeholder="<?php echo esc_attr__(
                            'Ej. Vestido midi de Zara',
                            'dsm-anuncios'
                        ); ?>"
                    >

                    <small>
                        <?php
                        printf(
                            esc_html__(
                                'Máximo %d caracteres.',
                                'dsm-anuncios'
                            ),
                            $titleMaxLength
                        );
                        ?>
                    </small>
                </div>

                <div class="dsm-form-field dsm-form-field--full">
                    <label for="dsm-advertisement-description">
                        <?php
                        esc_html_e(
                            'Descripción',
                            'dsm-anuncios'
                        );
                        ?>

                        <span aria-hidden="true">*</span>
                    </label>

                    <textarea
                        id="dsm-advertisement-description"
                        name="description"
                        rows="8"
                        maxlength="<?php echo esc_attr(
                            (string) $descriptionMaxLength
                        ); ?>"
                        required
                        placeholder="<?php echo esc_attr__(
                            'Describe el estado, talla, detalles, medidas o cualquier información útil sobre la prenda.',
                            'dsm-anuncios'
                        ); ?>"
                    ><?php echo esc_textarea(
                        $description
                    ); ?></textarea>

                    <small>
                        <?php
                        printf(
                            esc_html__(
                                'Máximo %d caracteres.',
                                'dsm-anuncios'
                            ),
                            $descriptionMaxLength
                        );
                        ?>
                    </small>
                </div>

                <div class="dsm-form-field">
                    <label for="dsm-advertisement-brand">
                        <?php
                        esc_html_e(
                            'Marca',
                            'dsm-anuncios'
                        );
                        ?>
                    </label>

                    <input
                        id="dsm-advertisement-brand"
                        name="brand"
                        type="text"
                        value="<?php echo esc_attr(
                            $brand
                        ); ?>"
                        maxlength="180"
                        autocomplete="off"
                        placeholder="<?php echo esc_attr__(
                            'Ej. Zara',
                            'dsm-anuncios'
                        ); ?>"
                    >
                </div>

                <div class="dsm-form-field">
                    <label for="dsm-advertisement-condition">
                        <?php
                        esc_html_e(
                            'Estado de conservación',
                            'dsm-anuncios'
                        );
                        ?>

                        <span aria-hidden="true">*</span>
                    </label>

                    <select
                        id="dsm-advertisement-condition"
                        name="condition_code"
                        required
                    >
                        <option value="">
                            <?php
                            esc_html_e(
                                'Selecciona el estado',
                                'dsm-anuncios'
                            );
                            ?>
                        </option>

                        <?php foreach (
                            $conditions
                            as $code => $label
                        ) : ?>
                            <option
                                value="<?php echo esc_attr(
                                    $code
                                ); ?>"
                                <?php selected(
                                    $conditionCode,
                                    $code
                                ); ?>
                            >
                                <?php echo esc_html(
                                    $label
                                ); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
        </section>

        <section class="dsm-advertisement-form-section">
            <header class="dsm-advertisement-form-section__header">
                <span class="dsm-advertisement-form-section__number">
                    2
                </span>

                <div>
                    <h2>
                        <?php
                        esc_html_e(
                            'Precio',
                            'dsm-anuncios'
                        );
                        ?>
                    </h2>

                    <p>
                        <?php
                        esc_html_e(
                            'Indica cuánto quieres recibir por la prenda.',
                            'dsm-anuncios'
                        );
                        ?>
                    </p>
                </div>
            </header>

            <div class="dsm-advertisement-form-grid">
                <div class="dsm-form-field">
                    <label for="dsm-advertisement-price">
                        <?php
                        esc_html_e(
                            'Precio de venta',
                            'dsm-anuncios'
                        );
                        ?>

                        <span aria-hidden="true">*</span>
                    </label>

                    <div class="dsm-price-input">
                        <input
                            id="dsm-advertisement-price"
                            name="price"
                            type="number"
                            value="<?php echo esc_attr(
                                $price
                            ); ?>"
                            min="<?php echo esc_attr(
                                (string) $minimumPrice
                            ); ?>"
                            <?php if (
                                $maximumPrice !== null
                            ) : ?>
                                max="<?php echo esc_attr(
                                    (string) $maximumPrice
                                ); ?>"
                            <?php endif; ?>
                            step="0.01"
                            inputmode="decimal"
                            required
                        >

                        <span aria-hidden="true">
                            €
                        </span>
                    </div>
                </div>

                <div class="dsm-form-field">
                    <label for="dsm-advertisement-original-price">
                        <?php
                        esc_html_e(
                            'Precio original',
                            'dsm-anuncios'
                        );
                        ?>
                    </label>

                    <div class="dsm-price-input">
                        <input
                            id="dsm-advertisement-original-price"
                            name="original_price"
                            type="number"
                            value="<?php echo esc_attr(
                                $originalPrice
                            ); ?>"
                            min="0"
                            step="0.01"
                            inputmode="decimal"
                        >

                        <span aria-hidden="true">
                            €
                        </span>
                    </div>

                    <small>
                        <?php
                        esc_html_e(
                            'Opcional. Puede ayudar al comprador a valorar el precio.',
                            'dsm-anuncios'
                        );
                        ?>
                    </small>
                </div>

                <div class="dsm-form-field">
                    <label for="dsm-advertisement-purchase-date">
                        <?php
                        esc_html_e(
                            'Fecha de compra',
                            'dsm-anuncios'
                        );
                        ?>
                    </label>

                    <input
                        id="dsm-advertisement-purchase-date"
                        name="purchase_date"
                        type="date"
                        value="<?php echo esc_attr(
                            $purchaseDate
                        ); ?>"
                        max="<?php echo esc_attr(
                            current_time(
                                'Y-m-d'
                            )
                        ); ?>"
                    >

                    <small>
                        <?php
                        esc_html_e(
                            'Opcional.',
                            'dsm-anuncios'
                        );
                        ?>
                    </small>
                </div>
            </div>
        </section>

        <section class="dsm-advertisement-form-section">
            <header class="dsm-advertisement-form-section__header">
                <span class="dsm-advertisement-form-section__number">
                    3
                </span>

                <div>
                    <h2>
                        <?php
                        esc_html_e(
                            'Ubicación',
                            'dsm-anuncios'
                        );
                        ?>
                    </h2>

                    <p>
                        <?php
                        esc_html_e(
                            'Indica dónde se encuentra la prenda.',
                            'dsm-anuncios'
                        );
                        ?>
                    </p>
                </div>
            </header>

            <div
                class="dsm-advertisement-form-grid"
                data-dsm-advertisement-location
            >
                <?php if ($countries !== []) : ?>
                    <div class="dsm-form-field">
                        <label for="dsm-advertisement-country">
                            <?php
                            esc_html_e(
                                'País',
                                'dsm-anuncios'
                            );
                            ?>
                        </label>

                        <select
                            id="dsm-advertisement-country"
                            name="country_id"
                            data-dsm-country
                        >
                            <option value="0">
                                <?php
                                esc_html_e(
                                    'Selecciona un país',
                                    'dsm-anuncios'
                                );
                                ?>
                            </option>

                            <?php foreach (
                                $countries
                                as $country
                            ) : ?>
                                <?php
                                if (!is_array($country)) {
                                    continue;
                                }

                                $currentCountryId =
                                    max(
                                        0,
                                        (int) (
                                            $country['id']
                                            ?? 0
                                        )
                                    );

                                if (
                                    $currentCountryId
                                    <= 0
                                ) {
                                    continue;
                                }
                                ?>

                                <option
                                    value="<?php echo esc_attr(
                                        (string) $currentCountryId
                                    ); ?>"
                                    <?php selected(
                                        $countryId,
                                        $currentCountryId
                                    ); ?>
                                >
                                    <?php echo esc_html(
                                        (string) (
                                            $country['name']
                                            ?? ''
                                        )
                                    ); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                <?php endif; ?>

                <div class="dsm-form-field">
                    <label for="dsm-advertisement-area">
                        <?php
                        esc_html_e(
                            'Área',
                            'dsm-anuncios'
                        );
                        ?>

                        <span aria-hidden="true">*</span>
                    </label>

                    <select
                        id="dsm-advertisement-area"
                        name="area_id"
                        data-dsm-area
                        required
                    >
                        <option value="">
                            <?php
                            esc_html_e(
                                'Selecciona un área',
                                'dsm-anuncios'
                            );
                            ?>
                        </option>

                        <?php foreach (
                            $areas
                            as $area
                        ) : ?>
                            <?php
                            if (!is_array($area)) {
                                continue;
                            }

                            $currentAreaId =
                                max(
                                    0,
                                    (int) (
                                        $area['id']
                                        ?? 0
                                    )
                                );

                            $areaCountryId =
                                max(
                                    0,
                                    (int) (
                                        $area[
                                            'country_id'
                                        ]
                                        ?? 0
                                    )
                                );

                            if (
                                $currentAreaId <= 0
                            ) {
                                continue;
                            }
                            ?>

                            <option
                                value="<?php echo esc_attr(
                                    (string) $currentAreaId
                                ); ?>"
                                data-country-id="<?php echo esc_attr(
                                    (string) $areaCountryId
                                ); ?>"
                                data-area-type="<?php echo esc_attr(
                                    sanitize_key(
                                        (string) (
                                            $area[
                                                'area_type'
                                            ]
                                            ?? ''
                                        )
                                    )
                                ); ?>"
                                <?php selected(
                                    $areaId,
                                    $currentAreaId
                                ); ?>
                            >
                                <?php echo esc_html(
                                    (string) (
                                        $area['name']
                                        ?? ''
                                    )
                                ); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="dsm-form-field">
                    <label for="dsm-advertisement-municipality">
                        <?php
                        esc_html_e(
                            'Municipio',
                            'dsm-anuncios'
                        );
                        ?>

                        <span aria-hidden="true">*</span>
                    </label>

                    <select
                        id="dsm-advertisement-municipality"
                        name="municipality_id"
                        data-dsm-municipality
                        required
                    >
                        <option value="">
                            <?php
                            esc_html_e(
                                'Selecciona un municipio',
                                'dsm-anuncios'
                            );
                            ?>
                        </option>

                        <?php foreach (
                            $municipalities
                            as $municipality
                        ) : ?>
                            <?php
                            if (
                                !is_array(
                                    $municipality
                                )
                            ) {
                                continue;
                            }

                            $currentMunicipalityId =
                                max(
                                    0,
                                    (int) (
                                        $municipality['id']
                                        ?? 0
                                    )
                                );

                            $municipalityAreaId =
                                max(
                                    0,
                                    (int) (
                                        $municipality[
                                            'area_id'
                                        ]
                                        ?? 0
                                    )
                                );

                            if (
                                $currentMunicipalityId
                                    <= 0
                                || $municipalityAreaId
                                    <= 0
                            ) {
                                continue;
                            }
                            ?>

                            <option
                                value="<?php echo esc_attr(
                                    (string) $currentMunicipalityId
                                ); ?>"
                                data-area-id="<?php echo esc_attr(
                                    (string) $municipalityAreaId
                                ); ?>"
                                <?php selected(
                                    $municipalityId,
                                    $currentMunicipalityId
                                ); ?>
                            >
                                <?php echo esc_html(
                                    (string) (
                                        $municipality[
                                            'name'
                                        ]
                                        ?? ''
                                    )
                                ); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <p class="dsm-form-help">
                <?php
                esc_html_e(
                    'La ubicación se utilizará para mostrar tu anuncio a compradores cercanos.',
                    'dsm-anuncios'
                );
                ?>
            </p>
        </section>

        <section class="dsm-advertisement-form-section">
            <header class="dsm-advertisement-form-section__header">
                <span class="dsm-advertisement-form-section__number">
                    4
                </span>

                <div>
                    <h2>
                        <?php
                        esc_html_e(
                            'Imágenes',
                            'dsm-anuncios'
                        );
                        ?>
                    </h2>

                    <p>
                        <?php
                        printf(
                            esc_html__(
                                'Puedes añadir hasta %d imágenes.',
                                'dsm-anuncios'
                            ),
                            $maximumImages
                        );
                        ?>
                    </p>
                </div>
            </header>

            <?php if (
                $existingImages !== []
            ) : ?>
                <div
                    class="dsm-advertisement-form-images"
                    data-dsm-existing-images
                >
                    <?php foreach (
                        $existingImages
                        as $image
                    ) : ?>
                        <?php
                        if (!is_array($image)) {
                            continue;
                        }

                        $imageId =
                            max(
                                0,
                                (int) (
                                    $image['id']
                                    ?? 0
                                )
                            );

                        $attachmentId =
                            max(
                                0,
                                (int) (
                                    $image[
                                        'attachment_id'
                                    ]
                                    ?? 0
                                )
                            );

                        $imageUrl =
                            trim(
                                (string) (
                                    $image[
                                        'medium_url'
                                    ]
                                    ?? $image[
                                        'full_url'
                                    ]
                                    ?? ''
                                )
                            );

                        if (
                            $imageId <= 0
                            || $attachmentId <= 0
                        ) {
                            continue;
                        }
                        ?>

                        <article
                            class="dsm-advertisement-form-image"
                            data-dsm-existing-image
                            data-image-id="<?php echo esc_attr(
                                (string) $imageId
                            ); ?>"
                        >
                            <?php if (
                                $imageUrl !== ''
                            ) : ?>
                                <img
                                    src="<?php echo esc_url(
                                        $imageUrl
                                    ); ?>"
                                    alt=""
                                >
                            <?php endif; ?>

                            <input
                                type="hidden"
                                name="existing_image_ids[]"
                                value="<?php echo esc_attr(
                                    (string) $imageId
                                ); ?>"
                            >

                            <label>
                                <input
                                    type="radio"
                                    name="cover_image_id"
                                    value="<?php echo esc_attr(
                                        (string) $imageId
                                    ); ?>"
                                    <?php checked(
                                        !empty(
                                            $image[
                                                'is_cover'
                                            ]
                                        )
                                    ); ?>
                                >

                                <?php
                                esc_html_e(
                                    'Portada',
                                    'dsm-anuncios'
                                );
                                ?>
                            </label>

                            <label>
                                <input
                                    type="checkbox"
                                    name="remove_image_ids[]"
                                    value="<?php echo esc_attr(
                                        (string) $imageId
                                    ); ?>"
                                >

                                <?php
                                esc_html_e(
                                    'Eliminar',
                                    'dsm-anuncios'
                                );
                                ?>
                            </label>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <div class="dsm-form-field dsm-form-field--full">
                <label for="dsm-advertisement-images">
                    <?php
                    esc_html_e(
                        'Añadir imágenes',
                        'dsm-anuncios'
                    );
                    ?>

                    <?php if (
                        $minimumImages > 0
                    ) : ?>
                        <span aria-hidden="true">*</span>
                    <?php endif; ?>
                </label>

                <input
                    id="dsm-advertisement-images"
                    name="advertisement_images[]"
                    type="file"
                    accept="image/jpeg,image/png,image/webp"
                    multiple
                    data-dsm-advertisement-images
                    <?php if (
                        !$isEditing
                        && $minimumImages > 0
                    ) : ?>
                        required
                    <?php endif; ?>
                >

                <small>
                    <?php
                    printf(
                        esc_html__(
                            'Formatos admitidos: JPG, PNG y WebP. Máximo %d imágenes.',
                            'dsm-anuncios'
                        ),
                        $maximumImages
                    );
                    ?>
                </small>

                <div
                    class="dsm-advertisement-image-preview"
                    data-dsm-image-preview
                    aria-live="polite"
                ></div>
            </div>
        </section>

        <section class="dsm-advertisement-form-section">
            <header class="dsm-advertisement-form-section__header">
                <span class="dsm-advertisement-form-section__number">
                    5
                </span>

                <div>
                    <h2>
                        <?php
                        esc_html_e(
                            'Revisa y guarda',
                            'dsm-anuncios'
                        );
                        ?>
                    </h2>

                    <p>
                        <?php
                        if ($isActiveEditing) {
                            esc_html_e(
                                'Al modificar un anuncio publicado, los cambios deberán revisarse antes de volver a publicarse.',
                                'dsm-anuncios'
                            );
                        } elseif ($isEditing) {
                            esc_html_e(
                                'Puedes guardar los cambios o volver a enviar el anuncio a revisión.',
                                'dsm-anuncios'
                            );
                        } else {
                            esc_html_e(
                                'Puedes guardar el anuncio como borrador o enviarlo directamente a revisión.',
                                'dsm-anuncios'
                            );
                        }
                        ?>
                    </p>
                </div>
            </header>

            <div class="dsm-advertisement-form-summary">
                <p>
                    <strong>
                        <?php
                        esc_html_e(
                            'Tu contacto:',
                            'dsm-anuncios'
                        );
                        ?>
                    </strong>

                    <?php echo esc_html(
                        (string) (
                            $currentCustomer[
                                'phone'
                            ]
                            ?? ''
                        )
                    ); ?>
                </p>

                <ul>
                    <?php if (
                        !empty(
                            $currentCustomer[
                                'allow_phone_calls'
                            ]
                        )
                    ) : ?>
                        <li>
                            <?php
                            esc_html_e(
                                'Aceptas llamadas telefónicas.',
                                'dsm-anuncios'
                            );
                            ?>
                        </li>
                    <?php endif; ?>

                    <?php if (
                        !empty(
                            $currentCustomer[
                                'allow_whatsapp'
                            ]
                        )
                    ) : ?>
                        <li>
                            <?php
                            esc_html_e(
                                'Aceptas contacto por WhatsApp.',
                                'dsm-anuncios'
                            );
                            ?>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>

            <div class="dsm-advertisement-form-actions">
                <a
                    class="dsm-button dsm-button--secondary"
                    href="<?php echo esc_url(
                        $cancelUrl
                    ); ?>"
                >
                    <?php
                    esc_html_e(
                        'Cancelar',
                        'dsm-anuncios'
                    );
                    ?>
                </a>

                <?php if (!$isActiveEditing) : ?>
                    <button
                        class="dsm-button dsm-button--secondary"
                        type="submit"
                        name="submit_intent"
                        value="draft"
                    >
                        <?php
                        if ($isEditing) {
                            esc_html_e(
                                'Guardar cambios',
                                'dsm-anuncios'
                            );
                        } else {
                            esc_html_e(
                                'Guardar borrador',
                                'dsm-anuncios'
                            );
                        }
                        ?>
                    </button>
                <?php endif; ?>

                <button
                    class="dsm-button dsm-button--primary"
                    type="submit"
                    name="submit_intent"
                    value="review"
                >
                    <?php
                    if ($isActiveEditing) {
                        esc_html_e(
                            'Guardar cambios y enviar a revisión',
                            'dsm-anuncios'
                        );
                    } elseif ($autoPublishEnabled) {
                        esc_html_e(
                            'Guardar y publicar',
                            'dsm-anuncios'
                        );
                    } else {
                        esc_html_e(
                            'Guardar y enviar a revisión',
                            'dsm-anuncios'
                        );
                    }
                    ?>
                </button>
            </div>
        </section>
    </form>
</section>