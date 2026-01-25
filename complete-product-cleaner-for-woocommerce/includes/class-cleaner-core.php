<?php

/**
 * Cleaner Core Class
 *
 * @package CompleteProductCleaner
 */

if (! defined('ABSPATH')) {
    exit;
}

class Cleaner_Core
{

    private static $modules = array();
    private $scheduler;

    public function __construct()
    {
        $this->scheduler = new Cleaner_Scheduler();

        $this->load_modules();

        // Admin hooks
        add_action('admin_init', array($this, 'check_dependencies'));

        // AJAX
        add_action('wp_ajax_wccc_start_cleanup', array($this, 'ajax_start_cleanup'));
        add_action('wp_ajax_wccc_get_status', array($this, 'ajax_get_status'));
        add_action('wp_ajax_wccc_get_counts', array($this, 'ajax_get_counts'));
    }

    private function load_modules()
    {
        $modules = array(
            'products'   => 'Cleaner_Products',
            'orders'     => 'Cleaner_Orders',
            'customers'  => 'Cleaner_Customers',
            'taxonomies' => 'Cleaner_Taxonomies',
            'coupons'    => 'Cleaner_Coupons',
            'wp_content' => 'Cleaner_WP_Content',
            'images'     => 'Cleaner_Images',
        );

        foreach ($modules as $id => $class_name) {
            $file = plugin_dir_path(__FILE__) . 'modules/class-cleaner-' . str_replace('_', '-', $id) . '.php';
            if (file_exists($file)) {
                require_once $file;
                if (class_exists($class_name)) {
                    self::register_module(new $class_name());
                }
            }
        }
    }

    public static function register_module(Cleaner_Module $module)
    {
        self::$modules[$module->get_id()] = $module;
    }

    public static function get_module($id)
    {
        return isset(self::$modules[$id]) ? self::$modules[$id] : null;
    }

    public function check_dependencies()
    {
        if (! class_exists('WooCommerce')) {
            add_action('admin_notices', function () {
                echo '<div class="notice notice-error"><p>Complete Product Cleaner requires WooCommerce to be active.</p></div>';
            });
        }
        if (! class_exists('ActionScheduler')) {
            // In theory WC comes with AS, but good to check or bundle if needed.
            // For now assuming WC environment.
        }
    }

    public function ajax_start_cleanup()
    {
        check_ajax_referer('wccc_cleanup_nonce', 'nonce');

        if (! current_user_can('manage_options')) {
            wp_send_json_error('Permission denied');
        }

        $type = isset($_POST['type']) ? sanitize_text_field($_POST['type']) : '';
        $options = isset($_POST['options']) ? (array) $_POST['options'] : array();

        if (! $type || ! self::get_module($type)) {
            wp_send_json_error('Invalid module type');
        }

        $this->scheduler->start_cleanup($type, $options);

        wp_send_json_success(array('message' => 'Cleanup started'));
    }

    public function ajax_get_status()
    {
        check_ajax_referer('wccc_cleanup_nonce', 'nonce');
        $status = $this->scheduler->get_status();
        wp_send_json_success(array('status' => $status));
    }

    public function ajax_get_counts()
    {
        check_ajax_referer('wccc_cleanup_nonce', 'nonce');

        $counts = array();
        foreach (self::$modules as $id => $module) {
            $counts[$id] = $module->count_items();
        }

        wp_send_json_success($counts);
    }
}
