<?php

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}
?>
<!doctype html>

<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1"
    >

    <?php wp_head(); ?>
</head>

<body <?php body_class(); ?>>

<?php wp_body_open(); ?>

<header class="dsm-site-header">
    <div class="dsm-container">
        <a
            class="dsm-site-brand"
            href="<?php echo esc_url(home_url('/')); ?>"
        >
            <?php bloginfo('name'); ?>
        </a>
    </div>
</header>
