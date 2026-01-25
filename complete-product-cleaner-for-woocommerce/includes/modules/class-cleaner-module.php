<?php

/**
 * Abstract Cleaner Module
 *
 * @package CompleteProductCleaner
 */

if (! defined('ABSPATH')) {
    exit;
}

abstract class Cleaner_Module
{

    /**
     * Get the unique ID for this module (e.g., 'products', 'orders').
     *
     * @return string
     */
    abstract public function get_id();

    /**
     * Get the public title for this module.
     *
     * @return string
     */
    abstract public function get_title();

    /**
     * Count items available to delete.
     *
     * @return int
     */
    abstract public function count_items();

    /**
     * Get a batch of item IDs to delete.
     *
     * @param int $limit Max items to retrieve.
     * @return array List of IDs.
     */
    abstract public function get_items_to_delete($limit = 50);

    /**
     * Delete a single item by ID.
     *
     * @param int $id Item ID.
     * @param array $options Optional settings.
     * @return bool|WP_Error True on success, error otherwise.
     */
    abstract public function delete_item($id, $options = array());

    /**
     * Perform any post-cleanup actions (e.g., clearing transients).
     */
    public function cleanup()
    {
        // Optional override.
    }
}
