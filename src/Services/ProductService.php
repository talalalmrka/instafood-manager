<?php

namespace Ifm\Services;


if (! defined('ABSPATH')) exit;

class ProductService
{

    private static ?ProductService $instance = null;
    private $products = [];
    public static function all(array $filters = []): array
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
            "posts_per_page" => (int) $filters["per_page"],
            "paged" => max(1, (int) $filters["page"]),
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

        return get_posts($args);
    }
    public function getAllProducts()
    {
        if (isset($this->products) && is_array($this->products) && sizeof($this->products) > 0) {
            return $this->products;
        }
        $args = [
            'posts_per_page' => -1,
            'post_type'      => IFM_ITEM_POST_TYPE,
            'post_status'    => 'publish',
            'orderby' => 'menu_order',
            'order' => 'ASC',
        ];
        $the_query = new \WP_Query($args);
        if ($the_query->have_posts()) {
            while ($the_query->have_posts()) {
                $the_query->the_post();
                $post_id = get_the_ID();
                $post_meta = get_post_meta($post_id, IFM_ITEM_META_KEY, true);

                $product = new \stdClass();
                $product->ID = get_the_ID();
                $product->title = get_the_title();
                $product->meta = $post_meta;
                if (isset($product->meta)) {
                    if (isset($product->meta['product_variations']) && is_array($product->meta['product_variations'])) {
                        /* foreach ($product->meta['product_variations'] as $key => $variation) {
                            if (isset($variation['price']) && $variation['price'] !== '') {
                                $product->meta['product_variations'][$key]['price_display'] = PriceUtil::getInstance()->getPriceDisplay($variation['price']);
                            } else {
                                $product->meta['product_variations'][$key]['price_display'] = PriceUtil::getInstance()->getPriceDisplay(0);
                            }
                        } */
                    }
                    if (isset($product->meta['square_photo']) && $product->meta['square_photo'] !== '') {
                        $product->meta['square_photo_images'] = $this->getProductImages($product->meta['square_photo']);
                    }
                    if (isset($product->meta['landscape_photo']) && $product->meta['landscape_photo'] !== '') {
                        $product->meta['landscape_photo_images'] = $this->getProductImages($product->meta['landscape_photo']);
                    }
                }
                array_push($this->products, $product);
            }
        }
        wp_reset_postdata();
        wp_reset_query();
        return $this->products;
    }

    public function getProductImages(int $id, array $sizes = [
        'appetit-cover-large',
        'appetit-cover-medium',
        'appetit-aquare-large',
        'appetit-aquare-medium',
        'appetit-aquare-small'
    ]): array
    {
        $images = [];
        if (isset($id) && $id !== '') {
            foreach ($sizes as $size) {
                $image_data = wp_get_attachment_image_src($id, $size);
                if (is_array($image_data) && isset($image_data[0])) {
                    $images[$size] = $image_data[0];
                }
            }
        }
        return $images;
    }

    public static function getProduct(int $productId)
    {
        $postResult = get_post($productId);
        if ($postResult->post_status !== 'publish') {
            return false;
        }
        return $postResult;
    }

    public static function getProductMeta(int $productId)
    {
        return get_post_meta($productId, IFM_ITEM_META_KEY, true);
    }

    public static function getInstance()
    {
        if (self::$instance == null) {
            self::$instance = new ProductService();
        }
        return self::$instance;
    }
}
