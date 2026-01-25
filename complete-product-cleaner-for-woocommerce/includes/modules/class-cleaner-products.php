<?php

/**
 * Cleaner Module: Products
 *
 * @package CompleteProductCleaner
 */

if (! defined('ABSPATH')) {
    exit;
}

class Cleaner_Products extends Cleaner_Module
{

    public function get_id()
    {
        return 'products';
    }

    public function get_title()
    {
        return 'Products';
    }

    public function count_items()
    {
        $counts = wp_count_posts('product');
        $variation_counts = wp_count_posts('product_variation');

        $total = 0;
        if (isset($counts->publish)) $total += $counts->publish;
        if (isset($counts->draft)) $total += $counts->draft;
        if (isset($counts->pending)) $total += $counts->pending;
        if (isset($counts->private)) $total += $counts->private;
        if (isset($counts->trash)) $total += $counts->trash;

        if (isset($variation_counts->publish)) $total += $variation_counts->publish;

        return $total;
    }

    public function get_items_to_delete($limit = 50)
    {
        global $wpdb;

        // Get products and variations
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
        return $wpdb->get_col($wpdb->prepare("
			SELECT ID FROM {$wpdb->posts} 
			WHERE post_type IN ('product', 'product_variation')
			LIMIT %d
		", $limit));
    }

    public function delete_item($id, $options = array())
    {
        // Check options
        $delete_attached = isset($options['delete_attached_images']);

        if ($delete_attached) {
            // Find attached images
            $attachments = get_children(array(
                'post_parent' => $id,
                'post_type'   => 'attachment',
                'post_mime_type' => 'image',
            ));

            foreach ($attachments as $attachment) {
                wp_delete_attachment($attachment->ID, true);
            }

            // Also featured image
            $thumb_id = get_post_meta($id, '_thumbnail_id', true);
            if ($thumb_id) {
                wp_delete_attachment($thumb_id, true);
            }
        }

        return wp_delete_post($id, true);
    }

    public function cleanup()
    {
        // Post-cleanup specific to products
        if (function_exists('wc_delete_product_transients')) {
            wc_delete_product_transients();
        }

        // If "Delete Orphaned Images" was selected, we should trigger that module?
        // For now just basic cleanup.
    }
}
