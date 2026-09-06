<?php

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Variables proporcionadas por FavoriteIntegration:
 *
 * @var int    $advertisementId
 * @var int    $customerId
 * @var bool   $isFavorite
 * @var string $action
 * @var string $context
 * @var string $redirectUrl
 */

$nonceAction =
    \DSM\Favoritos\Frontend\FavoriteController::getNonceAction(
        $action,
        $advertisementId
    );

$isGuest =
    $customerId <= 0;

$buttonLabel =
    $isFavorite
        ? __(
            'Quitar de favoritos',
            'dsm-favoritos'
        )
        : __(
            'Guardar en favoritos',
            'dsm-favoritos'
        );

$ariaLabel =
    $isFavorite
        ? __(
            'Quitar este anuncio de favoritos',
            'dsm-favoritos'
        )
        : __(
            'Guardar este anuncio en favoritos',
            'dsm-favoritos'
        );
?>

<form
    class="<?php echo esc_attr(
        'dsm-favorite-form'
        . ' dsm-favorite-form--'
        . $context
        . (
            $isFavorite
                ? ' is-favorite'
                : ''
        )
        . (
            $isGuest
                ? ' is-guest'
                : ''
        )
    ); ?>"
    method="post"
    action="<?php echo esc_url(
        admin_url(
            'admin-post.php'
        )
    ); ?>"
    data-dsm-favorite-form
    data-advertisement-id="<?php echo esc_attr(
        (string) $advertisementId
    ); ?>"
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

    <input
        type="hidden"
        name="redirect_to"
        value="<?php echo esc_attr(
            $redirectUrl
        ); ?>"
    >

    <?php
    wp_nonce_field(
        $nonceAction,
        \DSM\Favoritos\Frontend\FavoriteController::NONCE_FIELD
    );
    ?>

    <button
        class="<?php echo esc_attr(
            'dsm-favorite-button'
            . ' dsm-favorite-button--'
            . $context
            . (
                $isFavorite
                    ? ' is-favorite'
                    : ''
            )
        ); ?>"
        type="submit"
        aria-label="<?php echo esc_attr(
            $ariaLabel
        ); ?>"
        aria-pressed="<?php echo esc_attr(
            $isFavorite
                ? 'true'
                : 'false'
        ); ?>"
        title="<?php echo esc_attr(
            $buttonLabel
        ); ?>"
        data-dsm-favorite-button
    >
        <span
            class="dsm-favorite-button__heart"
            aria-hidden="true"
        >
            <?php echo $isFavorite ? '♥' : '♡'; ?>
        </span>
    </button>
</form>