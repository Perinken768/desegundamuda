<?php

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

return static function (): void {
    $migration =
        require DSM_PUBLICIDAD_PATH
        . 'database/migrations/'
        . '002-add-customer-id.php';

    if (is_callable($migration)) {
        $migration();
    }
};
