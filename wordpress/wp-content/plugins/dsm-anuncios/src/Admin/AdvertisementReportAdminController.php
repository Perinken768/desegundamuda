<?php

declare(strict_types=1);

namespace DSM\Anuncios\Admin;

use DSM\Anuncios\Advertisement\AdvertisementRepository;
use DSM\Anuncios\Moderation\AdvertisementModerationService;
use DSM\Anuncios\Moderation\AdvertisementStatusHistoryRepository;
use DSM\Anuncios\Report\AdvertisementReportRepository;
use RuntimeException;
use Throwable;

if (!defined('ABSPATH')) {
    exit;
}

final class AdvertisementReportAdminController
{
    public const ACTION_DISMISS =
        'dsm_ad_report_admin_dismiss';

    public const ACTION_REJECT =
        'dsm_ad_report_admin_reject';

    public const NONCE_FIELD =
        'dsm_ad_report_admin_nonce';

    private const CAPABILITY =
        'manage_options';

    private AdvertisementReportRepository $reportRepository;

    private AdvertisementModerationService $moderationService;

    public function __construct()
    {
        $this->reportRepository =
            new AdvertisementReportRepository();

        $this->moderationService =
            new AdvertisementModerationService(
                new AdvertisementRepository(),
                new AdvertisementStatusHistoryRepository()
            );
    }

    public function register(): void
    {
        add_action(
            'admin_post_'
            . self::ACTION_DISMISS,
            [
                $this,
                'handleDismiss',
            ]
        );

        add_action(
            'admin_post_'
            . self::ACTION_REJECT,
            [
                $this,
                'handleReject',
            ]
        );
    }

    public function handleDismiss(): void
    {
        $advertisementId =
            $this->prepareAction(
                self::ACTION_DISMISS
            );

        try {
            $notes =
                $this->getAdminNotes();

            $this->reportRepository
                ->dismissActiveByAdvertisement(
                    $advertisementId,
                    get_current_user_id(),
                    $notes !== ''
                        ? $notes
                        : 'Denuncias ignoradas desde administración.'
                );

            $this->redirect(
                'dismissed'
            );

        } catch (Throwable $exception) {
            $this->redirectError(
                $exception
            );
        }
    }

    public function handleReject(): void
    {
        $advertisementId =
            $this->prepareAction(
                self::ACTION_REJECT
            );

        try {
            $notes =
                $this->getAdminNotes();

            if ($notes === '') {
                throw new RuntimeException(
                    'Indica el motivo por el que se retira el anuncio.'
                );
            }

            /*
             * El anuncio publicado no pasa por rejected.
             *
             * Se cierra por moderación porque el flujo actual
             * de publicación es directamente ACTIVE.
             */
            $this->moderationService
                ->closeByUser(
                    $advertisementId,
                    get_current_user_id(),
                    AdvertisementModerationService::
                        CLOSURE_REASON_MODERATED,
                    'Anuncio retirado tras revisión de denuncias. '
                    . $notes
                );

            $this->reportRepository
                ->actionActiveByAdvertisement(
                    $advertisementId,
                    get_current_user_id(),
                    $notes
                );

            $this->redirect(
                'rejected'
            );

        } catch (Throwable $exception) {
            $this->redirectError(
                $exception
            );
        }
    }

    private function prepareAction(
        string $action
    ): int {
        if (
            !current_user_can(
                self::CAPABILITY
            )
        ) {
            wp_die(
                esc_html__(
                    'No tienes permisos para realizar esta acción.',
                    'dsm-anuncios'
                )
            );
        }

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
                    $action,
                    $advertisementId
                )
            ) === false
        ) {
            throw new RuntimeException(
                'La solicitud de administración ha caducado.'
            );
        }

        return $advertisementId;
    }

    public static function getNonceAction(
        string $action,
        int $advertisementId
    ): string {
        return
            'dsm_ad_report_admin_'
            . sanitize_key($action)
            . '_'
            . max(
                0,
                $advertisementId
            );
    }

    private function getAdminNotes(): string
    {
        return isset($_POST['admin_notes'])
            ? trim(
                sanitize_textarea_field(
                    wp_unslash(
                        (string) $_POST[
                            'admin_notes'
                        ]
                    )
                )
            )
            : '';
    }

    private function redirect(
        string $status
    ): never {
        wp_safe_redirect(
            add_query_arg(
                [
                    'page' =>
                        AdvertisementReportsPage::
                            MENU_SLUG,

                    'report_status' =>
                        sanitize_key(
                            $status
                        ),
                ],
                admin_url(
                    'admin.php'
                )
            )
        );

        exit;
    }

    private function redirectError(
        Throwable $exception
    ): never {
        error_log(
            '[DSM Anuncios] Error gestionando denuncias: '
            . $exception->getMessage()
        );

        wp_safe_redirect(
            add_query_arg(
                [
                    'page' =>
                        AdvertisementReportsPage::
                            MENU_SLUG,

                    'report_status' =>
                        'error',

                    'report_error' =>
                        rawurlencode(
                            $exception->getMessage()
                        ),
                ],
                admin_url(
                    'admin.php'
                )
            )
        );

        exit;
    }
}
