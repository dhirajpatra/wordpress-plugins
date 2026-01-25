<?php

/**
 * Cleaner Module: Orders
 *
 * @package CompleteProductCleaner
 */

if (! defined('ABSPATH')) {
    exit;
}

class Cleaner_Orders extends Cleaner_Module
{

    public function get_id()
    {
        return 'orders';
    }

    public function get_title()
    {
        return 'Orders';
    }

    public function count_items()
    {
        $counts = wp_count_posts('shop_order');
        $refunds = wp_count_posts('shop_order_refund');

        $total = 0;
        // Sum all statuses
        foreach ((array) $counts as $status => $count) {
            $total += $count;
        }
        foreach ((array) $refunds as $status => $count) {
            $total += $count;
        }

        return $total;
    }

    public function get_items_to_delete($limit = 50)
    {
        global $wpdb;

        // Get orders and refunds
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
        return $wpdb->get_col($wpdb->prepare("
			SELECT ID FROM {$wpdb->posts} 
			WHERE post_type IN ('shop_order', 'shop_order_refund')
			LIMIT %d
		", $limit));
    }

    public function delete_item($id, $options = array())
    {
        return wp_delete_post($id, true);
    }
}
