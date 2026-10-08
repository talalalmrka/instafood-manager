<?php

namespace Ifm\Services;

use WP_Post;

if (! defined('ABSPATH')) exit;

class ProductService
{
    /**
     * @param array $filters
     * @return \Ifm\Collections\PaginatedCollection
     */
    public static function all(array $filters = [])
    {
        $defaults = [
            "no_images" => false,
            "category" => 0,
            "status" => "any",
            "search" => "",
            "per_page" => -1,
            "page" => 1,
            "orderby" => "menu_order",
            "order" => "ASC",
        ];

        $filters = wp_parse_args($filters, $defaults);

        $args = [
            "post_type" => IFM_ITEM_POST_TYPE,
            "post_status" => $filters["status"],
            // "posts_per_page" => (int) $filters["per_page"],
            "posts_per_page" => -1,
            // "paged" => max(1, (int) $filters["page"]),
            "orderby" => $filters["orderby"],
            "order" => $filters["order"],
        ];

        if ($filters["search"] !== "") {
            $args["s"] = $filters["search"];
        }

        if ((int) $filters["category"] > 0) {
            $args["tax_query"] = [
                [
                    "taxonomy" => IFM_ITEM_TAXONOMY,
                    "field" => "term_id",
                    "terms" => (int) $filters["category"],
                ],
            ];
        }

        if ($filters["no_images"]) {
            $args["meta_query"] = [
                "relation" => "AND",
                [
                    "key" => IFM_ITEM_META_KEY,
                    "value" => '"square_photo";s:1:"0"',
                    "compare" => "LIKE",
                ],
                [
                    "key" => IFM_ITEM_META_KEY,
                    "value" => '"landscape_photo";s:1:"0"',
                    "compare" => "LIKE",
                ],
            ];
        }

        $products = get_posts($args);

        return is_wp_error($products)
            ? pcollect()
            : pcollect($products);
    }

    /**
     * Find a product by ID.
     */
    public static function find(int $id): ?WP_Post
    {
        $product = get_post($id);

        if (! $product instanceof WP_Post) {
            return null;
        }

        if ($product->post_type !== IFM_ITEM_POST_TYPE) {
            return null;
        }

        return $product;
    }

    public static function getProductImages(
        int $id,
        array $sizes = [
            'appetit-cover-large',
            'appetit-cover-medium',
            'appetit-aquare-large',
            'appetit-aquare-medium',
            'appetit-aquare-small',
        ]
    ): array {
        $images = [];

        if ($id > 0) {
            foreach ($sizes as $size) {
                $imageData = wp_get_attachment_image_src($id, $size);

                if (is_array($imageData) && isset($imageData[0])) {
                    $images[$size] = $imageData[0];
                }
            }
        }

        return $images;
    }

    public static function getProduct(int $productId): ?WP_Post
    {
        $product = self::find($productId);

        if (! $product || $product->post_status !== 'publish') {
            return null;
        }

        return $product;
    }

    public static function getProductMeta(int $productId)
    {
        return get_post_meta($productId, IFM_ITEM_META_KEY, true);
    }
}
