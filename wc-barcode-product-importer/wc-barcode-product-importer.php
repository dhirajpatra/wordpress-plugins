<?php
/**
 * Plugin Name: WC Barcode Product Importer
 * Plugin URI: https://wordpress.org/plugins/wc-barcode-product-importer/
 * Description: WC Barcode Product Importer - Scan product barcodes to instantly import products into WooCommerce with full details and images.
 * Version: 1.0.0
 * Author: Dhiraj Patra
 * Author URI: https://github.com/DhirajPatra
 * Text Domain: wc-barcode-product-importer
 * License: GPLv2 or later
 * Requires Plugins: woocommerce
 */

if (!defined('ABSPATH')) {
    exit;
}

// Check if WooCommerce is active
if (!in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins')))) {
    add_action('admin_notices', function () {
        echo '<div class="error"><p><strong>WC Barcode Product Importer</strong> requires WooCommerce to be installed and active.</p></div>';
    });
    return;
}

class WC_Barcode_Product_Importer
{
    private $plugin_version = '1.0.0';
    private $session_key = 'wc_bpi_scan_session';

    public function __construct()
    {
        add_action('admin_menu', [$this, 'add_menu_page']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_scripts']);
        add_action('wp_ajax_wc_bpi_create_product', [$this, 'ajax_create_product']);
        add_action('wp_ajax_wc_bpi_fetch_product_data', [$this, 'ajax_fetch_product_data']);
        add_action('wp_ajax_wc_bpi_get_session_stats', [$this, 'ajax_get_session_stats']);
        add_action('wp_ajax_wc_bpi_clear_session', [$this, 'ajax_clear_session']);
        add_action('init', [$this, 'init_session']);
    }

    public function init_session()
    {
        if (!session_id()) {
            session_start();
        }
    }

    public function add_menu_page()
    {
        add_menu_page(
            __('WP Barcode Scanner', 'wc-barcode-product-importer'),
            __('WP Barcode Scanner', 'wc-barcode-product-importer'),
            'manage_woocommerce',
            'wc-bpi',
            [$this, 'render_page'],
            'dashicons-smartphone',
            58
        );

        add_submenu_page(
            'wc-bpi',
            __('Scan History', 'wc-barcode-product-importer'),
            __('Scan History', 'wc-barcode-product-importer'),
            'manage_woocommerce',
            'wc-bpi-history',
            [$this, 'render_history_page']
        );
    }

    public function enqueue_scripts($hook)
    {
        if ($hook !== 'toplevel_page_wc-bpi' && $hook !== 'barcode-scanner_page_wc-bpi-history') {
            return;
        }

        // Enqueue CSS
        wp_enqueue_style(
            'wc-bpi-styles',
            plugin_dir_url(__FILE__) . 'css/barcode_scanner.css',
            [],
            $this->plugin_version
        );

        // Enqueue HTML5 QRCode Library
        wp_enqueue_script(
            'html5-qrcode',
            plugin_dir_url(__FILE__) . 'js/html5-qrcode.min.js',
            [],
            '2.3.8',
            true
        );

        // Enqueue JavaScript
        wp_enqueue_script(
            'wc-bpi-barcode',
            plugin_dir_url(__FILE__) . 'js/barcode-scanner.js',
            ['jquery', 'html5-qrcode'],
            $this->plugin_version,
            true
        );

        wp_localize_script('wc-bpi-barcode', 'WCBPI', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('wc_bpi_nonce'),
            'strings' => [
                'cameraStarted' => __('📸 Camera ready - Scan products', 'wc-barcode-product-importer'),
                'detected' => __('✓ Barcode detected', 'wc-barcode-product-importer'),
                'fetching' => __('🔍 Fetching product info...', 'wc-barcode-product-importer'),
                'creating' => __('💾 Creating product...', 'wc-barcode-product-importer'),
                'success' => __('✓ Product imported successfully!', 'wc-barcode-product-importer'),
                'duplicate' => __('⚠ Product already exists', 'wc-barcode-product-importer'),
                'error' => __('✗ Error:', 'wc-barcode-product-importer'),
                'cameraError' => __('Camera error:', 'wc-barcode-product-importer'),
                'notSupported' => __('⚠ Barcode scanner not supported. Please use Chrome/Edge on Android or Safari on iOS.', 'wc-barcode-product-importer'),
                'readyForNext' => __('📦 Ready for next scan...', 'wc-barcode-product-importer'),
            ]
        ]);
    }

