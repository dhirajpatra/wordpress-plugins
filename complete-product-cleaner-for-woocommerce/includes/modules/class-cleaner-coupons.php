<?php

/**
 * Cleaner Module: Coupons
 *
 * @package CompleteProductCleaner
 */

if (! defined('ABSPATH')) {
    exit;
}

class Cleaner_Coupons extends Cleaner_Module
{

    public function get_id()
    {
        return 'coupons';
    }

    public function get_title()
    {
        return 'Coupons';
    }

    public function count_items()
    {
        $counts = wp_count_posts('shop_coupon');

        $total = 0;
        if (isset($counts->publish)) $total += $counts->publish;
        if (isset($counts->draft)) $total += $counts->draft;
        if (isset($counts->pending)) $total += $counts->pending;
        if (isset($counts->private)) $total += $counts->private;
        if (isset($counts->trash)) $total += $counts->trash;

        return $total;
    }

    public function get_items_to_delete($limit = 50)
    {
        global $wpdb;

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
        return $wpdb->get_col($wpdb->prepare("
			SELECT ID FROM {$wpdb->posts} 
			WHERE post_type = 'shop_coupon'
			LIMIT %d
		", $limit));
    }

    public function delete_item($id, $options = array())
    {
        return wp_delete_post($id, true);
    }
}
