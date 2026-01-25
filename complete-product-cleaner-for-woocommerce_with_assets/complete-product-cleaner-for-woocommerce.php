<?php

/**
 * Plugin Name: Complete Product Cleaner for WooCommerce
 * Plugin URI: https://wordpress.org/plugins/complete-product-cleaner-for-woocommerce
 * Description: Delete all WooCommerce products with options to remove related images and orphaned attachments
 * Version: 1.0.5
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

// Check if WooCommerce is active
add_action('admin_init', 'wccc_check_woocommerce');
function wccc_check_woocommerce()
{
    if (!class_exists('WooCommerce')) {
        add_action('admin_notices', function () {
?>
            <div class="notice notice-error is-dismissible">
                <p><strong>Complete Product Cleaner for WooCommerce:</strong> WooCommerce is not active. Please install and activate WooCommerce first.</p>
            </div>
    <?php
        });
    }
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

    wp_enqueue_style('wccc-admin-css', plugin_dir_url(__FILE__) . 'css/complete-product-cleaner-for-woocommerce.css', array(), '1.0.3');
    wp_enqueue_script('wccc-admin-js', plugin_dir_url(__FILE__) . 'js/complete-product-cleaner-for-woocommerce.js', array('jquery'), '1.0.3', true);

    wp_localize_script('wccc-admin-js', 'wcCleanerData', array(
        'ajaxUrl' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('wccc_cleanup_nonce'),
        'placeholder' => function_exists('wc_placeholder_img_src') ? wc_placeholder_img_src() : ''
    ));
}

// AJAX: Get product count
add_action('wp_ajax_wccc_get_product_count', 'wccc_ajax_get_product_count');
function wccc_ajax_get_product_count()
{
    check_ajax_referer('wccc_cleanup_nonce', 'nonce');
    if (!current_user_can('manage_options')) {
        wp_send_json_error(array('message' => 'Permission denied'));
    }

    $count = wccc_count_products();
    wp_send_json_success(array('count' => $count));
}

// AJAX: Scan images
add_action('wp_ajax_wccc_scan_images', 'wccc_ajax_scan_images');
function wccc_ajax_scan_images()
{
    check_ajax_referer('wccc_cleanup_nonce', 'nonce');
    if (!current_user_can('manage_options')) {
        wp_send_json_error(array('message' => 'Permission denied'));
    }

    $results = wccc_scan_orphaned_images_logic();

    // Format for JS
    $formatted_images = array();
    foreach ($results['orphaned_images'] as $image) {
        $img_url = wp_get_attachment_image_src($image->ID, 'thumbnail');
        $formatted_images[] = array(
            'id' => $image->ID,
            'title' => $image->post_title,
            'size' => size_format(filesize(get_attached_file($image->ID))),
            'thumbnail' => $img_url ? $img_url[0] : ''
        );
    }

    wp_send_json_success(array(
        'count' => $results['orphaned_count'],
        'size' => size_format($results['total_size']),
        'images' => $formatted_images
    ));
}

// AJAX: Delete orphan images
add_action('wp_ajax_wccc_delete_images', 'wccc_ajax_delete_images');
function wccc_ajax_delete_images()
{
    check_ajax_referer('wccc_cleanup_nonce', 'nonce');
    if (!current_user_can('manage_options')) {
        wp_send_json_error(array('message' => 'Permission denied'));
    }

    $results = wccc_delete_orphaned_images_logic();

    wp_send_json_success(array(
        'deleted_count' => $results['deleted_count'],
        'freed_space' => size_format($results['freed_space'])
    ));
}

// AJAX: Delete products
add_action('wp_ajax_wccc_delete_products_ajax', 'wccc_ajax_delete_products');
function wccc_ajax_delete_products()
{
    check_ajax_referer('wccc_cleanup_nonce', 'nonce');
    if (!current_user_can('manage_options')) {
        wp_send_json_error(array('message' => 'Permission denied'));
    }

    // Check if checkboxes are present in POST data
    $delete_attached = isset($_POST['delete_attached_images']);
    $delete_orphaned = isset($_POST['delete_orphaned_images']);

    $results = wccc_delete_all_products_logic($delete_attached, $delete_orphaned);

    wp_send_json_success($results);
}


// Admin page content
function wccc_admin_page()
{
    if (!current_user_can('manage_options')) {
        wp_die(esc_html__('You do not have sufficient permissions to access this page.', 'complete-product-cleaner-for-woocommerce'));
    }
    ?>
    <div class="wrap">
        <div class="wc-cleaner-wrapper">
            <div class="wc-cleaner-header">
                <h1>Complete Product Cleaner for WooCommerce</h1>
                <p>Delete all products, variations, and clean up media library.</p>
            </div>

            <div class="wc-cleaner-tabs">
                <div class="nav-tab-wrapper">
                    <button class="nav-tab wc-cleaner-tab nav-tab-active" data-tab="products">
                        🗑️ Delete Products
                    </button>
                    <button class="nav-tab wc-cleaner-tab" data-tab="images">
                        🖼️ Clean Images
                    </button>
                </div>

                <!-- Tab: Products -->
                <div id="wc-cleaner-tab-products" class="wc-cleaner-tab-content active">
                    <form id="wc-cleaner-delete-form">
                        <div class="wc-cleaner-card">
                            <h2>Delete All Products</h2>
                            <p>Select what else you want to delete along with products.</p>

                            <div class="wc-cleaner-options">
                                <div class="wc-cleaner-option">
                                    <input type="checkbox" name="delete_attached_images" id="delete-attached-images">
                                    <div>
                                        <label for="delete-attached-images">Delete Attached Images</label>
                                        <span class="description">Deletes images that are attached to the products being deleted.</span>
                                    </div>
                                </div>
                                <div class="wc-cleaner-option">
                                    <input type="checkbox" name="delete_orphaned_images" id="delete-orphaned-images">
                                    <div>
                                        <label for="delete-orphaned-images">Delete Orphaned Images</label>
                                        <span class="description">Scan and delete images that are not used by any other content.</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="wc-cleaner-danger-zone">
                            <h3>⚠️ Danger Zone</h3>
                            <p id="wc-cleaner-delete-summary">This will delete <strong id="wc-cleaner-product-count">...</strong> products and variations.</p>
                            <button type="button" class="button button-primary wc-cleaner-button-delete">
                                🗑️ DELETE ALL PRODUCTS NOW
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Tab: Images -->
                <div id="wc-cleaner-tab-images" class="wc-cleaner-tab-content">
                    <div class="wc-cleaner-card">
                        <h2>Clean Orphaned Images</h2>
                        <p>Find and delete images that are not attached to any post, product, or page.</p>

                        <div style="margin-bottom: 20px;">
                            <button type="button" id="wc-cleaner-scan-images" class="button button-secondary button-large">
                                🔍 Scan for Orphaned Images
                            </button>
                        </div>

                        <div id="wc-cleaner-scan-results">
                            <!-- Results will appear here -->
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php
}

// Helper: Count total products
function wccc_count_products()
{
    $count = 0;
    $post_types = array('product', 'product_variation');

    foreach ($post_types as $type) {
        $counts = wp_count_posts($type);
        foreach ($counts as $status => $num) {
            if ($status !== 'trash') {
                $count += $num;
            }
        }
    }
    return $count;
}

// Logic: Scan orphaned images
function wccc_scan_orphaned_images_logic()
{
    global $wpdb;

    // 1. Get all images currently in use (Safelist)
    $safe_ids = array();

    // A. Site Logo and Icon
    $main_logo_id = get_theme_mod('logo');
    if ($main_logo_id) {
        if (is_numeric($main_logo_id)) {
            $safe_ids[] = $main_logo_id;
        } else {
            $id_from_url = attachment_url_to_postid($main_logo_id);
            if ($id_from_url) $safe_ids[] = $id_from_url;
        }
    }

    $custom_logo_id = get_theme_mod('custom_logo');
    if ($custom_logo_id) {
        if (is_numeric($custom_logo_id)) {
            $safe_ids[] = $custom_logo_id;
        } else {
            $id_from_url = attachment_url_to_postid($custom_logo_id);
            if ($id_from_url) $safe_ids[] = $id_from_url;
        }
    }

    $logo_id = get_option('site_logo');
    if ($logo_id) {
        if (is_numeric($logo_id)) {
            $safe_ids[] = $logo_id;
        } else {
            $id_from_url = attachment_url_to_postid($logo_id);
            if ($id_from_url) $safe_ids[] = $id_from_url;
        }
    }

    $icon_id = get_option('site_icon');
    if ($icon_id) {
        if (is_numeric($icon_id)) {
            $safe_ids[] = $icon_id;
        } else {
            $id_from_url = attachment_url_to_postid($icon_id);
            if ($id_from_url) $safe_ids[] = $id_from_url;
        }
    }

    // A2. Header and Background Images
    $header_image_data = get_theme_mod('header_image_data');
    if (is_object($header_image_data) && isset($header_image_data->attachment_id)) {
        $safe_ids[] = $header_image_data->attachment_id;
    }

    $background_image = get_theme_mod('background_image');
    if ($background_image) {
        $bg_id = attachment_url_to_postid($background_image);
        if ($bg_id) $safe_ids[] = $bg_id;
    }

    // A3. WooCommerce Specific Settings
    $wc_placeholder_id = get_option('woocommerce_placeholder_image');
    if ($wc_placeholder_id) $safe_ids[] = $wc_placeholder_id;

    $wc_email_header = get_option('woocommerce_email_header_image');
    if ($wc_email_header) {
        $header_id = attachment_url_to_postid($wc_email_header);
        if ($header_id) $safe_ids[] = $header_id;
    }

    // B. Featured Images (used by ANY post type)
    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
    $featured_images = $wpdb->get_col("
        SELECT meta_value FROM {$wpdb->postmeta} 
        WHERE meta_key = '_thumbnail_id'
    ");
    if (!empty($featured_images)) {
        $safe_ids = array_merge($safe_ids, $featured_images);
    }

    $safe_ids = array_map('intval', array_unique($safe_ids));

    // 2. Scan ALL images in Media Library
    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
    $all_images = $wpdb->get_results("
        SELECT p.ID, p.post_parent, p.post_title, p.guid
        FROM {$wpdb->posts} p
        WHERE p.post_type = 'attachment'
        AND p.post_mime_type LIKE 'image/%'
    ");

    $orphaned = array();
    $total_size = 0;

    foreach ($all_images as $image) {
        $image_id = intval($image->ID);

        // CHECK 1: Is it in our Safelist?
        if (in_array($image_id, $safe_ids)) {
            continue;
        }

        // CHECK 2: Is it attached to a valid parent?
        if ($image->post_parent > 0) {
            $parent = get_post($image->post_parent);
            if ($parent && $parent->post_status !== 'trash') {
                continue;
            }
        }

        // CHECK 3: Is the image filename used in any post content?
        $filename = basename($image->guid);
        if ($filename) {
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
            $in_content = $wpdb->get_var($wpdb->prepare(
                "SELECT ID FROM {$wpdb->posts} 
                 WHERE post_content LIKE %s 
                 AND post_status NOT IN ('trash', 'auto-draft', 'inherit') 
                 LIMIT 1",
                '%' . $wpdb->esc_like($filename) . '%'
            ));

            if ($in_content) {
                continue;
            }
        }

        // It is an orphan
        $orphaned[] = $image;
        $file_path = get_attached_file($image->ID);
        if ($file_path && file_exists($file_path)) {
            $total_size += filesize($file_path);
        }
    }

    return array(
        'orphaned_count' => count($orphaned),
        'total_size' => $total_size,
        'orphaned_images' => $orphaned
    );
}

// Logic: Delete orphaned images
function wccc_delete_orphaned_images_logic()
{
    $scan_results = wccc_scan_orphaned_images_logic();
    $deleted_count = 0;
    $freed_space = 0;

    foreach ($scan_results['orphaned_images'] as $image) {
        $file_path = get_attached_file($image->ID);
        $file_size = ($file_path && file_exists($file_path)) ? filesize($file_path) : 0;

        if (wp_delete_attachment($image->ID, true)) {
            $deleted_count++;
            $freed_space += $file_size;
        }
    }

    return array(
        'deleted_count' => $deleted_count,
        'freed_space' => $freed_space
    );
}

// Logic: Delete all products
function wccc_delete_all_products_logic($delete_attached_images = false, $delete_orphaned_images = false)
{
    global $wpdb;

    if (function_exists('wp_raise_memory_limit')) {
        wp_raise_memory_limit('admin');
    }

    $results = array(
        'products_deleted' => 0,
        'variations_deleted' => 0,
        'attached_images_deleted' => 0,
        'orphaned_images_deleted' => 0,
        'orders_cleaned' => 0,
        'errors' => array()
    );

    try {
        wp_defer_term_counting(true);
        wp_suspend_cache_invalidation(true);

        // Get all products and variations (including trash)
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
        $product_ids = $wpdb->get_col("
            SELECT ID FROM {$wpdb->posts} 
            WHERE post_type IN ('product', 'product_variation')
        ");

        if (empty($product_ids)) {
            wp_suspend_cache_invalidation(false);
            wp_defer_term_counting(false);
            return $results;
        }

        // Collect all potential images to delete first if option is enabled
        $images_to_process = array();

        if ($delete_attached_images) {
            foreach ($product_ids as $p_id) {
                // 1. Attached via post_parent
                // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
                $children = $wpdb->get_col($wpdb->prepare(
                    "SELECT ID FROM {$wpdb->posts} WHERE post_type = 'attachment' AND post_parent = %d",
                    $p_id
                ));
                foreach ($children as $c_id) {
                    $images_to_process[] = intval($c_id);
                }

                // 2. Featured Image (_thumbnail_id)
                $thumb_id = get_post_meta($p_id, '_thumbnail_id', true);
                if ($thumb_id) {
                    $images_to_process[] = intval($thumb_id);
                }

                // 3. Gallery Images (_product_image_gallery)
                $gallery = get_post_meta($p_id, '_product_image_gallery', true);
                if ($gallery) {
                    $gallery_ids = array_map('intval', explode(',', $gallery));
                    foreach ($gallery_ids as $g_id) {
                        if ($g_id > 0) {
                            $images_to_process[] = $g_id;
                        }
                    }
                }
            }
            $images_to_process = array_unique(array_filter($images_to_process));
        }

        // Delete Products and Variations
        foreach ($product_ids as $product_id) {
            $post_type = get_post_type($product_id);

            // Force delete (bypass trash)
            if (wp_delete_post($product_id, true)) {
                if ($post_type === 'product_variation') {
                    $results['variations_deleted']++;
                } else {
                    $results['products_deleted']++;
                }
            } else {
                $results['errors'][] = "Failed to delete product ID: {$product_id}";
            }
        }

        // Processing Images Deletion
        if ($delete_attached_images && !empty($images_to_process)) {
            foreach ($images_to_process as $img_id) {
                if ($img_id <= 0) continue;

                // SAFETY CHECK 1: Is this image attached to a NON-PRODUCT parent?
                // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
                $parent_check = $wpdb->get_var($wpdb->prepare(
                    "SELECT ID FROM {$wpdb->posts} 
                     WHERE ID = (SELECT post_parent FROM {$wpdb->posts} WHERE ID = %d)
                     AND post_type NOT IN ('product', 'product_variation', 'attachment')",
                    $img_id
                ));

                if ($parent_check) {
                    continue;
                }

                // SAFETY CHECK 2: Is this image used as _thumbnail_id for a NON-PRODUCT post?
                // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
                $thumb_check = $wpdb->get_var($wpdb->prepare(
                    "SELECT post_id FROM {$wpdb->postmeta} 
                     WHERE meta_key = '_thumbnail_id' 
                     AND meta_value = %d
                     AND post_id IN (
                        SELECT ID FROM {$wpdb->posts} 
                        WHERE post_type NOT IN ('product', 'product_variation', 'attachment', 'revision')
                     ) LIMIT 1",
                    $img_id
                ));

                if ($thumb_check) {
                    continue;
                }

                // Safe to delete
                if (wp_delete_attachment($img_id, true)) {
                    $results['attached_images_deleted']++;
                }
            }
        }

        // Cleanup orphaned postmeta
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
        $wpdb->query(
            "DELETE pm FROM {$wpdb->postmeta} pm 
             LEFT JOIN {$wpdb->posts} p ON pm.post_id = p.ID 
             WHERE p.ID IS NULL"
        );

        // Cleanup orphaned term relationships
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
        $wpdb->query(
            "DELETE tr FROM {$wpdb->term_relationships} tr 
             LEFT JOIN {$wpdb->posts} p ON tr.object_id = p.ID 
             WHERE p.ID IS NULL"
        );

        // Cleanup WooCommerce specific tables
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
        if ($wpdb->get_var("SHOW TABLES LIKE '{$wpdb->prefix}wc_product_meta_lookup'") === $wpdb->prefix . 'wc_product_meta_lookup') {
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange
            $wpdb->query("TRUNCATE TABLE {$wpdb->prefix}wc_product_meta_lookup");
        }

        // Clean up order items (remove product references from orders, but keep orders intact)
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
        $order_items_cleaned = $wpdb->query(
            "UPDATE {$wpdb->prefix}woocommerce_order_itemmeta 
             SET meta_value = '0' 
             WHERE meta_key IN ('_product_id', '_variation_id') 
             AND meta_value != '0'"
        );
        $results['orders_cleaned'] = $order_items_cleaned;

        // Delete orphaned images if requested
        if ($delete_orphaned_images) {
            $orphaned_results = wccc_delete_orphaned_images_logic();
            $results['orphaned_images_deleted'] = $orphaned_results['deleted_count'];
        }

        // Re-enable caching
        wp_suspend_cache_invalidation(false);
        wp_defer_term_counting(false);

        // Clear WooCommerce transients
        if (function_exists('wc_delete_product_transients')) {
            wc_delete_product_transients();
        }

        // Clear all caches
        wp_cache_flush();

        return $results;
    } catch (Exception $e) {
        wp_suspend_cache_invalidation(false);
        wp_defer_term_counting(false);
        $results['errors'][] = "Exception: " . $e->getMessage();
        return $results;
    }
}

// Add settings link
add_filter('plugin_action_links_' . plugin_basename(__FILE__), 'wccc_plugin_action_links');
function wccc_plugin_action_links($links)
{
    $settings_link = '<a href="' . admin_url('admin.php?page=complete-product-cleaner-for-woocommerce') . '">Open Cleaner</a>';
    array_unshift($links, $settings_link);
    return $links;
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
