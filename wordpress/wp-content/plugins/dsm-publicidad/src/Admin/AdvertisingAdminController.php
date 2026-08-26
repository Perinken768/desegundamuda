<?php

declare(strict_types=1);

namespace DSM\Publicidad\Admin;

use DSM\Publicidad\Advertising\AdvertisingBannerRepository;
use RuntimeException;
use Throwable;

if (!defined('ABSPATH')) {
    exit;
}

final class AdvertisingAdminController
{
    private const SAVE_ACTION =
        'dsm_publicidad_save_banner';

    private const DELETE_ACTION =
        'dsm_publicidad_delete_banner';

    private const NONCE_FIELD =
        'dsm_publicidad_nonce';

    public static function register(): void
    {
        add_action(
            'admin_menu',
            [
                self::class,
                'registerMenu',
            ]
        );

        add_action(
            'admin_enqueue_scripts',
            [
                self::class,
                'enqueueMedia',
            ]
        );

        add_action(
            'admin_post_'
            . self::SAVE_ACTION,
            [
                self::class,
                'handleSave',
            ]
        );

        add_action(
            'admin_post_'
            . self::DELETE_ACTION,
            [
                self::class,
                'handleDelete',
            ]
        );
    }

    public static function registerMenu(): void
    {
        add_menu_page(
            'DSM Publicidad',
            'DSM Publicidad',
            'manage_options',
            'dsm-publicidad',
            [
                self::class,
                'renderPage',
            ],
            'dashicons-images-alt2',
            58
        );
    }

    public static function enqueueMedia(
        string $hook
    ): void {
        if (
            !str_contains(
                $hook,
                'dsm-publicidad'
            )
        ) {
            return;
        }

        wp_enqueue_media();
    }

    public static function renderPage(): void
    {
        if (
            !current_user_can(
                'manage_options'
            )
        ) {
            return;
        }

        $repository =
            new AdvertisingBannerRepository();

        $banners =
            $repository->findAll();

        $editingBanner = null;

        $editId =
            isset($_GET['banner_id'])
                ? absint(
                    wp_unslash(
                        (string) $_GET[
                            'banner_id'
                        ]
                    )
                )
                : 0;

        if ($editId > 0) {
            $editingBanner =
                $repository->findById(
                    $editId
                );
        }

        $areas =
            apply_filters(
                'dsm_location_areas',
                [],
                null,
                'island'
            );

        if (!is_array($areas)) {
            $areas = [];
        }

        require DSM_PUBLICIDAD_PATH
            . 'templates/admin/banners.php';
    }

    public static function handleSave(): never
    {
        try {
            if (
                !current_user_can(
                    'manage_options'
                )
            ) {
                throw new RuntimeException(
                    'No tienes permisos para gestionar publicidad.'
                );
            }

            check_admin_referer(
                self::SAVE_ACTION,
                self::NONCE_FIELD
            );

            $bannerId =
                isset($_POST['banner_id'])
                    ? absint(
                        wp_unslash(
                            (string) $_POST[
                                'banner_id'
                            ]
                        )
                    )
                    : 0;

            $title =
                isset($_POST['title'])
                    ? sanitize_text_field(
                        wp_unslash(
                            (string) $_POST[
                                'title'
                            ]
                        )
                    )
                    : '';

            $imageAttachmentId =
                isset($_POST['image_attachment_id'])
                    ? absint(
                        wp_unslash(
                            (string) $_POST[
                                'image_attachment_id'
                            ]
                        )
                    )
                    : 0;

            $targetUrl =
                isset($_POST['target_url'])
                    ? esc_url_raw(
                        wp_unslash(
                            (string) $_POST[
                                'target_url'
                            ]
                        )
                    )
                    : '';

            $areaId =
                isset($_POST['area_id'])
                    ? absint(
                        wp_unslash(
                            (string) $_POST[
                                'area_id'
                            ]
                        )
                    )
                    : 0;

            $priority =
                isset($_POST['priority'])
                    ? (int) wp_unslash(
                        (string) $_POST[
                            'priority'
                        ]
                    )
                    : 0;

            $status =
                isset($_POST['status'])
                    ? sanitize_key(
                        wp_unslash(
                            (string) $_POST[
                                'status'
                            ]
                        )
                    )
                    : 'draft';

            $startsAt =
                self::normalizeDateTime(
                    $_POST['starts_at']
                    ?? null
                );

            $endsAt =
                self::normalizeDateTime(
                    $_POST['ends_at']
                    ?? null
                );

            $repository =
                new AdvertisingBannerRepository();

            if ($bannerId > 0) {
                $repository->update(
                    $bannerId,
                    $title,
                    $imageAttachmentId,
                    $targetUrl,
                    $areaId > 0
                        ? $areaId
                        : null,
                    $priority,
                    $status,
                    $startsAt,
                    $endsAt
                );

                self::redirect(
                    'updated'
                );
            }

            $repository->create(
                $title,
                $imageAttachmentId,
                $targetUrl,
                $areaId > 0
                    ? $areaId
                    : null,
                $priority,
                $status,
                $startsAt,
                $endsAt
            );

            self::redirect(
                'created'
            );
        } catch (Throwable $exception) {
            self::redirect(
                'error',
                $exception->getMessage()
            );
        }
    }

    public static function handleDelete(): never
    {
        try {
            if (
                !current_user_can(
                    'manage_options'
                )
            ) {
                throw new RuntimeException(
                    'No tienes permisos para eliminar publicidad.'
                );
            }

            $bannerId =
                isset($_POST['banner_id'])
                    ? absint(
                        wp_unslash(
                            (string) $_POST[
                                'banner_id'
                            ]
                        )
                    )
                    : 0;

            check_admin_referer(
                self::DELETE_ACTION
                . '_'
                . $bannerId,
                self::NONCE_FIELD
            );

            $repository =
                new AdvertisingBannerRepository();

            $repository->delete(
                $bannerId
            );

            self::redirect(
                'deleted'
            );
        } catch (Throwable $exception) {
            self::redirect(
                'error',
                $exception->getMessage()
            );
        }
    }

    private static function normalizeDateTime(
        mixed $value
    ): ?string {
        if ($value === null) {
            return null;
        }

        $value =
            trim(
                sanitize_text_field(
                    wp_unslash(
                        (string) $value
                    )
                )
            );

        if ($value === '') {
            return null;
        }

        $timestamp =
            strtotime(
                $value
            );

        if ($timestamp === false) {
            throw new RuntimeException(
                'Una de las fechas no es válida.'
            );
        }

        return gmdate(
            'Y-m-d H:i:s',
            $timestamp
        );
    }

    private static function redirect(
        string $notice,
        string $error = ''
    ): never {
        $arguments = [
            'page' =>
                'dsm-publicidad',

            'dsm_publicidad_notice' =>
                $notice,
        ];

        if ($error !== '') {
            $arguments[
                'dsm_publicidad_error'
            ] =
                $error;
        }

        wp_safe_redirect(
            add_query_arg(
                $arguments,
                admin_url(
                    'admin.php'
                )
            )
        );

        exit;
    }

    private function __construct()
    {
    }
}
