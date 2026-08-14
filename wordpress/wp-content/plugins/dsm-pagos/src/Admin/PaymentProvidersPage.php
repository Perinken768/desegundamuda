<?php

declare(strict_types=1);

namespace DSM\Pagos\Admin;

use DSM\Pagos\Provider\PaymentProviderManager;
use RuntimeException;
use Throwable;

if (!defined('ABSPATH')) {
    exit;
}

final class PaymentProvidersPage
{
    public const MENU_SLUG =
        'dsm-pagos';

    private const CAPABILITY =
        'manage_options';

    private const SAVE_ACTION =
        'dsm_payment_providers_save';

    private const NONCE_ACTION =
        'dsm_payment_providers_save';

    private const NONCE_NAME =
        'dsm_payment_providers_nonce';

    public static function register(): void
    {
        $page =
            new self();

        add_action(
            'admin_menu',
            [
                $page,
                'registerMenu',
            ],
            5
        );

        add_action(
            'admin_post_'
            . self::SAVE_ACTION,
            [
                $page,
                'handleSave',
            ]
        );
    }

    public function registerMenu(): void
    {
        add_menu_page(
            page_title:
                __(
                    'DSM Pagos',
                    'dsm-pagos'
                ),

            menu_title:
                __(
                    'DSM Pagos',
                    'dsm-pagos'
                ),

            capability:
                self::CAPABILITY,

            menu_slug:
                self::MENU_SLUG,

            callback:
                [
                    $this,
                    'render',
                ],

            icon_url:
                'dashicons-money-alt',

            position:
                28
        );

        add_submenu_page(
            parent_slug:
                self::MENU_SLUG,

            page_title:
                __(
                    'Proveedores de pago',
                    'dsm-pagos'
                ),

            menu_title:
                __(
                    'Proveedores',
                    'dsm-pagos'
                ),

            capability:
                self::CAPABILITY,

            menu_slug:
                self::MENU_SLUG,

            callback:
                [
                    $this,
                    'render',
                ]
        );
    }

    public function render(): void
    {
        $this->assertPermission();

        PaymentProviderManager::reset();

        $providers =
            PaymentProviderManager::registry()
                ->all();

        $settings =
            $this->getSettings();

        $notice =
            isset($_GET['notice'])
                ? sanitize_key(
                    wp_unslash(
                        (string) $_GET['notice']
                    )
                )
                : '';

        $error =
            isset($_GET['error'])
                ? sanitize_text_field(
                    wp_unslash(
                        (string) $_GET['error']
                    )
                )
                : '';

        $template =
            DSM_PAGOS_PATH
            . 'templates/admin/payment-providers.php';

        if (!is_file($template)) {
            throw new RuntimeException(
                'No se encontró la plantilla de proveedores de pago.'
            );
        }

        require $template;
    }

