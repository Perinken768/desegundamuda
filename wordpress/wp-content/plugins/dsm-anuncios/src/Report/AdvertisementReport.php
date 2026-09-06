<?php

declare(strict_types=1);

namespace DSM\Anuncios\Report;

if (!defined('ABSPATH')) {
    exit;
}

final class AdvertisementReport
{
    public const STATUS_PENDING =
        'pending';

    public const STATUS_REVIEWING =
        'reviewing';

    public const STATUS_DISMISSED =
        'dismissed';

    public const STATUS_ACTIONED =
        'actioned';


    public const REASON_PROHIBITED_ITEM =
        'prohibited_item';

    public const REASON_POSSIBLE_FRAUD =
        'possible_fraud';

    public const REASON_DUPLICATE_SPAM =
        'duplicate_spam';

    public const REASON_FALSE_INFORMATION =
        'false_information';

    public const REASON_INAPPROPRIATE_CONTENT =
        'inappropriate_content';

    public const REASON_OTHER =
        'other';


    /**
     * @return array<int, string>
     */
    public static function statuses(): array
    {
        return [
            self::STATUS_PENDING,
            self::STATUS_REVIEWING,
            self::STATUS_DISMISSED,
            self::STATUS_ACTIONED,
        ];
    }

    /**
     * @return array<int, string>
     */
    public static function reasons(): array
    {
        return [
            self::REASON_PROHIBITED_ITEM,
            self::REASON_POSSIBLE_FRAUD,
            self::REASON_DUPLICATE_SPAM,
            self::REASON_FALSE_INFORMATION,
            self::REASON_INAPPROPRIATE_CONTENT,
            self::REASON_OTHER,
        ];
    }

    public static function isValidStatus(
        string $status
    ): bool {
        return in_array(
            sanitize_key($status),
            self::statuses(),
            true
        );
    }

    public static function isValidReason(
        string $reason
    ): bool {
        return in_array(
            sanitize_key($reason),
            self::reasons(),
            true
        );
    }

    public static function getReasonLabel(
        string $reason
    ): string {
        return match (
            sanitize_key($reason)
        ) {
            self::REASON_PROHIBITED_ITEM =>
                'Artículo prohibido',

            self::REASON_POSSIBLE_FRAUD =>
                'Posible fraude',

            self::REASON_DUPLICATE_SPAM =>
                'Anuncio duplicado o spam',

            self::REASON_FALSE_INFORMATION =>
                'Información falsa o engañosa',

            self::REASON_INAPPROPRIATE_CONTENT =>
                'Contenido inapropiado',

            self::REASON_OTHER =>
                'Otro motivo',

            default =>
                'Motivo desconocido',
        };
    }

    private function __construct()
    {
    }
}
