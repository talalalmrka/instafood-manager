<?php

namespace Ifm\Services;

if (! defined('ABSPATH')) exit;

use WP_Term;

class CategoryService
{
    public static function prepareFilters(array $filters = []): array
    {
        $defaults = [
            "orderby"  => "",
            "order"    => "",
            "search"   => "",
            "per_page" => -1,
            "page"     => 1,
        ];

        // Merge passed filters into defaults
        $parsed_filters = wp_parse_args($filters, $defaults);
        return $parsed_filters;
    }
    /**
     * Helper to prepare common query arguments from given filters.
     * Keeps your code DRY across all(), total(), and totalPages().
     */
    public static function prepareQueryArgs(array $filters = []): array
    {
        // Merge passed filters into defaults
        $parsed_filters = self::prepareFilters($filters);

        $args = [
            "taxonomy"   => IFM_ITEM_TAXONOMY,
            "hide_empty" => false,
            // "orderby"    => sanitize_key($parsed_filters["orderby"]),
            // "order"      => sanitize_key($parsed_filters["order"]),
        ];

        $search_term = sanitize_text_field($parsed_filters["search"]);
        if ($search_term !== "") {
            $args["search"] = $search_term;
        }

        $order_by = sanitize_text_field($parsed_filters["orderby"]);
        if ($order_by !== "") {
            $args["order_by"] = $order_by;
        }

        $order = sanitize_text_field($parsed_filters["order"]);
        if ($order !== "") {
            $args["order"] = $order;
        }

        return [
            'args'     => $args,
            'per_page' => (int) $parsed_filters["per_page"],
            'page'     => max(1, (int) $parsed_filters["page"]),
        ];
    }

    /**
     * @param array $filters
     * @return \Illuminate\Support\Collection
     */
    public static function all(array $filters = [])
    {
        $prepared = self::prepareQueryArgs($filters);
        $args     = $prepared['args'];
        $per_page = $prepared['per_page'];
        $page     = $prepared['page'];

        // Handle Pagination Parameters
        if ($per_page > 0) {
            $args["number"] = $per_page;
            $args["offset"] = ($page - 1) * $per_page;
        }

        // Fetch items
        $terms = get_terms($args);
        return is_wp_error($terms) ? collect() : collect($terms);
    }


    /**
     * Handle complete paginated response data structure to keep controller clean.
     * 
     * @param array $requestData Typically $_REQUEST or validated input array
     * @return array
     */
    public static function paginate(array $requestData = []): array
    {
        $prepared = self::prepareQueryArgs($requestData);
        $per_page = $prepared['per_page'];
        $page     = $prepared['page'];

        // 1. Fetch filtered items
        $items = self::all($requestData);

        // 2. Fetch total counts matching filters
        $total_items = self::total($requestData);

        // 3. Compute total pages
        $total_pages = $per_page > 0 ? (int) ceil($total_items / $per_page) : 1;

        return [
            'items'      => $items,
            'pagination' => [
                'total_items'  => $total_items,
                'total_pages'  => $total_pages,
                'current_page' => $page,
                'per_page'     => $per_page,
            ]
        ];
    }
    /**
     * Get total terms matching the filters (ignoring limits/pagination).
     * 
     * @param array $filters
     * @return int
     */
    public static function total(array $filters = []): int
    {
        $prepared = self::prepareQueryArgs($filters);
        $args     = $prepared['args'];

        // Force 'count' fields to get a performance-optimized integer back
        $args['fields'] = 'count';
        unset($args['number'], $args['offset']);

        $count = get_terms($args);
        return is_wp_error($count) ? 0 : (int) $count;
    }

    /**
     * Get total pages based on filters and per_page limits.
     * 
     * @param array $filters
     * @return int
     */
    public static function totalPages(array $filters = []): int
    {
        $prepared = self::prepareQueryArgs($filters);
        $per_page = $prepared['per_page'];

        if ($per_page <= 0) {
            return 1;
        }

        $total_items = self::total($filters);
        return (int) ceil($total_items / $per_page);
    }

    public static function find(int $id): ?WP_Term
    {
        $term = get_term($id, IFM_ITEM_TAXONOMY);

        if (is_wp_error($term) || !$term instanceof WP_Term) {
            return null;
        }

        return $term;
    }

    public static function findByName(string $name): ?WP_Term
    {
        $term = get_term_by("name", $name, IFM_ITEM_TAXONOMY);

        return $term instanceof WP_Term ? $term : null;
    }

    public static function create(string $name): ?WP_Term
    {
        $result = wp_insert_term($name, IFM_ITEM_TAXONOMY);

        if (is_wp_error($result)) {
            return null;
        }

        return self::find((int) $result["term_id"]);
    }

    public static function update(int $id, string $name): ?WP_Term
    {
        $result = wp_update_term($id, IFM_ITEM_TAXONOMY, [
            "name" => $name,
        ]);

        if (is_wp_error($result)) {
            return null;
        }

        return self::find($id);
    }

    public static function delete(int $id): bool
    {
        $result = wp_delete_term($id, IFM_ITEM_TAXONOMY);

        return !is_wp_error($result) && $result !== false;
    }

    public static function count(): int
    {
        $count = wp_count_terms([
            "taxonomy" => IFM_ITEM_TAXONOMY,
        ]);

        return is_wp_error($count) ? 0 : (int) $count;
    }

    /**
     * @param string $search
     * @return \Illuminate\Support\Collection
     */
    public static function search(string $search)
    {
        return self::all([
            "search" => $search,
        ]);
    }

    public static function reorder(array $ids): bool
    {
        global $wpdb;

        foreach ($ids as $order => $id) {
            $wpdb->update(
                $wpdb->term_taxonomy,
                [
                    "term_order" => $order,
                ],
                [
                    "term_id" => (int) $id,
                ],
                ["%d"],
                ["%d"]
            );
        }

        clean_term_cache($ids, IFM_ITEM_TAXONOMY);

        return true;
    }
}
