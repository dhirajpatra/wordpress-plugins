<?php

/**
 * Plugin Name: Complete Product Cleaner for WooCommerce
 * Plugin URI: https://wordpress.org/plugins/complete-product-cleaner-for-woocommerce
 * Description: Delete all WooCommerce products with options to remove related images and orphaned attachments
 * Version: 1.1.1
 * Author: Dhiraj Patra
 * Author URI: https://github.com/DhirajPatra
 * License: GPL v2 or later
 * Text Domain: complete-product-cleaner-for-woocommerce
 * Requires Plugins: woocommerce
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define Constants
define('WCCC_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('WCCC_PLUGIN_URL', plugin_dir_url(__FILE__));
define('WCCC_VERSION', '1.1.1');

// Include Core
require_once WCCC_PLUGIN_DIR . 'includes/modules/class-cleaner-module.php';
require_once WCCC_PLUGIN_DIR . 'includes/class-cleaner-scheduler.php';
require_once WCCC_PLUGIN_DIR . 'includes/class-cleaner-core.php';

// Initialize
add_action('plugins_loaded', 'wccc_init');
function wccc_init()
{
    new Cleaner_Core();
}

// Add admin menu
add_action('admin_menu', 'wccc_add_admin_menu');
function wccc_add_admin_menu()
{
    add_submenu_page(
        'woocommerce',
        'Product Cleaner',
        'Product Cleaner',
        'manage_options',
        'complete-product-cleaner-for-woocommerce',
        'wccc_admin_page'
    );
}

// Enqueue scripts and styles
add_action('admin_enqueue_scripts', 'wccc_enqueue_admin_assets');
function wccc_enqueue_admin_assets($hook)
{
    if ($hook != 'woocommerce_page_complete-product-cleaner-for-woocommerce') {
        return;
    }

    wp_enqueue_style('wccc-admin-css', WCCC_PLUGIN_URL . 'css/complete-product-cleaner-for-woocommerce.css', array(), WCCC_VERSION);
    wp_enqueue_script('wccc-admin-js', WCCC_PLUGIN_URL . 'js/complete-product-cleaner-for-woocommerce.js', array('jquery'), WCCC_VERSION, true);

    wp_localize_script('wccc-admin-js', 'wcCleanerData', array(
        'ajaxUrl' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('wccc_cleanup_nonce'),
        'confirmMessage' => esc_html__('Are you sure you want to delete these items? This action cannot be undone!', 'complete-product-cleaner-for-woocommerce')
    ));
}

// Admin page content
function wccc_admin_page()
{
    if (!current_user_can('manage_options')) {
        wp_die(esc_html__('You do not have sufficient permissions to access this page.', 'complete-product-cleaner-for-woocommerce'));
    }

    // Get list of modules for tabs
    $modules = array(
        'products' => 'Products',
        'orders' => 'Orders',
        'customers' => 'Customers',
        'taxonomies' => 'Taxonomies',
        'coupons' => 'Coupons',
        'wp_content' => 'WP Content',
        'images' => 'Orphaned Images'
    );
?>
    <div class="wrap">
        <div class="wc-cleaner-wrapper">
            <div class="wc-cleaner-header">
                <h1>Complete Product Cleaner for WooCommerce</h1>
                <p>Advanced cleaner for your WooCommerce store. <strong style="color: #d63638;">Warning: Deletions are permanent. Back up your database first!</strong></p>
                <!-- Global Status Indicator -->
                <div id="wccc-global-status" class="wccc-status-idle">
                    Status: <span class="status-text">Idle</span>
                </div>
            </div>

            <div class="wc-cleaner-tabs">
                <div class="nav-tab-wrapper">
                    <?php foreach ($modules as $id => $label) : ?>
                        <button class="nav-tab wc-cleaner-tab <?php echo ($id === 'products') ? 'nav-tab-active' : ''; ?>" data-tab="<?php echo esc_attr($id); ?>">
                            <?php echo esc_html($label); ?>
                        </button>
                    <?php endforeach; ?>
                </div>

                <?php foreach ($modules as $id => $label) : ?>
                    <div id="wc-cleaner-tab-<?php echo esc_attr($id); ?>" class="wc-cleaner-tab-content <?php echo ($id === 'products') ? 'active' : ''; ?>">
                        <div class="wc-cleaner-card">
                            <h2>Delete <?php echo esc_html($label); ?></h2>
                            <p>Loading counts...</p>

                            <div class="wccc-counts-wrapper" data-module="<?php echo esc_attr($id); ?>">
                                <p>Total found: <strong class="wccc-count-val">...</strong></p>
                            </div>

                            <div class="wc-cleaner-danger-zone">
                                <button type="button" class="button button-primary wc-cleaner-button-delete" data-module="<?php echo esc_attr($id); ?>">
                                    Start Cleanup
                                </button>
                                <button type="button" class="button button-secondary wc-cleaner-button-stop" data-module="<?php echo esc_attr($id); ?>" style="display:none;">
                                    Stop Cleanup
                                </button>
                                <div class="wccc-progress-bar" style="display:none;">
                                    <div class="wccc-progress-inner" style="width: 0%"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
    <?php
}

// Activation hook
register_activation_hook(__FILE__, 'wccc_activation_notice');
function wccc_activation_notice()
{
    set_transient('wccc_activation_notice', true, 5);
}

add_action('admin_notices', 'wccc_show_activation_notice');
function wccc_show_activation_notice()
{
    if (get_transient('wccc_activation_notice')) {
    ?>
        <div class="notice notice-success is-dismissible">
            <p><strong><?php echo esc_html__('Complete Product Cleaner for WooCommerce activated!', 'complete-product-cleaner-for-woocommerce'); ?></strong> <a href="<?php echo esc_url(admin_url('admin.php?page=complete-product-cleaner-for-woocommerce')); ?>"><?php echo esc_html__('Click here to start cleaning', 'complete-product-cleaner-for-woocommerce'); ?></a>.</p>
        </div>
<?php
        delete_transient('wccc_activation_notice');
    }
}
