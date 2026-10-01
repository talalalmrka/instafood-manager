<?php

namespace Ifm\Pages;

use Ifm\Generic\Page;
use Ifm\Services\CategoryService;

class Categories extends Page
{

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

    public static function render(): void
    {
        $nonce = wp_create_nonce("ifm_categories");
        $categories = CategoryService::all($_REQUEST);
?>
        <div class="flex md:items-center flex-col md:flex-row md:justify-between gap-2 mb-3">
            <div class="btn-group btn-group-xs">
                <button type="button" role="button" class="btn btn-primary">
                    <i class="icon bi-plus-lg"></i>
                </button>
                <button type="submit" name="deleteSelected" role="button" class="btn btn-red">
                    <i class="icon bi-trash"></i>
                </button>
            </div>
            <div class="flex items-center gap-2">
                <div class="inline-flex items-center">
                    <div class="form-control-container">
                        <span class="start-icon"><i class="icon bi-list"></i></span>
                        <select name="per_page" id="per_page" class="form-select has-start-icon xs pill">
                            <?php foreach (per_page_options() as $op): ?>
                                <option value="<?php echo esc_attr($op['value']); ?>"><?php echo esc_html($op['label']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="inline-flex items-center">
                    <div class="form-control-container">
                        <span class="start-icon"><i class="icon bi-search"></i></span>
                        <input type="search" name="search" placeholder="Search..." class="form-control has-start-icon xs pill">
                    </div>
                </div>
            </div>
        </div>
        <div class="table-container">
            <table class="table table-divide table-striped table-border xs">
                <thead>
                    <?php self::headRow(); ?>
                </thead>
                <tbody>
                    <?php if (!empty($categories)): ?>
                        <?php foreach ($categories as $category): ?>
                            <tr>
                                <td>
                                    <input type="checkbox" name="selected[]" value="<?php echo esc_attr($category->term_id); ?>" placeholder="Enter text">
                                </td>
                                <td><?php echo esc_html($category->name); ?></td>
                                <td><?php echo esc_html($category->slug); ?></td>
                                <td><?php echo esc_html($category->count); ?></td>
                                <td></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" class="text-center"><?php echo esc_html('No Items!'); ?></td>
                        </tr>
                    <?php endif; ?>
                </tbody>
                <tfoot>
                    <?php self::headRow(); ?>
                </tfoot>
            </table>
        </div>
    <?php
    }

    public static function headRow()
    {
    ?>
        <tr>
            <th><input type="checkbox" class="select-all"></th>
            <th><?php echo esc_html(__("Name")); ?></th>
            <th><?php echo esc_html(__("Slug")); ?></th>
            <th><?php echo esc_html(__("Products")); ?></th>
            <th><?php echo esc_html(__("Actions")); ?></th>
        </tr>
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
