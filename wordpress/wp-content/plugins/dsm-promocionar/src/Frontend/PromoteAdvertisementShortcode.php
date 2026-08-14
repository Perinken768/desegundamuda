<?php

declare(strict_types=1);

namespace DSM\Promocionar\Frontend;

use DSM\Promocionar\Promotion\PromotionAssignmentRepository;
use DSM\Promocionar\Promotion\PromotionPlanRepository;
use DSM\Promocionar\Promotion\PromotionWalletRepository;
use DSM\Promocionar\Support\AdvertisementContext;
use Throwable;

if (!defined('ABSPATH')) {
    exit;
}

final class PromoteAdvertisementShortcode
{
    public const SHORTCODE =
        'dsm_promote_advertisement';

    public static function register(): void
    {
        add_shortcode(
            self::SHORTCODE,
            [
                self::class,
                'render',
            ]
        );
    }

    public static function render(): string
    {
        $customerContext =
            apply_filters(
                'dsm_current_customer_context',
                null
            );

        if (
            !is_array($customerContext)
            || (
                (int) (
                    $customerContext['id']
                    ?? 0
                )
            ) <= 0
        ) {
            wp_safe_redirect(
                home_url(
                    '/iniciar-sesion/'
                )
            );

            exit;
        }

        $customerId =
            (int) $customerContext['id'];

        $advertisementId =
            isset($_GET['advertisement_id'])
                ? absint(
                    wp_unslash(
                        (string) $_GET['advertisement_id']
                    )
                )
                : 0;

        if ($advertisementId <= 0) {
            return self::renderError(
                __(
                    'No se indicó un anuncio válido.',
                    'dsm-promocionar'
                )
            );
        }

        try {
            $advertisement =
                AdvertisementContext::requirePromotable(
                    $advertisementId,
                    $customerId
                );

            $assignmentRepository =
                new PromotionAssignmentRepository();

            $activeAssignment =
                $assignmentRepository
                    ->findActiveByAdvertisement(
                        $advertisementId
                    );

            $walletRepository =
                new PromotionWalletRepository();

            $wallets =
                $walletRepository
                    ->findAvailableByCustomer(
                        $customerId
                    );

            $planRepository =
                new PromotionPlanRepository();

            $plans =
                $planRepository
                    ->findActive();

            return self::renderTemplate(
                [
                    'customerContext' =>
                        $customerContext,

                    'customerId' =>
                        $customerId,

                    'advertisement' =>
                        $advertisement,

                    'advertisementId' =>
                        $advertisementId,

                    'activeAssignment' =>
                        $activeAssignment,

                    'wallets' =>
                        $wallets,

                    'plans' =>
                        $plans,

                    'status' =>
                        isset($_GET['promotion_status'])
                            ? sanitize_key(
                                wp_unslash(
                                    (string) $_GET[
                                        'promotion_status'
                                    ]
                                )
                            )
                            : '',

                    'error' =>
                        isset($_GET['promotion_error'])
                            ? sanitize_text_field(
                                wp_unslash(
                                    (string) $_GET[
                                        'promotion_error'
                                    ]
                                )
                            )
                            : '',
                ]
            );
        } catch (Throwable $exception) {
            return self::renderError(
                $exception->getMessage()
            );
        }
    }

    /**
     * @param array<string, mixed> $variables
     */
    private static function renderTemplate(
        array $variables
    ): string {
        $template =
            DSM_PROMOCIONAR_PATH
            . 'templates/account/'
            . 'promote-advertisement.php';

        if (!is_file($template)) {
            return self::renderError(
                __(
                    'No se encontró la plantilla de promoción.',
                    'dsm-promocionar'
                )
            );
        }

        extract(
            $variables,
            EXTR_SKIP
        );

        ob_start();

        include $template;

        $output =
            ob_get_clean();

        return is_string($output)
            ? $output
            : '';
    }

    private static function renderError(
        string $message
    ): string {
        return sprintf(
            '<div class="dsm-account-notice dsm-account-notice--error">%s</div>',
            esc_html(
                $message
            )
        );
    }

    private function __construct()
    {
    }
}
