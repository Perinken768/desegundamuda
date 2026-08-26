<?php

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}
?>

<section class="dsm-home-section dsm-home-categories">

    <div class="dsm-container">

        <div class="dsm-section-heading">
            <h2>
                <?php
                echo esc_html(
                    dsm_theme_home_categories_title()
                );
                ?>
            </h2>
        </div>

        <div class="dsm-category-strip">

            <?php if (
                has_action(
                    'dsm_theme_home_categories'
                )
            ) : ?>

                <?php
                do_action(
                    'dsm_theme_home_categories'
                );
                ?>

            <?php else : ?>

                <span class="dsm-empty-state">
                    Las categorías aparecerán aquí.
                </span>

            <?php endif; ?>

        </div>

    </div>

</section>
