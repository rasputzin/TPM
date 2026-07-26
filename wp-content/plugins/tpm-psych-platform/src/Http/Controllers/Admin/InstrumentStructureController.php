<?php

declare(strict_types=1);

namespace TpmPsych\Http\Controllers\Admin;

use TpmPsych\Authorization\Capabilities;
use TpmPsych\Http\ExceptionResponseMapper;
use TpmPsych\Support\Container;

final class InstrumentStructureController
{
    private const NAMESPACE = 'tpm/v1';

    public static function register(): void
    {
        register_rest_route(self::NAMESPACE, '/admin/instrument-versions/(?P<version_id>\d+)/sections', [
            [
                'methods' => 'GET',
                'callback' => [self::class, 'indexSections'],
                'permission_callback' => [self::class, 'checkPermission'],
            ],
            [
                'methods' => 'POST',
                'callback' => [self::class, 'createSection'],
                'permission_callback' => [self::class, 'checkPermission'],
            ],
        ]);

        register_rest_route(self::NAMESPACE, '/admin/instrument-versions/(?P<version_id>\d+)/items', [
            [
                'methods' => 'GET',
                'callback' => [self::class, 'indexItems'],
                'permission_callback' => [self::class, 'checkPermission'],
            ],
            [
                'methods' => 'POST',
                'callback' => [self::class, 'createItem'],
                'permission_callback' => [self::class, 'checkPermission'],
            ],
        ]);

        register_rest_route(self::NAMESPACE, '/admin/items/(?P<id>\d+)', [
            [
                'methods' => 'PUT',
                'callback' => [self::class, 'updateItem'],
                'permission_callback' => [self::class, 'checkPermission'],
            ],
            [
                'methods' => 'DELETE',
                'callback' => [self::class, 'deleteItem'],
                'permission_callback' => [self::class, 'checkPermission'],
            ],
        ]);

        register_rest_route(self::NAMESPACE, '/admin/items/(?P<item_id>\d+)/response-options', [
            [
                'methods' => 'GET',
                'callback' => [self::class, 'indexResponseOptions'],
                'permission_callback' => [self::class, 'checkPermission'],
            ],
            [
                'methods' => 'POST',
                'callback' => [self::class, 'createResponseOption'],
                'permission_callback' => [self::class, 'checkPermission'],
            ],
        ]);
    }

    public static function checkPermission(): bool
    {
        return current_user_can(Capabilities::MANAGE_INSTRUMENTS);
    }

    public static function indexSections(\WP_REST_Request $request): \WP_REST_Response
    {
        return ExceptionResponseMapper::run(static function () use ($request) {
            $sections = Container::instrumentStructureService()->listSections((int) $request->get_param('version_id'));

            return array_map(static fn ($s) => $s->toArray(), $sections);
        });
    }

    public static function createSection(\WP_REST_Request $request): \WP_REST_Response
    {
        return ExceptionResponseMapper::run(static function () use ($request) {
            $section = Container::instrumentStructureService()->addSection(
                instrumentVersionId: (int) $request->get_param('version_id'),
                title: $request->get_param('title'),
                orderIndex: (int) ($request->get_param('order_index') ?? 0),
                parentSectionId: $request->get_param('parent_section_id') !== null ? (int) $request->get_param('parent_section_id') : null,
                actorUserId: get_current_user_id(),
            );

            return $section->toArray();
        });
    }

    public static function indexItems(\WP_REST_Request $request): \WP_REST_Response
    {
        return ExceptionResponseMapper::run(static function () use ($request) {
            $items = Container::instrumentStructureService()->listItems((int) $request->get_param('version_id'));

            return array_map(static fn ($i) => $i->toArray(), $items);
        });
    }

    public static function createItem(\WP_REST_Request $request): \WP_REST_Response
    {
        return ExceptionResponseMapper::run(static function () use ($request) {
            $item = Container::instrumentStructureService()->addItem(
                instrumentVersionId: (int) $request->get_param('version_id'),
                sectionId: $request->get_param('section_id') !== null ? (int) $request->get_param('section_id') : null,
                code: (string) $request->get_param('code'),
                promptText: (string) $request->get_param('prompt_text'),
                itemType: (string) $request->get_param('item_type'),
                isReverseScored: (bool) $request->get_param('is_reverse_scored'),
                isRequired: $request->get_param('is_required') === null ? true : (bool) $request->get_param('is_required'),
                orderIndex: (int) ($request->get_param('order_index') ?? 0),
                actorUserId: get_current_user_id(),
            );

            return $item->toArray();
        });
    }

    public static function updateItem(\WP_REST_Request $request): \WP_REST_Response
    {
        return ExceptionResponseMapper::run(static function () use ($request) {
            $item = Container::instrumentStructureService()->updateItem(
                itemId: (int) $request->get_param('id'),
                promptText: (string) $request->get_param('prompt_text'),
                isReverseScored: (bool) $request->get_param('is_reverse_scored'),
                isRequired: $request->get_param('is_required') === null ? true : (bool) $request->get_param('is_required'),
                orderIndex: (int) ($request->get_param('order_index') ?? 0),
                actorUserId: get_current_user_id(),
            );

            return $item->toArray();
        });
    }

    public static function deleteItem(\WP_REST_Request $request): \WP_REST_Response
    {
        return ExceptionResponseMapper::run(static function () use ($request) {
            Container::instrumentStructureService()->deleteItem((int) $request->get_param('id'), get_current_user_id());

            return ['status' => 'deleted'];
        });
    }

    public static function indexResponseOptions(\WP_REST_Request $request): \WP_REST_Response
    {
        return ExceptionResponseMapper::run(static function () use ($request) {
            $options = Container::instrumentStructureService()->listResponseOptions((int) $request->get_param('item_id'));

            return array_map(static fn ($o) => $o->toArray(), $options);
        });
    }

    public static function createResponseOption(\WP_REST_Request $request): \WP_REST_Response
    {
        return ExceptionResponseMapper::run(static function () use ($request) {
            $option = Container::instrumentStructureService()->addResponseOption(
                itemId: (int) $request->get_param('item_id'),
                labelText: (string) $request->get_param('label_text'),
                rawValue: (float) $request->get_param('raw_value'),
                orderIndex: (int) ($request->get_param('order_index') ?? 0),
                actorUserId: get_current_user_id(),
            );

            return $option->toArray();
        });
    }
}
