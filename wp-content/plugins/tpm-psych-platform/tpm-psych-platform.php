<?php
/**
 * Plugin Name: TPM Psych Platform
 * Description: Platformă profesională de testare psihologică (administrare instrumente, cotare server-side, rapoarte validate de psiholog).
 * Version: 0.1.0
 * Requires PHP: 8.1
 * Requires at least: 6.0
 * Author: TPM
 * Text Domain: tpm-psych-platform
 */

declare(strict_types=1);

if (! defined('ABSPATH')) {
    exit;
}

define('TPM_PSYCH_PLATFORM_VERSION', '0.1.0');
define('TPM_PSYCH_PLATFORM_FILE', __FILE__);
define('TPM_PSYCH_PLATFORM_DIR', __DIR__);

$tpm_autoload = __DIR__ . '/vendor/autoload.php';
if (! file_exists($tpm_autoload)) {
    add_action('admin_notices', static function (): void {
        echo '<div class="notice notice-error"><p>';
        echo esc_html__('TPM Psych Platform: rulează "composer install" în folderul pluginului înainte de activare.', 'tpm-psych-platform');
        echo '</p></div>';
    });
    return;
}
require_once $tpm_autoload;

register_activation_hook(__FILE__, static function (): void {
    \TpmPsych\Plugin::instance()->activate();
});

register_deactivation_hook(__FILE__, static function (): void {
    \TpmPsych\Plugin::instance()->deactivate();
});

add_action('plugins_loaded', static function (): void {
    \TpmPsych\Plugin::instance()->boot();
});
