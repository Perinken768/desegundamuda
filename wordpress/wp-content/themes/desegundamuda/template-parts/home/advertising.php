<?php

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

$locationContext =
    dsm_theme_home_location_context();
?>

<section class="dsm-home-advertising">

    <div class="dsm-container">

        <?php if (
            has_action(
                'dsm_theme_home_advertising'
            )
        ) : ?>

            <?php
            do_action(
                'dsm_theme_home_advertising',
                $locationContext
            );
            ?>

        <?php else : ?>

            <div class="dsm-advertising-slot">
                <span>
                    Espacio publicitario
                </span>
            </div>

        <?php endif; ?>

    </div>

</section>
