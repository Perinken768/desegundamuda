<?php
declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

function desegundamuda_setup(): void
{
    add_theme_support('title-tag');
    add_theme_support('post-thumbnails');
    add_theme_support('html5', [
        'search-form',
        'comment-form',
        'comment-list',
        'gallery',
        'caption',
        'style',
        'script',
    ]);
}

add_action('after_setup_theme', 'desegundamuda_setup');
