<?php

declare(strict_types=1);

namespace DSM\Promocionar\Frontend;

use DSM\Promocionar\Promotion\PromotionAssignmentRepository;

if (!defined('ABSPATH')) {
    exit;
}

final class CustomerAdvertisementPromotionIntegration
{
    public static function register(): void
    {
        add_filter(
            'dsm_customer_advertisement_has_extension_actions',
            [
                self::class,
                'hasExtensionActions',
            ],
            10,
            3
        );

        add_action(
            'dsm_customer_advertisement_actions',
            [
                self::class,
                'renderActions',
            ],
            10,
            2
        );
    }

    /**
     * @param mixed $current
     * @param array<string, mixed> $advertisement
     */
    public static function hasExtensionActions(
        mixed $current,
        array $advertisement,
        int $customerId
    ): bool {
        if ((bool) $current) {
            return true;
        }

        return self::canRenderAction(
            $advertisement,
            $customerId
        );
    }

    /**
     * @param array<string, mixed> $advertisement
     */
    public static function renderActions(
        array $advertisement,
        int $customerId
    ): void {
        if (
            !self::canRenderAction(
                $advertisement,
                $customerId
            )
        ) {
            return;
        }

        $advertisementId =
            max(
                0,
                (int) (
                    $advertisement['id']
                    ?? 0
                )
            );

        if ($advertisementId <= 0) {
            return;
        }

        $repository =
            new PromotionAssignmentRepository();

        $activeAssignment =
            $repository
                ->findActiveByAdvertisement(
                    $advertisementId
                );

        if ($activeAssignment !== null) {
            ?>
            <span
                class="dsm-button dsm-button--secondary"
                aria-disabled="true"
            >
                <?php
                esc_html_e(
                    'Promocionado',
                    'dsm-promocionar'
                );
                ?>
            </span>
            <?php

            return;
        }

        $url =
            add_query_arg(
                [
                    'advertisement_id' =>
                        $advertisementId,
                ],
                home_url(
                    '/promocionar-anuncio/'
                )
            );

        $url =
            (string) apply_filters(
                'dsm_customer_promote_advertisement_url',
                $url,
                $advertisementId,
                $customerId,
                $advertisement
            );

        ?>
        <a
            class="dsm-button dsm-button--primary"
            href="<?php echo esc_url($url); ?>"
        >
            <?php
            esc_html_e(
                'Promocionar',
                'dsm-promocionar'
            );
            ?>
        </a>
        <?php
    }

    /**
     * @param array<string, mixed> $advertisement
     */
    private static function canRenderAction(
        array $advertisement,
        int $customerId
    ): bool {
        if ($customerId <= 0) {
            return false;
        }

        $advertisementId =
            max(
                0,
                (int) (
                    $advertisement['id']
                    ?? 0
                )
            );

        if ($advertisementId <= 0) {
            return false;
        }

        $status =
            sanitize_key(
                (string) (
                    $advertisement['status']
                    ?? ''
                )
            );

        if ($status !== 'active') {
            return false;
        }

        $isPublic =
            !empty(
                $advertisement['is_public']
            );

        return $isPublic;
    }

    private function __construct()
    {
    }
}