    public function render_page()
    {
        $stats = $this->get_session_stats();
        ?>
        <div class="wrap wc-bpi-wrap">
            <h1>
                <span class="dashicons dashicons-smartphone"></span>
                <?php echo esc_html__('WC Barcode Product Scanner', 'wc-barcode-product-importer'); ?>
            </h1>
            <p class="description">
                <?php echo esc_html__('Scan product barcodes to import them into your WooCommerce store', 'wc-barcode-product-importer'); ?>
            </p>

            <!-- Session Statistics -->
            <div class="wc-bpi-stats-bar">
                <div class="wc-bpi-stat">
                    <span
                        class="wc-bpi-stat-label"><?php echo esc_html__('Session Scans:', 'wc-barcode-product-importer'); ?></span>
                    <span class="wc-bpi-stat-value" id="wc-bpi-scan-count"><?php echo esc_html($stats['total_scans']); ?></span>
                </div>
                <div class="wc-bpi-stat success">
                    <span class="wc-bpi-stat-label"><?php echo esc_html__('Imported:', 'wc-barcode-product-importer'); ?></span>
                    <span class="wc-bpi-stat-value"
                        id="wc-bpi-success-count"><?php echo esc_html($stats['successful']); ?></span>
                </div>
                <div class="wc-bpi-stat warning">
                    <span
                        class="wc-bpi-stat-label"><?php echo esc_html__('Duplicates:', 'wc-barcode-product-importer'); ?></span>
                    <span class="wc-bpi-stat-value"
                        id="wc-bpi-duplicate-count"><?php echo esc_html($stats['duplicates']); ?></span>
                </div>
                <div class="wc-bpi-stat error">
                    <span class="wc-bpi-stat-label"><?php echo esc_html__('Errors:', 'wc-barcode-product-importer'); ?></span>
                    <span class="wc-bpi-stat-value" id="wc-bpi-error-count"><?php echo esc_html($stats['errors']); ?></span>
                </div>
                <button id="wc-bpi-clear-session" class="button button-secondary">
                    <span class="dashicons dashicons-trash"></span>
                    <?php echo esc_html__('Clear Session', 'wc-barcode-product-importer'); ?>
                </button>
            </div>

            <div class="wc-bpi-container">
                <div class="wc-bpi-scanner-section">
                    <div class="wc-bpi-scanner-wrapper">
                        <video id="wc-bpi-video" autoplay playsinline muted></video>
                        <div class="wc-bpi-scanner-overlay">
                            <div class="wc-bpi-scanner-box">
                                <div class="wc-bpi-scanner-corner tl"></div>
                                <div class="wc-bpi-scanner-corner tr"></div>
                                <div class="wc-bpi-scanner-corner bl"></div>
                                <div class="wc-bpi-scanner-corner br"></div>
                                <div class="wc-bpi-scanner-line"></div>
                            </div>
                        </div>
                    </div>

                    <div class="wc-bpi-controls">
                        <button id="wc-bpi-start" class="button button-primary button-hero">
                            <span class="dashicons dashicons-camera"></span>
                            <?php echo esc_html__('Start Scanning', 'wc-barcode-product-importer'); ?>
                        </button>
                        <button id="wc-bpi-stop" class="button button-secondary button-hero" style="display:none;">
                            <span class="dashicons dashicons-no"></span>
                            <?php echo esc_html__('Stop Scanner', 'wc-barcode-product-importer'); ?>
                        </button>
                    </div>

                    <div id="wc-bpi-status" class="wc-bpi-status"></div>
                </div>

                <!-- Recent Scans List -->
                <div class="wc-bpi-recent-scans">
                    <h3><?php echo esc_html__('Recent Scans', 'wc-barcode-product-importer'); ?></h3>
                    <div id="wc-bpi-scan-list" class="wc-bpi-scan-list">
                        <p class="wc-bpi-empty-state">
                            <?php echo esc_html__('No products scanned yet. Start scanning to see results here.', 'wc-barcode-product-importer'); ?>
                        </p>
                    </div>
                </div>
            </div>

            <div class="wc-bpi-info-box">
                <h3>📋 <?php echo esc_html__('Quick Guide', 'wc-barcode-product-importer'); ?></h3>
                <div class="wc-bpi-info-grid">
                    <div class="wc-bpi-info-item">
                        <span class="dashicons dashicons-smartphone"></span>
                        <strong><?php echo esc_html__('Use Mobile Device', 'wc-barcode-product-importer'); ?></strong>
                        <p><?php echo esc_html__('Best experience on phones/tablets with camera', 'wc-barcode-product-importer'); ?>
                        </p>
                    </div>
                    <div class="wc-bpi-info-item">
                        <span class="dashicons dashicons-camera"></span>
                        <strong><?php echo esc_html__('Point at Barcode', 'wc-barcode-product-importer'); ?></strong>
                        <p><?php echo esc_html__('Automatic detection - no button press needed', 'wc-barcode-product-importer'); ?>
                        </p>
                    </div>
                    <div class="wc-bpi-info-item">
                        <span class="dashicons dashicons-update"></span>
                        <strong><?php echo esc_html__('Continuous Scanning', 'wc-barcode-product-importer'); ?></strong>
                        <p><?php echo esc_html__('Scan multiple products without stopping', 'wc-barcode-product-importer'); ?>
                        </p>
                    </div>
                    <div class="wc-bpi-info-item">
                        <span class="dashicons dashicons-yes"></span>
                        <strong><?php echo esc_html__('Auto Import', 'wc-barcode-product-importer'); ?></strong>
                        <p><?php echo esc_html__('Products saved as drafts for review', 'wc-barcode-product-importer'); ?></p>
                    </div>
                </div>
                <p class="wc-bpi-note">
                    <strong><?php echo esc_html__('Supported Formats:', 'wc-barcode-product-importer'); ?></strong>
                    EAN-13, EAN-8, UPC-A, UPC-E, Code-128, Code-39, ITF, QR Code
                </p>
            </div>
        </div>
        <?php
    }

