<?php

declare(strict_types=1);

use DSM\Anuncios\Advertisement\AdvertisementStatus;
use DSM\Anuncios\Frontend\CustomerAdvertisementActionController;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Variables proporcionadas por CustomerAdvertisementsShortcode:
 *
 * @var array<string, mixed>              $customerContext
 * @var int                               $customerId
 * @var array<int, array<string, mixed>>  $advertisements
 * @var string                            $selectedStatus
 * @var array<int, string>                $statuses
 * @var array<string, int>                $statusCounts
 * @var string                            $createAdvertisementUrl
 * @var string                            $customerAdvertisementsUrl
 * @var bool                              $hasAdvertisements
 * @var bool                              $hasFilteredAdvertisements
 */

$advertisements =
    isset($advertisements)
    && is_array($advertisements)
        ? $advertisements
        : [];

$statuses =
    isset($statuses)
    && is_array($statuses)
        ? $statuses
        : [];

$statusCounts =
    isset($statusCounts)
    && is_array($statusCounts)
        ? $statusCounts
        : [];

$customerId =
    isset($customerId)
        ? max(
            0,
            (int) $customerId
        )
        : 0;

$selectedStatus =
    isset($selectedStatus)
        ? sanitize_key(
            (string) $selectedStatus
        )
        : '';

$createAdvertisementUrl =
    isset($createAdvertisementUrl)
        ? (string) $createAdvertisementUrl
        : home_url(
            '/publicar-anuncio/'
        );

$customerAdvertisementsUrl =
    isset($customerAdvertisementsUrl)
        ? (string) $customerAdvertisementsUrl
        : home_url(
            '/mis-anuncios/'
        );

$hasAdvertisements =
    isset($hasAdvertisements)
        ? (bool) $hasAdvertisements
        : false;

$hasFilteredAdvertisements =
    isset($hasFilteredAdvertisements)
        ? (bool) $hasFilteredAdvertisements
        : false;

/*
 * Resultado de una acción sobre un anuncio.
 */
$actionStatus =
    isset(
        $_GET[
            'dsm_ad_action_status'
        ]
    )
        ? sanitize_key(
            wp_unslash(
                (string) $_GET[
                    'dsm_ad_action_status'
                ]
            )
        )
        : '';

$lastActionError =
    CustomerAdvertisementActionController::
        getLastError(
            $customerId
        );

/**
 * Etiqueta pública de un estado.
 */
$getStatusLabel =
    static function (
        string $status
    ): string {
        return match ($status) {
            AdvertisementStatus::DRAFT =>
                __(
                    'Borrador',
                    'dsm-anuncios'
                ),

            AdvertisementStatus::PENDING =>
                __(
                    'Pendiente de revisión',
                    'dsm-anuncios'
                ),

            AdvertisementStatus::ACTIVE =>
                __(
                    'Activo',
                    'dsm-anuncios'
                ),

            AdvertisementStatus::RESERVED =>
                __(
                    'Reservado',
                    'dsm-anuncios'
                ),

            AdvertisementStatus::CLOSED =>
                __(
                    'Cerrado',
                    'dsm-anuncios'
                ),

            AdvertisementStatus::REJECTED =>
                __(
                    'Rechazado',
                    'dsm-anuncios'
                ),

            default =>
                $status !== ''
                    ? $status
                    : __(
                        'Sin estado',
                        'dsm-anuncios'
                    ),
        };
    };

/**
 * Clase visual de un estado.
 */
$getStatusClass =
    static function (
        string $status
    ): string {
        return match ($status) {
            AdvertisementStatus::ACTIVE =>
                'success',

            AdvertisementStatus::PENDING,
            AdvertisementStatus::RESERVED =>
                'warning',

            AdvertisementStatus::REJECTED =>
                'danger',

            AdvertisementStatus::CLOSED =>
                'muted',

            default =>
                'neutral',
        };
    };

/**
 * Convierte una fecha UTC a la zona horaria de WordPress.
 */
$formatDate =
    static function (
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
    };

/**
 * Genera la URL del filtro por estado.
 */
$getStatusUrl =
    static function (
        string $status
    ) use (
        $customerAdvertisementsUrl
    ): string {
        if ($status === '') {
            return remove_query_arg(
                'dsm_ad_status',
                $customerAdvertisementsUrl
            );
        }

        return add_query_arg(
            [
                'dsm_ad_status' =>
                    $status,
            ],
            $customerAdvertisementsUrl
        );
    };

/**
 * Renderiza un formulario POST para una acción
 * sobre un anuncio.
 */
