<?php

/**
 * Cleaner Module: Taxonomies
 *
 * @package CompleteProductCleaner
 */

if (! defined('ABSPATH')) {
    exit;
}

class Cleaner_Taxonomies extends Cleaner_Module
{

    public function get_id()
    {
        return 'taxonomies';
    }

    public function get_title()
    {
        return 'Taxonomies (Categories, Tags)';
    }

    private function get_taxonomies()
    {
        $taxonomies = array('product_cat', 'product_tag', 'product_shipping_class');
        // Add attribute taxonomies
        if (function_exists('wc_get_attribute_taxonomies')) {
            $attributes = wc_get_attribute_taxonomies();
            foreach ($attributes as $attribute) {
                $taxonomies[] = 'pa_' . $attribute->attribute_name;
            }
        }
        return $taxonomies;
    }

    public function count_items()
    {
        $taxonomies = $this->get_taxonomies();
        $count = 0;
        foreach ($taxonomies as $tax) {
            $count += wp_count_terms(array('taxonomy' => $tax, 'hide_empty' => false));
        }
        return $count;
    }

    public function get_items_to_delete($limit = 50)
    {
        global $wpdb;
        $taxonomies = $this->get_taxonomies();
        $tax_sql = "'" . implode("','", array_map('esc_sql', $taxonomies)) . "'";

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
        return $wpdb->get_col($wpdb->prepare("
			SELECT term_id FROM {$wpdb->term_taxonomy}
			WHERE taxonomy IN ($tax_sql)
			LIMIT %d
		", $limit));
    }

    public function delete_item($id, $options = array())
    {
        // We need to find the taxonomy for this term_id to delete it properly
        $term = get_term($id);
        if (! $term || is_wp_error($term)) {
            return false;
        }
        return wp_delete_term($id, $term->taxonomy);
    }
}
