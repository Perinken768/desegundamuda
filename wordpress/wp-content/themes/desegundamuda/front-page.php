<?php

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

get_header();

$sections =
    dsm_theme_home_section_order();

$visibility = [
    'search' =>
        dsm_theme_home_enabled(
            'dsm_home_show_search'
        ),

    'categories' =>
        dsm_theme_home_enabled(
            'dsm_home_show_categories'
        ),

    'filters' =>
        dsm_theme_home_enabled(
            'dsm_home_show_filters'
        ),

    'advertising' =>
        dsm_theme_home_enabled(
            'dsm_home_show_advertising'
        ),

    /*
     * Resultados forman parte estructural
     * de la portada y siempre están disponibles.
     */
    'listings' =>
        true,
];
?>

<main class="dsm-site-main dsm-home">

    <?php foreach (
        $sections
        as $section
    ) : ?>

        <?php if (
            !isset(
                $visibility[
                    $section
                ]
            )
            || !$visibility[
                $section
            ]
        ) {
            continue;
        }
        ?>

        <?php
        get_template_part(
            'template-parts/home/'
            . $section
        );
        ?>

    <?php endforeach; ?>

</main>

<?php
get_footer();
