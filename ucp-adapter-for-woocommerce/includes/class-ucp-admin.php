<?php

/**
 * UCP Admin Interface
 *
 * @package UCP_Adapter
 */

// Exit if accessed directly.
if (! defined('ABSPATH')) {
	exit;
}

/**
 * UCP Admin Class
 */
class UCP_Admin
{

	/**
	 * Single instance
	 *
	 * @var UCP_Admin
	 */
	private static $instance = null;

	/**
	 * Get instance
	 *
	 * @return UCP_Admin
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
		add_action('admin_menu', array($this, 'add_admin_menu'));
		add_action('admin_init', array($this, 'register_settings'));
		add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
	}

	/**
	 * Add admin menu
	 */
	public function add_admin_menu()
	{
		add_menu_page(
			__('UCP Adapter', 'ucp-adapter-for-woocommerce'),
			__('UCP Adapter', 'ucp-adapter-for-woocommerce'),
			'manage_options',
			'ucp-adapter-for-woocommerce',
			array($this, 'render_settings_page'),
			'dashicons-rest-api',
			80
		);

		add_submenu_page(
			'ucp-adapter-for-woocommerce',
			__('Settings', 'ucp-adapter-for-woocommerce'),
			__('Settings', 'ucp-adapter-for-woocommerce'),
			'manage_options',
			'ucp-adapter-for-woocommerce',
			array($this, 'render_settings_page')
		);

		add_submenu_page(
			'ucp-adapter-for-woocommerce',
			__('Sessions', 'ucp-adapter-for-woocommerce'),
			__('Sessions', 'ucp-adapter-for-woocommerce'),
			'manage_options',
			'ucp-adapter-sessions',
			array($this, 'render_sessions_page')
		);

		add_submenu_page(
			'ucp-adapter-for-woocommerce',
			__('Documentation', 'ucp-adapter-for-woocommerce'),
			__('Documentation', 'ucp-adapter-for-woocommerce'),
			'manage_options',
			'ucp-adapter-docs',
			array($this, 'render_docs_page')
		);
	}

	/**
	 * Register settings
	 */
	public function register_settings()
	{
		register_setting('ucp_adapter_settings', 'ucp_adapter_api_key', array('sanitize_callback' => 'sanitize_text_field'));
		register_setting('ucp_adapter_settings', 'ucp_adapter_session_timeout', array('sanitize_callback' => 'absint'));
		register_setting('ucp_adapter_settings', 'ucp_adapter_rate_limit_enabled', array('sanitize_callback' => 'absint'));
		register_setting('ucp_adapter_settings', 'ucp_adapter_rate_limit', array('sanitize_callback' => 'absint'));
		register_setting('ucp_adapter_settings', 'ucp_adapter_rate_window', array('sanitize_callback' => 'absint'));
		register_setting('ucp_adapter_settings', 'ucp_adapter_ip_whitelist', array('sanitize_callback' => 'sanitize_textarea_field'));
	}

	/**
	 * Enqueue admin scripts
	 *
	 * @param string $hook Current page hook.
	 */
	public function enqueue_admin_scripts($hook)
	{
		if (strpos($hook, 'ucp-adapter-for-woocommerce') === false) {
			return;
		}

		wp_enqueue_style(
			'ucp-adapter-admin',
			UCP_ADAPTER_PLUGIN_URL . 'assets/css/admin.css',
			array(),
			UCP_ADAPTER_VERSION
		);

		wp_enqueue_script(
			'ucp-adapter-admin',
			UCP_ADAPTER_PLUGIN_URL . 'assets/js/admin.js',
			array('jquery'),
			UCP_ADAPTER_VERSION,
			true
		);

		wp_localize_script(
			'ucp-adapter-admin',
			'ucpAdapterAdmin',
			array(
				'ajaxUrl' => admin_url('admin-ajax.php'),
				'nonce'   => wp_create_nonce('ucp_adapter_admin'),
			)
		);
	}

