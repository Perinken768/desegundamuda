<?php

declare(strict_types=1);

use DSM\Favoritos\Frontend\FavoriteController;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Variables:
 *
 * @var array{id:int,status:string} $customerContext
 * @var int $customerId
 * @var array<int, array<string, mixed>> $favorites
 * @var int $favoriteCount
 * @var string $marketplaceUrl
 * @var bool $hasFavorites
 */

$conditionLabels = [
    'new_with_tags' =>
        __(
            'Nuevo con etiquetas',
            'dsm-favoritos'
        ),

    'new_without_tags' =>
        __(
            'Nuevo sin etiquetas',
            'dsm-favoritos'
        ),

    'very_good' =>
        __(
            'Muy buen estado',
            'dsm-favoritos'
        ),

    'good' =>
        __(
            'Buen estado',
            'dsm-favoritos'
        ),

    'satisfactory' =>
        __(
            'Estado satisfactorio',
            'dsm-favoritos'
        ),
];

$currentUrl =
    isset($_SERVER['REQUEST_URI'])
    && is_string(
        $_SERVER['REQUEST_URI']
    )
        ? home_url(
            wp_unslash(
                $_SERVER['REQUEST_URI']
            )
        )
        : home_url(
            '/mis-favoritos/'
        );

$currentUrl =
    wp_validate_redirect(
        $currentUrl,
        home_url(
            '/mis-favoritos/'
        )
    );
?>

