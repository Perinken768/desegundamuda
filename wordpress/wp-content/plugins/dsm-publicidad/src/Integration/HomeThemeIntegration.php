<?php

declare(strict_types=1);

namespace DSM\Publicidad\Integration;

use DSM\Publicidad\Advertising\AdvertisingBannerRepository;
use Throwable;

if (!defined('ABSPATH')) {
    exit;
}

final class HomeThemeIntegration
{
    public static function register(): void
    {
        add_action(
            'dsm_theme_home_advertising',
            [
                self::class,
                'render',
            ],
            10,
            1
        );
    }

    /**
     * @param array<string, mixed> $locationContext
     */
    public static function render(
        array $locationContext = []
    ): void {
        try {
            $areaId =
                max(
                    0,
                    (int) (
                        $locationContext[
                            'area_id'
                        ]
                        ?? 0
                    )
                );

            $repository =
                new AdvertisingBannerRepository();

            $banners =
                $repository
                    ->findCurrentForAreaAll(
                        $areaId
                    );

            if ($banners === []) {
                return;
            }

            $slides = [];

            foreach ($banners as $banner) {
                $imageUrl =
                    wp_get_attachment_image_url(
                        $banner
                            ->getImageAttachmentId(),
                        'full'
                    );

                if (!is_string($imageUrl)) {
                    continue;
                }

                $contact =
                    self::resolveContact(
                        $banner->getCustomerId(),
                        $banner->getTitle()
                    );

                $slides[] = [
                    'banner' =>
                        $banner,

                    'image_url' =>
                        $imageUrl,

                    'phone_call_url' =>
                        $contact[
                            'phone_call_url'
                        ],

                    'whatsapp_url' =>
                        $contact[
                            'whatsapp_url'
                        ],
                ];
            }

            if ($slides === []) {
                return;
            }

            $hasMultiple =
                count($slides) > 1;

            ?>

            <div
                class="dsm-advertising-carousel"
                data-dsm-advertising-carousel
                data-interval="6000"
            >

                <div class="dsm-advertising-carousel__viewport">

                    <?php foreach (
                        $slides
                        as $index => $slide
                    ) : ?>

                        <?php
                        $banner =
                            $slide['banner'];

                        $isActive =
                            $index === 0;
                        ?>

                        <article
                            class="
                                dsm-advertising-carousel__slide
                                <?php
                                echo $isActive
                                    ? 'is-active'
                                    : '';
                                ?>
                            "
                            data-dsm-advertising-slide
                            <?php
                            if (!$isActive) {
                                echo ' hidden';
                            }
                            ?>
                        >

                            <a
                                class="dsm-advertising-banner"
                                href="<?php
                                echo esc_url(
                                    $banner
                                        ->getTargetUrl()
                                );
                                ?>"
                                target="_blank"
                                rel="noopener sponsored"
                            >
                                <img
                                    src="<?php
                                    echo esc_url(
                                        $slide[
                                            'image_url'
                                        ]
                                    );
                                    ?>"
                                    alt="<?php
                                    echo esc_attr(
                                        $banner
                                            ->getTitle()
                                    );
                                    ?>"
                                    loading="lazy"
                                >
                            </a>

                            <?php if (
                                $slide['whatsapp_url'] !== ''
                                || $slide['phone_call_url'] !== ''
                            ) : ?>

                                <div
                                    class="
                                        dsm-advertising-card__actions
                                    "
                                >

                                    <?php if (
                                        $slide['whatsapp_url'] !== ''
                                    ) : ?>

                                        <a
                                            class="
                                                dsm-button
                                                dsm-button--success
                                                dsm-button--whatsapp
                                            "
                                            href="<?php
                                            echo esc_url(
                                                $slide[
                                                    'whatsapp_url'
                                                ]
                                            );
                                            ?>"
                                            target="_blank"
                                            rel="noopener noreferrer"
                                        >
                                            WhatsApp
                                        </a>

                                    <?php endif; ?>

                                    <?php if (
                                        $slide['phone_call_url'] !== ''
                                    ) : ?>

                                        <a
                                            class="
                                                dsm-button
                                                dsm-button--primary
                                                dsm-button--phone
                                            "
                                            href="<?php
                                            echo esc_url(
                                                $slide[
                                                    'phone_call_url'
                                                ]
                                            );
                                            ?>"
                                        >
                                            Llamar
                                        </a>

                                    <?php endif; ?>

                                </div>

                            <?php endif; ?>

                        </article>

                    <?php endforeach; ?>

                </div>

                <?php if ($hasMultiple) : ?>

                    <div
                        class="dsm-advertising-carousel__controls"
                        aria-label="Controles de publicidad"
                    >

                        <button
                            class="
                                dsm-advertising-carousel__control
                                dsm-advertising-carousel__control--previous
                            "
                            type="button"
                            data-dsm-advertising-previous
                            aria-label="Publicidad anterior"
                        >
                            ‹
                        </button>

                        <div
                            class="dsm-advertising-carousel__dots"
                            aria-label="Publicidades disponibles"
                        >

                            <?php foreach (
                                $slides
                                as $index => $slide
                            ) : ?>

                                <button
                                    class="
                                        dsm-advertising-carousel__dot
                                        <?php
                                        echo $index === 0
                                            ? 'is-active'
                                            : '';
                                        ?>
                                    "
                                    type="button"
                                    data-dsm-advertising-dot="<?php
                                    echo esc_attr(
                                        (string) $index
                                    );
                                    ?>"
                                    aria-label="<?php
                                    echo esc_attr(
                                        sprintf(
                                            'Mostrar publicidad %d',
                                            $index + 1
                                        )
                                    );
                                    ?>"
                                    <?php if ($index === 0) : ?>
                                        aria-current="true"
                                    <?php endif; ?>
                                ></button>

                            <?php endforeach; ?>

                        </div>

                        <button
                            class="
                                dsm-advertising-carousel__control
                                dsm-advertising-carousel__control--next
                            "
                            type="button"
                            data-dsm-advertising-next
                            aria-label="Publicidad siguiente"
                        >
                            ›
                        </button>

                    </div>

                <?php endif; ?>

            </div>

            <?php
        } catch (Throwable $exception) {
            error_log(
                '[DSM Publicidad] No se pudo renderizar '
                . 'el carrusel de publicidad: '
                . $exception->getMessage()
            );
        }
    }

