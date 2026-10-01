<?php

namespace Ifm\Pages;

use Ifm\Ifm;
use Ifm\Generic\Page;

class Import extends Page
{
    /* public static function title(): string
    {
        return __('Import');
    }
 */
    public static function icon(): string
    {
        return 'bi-box-arrow-in-down';
    }
    public static function slug(): string
    {
        return IFM_PAGE_SLUG;
    }

    public static function boot(): void
    {
        add_action(
            'admin_post_' . self::nonce(),
            [self::class, 'handleImport']
        );

        add_action(
            'wp_ajax_' . self::nonce(),
            [self::class, 'handleImportAjax']
        );
    }

    public static function render(): void
    {
        $json = isset($_POST['instafood_json'])
            ? wp_unslash($_POST['instafood_json'])
            : '';
        $sample = [
            "restaurant" => "GOURMET SQUARE",
            "location" => "MINI MARKET & DELI",
            "url" => "https://example.com",
            "categories" => [],
        ];
        $placeholder = json_encode($sample);
?>
        <?php if (!post_type_exists(IFM_ITEM_POST_TYPE)): ?>
            <div class="alert alert-error alert-soft sm mb-3">
                <p>
                    InstaFood post type <code><?php echo esc_html(IFM_ITEM_POST_TYPE); ?></code>
                    was not found. Activate InstaFood before importing.
                </p>
            </div>
        <?php endif; ?>
        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" id="instafood-import-form" class="ajax-form">
            <input
                type="hidden"
                name="action"
                value="<?php echo esc_attr(self::nonce()); ?>">

            <?php wp_nonce_field(self::nonce()); ?>
            <div class="flex flex-wrap items-center gap-2 mb-2">
                <div class="inline-flex items-center gap-1">
                    <label for="instafood_import_mode" class="form-label mb-0! text-nowrap">Existing items:</label>
                    <select id="instafood_import_mode" name="import_mode" class="form-select pill xs">
                        <option value="update">Update existing</option>
                        <option value="skip">Skip existing</option>
                        <option value="create">Always create</option>
                    </select>
                </div>
                <div class="inline-flex">
                    <label class="form-switch">
                        <input type="checkbox"
                            id="delete_previous"
                            name="delete_previous"
                            value="1"
                            class="hidden!">
                        <span class="toggle-slider"></span>
                        <span class="form-switch-label"><?php _e('Delete exists.'); ?></span>
                    </label>
                </div>
            </div>
            <div class="grid grid-cols-1 gap-3">
                <div class="col">
                    <label for="instafood_json_file" class="form-label!">
                        <?php esc_html_e('Import from JSON file'); ?>
                    </label>
                    <div class="form-control-container">
                        <span class="start-icon"><i class="icon bi-cloud-upload"></i></span>
                        <input
                            type="file"
                            id="instafood_json_file"
                            accept=".json,application/json"
                            class="form-control xs has-start-icon" />
                    </div>
                    <div class="form-info">
                        <?php esc_html_e('Select a JSON file to load its contents into the import field.'); ?>
                    </div>
                    <div
                        id="instafood_file_status" class="form-info"></div>
                </div>
                <div class="col md:col-span-3">
                    <label for="instafood_json" class="form-label!"><?php _e('Import from json'); ?></label>
                    <textarea
                        id="instafood_json"
                        name="instafood_json"
                        rows="12"
                        class="form-control xs font-mono!"
                        placeholder='<?php echo esc_html($placeholder); ?>'><?php echo esc_textarea($json); ?></textarea>
                </div>
                <div class="col flex items-center gap-2 justify-between">
                    <button id="instafood_validate" type="button" class="btn btn-outline-primary btn-sm">
                        <i class="icon bi-shield-check"></i>
                        <span><?php _e('Validate'); ?></span>
                    </button>
                    <button type="submit" class="btn btn-primary btn-sm">
                        <i class="icon bi-cloud-upload"></i>
                        <span><?php _e('Import'); ?></span>
                    </button>
                </div>
            </div>
            <div id="instafood_validation_result"></div>
        </form>
<?php
    }

