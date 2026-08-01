<?php
/**
 * Plugin Name: DSM Mail
 * Description: Infraestructura de correo de la plataforma DeSegundaMuda.
 * Version: 0.1.0
 * Author: DeSegundaMuda
 * Text Domain: dsm-mail
 * Requires Plugins: dsm-core
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

define('DSM_MAIL_VERSION', '0.1.0');
define('DSM_MAIL_PATH', plugin_dir_path(__FILE__));
define('DSM_MAIL_URL', plugin_dir_url(__FILE__));

require_once DSM_MAIL_PATH . 'src/Support/Autoloader.php';

use DSM\Core\Mail\MailerRegistry;
use DSM\Mail\Mail\WordPressMailer;
use DSM\Mail\Support\Autoloader;

Autoloader::register();

add_action(
    'plugins_loaded',
    static function (): void {
        MailerRegistry::set(
            new WordPressMailer()
        );
    },
    20
);