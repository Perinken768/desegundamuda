<?php

declare(strict_types=1);

namespace DSM\Facturacion\Billing;

if (!defined('ABSPATH')) {
    exit;
}

final class SpanishTaxIdValidator
{
    private const CONTROL_LETTERS =
        'TRWAGMYFPDXBNJZSQVHLCKE';

    public static function normalize(
        string $taxId
    ): string {
        $taxId =
            strtoupper(
                trim(
                    $taxId
                )
            );

        return preg_replace(
            '/[\s\-\.]+/',
            '',
            $taxId
        ) ?? '';
    }

    public static function isValid(
        string $taxId
    ): bool {
        $taxId =
            self::normalize(
                $taxId
            );

        if (strlen($taxId) !== 9) {
            return false;
        }

        if (self::isDni($taxId)) {
            return self::validateDni(
                $taxId
            );
        }

        if (self::isNie($taxId)) {
            return self::validateNie(
                $taxId
            );
        }

        if (self::isLegalEntityNif($taxId)) {
            return self::validateLegalEntityNif(
                $taxId
            );
        }

        return false;
    }

    private static function isDni(
        string $taxId
    ): bool {
        return preg_match(
            '/^[0-9]{8}[A-Z]$/',
            $taxId
        ) === 1;
    }

    private static function validateDni(
        string $taxId
    ): bool {
        $number =
            (int) substr(
                $taxId,
                0,
                8
            );

        $expectedLetter =
            self::CONTROL_LETTERS[
                $number % 23
            ];

        return $taxId[8]
            === $expectedLetter;
    }

    private static function isNie(
        string $taxId
    ): bool {
        return preg_match(
            '/^[XYZ][0-9]{7}[A-Z]$/',
            $taxId
        ) === 1;
    }

    private static function validateNie(
        string $taxId
    ): bool {
        $prefixMap = [
            'X' => '0',
            'Y' => '1',
            'Z' => '2',
        ];

        $numeric =
            $prefixMap[$taxId[0]]
            . substr(
                $taxId,
                1,
                7
            );

        $number =
            (int) $numeric;

        $expectedLetter =
            self::CONTROL_LETTERS[
                $number % 23
            ];

        return $taxId[8]
            === $expectedLetter;
    }

    private static function isLegalEntityNif(
        string $taxId
    ): bool {
        return preg_match(
            '/^[ABCDEFGHJNPQRSUVW][0-9]{7}[0-9A-J]$/',
            $taxId
        ) === 1;
    }

    private static function validateLegalEntityNif(
        string $taxId
    ): bool {
        $prefix =
            $taxId[0];

        $digits =
            substr(
                $taxId,
                1,
                7
            );

        $sum =
            0;

        for ($index = 0; $index < 7; $index++) {
            $digit =
                (int) $digits[$index];

            if ($index % 2 === 0) {
                $product =
                    $digit * 2;

                $sum +=
                    intdiv(
                        $product,
                        10
                    )
                    + (
                        $product % 10
                    );
            } else {
                $sum +=
                    $digit;
            }
        }

        $controlNumber =
            (10 - ($sum % 10))
            % 10;

        $controlLetters =
            'JABCDEFGHI';

        $expectedDigit =
            (string) $controlNumber;

        $expectedLetter =
            $controlLetters[
                $controlNumber
            ];

        $actualControl =
            $taxId[8];

        /*
         * Determinadas entidades utilizan obligatoriamente
         * letra como carácter de control.
         */
        if (
            in_array(
                $prefix,
                [
                    'K',
                    'P',
                    'Q',
                    'S',
                ],
                true
            )
        ) {
            return $actualControl
                === $expectedLetter;
        }

        /*
         * Las sociedades A, B, E y H utilizan
         * normalmente control numérico.
         */
        if (
            in_array(
                $prefix,
                [
                    'A',
                    'B',
                    'E',
                    'H',
                ],
                true
            )
        ) {
            return $actualControl
                === $expectedDigit;
        }

        /*
         * Para el resto de tipos admitimos el carácter
         * de control numérico o alfabético calculado.
         */
        return $actualControl
            === $expectedDigit
            || $actualControl
                === $expectedLetter;
    }

    private function __construct()
    {
    }
}