    public static function handleImport(): void
    {
        if (!current_user_can('manage_options')) {
            wp_die('You do not have permission to perform this action.');
        }

        check_admin_referer(self::nonce());

        if (!post_type_exists(IFM_ITEM_POST_TYPE)) {
            self::redirect_with_error('InstaFood post type was not found.');
        }

        $json = isset($_POST['instafood_json'])
            ? trim(wp_unslash($_POST['instafood_json']))
            : '';

        $mode = isset($_POST['import_mode'])
            ? sanitize_key(wp_unslash($_POST['import_mode']))
            : 'update';

        if (!in_array($mode, ['update', 'skip', 'create'], true)) {
            $mode = 'update';
        }
        $deletePrevious = !empty($_POST['delete_previous']);
        if ($json === '') {
            self::redirect_with_error('JSON input is empty.');
        }

        $data = json_decode($json, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            self::redirect_with_error(
                'Invalid JSON: ' . json_last_error_msg()
            );
        }

        try {
            $summary = self::import($data, $mode, $deletePrevious);

            set_transient(
                self::resultKey(),
                [
                    'type' => 'success',
                    'summary' => $summary,
                ],
                60
            );
        } catch (\Throwable $e) {
            set_transient(
                self::resultKey(),
                [
                    'type' => 'error',
                    'message' => $e->getMessage(),
                ],
                60
            );
        }

        wp_safe_redirect(
            admin_url('admin.php?page=' . self::slug())
        );
        exit;
    }

    public static function handleImportAjax(): void
    {
        if (!current_user_can('manage_options')) {
            wp_send_json_error([
                'message' => 'You do not have permission to perform this action.',
            ], 403);
        }

        check_ajax_referer(self::nonce());

        if (!post_type_exists(IFM_ITEM_POST_TYPE)) {
            wp_send_json_error([
                'message' => 'InstaFood post type was not found.',
            ], 400);
        }

        $json = isset($_POST['instafood_json'])
            ? trim(wp_unslash($_POST['instafood_json']))
            : '';

        $mode = isset($_POST['import_mode'])
            ? sanitize_key(wp_unslash($_POST['import_mode']))
            : 'update';

        if (!in_array($mode, ['update', 'skip', 'create'], true)) {
            $mode = 'update';
        }

        $deletePrevious = !empty($_POST['delete_previous']);

        if ($json === '') {
            wp_send_json_error([
                'message' => 'JSON input is empty.',
            ], 400);
        }

        $data = json_decode($json, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            wp_send_json_error([
                'message' => 'Invalid JSON: ' . json_last_error_msg(),
            ], 400);
        }

        try {
            $summary = self::import(
                $data,
                $mode,
                $deletePrevious
            );

            wp_send_json_success([
                'message' => 'Import completed successfully.',
                'summary' => $summary,
            ]);
        } catch (\Throwable $e) {
            wp_send_json_error([
                'message' => $e->getMessage(),
            ], 500);
        }
    }
    private static function import(array $data, string $mode, bool $deletePrevious): array
    {
        self::validate_data($data);
        if ($deletePrevious) {
            self::delete_previous_data();
        }
        $categories = 0;
        $itemsCreated = 0;
        $itemsUpdated = 0;
        $itemsSkipped = 0;
        $variations = 0;
        $itemOrder = 0;

        foreach ($data['categories'] as $categoryIndex => $categoryData) {
            $termId = self::find_or_create_category(
                $categoryData['name'],
                $categoryIndex,
                sizeof($data['categories']),
            );

            if (!$termId) {
                throw new \RuntimeException(
                    'Unable to create category: ' . $categoryData['name']
                );
            }

            $categories++;

            foreach ($categoryData['items'] as $itemIndex => $itemData) {
                $result = self::import_item(
                    $itemData,
                    (int) $termId,
                    $mode,
                    //$itemIndex,
                    $itemOrder,
                    sizeof($categoryData['items']),
                );
                $itemOrder++;

                if ($result === 'created') {
                    $itemsCreated++;
                } elseif ($result === 'updated') {
                    $itemsUpdated++;
                } else {
                    $itemsSkipped++;
                }

                $variations += count($itemData['variations']);
            }
        }

        return [
            'categories' => $categories,
            'items_created' => $itemsCreated,
            'items_updated' => $itemsUpdated,
            'items_skipped' => $itemsSkipped,
            'variations' => $variations,
        ];
    }
    private static function delete_previous_data(): void
    {
        $items = get_posts([
            'post_type' => IFM_ITEM_POST_TYPE,
            'post_status' => 'any',
            'posts_per_page' => -1,
            'fields' => 'ids',
            'no_found_rows' => true,
            'suppress_filters' => true,
        ]);

        foreach ($items as $postId) {
            wp_delete_post((int) $postId, true);
        }

        $terms = get_terms([
            'taxonomy' => IFM_ITEM_TAXONOMY,
            'hide_empty' => false,
            'fields' => 'ids',
        ]);

        if (!is_wp_error($terms)) {
            foreach ($terms as $termId) {
                wp_delete_term(
                    (int) $termId,
                    IFM_ITEM_TAXONOMY
                );
            }
        }
    }
    private static function validate_data(array $data): void
    {
        if (!isset($data['categories']) || !is_array($data['categories'])) {
            throw new \InvalidArgumentException(
                'The "categories" property must be an array.'
            );
        }

        foreach ($data['categories'] as $categoryIndex => $category) {
            if (!is_array($category)) {
                throw new \InvalidArgumentException(
                    "Category {$categoryIndex} must be an object."
                );
            }

            $categoryName = isset($category['name'])
                ? trim((string) $category['name'])
                : '';

            if ($categoryName === '') {
                throw new \InvalidArgumentException(
                    "Category {$categoryIndex} has no name."
                );
            }

            if (!isset($category['items']) || !is_array($category['items'])) {
                throw new \InvalidArgumentException(
                    "Category {$categoryName} must have an items array."
                );
            }

            foreach ($category['items'] as $itemIndex => $item) {
                if (!is_array($item)) {
                    throw new \InvalidArgumentException(
                        "Item {$itemIndex} in {$categoryName} must be an object."
                    );
                }

                $itemName = isset($item['name'])
                    ? trim((string) $item['name'])
                    : '';

                if ($itemName === '') {
                    throw new \InvalidArgumentException(
                        "An item in {$categoryName} has no name."
                    );
                }

                if (!isset($item['variations']) || !is_array($item['variations'])) {
                    throw new \InvalidArgumentException(
                        "Item {$itemName} must have a variations array."
                    );
                }

                foreach ($item['variations'] as $variationIndex => $variation) {
                    if (!is_array($variation)) {
                        throw new \InvalidArgumentException(
                            "Variation {$variationIndex} in {$itemName} must be an object."
                        );
                    }

                    if (!isset($variation['price']) || !is_numeric($variation['price'])) {
                        throw new \InvalidArgumentException(
                            "Variation {$variationIndex} in {$itemName} has an invalid price."
                        );
                    }
                }
            }
        }
    }

