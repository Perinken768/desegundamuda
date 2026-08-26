<?php

declare(strict_types=1);

namespace DSM\Cookies;

use DSM\Cookies\Frontend\ConsentBanner;
use DSM\Cookies\Frontend\ConsentController;
use DSM\Cookies\Integration\PublicConsentIntegration;

if (!defined('ABSPATH')) {
    exit;
}

final class Plugin
{
    public static function register(): void
    {
        ConsentController::register();

        ConsentBanner::register();

        PublicConsentIntegration::register();
    }

    private function __construct()
    {
    }
}
