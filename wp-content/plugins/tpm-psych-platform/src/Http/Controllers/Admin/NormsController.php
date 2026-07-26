<?php

declare(strict_types=1);

namespace TpmPsych\Http\Controllers\Admin;

use TpmPsych\Authorization\Capabilities;
use TpmPsych\Http\ExceptionResponseMapper;
use TpmPsych\Support\Container;

final class NormsController
{
    private const NAMESPACE = 'tpm/v1';

    public static function register(): void
    {
        register_rest_route(self::NAMESPACE, '/admin/instrument-versions/(?P<version_id>\d+)/norm-sets', [
            [
                'methods' => 'GET',
                'callback' => [self::class, 'indexSets'],
                'permission_callback' => [self::class, 'checkPermission'],
            ],
            [
                'methods' => 'POST',
                'callback' => [self::class, 'createSet'],
                'permission_callback' => [self::class, 'checkPermission'],
            ],
        ]);

        register_rest_route(self::NAMESPACE, '/admin/norm-sets/(?P<id>\d+)/publish', [
            'methods' => 'POST',
            'callback' => [self::class, 'publishSet'],
            'permission_callback' => [self::class, 'checkPermission'],
        ]);

        register_rest_route(self::NAMESPACE, '/admin/norm-sets/(?P<norm_set_id>\d+)/entries', [
            [
                'methods' => 'GET',
                'callback' => [self::class, 'indexEntries'],
                'permission_callback' => [self::class, 'checkPermission'],
            ],
            [
                'methods' => 'POST',
                'callback' => [self::class, 'createEntry'],
                'permission_callback' => [self::class, 'checkPermission'],
            ],
        ]);
    }

    public static function checkPermission(): bool
    {
        return current_user_can(Capabilities::MANAGE_INSTRUMENTS);
    }

    public static function indexSets(\WP_REST_Request $request): \WP_REST_Response
    {
        return ExceptionResponseMapper::run(static function () use ($request) {
            $sets = Container::normService()->listSets((int) $request->get_param('version_id'));

            return array_map(static fn ($s) => $s->toArray(), $sets);
        });
    }

    public static function createSet(\WP_REST_Request $request): \WP_REST_Response
    {
        return ExceptionResponseMapper::run(static function () use ($request) {
            $criteria = $request->get_param('applicable_criteria');

            $set = Container::normService()->createSet(
                instrumentVersionId: (int) $request->get_param('version_id'),
                label: (string) $request->get_param('label'),
                versionLabel: (string) $request->get_param('version_label'),
                applicableCriteria: is_array($criteria) ? $criteria : null,
                actorUserId: get_current_user_id(),
            );

            return $set->toArray();
        });
    }

    public static function publishSet(\WP_REST_Request $request): \WP_REST_Response
    {
        return ExceptionResponseMapper::run(static function () use ($request) {
            $set = Container::normService()->publishSet((int) $request->get_param('id'), get_current_user_id());

            return $set->toArray();
        });
    }

    public static function indexEntries(\WP_REST_Request $request): \WP_REST_Response
    {
        return ExceptionResponseMapper::run(static function () use ($request) {
            $scaleId = $request->get_param('scale_id');
            $entries = Container::normService()->listEntries(
                (int) $request->get_param('norm_set_id'),
                $scaleId !== null ? (int) $scaleId : null
            );

            return array_map(static fn ($e) => $e->toArray(), $entries);
        });
    }

    public static function createEntry(\WP_REST_Request $request): \WP_REST_Response
    {
        return ExceptionResponseMapper::run(static function () use ($request) {
            $entry = Container::normService()->addEntry(
                normSetId: (int) $request->get_param('norm_set_id'),
                scaleId: (int) $request->get_param('scale_id'),
                rawScoreMin: (float) $request->get_param('raw_score_min'),
                rawScoreMax: (float) $request->get_param('raw_score_max'),
                standardScoreType: (string) $request->get_param('standard_score_type'),
                standardScoreValue: (float) $request->get_param('standard_score_value'),
                interpretationRangeCode: $request->get_param('interpretation_range_code'),
                actorUserId: get_current_user_id(),
            );

            return $entry->toArray();
        });
    }
}
