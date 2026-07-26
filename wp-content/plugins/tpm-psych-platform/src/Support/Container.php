<?php

declare(strict_types=1);

namespace TpmPsych\Support;

use TpmPsych\Repositories\Contracts\InstrumentRepositoryInterface;
use TpmPsych\Repositories\Contracts\InstrumentStructureRepositoryInterface;
use TpmPsych\Repositories\Contracts\InstrumentVersionRepositoryInterface;
use TpmPsych\Repositories\Contracts\NormRepositoryInterface;
use TpmPsych\Repositories\Contracts\ReportTemplateRepositoryInterface;
use TpmPsych\Repositories\Contracts\ScaleRepositoryInterface;
use TpmPsych\Repositories\Contracts\ScoringRuleRepositoryInterface;
use TpmPsych\Repositories\Wpdb\WpdbInstrumentRepository;
use TpmPsych\Repositories\Wpdb\WpdbInstrumentStructureRepository;
use TpmPsych\Repositories\Wpdb\WpdbInstrumentVersionRepository;
use TpmPsych\Repositories\Wpdb\WpdbNormRepository;
use TpmPsych\Repositories\Wpdb\WpdbReportTemplateRepository;
use TpmPsych\Repositories\Wpdb\WpdbScaleRepository;
use TpmPsych\Repositories\Wpdb\WpdbScoringRuleRepository;
use TpmPsych\Services\AuditService;
use TpmPsych\Services\InstrumentService;
use TpmPsych\Services\InstrumentStructureService;
use TpmPsych\Services\InstrumentVersionService;
use TpmPsych\Services\NormService;
use TpmPsych\Services\ReportTemplateService;
use TpmPsych\Services\ScaleService;
use TpmPsych\Services\ScoringRuleService;
use TpmPsych\Validation\FormulaDefinitionValidator;
use TpmPsych\Validation\NormRangeValidator;
use TpmPsych\Validation\ReportTemplateValidator;

/**
 * Wiring minimal de dependințe, centralizat într-un singur loc (nu în
 * fiecare controller). Nu e un framework DI complet — doar suficient cât să
 * păstreze Controllerele subțiri și Service-urile testabile independent
 * (testele unitare instanțiază Service-urile direct, cu dubluri de test, nu
 * prin acest container).
 */
final class Container
{
    private static array $instances = [];

    public static function instrumentRepository(): InstrumentRepositoryInterface
    {
        return self::$instances[InstrumentRepositoryInterface::class] ??= new WpdbInstrumentRepository();
    }

    public static function instrumentVersionRepository(): InstrumentVersionRepositoryInterface
    {
        return self::$instances[InstrumentVersionRepositoryInterface::class] ??= new WpdbInstrumentVersionRepository();
    }

    public static function instrumentStructureRepository(): InstrumentStructureRepositoryInterface
    {
        return self::$instances[InstrumentStructureRepositoryInterface::class] ??= new WpdbInstrumentStructureRepository();
    }

    public static function scaleRepository(): ScaleRepositoryInterface
    {
        return self::$instances[ScaleRepositoryInterface::class] ??= new WpdbScaleRepository();
    }

    public static function scoringRuleRepository(): ScoringRuleRepositoryInterface
    {
        return self::$instances[ScoringRuleRepositoryInterface::class] ??= new WpdbScoringRuleRepository();
    }

    public static function normRepository(): NormRepositoryInterface
    {
        return self::$instances[NormRepositoryInterface::class] ??= new WpdbNormRepository();
    }

    public static function reportTemplateRepository(): ReportTemplateRepositoryInterface
    {
        return self::$instances[ReportTemplateRepositoryInterface::class] ??= new WpdbReportTemplateRepository();
    }

    public static function auditService(): AuditService
    {
        return self::$instances[AuditService::class] ??= new AuditService();
    }

    public static function instrumentService(): InstrumentService
    {
        return self::$instances[InstrumentService::class] ??= new InstrumentService(
            self::instrumentRepository(),
            self::auditService(),
        );
    }

    public static function instrumentVersionService(): InstrumentVersionService
    {
        return self::$instances[InstrumentVersionService::class] ??= new InstrumentVersionService(
            self::instrumentVersionRepository(),
            self::instrumentStructureRepository(),
            self::scaleRepository(),
            self::scoringRuleRepository(),
            self::auditService(),
        );
    }

    public static function instrumentStructureService(): InstrumentStructureService
    {
        return self::$instances[InstrumentStructureService::class] ??= new InstrumentStructureService(
            self::instrumentStructureRepository(),
            self::instrumentVersionService(),
            self::auditService(),
        );
    }

    public static function scaleService(): ScaleService
    {
        return self::$instances[ScaleService::class] ??= new ScaleService(
            self::scaleRepository(),
            self::instrumentVersionService(),
            self::auditService(),
        );
    }

    public static function scoringRuleService(): ScoringRuleService
    {
        return self::$instances[ScoringRuleService::class] ??= new ScoringRuleService(
            self::scoringRuleRepository(),
            self::scaleRepository(),
            self::instrumentStructureRepository(),
            self::instrumentVersionService(),
            new FormulaDefinitionValidator(),
            self::auditService(),
        );
    }

    public static function normService(): NormService
    {
        return self::$instances[NormService::class] ??= new NormService(
            self::normRepository(),
            self::scaleRepository(),
            new NormRangeValidator(),
            self::auditService(),
        );
    }

    public static function reportTemplateService(): ReportTemplateService
    {
        return self::$instances[ReportTemplateService::class] ??= new ReportTemplateService(
            self::reportTemplateRepository(),
            self::normRepository(),
            self::scaleRepository(),
            new ReportTemplateValidator(),
            self::auditService(),
        );
    }
}