    public function render_history_page()
    {
        $scans = $this->get_all_scans_history();
        ?>
        <div class="wrap">
            <h1><?php echo esc_html__('Scan History', 'wc-barcode-product-importer'); ?></h1>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th><?php echo esc_html__('Barcode', 'wc-barcode-product-importer'); ?></th>
                        <th><?php echo esc_html__('Product Name', 'wc-barcode-product-importer'); ?></th>
                        <th><?php echo esc_html__('Status', 'wc-barcode-product-importer'); ?></th>
                        <th><?php echo esc_html__('Date', 'wc-barcode-product-importer'); ?></th>
                        <th><?php echo esc_html__('Action', 'wc-barcode-product-importer'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($scans)): ?>
                        <tr>
                            <td colspan="5"><?php echo esc_html__('No scan history available.', 'wc-barcode-product-importer'); ?>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($scans as $scan): ?>
                            <tr>
                                <td><?php echo esc_html($scan['barcode']); ?></td>
                                <td><?php echo esc_html($scan['product_name']); ?></td>
                                <td><?php echo esc_html($scan['status']); ?></td>
                                <td><?php echo esc_html($scan['date']); ?></td>
                                <td>
                                    <?php if ($scan['product_id']): ?>
                                        <a href="<?php echo esc_url(admin_url('post.php?post=' . $scan['product_id'] . '&action=edit')); ?>"
                                            class="button button-small">
                                            <?php echo esc_html__('Edit', 'wc-barcode-product-importer'); ?>
                                        </a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <?php
    }

    public function ajax_get_session_stats()
    {
        check_ajax_referer('wc_bpi_nonce', 'nonce');
        wp_send_json_success($this->get_session_stats());
    }

    public function ajax_clear_session()
    {
        check_ajax_referer('wc_bpi_nonce', 'nonce');

        if (!current_user_can('manage_woocommerce')) {
            wp_send_json_error(['message' => __('Permission denied', 'wc-barcode-product-importer')], 403);
        }

        unset($_SESSION[$this->session_key]);
        wp_send_json_success(['message' => __('Session cleared', 'wc-barcode-product-importer')]);
    }

    private function get_session_stats()
    {
        if (!isset($_SESSION[$this->session_key])) {
            return [
                'total_scans' => 0,
                'successful' => 0,
                'duplicates' => 0,
                'errors' => 0
            ];
        }
        return $_SESSION[$this->session_key];
    }

