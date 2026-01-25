<?php

/**
 * Cleaner Module: Customers
 *
 * @package CompleteProductCleaner
 */

if (! defined('ABSPATH')) {
    exit;
}

class Cleaner_Customers extends Cleaner_Module
{

    public function get_id()
    {
        return 'customers';
    }

    public function get_title()
    {
        return 'Customers';
    }

    public function count_items()
    {
        $query = new WP_User_Query(array(
            'role'   => 'customer',
            'fields' => 'ID',
            'number' => 1, // Minimize load, just need total
            'count_total' => true
        ));
        return $query->get_total();
    }

    public function get_items_to_delete($limit = 50)
    {
        $query = new WP_User_Query(array(
            'role'   => 'customer',
            'fields' => 'ID',
            'number' => $limit
        ));
        return $query->get_results();
    }

    public function delete_item($id, $options = array())
    {
        require_once(ABSPATH . 'wp-admin/includes/user.php');
        return wp_delete_user($id);
    }
}
