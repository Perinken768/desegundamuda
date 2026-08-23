<?php

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}
?>

<section
    class="dsm-home-search"
    aria-label="Buscar en DeSegundaMuda"
>

    <div class="dsm-container">

        <?php if (
            has_action(
                'dsm_theme_home_search'
            )
        ) : ?>

            <?php
            do_action(
                'dsm_theme_home_search'
            );
            ?>

        <?php else : ?>

            <form
                class="dsm-search"
                method="get"
                action="<?php
                echo esc_url(
                    home_url('/')
                );
                ?>"
            >

                <?php
                $preservedParameters = [
                    'tipo',
                    'dsm_area',
                    'dsm_category',
                    'dsm_min_price',
                    'dsm_max_price',
                    'dsm_orderby',
                ];

                foreach (
                    $preservedParameters
                    as $parameter
                ) :
                    if (
                        !isset($_GET[$parameter])
                        || $_GET[$parameter] === ''
                    ) {
                        continue;
                    }

                    $value =
                        sanitize_text_field(
                            wp_unslash(
                                (string) $_GET[
                                    $parameter
                                ]
                            )
                        );
                    ?>

                    <input
                        type="hidden"
                        name="<?php
                        echo esc_attr(
                            $parameter
                        );
                        ?>"
                        value="<?php
                        echo esc_attr(
                            $value
                        );
                        ?>"
                    >

                <?php endforeach; ?>

                <label
                    class="screen-reader-text"
                    for="dsm-main-search"
                >
                    Buscar
                </label>

                <input
                    id="dsm-main-search"
                    class="dsm-search__input"
                    type="search"
                    name="dsm_search"
                    value="<?php
                    echo esc_attr(
                        isset($_GET['dsm_search'])
                            ? sanitize_text_field(
                                wp_unslash(
                                    (string) $_GET[
                                        'dsm_search'
                                    ]
                                )
                            )
                            : ''
                    );
                    ?>"
                    placeholder="<?php
                    echo esc_attr(
                        dsm_theme_search_placeholder()
                    );
                    ?>"
                >

                <button
                    class="
                        dsm-button
                        dsm-button--primary
                    "
                    type="submit"
                >
                    Buscar
                </button>

            </form>

        <?php endif; ?>

    </div>

</section>