    private function update_session_stats($type)
    {
        if (!isset($_SESSION[$this->session_key])) {
            $_SESSION[$this->session_key] = [
                'total_scans' => 0,
                'successful' => 0,
                'duplicates' => 0,
                'errors' => 0
            ];
        }

        $_SESSION[$this->session_key]['total_scans']++;
        $_SESSION[$this->session_key][$type]++;
    }

    private function get_all_scans_history()
    {
        global $wpdb;

        $results = $wpdb->get_results("
            SELECT p.ID as product_id, p.post_title as product_name, pm.meta_value as barcode, p.post_date as date, p.post_status as status
            FROM {$wpdb->posts} p
            LEFT JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id AND pm.meta_key = '_sku'
            WHERE p.post_type = 'product'
            AND pm.meta_value IS NOT NULL
            ORDER BY p.post_date DESC
            LIMIT 100
        ", ARRAY_A);

        return $results ?: [];
    }

    public function ajax_fetch_product_data()
    {
        check_ajax_referer('wc_bpi_nonce', 'nonce');

        if (!current_user_can('manage_woocommerce')) {
            wp_send_json_error(['message' => __('Permission denied', 'wc-barcode-product-importer')], 403);
        }

        $barcode = isset($_POST['barcode']) ? sanitize_text_field($_POST['barcode']) : '';

        if (empty($barcode)) {
            wp_send_json_error(['message' => __('Barcode is required', 'wc-barcode-product-importer')], 400);
        }

        // Check if product already exists
        $existing_id = wc_get_product_id_by_sku($barcode);
        if ($existing_id) {
            wp_send_json_error([
                'message' => __('Product already exists in your store', 'wc-barcode-product-importer'),
                'product_id' => $existing_id,
                'is_duplicate' => true
            ], 409);
        }

        // Fetch product data from external API
        $product_data = $this->fetch_product_from_api($barcode);

        if (is_wp_error($product_data)) {
            wp_send_json_error(['message' => $product_data->get_error_message()], 500);
        }

        wp_send_json_success($product_data);
    }

    public function ajax_create_product()
    {
        check_ajax_referer('wc_bpi_nonce', 'nonce');

        if (!current_user_can('manage_woocommerce')) {
            wp_send_json_error(['message' => __('Permission denied', 'wc-barcode-product-importer')], 403);
        }

        $product_json = isset($_POST['product']) ? wp_unslash($_POST['product']) : '';

        if (empty($product_json)) {
            wp_send_json_error(['message' => __('Product data is required', 'wc-barcode-product-importer')], 400);
        }

        $product_data = json_decode($product_json, true);

        if (!is_array($product_data) || empty($product_data['name'])) {
            wp_send_json_error(['message' => __('Invalid product data', 'wc-barcode-product-importer')], 400);
        }

        // Double check for duplicates
        if (!empty($product_data['sku'])) {
            $existing_id = wc_get_product_id_by_sku($product_data['sku']);
            if ($existing_id) {
                $this->update_session_stats('duplicates');
                wp_send_json_error([
                    'message' => __('Product already exists', 'wc-barcode-product-importer'),
                    'product_id' => $existing_id,
                    'is_duplicate' => true
                ], 409);
            }
        }

        $product_id = $this->create_wc_product_from_data($product_data);

        if (is_wp_error($product_id)) {
            $this->update_session_stats('errors');
            wp_send_json_error(['message' => $product_id->get_error_message()], 500);
        }

        $this->update_session_stats('successful');

        wp_send_json_success([
            'product_id' => $product_id,
            'edit_url' => admin_url('post.php?post=' . $product_id . '&action=edit'),
            'view_url' => get_permalink($product_id),
            'stats' => $this->get_session_stats()
        ]);
    }

    protected function fetch_product_from_api($barcode)
    {
        // Try Open Food Facts API
        $response = wp_remote_get('https://world.openfoodfacts.org/api/v0/product/' . $barcode . '.json', [
            'timeout' => 15,
            'headers' => ['User-Agent' => 'WC-Barcode-Importer/1.0']
        ]);

        if (!is_wp_error($response)) {
            $body = wp_remote_retrieve_body($response);
            $data = json_decode($body, true);

            if (!empty($data['status']) && $data['status'] == 1 && !empty($data['product'])) {
                $product = $data['product'];

                return [
                    'name' => !empty($product['product_name']) ? $product['product_name'] : 'Product ' . $barcode,
                    'sku' => $barcode,
                    'regular_price' => '0.00',
                    'description' => $this->build_description($product),
                    'short_description' => !empty($product['generic_name']) ? $product['generic_name'] : '',
                    'image_url' => !empty($product['image_url']) ? $product['image_url'] : '',
                    'categories' => !empty($product['categories']) ? explode(',', $product['categories']) : [],
                    'brand' => !empty($product['brands']) ? $product['brands'] : '',
                    'weight' => !empty($product['quantity']) ? $product['quantity'] : '',
                    'api_source' => 'Open Food Facts'
                ];
            }
        }

        // Fallback: Create basic product entry
        return [
            'name' => 'Product ' . $barcode,
            'sku' => $barcode,
            'regular_price' => '0.00',
            'description' => sprintf(_('Product imported from barcode scan on %s. Barcode: %s. Please update product details.', 'wc-barcode-product-importer'), date('Y-m-d H:i:s'), $barcode),
            'short_description' => _('Product - details pending', 'wc-barcode-product-importer'),
            'image_url' => '',
            'api_source' => 'Manual Entry Required'
        ];
    }

    private function build_description($product)
    {
        $parts = [];

        if (!empty($product['ingredients_text'])) {
            $parts[] = '<strong>Ingredients:</strong> ' . $product['ingredients_text'];
        }

        if (!empty($product['allergens'])) {
            $parts[] = '<strong>Allergens:</strong> ' . $product['allergens'];
        }

        if (!empty($product['nutrition_data_per'])) {
            $parts[] = '<strong>Nutrition per:</strong> ' . $product['nutrition_data_per'];
        }

        return !empty($parts) ? implode('<br><br>', $parts) : 'Product details pending';
    }

    protected function create_wc_product_from_data($data)
    {
        if (!class_exists('WC_Product_Simple')) {
            return new WP_Error('no_wc', __('WooCommerce is not loaded', 'wc-barcode-product-importer'));
        }

        try {
            $product = new WC_Product_Simple();

            // Set basic product data
            $product->set_name(sanitize_text_field($data['name'] ?? __('Imported Product', 'wc-barcode-product-importer')));
            $product->set_regular_price(sanitize_text_field($data['regular_price'] ?? '0'));
            $product->set_sku(sanitize_text_field($data['sku'] ?? ''));
            $product->set_description(wp_kses_post($data['description'] ?? ''));
            $product->set_short_description(wp_kses_post($data['short_description'] ?? ''));
            $product->set_status('draft'); // Draft for barcode scanner staff to review and add pricing
            $product->set_catalog_visibility('visible');
            $product->set_manage_stock(true);
            $product->set_stock_quantity(0);
            $product->set_stock_status('outofstock');

            // Save product
            $product_id = $product->save();

            // Download and attach image
            if (!empty($data['image_url']) && filter_var($data['image_url'], FILTER_VALIDATE_URL)) {
                require_once(ABSPATH . 'wp-admin/includes/media.php');
                require_once(ABSPATH . 'wp-admin/includes/file.php');
                require_once(ABSPATH . 'wp-admin/includes/image.php');

                $attach_id = media_sideload_image($data['image_url'], $product_id, $data['name'], 'id');

                if (!is_wp_error($attach_id)) {
                    set_post_thumbnail($product_id, $attach_id);
                }
            }

            // Add custom meta
            if (!empty($data['brand'])) {
                update_post_meta($product_id, '_brand', sanitize_text_field($data['brand']));
            }

            if (!empty($data['weight'])) {
                update_post_meta($product_id, '_weight', sanitize_text_field($data['weight']));
            }

            if (!empty($data['api_source'])) {
                update_post_meta($product_id, '_barcode_import_source', sanitize_text_field($data['api_source']));
            }

            update_post_meta($product_id, '_barcode_import_date', current_time('mysql'));
            update_post_meta($product_id, '_barcode_import_user', get_current_user_id());

            return $product_id;

        } catch (Exception $e) {
            return new WP_Error('product_creation_failed', $e->getMessage());
        }
    }
}

// Initialize plugin
new WC_Barcode_Product_Importer();
