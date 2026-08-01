<?php

declare(strict_types=1);

namespace DSM\Mail\Admin;

use DSM\Core\Mail\MailerRegistry;
use DSM\Mail\Config\MailSettingsRepository;
use RuntimeException;
use Throwable;

if (!defined('ABSPATH')) {
    exit;
}

final class MailTestController
{
    private const ERROR_TRANSIENT_PREFIX =
        'dsm_mail_test_error_';

    public function __construct(
        private readonly MailSettingsRepository $repository
    ) {
    }

    public function register(): void
    {
        add_action(
            'admin_post_dsm_mail_send_test',
            [$this, 'handle']
        );
    }

    public function handle(): void
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
            'dsm_mail_send_test',
            'dsm_mail_test_nonce'
        );

        $recipient = sanitize_email(
            wp_unslash(
                $_POST['test_email'] ?? ''
            )
        );

        $status = 'test_error';

        try {
            if (!is_email($recipient)) {
                throw new RuntimeException(
                    'El destinatario de prueba no es válido.'
                );
            }

            $settings = $this->repository->get();

            if (!$settings->isComplete()) {
                throw new RuntimeException(
                    'La configuración SMTP no está completa.'
                );
            }

            MailerRegistry::get()->send(
                $recipient,
                'Prueba SMTP de DeSegundaMuda',
                "La configuración de DSM Mail funciona correctamente.\n\n"
                . 'Este correo se ha enviado desde el panel de WordPress.'
            );

            $status = 'test_sent';

            delete_transient(
                self::ERROR_TRANSIENT_PREFIX
                . get_current_user_id()
            );
        } catch (Throwable $exception) {
            $errorMessage = sanitize_text_field(
                $exception->getMessage()
            );

            set_transient(
                self::ERROR_TRANSIENT_PREFIX
                . get_current_user_id(),
                $errorMessage,
                5 * MINUTE_IN_SECONDS
            );

            error_log(
                '[DSM Mail] Falló la prueba SMTP: '
                . $errorMessage
            );
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

    public static function getLastError(): string
    {
        $error = get_transient(
            self::ERROR_TRANSIENT_PREFIX
            . get_current_user_id()
        );

        return is_string($error)
            ? $error
            : '';
    }
}