	/**
	 * Render settings page
	 */
	public function render_settings_page()
	{
?>
		<div class="wrap">
			<h1><?php echo esc_html(get_admin_page_title()); ?></h1>

			<form method="post" action="options.php">
				<?php
				settings_fields('ucp_adapter_settings');
				do_settings_sections('ucp_adapter_settings');
				?>

				<table class="form-table">
					<tr>
						<th scope="row">
							<label for="ucp_adapter_api_key"><?php esc_html_e('API Key', 'ucp-adapter-for-woocommerce'); ?></label>
						</th>
						<td>
							<input type="text"
								id="ucp_adapter_api_key"
								name="ucp_adapter_api_key"
								value="<?php echo esc_attr(get_option('ucp_adapter_api_key')); ?>"
								class="regular-text"
								readonly />
							<p class="description">
								<?php esc_html_e('Use this API key in your UCP requests.', 'ucp-adapter-for-woocommerce'); ?>
								<button type="button" class="button button-secondary" id="regenerate-api-key">
									<?php esc_html_e('Regenerate', 'ucp-adapter-for-woocommerce'); ?>
								</button>
							</p>
						</td>
					</tr>

					<tr>
						<th scope="row">
							<label for="ucp_adapter_session_timeout"><?php esc_html_e('Session Timeout (seconds)', 'ucp-adapter-for-woocommerce'); ?></label>
						</th>
						<td>
							<input type="number"
								id="ucp_adapter_session_timeout"
								name="ucp_adapter_session_timeout"
								value="<?php echo esc_attr(get_option('ucp_adapter_session_timeout', 3600)); ?>"
								class="small-text" />
							<p class="description"><?php esc_html_e('Default: 3600 seconds (1 hour)', 'ucp-adapter-for-woocommerce'); ?></p>
						</td>
					</tr>

					<tr>
						<th scope="row">
							<label for="ucp_adapter_rate_limit_enabled"><?php esc_html_e('Enable Rate Limiting', 'ucp-adapter-for-woocommerce'); ?></label>
						</th>
						<td>
							<input type="checkbox"
								id="ucp_adapter_rate_limit_enabled"
								name="ucp_adapter_rate_limit_enabled"
								value="1"
								<?php checked(get_option('ucp_adapter_rate_limit_enabled'), 1); ?> />
						</td>
					</tr>

					<tr>
						<th scope="row">
							<label for="ucp_adapter_rate_limit"><?php esc_html_e('Rate Limit', 'ucp-adapter-for-woocommerce'); ?></label>
						</th>
						<td>
							<input type="number"
								id="ucp_adapter_rate_limit"
								name="ucp_adapter_rate_limit"
								value="<?php echo esc_attr(get_option('ucp_adapter_rate_limit', 100)); ?>"
								class="small-text" />
							<p class="description"><?php esc_html_e('Maximum requests per time window', 'ucp-adapter-for-woocommerce'); ?></p>
						</td>
					</tr>

					<tr>
						<th scope="row">
							<label for="ucp_adapter_rate_window"><?php esc_html_e('Rate Window (seconds)', 'ucp-adapter-for-woocommerce'); ?></label>
						</th>
						<td>
							<input type="number"
								id="ucp_adapter_rate_window"
								name="ucp_adapter_rate_window"
								value="<?php echo esc_attr(get_option('ucp_adapter_rate_window', 60)); ?>"
								class="small-text" />
						</td>
					</tr>
				</table>

				<?php submit_button(); ?>
			</form>

			<h2><?php esc_html_e('REST API Endpoints', 'ucp-adapter-for-woocommerce'); ?></h2>
			<table class="widefat">
				<thead>
					<tr>
						<th><?php esc_html_e('Endpoint', 'ucp-adapter-for-woocommerce'); ?></th>
						<th><?php esc_html_e('Method', 'ucp-adapter-for-woocommerce'); ?></th>
						<th><?php esc_html_e('Description', 'ucp-adapter-for-woocommerce'); ?></th>
					</tr>
				</thead>
				<tbody>
					<tr>
						<td><code>/wp-json/ucp/v1/session</code></td>
						<td>POST</td>
						<td><?php esc_html_e('Create a new session', 'ucp-adapter-for-woocommerce'); ?></td>
					</tr>
					<tr>
						<td><code>/wp-json/ucp/v1/update/{session_id}</code></td>
						<td>PUT</td>
						<td><?php esc_html_e('Update session data', 'ucp-adapter-for-woocommerce'); ?></td>
					</tr>
					<tr>
						<td><code>/wp-json/ucp/v1/complete/{session_id}</code></td>
						<td>POST</td>
						<td><?php esc_html_e('Complete a session', 'ucp-adapter-for-woocommerce'); ?></td>
					</tr>
					<tr>
						<td><code>/wp-json/ucp/v1/status/{session_id}</code></td>
						<td>GET</td>
						<td><?php esc_html_e('Check session status', 'ucp-adapter-for-woocommerce'); ?></td>
					</tr>
				</tbody>
			</table>
		</div>
	<?php
	}