    /**
     * @return array{
     *     phone_call_url:string,
     *     whatsapp_url:string
     * }
     */
    private static function resolveContact(
        ?int $customerId,
        string $title
    ): array {
        $result = [
            'phone_call_url' =>
                '',

            'whatsapp_url' =>
                '',
        ];

        if (
            $customerId === null
            || $customerId <= 0
        ) {
            return $result;
        }

        $contact =
            apply_filters(
                'dsm_customer_public_contact_by_id',
                null,
                $customerId
            );

        if (!is_array($contact)) {
            return $result;
        }

        $hasValidContact =
            !empty(
                $contact[
                    'has_valid_contact'
                ]
            );

        if (!$hasValidContact) {
            return $result;
        }

        if (
            !empty(
                $contact[
                    'allows_phone_calls'
                ]
            )
        ) {
            $result['phone_call_url'] =
                trim(
                    (string) (
                        $contact[
                            'phone_call_url'
                        ]
                        ?? ''
                    )
                );
        }

        if (
            !empty(
                $contact[
                    'allows_whatsapp'
                ]
            )
        ) {
            $whatsappUrl =
                trim(
                    (string) (
                        $contact[
                            'whatsapp_url'
                        ]
                        ?? ''
                    )
                );

            if ($whatsappUrl !== '') {
                $message =
                    sprintf(
                        'Hola, he visto la publicidad "%s" en DeSegundaMuda y me gustaría recibir más información.',
                        $title
                    );

                $result['whatsapp_url'] =
                    add_query_arg(
                        [
                            'text' =>
                                $message,
                        ],
                        $whatsappUrl
                    );
            }
        }

        return $result;
    }

    private function __construct()
    {
    }
}