    public function handleSave(): void
    {
        $this->assertPermission();

        check_admin_referer(
            self::NONCE_ACTION,
            self::NONCE_NAME
        );

        try {
            $this->saveStripe();
            $this->saveRedsys();
            $this->savePayPal();

            PaymentProviderManager::reset();

            $this->redirect(
                [
                    'notice' =>
                        'saved',
                ]
            );
        } catch (Throwable $exception) {
            $this->redirect(
                [
                    'error' =>
                        $exception->getMessage(),
                ]
            );
        }
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    private function getSettings(): array
    {
        return [
            'stripe' => [
                'enabled' =>
                    (bool) get_option(
                        'dsm_pagos_provider_stripe_enabled',
                        false
                    ),

                'mode' =>
                    (string) get_option(
                        'dsm_pagos_provider_stripe_mode',
                        'test'
                    ),

                'has_secret_key' =>
                    trim(
                        (string) get_option(
                            'dsm_pagos_provider_stripe_secret_key',
                            ''
                        )
                    ) !== '',

                'has_webhook_secret' =>
                    trim(
                        (string) get_option(
                            'dsm_pagos_provider_stripe_webhook_secret',
                            ''
                        )
                    ) !== '',
            ],

            'redsys' => [
                'enabled' =>
                    (bool) get_option(
                        'dsm_pagos_provider_redsys_enabled',
                        false
                    ),

                'mode' =>
                    (string) get_option(
                        'dsm_pagos_provider_redsys_mode',
                        'test'
                    ),

                'merchant_code' =>
                    (string) get_option(
                        'dsm_pagos_provider_redsys_merchant_code',
                        ''
                    ),

                'terminal' =>
                    (string) get_option(
                        'dsm_pagos_provider_redsys_terminal',
                        '001'
                    ),

                'has_secret_key' =>
                    trim(
                        (string) get_option(
                            'dsm_pagos_provider_redsys_secret_key',
                            ''
                        )
                    ) !== '',
            ],

            'paypal' => [
                'enabled' =>
                    (bool) get_option(
                        'dsm_pagos_provider_paypal_enabled',
                        false
                    ),

                'mode' =>
                    (string) get_option(
                        'dsm_pagos_provider_paypal_mode',
                        'sandbox'
                    ),

                'client_id' =>
                    (string) get_option(
                        'dsm_pagos_provider_paypal_client_id',
                        ''
                    ),

                'webhook_id' =>
                    (string) get_option(
                        'dsm_pagos_provider_paypal_webhook_id',
                        ''
                    ),

                'has_client_secret' =>
                    trim(
                        (string) get_option(
                            'dsm_pagos_provider_paypal_client_secret',
                            ''
                        )
                    ) !== '',
            ],
        ];
    }

    private function saveStripe(): void
    {
        $enabled =
            isset(
                $_POST['stripe_enabled']
            );

        $mode =
            $this->getPostedChoice(
                'stripe_mode',
                [
                    'test',
                    'live',
                ],
                'test'
            );

        update_option(
            'dsm_pagos_provider_stripe_enabled',
            $enabled ? 1 : 0,
            false
        );

        update_option(
            'dsm_pagos_provider_stripe_mode',
            $mode,
            false
        );

        $this->saveOptionalSecret(
            'stripe_secret_key',
            'dsm_pagos_provider_stripe_secret_key'
        );

        $this->saveOptionalSecret(
            'stripe_webhook_secret',
            'dsm_pagos_provider_stripe_webhook_secret'
        );
    }

    private function saveRedsys(): void
    {
        $enabled =
            isset(
                $_POST['redsys_enabled']
            );

        $mode =
            $this->getPostedChoice(
                'redsys_mode',
                [
                    'test',
                    'live',
                ],
                'test'
            );

        $merchantCode =
            $this->getPostedText(
                'redsys_merchant_code'
            );

        $terminal =
            $this->getPostedText(
                'redsys_terminal'
            );

        update_option(
            'dsm_pagos_provider_redsys_enabled',
            $enabled ? 1 : 0,
            false
        );

        update_option(
            'dsm_pagos_provider_redsys_mode',
            $mode,
            false
        );

        update_option(
            'dsm_pagos_provider_redsys_merchant_code',
            $merchantCode,
            false
        );

        update_option(
            'dsm_pagos_provider_redsys_terminal',
            $terminal,
            false
        );

        $this->saveOptionalSecret(
            'redsys_secret_key',
            'dsm_pagos_provider_redsys_secret_key'
        );
    }

    private function savePayPal(): void
    {
        $enabled =
            isset(
                $_POST['paypal_enabled']
            );

        $mode =
            $this->getPostedChoice(
                'paypal_mode',
                [
                    'sandbox',
                    'live',
                ],
                'sandbox'
            );

        $clientId =
            $this->getPostedText(
                'paypal_client_id'
            );

        $webhookId =
            $this->getPostedText(
                'paypal_webhook_id'
            );

        update_option(
            'dsm_pagos_provider_paypal_enabled',
            $enabled ? 1 : 0,
            false
        );

        update_option(
            'dsm_pagos_provider_paypal_mode',
            $mode,
            false
        );

        update_option(
            'dsm_pagos_provider_paypal_client_id',
            $clientId,
            false
        );

        update_option(
            'dsm_pagos_provider_paypal_webhook_id',
            $webhookId,
            false
        );

        $this->saveOptionalSecret(
            'paypal_client_secret',
            'dsm_pagos_provider_paypal_client_secret'
        );
    }

    private function saveOptionalSecret(
        string $postField,
        string $optionName
    ): void {
        if (!isset($_POST[$postField])) {
            return;
        }

        $secret =
            trim(
                wp_unslash(
                    (string) $_POST[$postField]
                )
            );

        /*
         * Campo vacío:
         * conserva el secreto existente.
         */
        if ($secret === '') {
            return;
        }

        $secret =
            preg_replace(
                '/[\x00-\x1F\x7F]/',
                '',
                $secret
            );

        if (!is_string($secret)) {
            throw new RuntimeException(
                'No se pudo procesar una de las credenciales.'
            );
        }

        update_option(
            $optionName,
            $secret,
            false
        );
    }

    private function getPostedText(
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

    /**
     * @param array<int, string> $allowed
     */
    private function getPostedChoice(
        string $field,
        array $allowed,
        string $default
    ): string {
        $value =
            isset($_POST[$field])
                ? sanitize_key(
                    wp_unslash(
                        (string) $_POST[$field]
                    )
                )
                : $default;

        return in_array(
            $value,
            $allowed,
            true
        )
            ? $value
            : $default;
    }

    private function assertPermission(): void
    {
        if (
            !current_user_can(
                self::CAPABILITY
            )
        ) {
            wp_die(
                esc_html__(
                    'No tienes permisos para gestionar los proveedores de pago.',
                    'dsm-pagos'
                )
            );
        }
    }

    /**
     * @param array<string, scalar> $arguments
     */
    private function redirect(
        array $arguments = []
    ): never {
        $url =
            add_query_arg(
                $arguments,
                admin_url(
                    'admin.php?page='
                    . self::MENU_SLUG
                )
            );

        wp_safe_redirect(
            $url
        );

        exit;
    }

    public static function getSaveAction(): string
    {
        return self::SAVE_ACTION;
    }

    public static function getNonceAction(): string
    {
        return self::NONCE_ACTION;
    }

    public static function getNonceName(): string
    {
        return self::NONCE_NAME;
    }

    private function __construct()
    {
    }
}