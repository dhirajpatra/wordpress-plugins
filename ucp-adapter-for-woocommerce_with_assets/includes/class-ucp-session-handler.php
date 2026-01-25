<?php

/**
 * UCP Session Handler
 *
 * @package UCP_Adapter
 */

// Exit if accessed directly.
if (! defined('ABSPATH')) {
	exit;
}

/**
 * UCP Session Handler Class
 */
class UCP_Adapter_Session_Handler
{

	/**
	 * Single instance
	 *
	 * @var UCP_Adapter_Session_Handler
	 */
	private static $instance = null;

	/**
	 * Get instance
	 *
	 * @return UCP_Adapter_Session_Handler
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
		// Schedule cleanup of expired sessions.
		if (! wp_next_scheduled('ucp_adapter_cleanup_sessions')) {
			wp_schedule_event(time(), 'hourly', 'ucp_adapter_cleanup_sessions');
		}
		add_action('ucp_adapter_cleanup_sessions', array(__CLASS__, 'cleanup_expired_sessions'));
	}

	/**
	 * Clean session cache
	 */
	public function clean_session_cache()
	{
		wp_cache_delete('ucp_adapter_recent_sessions', 'ucp_adapter');
	}

	/**
	 * Create a new session
	 *
	 * @param string $platform Platform identifier.
	 * @param array  $user_data User data.
	 * @return string|WP_Error Session ID or error.
	 */
	public function create_session($platform = 'default', $user_data = array())
	{
		global $wpdb;

		$session_id = $this->generate_session_id();
		$timeout    = (int) get_option('ucp_adapter_session_timeout', 3600);
		$expires    = time() + $timeout;

		$session_data = array(
			'platform'   => sanitize_text_field($platform),
			'user_data'  => $user_data,
			'data'       => array(),
			'status'     => 'active',
			'created_at' => current_time('mysql'),
		);

		$table_name = $wpdb->prefix . 'ucp_adapter_sessions';

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$result = $wpdb->insert(
			$table_name,
			array(
				'session_id'   => $session_id,
				'session_data' => wp_json_encode($session_data),
				'expires'      => $expires,
			),
			array('%s', '%s', '%d')
		);

		if (false === $result) {
			return new WP_Error(
				'ucp_session_error',
				__('Failed to create session.', 'ucp-adapter-for-woocommerce'),
				array('status' => 500)
			);
		}

		$this->clean_session_cache();

		return $session_id;
	}