    private static function orderedName(
        string $name,
        int $order,
        int $total,
    ): string {
        $i = $order + 1;
        $pre = ($total > 10 && $i < 10) ? "0$i. " : "$i. ";
        return "{$pre}{$name}";
    }

    private static function find_or_create_category(
        string $name,
        int $order,
        int $total
    ): int {
        // $name = self::orderedName(trim($name), $order, $total);
        $name = str_pad($order + 1, 2, '0', STR_PAD_LEFT) . "." . trim($name);

        $term = term_exists($name, IFM_ITEM_TAXONOMY);

        if ($term) {
            $termId = (int) (is_array($term) ? $term['term_id'] : $term);
            // self::set_category_order($termId, $order);
            return $termId;
        }

        $result = wp_insert_term(
            $name,
            IFM_ITEM_TAXONOMY,
            [
                'slug' => sanitize_title($name),
            ]
        );

        if (is_wp_error($result)) {
            if ($result->get_error_code() === 'term_exists') {
                return (int) $result->get_error_data('term_exists');
            }

            throw new \RuntimeException(
                $result->get_error_message()
            );
        }

        $termId = (int) $result['term_id'];

        // self::set_category_order($termId, $order);

        update_term_meta(
            $termId,
            'appetit_items_category_meta',
            [
                'primary_lang_category_name' => $name,
                'secondary_lang_category_name' => '',
            ]
        );

        return $termId;
    }

    private static function set_category_order(int $termId, int $order): void
    {
        global $wpdb;

        $term = get_term($termId, IFM_ITEM_TAXONOMY);

        if (!$term || is_wp_error($term)) {
            return;
        }

        $wpdb->update(
            $wpdb->term_taxonomy,
            ['term_order' => $order],
            ['term_taxonomy_id' => (int) $term->term_taxonomy_id],
            ['%d'],
            ['%d']
        );
    }

