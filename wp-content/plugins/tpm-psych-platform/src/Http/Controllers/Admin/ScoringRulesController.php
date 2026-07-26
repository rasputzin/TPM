<?php

declare(strict_types=1);

namespace TpmPsych\Http\Controllers\Admin;

use TpmPsych\Authorization\Capabilities;
use TpmPsych\Http\ExceptionResponseMapper;
use TpmPsych\Support\Container;

final class ScoringRulesController
{
    private const NAMESPACE = 'tpm/v1';

    public static function register(): void
    {
        register_rest_route(self::NAMESPACE, '/admin/scales/(?P<scale_id>\d+)/scoring-rules', [
            [
                'methods' => 'GET',
                'callback' => [self::class, 'index'],
                'permission_callback' => [self::class, 'checkPermission'],
            ],
            [
                'methods' => 'POST',
                'callback' => [self::class, 'create'],
                'permission_callback' => [self::class, 'checkPermission'],
            ],
        ]);
    }

    public static function checkPermission(): bool
    {
        return current_user_can(Capabilities::MANAGE_INSTRUMENTS);
    }

    public static function index(\WP_REST_Request $request): \WP_REST_Response
    {
        return ExceptionResponseMapper::run(static function () use ($request) {
            $rules = Container::scoringRuleService()->listByScale((int) $request->get_param('scale_id'));

            return array_map(static fn ($r) => $r->toArray(), $rules);
        });
    }

    public static function create(\WP_REST_Request $request): \WP_REST_Response
    {
        return ExceptionResponseMapper::run(static function () use ($request) {
            $definition = $request->get_param('formula_definition');

            $rule = Container::scoringRuleService()->save(
                scaleId: (int) $request->get_param('scale_id'),
                ruleType: (string) $request->get_param('rule_type'),
                formulaDefinition: is_array($definition) ? $definition : [],
                actorUserId: get_current_user_id(),
            );

            return $rule->toArray();
        });
    }
}