	/**
	 * Get session data
	 *
	 * @param string $session_id Session ID.
	 * @return array|WP_Error Session data or error.
	 */
	public function get_session($session_id)
	{
		$cached_session = wp_cache_get('ucp_adapter_session_' . $session_id, 'ucp_adapter');

		if (false !== $cached_session) {
			return $cached_session;
		}

		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$session = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$wpdb->prefix}ucp_adapter_sessions WHERE session_id = %s AND expires > %d",
				$session_id,
				time()
			),
			ARRAY_A
		);

		if (! $session) {
			return new WP_Error(
				'ucp_session_not_found',
				__('Session not found or expired.', 'ucp-adapter-for-woocommerce'),
				array('status' => 404)
			);
		}

		$session['data'] = json_decode($session['session_data'], true);
		unset($session['session_data']);

		wp_cache_set('ucp_adapter_session_' . $session_id, $session, 'ucp_adapter', 3600);

		return $session;
	}

	/**
	 * Update session data
	 *
	 * @param string $session_id Session ID.
	 * @param array  $data Data to update.
	 * @return bool|WP_Error True on success, error on failure.
	 */
	public function update_session($session_id, $data)
	{
		global $wpdb;

		$session = $this->get_session($session_id);

		if (is_wp_error($session)) {
			return $session;
		}

		$session_data            = json_decode($session['data']['session_data'] ?? '{}', true);
		$session_data['data']    = $data;
		$session_data['updated'] = current_time('mysql');

		$table_name = $wpdb->prefix . 'ucp_adapter_sessions';

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$result = $wpdb->update(
			$table_name,
			array(
				'session_data' => wp_json_encode($session_data),
			),
			array('session_id' => $session_id),
			array('%s'),
			array('%s')
		);

		if (false === $result) {
			return new WP_Error(
				'ucp_update_error',
				__('Failed to update session.', 'ucp-adapter-for-woocommerce'),
				array('status' => 500)
			);
		}

		$this->clean_session_cache();
		wp_cache_delete('ucp_adapter_session_' . $session_id, 'ucp_adapter');

		return true;
	}

	/**
	 * Complete session
	 *
	 * @param string $session_id Session ID.
	 * @param array  $completion_data Completion data.
	 * @return bool|WP_Error True on success, error on failure.
	 */
	public function complete_session($session_id, $completion_data)
	{
		global $wpdb;

		$session = $this->get_session($session_id);

		if (is_wp_error($session)) {
			return $session;
		}

		$session_data               = json_decode($session['data']['session_data'] ?? '{}', true);
		$session_data['status']     = $completion_data['status'] ?? 'completed';
		$session_data['completed']  = true;
		$session_data['completion'] = $completion_data;

		$table_name = $wpdb->prefix . 'ucp_adapter_sessions';

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$result = $wpdb->update(
			$table_name,
			array(
				'session_data' => wp_json_encode($session_data),
				'expires'      => time() + (86400 * 7), // Keep for 7 days after completion.
			),
			array('session_id' => $session_id),
			array('%s', '%d'),
			array('%s')
		);

		if (false === $result) {
			return new WP_Error(
				'ucp_complete_error',
				__('Failed to complete session.', 'ucp-adapter-for-woocommerce'),
				array('status' => 500)
			);
		}

		$this->clean_session_cache();
		wp_cache_delete('ucp_adapter_session_' . $session_id, 'ucp_adapter');

		return true;
	}

	/**
	 * Delete session
	 *
	 * @param string $session_id Session ID.
	 * @return bool True on success, false on failure.
	 */
	public function delete_session($session_id)
	{
		global $wpdb;

		$table_name = $wpdb->prefix . 'ucp_adapter_sessions';

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$result = $wpdb->delete(
			$table_name,
			array('session_id' => $session_id),
			array('%s')
		);

		if (false !== $result) {
			$this->clean_session_cache();
			wp_cache_delete('ucp_adapter_session_' . $session_id, 'ucp_adapter');
		}

		return false !== $result;
	}

	/**
	 * Get recent sessions
	 *
	 * @param int $limit Number of sessions to retrieve.
	 * @return array List of sessions.
	 */
	public function get_recent_sessions($limit = 50)
	{
		$cached_sessions = wp_cache_get('ucp_adapter_recent_sessions', 'ucp_adapter');

		if (false !== $cached_sessions) {
			return $cached_sessions;
		}

		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$sessions = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM {$wpdb->prefix}ucp_adapter_sessions ORDER BY created_at DESC LIMIT %d",
				$limit
			),
			ARRAY_A
		);

		wp_cache_set('ucp_adapter_recent_sessions', $sessions, 'ucp_adapter', 3600);

		return $sessions;
	}

	/**
	 * Generate unique session ID
	 *
	 * @return string Session ID.
	 */
	private function generate_session_id()
	{
		return 'ucp_adapter_' . bin2hex(random_bytes(16)) . '_' . time();
	}

	/**
	 * Cleanup expired sessions
	 */
	public static function cleanup_expired_sessions()
	{
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$wpdb->query(
			$wpdb->prepare(
				"DELETE FROM {$wpdb->prefix}ucp_adapter_sessions WHERE expires < %d",
				time()
			)
		);

		wp_cache_delete('ucp_adapter_recent_sessions', 'ucp_adapter');
	}
}
