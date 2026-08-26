<?php

declare(strict_types=1);

namespace DSM\Legal\Document;

use DSM\Legal\Admin\LegalSettings;

if (!defined('ABSPATH')) {
    exit;
}

final class LegalContentResolver
{
    public static function resolve(
        string $content
    ): string {
        $replacements = [
            '{{owner_name}}' =>
                LegalSettings::get(
                    'owner_name'
                ),

            '{{tax_id}}' =>
                LegalSettings::get(
                    'tax_id'
                ),

            '{{trade_name}}' =>
                LegalSettings::get(
                    'trade_name'
                ),

            '{{registered_address}}' =>
                LegalSettings::get(
                    'registered_address'
                ),

            '{{contact_email}}' =>
                LegalSettings::get(
                    'contact_email'
                ),

            '{{registry_data}}' =>
                LegalSettings::get(
                    'registry_data'
                ),
        ];

        return strtr(
            $content,
            $replacements
        );
    }

    private function __construct()
    {
    }
}
