<?php

if (!defined('ABSPATH')) {
    exit;
}
if (!function_exists('find_attachment_by_filename')) {
    function find_attachment_by_filename(string $filename)
    {
        $filename = basename($filename);

        $attachments = get_posts([
            'post_type'      => 'attachment',
            'post_status'    => 'inherit',
            'posts_per_page' => 1,
            'fields'         => 'ids',
            'meta_query'     => [
                [
                    'key'     => '_wp_attached_file',
                    'value'   => $filename,
                    'compare' => 'LIKE',
                ],
            ],
        ]);
        return (int) ($attachments[0] ?? 0);
    }
}

if (!function_exists('get_image_by_file_name')) {
    function get_image_by_file_name(string $filename)
    {
        $imageId = find_attachment_by_filename($filename);
        $imageUrl = $imageId
            ? wp_get_attachment_image_url($imageId, 'thumbnail')
            : '';
        return $imageUrl;
    }
}
