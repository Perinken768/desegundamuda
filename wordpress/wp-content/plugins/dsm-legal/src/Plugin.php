<?php

declare(strict_types=1);

namespace DSM\Legal;

use DSM\Legal\Admin\LegalAdminPage;
use DSM\Legal\Admin\LegalSettings;
use DSM\Legal\Frontend\LegalDocumentController;
use DSM\Legal\Integration\FooterIntegration;

if (!defined('ABSPATH')) {
    exit;
}

final class Plugin
{
    public static function register(): void
    {
        LegalAdminPage::register();

        LegalSettings::register();

        LegalDocumentController::register();

        FooterIntegration::register();
    }

    private function __construct()
    {
    }
}
