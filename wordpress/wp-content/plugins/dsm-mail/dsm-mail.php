<?php
/**
 * Plugin Name: DSM Mail
 * Description: Infraestructura de correo de la plataforma DeSegundaMuda.
 * Version: 0.2.0
 * Author: DeSegundaMuda
 * Text Domain: dsm-mail
 * Requires Plugins: dsm-core
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

define('DSM_MAIL_VERSION', '0.2.0');
define('DSM_MAIL_PATH', plugin_dir_path(__FILE__));
define('DSM_MAIL_URL', plugin_dir_url(__FILE__));

require_once DSM_MAIL_PATH . 'src/Support/Autoloader.php';

use DSM\Core\Mail\MailerRegistry;
use DSM\Mail\Admin\MailSettingsPage;
use DSM\Mail\Admin\MailTestController;
use DSM\Mail\Config\MailSettingsRepository;
use DSM\Mail\Mail\WordPressMailer;
use DSM\Mail\Security\SecretCipher;
use DSM\Mail\Smtp\SmtpConfigurator;
use DSM\Mail\Support\Autoloader;

Autoloader::register();

$cipher = new SecretCipher();

$settingsRepository = new MailSettingsRepository(
    $cipher
);

MailerRegistry::set(
    new WordPressMailer()
);

$settings = $settingsRepository->get();

if ($settings->isComplete()) {
    $smtpConfigurator = new SmtpConfigurator(
        $settings
    );

    $smtpConfigurator->register();
}

$mailSettingsPage = new MailSettingsPage(
    $settingsRepository
);

$mailSettingsPage->register();

$mailTestController = new MailTestController(
    $settingsRepository
);

$mailTestController->register();

add_action(
    'wp_mail_failed',
    static function (WP_Error $error): void {
        error_log(
            '[DSM Mail] Falló un envío: '
            . $error->get_error_message()
        );
    }
);