$renderActionForm =
    static function (
        int $advertisementId,
        string $action,
        string $label,
        string $buttonClass = 'dsm-button--secondary',
        string $confirmation = ''
    ): void {
        ?>
        <form
            method="post"
            action="<?php echo esc_url(
                admin_url(
                    'admin-post.php'
                )
            ); ?>"
            class="dsm-customer-advertisement-action-form"
            <?php if ($confirmation !== '') : ?>
                onsubmit="return window.confirm('<?php
                    echo esc_js(
                        $confirmation
                    );
                ?>');"
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
                CustomerAdvertisementActionController::
                    getNonceAction(
                        $action,
                        $advertisementId
                    ),
                CustomerAdvertisementActionController::
                    NONCE_FIELD
            );
            ?>

            <button
                type="submit"
                class="<?php echo esc_attr(
                    trim(
                        'dsm-button '
                        . $buttonClass
                    )
                ); ?>"
            >
                <?php echo esc_html(
                    $label
                ); ?>
            </button>
        </form>
        <?php
    };
?>

<section class="dsm-customer-advertisements">
    <header class="dsm-account-section-header">
        <div class="dsm-account-section-header__content">
            <h1>
                <?php
                esc_html_e(
                    'Mis anuncios',
                    'dsm-anuncios'
                );
                ?>
            </h1>

            <p>
                <?php
                esc_html_e(
                    'Consulta y gestiona los anuncios que has publicado en DeSegundaMuda.',
                    'dsm-anuncios'
                );
                ?>
            </p>
        </div>

        <div class="dsm-account-section-header__actions">
            <a
                class="dsm-button dsm-button--primary"
                href="<?php echo esc_url(
                    $createAdvertisementUrl
                ); ?>"
            >
                <?php
                esc_html_e(
                    'Publicar un anuncio',
                    'dsm-anuncios'
                );
                ?>
            </a>
            <?php
            do_action(
                'dsm_customer_advertisements_header_actions',
                $customerId
            );
            ?>

        </div>
    </header>

    <?php if (
        $actionStatus === 'submitted'
    ) : ?>
        <div
            class="dsm-account-notice dsm-account-notice--success"
            role="status"
        >
            <?php
            esc_html_e(
                'El anuncio se envió a revisión correctamente.',
                'dsm-anuncios'
            );
            ?>
        </div>

    <?php elseif (
        $actionStatus === 'reserved'
    ) : ?>
        <div
            class="dsm-account-notice dsm-account-notice--success"
            role="status"
        >
            <?php
            esc_html_e(
                'El anuncio se marcó como reservado.',
                'dsm-anuncios'
            );
            ?>
        </div>

    <?php elseif (
        $actionStatus === 'released'
    ) : ?>
        <div
            class="dsm-account-notice dsm-account-notice--success"
            role="status"
        >
            <?php
            esc_html_e(
                'La reserva se liberó correctamente.',
                'dsm-anuncios'
            );
            ?>
        </div>

    <?php elseif (
        $actionStatus === 'closed'
    ) : ?>
        <div
            class="dsm-account-notice dsm-account-notice--success"
            role="status"
        >
            <?php
            esc_html_e(
                'El anuncio se cerró correctamente.',
                'dsm-anuncios'
            );
            ?>
        </div>

    <?php elseif (
        $actionStatus === 'deleted'
    ) : ?>
        <div
            class="dsm-account-notice dsm-account-notice--success"
            role="status"
        >
            <?php
            esc_html_e(
                'El anuncio se eliminó definitivamente.',
                'dsm-anuncios'
            );
            ?>
        </div>

    <?php elseif (
        $actionStatus === 'error'
    ) : ?>
        <div
            class="dsm-account-notice dsm-account-notice--error"
            role="alert"
        >
            <?php
            echo esc_html(
                $lastActionError !== ''
                    ? $lastActionError
                    : __(
                        'No se pudo completar la acción.',
                        'dsm-anuncios'
                    )
            );
            ?>
        </div>
    <?php endif; ?>

    <?php if (!$hasAdvertisements) : ?>
        <section class="dsm-account-empty-state">
            <span
                class="dashicons dashicons-megaphone"
                aria-hidden="true"
            ></span>

            <h2>
                <?php
                esc_html_e(
                    'Todavía no tienes anuncios',
                    'dsm-anuncios'
                );
                ?>
            </h2>

            <p>
                <?php
                esc_html_e(
                    'Crea tu primer anuncio para empezar a vender ropa que ya no utilizas.',
                    'dsm-anuncios'
                );
                ?>
            </p>

            <a
                class="dsm-button dsm-button--primary"
                href="<?php echo esc_url(
                    $createAdvertisementUrl
                ); ?>"
            >
                <?php
                esc_html_e(
                    'Crear mi primer anuncio',
                    'dsm-anuncios'
                );
                ?>
            </a>
        </section>

        <?php return; ?>
    <?php endif; ?>

    <nav
        class="dsm-customer-advertisements__filters"
        aria-label="<?php echo esc_attr__(
            'Filtrar mis anuncios',
            'dsm-anuncios'
        ); ?>"
    >
        <?php
        $allCount =
            array_sum(
                array_map(
                    'intval',
                    $statusCounts
                )
            );
        ?>

        <a
            class="<?php echo esc_attr(
                'dsm-account-filter'
                . (
                    $selectedStatus === ''
                        ? ' is-active'
                        : ''
                )
            ); ?>"
            href="<?php echo esc_url(
                $getStatusUrl('')
            ); ?>"
        >
            <span>
                <?php
                esc_html_e(
                    'Todos',
                    'dsm-anuncios'
                );
                ?>
            </span>

            <strong>
                <?php echo esc_html(
                    number_format_i18n(
                        $allCount
                    )
                ); ?>
            </strong>
        </a>

        <?php foreach (
            $statuses
            as $status
        ) : ?>
            <?php
            $status =
                sanitize_key(
                    (string) $status
                );

            if (
                !AdvertisementStatus::isValid(
                    $status
                )
            ) {
                continue;
            }
            ?>

            <a
                class="<?php echo esc_attr(
                    'dsm-account-filter'
                    . (
                        $selectedStatus === $status
                            ? ' is-active'
                            : ''
                    )
                ); ?>"
                href="<?php echo esc_url(
                    $getStatusUrl(
                        $status
                    )
                ); ?>"
            >
                <span>
                    <?php echo esc_html(
                        $getStatusLabel(
                            $status
                        )
                    ); ?>
                </span>

                <strong>
                    <?php echo esc_html(
                        number_format_i18n(
                            (int) (
                                $statusCounts[
                                    $status
                                ]
                                ?? 0
                            )
                        )
                    ); ?>
                </strong>
            </a>
        <?php endforeach; ?>
    </nav>

    <?php if (
        !$hasFilteredAdvertisements
    ) : ?>
        <section class="dsm-account-empty-state">
            <span
                class="dashicons dashicons-filter"
                aria-hidden="true"
            ></span>

            <h2>
                <?php
                esc_html_e(
                    'No hay anuncios con este estado',
                    'dsm-anuncios'
                );
                ?>
            </h2>

            <p>
                <?php
                esc_html_e(
                    'Selecciona otro estado para consultar el resto de tus anuncios.',
                    'dsm-anuncios'
                );
                ?>
            </p>

            <a
                class="dsm-button dsm-button--secondary"
                href="<?php echo esc_url(
                    $getStatusUrl('')
                ); ?>"
            >
                <?php
                esc_html_e(
                    'Ver todos mis anuncios',
                    'dsm-anuncios'
                );
                ?>
            </a>
        </section>

        <?php return; ?>
    <?php endif; ?>

    <div class="dsm-customer-advertisements__grid">
        <?php foreach (
            $advertisements
            as $advertisement
        ) : ?>
            <?php
            if (!is_array($advertisement)) {
                continue;
            }

            $advertisementId =
                max(
                    0,
                    (int) (
                        $advertisement['id']
                        ?? 0
                    )
                );

            if ($advertisementId <= 0) {
                continue;
            }

            $title =
                trim(
                    (string) (
                        $advertisement['title']
                        ?? ''
                    )
                );

            $status =
                sanitize_key(
                    (string) (
                        $advertisement['status']
                        ?? ''
                    )
                );

            $statusLabel =
                $getStatusLabel(
                    $status
                );

            $statusClass =
                $getStatusClass(
                    $status
                );

            $coverUrl =
                trim(
                    (string) (
                        $advertisement[
                            'cover_url'
                        ]
                        ?? ''
                    )
                );

            $brand =
                trim(
                    (string) (
                        $advertisement['brand']
                        ?? ''
                    )
                );

            $publicUrl =
                trim(
                    (string) (
                        $advertisement[
                            'public_url'
                        ]
                        ?? ''
                    )
                );

            $editUrl =
                trim(
                    (string) (
                        $advertisement[
                            'edit_url'
                        ]
                        ?? ''
                    )
                );

            $isPublic =
                !empty(
                    $advertisement[
                        'is_public'
                    ]
                );

            $canEdit =
                !empty(
                    $advertisement[
                        'can_edit'
                    ]
                );

            $canSubmit =
                !empty(
                    $advertisement[
                        'can_submit'
                    ]
                );

            $canReserve =
                !empty(
                    $advertisement[
                        'can_reserve'
                    ]
                );

            $canRelease =
                !empty(
                    $advertisement[
                        'can_release'
                    ]
                );

            $canClose =
                !empty(
                    $advertisement[
                        'can_close'
                    ]
                );

            $canDelete =
                !empty(
                    $advertisement[
                        'can_delete'
                    ]
                );

            $rejectionReason =
                trim(
                    (string) (
                        $advertisement[
                            'rejection_reason'
                        ]
                        ?? ''
                    )
                );

            $price =
                (float) (
                    $advertisement['price']
                    ?? 0
                );

            $createdAt =
                $formatDate(
                    $advertisement[
                        'created_at'
                    ]
                    ?? null
                );

            $updatedAt =
                $formatDate(
                    $advertisement[
                        'updated_at'
                    ]
                    ?? null
                );

            $hasExtensionActions =
    (bool) apply_filters(
        'dsm_customer_advertisement_has_extension_actions',
        false,
        $advertisement,
        $customerId
    );

            $hasActions =
                (
                    $isPublic
                    && $publicUrl !== ''
                )
                || (
                    $canEdit
                    && $editUrl !== ''
                )
                || $canSubmit
                || $canReserve
                || $canRelease
                || $canClose
                || $canDelete
                || $hasExtensionActions;
            ?>

            <article
                class="<?php echo esc_attr(
                    'dsm-customer-advertisement-card '
                    . 'dsm-customer-advertisement-card--'
                    . $statusClass
                ); ?>"
                data-advertisement-id="<?php echo esc_attr(
                    (string) $advertisementId
                ); ?>"
            >
                <div class="dsm-customer-advertisement-card__image">
                    <?php if (
                        $coverUrl !== ''
                    ) : ?>
                        <img
                            src="<?php echo esc_url(
                                $coverUrl
                            ); ?>"
                            alt="<?php echo esc_attr(
                                $title
                            ); ?>"
                            loading="lazy"
                        >
                    <?php else : ?>
                        <span
                            class="dsm-customer-advertisement-card__placeholder"
                            aria-hidden="true"
                        >
                            <span
                                class="dashicons dashicons-format-image"
                            ></span>
                        </span>
                    <?php endif; ?>

                    <span
                        class="<?php echo esc_attr(
                            'dsm-customer-advertisement-card__status '
                            . 'dsm-customer-advertisement-card__status--'
                            . $statusClass
                        ); ?>"
                    >
                        <?php echo esc_html(
                            $statusLabel
                        ); ?>
                    </span>
                </div>

                <div class="dsm-customer-advertisement-card__content">
                    <header>
                        <h2>
                            <?php
                            echo esc_html(
                                $title !== ''
                                    ? $title
                                    : sprintf(
                                        __(
                                            'Anuncio #%d',
                                            'dsm-anuncios'
                                        ),
                                        $advertisementId
                                    )
                            );
                            ?>
                        </h2>

                        <?php if (
                            $brand !== ''
                        ) : ?>
                            <p class="dsm-customer-advertisement-card__brand">
                                <?php echo esc_html(
                                    $brand
                                ); ?>
                            </p>
                        <?php endif; ?>
                    </header>

                    <p class="dsm-customer-advertisement-card__price">
                        <?php
                        echo esc_html(
                            number_format_i18n(
                                $price,
                                2
                            )
                            . ' €'
                        );
                        ?>
                    </p>

                    <?php if (
                        $status
                        === AdvertisementStatus::PENDING
                    ) : ?>
                        <div class="dsm-account-notice dsm-account-notice--info">
                            <?php
                            esc_html_e(
                                'Tu anuncio está pendiente de revisión.',
                                'dsm-anuncios'
                            );
                            ?>
                        </div>
                    <?php endif; ?>

                    <?php if (
                        $status
                        === AdvertisementStatus::RESERVED
                    ) : ?>
                        <div class="dsm-account-notice dsm-account-notice--warning">
                            <?php
                            esc_html_e(
                                'Este anuncio está marcado como reservado.',
                                'dsm-anuncios'
                            );
                            ?>
                        </div>
                    <?php endif; ?>

                    <?php if (
                        $status
                        === AdvertisementStatus::CLOSED
                    ) : ?>
                        <div class="dsm-account-notice dsm-account-notice--muted">
                            <?php
                            esc_html_e(
                                'Este anuncio está cerrado y ya no aparece públicamente.',
                                'dsm-anuncios'
                            );
                            ?>
                        </div>
                    <?php endif; ?>

                    <?php if (
                        $status
                        === AdvertisementStatus::REJECTED
                    ) : ?>
                        <div class="dsm-account-notice dsm-account-notice--error">
                            <strong>
                                <?php
                                esc_html_e(
                                    'El anuncio necesita cambios.',
                                    'dsm-anuncios'
                                );
                                ?>
                            </strong>

                            <?php if (
                                $rejectionReason !== ''
                            ) : ?>
                                <p>
                                    <?php echo esc_html(
                                        $rejectionReason
                                    ); ?>
                                </p>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>

                    <dl class="dsm-customer-advertisement-card__metadata">
                        <div>
                            <dt>
                                <?php
                                esc_html_e(
                                    'Creado',
                                    'dsm-anuncios'
                                );
                                ?>
                            </dt>

                            <dd>
                                <?php echo esc_html(
                                    $createdAt
                                ); ?>
                            </dd>
                        </div>

                        <div>
                            <dt>
                                <?php
                                esc_html_e(
                                    'Actualizado',
                                    'dsm-anuncios'
                                );
                                ?>
                            </dt>

                            <dd>
                                <?php echo esc_html(
                                    $updatedAt
                                ); ?>
                            </dd>
                        </div>
                    </dl>
                </div>

                <footer class="dsm-customer-advertisement-card__actions">
                    <?php if (
                        $isPublic
                        && $publicUrl !== ''
                    ) : ?>
                        <a
                            class="dsm-button dsm-button--secondary"
                            href="<?php echo esc_url(
                                $publicUrl
                            ); ?>"
                        >
                            <?php
                            esc_html_e(
                                'Ver anuncio',
                                'dsm-anuncios'
                            );
                            ?>
                        </a>
                    <?php endif; ?>

                    <?php if (
                        $canEdit
                        && $editUrl !== ''
                    ) : ?>
                        <a
                            class="dsm-button dsm-button--secondary"
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
                    <?php endif; ?>

                    <?php if ($canSubmit) : ?>
                        <?php
                        $renderActionForm(
                            $advertisementId,
                            CustomerAdvertisementActionController::
                                ACTION_SUBMIT,
                            __(
                                'Enviar a revisión',
                                'dsm-anuncios'
                            ),
                            'dsm-button--primary'
                        );
                        ?>
                    <?php endif; ?>

                    <?php if ($canReserve) : ?>
                        <?php
                        $renderActionForm(
                            $advertisementId,
                            CustomerAdvertisementActionController::
                                ACTION_RESERVE,
                            __(
                                'Marcar como reservado',
                                'dsm-anuncios'
                            ),
                            'dsm-button--secondary'
                        );
                        ?>
                    <?php endif; ?>

                    <?php if ($canRelease) : ?>
                        <?php
                        $renderActionForm(
                            $advertisementId,
                            CustomerAdvertisementActionController::
                                ACTION_RELEASE,
                            __(
                                'Liberar reserva',
                                'dsm-anuncios'
                            ),
                            'dsm-button--secondary'
                        );
                        ?>
                    <?php endif; ?>

                    <?php if ($canClose) : ?>
                        <?php
                        $renderActionForm(
                            $advertisementId,
                            CustomerAdvertisementActionController::
                                ACTION_CLOSE,
                            __(
                                'Cerrar anuncio',
                                'dsm-anuncios'
                            ),
                            'dsm-button--secondary',
                            __(
                                '¿Seguro que quieres cerrar este anuncio? Dejará de mostrarse públicamente.',
                                'dsm-anuncios'
                            )
                        );
                        ?>
                    <?php endif; ?>

                    <?php if ($canDelete) : ?>
                        <?php
                        $renderActionForm(
                            $advertisementId,
                            CustomerAdvertisementActionController::
                                ACTION_DELETE,
                            __(
                                'Eliminar definitivamente',
                                'dsm-anuncios'
                            ),
                            'dsm-button--danger',
                            __(
                                '¿Seguro que quieres eliminar definitivamente este anuncio? También se eliminarán sus imágenes y esta acción no se puede deshacer.',
                                'dsm-anuncios'
                            )
                        );
                        ?>
                    <?php endif; ?>

                    <?php
                    do_action(
                        'dsm_customer_advertisement_actions',
                        $advertisement,
                        $customerId
                    );
                    ?>

                    <?php if (!$hasActions) : ?>
                        <span class="dsm-customer-advertisement-card__no-actions">
                            <?php
                            esc_html_e(
                                'No hay acciones disponibles en este estado.',
                                'dsm-anuncios'
                            );
                            ?>
                        </span>
                    <?php endif; ?>
                </footer>
            </article>
        <?php endforeach; ?>
    </div>
</section>
