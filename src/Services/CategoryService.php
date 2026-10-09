<?php

namespace Ifm\Services;

if (! defined('ABSPATH')) exit;

use Illuminate\Support\Arr;
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
    public static function prepareQueryArgs(array $args = []): array
    {
        $defaults = [
            "taxonomy"   => IFM_ITEM_TAXONOMY,
            "hide_empty" => false,
            "per_page" => 15,
            "paged" => 1,
        ];
        $args = wp_parse_args($args, $defaults);

        $search = sanitize_text_field(data_get($args, 'search'));
        if (!empty($search)) {
            $args["search"] = $search;
        }

        $orderby = sanitize_text_field(data_get($args, 'orderby'));
        if (!empty($orderby)) {
            $args["orderby"] = $orderby;
        }

        $order = sanitize_text_field(data_get($args, 'order'));
        if (!empty($order)) {
            $args["order"] = $order;
        }

        $per_page = sanitize_text_field(data_get($args, 'per_page'));
        if (!empty($per_page)) {
            $args['number'] = $per_page;
        }

        $paged = sanitize_text_field(data_get($args, 'paged'));
        if (!empty($paged)) {
            $args['offset'] = $paged;
        }
        return $args;
    }

    /**
     * @param array $args
     * @return \Illuminate\Support\Collection
     */
    public static function all(array $args = [])
    {
        $args = self::prepareQueryArgs($args);
        $terms = get_terms($args);
        return is_wp_error($terms) ? collect() : collect($terms);
    }


    /**
     * Handle complete paginated response data structure to keep controller clean.
     * 
     * @param array $args Typically $_REQUEST or validated input array
     * @return array
     */
    public static function paginate(array $args = []): array
    {
        $args = self::prepareQueryArgs($args);
        $number = data_get($args, 'number');
        $offset     = data_get($args, 'offset');

        // 1. Fetch filtered items
        $items = self::all($args);

        // 2. Fetch total counts matching filters
        $total_items = self::total($args);

        // 3. Compute total pages
        $total_pages = $number > 0 ? (int) ceil($total_items / $number) : 1;

        return [
            'items'      => $items,
            'pagination' => [
                'total_items'  => $total_items,
                'total_pages'  => $total_pages,
                'current_page' => (int) $offset,
                'per_page'     => (int) $number,
            ]
        ];
    }

    public static function links() {}

    /**
     * Get total terms matching the filters (ignoring limits/pagination).
     * 
     * @param array $args
     * @return int
     */
    public static function total(array $args = []): int
    {
        $args = self::prepareQueryArgs($args);
        $args['fields'] = 'count';
        unset($args['number'], $args['offset']);

        $count = get_terms(Arr::except($args, ['number', 'offset']));
        return is_wp_error($count) ? 0 : (int) $count;
    }

    /**
     * Get total pages based on filters and per_page limits.
     * 
     * @param array $args
     * @return int
     */
    public static function totalPages(array $args = []): int
    {
        $args = self::prepareQueryArgs($args);
        $per_page = data_get($args, 'per_page') ?? data_get($args, 'number');

        if ($per_page <= 0) {
            return 1;
        }

        $total_items = self::total($args);
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
