<?php

declare(strict_types=1);

namespace DSM\Anuncios\Frontend;

use DSM\Anuncios\Application\ReportAdvertisement;
use RuntimeException;
use Throwable;

if (!defined('ABSPATH')) {
    exit;
}

final class AdvertisementReportController
{
    public const ACTION =
        'dsm_report_advertisement';

    public const NONCE_FIELD =
        'dsm_advertisement_report_nonce';

    private const NONCE_PREFIX =
        'dsm_report_advertisement_';

    public function __construct(
        private readonly ReportAdvertisement $reportAdvertisement =
            new ReportAdvertisement()
    ) {
    }

    public function register(): void
    {
        add_action(
            'admin_post_'
            . self::ACTION,
            [
                $this,
                'handle',
            ]
        );

        add_action(
            'admin_post_nopriv_'
            . self::ACTION,
            [
                $this,
                'handle',
            ]
        );
    }

    public function handle(): void
    {
        $advertisementId =
            isset($_POST['advertisement_id'])
                ? max(
                    0,
                    absint(
                        wp_unslash(
                            (string) $_POST[
                                'advertisement_id'
                            ]
                        )
                    )
                )
                : 0;

        try {
            if ($advertisementId <= 0) {
                throw new RuntimeException(
                    'El anuncio indicado no es válido.'
                );
            }

            $nonce =
                isset(
                    $_POST[
                        self::NONCE_FIELD
                    ]
                )
                    ? sanitize_text_field(
                        wp_unslash(
                            (string) $_POST[
                                self::NONCE_FIELD
                            ]
                        )
                    )
                    : '';

            if (
                $nonce === ''
                || wp_verify_nonce(
                    $nonce,
                    self::getNonceAction(
                        $advertisementId
                    )
                ) === false
            ) {
                throw new RuntimeException(
                    'La solicitud ha caducado. Recarga la página e inténtalo de nuevo.'
                );
            }

            $customerContext =
                apply_filters(
                    'dsm_current_customer_context',
                    null
                );

            if (!is_array($customerContext)) {
                $this->redirect(
                    'login_required'
                );
            }

            $customerId =
                max(
                    0,
                    (int) (
                        $customerContext['id']
                        ?? 0
                    )
                );

            if ($customerId <= 0) {
                $this->redirect(
                    'login_required'
                );
            }

            $reasonCode =
                isset($_POST['reason_code'])
                    ? sanitize_key(
                        wp_unslash(
                            (string) $_POST[
                                'reason_code'
                            ]
                        )
                    )
                    : '';

            $details =
                isset($_POST['details'])
                    ? sanitize_textarea_field(
                        wp_unslash(
                            (string) $_POST[
                                'details'
                            ]
                        )
                    )
                    : '';

            $reportId =
                $this->reportAdvertisement
                    ->execute(
                        $customerId,
                        $advertisementId,
                        $reasonCode,
                        $details
                    );

            $this->redirect(
                'reported',
                [
                    'ad_report_id' =>
                        $reportId,
                ]
            );

        } catch (Throwable $exception) {
            error_log(
                sprintf(
                    '[DSM Anuncios] Error denunciando anuncio %d: %s',
                    $advertisementId,
                    $exception->getMessage()
                )
            );

            $message =
                $exception->getMessage();

            if (
                str_contains(
                    $message,
                    'Ya has denunciado'
                )
            ) {
                $this->redirect(
                    'already_reported'
                );
            }

            $this->redirect(
                'error'
            );
        }
    }

    public static function getNonceAction(
        int $advertisementId
    ): string {
        return self::NONCE_PREFIX
            . max(
                0,
                $advertisementId
            );
    }

    /**
     * @param array<string, scalar> $extra
     */
    private function redirect(
        string $status,
        array $extra = []
    ): never {
        $url =
            wp_get_referer();

        if (
            !is_string($url)
            || trim($url) === ''
        ) {
            $url =
                home_url(
                    '/anuncios/'
                );
        }

        $url =
            remove_query_arg(
                [
                    'ad_report_status',
                    'ad_report_id',
                ],
                $url
            );

        $args =
            array_merge(
                [
                    'ad_report_status' =>
                        sanitize_key(
                            $status
                        ),
                ],
                $extra
            );

        wp_safe_redirect(
            add_query_arg(
                $args,
                $url
            )
        );

        exit;
    }
}
