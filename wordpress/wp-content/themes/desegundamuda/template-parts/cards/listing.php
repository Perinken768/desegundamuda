<?php

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

$item =
    isset($args['item'])
    && is_array($args['item'])
        ? $args['item']
        : [];

$title =
    trim(
        (string) (
            $item['title']
            ?? ''
        )
    );

$url =
    trim(
        (string) (
            $item['url']
            ?? ''
        )
    );

$imageUrl =
    trim(
        (string) (
            $item['image_url']
            ?? ''
        )
    );

$contentType =
    sanitize_key(
        (string) (
            $item['content_type']
            ?? ''
        )
    );

$price =
    isset($item['price'])
        ? (float) $item['price']
        : null;

$originalPrice =
    isset($item['original_price'])
    && $item['original_price'] !== null
        ? (float) $item['original_price']
        : null;

$seller =
    trim(
        (string) (
            $item['seller']
            ?? ''
        )
    );

$isPromoted =
    !empty(
        $item['is_promoted']
    );

$isReserved =
    !empty(
        $item['is_reserved']
    );

if (
    $title === ''
    || $url === ''
) {
    return;
}
?>

<article class="dsm-listing-card">

    <a
        class="dsm-listing-card__link"
        href="<?php
        echo esc_url(
            $url
        );
        ?>"
    >

        <div class="dsm-listing-card__media">

            <?php if (
                $imageUrl !== ''
            ) : ?>

                <img
                    class="dsm-listing-card__image"
                    src="<?php
                    echo esc_url(
                        $imageUrl
                    );
                    ?>"
                    alt=""
                    loading="lazy"
                >

            <?php else : ?>

                <div
                    class="
                        dsm-listing-card__image
                        dsm-listing-card__image--empty
                    "
                >
                    Sin imagen
                </div>

            <?php endif; ?>

            <div class="dsm-listing-card__badges">

                <?php if (
                    $contentType
                    === 'store_product'
                ) : ?>

                    <span class="dsm-listing-badge">
                        Tienda
                    </span>

                <?php endif; ?>

                <?php if ($isPromoted) : ?>

                    <span
                        class="
                            dsm-listing-badge
                            dsm-listing-badge--promoted
                        "
                    >
                        Destacado
                    </span>

                <?php endif; ?>

                <?php if ($isReserved) : ?>

                    <span
                        class="
                            dsm-listing-badge
                            dsm-listing-badge--reserved
                        "
                    >
                        Reservado
                    </span>

                <?php endif; ?>

            </div>

        </div>

        <div class="dsm-listing-card__body">

            <h3 class="dsm-listing-card__title">
                <?php
                echo esc_html(
                    $title
                );
                ?>
            </h3>

            <?php if (
                $seller !== ''
            ) : ?>

                <p class="dsm-listing-card__seller">
                    <?php
                    echo esc_html(
                        $seller
                    );
                    ?>
                </p>

            <?php endif; ?>

            <?php if (
                $price !== null
            ) : ?>

                <div class="dsm-listing-card__price">

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

            <?php endif; ?>

        </div>

    </a>

</article>
