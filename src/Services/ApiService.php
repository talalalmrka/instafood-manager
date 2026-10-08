<?php

namespace Ifm\Services;

if (!defined('ABSPATH')) {
    exit;
}

use Ifm\Pages\Categories;
use Ifm\Pages\Products;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;

class ApiService
{
    protected const NAMESPACE = 'ifm';

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
        register_rest_route(static::NAMESPACE, '/categories/datatable', [
            'methods' => WP_REST_Server::READABLE,
            'callback' => [Categories::class, 'datatable'],
            'permission_callback' => '__return_true',
        ]);

        register_rest_route(static::NAMESPACE, '/categories/(?P<id>\d+)', [
            'methods' => WP_REST_Server::READABLE,
            'callback' => [static::class, 'category'],
            'permission_callback' => '__return_true',
        ]);

        register_rest_route(static::NAMESPACE, '/products', [
            'methods' => WP_REST_Server::READABLE,
            'callback' => [static::class, 'products'],
            'permission_callback' => '__return_true',
        ]);
        register_rest_route(static::NAMESPACE, '/products/datatable', [
            'methods' => WP_REST_Server::READABLE,
            'callback' => [Products::class, 'datatable'],
            'permission_callback' => '__return_true',
        ]);

        register_rest_route(static::NAMESPACE, '/products/(?P<id>\d+)', [
            'methods' => WP_REST_Server::READABLE,
            'callback' => [static::class, 'product'],
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
        $filters = $request->get_params();
        $perPage = $request->get_param('per_page');
        $page = $request->get_param('page') ?? 1;
        $data = CategoryService::all($filters)->paginate($perPage, $page);
        return new WP_REST_Response([
            'success' => true,
            'data' => $data,
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
        $filters = $request->get_params();
        $perPage = $request->get_param('per_page');
        $page = $request->get_param('page') ?? 1;
        $data = ProductService::all($filters)->paginate($perPage, $page);
        return new WP_REST_Response([
            'success' => true,
            'data' => $data,
        ]);
    }

    public static function product(WP_REST_Request $request): WP_REST_Response
    {
        $id = (int) $request->get_param('id');

        return new WP_REST_Response([
            'success' => true,
            'data' => ProductService::find($id),
        ]);
    }

    public static function permission(): bool
    {
        return current_user_can('manage_options');
    }
}
