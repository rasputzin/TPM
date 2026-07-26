<?php

declare(strict_types=1);

namespace TpmPsych\Http\Controllers\Admin;

use TpmPsych\Authorization\Capabilities;
use TpmPsych\Http\ExceptionResponseMapper;
use TpmPsych\Support\Container;

final class InstrumentVersionsController
{
    private const NAMESPACE = 'tpm/v1';

    public static function register(): void
    {
        register_rest_route(self::NAMESPACE, '/admin/instruments/(?P<instrument_id>\d+)/versions', [
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

        register_rest_route(self::NAMESPACE, '/admin/instrument-versions/(?P<id>\d+)', [
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

        register_rest_route(self::NAMESPACE, '/admin/instrument-versions/(?P<id>\d+)/clone', [
            'methods' => 'POST',
            'callback' => [self::class, 'cloneVersion'],
            'permission_callback' => [self::class, 'checkPermission'],
        ]);

        register_rest_route(self::NAMESPACE, '/admin/instrument-versions/(?P<id>\d+)/publish', [
            'methods' => 'POST',
            'callback' => [self::class, 'publish'],
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
            $versions = Container::instrumentVersionService()->listByInstrument((int) $request->get_param('instrument_id'));

            return array_map(static fn ($v) => $v->toArray(), $versions);
        });
    }

    public static function show(\WP_REST_Request $request): \WP_REST_Response
    {
        return ExceptionResponseMapper::run(static function () use ($request) {
            $version = Container::instrumentVersionService()->find((int) $request->get_param('id'));

            if ($version === null) {
                return new \WP_REST_Response(['code' => 'tpm_not_found', 'message' => 'Versiune inexistentă.'], 404);
            }

            return $version->toArray();
        });
    }

    public static function create(\WP_REST_Request $request): \WP_REST_Response
    {
        return ExceptionResponseMapper::run(static function () use ($request) {
            $version = Container::instrumentVersionService()->createEmptyDraft(
                instrumentId: (int) $request->get_param('instrument_id'),
                versionLabel: (string) $request->get_param('version_label'),
                language: (string) ($request->get_param('language') ?? 'ro'),
                actorUserId: get_current_user_id(),
            );

            return $version->toArray();
        });
    }

    public static function update(\WP_REST_Request $request): \WP_REST_Response
    {
        return ExceptionResponseMapper::run(static function () use ($request) {
            $version = Container::instrumentVersionService()->updateDraftMetadata(
                versionId: (int) $request->get_param('id'),
                language: (string) ($request->get_param('language') ?? 'ro'),
                estimatedDurationMinutes: $request->get_param('estimated_duration_minutes') !== null
                    ? (int) $request->get_param('estimated_duration_minutes')
                    : null,
                instructionsText: $request->get_param('instructions_text'),
                missingResponsePolicy: $request->get_param('missing_response_policy'),
                actorUserId: get_current_user_id(),
            );

            return $version->toArray();
        });
    }

    public static function cloneVersion(\WP_REST_Request $request): \WP_REST_Response
    {
        return ExceptionResponseMapper::run(static function () use ($request) {
            $version = Container::instrumentVersionService()->cloneAsDraft(
                sourceVersionId: (int) $request->get_param('id'),
                newVersionLabel: (string) $request->get_param('new_version_label'),
                actorUserId: get_current_user_id(),
            );

            return $version->toArray();
        });
    }

    public static function publish(\WP_REST_Request $request): \WP_REST_Response
    {
        return ExceptionResponseMapper::run(static function () use ($request) {
            $version = Container::instrumentVersionService()->publish((int) $request->get_param('id'), get_current_user_id());

            return $version->toArray();
        });
    }
}
