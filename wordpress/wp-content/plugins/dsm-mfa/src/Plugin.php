<?php

declare(strict_types=1);

namespace DSM\Mfa;

use DSM\Mfa\Frontend\MfaChallengeController;
use DSM\Mfa\Integration\CustomerMfaIntegration;
use DSM\Mfa\Integration\WordPressMfaIntegration;

if (!defined('ABSPATH')) {
    exit;
}

final class Plugin
{
    public static function register(): void
    {
        CustomerMfaIntegration::register();

        WordPressMfaIntegration::register();

        MfaChallengeController::register();
    }

    private function __construct()
    {
    }
}
