<?php

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

$footerText =
    trim(
        (string) get_theme_mod(
            'dsm_footer_text',
            ''
        )
    );

$legalLinks =
    apply_filters(
        'dsm_legal_footer_links',
        []
    );

if (!is_array($legalLinks)) {
    $legalLinks = [];
}

$socialLinks = [];

$socialNetworks = [
    'instagram' => [
        'setting' =>
            'dsm_footer_instagram_url',

        'label' =>
            'Instagram',
    ],

    'facebook' => [
        'setting' =>
            'dsm_footer_facebook_url',

        'label' =>
            'Facebook',
    ],

    'tiktok' => [
        'setting' =>
            'dsm_footer_tiktok_url',

        'label' =>
            'TikTok',
    ],

    'x' => [
        'setting' =>
            'dsm_footer_x_url',

        'label' =>
            'X',
    ],

    'youtube' => [
        'setting' =>
            'dsm_footer_youtube_url',

        'label' =>
            'YouTube',
    ],
];

foreach (
    $socialNetworks
    as $network => $configuration
) {
    $url =
        trim(
            (string) get_theme_mod(
                $configuration[
                    'setting'
                ],
                ''
            )
        );

    if ($url === '') {
        continue;
    }

    $socialLinks[] = [
        'network' =>
            $network,

        'label' =>
            $configuration[
                'label'
            ],

        'url' =>
            $url,
    ];
}
?>

<footer class="dsm-site-footer">

    <div class="dsm-container">

        <div class="dsm-site-footer__grid">

            <div class="dsm-site-footer__brand">

                <strong class="dsm-site-footer__name">
                    <?php bloginfo('name'); ?>
                </strong>

                <?php if ($footerText !== '') : ?>

                    <p class="dsm-site-footer__text">
                        <?php
                        echo esc_html(
                            $footerText
                        );
                        ?>
                    </p>

                <?php endif; ?>

                <p class="dsm-site-footer__copyright">
                    &copy;
                    <?php
                    echo esc_html(
                        gmdate('Y')
                    );
                    ?>
                    <?php bloginfo('name'); ?>
                </p>

            </div>


            <?php if (
                $legalLinks !== []
                || has_filter(
                    'dsm_cookie_consent_state'
                )
            ) : ?>

                <nav
                    class="dsm-site-footer__legal"
                    aria-label="<?php
                    esc_attr_e(
                        'Información legal',
                        'desegundamuda'
                    );
                    ?>"
                >

                    <strong class="dsm-site-footer__heading">
                        Información legal
                    </strong>

                    <ul>

                        <?php foreach (
                            $legalLinks
                            as $legalLink
                        ) : ?>

                            <li>
                                <a
                                    href="<?php
                                    echo esc_url(
                                        $legalLink[
                                            'url'
                                        ]
                                    );
                                    ?>"
                                >
                                    <?php
                                    echo esc_html(
                                        $legalLink[
                                            'label'
                                        ]
                                    );
                                    ?>
                                </a>
                            </li>

                        <?php endforeach; ?>

                        <li>
                            <a
                                href="#"
                                data-dsm-cookie-open
                            >
                                <?php
                                esc_html_e(
                                    'Configurar cookies',
                                    'desegundamuda'
                                );
                                ?>
                            </a>
                        </li>

                    </ul>

                </nav>

            <?php endif; ?>


            <?php if ($socialLinks !== []) : ?>

                <div class="dsm-site-footer__social">

                    <strong class="dsm-site-footer__heading">
                        Síguenos
                    </strong>

                    <div class="dsm-site-footer__social-links">

                        <?php foreach (
                            $socialLinks
                            as $socialLink
                        ) : ?>

                            <a
                                class="<?php
                                echo esc_attr(
                                    'dsm-social-link '
                                    . 'dsm-social-link--'
                                    . $socialLink[
                                        'network'
                                    ]
                                );
                                ?>"
                                href="<?php
                                echo esc_url(
                                    $socialLink[
                                        'url'
                                    ]
                                );
                                ?>"
                                target="_blank"
                                rel="noopener noreferrer"
                            >
                                <?php
                                echo esc_html(
                                    $socialLink[
                                        'label'
                                    ]
                                );
                                ?>
                            </a>

                        <?php endforeach; ?>

                    </div>

                </div>

            <?php endif; ?>

        </div>

    </div>

</footer>

<?php wp_footer(); ?>

</body>
</html>
