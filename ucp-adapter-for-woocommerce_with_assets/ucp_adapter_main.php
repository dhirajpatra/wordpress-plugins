<?php

/**
 * Plugin Name: UCP Adapter For WooCommerce
 * Plugin URI: https://wordpress.org/plugins/ucp-adapter-for-woocommerce
 * Description: Universal Commerce Platform REST API adapter providing Session, Update, and Complete endpoints for e-commerce integration
 * Version: 1.0.1
 * Requires at least: 6.9
 * Requires PHP: 8.0
 * Author: Dhiraj Patra
 * Author URI: https://github.com/dhirajpatra
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: ucp-adapter-for-woocommerce
 * Domain Path: /languages
 *
 * @package UCP_Adapter
 */

// Exit if accessed directly.
if (! defined('ABSPATH')) {
	exit;
}

// Define plugin constants.
define('UCP_ADAPTER_VERSION', '1.0.1');
define('UCP_ADAPTER_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('UCP_ADAPTER_PLUGIN_URL', plugin_dir_url(__FILE__));
define('UCP_ADAPTER_PLUGIN_BASENAME', plugin_basename(__FILE__));

/**
 * Main UCP Adapter Class
 */
class UCP_Adapter_Core
{

	/**
	 * Single instance of the class
	 *
	 * @var UCP_Adapter_Core
	 */
	private static $instance = null;

	/**
	 * Get instance
	 *
	 * @return UCP_Adapter_Core
	 */
	public static function get_instance()
	{
		if (null === self::$instance) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor
	 */
	private function __construct()
	{
		$this->includes();
		$this->init_hooks();
	}

	/**
	 * Include required files
	 */
	private function includes()
	{
		require_once UCP_ADAPTER_PLUGIN_DIR . 'includes/class-ucp-rest-api.php';
		require_once UCP_ADAPTER_PLUGIN_DIR . 'includes/class-ucp-session-handler.php';
		require_once UCP_ADAPTER_PLUGIN_DIR . 'includes/class-ucp-security.php';
		require_once UCP_ADAPTER_PLUGIN_DIR . 'includes/class-ucp-admin.php';
	}

	/**
	 * Initialize hooks
	 */
	private function init_hooks()
	{
		add_action('init', array($this, 'init'));
		add_action('rest_api_init', array('UCP_Adapter_REST_API', 'register_routes'));
		add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));

		// Admin hooks.
		if (is_admin()) {
			UCP_Adapter_Admin::get_instance();
		}

		// Activation and deactivation hooks.
		register_activation_hook(__FILE__, array($this, 'activate'));
		register_deactivation_hook(__FILE__, array($this, 'deactivate'));
	}



	/**
	 * Initialize plugin
	 */
	public function init()
	{
		// Initialize session handler.
		UCP_Adapter_Session_Handler::get_instance();

		// Apply filters for extensibility.
		do_action('ucp_adapter_init');
	}

	/**
	 * Enqueue scripts and styles
	 */
	public function enqueue_scripts()
	{
		if (! is_admin()) {
			wp_enqueue_style(
				'ucp-adapter-frontend',
				UCP_ADAPTER_PLUGIN_URL . 'assets/css/frontend.css',
				array(),
				UCP_ADAPTER_VERSION
			);

			wp_enqueue_script(
				'ucp-adapter-frontend',
				UCP_ADAPTER_PLUGIN_URL . 'assets/js/frontend.js',
				array('jquery'),
				UCP_ADAPTER_VERSION,
				true
			);

			wp_localize_script(
				'ucp-adapter-frontend',
				'ucpAdapterData',
				array(
					'ajaxUrl'   => admin_url('admin-ajax.php'),
					'restUrl'   => rest_url('ucp/v1'),
					'nonce'     => wp_create_nonce('wp_rest'),
					'version'   => UCP_ADAPTER_VERSION,
				)
			);
		}
	}

	/**
	 * Plugin activation
	 */
	public function activate()
	{
		// Create custom database table for sessions if needed.
		global $wpdb;
		$table_name      = $wpdb->prefix . 'ucp_adapter_sessions';
		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE IF NOT EXISTS $table_name (
			id bigint(20) NOT NULL AUTO_INCREMENT,
			session_id varchar(255) NOT NULL,
			session_data longtext NOT NULL,
			expires bigint(20) NOT NULL,
			created_at datetime DEFAULT CURRENT_TIMESTAMP,
			updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			UNIQUE KEY session_id (session_id),
			KEY expires (expires)
		) $charset_collate;";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta($sql);

		// Set default options.
		add_option('ucp_adapter_version', UCP_ADAPTER_VERSION);
		add_option('ucp_adapter_api_key', wp_generate_password(32, false));
		add_option('ucp_adapter_session_timeout', 3600);

		// Flush rewrite rules.
		flush_rewrite_rules();
	}

	/**
	 * Plugin deactivation
	 */
	public function deactivate()
	{
		// Clean up expired sessions.
		UCP_Adapter_Session_Handler::cleanup_expired_sessions();

		// Flush rewrite rules.
		flush_rewrite_rules();
	}
}

/**
 * Initialize the plugin
 */
function ucp_adapter_plugin()
{
	return UCP_Adapter_Core::get_instance();
}

// Start the plugin.
ucp_adapter_plugin();
