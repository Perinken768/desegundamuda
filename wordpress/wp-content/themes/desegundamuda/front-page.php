<?php

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

get_header();
?>

<main class="dsm-site-main dsm-home">

    <?php if (
        dsm_theme_home_enabled(
            'dsm_home_show_search'
        )
    ) : ?>

        <?php
        get_template_part(
            'template-parts/home/search'
        );
        ?>

    <?php endif; ?>

    <?php if (
        dsm_theme_home_enabled(
            'dsm_home_show_categories'
        )
    ) : ?>

        <?php
        get_template_part(
            'template-parts/home/categories'
        );
        ?>

    <?php endif; ?>

    <?php if (
        dsm_theme_home_enabled(
            'dsm_home_show_filters'
        )
    ) : ?>

        <?php
        get_template_part(
            'template-parts/home/filters'
        );
        ?>

    <?php endif; ?>

    <?php if (
        dsm_theme_home_enabled(
            'dsm_home_show_advertising'
        )
    ) : ?>

        <?php
        get_template_part(
            'template-parts/home/advertising'
        );
        ?>

    <?php endif; ?>

    <?php
    get_template_part(
        'template-parts/home/listings'
    );
    ?>

</main>

<?php
get_footer();