<section class="dsm-customer-favorites">
    <header class="dsm-customer-favorites__header">
        <div>
            <h1 class="dsm-customer-favorites__title">
                <?php
                esc_html_e(
                    'Mis favoritos',
                    'dsm-favoritos'
                );
                ?>
            </h1>

            <p class="dsm-customer-favorites__description">
                <?php
                esc_html_e(
                    'Aquí encontrarás los anuncios y productos que has guardado para verlos más tarde.',
                    'dsm-favoritos'
                );
                ?>
            </p>
        </div>

        <div class="dsm-customer-favorites__count">
            <?php
            printf(
                esc_html(
                    _n(
                        '%s favorito',
                        '%s favoritos',
                        $favoriteCount,
                        'dsm-favoritos'
                    )
                ),
                esc_html(
                    number_format_i18n(
                        $favoriteCount
                    )
                )
            );
            ?>
        </div>
    </header>

    <?php if (!$hasFavorites) : ?>
        <div class="dsm-customer-favorites__empty">
            <span
                class="dashicons dashicons-heart"
                aria-hidden="true"
            ></span>

            <h2>
                <?php
                esc_html_e(
                    'Todavía no tienes favoritos',
                    'dsm-favoritos'
                );
                ?>
            </h2>

            <p>
                <?php
                esc_html_e(
                    'Guarda los anuncios y productos que te interesen y aparecerán aquí.',
                    'dsm-favoritos'
                );
                ?>
            </p>

            <a
                class="dsm-favorites-button dsm-favorites-button--primary"
                href="<?php echo esc_url(
                    $marketplaceUrl
                ); ?>"
            >
                <?php
                esc_html_e(
                    'Explorar anuncios',
                    'dsm-favoritos'
                );
                ?>
            </a>
        </div>

    <?php else : ?>
        <div class="dsm-customer-favorites__grid">
            <?php foreach (
                $favorites
                as $favorite
            ) : ?>
                <?php
                $itemType =
                    sanitize_key(
                        (string) (
                            $favorite[
                                'item_type'
                            ]
                            ?? ''
                        )
                    );

                $itemId =
                    max(
                        0,
                        (int) (
                            $favorite[
                                'item_id'
                            ]
                            ?? 0
                        )
                    );

                if (
                    $itemType === ''
                    || $itemId <= 0
                ) {
                    continue;
                }

                $isStoreProduct =
                    $itemType
                    === 'store_product';

                $title =
                    trim(
                        (string) (
                            $favorite['title']
                            ?? ''
                        )
                    );

                $coverUrl =
                    trim(
                        (string) (
                            $favorite['cover_url']
                            ?? ''
                        )
                    );

                $publicUrl =
                    trim(
                        (string) (
                            $favorite['public_url']
                            ?? ''
                        )
                    );

                $brand =
                    trim(
                        (string) (
                            $favorite['brand']
                            ?? ''
                        )
                    );

                $conditionCode =
                    sanitize_key(
                        (string) (
                            $favorite[
                                'condition_code'
                            ]
                            ?? ''
                        )
                    );

                $conditionLabel =
                    $conditionLabels[
                        $conditionCode
                    ]
                    ?? $conditionCode;

                $price =
                    (float) (
                        $favorite['price']
                        ?? 0
                    );

                $originalPrice =
                    isset(
                        $favorite[
                            'original_price'
                        ]
                    )
                    && $favorite[
                        'original_price'
                    ] !== null
                        ? (float) $favorite[
                            'original_price'
                        ]
                        : null;

                $isReserved =
                    !empty(
                        $favorite[
                            'is_reserved'
                        ]
                    );

                $sourceLabel =
                    trim(
                        (string) (
                            $favorite[
                                'source_label'
                            ]
                            ?? ''
                        )
                    );

                $actionLabel =
                    trim(
                        (string) (
                            $favorite[
                                'action_label'
                            ]
                            ?? ''
                        )
                    );

                $nonceAction =
                    FavoriteController::
                        getNonceAction(
                            FavoriteController::
                                ACTION_REMOVE,
                            $itemId,
                            $itemType
                        );
                ?>

                <article
                    class="<?php echo esc_attr(
                        'dsm-customer-favorite'
                        . (
                            $isReserved
                                ? ' is-reserved'
                                : ''
                        )
                    ); ?>"
                    data-item-type="<?php echo esc_attr(
                        $itemType
                    ); ?>"
                    data-item-id="<?php echo esc_attr(
                        (string) $itemId
                    ); ?>"
                >
                    <a
                        class="dsm-customer-favorite__image"
                        href="<?php echo esc_url(
                            $publicUrl
                        ); ?>"
                    >
                        <?php if ($coverUrl !== '') : ?>
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
                                class="dsm-customer-favorite__placeholder"
                                aria-hidden="true"
                            >
                                <span class="dashicons dashicons-format-image"></span>
                            </span>
                        <?php endif; ?>

                        <?php if ($isReserved) : ?>
                            <span class="dsm-customer-favorite__reserved">
                                <?php
                                esc_html_e(
                                    'Reservado',
                                    'dsm-favoritos'
                                );
                                ?>
                            </span>
                        <?php endif; ?>
                    </a>

                    <form
                        class="dsm-customer-favorite__heart-form"
                        method="post"
                        action="<?php echo esc_url(
                            admin_url(
                                'admin-post.php'
                            )
                        ); ?>"
                        data-dsm-favorite-form
                    >
                        <input
                            type="hidden"
                            name="action"
                            value="<?php echo esc_attr(
                                FavoriteController::
                                    ACTION_REMOVE
                            ); ?>"
                        >

                        <input
                            type="hidden"
                            name="item_type"
                            value="<?php echo esc_attr(
                                $itemType
                            ); ?>"
                        >

                        <input
                            type="hidden"
                            name="item_id"
                            value="<?php echo esc_attr(
                                (string) $itemId
                            ); ?>"
                        >

                        <input
                            type="hidden"
                            name="redirect_to"
                            value="<?php echo esc_attr(
                                $currentUrl
                            ); ?>"
                        >

                        <?php
                        wp_nonce_field(
                            $nonceAction,
                            FavoriteController::
                                NONCE_FIELD
                        );
                        ?>

                        <button
                            class="dsm-customer-favorite__heart"
                            type="submit"
                            data-dsm-favorite-button
                            aria-label="<?php
                            echo esc_attr__(
                                'Quitar de favoritos',
                                'dsm-favoritos'
                            );
                            ?>"
                            title="<?php
                            echo esc_attr__(
                                'Quitar de favoritos',
                                'dsm-favoritos'
                            );
                            ?>"
                        >
                            <span
                                class="dsm-customer-favorite__heart-icon"
                                aria-hidden="true"
                            >
                                ♥
                            </span>
                        </button>
                    </form>

                    <div class="dsm-customer-favorite__content">
                        <h2 class="dsm-customer-favorite__title">
                            <a href="<?php echo esc_url(
                                $publicUrl
                            ); ?>">
                                <?php echo esc_html(
                                    $title
                                ); ?>
                            </a>
                        </h2>

                        <?php if ($sourceLabel !== '') : ?>
                            <p class="dsm-customer-favorite__source">
                                <?php
                                echo esc_html(
                                    $sourceLabel
                                );
                                ?>
                            </p>
                        <?php endif; ?>

                        <div class="dsm-customer-favorite__price">
                            <strong>
                                <?php
                                echo esc_html(
                                    number_format_i18n(
                                        $price,
                                        2
                                    )
                                    . ' €'
                                );
                                ?>
                            </strong>

                            <?php if (
                                $originalPrice !== null
                                && $originalPrice > $price
                            ) : ?>
                                <del>
                                    <?php
                                    echo esc_html(
                                        number_format_i18n(
                                            $originalPrice,
                                            2
                                        )
                                        . ' €'
                                    );
                                    ?>
                                </del>
                            <?php endif; ?>
                        </div>

                        <?php if (
                            $brand !== ''
                            || $conditionLabel !== ''
                        ) : ?>
                            <dl class="dsm-customer-favorite__attributes">
                                <?php if ($brand !== '') : ?>
                                    <div>
                                        <dt>
                                            <?php
                                            esc_html_e(
                                                'Marca',
                                                'dsm-favoritos'
                                            );
                                            ?>
                                        </dt>

                                        <dd>
                                            <?php echo esc_html(
                                                $brand
                                            ); ?>
                                        </dd>
                                    </div>
                                <?php endif; ?>

                                <?php if (
                                    $conditionLabel !== ''
                                ) : ?>
                                    <div>
                                        <dt>
                                            <?php
                                            esc_html_e(
                                                'Estado',
                                                'dsm-favoritos'
                                            );
                                            ?>
                                        </dt>

                                        <dd>
                                            <?php echo esc_html(
                                                $conditionLabel
                                            ); ?>
                                        </dd>
                                    </div>
                                <?php endif; ?>
                            </dl>
                        <?php endif; ?>

                        <div class="dsm-customer-favorite__actions">
                            <a
                                class="dsm-favorites-button dsm-favorites-button--primary"
                                href="<?php echo esc_url(
                                    $publicUrl
                                ); ?>"
                            >
                                <?php
                                echo esc_html(
                                    $actionLabel !== ''
                                        ? $actionLabel
                                        : __(
                                            'Ver',
                                            'dsm-favoritos'
                                        )
                                );
                                ?>
                            </a>

                        </div>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>