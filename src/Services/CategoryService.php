<?php

namespace Ifm\Services;

if (! defined('ABSPATH')) exit;

use WP_Term;

class CategoryService
{
    public static function all(array $filters = []): array
    {
        $args = array_merge(
            [
                "taxonomy" => IFM_ITEM_TAXONOMY,
                "hide_empty" => false,
                "orderby" => "term_order",
                "order" => "ASC",
            ],
            $filters
        );

        $terms = get_terms($args);

        if (is_wp_error($terms)) {
            return [];
        }

        return $terms;
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

    public static function search(string $search): array
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
