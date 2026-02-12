<?php

/**
 * Plugin Name: Single Click Posting Products to Social Networks
 * Description: Post WooCommerce products to Facebook, Pinterest, X, LinkedIn, YouTube, and Instagram with a single click.
 * Version: 1.0.0
 * Author: Dhiraj Patra
 * Author URI: https://github.com/dhirajpatra
 * Text Domain: single-click-posting-products-to-social-networks
 * Requires at least: 6.0
 * Tested up to: 6.9
 * Requires PHP: 8.0
 * License: GPL v3 or later
 * License URI: http://www.gnu.org/licenses/gpl-3.0.html
 */

if (! defined('ABSPATH')) {
	exit;
}

class SCPPSN_Plugin
{
	private static $instance = null;
	private $settings_page_hook = '';

	public static function get_instance()
	{
		if (null === self::$instance) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct()
	{
		add_action('plugins_loaded', array($this, 'init'));
		add_filter('plugin_action_links_' . plugin_basename(__FILE__), array($this, 'add_settings_link'));
		register_activation_hook(__FILE__, array($this, 'activate'));
	}

	public function init()
	{
		if (! class_exists('WooCommerce')) {
			add_action('admin_notices', array($this, 'woocommerce_notice'));
			return;
		}

		add_action('admin_menu', array($this, 'add_menu'));
		add_action('admin_init', array($this, 'register_settings'));
		add_action('add_meta_boxes', array($this, 'add_meta_box'));
		add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));
		add_action('wp_ajax_scppsn_post', array($this, 'ajax_post'));
	}

	public function woocommerce_notice()
	{
		echo '<div class="notice notice-error"><p><strong>Single Click Posting Products to Social Networks</strong> requires WooCommerce to be installed and activated.</p></div>';
	}

	public function add_settings_link($links)
	{
		$settings_link = '<a href="admin.php?page=scppsn-settings">Settings</a>';
		array_unshift($links, $settings_link);
		return $links;
	}

	public function activate()
	{
		global $wpdb;
		$table_name = $wpdb->prefix . 'scppsn_logs';
		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE IF NOT EXISTS {$table_name} (
			id bigint(20) NOT NULL AUTO_INCREMENT,
			product_id bigint(20) NOT NULL,
			network varchar(50) NOT NULL,
			status varchar(20) NOT NULL,
			message text,
			created_at datetime DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY (id)
		) {$charset_collate};";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta($sql);
	}

	public function add_menu()
	{
		$this->settings_page_hook = add_menu_page(
			'Social Networks',
			'Social Networks',
			'manage_woocommerce',
			'scppsn-settings',
			array($this, 'render_settings'),
			'dashicons-share',
			56
		);
	}

	public function register_settings()
	{
		register_setting('scppsn_settings', 'scppsn_settings', array($this, 'sanitize_settings'));
	}

	public function sanitize_settings($input)
	{
		$sanitized = array();
		$networks = array('facebook', 'pinterest', 'x', 'linkedin', 'youtube', 'instagram');

		foreach ($networks as $network) {
			if (isset($input[$network])) {
				foreach ($input[$network] as $key => $value) {
					$sanitized[$network][$key] = sanitize_text_field($value);
				}
			}
		}

		return $sanitized;
	}

	public function render_settings()
	{
		$settings = get_option('scppsn_settings', array());
?>
		<div class="wrap">
			<h1>Social Networks Settings</h1>
			<form method="post" action="options.php">
				<?php settings_fields('scppsn_settings'); ?>

				<h2 class="nav-tab-wrapper">
					<a href="#facebook" class="nav-tab nav-tab-active">Facebook</a>
					<a href="#pinterest" class="nav-tab">Pinterest</a>
					<a href="#x" class="nav-tab">X (Twitter)</a>
					<a href="#linkedin" class="nav-tab">LinkedIn</a>
					<a href="#youtube" class="nav-tab">YouTube</a>
					<a href="#instagram" class="nav-tab">Instagram</a>
					<a href="#help" class="nav-tab">Help</a>
				</h2>

				<div id="facebook" class="tab-content">
					<h3>Facebook Settings</h3>
					<table class="form-table">
						<tr>
							<th>Enable</th>
							<td><input type="checkbox" name="scppsn_settings[facebook][enabled]" value="1" <?php checked(isset($settings['facebook']['enabled'])); ?>></td>
						</tr>
						<tr>
							<th>Page Access Token</th>
							<td>
								<input type="text" name="scppsn_settings[facebook][access_token]" value="<?php echo esc_attr($settings['facebook']['access_token'] ?? ''); ?>" class="large-text">
								<p class="description">Long-lived token with <code>pages_manage_posts</code> permission.</p>
							</td>
						</tr>
						<tr>
							<th>Page ID</th>
							<td>
								<input type="text" name="scppsn_settings[facebook][page_id]" value="<?php echo esc_attr($settings['facebook']['page_id'] ?? ''); ?>" class="regular-text">
								<p class="description">The numeric ID of your Facebook Page.</p>
							</td>
						</tr>
					</table>
				</div>

				<div id="pinterest" class="tab-content" style="display:none;">
					<h3>Pinterest Settings</h3>
					<table class="form-table">
						<tr>
							<th>Enable</th>
							<td><input type="checkbox" name="scppsn_settings[pinterest][enabled]" value="1" <?php checked(isset($settings['pinterest']['enabled'])); ?>></td>
						</tr>
						<tr>
							<th>Access Token</th>
							<td>
								<input type="text" name="scppsn_settings[pinterest][access_token]" value="<?php echo esc_attr($settings['pinterest']['access_token'] ?? ''); ?>" class="large-text">
								<p class="description">App token with <code>pins:write</code> scope.</p>
							</td>
						</tr>
						<tr>
							<th>Board ID</th>
							<td>
								<input type="text" name="scppsn_settings[pinterest][board_id]" value="<?php echo esc_attr($settings['pinterest']['board_id'] ?? ''); ?>" class="regular-text">
								<p class="description">The numeric ID of the board to pin to.</p>
							</td>
						</tr>
					</table>
				</div>

				<div id="x" class="tab-content" style="display:none;">
					<h3>X (Twitter) Settings</h3>
					<table class="form-table">
						<tr>
							<th>Enable</th>
							<td><input type="checkbox" name="scppsn_settings[x][enabled]" value="1" <?php checked(isset($settings['x']['enabled'])); ?>></td>
						</tr>
						<tr>
							<th>Bearer Token</th>
							<td>
								<input type="text" name="scppsn_settings[x][bearer_token]" value="<?php echo esc_attr($settings['x']['bearer_token'] ?? ''); ?>" class="large-text">
								<p class="description">App-only Bearer Token with read/write permissions.</p>
							</td>
						</tr>
					</table>
				</div>

				<div id="linkedin" class="tab-content" style="display:none;">
					<h3>LinkedIn Settings</h3>
					<table class="form-table">
						<tr>
							<th>Enable</th>
							<td><input type="checkbox" name="scppsn_settings[linkedin][enabled]" value="1" <?php checked(isset($settings['linkedin']['enabled'])); ?>></td>
						</tr>
						<tr>
							<th>Access Token</th>
							<td>
								<input type="text" name="scppsn_settings[linkedin][access_token]" value="<?php echo esc_attr($settings['linkedin']['access_token'] ?? ''); ?>" class="large-text">
								<p class="description">OAuth 2.0 Access Token with <code>w_member_social</code> scope.</p>
							</td>
						</tr>
					</table>
				</div>

				<div id="youtube" class="tab-content" style="display:none;">
					<h3>YouTube Settings</h3>
					<table class="form-table">
						<tr>
							<th>Enable</th>
							<td><input type="checkbox" name="scppsn_settings[youtube][enabled]" value="1" <?php checked(isset($settings['youtube']['enabled'])); ?>></td>
						</tr>
						<tr>
							<th>Access Token</th>
							<td>
								<input type="text" name="scppsn_settings[youtube][access_token]" value="<?php echo esc_attr($settings['youtube']['access_token'] ?? ''); ?>" class="large-text">
								<p class="description">OAuth 2.0 token for YouTube Data API.</p>
							</td>
						</tr>
					</table>
				</div>

				<div id="instagram" class="tab-content" style="display:none;">
					<h3>Instagram Settings</h3>
					<table class="form-table">
						<tr>
							<th>Enable</th>
							<td><input type="checkbox" name="scppsn_settings[instagram][enabled]" value="1" <?php checked(isset($settings['instagram']['enabled'])); ?>></td>
						</tr>
						<tr>
							<th>Access Token</th>
							<td>
								<input type="text" name="scppsn_settings[instagram][access_token]" value="<?php echo esc_attr($settings['instagram']['access_token'] ?? ''); ?>" class="large-text">
								<p class="description">Graph API Token with <code>instagram_content_publish</code>.</p>
							</td>
						</tr>
						<tr>
							<th>Business Account ID</th>
							<td>
								<input type="text" name="scppsn_settings[instagram][user_id]" value="<?php echo esc_attr($settings['instagram']['user_id'] ?? ''); ?>" class="regular-text">
								<p class="description">Your Instagram Business Account ID.</p>
							</td>
						</tr>
					</table>
				</div>

				<div id="help" class="tab-content" style="display:none;">
					<div class="notice notice-info inline">
						<p><strong>Note:</strong> This plugin posts content <strong>manually</strong>. It does not automatically post products when you create or update them.</p>
						<p>In the product edit screen, look for the <strong>"Post to Social Networks"</strong> box. Use the checkboxes to select which networks you want to post to, then click the <strong>"Post Now"</strong> button.</p>
					</div>
					<h3>How to get API Credentials</h3>
					<div class="scppsn-help-section">
						<h4><span class="dashicons dashicons-facebook-alt"></span> Facebook</h4>
						<p>1. Go to <a href="https://developers.facebook.com/" target="_blank">Meta for Developers</a>.</p>
						<p>2. Create a specific App for your site.</p>
						<p>3. Generate a <strong>Page Access Token</strong> with <code>pages_manage_posts</code> and <code>pages_read_engagement</code> permissions.</p>
						<p>4. Find your <strong>Page ID</strong> in the "About" section of your Facebook Page.</p>
					</div>
					<hr>
					<div class="scppsn-help-section">
						<h4><span class="dashicons dashicons-pinterest"></span> Pinterest</h4>
						<p>1. Go to <a href="https://developers.pinterest.com/" target="_blank">Pinterest Developers</a>.</p>
						<p>2. Create a new App.</p>
						<p>3. Generate an Access Token with <code>pins:read</code> and <code>pins:write</code> scopes.</p>
						<p>4. The <strong>Board ID</strong> is the numeric code found in your board's URL.</p>
					</div>
					<hr>
					<div class="scppsn-help-section">
						<h4><span class="dashicons dashicons-twitter"></span> X (Twitter)</h4>
						<p>1. Go to <a href="https://developer.twitter.com/en/portal/dashboard" target="_blank">X Developer Portal</a>.</p>
						<p>2. Create a Project and App.</p>
						<p>3. Use the <strong>Bearer Token</strong> (App-only) from the "Keys and Tokens" tab.</p>
					</div>
					<hr>
					<div class="scppsn-help-section">
						<h4><span class="dashicons dashicons-linkedin"></span> LinkedIn</h4>
						<p>1. Go to <a href="https://www.linkedin.com/developers/" target="_blank">LinkedIn Developers</a>.</p>
						<p>2. Create an App and link it to your company page (if applicable).</p>
						<p>3. Generate an OAuth 2.0 Access Token with the <code>w_member_social</code> scope.</p>
					</div>
					<hr>
					<div class="scppsn-help-section">
						<h4><span class="dashicons dashicons-instagram"></span> Instagram</h4>
						<p>1. Use the same App created in Meta for Developers (Facebook).</p>
						<p>2. Ensure your Instagram account is a <strong>Business Account</strong> and linked to your Facebook Page.</p>
						<p>3. The Access Token is the same as Facebook but requires <code>instagram_basic</code> and <code>instagram_content_publish</code>.</p>
					</div>
					<hr>
					<div class="scppsn-help-section">
						<h4><span class="dashicons dashicons-video-alt3"></span> YouTube</h4>
						<p>1. Go to <a href="https://console.cloud.google.com/" target="_blank">Google Cloud Console</a>.</p>
						<p>2. Enable the <strong>YouTube Data API v3</strong>.</p>
						<p>3. Create Credentials (OAuth Client ID) and obtain an Access Token.</p>
					</div>
				</div>

				<?php submit_button(); ?>
			</form>
		</div>
		<script>
			jQuery(document).ready(function($) {
				$('.nav-tab').click(function(e) {
					e.preventDefault();
					$('.nav-tab').removeClass('nav-tab-active');
					$(this).addClass('nav-tab-active');
					$('.tab-content').hide();
					$($(this).attr('href')).show();
				});
			});
		</script>
<?php
	}

	public function add_meta_box()
	{
		add_meta_box(
			'scppsn_post',
			'Post to Social Networks',
			array($this, 'render_meta_box'),
			'product',
			'side',
			'high'
		);
	}

	public function render_meta_box($post)
	{
		wp_nonce_field('scppsn_meta', 'scppsn_nonce');
		$settings = get_option('scppsn_settings', array());
		$posted = get_post_meta($post->ID, '_scppsn_posted', true) ?: array();

		$networks = array(
			'facebook' => 'Facebook',
			'pinterest' => 'Pinterest',
			'x' => 'X (Twitter)',
			'linkedin' => 'LinkedIn',
			'youtube' => 'YouTube',
			'instagram' => 'Instagram'
		);

		foreach ($networks as $key => $label) {
			$enabled = isset($settings[$key]['enabled']);
			$is_posted = isset($posted[$key]);
			echo '<p><label><input type="checkbox" name="scppsn_networks[]" value="' . esc_attr($key) . '"' .
				(! $enabled ? ' disabled' : '') . '> ' . esc_html($label);
			if (! $enabled) echo ' <em>(not configured)</em>';
			if ($is_posted) echo ' <strong style="color:green;">✓</strong>';
			echo '</label></p>';
		}

		echo '<button type="button" class="button button-primary scppsn-post-btn" data-product="' . esc_attr($post->ID) . '">Post Now</button>';
		echo '<div class="scppsn-status" style="margin-top:10px;padding:8px;display:none;"></div>';
	}

	public function enqueue_scripts($hook)
	{
		if ('post.php' === $hook || 'post-new.php' === $hook || $hook === $this->settings_page_hook) {
			wp_enqueue_style('scppsn-admin-css', plugin_dir_url(__FILE__) . 'assets/admin.css', array(), '1.0');
		}

		if ('post.php' === $hook || 'post-new.php' === $hook) {
			wp_enqueue_script('scppsn-admin', plugin_dir_url(__FILE__) . 'assets/admin.js', array('jquery'), '1.0', true);
			wp_localize_script('scppsn-admin', 'scppsnData', array(
				'ajax_url' => admin_url('admin-ajax.php'),
				'nonce' => wp_create_nonce('scppsn_ajax')
			));
		}
	}

	public function ajax_post()
	{
		check_ajax_referer('scppsn_ajax', 'nonce');

		if (! current_user_can('edit_products')) {
			wp_send_json_error(array('message' => 'Permission denied'));
		}

		$product_id = absint($_POST['product_id'] ?? 0);
		$networks = array_map('sanitize_text_field', wp_unslash($_POST['networks'] ?? array()));

		if (! $product_id || empty($networks)) {
			wp_send_json_error(array('message' => 'Invalid data'));
		}

		$product = wc_get_product($product_id);
		if (! $product) {
			wp_send_json_error(array('message' => 'Product not found'));
		}

		$results = $this->post_to_networks($product, $networks);
		$success = count(array_filter($results, fn($r) => $r['success']));

		if ($success > 0) {
			wp_send_json_success(array('message' => "Posted to {$success} network(s)", 'results' => $results));
		} else {
			wp_send_json_error(array('message' => 'All posts failed', 'results' => $results));
		}
	}

	private function post_to_networks($product, $networks)
	{
		$settings = get_option('scppsn_settings', array());
		$results = array();
		$posted = array();

		$data = array(
			'title' => $product->get_name(),
			'description' => wp_strip_all_tags($product->get_short_description()),
			'url' => get_permalink($product->get_id()),
			'price' => $product->get_price(),
			'image' => wp_get_attachment_url($product->get_image_id())
		);

		foreach ($networks as $network) {
			if (! isset($settings[$network]['enabled'])) {
				$results[$network] = array('success' => false, 'message' => 'Not enabled');
				continue;
			}

			$result = $this->post_to_network($network, $data, $settings[$network]);
			$results[$network] = $result;

			if ($result['success']) {
				$posted[$network] = time();
			}

			$this->log($product->get_id(), $network, $result);
		}

		update_post_meta($product->get_id(), '_scppsn_posted', $posted);
		return $results;
	}

	private function post_to_network($network, $data, $config)
	{
		$message = "{$data['title']} - " . wc_price($data['price']) . ". {$data['url']}";

		switch ($network) {
			case 'facebook':
				return $this->post_facebook($data, $config, $message);
			case 'pinterest':
				return $this->post_pinterest($data, $config);
			case 'x':
				return $this->post_x($data, $config, $message);
			case 'linkedin':
				return $this->post_linkedin($data, $config, $message);
			case 'youtube':
				return array('success' => false, 'message' => 'YouTube API limited');
			case 'instagram':
				return $this->post_instagram($data, $config, $message);
		}

		return array('success' => false, 'message' => 'Unknown network');
	}

	private function post_facebook($data, $config, $message)
	{
		if (empty($config['access_token']) || empty($config['page_id'])) {
			return array('success' => false, 'message' => 'Missing credentials');
		}

		$response = wp_remote_post("https://graph.facebook.com/v18.0/{$config['page_id']}/feed", array(
			'body' => array(
				'message' => $message,
				'link' => $data['url'],
				'access_token' => $config['access_token']
			)
		));

		if (is_wp_error($response)) {
			return array('success' => false, 'message' => $response->get_error_message());
		}

		$body = json_decode(wp_remote_retrieve_body($response), true);

		if (isset($body['error'])) {
			return array('success' => false, 'message' => $body['error']['message']);
		}

		return array('success' => true, 'message' => 'Posted to Facebook');
	}

	private function post_pinterest($data, $config)
	{
		if (empty($config['access_token']) || empty($config['board_id']) || empty($data['image'])) {
			return array('success' => false, 'message' => 'Missing credentials or image');
		}

		$response = wp_remote_post('https://api.pinterest.com/v5/pins', array(
			'headers' => array(
				'Authorization' => 'Bearer ' . $config['access_token'],
				'Content-Type' => 'application/json'
			),
			'body' => wp_json_encode(array(
				'board_id' => $config['board_id'],
				'title' => $data['title'],
				'description' => $data['description'],
				'link' => $data['url'],
				'media_source' => array(
					'source_type' => 'image_url',
					'url' => $data['image']
				)
			))
		));

		if (is_wp_error($response)) {
			return array('success' => false, 'message' => $response->get_error_message());
		}

		$body = json_decode(wp_remote_retrieve_body($response), true);

		if (isset($body['code']) && $body['code'] !== 200) {
			return array('success' => false, 'message' => $body['message'] ?? 'Failed');
		}

		return array('success' => true, 'message' => 'Posted to Pinterest');
	}

	private function post_x($data, $config, $message)
	{
		if (empty($config['bearer_token'])) {
			return array('success' => false, 'message' => 'Missing bearer token');
		}

		$text = strlen($message) > 280 ? substr($message, 0, 277) . '...' : $message;

		$response = wp_remote_post('https://api.twitter.com/2/tweets', array(
			'headers' => array(
				'Authorization' => 'Bearer ' . $config['bearer_token'],
				'Content-Type' => 'application/json'
			),
			'body' => wp_json_encode(array('text' => $text))
		));

		if (is_wp_error($response)) {
			return array('success' => false, 'message' => $response->get_error_message());
		}

		$body = json_decode(wp_remote_retrieve_body($response), true);

		if (isset($body['errors'])) {
			return array('success' => false, 'message' => $body['errors'][0]['message']);
		}

		return array('success' => true, 'message' => 'Posted to X');
	}

	private function post_linkedin($data, $config, $message)
	{
		if (empty($config['access_token'])) {
			return array('success' => false, 'message' => 'Missing access token');
		}

		$response = wp_remote_post('https://api.linkedin.com/v2/ugcPosts', array(
			'headers' => array(
				'Authorization' => 'Bearer ' . $config['access_token'],
				'Content-Type' => 'application/json',
				'X-Restli-Protocol-Version' => '2.0.0'
			),
			'body' => wp_json_encode(array(
				'author' => 'urn:li:person:me',
				'lifecycleState' => 'PUBLISHED',
				'specificContent' => array(
					'com.linkedin.ugc.ShareContent' => array(
						'shareCommentary' => array('text' => $message),
						'shareMediaCategory' => 'NONE'
					)
				),
				'visibility' => array(
					'com.linkedin.ugc.MemberNetworkVisibility' => 'PUBLIC'
				)
			))
		));

		if (is_wp_error($response)) {
			return array('success' => false, 'message' => $response->get_error_message());
		}

		if (wp_remote_retrieve_response_code($response) !== 201) {
			$body = json_decode(wp_remote_retrieve_body($response), true);
			return array('success' => false, 'message' => $body['message'] ?? 'Failed');
		}

		return array('success' => true, 'message' => 'Posted to LinkedIn');
	}

	private function post_instagram($data, $config, $message)
	{
		if (empty($config['access_token']) || empty($config['user_id']) || empty($data['image'])) {
			return array('success' => false, 'message' => 'Missing credentials or image');
		}

		$container = wp_remote_post(add_query_arg(array(
			'image_url' => $data['image'],
			'caption' => $message,
			'access_token' => $config['access_token']
		), "https://graph.instagram.com/{$config['user_id']}/media"));

		if (is_wp_error($container)) {
			return array('success' => false, 'message' => $container->get_error_message());
		}

		$container_data = json_decode(wp_remote_retrieve_body($container), true);

		if (isset($container_data['error']) || ! isset($container_data['id'])) {
			return array('success' => false, 'message' => $container_data['error']['message'] ?? 'Container failed');
		}

		$publish = wp_remote_post(add_query_arg(array(
			'creation_id' => $container_data['id'],
			'access_token' => $config['access_token']
		), "https://graph.instagram.com/{$config['user_id']}/media_publish"));

		if (is_wp_error($publish)) {
			return array('success' => false, 'message' => $publish->get_error_message());
		}

		$publish_data = json_decode(wp_remote_retrieve_body($publish), true);

		if (isset($publish_data['error'])) {
			return array('success' => false, 'message' => $publish_data['error']['message']);
		}

		return array('success' => true, 'message' => 'Posted to Instagram');
	}

	private function log($product_id, $network, $result)
	{
		global $wpdb;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		$wpdb->insert(
			$wpdb->prefix . 'scppsn_logs',
			array(
				'product_id' => $product_id,
				'network' => $network,
				'status' => $result['success'] ? 'success' : 'error',
				'message' => $result['message']
			),
			array('%d', '%s', '%s', '%s')
		);
	}
}

SCPPSN_Plugin::get_instance();
