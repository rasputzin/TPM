<?php

declare(strict_types=1);

namespace TpmPsych\Http\Controllers\Admin;

use TpmPsych\Authorization\Capabilities;
use TpmPsych\Http\ExceptionResponseMapper;
use TpmPsych\Support\Container;

final class ReportTemplatesController
{
    private const NAMESPACE = 'tpm/v1';

    public static function register(): void
    {
        register_rest_route(self::NAMESPACE, '/admin/organizations/(?P<organization_id>\d+)/report-templates', [
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

        register_rest_route(self::NAMESPACE, '/admin/report-templates/(?P<id>\d+)/publish', [
            'methods' => 'POST',
            'callback' => [self::class, 'publish'],
            'permission_callback' => [self::class, 'checkPermission'],
        ]);
    }

    public static function checkPermission(): bool
    {
        return current_user_can(Capabilities::MANAGE_TEMPLATES);
    }

    public static function index(\WP_REST_Request $request): \WP_REST_Response
    {
        return ExceptionResponseMapper::run(static function () use ($request) {
            $templates = Container::reportTemplateService()->listByOrganization((int) $request->get_param('organization_id'));

            return array_map(static fn ($t) => $t->toArray(), $templates);
        });
    }

    public static function create(\WP_REST_Request $request): \WP_REST_Response
    {
        return ExceptionResponseMapper::run(static function () use ($request) {
            $layout = $request->get_param('layout_definition');
            $instrumentId = $request->get_param('instrument_id');
            $referenceVersionId = $request->get_param('reference_instrument_version_id');

            $template = Container::reportTemplateService()->create(
                organizationId: (int) $request->get_param('organization_id'),
                instrumentId: $instrumentId !== null ? (int) $instrumentId : null,
                versionLabel: (string) $request->get_param('version_label'),
                layoutDefinition: is_array($layout) ? $layout : [],
                referenceInstrumentVersionId: $referenceVersionId !== null ? (int) $referenceVersionId : null,
                actorUserId: get_current_user_id(),
            );

            return $template->toArray();
        });
    }

    public static function publish(\WP_REST_Request $request): \WP_REST_Response
    {
        return ExceptionResponseMapper::run(static function () use ($request) {
            $template = Container::reportTemplateService()->publish((int) $request->get_param('id'), get_current_user_id());

            return $template->toArray();
        });
    }
}
