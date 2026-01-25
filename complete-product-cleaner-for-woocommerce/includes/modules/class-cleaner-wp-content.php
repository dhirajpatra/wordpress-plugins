<?php

/**
 * Cleaner Module: WP Content (Posts & Pages)
 *
 * @package CompleteProductCleaner
 */

if (! defined('ABSPATH')) {
    exit;
}

class Cleaner_WP_Content extends Cleaner_Module
{

    public function get_id()
    {
        return 'wp_content';
    }

    public function get_title()
    {
        return 'Posts & Pages';
    }

    public function count_items()
    {
        $posts = wp_count_posts('post');
        $pages = wp_count_posts('page');

        $total = 0;
        // Add up posts
        foreach ((array) $posts as $status => $count) {
            $total += $count;
        }
        // Add up pages
        foreach ((array) $pages as $status => $count) {
            $total += $count;
        }

        return $total;
    }

    public function get_items_to_delete($limit = 50)
    {
        global $wpdb;

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
        return $wpdb->get_col($wpdb->prepare("
			SELECT ID FROM {$wpdb->posts} 
			WHERE post_type IN ('post', 'page')
			LIMIT %d
		", $limit));
    }

    public function delete_item($id, $options = array())
    {
        return wp_delete_post($id, true);
    }
}
