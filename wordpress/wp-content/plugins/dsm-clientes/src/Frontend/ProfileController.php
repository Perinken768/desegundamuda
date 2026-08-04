<?php

declare(strict_types=1);

namespace DSM\Clientes\Frontend;

use DSM\Clientes\Application\UpdateCustomerProfile;
use DSM\Clientes\Authentication\AuthenticatedCustomer;
use DSM\Clientes\Authentication\CustomerSessionRepository;
use DSM\Clientes\Customer\CustomerRepository;
use DSM\Clientes\Profile\CustomerProfileRepository;
use Throwable;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Procesa la actualización pública del perfil del cliente.
 *
 * Para el MVP:
 *
 * - phone es el único número de contacto;
 * - allow_phone_calls habilita las llamadas;
 * - allow_whatsapp habilita WhatsApp;
 * - whatsapp_phone permanece reservado y no se procesa.
 */
final class ProfileController
{
    public const ACTION =
        'dsm_customer_profile_update';

    public const NONCE_ACTION =
        'dsm_customer_profile_update';

    public const NONCE_FIELD =
        'dsm_profile_nonce';

    public static function register(): void
    {
        add_action(
            'admin_post_nopriv_'
            . self::ACTION,
            [
                self::class,
                'handleUpdate',
            ]
        );

        add_action(
            'admin_post_'
            . self::ACTION,
            [
                self::class,
                'handleUpdate',
            ]
        );
    }

    public static function handleUpdate(): void
    {
        check_admin_referer(
            self::NONCE_ACTION,
            self::NONCE_FIELD
        );

        $authenticatedCustomer =
            new AuthenticatedCustomer(
                new CustomerSessionRepository(),
                new CustomerRepository()
            );

        $customer =
            $authenticatedCustomer->resolve();

        if ($customer === null) {
            wp_safe_redirect(
                home_url(
                    '/iniciar-sesion/'
                )
            );

            exit;
        }

        $displayName =
            isset($_POST['display_name'])
                ? sanitize_text_field(
                    wp_unslash(
                        (string) $_POST[
                            'display_name'
                        ]
                    )
                )
                : '';

        $phone =
            isset($_POST['phone'])
                ? sanitize_text_field(
                    wp_unslash(
                        (string) $_POST['phone']
                    )
                )
                : null;

        /*
         * Las casillas no marcadas no se envían en POST.
         */
        $allowPhoneCalls =
            isset(
                $_POST[
                    'allow_phone_calls'
                ]
            )
            && self::normalizeCheckbox(
                $_POST[
                    'allow_phone_calls'
                ]
            );

        $allowWhatsapp =
            isset(
                $_POST[
                    'allow_whatsapp'
                ]
            )
            && self::normalizeCheckbox(
                $_POST[
                    'allow_whatsapp'
                ]
            );

        $bio =
            isset($_POST['bio'])
                ? sanitize_textarea_field(
                    wp_unslash(
                        (string) $_POST['bio']
                    )
                )
                : null;

        try {
            $service =
                new UpdateCustomerProfile(
                    new CustomerProfileRepository()
                );

            $service->execute(
                $customer->getId(),
                $displayName,
                $phone,
                $allowPhoneCalls,
                $allowWhatsapp,
                $bio
            );

            wp_safe_redirect(
                add_query_arg(
                    [
                        'profile_updated' =>
                            '1',
                    ],
                    home_url(
                        '/editar-perfil/'
                    )
                )
            );

            exit;
        } catch (Throwable $exception) {
            error_log(
                sprintf(
                    '[DSM Clientes] Error actualizando perfil del cliente %d: %s',
                    $customer->getId(),
                    $exception->getMessage()
                )
            );

            wp_safe_redirect(
                add_query_arg(
                    [
                        'profile_error' =>
                            'invalid_data',
                    ],
                    home_url(
                        '/editar-perfil/'
                    )
                )
            );

            exit;
        }
    }

    /**
     * Convierte los valores habituales de una casilla HTML
     * en un booleano seguro.
     */
    private static function normalizeCheckbox(
        mixed $value
    ): bool {
        $value =
            strtolower(
                trim(
                    (string) wp_unslash(
                        $value
                    )
                )
            );

        return in_array(
            $value,
            [
                '1',
                'true',
                'yes',
                'on',
            ],
            true
        );
    }

    private function __construct()
    {
    }
}