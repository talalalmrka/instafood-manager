<?php

namespace Ifm\Services;

if (!defined('ABSPATH')) {
    exit;
}

use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;

class ApiService
{
    protected const NAMESPACE = 'ifm/v1';

    public static function boot(): void
    {
        add_action('rest_api_init', [static::class, 'registerRoutes']);
    }

    public static function registerRoutes(): void
    {
        register_rest_route(static::NAMESPACE, '/categories', [
            'methods' => WP_REST_Server::READABLE,
            'callback' => [static::class, 'categories'],
            'permission_callback' => '__return_true',
        ]);

        register_rest_route(static::NAMESPACE, '/categories/(?P<id>\d+)', [
            'methods' => WP_REST_Server::READABLE,
            'callback' => [static::class, 'category'],
            'permission_callback' => '__return_true',
        ]);
        /* register_rest_route(static::NAMESPACE, '/products', [
            'methods' => WP_REST_Server::READABLE,
            'callback' => [static::class, 'products'],
            'permission_callback' => [static::class, 'permission'],
        ]);

        register_rest_route(static::NAMESPACE, '/products/(?P<id>\d+)', [
            'methods' => WP_REST_Server::READABLE,
            'callback' => [static::class, 'product'],
            'permission_callback' => [static::class, 'permission'],
        ]); */
    }

    public static function categories(WP_REST_Request $request): WP_REST_Response
    {
        return new WP_REST_Response([
            'success' => true,
            'data' => CategoryService::paginate($request->get_params()),
        ]);
    }

    public static function category(WP_REST_Request $request): WP_REST_Response
    {
        $id = (int) $request->get_param('id');

        return new WP_REST_Response([
            'success' => true,
            'data' => CategoryService::find($id),
        ]);
    }
    public static function products(WP_REST_Request $request): WP_REST_Response
    {
        return new WP_REST_Response([
            'success' => true,
            'data' => [],
        ]);
    }

    public static function product(WP_REST_Request $request): WP_REST_Response
    {
        $id = (int) $request->get_param('id');

        return new WP_REST_Response([
            'success' => true,
            'data' => [
                'id' => $id,
            ],
        ]);
    }

    public static function permission(): bool
    {
        return current_user_can('manage_options');
    }
}
