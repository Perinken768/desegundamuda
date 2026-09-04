<?php

declare(strict_types=1);

namespace DSM\Facturacion\Billing;

if (!defined('ABSPATH')) {
    exit;
}

final class BillingSettings
{
    public const OPTION_NAME =
        'dsm_facturacion_settings';

    /**
     * @return array<string, mixed>
     */
    public static function get(): array
    {
        $stored =
            get_option(
                self::OPTION_NAME,
                []
            );

        $stored =
            is_array($stored)
                ? $stored
                : [];

        return array_merge(
            self::defaults(),
            $stored
        );
    }

    /**
     * @return array<string, mixed>
     */
    public static function defaults(): array
    {
        return [
            'fiscal_name' =>
                '',

            'tax_id' =>
                '',

            'address_line_1' =>
                '',

            'address_line_2' =>
                '',

            'postal_code' =>
                '',

            'city' =>
                '',

            'province' =>
                '',

            'country_code' =>
                'ES',

            'email' =>
                '',

            'series' =>
                'DSM',

            'tax_type' =>
                'IGIC',

            'tax_rate' =>
                '7.0000',

            'prices_include_tax' =>
                1,

            'logo_attachment_id' =>
                0,

            'footer_text' =>
                '',
        ];
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function save(
        array $data
    ): void {
        update_option(
            self::OPTION_NAME,
            $data,
            false
        );
    }

    public static function isReady(): bool
    {
        $settings =
            self::get();

        return trim(
            (string) $settings[
                'fiscal_name'
            ]
        ) !== ''
            && trim(
                (string) $settings[
                    'tax_id'
                ]
            ) !== ''
            && trim(
                (string) $settings[
                    'address_line_1'
                ]
            ) !== ''
            && trim(
                (string) $settings[
                    'postal_code'
                ]
            ) !== ''
            && trim(
                (string) $settings[
                    'city'
                ]
            ) !== ''
            && trim(
                (string) $settings[
                    'province'
                ]
            ) !== '';
    }

    private function __construct()
    {
    }
}
