<?php

declare(strict_types=1);

namespace DSM\Mail\Admin;

use DSM\Mail\Config\MailSettingsRepository;
use Throwable;

if (!defined('ABSPATH')) {
    exit;
}

final class MailSettingsPage
{
    public function __construct(
        private readonly MailSettingsRepository $repository
    ) {
    }

    public function register(): void
    {
        add_action(
            'admin_menu',
            [$this, 'registerMenu']
        );

        add_action(
            'admin_post_dsm_mail_save_settings',
            [$this, 'handleSave']
        );
    }

    public function registerMenu(): void
    {
        add_options_page(
            'DSM Mail',
            'DSM Mail',
            'manage_options',
            'dsm-mail',
            [$this, 'render']
        );
    }

    public function render(): void
    {
        if (!current_user_can('manage_options')) {
            wp_die(
                esc_html__(
                    'No tienes permisos para acceder a esta página.',
                    'dsm-mail'
                )
            );
        }

        $settings = $this->repository->get();

        $hasStoredPassword =
            $this->repository->hasStoredPassword();

        $templateFile = DSM_MAIL_PATH
            . 'templates/admin/settings-page.php';

        require $templateFile;
    }

    public function handleSave(): void
    {
        if (!current_user_can('manage_options')) {
            wp_die(
                esc_html__(
                    'No tienes permisos para realizar esta acción.',
                    'dsm-mail'
                )
            );
        }

        check_admin_referer(
            'dsm_mail_save_settings',
            'dsm_mail_nonce'
        );

        try {
            $this->repository->save(
                [
                    'enabled' =>
                        isset($_POST['enabled']),
                    'host' =>
                        sanitize_text_field(
                            wp_unslash(
                                $_POST['host'] ?? ''
                            )
                        ),
                    'port' =>
                        (int) ($_POST['port'] ?? 587),
                    'encryption' =>
                        sanitize_key(
                            wp_unslash(
                                $_POST['encryption'] ?? 'tls'
                            )
                        ),
                    'authentication_enabled' =>
                        isset($_POST['authentication_enabled']),
                    'username' =>
                        sanitize_text_field(
                            wp_unslash(
                                $_POST['username'] ?? ''
                            )
                        ),
                    'password' =>
                        (string) wp_unslash(
                            $_POST['password'] ?? ''
                        ),
                    'from_email' =>
                        sanitize_email(
                            wp_unslash(
                                $_POST['from_email'] ?? ''
                            )
                        ),
                    'from_name' =>
                        sanitize_text_field(
                            wp_unslash(
                                $_POST['from_name'] ?? ''
                            )
                        ),
                ]
            );

            $status = 'saved';
        } catch (Throwable $exception) {
            error_log(
                '[DSM Mail] Error guardando configuración: '
                . $exception->getMessage()
            );

            $status = 'error';
        }

        wp_safe_redirect(
            add_query_arg(
                'dsm_mail_status',
                $status,
                admin_url(
                    'options-general.php?page=dsm-mail'
                )
            )
        );

        exit;
    }
}