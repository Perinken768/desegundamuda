<?php

declare(strict_types=1);

use DSM\Legal\Document\LegalDocument;
use DSM\Legal\Document\LegalContentResolver;

if (!defined('ABSPATH')) {
    exit;
}

/** @var LegalDocument|null $document */
$document =
    $GLOBALS[
        'dsm_legal_document'
    ]
        ?? null;

if (!$document instanceof LegalDocument) {
    return;
}

get_header();
?>

<main class="dsm-site-main dsm-legal">

    <div class="dsm-container">

        <article class="dsm-legal-document">

            <header class="dsm-legal-document__header">

                <h1 class="dsm-legal-document__title">
                    <?php
                    echo esc_html(
                        $document->getTitle()
                    );
                    ?>
                </h1>

                <div class="dsm-legal-document__meta">

                    <span>
                        <?php
                        echo esc_html(
                            sprintf(
                                'Versión %s',
                                $document->getVersion()
                            )
                        );
                        ?>
                    </span>

                    <?php if (
                        $document->getUpdatedAt() !== ''
                    ) : ?>

                        <span aria-hidden="true">
                            ·
                        </span>

                        <span>
                            <?php
                            $updatedTimestamp =
                                strtotime(
                                    $document->getUpdatedAt()
                                );

                            echo esc_html(
                                sprintf(
                                    'Última actualización: %s',
                                    $updatedTimestamp !== false
                                        ? wp_date(
                                            'j \d\e F \d\e Y',
                                            $updatedTimestamp
                                        )
                                        : $document->getUpdatedAt()
                                )
                            );
                            ?>
                        </span>

                    <?php endif; ?>

                </div>

            </header>

            <div class="dsm-legal-document__content">

                <?php
                echo wp_kses_post(
                    wpautop(
                        LegalContentResolver::resolve(
                            $document->getContent()
                        )
                    )
                );
                ?>

            </div>

        </article>

    </div>

</main>

<?php
get_footer();