    private static function import_item(
        array $item,
        int $categoryId,
        string $mode,
        int $order,
        int $total
    ): string {
        $name = trim((string) $item['name']);
        // $name = self::orderedName($name, $order, $total);
        $description = isset($item['description'])
            ? trim((string) $item['description'])
            : '';

        $existingId = self::find_existing_item($name);

        if ($existingId && $mode === 'skip') {
            return 'skipped';
        }

        $postData = [
            'post_title' => $name,
            'post_name' => sanitize_title($name),
            'post_content' => '',
            'post_excerpt' => $description,
            'post_status' => 'publish',
            'menu_order' => $order,
            'post_type' => IFM_ITEM_POST_TYPE,
        ];

        if ($existingId && $mode === 'update') {
            $postData['ID'] = $existingId;

            $postId = wp_update_post(
                wp_slash($postData),
                true
            );

            if (is_wp_error($postId)) {
                throw new \RuntimeException(
                    $postId->get_error_message()
                );
            }

            $result = 'updated';
        } else {
            $postId = wp_insert_post(
                wp_slash($postData),
                true
            );

            if (is_wp_error($postId)) {
                throw new \RuntimeException(
                    $postId->get_error_message()
                );
            }

            $result = 'created';
        }

        $postId = (int) $postId;
        $imageId = self::find_attachment_by_slug(get_post_field('post_name', $postId));
        // $imageId = 0;
        $termResult = wp_set_object_terms(
            $postId,
            [$categoryId],
            IFM_ITEM_TAXONOMY,
            false
        );

        if (is_wp_error($termResult)) {
            throw new \RuntimeException(
                $termResult->get_error_message()
            );
        }

        $meta = [
            'item_name_1' => $name,
            'item_name_2' => '',
            'item_description_1' => $description,
            'item_description_2' => '',
            'item_category' => [(string) $categoryId],
            'square_photo' => $imageId > 0 ? (string) $imageId : '0',
            'landscape_photo' => $imageId > 0 ? (string) $imageId : '0',
            'product_variations' => [],
        ];

        foreach ($item['variations'] as $variation) {
            $variationName = isset($variation['name'])
                ? trim((string) $variation['name'])
                : '';

            $meta['product_variations'][] = [
                'price' => self::format_price($variation['price']),
                'variation_name_1' => $variationName,
                'variation_name_2' => '',
                'ID' => self::generate_variation_id(),
            ];
        }

        if (!$meta['product_variations']) {
            $meta['product_variations'][] = [
                'price' => '0',
                'variation_name_1' => '',
                'variation_name_2' => '',
                'ID' => self::generate_variation_id(),
            ];
        }

        update_post_meta(
            $postId,
            IFM_ITEM_META_KEY,
            $meta
        );

        return $result;
    }

    private static function find_existing_item(string $name): int
    {
        $posts = get_posts([
            'post_type' => IFM_ITEM_POST_TYPE,
            'post_status' => 'any',
            'posts_per_page' => 1,
            'title' => $name,
            'fields' => 'ids',
            'no_found_rows' => true,
            'suppress_filters' => true,
        ]);

        if (!$posts) {
            $posts = get_posts([
                'post_type' => IFM_ITEM_POST_TYPE,
                'post_status' => 'any',
                'posts_per_page' => 1,
                'name' => sanitize_title($name),
                'fields' => 'ids',
                'no_found_rows' => true,
                'suppress_filters' => true,
            ]);
        }

        return $posts ? (int) $posts[0] : 0;
    }

    private static function generate_variation_id(): string
    {
        return '_id_' . bin2hex(random_bytes(6));
    }

    private static function format_price($price): string
    {
        return number_format(
            (float) $price,
            2,
            '.',
            ''
        );
    }


    private static function find_attachment_by_slug(string $slug): int
    {
        $slug = sanitize_title($slug);

        if ($slug === '') {
            return 0;
        }

        $attachments = get_posts([
            'post_type' => 'attachment',
            'post_status' => 'inherit',
            'posts_per_page' => 1,
            'fields' => 'ids',
            'name' => $slug,
            'no_found_rows' => true,
            'suppress_filters' => true,
        ]);

        return (int) ($attachments[0] ?? 0);
    }
}
