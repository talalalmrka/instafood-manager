<?php

namespace Ifm\Pages;

use Ifm\Ifm;
use Ifm\Generic\Page;

class FixImages extends Page
{
    public static function title(): string
    {
        return __('Fix images');
    }

    public static function slug(): string
    {
        return IFM_PAGE_SLUG . '-fix-images';
    }

    public static function boot(): void {}

    public static function render(): void
    {
        if (!current_user_can('manage_options')) {
            wp_die('You do not have permission to access this page.');
        }

        $result = get_transient(self::resultKey());

        if ($result !== false) {
            delete_transient(self::resultKey());
        }
?>

        <div class="wrap">
            <h1><?php echo esc_html(self::title()); ?></h1>
            <div class="page-wrapper">
                <?php self::renderResult($result); ?>
                <div class="alert alert-info alert-soft sm mb-3">
                    This tool finds WordPress media attachments using the product
                    slug and updates the product image fields.
                </div>

                <form
                    method="post"
                    action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                    <input
                        type="hidden"
                        name="action"
                        value="instafood_fix_images">

                    <?php wp_nonce_field('instafood_fix_images'); ?>

                    <button
                        type="submit"
                        class="btn btn-primary">
                        Fix Product Images
                    </button>
                </form>
            </div>
        </div>
<?php
    }

    public static function handle_fix_images(): void
    {
        if (!current_user_can('manage_options')) {
            wp_die('You do not have permission to perform this action.');
        }

        check_admin_referer('instafood_fix_images');

        $items = get_posts([
            'post_type' => IFM_ITEM_POST_TYPE,
            'post_status' => 'any',
            'posts_per_page' => -1,
            'fields' => 'ids',
            'no_found_rows' => true,
            'suppress_filters' => true,
        ]);

        $checked = 0;
        $found = 0;
        $updated = 0;
        $notFound = 0;

        foreach ($items as $postId) {
            $checked++;

            $postId = (int) $postId;

            $slug = get_post_field(
                'post_name',
                $postId
            );

            if (!$slug) {
                $notFound++;
                continue;
            }

            /*$attachments = get_posts([
            'post_type' => 'attachment',
            'post_status' => 'inherit',
            'posts_per_page' => 1,
            'fields' => 'ids',
            'name' => sanitize_title($slug),
            'no_found_rows' => true,
            'suppress_filters' => true,
        ]);*/

            $imageId = find_attachment_by_filename($slug . ".jpg");

            if (!$imageId) {
                $notFound++;
                continue;
            }

            $found++;

            $meta = get_post_meta(
                $postId,
                IFM_ITEM_META_KEY,
                true
            );

            if (!is_array($meta)) {
                $meta = [];
            }

            $meta['square_photo'] = (string) $imageId;
            $meta['landscape_photo'] = (string) $imageId;

            update_post_meta(
                $postId,
                IFM_ITEM_META_KEY,
                $meta
            );

            $updated++;
        }

        set_transient(
            'instafood_fix_images_' . get_current_user_id(),
            [
                'checked' => $checked,
                'found' => $found,
                'updated' => $updated,
                'not_found' => $notFound,
            ],
            60
        );

        wp_safe_redirect(
            admin_url(
                'admin.php?page=' . self::slug() . '-fix-images'
            )
        );

        exit;
    }
}
