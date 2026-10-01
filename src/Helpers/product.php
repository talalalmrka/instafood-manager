<?php

if (!defined("ABSPATH")) {
    exit();
}

if (!function_exists("get_products")) {
    function get_products(array $filters = []): array
    {
        $defaults = [
            "no_images" => false,
            "category" => 0,
            "status" => "any",
            "search" => "",
            "limit" => -1,
            "page" => 1,
            "orderby" => "menu_order",
            "order" => "ASC",
        ];

        $filters = wp_parse_args($filters, $defaults);

        $args = [
            "post_type" => self::ITEM_POST_TYPE,
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
}
