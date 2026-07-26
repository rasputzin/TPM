<?php

declare(strict_types=1);

namespace TpmPsych\Http\Controllers\Admin;

use TpmPsych\Authorization\Capabilities;
use TpmPsych\Http\ExceptionResponseMapper;
use TpmPsych\Support\Container;

final class ScalesController
{
    private const NAMESPACE = 'tpm/v1';

    public static function register(): void
    {
        register_rest_route(self::NAMESPACE, '/admin/instrument-versions/(?P<version_id>\d+)/scales', [
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

        register_rest_route(self::NAMESPACE, '/admin/scales/(?P<id>\d+)', [
            'methods' => 'PUT',
            'callback' => [self::class, 'update'],
            'permission_callback' => [self::class, 'checkPermission'],
        ]);
    }

    public static function checkPermission(): bool
    {
        return current_user_can(Capabilities::MANAGE_INSTRUMENTS);
    }

    public static function index(\WP_REST_Request $request): \WP_REST_Response
    {
        return ExceptionResponseMapper::run(static function () use ($request) {
            $scales = Container::scaleService()->listByInstrumentVersion((int) $request->get_param('version_id'));

            return array_map(static fn ($s) => $s->toArray(), $scales);
        });
    }

    public static function create(\WP_REST_Request $request): \WP_REST_Response
    {
        return ExceptionResponseMapper::run(static function () use ($request) {
            $scale = Container::scaleService()->add(
                instrumentVersionId: (int) $request->get_param('version_id'),
                code: (string) $request->get_param('code'),
                name: (string) $request->get_param('name'),
                parentScaleId: $request->get_param('parent_scale_id') !== null ? (int) $request->get_param('parent_scale_id') : null,
                actorUserId: get_current_user_id(),
            );

            return $scale->toArray();
        });
    }

    public static function update(\WP_REST_Request $request): \WP_REST_Response
    {
        return ExceptionResponseMapper::run(static function () use ($request) {
            $scale = Container::scaleService()->update(
                scaleId: (int) $request->get_param('id'),
                name: (string) $request->get_param('name'),
                actorUserId: get_current_user_id(),
            );

            return $scale->toArray();
        });
    }
}