	/**
	 * Render sessions page
	 */
	public function render_sessions_page()
	{
		$sessions = UCP_Session_Handler::get_instance()->get_recent_sessions();
	?>
		<div class="wrap">
			<h1><?php esc_html_e('Active Sessions', 'ucp-adapter-for-woocommerce'); ?></h1>

			<table class="widefat striped">
				<thead>
					<tr>
						<th><?php esc_html_e('Session ID', 'ucp-adapter-for-woocommerce'); ?></th>
						<th><?php esc_html_e('Created', 'ucp-adapter-for-woocommerce'); ?></th>
						<th><?php esc_html_e('Expires', 'ucp-adapter-for-woocommerce'); ?></th>
						<th><?php esc_html_e('Status', 'ucp-adapter-for-woocommerce'); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php if (! empty($sessions)) : ?>
						<?php foreach ($sessions as $session) : ?>
							<tr>
								<td><code><?php echo esc_html($session['session_id']); ?></code></td>
								<td><?php echo esc_html($session['created_at']); ?></td>
								<td><?php echo esc_html(gmdate('Y-m-d H:i:s', $session['expires'])); ?></td>
								<td>
									<?php
									$data   = json_decode($session['session_data'], true);
									$status = $data['status'] ?? 'active';
									echo esc_html(ucfirst($status));
									?>
								</td>
							</tr>
						<?php endforeach; ?>
					<?php else : ?>
						<tr>
							<td colspan="4"><?php esc_html_e('No sessions found.', 'ucp-adapter-for-woocommerce'); ?></td>
						</tr>
					<?php endif; ?>
				</tbody>
			</table>
		</div>
	<?php
	}

	/**
	 * Render documentation page
	 */
	public function render_docs_page()
	{
	?>
		<div class="wrap">
			<h1><?php esc_html_e('UCP Adapter Documentation', 'ucp-adapter-for-woocommerce'); ?></h1>

			<div class="card">
				<h2><?php esc_html_e('Getting Started', 'ucp-adapter-for-woocommerce'); ?></h2>
				<p><?php esc_html_e('The UCP Adapter provides REST API endpoints for Universal Commerce Platform integration.', 'ucp-adapter-for-woocommerce'); ?></p>

				<h3><?php esc_html_e('Authentication', 'ucp-adapter-for-woocommerce'); ?></h3>
				<p><?php esc_html_e('Include your API key in the request header:', 'ucp-adapter-for-woocommerce'); ?></p>
				<pre><code>X-UCP-API-Key: your-api-key-here</code></pre>

				<h3><?php esc_html_e('Example: Create Session', 'ucp-adapter-for-woocommerce'); ?></h3>
				<pre><code>POST /wp-json/ucp/v1/session
Content-Type: application/json
X-UCP-API-Key: your-api-key

{
  "platform": "woocommerce",
  "user_data": {
    "user_id": 123,
    "email": "user@example.com"
  }
}</code></pre>
			</div>
		</div>
<?php
	}
}
