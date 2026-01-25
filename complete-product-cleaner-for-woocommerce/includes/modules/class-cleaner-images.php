<?php

/**
 * Cleaner Module: Images (Orphaned)
 *
 * @package CompleteProductCleaner
 */

if (! defined('ABSPATH')) {
    exit;
}

class Cleaner_Images extends Cleaner_Module
{

    public function get_id()
    {
        return 'images';
    }

    public function get_title()
    {
        return 'Orphaned Images';
    }

    public function count_items()
    {
        // This is expensive to count precisely every time, so we might estimate or just return "Unknown" initially?
        // Or we re-use the scan logic but optimized.
        // For now, let's just count total attachments as a proxy or 0 (initially).
        // Better: Do a quick query for unattached images.
        global $wpdb;
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
        $count = $wpdb->get_var("
			SELECT COUNT(ID) FROM {$wpdb->posts} 
			WHERE post_type = 'attachment' 
			AND post_mime_type LIKE 'image/%' 
			AND post_parent = 0
		");
        return (int) $count;
    }

    public function get_items_to_delete($limit = 50)
    {
        // We re-use logic similar to the old scan, but we need to limit it.
        // Finding orphans is hard to do with LIMIT because we have to check content usage.
        // Strategy: Get Batch of unattached images -> Check usage -> If used, skip (mark/ignore), if unused, delete.

        global $wpdb;

        // Get candidates (unattached)
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
        $candidates = $wpdb->get_col($wpdb->prepare("
			SELECT ID FROM {$wpdb->posts} 
			WHERE post_type = 'attachment' 
			AND post_mime_type LIKE 'image/%' 
			AND post_parent = 0
			LIMIT %d
		", $limit * 2)); // Get more because some might be used

        $to_delete = array();

        foreach ($candidates as $id) {
            if (count($to_delete) >= $limit) break;

            if ($this->is_orphan($id)) {
                $to_delete[] = $id;
            }
        }

        return $to_delete;
    }

    private function is_orphan($image_id)
    {
        global $wpdb;

        // 1. Check if used as Site Icon/Logo (Basic checks)
        if (get_option('site_logo') == $image_id) return false;
        if (get_option('site_icon') == $image_id) return false;
        if (get_theme_mod('custom_logo') == $image_id) return false;

        // 2. Check if used in Post Content
        $guid = get_the_guid($image_id);
        $filename = basename($guid);

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
        $in_content = $wpdb->get_var($wpdb->prepare(
            "SELECT ID FROM {$wpdb->posts} 
			 WHERE post_content LIKE %s 
			 AND post_status NOT IN ('trash', 'auto-draft', 'inherit') 
			 LIMIT 1",
            '%' . $wpdb->esc_like($filename) . '%'
        ));

        if ($in_content) return false;

        // 3. Check Post Meta (_thumbnail_id)
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
        $is_thumbnail = $wpdb->get_var($wpdb->prepare(
            "SELECT meta_id FROM {$wpdb->postmeta} WHERE meta_key = '_thumbnail_id' AND meta_value = %d LIMIT 1",
            $image_id
        ));

        if ($is_thumbnail) return false;

        return true;
    }

    public function delete_item($id, $options = array())
    {
        return wp_delete_attachment($id, true);
    }
}
