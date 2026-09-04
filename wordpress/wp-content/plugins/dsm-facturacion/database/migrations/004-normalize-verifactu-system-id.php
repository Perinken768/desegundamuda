<?php

declare(strict_types=1);

use DSM\Facturacion\Verifactu\VerifactuSettings;

if (!defined('ABSPATH')) {
    exit;
}

return static function (): void {
    $stored =
        get_option(
            VerifactuSettings::OPTION_NAME,
            []
        );

    $stored =
        is_array($stored)
            ? $stored
            : [];

    $currentSystemId =
        strtoupper(
            trim(
                (string) (
                    $stored['system_id']
                    ?? ''
                )
            )
        );

    /*
     * Los registros VERI*FACTU ya generados no se
     * modifican aquí.
     *
     * Únicamente corregimos la configuración utilizada
     * para futuras cadenas/registros.
     */
    if (
        $currentSystemId === ''
        || strlen($currentSystemId) > 2
        || preg_match(
            '/^[A-Z0-9]{1,2}$/',
            $currentSystemId
        ) !== 1
    ) {
        $stored['system_id'] =
            VerifactuSettings::DEFAULT_SYSTEM_ID;
    }

    VerifactuSettings::save(
        $stored
    );
};
