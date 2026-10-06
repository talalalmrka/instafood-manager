<?php

namespace Ifm\Pages;

use Ifm\Generic\Datatable\Column;
use Ifm\Generic\Datatable\Datatable;
use Ifm\Generic\Page;
use Ifm\Services\CategoryService;
use Override;

class Categories extends Datatable
{
    public static string $primaryKey = 'term_id';

    public static function icon(): string
    {
        return 'bi-tags';
    }
    public static function boot(): void
    {
        parent::boot();
        // add_action("wp_ajax_ifm_categories_datatable", [self::class, "datatable"]);

        add_action("wp_ajax_ifm_category_create", [self::class, "create"]);

        add_action("wp_ajax_ifm_category_update", [self::class, "update"]);

        add_action("wp_ajax_ifm_category_delete", [self::class, "delete"]);

        add_action("wp_ajax_ifm_categories_reorder", [self::class, "reorder"]);

        add_action('wp_ajax_cats', [self::class, 'cats']);
        add_action('wp_ajax_nopriv_cats', [self::class, 'cats']);

        add_action('wp_ajax_routes', [self::class, 'routes']);
        add_action('wp_ajax_nopriv_routes', [self::class, 'routes']);
    }

    public static function columns(): array
    {
        return [
            Column::make('name')
                ->label(__('Name')),
            Column::make('slug')
                ->label(__('Slug')),
            Column::make('count')
                ->label(__('Categories'))
                ->class('text-center'),
        ];
    }
    /**
     * get items
     * @return \Illuminate\Support\Collection
     */
    public static function items()
    {
        $categories = CategoryService::all($_REQUEST);
        return $categories;
    }

    public static function render(): void
    {
        $nonce = wp_create_nonce("ifm_categories");
        // self::dump(static::datatableOptions());
?>
        <?php self::renderTable();
        ?>
    <?php
    }

    /*public static function datatable(): void
    {
        self::verifyAjax();

        $search = isset($_POST["search"])
            ? sanitize_text_field(wp_unslash($_POST["search"]))
            : "";

        $categories = CategoryService::all([
            "search" => $search,
        ]);

        $data = [];

        foreach ($categories as $category) {
            $data[] = [
                "id" => (int) $category->term_id,
                "name" => $category->name,
                "slug" => $category->slug,
                "count" => (int) $category->count,
            ];
        }

        wp_send_json_success([
            "data" => $data,
            "recordsTotal" => count($data),
            "recordsFiltered" => count($data),
        ]);
    }

    public static function create(): void
    {
        self::verifyAjax();

        $name = isset($_POST["name"])
            ? sanitize_text_field(wp_unslash($_POST["name"]))
            : "";

        if ($name === "") {
            wp_send_json_error([
                "message" => __("Category name is required."),
            ]);
        }

        if (CategoryService::findByName($name)) {
            wp_send_json_error([
                "message" => __("Category already exists."),
            ]);
        }

        $category = CategoryService::create($name);

        if (!$category) {
            wp_send_json_error([
                "message" => __("Unable to create category."),
            ]);
        }

        wp_send_json_success([
            "message" => __("Category created successfully."),
            "data" => [
                "id" => (int) $category->term_id,
                "name" => $category->name,
                "slug" => $category->slug,
                "count" => (int) $category->count,
            ],
        ]);
    }

    public static function update(): void
    {
        self::verifyAjax();

        $id = isset($_POST["id"]) ? absint($_POST["id"]) : 0;

        $name = isset($_POST["name"])
            ? sanitize_text_field(wp_unslash($_POST["name"]))
            : "";

        if (!$id || $name === "") {
            wp_send_json_error([
                "message" => __("Invalid category data."),
            ]);
        }

        $category = CategoryService::update($id, $name);

        if (!$category) {
            wp_send_json_error([
                "message" => __("Unable to update category."),
            ]);
        }

        wp_send_json_success([
            "message" => __("Category updated successfully."),
            "data" => [
                "id" => (int) $category->term_id,
                "name" => $category->name,
                "slug" => $category->slug,
                "count" => (int) $category->count,
            ],
        ]);
    }

    public static function delete(): void
    {
        self::verifyAjax();

        $id = isset($_POST["id"]) ? absint($_POST["id"]) : 0;

        if (!$id) {
            wp_send_json_error([
                "message" => __("Invalid category."),
            ]);
        }

        if (!CategoryService::delete($id)) {
            wp_send_json_error([
                "message" => __("Unable to delete category."),
            ]);
        }

        wp_send_json_success([
            "message" => __("Category deleted successfully."),
            "id" => $id,
        ]);
    }

    public static function reorder(): void
    {
        self::verifyAjax();

        $ids = isset($_POST["ids"]) ? (array) $_POST["ids"] : [];

        $ids = array_map("absint", $ids);
        $ids = array_values(array_filter($ids));

        if (!$ids) {
            wp_send_json_error([
                "message" => __("No categories provided."),
            ]);
        }

        CategoryService::reorder($ids);

        wp_send_json_success([
            "message" => __("Categories reordered successfully."),
        ]);
    }

    public static function verifyAjax(): void
    {
        if (!current_user_can("manage_options")) {
            wp_send_json_error(
                [
                    "message" => __("Unauthorized."),
                ],
                403
            );
        }

        check_ajax_referer("ifm_nonce", "nonce");
    }*/

    public static function cats()
    {
        $filters = CategoryService::prepareFilters($_REQUEST);
        $args = CategoryService::prepareQueryArgs($_REQUEST);
        $items = CategoryService::all($filters);
        $total_items = CategoryService::total($filters);
        $total_pages = CategoryService::totalPages($filters);
        $current_page = data_get($filters, 'page');
        $per_page = data_get($filters, 'per_page');

        wp_send_json_success([
            'filters'    => $filters,
            'args'       => $args,
            'items'      => $items,
            'pagination' => [
                'total_items'  => $total_items,
                'total_pages'  => $total_pages,
                'current_page' => $current_page,
                'per_page'     => $per_page,
            ]
        ]);
    }

    public static function routes()
    {
        $routes = rest_get_server()->get_routes();
        wp_send_json_success([
            'routes' => $routes,
        ]);
    }
}
