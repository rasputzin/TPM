<?php

declare(strict_types=1);

namespace TpmPsych\Admin;

use TpmPsych\Authorization\Capabilities;

/**
 * Montează SPA-ul React (admin-app/dist/) într-o pagină proprie din
 * wp-admin. Nu depinde de Divi — funcționează identic indiferent de tema
 * activă, conform principiului de cuplare slabă cu prezentarea.
 */
final class AdminPage
{
    private const MENU_SLUG = 'tpm-psych-platform';

    private const SCRIPT_HANDLE = 'tpm-psych-admin-app';

    private static ?string $hookSuffix = null;

    public static function register(): void
    {
        add_action('admin_menu', [self::class, 'registerMenu']);
        add_action('admin_enqueue_scripts', [self::class, 'enqueueAssets']);
        add_filter('script_loader_tag', [self::class, 'renderAsModule'], 10, 2);
    }

    public static function registerMenu(): void
    {
        self::$hookSuffix = add_menu_page(
            __('TPM Psych Platform', 'tpm-psych-platform'),
            __('Testare psihologică', 'tpm-psych-platform'),
            Capabilities::MANAGE_INSTRUMENTS,
            self::MENU_SLUG,
            [self::class, 'render'],
            'dashicons-clipboard',
            30
        );
    }

    public static function render(): void
    {
        echo '<div id="tpm-admin-root"></div>';
    }

    public static function enqueueAssets(string $hook): void
    {
        if ($hook !== self::$hookSuffix) {
            return;
        }

        $distDir = TPM_PSYCH_PLATFORM_DIR . '/admin-app/dist';
        $distUrl = plugins_url('admin-app/dist', TPM_PSYCH_PLATFORM_FILE);

        if (! file_exists($distDir . '/admin-app.js')) {
            echo '<div class="notice notice-error"><p>'
                . esc_html__('Interfața admin nu e construită. Rulează "npm install && npm run build" în admin-app/.', 'tpm-psych-platform')
                . '</p></div>';

            return;
        }

        wp_enqueue_style('tpm-psych-admin-app', $distUrl . '/admin-app.css', [], TPM_PSYCH_PLATFORM_VERSION);
        wp_enqueue_script(self::SCRIPT_HANDLE, $distUrl . '/admin-app.js', [], TPM_PSYCH_PLATFORM_VERSION, true);

        wp_localize_script(self::SCRIPT_HANDLE, 'tpmPsychAdmin', [
            'restRoot' => esc_url_raw(rest_url()),
            'nonce' => wp_create_nonce('wp_rest'),
        ]);
    }

    /**
     * Build-ul Vite produce un modul ES; WP nu suportă nativ atributul
     * `type="module"` la `wp_enqueue_script()` pe versiunea minimă declarată
     * de plugin (6.0), deci îl adăugăm explicit doar pentru handle-ul nostru.
     */
    public static function renderAsModule(string $tag, string $handle): string
    {
        if ($handle !== self::SCRIPT_HANDLE) {
            return $tag;
        }

        return str_replace(' src=', ' type="module" src=', $tag);
    }
}
