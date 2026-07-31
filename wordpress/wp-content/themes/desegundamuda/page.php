<?php

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

get_header();
?>

<main class="dsm-site-main">
    <?php
    if (have_posts()) {
        while (have_posts()) {
            the_post();
            ?>

            <article
                id="post-<?php the_ID(); ?>"
                <?php post_class('dsm-page'); ?>
            >
                <div class="dsm-page__content">
                    <?php the_content(); ?>
                </div>
            </article>

            <?php
        }
    }
    ?>
</main>

<?php
get_footer();
