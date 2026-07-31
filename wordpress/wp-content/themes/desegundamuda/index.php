<?php

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

get_header();
?>

<main class="dsm-site-main">
    <div class="dsm-container">

        <?php
        if (have_posts()) {
            while (have_posts()) {
                the_post();
                ?>

                <article
                    id="post-<?php the_ID(); ?>"
                    <?php post_class('dsm-entry'); ?>
                >
                    <h1 class="dsm-entry__title">
                        <?php the_title(); ?>
                    </h1>

                    <div class="dsm-entry__content">
                        <?php the_content(); ?>
                    </div>
                </article>

                <?php
            }
        } else {
            ?>
            <p>
                <?php
                esc_html_e(
                    'No se ha encontrado contenido.',
                    'desegundamuda'
                );
                ?>
            </p>
            <?php
        }
        ?>

    </div>
</main>

<?php
get_footer();
