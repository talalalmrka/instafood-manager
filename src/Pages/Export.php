<?php

namespace Ifm\Pages;

use Ifm\Generic\Page;
use Ifm\Ifm;

class Export extends Page
{
    /* public static function title(): string
    {
        return __('Export');
    } */

    /* public static function slug(): string
    {
        return IFM_PAGE_SLUG . '-export';
    } */
    public static function icon(): string
    {
        return 'bi-box-arrow-up';
    }
    public static function boot(): void {}

    public static function render(): void
    {
        try {
            $data = self::build_export_data();

            $json = wp_json_encode(
                $data,
                JSON_PRETTY_PRINT |
                    JSON_UNESCAPED_UNICODE |
                    JSON_UNESCAPED_SLASHES
            );

            if ($json === false) {
                throw new \RuntimeException('Unable to generate JSON export.');
            }
        } catch (\Throwable $e) {
            $json = '';
            $error = $e->getMessage();
        } ?>
        <?php if (!empty($error)): ?>

            <div class="alert alert-error alert-soft sm">
                <p>
                    <strong>Export failed:</strong>
                    <?php echo esc_html($error); ?>
                </p>
            </div>

        <?php else: ?>
            <div class="flex flex-wrap items-center gap-2">
                <button
                    type="button"
                    class="btn btn-primary btn-sm"
                    id="btn-copy">
                    <i class="icon bi-clipboard"></i>
                    <span><?php _e('Copy'); ?></span>
                </button>
                <button
                    type="button"
                    class="btn btn-blue btn-sm"
                    id="btn-json">
                    <i class="icon bi-cloud-download"></i>
                    <span><?php _e('JSON'); ?></span>
                </button>
                <button
                    type="button"
                    class="btn btn-green btn-sm"
                    id="btn-xls">
                    <i class="icon bi-filetype-xls"></i>
                    <span><?php _e('Xls'); ?></span>
                </button>
            </div>

            <textarea
                id="textarea-json"
                readonly
                spellcheck="false"
                class="form-control xs font-mono! mt-3" rows="12"><?php echo esc_textarea($json); ?></textarea>

        <?php endif; ?>

<?php
    }
    private static function build_export_data(): array
    {
        $data = [
            'restaurant' => '',
            'location' => '',
            'url' => home_url('/'),
            'categories' => [],
        ];

        $terms = get_terms([
            'taxonomy' => IFM_ITEM_TAXONOMY,
            'hide_empty' => false,
            'orderby' => 'term_order',
            'order' => 'ASC',
        ]);

        if (is_wp_error($terms)) {
            throw new \RuntimeException(
                $terms->get_error_message()
            );
        }

        foreach ($terms as $term) {
            $items = get_posts([
                'post_type' => IFM_ITEM_POST_TYPE,
                'post_status' => 'any',
                'posts_per_page' => -1,
                'orderby' => 'menu_order',
                'order' => 'ASC',
                'tax_query' => [
                    [
                        'taxonomy' => IFM_ITEM_TAXONOMY,
                        'field' => 'term_id',
                        'terms' => $term->term_id,
                    ],
                ],
                'suppress_filters' => true,
            ]);

            $category = [
                'name' => self::remove_order_prefix($term->name),
                'items' => [],
            ];

            foreach ($items as $post) {
                $meta = get_post_meta(
                    $post->ID,
                    IFM_ITEM_META_KEY,
                    true
                );

                $description = get_post_field(
                    'post_excerpt',
                    $post->ID
                );

                $item = [
                    'name' => self::remove_order_prefix($post->post_title),
                    'description' => (string) $description,
                    'variations' => [],
                ];

                if (is_array($meta) && !empty($meta['product_variations'])) {
                    foreach ($meta['product_variations'] as $variation) {
                        if (!is_array($variation)) {
                            continue;
                        }

                        $item['variations'][] = [
                            'name' => isset($variation['variation_name_1'])
                                ? (string) $variation['variation_name_1']
                                : '',
                            'price' => isset($variation['price'])
                                ? (string) $variation['price']
                                : '0',
                        ];
                    }
                }

                if (!$item['variations']) {
                    $item['variations'][] = [
                        'name' => '',
                        'price' => '0',
                    ];
                }

                $category['items'][] = $item;
            }

            $data['categories'][] = $category;
        }

        return $data;
    }

    private static function remove_order_prefix(string $name): string
    {
        return preg_replace(
            '/^\d+\.\s*/',
            '',
            trim($name)
        ) ?? trim($name);
    }
}
