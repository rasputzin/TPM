<?php

declare(strict_types=1);

namespace TpmPsych;

use TpmPsych\Admin\AdminPage;
use TpmPsych\Authorization\Capabilities;
use TpmPsych\Http\RestRouteRegistrar;
use TpmPsych\Migrations\MigrationRunner;

final class Plugin
{
    private static ?Plugin $instance = null;

    public static function instance(): self
    {
        return self::$instance ??= new self();
    }

    private function __construct()
    {
    }

    public function activate(): void
    {
        (new MigrationRunner())->runPending();
        Capabilities::register();
    }

    public function deactivate(): void
    {
        // Dezactivarea nu șterge date și nu retrage capabilities existente —
        // reactivarea trebuie să găsească exact aceeași stare. Ștergerea
        // efectivă se face doar din uninstall.php, sub confirmare explicită.
    }

    public function boot(): void
    {
        add_action('init', static function (): void {
            load_plugin_textdomain('tpm-psych-platform', false, dirname(plugin_basename(TPM_PSYCH_PLATFORM_FILE)) . '/languages');
        });

        add_action('rest_api_init', static function (): void {
            (new RestRouteRegistrar())->register();
        });

        AdminPage::register();
    }
}
