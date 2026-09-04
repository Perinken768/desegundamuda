<?php

declare(strict_types=1);

namespace DSM\Facturacion\Frontend;

use DSM\Clientes\Authentication\AuthenticatedCustomer;
use DSM\Clientes\Authentication\CustomerSessionRepository;
use DSM\Clientes\Customer\CustomerRepository;
use DSM\Clientes\Impersonation\CustomerImpersonationCookie;
use DSM\Facturacion\Billing\BillingProfileRepository;
use DSM\Facturacion\Billing\SpanishTaxIdValidator;
use RuntimeException;
use Throwable;

if (!defined('ABSPATH')) {
    exit;
}

final class BillingProfileController
{
    private const ACTION =
        'dsm_facturacion_save_billing_profile';

    private const NONCE_ACTION =
        'dsm_facturacion_save_billing_profile';

    private const NONCE_NAME =
        'dsm_facturacion_billing_profile_nonce';

    public static function register(): void
    {
        add_action(
            'admin_post_nopriv_'
                . self::ACTION,
            [
                self::class,
                'handleSave',
            ]
        );

        add_action(
            'admin_post_'
                . self::ACTION,
            [
                self::class,
                'handleSave',
            ]
        );
    }

    public static function handleSave(): never
    {
        check_admin_referer(
            self::NONCE_ACTION,
            self::NONCE_NAME
        );

        try {
            if (
                class_exists(
                    CustomerImpersonationCookie::class
                )
                && CustomerImpersonationCookie::isActive()
            ) {
                throw new RuntimeException(
                    'No puedes modificar datos fiscales durante una sesión administrativa temporal.'
                );
            }

            $authenticatedCustomer =
                new AuthenticatedCustomer(
                    new CustomerSessionRepository(),
                    new CustomerRepository()
                );

            $customer =
                $authenticatedCustomer->resolve();

            if ($customer === null) {
                self::redirectToLogin();
            }

            $customerType =
                isset(
                    $_POST['customer_type']
                )
                    ? sanitize_key(
                        wp_unslash(
                            (string) $_POST['customer_type']
                        )
                    )
                    : 'individual';

            if (
                !in_array(
                    $customerType,
                    [
                        'individual',
                        'business',
                    ],
                    true
                )
            ) {
                throw new RuntimeException(
                    'El tipo de cliente fiscal no es válido.'
                );
            }

            $fiscalName =
                self::postedText(
                    'fiscal_name'
                );

            if ($fiscalName === '') {
                throw new RuntimeException(
                    'Debes indicar el nombre o razón social.'
                );
            }

            $countryCode =
                strtoupper(
                    self::postedText(
                        'country_code'
                    )
                );

            if (
                strlen($countryCode) !== 2
                || !ctype_alpha(
                    $countryCode
                )
            ) {
                throw new RuntimeException(
                    'El código de país no es válido.'
                );
            }

            $taxId =
                SpanishTaxIdValidator::normalize(
                    self::postedText(
                        'tax_id'
                    )
                );

            if ($countryCode === 'ES') {
                if ($taxId === '') {
                    self::redirect(
                        'tax_id_required'
                    );
                }

                if (
                    !SpanishTaxIdValidator::isValid(
                        $taxId
                    )
                ) {
                    self::redirect(
                        'invalid_tax_id'
                    );
                }
            }

            $billingEmail =
                isset(
                    $_POST['billing_email']
                )
                    ? sanitize_email(
                        wp_unslash(
                            (string) $_POST['billing_email']
                        )
                    )
                    : '';

            $repository =
                new BillingProfileRepository();

            $repository->save(
                $customer->getId(),
                [
                    'customer_type' =>
                        $customerType,

                    'fiscal_name' =>
                        $fiscalName,

                    'tax_id' =>
                        $taxId,

                    'address_line_1' =>
                        self::postedText(
                            'address_line_1'
                        ),

                    'address_line_2' =>
                        self::postedText(
                            'address_line_2'
                        ),

                    'postal_code' =>
                        self::postedText(
                            'postal_code'
                        ),

                    'city' =>
                        self::postedText(
                            'city'
                        ),

                    'province' =>
                        self::postedText(
                            'province'
                        ),

                    'country_code' =>
                        $countryCode,

                    'billing_email' =>
                        $billingEmail,
                ]
            );

            self::redirect(
                'saved'
            );
        } catch (Throwable $exception) {
            error_log(
                '[DSM Facturación] No se pudieron guardar los datos fiscales: '
                . $exception->getMessage()
            );

            self::redirect(
                'error'
            );
        }
    }

    public static function getAction(): string
    {
        return self::ACTION;
    }

    public static function getNonceAction(): string
    {
        return self::NONCE_ACTION;
    }

    public static function getNonceName(): string
    {
        return self::NONCE_NAME;
    }

    private static function postedText(
        string $field
    ): string {
        if (!isset($_POST[$field])) {
            return '';
        }

        return trim(
            sanitize_text_field(
                wp_unslash(
                    (string) $_POST[$field]
                )
            )
        );
    }

    private static function redirect(
        string $status
    ): never {
        wp_safe_redirect(
            add_query_arg(
                'billing_status',
                $status,
                home_url(
                    '/datos-fiscales/'
                )
            )
        );

        exit;
    }

    private static function redirectToLogin(): never
    {
        wp_safe_redirect(
            add_query_arg(
                'redirect_to',
                home_url(
                    '/datos-fiscales/'
                ),
                home_url(
                    '/iniciar-sesion/'
                )
            )
        );

        exit;
    }

    private function __construct()
    {
    }
}
