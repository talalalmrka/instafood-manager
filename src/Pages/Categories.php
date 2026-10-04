<?php

namespace Ifm\Pages;

use Ifm\Generic\Datatable\Column;
use Ifm\Generic\Datatable\Datatable;
use Ifm\Generic\Page;
use Ifm\Services\CategoryService;
use Override;

class Categories extends Datatable
{
    public static string $key = 'term_id';

    public static function icon(): string
    {
        return 'bi-tags';
    }
    public static function boot(): void
    {
        add_action("wp_ajax_ifm_categories_datatable", [self::class, "datatable"]);

        add_action("wp_ajax_ifm_category_create", [self::class, "create"]);

        add_action("wp_ajax_ifm_category_update", [self::class, "update"]);

        add_action("wp_ajax_ifm_category_delete", [self::class, "delete"]);

        add_action("wp_ajax_ifm_categories_reorder", [self::class, "reorder"]);
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

    public static function items(): array
    {
        $categories = CategoryService::all($_REQUEST);
        return $categories;
    }

    public static function render(): void
    {
        $nonce = wp_create_nonce("ifm_categories"); ?>
        <?php self::renderTable(); ?>
    <?php
    }

    public static function datatable(): void
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

    private static function verifyAjax(): void
    {
        if (!current_user_can("manage_options")) {
            wp_send_json_error(
                [
                    "message" => __("Unauthorized."),
                ],
                403
            );
        }

        check_ajax_referer("ifm_categories", "nonce");
    }
}
