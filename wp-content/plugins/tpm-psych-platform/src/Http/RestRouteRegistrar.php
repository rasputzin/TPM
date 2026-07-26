<?php

declare(strict_types=1);

namespace TpmPsych\Http;

use TpmPsych\Http\Controllers\Admin\InstrumentsController;
use TpmPsych\Http\Controllers\Admin\InstrumentStructureController;
use TpmPsych\Http\Controllers\Admin\InstrumentVersionsController;
use TpmPsych\Http\Controllers\Admin\NormsController;
use TpmPsych\Http\Controllers\Admin\ReportTemplatesController;
use TpmPsych\Http\Controllers\Admin\ScalesController;
use TpmPsych\Http\Controllers\Admin\ScoringRulesController;

/**
 * Punct unic de înregistrare a rutelor REST — fiecare controller își
 * definește propriile rute și `permission_callback`; niciun endpoint din
 * acest checkpoint nu rămâne cu `__return_true` (toate cer capability
 * `tpm_manage_instruments`/`tpm_manage_templates`, verificat de WordPress
 * pe baza cookie-ului de autentificare + nonce trimis de admin SPA).
 */
final class RestRouteRegistrar
{
    public function register(): void
    {
        InstrumentsController::register();
        InstrumentVersionsController::register();
        InstrumentStructureController::register();
        ScalesController::register();
        ScoringRulesController::register();
        NormsController::register();
        ReportTemplatesController::register();
    }
}
