<?php

declare(strict_types=1);

namespace TpmPsych\Http\Controllers\Admin;

use TpmPsych\Authorization\Capabilities;
use TpmPsych\Http\ExceptionResponseMapper;
use TpmPsych\Support\Container;

final class InstrumentsController
{
    private const NAMESPACE = 'tpm/v1';

    public static function register(): void
    {
        register_rest_route(self::NAMESPACE, '/admin/instruments', [
            [
                'methods' => 'GET',
                'callback' => [self::class, 'index'],
                'permission_callback' => [self::class, 'checkPermission'],
                'args' => [
                    'status' => ['required' => false, 'type' => 'string'],
                ],
            ],
            [
                'methods' => 'POST',
                'callback' => [self::class, 'create'],
                'permission_callback' => [self::class, 'checkPermission'],
            ],
        ]);

        register_rest_route(self::NAMESPACE, '/admin/instruments/(?P<id>\d+)', [
            [
                'methods' => 'GET',
                'callback' => [self::class, 'show'],
                'permission_callback' => [self::class, 'checkPermission'],
            ],
            [
                'methods' => 'PUT',
                'callback' => [self::class, 'update'],
                'permission_callback' => [self::class, 'checkPermission'],
            ],
        ]);

        register_rest_route(self::NAMESPACE, '/admin/instruments/(?P<id>\d+)/archive', [
            'methods' => 'POST',
            'callback' => [self::class, 'archive'],
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
            $status = $request->get_param('status');
            $instruments = Container::instrumentService()->list($status !== null ? (string) $status : null);

            return array_map(static fn ($i) => $i->toArray(), $instruments);
        });
    }

    public static function show(\WP_REST_Request $request): \WP_REST_Response
    {
        return ExceptionResponseMapper::run(static function () use ($request) {
            $instrument = Container::instrumentService()->find((int) $request->get_param('id'));

            if ($instrument === null) {
                return new \WP_REST_Response(['code' => 'tpm_not_found', 'message' => 'Instrument inexistent.'], 404);
            }

            return $instrument->toArray();
        });
    }

    public static function create(\WP_REST_Request $request): \WP_REST_Response
    {
        return ExceptionResponseMapper::run(static function () use ($request) {
            $instrument = Container::instrumentService()->create(
                organizationId: $request->get_param('organization_id') !== null ? (int) $request->get_param('organization_id') : null,
                code: (string) $request->get_param('code'),
                name: (string) $request->get_param('name'),
                rightsHolder: (string) $request->get_param('rights_holder'),
                licenseReference: $request->get_param('license_reference'),
                targetPopulation: $request->get_param('target_population'),
                actorUserId: get_current_user_id(),
            );

            return $instrument->toArray();
        });
    }

    public static function update(\WP_REST_Request $request): \WP_REST_Response
    {
        return ExceptionResponseMapper::run(static function () use ($request) {
            $instrument = Container::instrumentService()->update(
                id: (int) $request->get_param('id'),
                name: (string) $request->get_param('name'),
                rightsHolder: (string) $request->get_param('rights_holder'),
                licenseReference: $request->get_param('license_reference'),
                targetPopulation: $request->get_param('target_population'),
                actorUserId: get_current_user_id(),
            );

            return $instrument->toArray();
        });
    }

    public static function archive(\WP_REST_Request $request): \WP_REST_Response
    {
        return ExceptionResponseMapper::run(static function () use ($request) {
            Container::instrumentService()->archive((int) $request->get_param('id'), get_current_user_id());

            return ['status' => 'archived'];
        });
    }
}
