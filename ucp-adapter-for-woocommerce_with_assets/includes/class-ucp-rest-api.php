<?php

/**
 * UCP REST API Handler
 *
 * @package UCP_Adapter
 */

// Exit if accessed directly.
if (! defined('ABSPATH')) {
	exit;
}

/**
 * UCP REST API Class
 */
class UCP_Adapter_REST_API
{

	/**
	 * API namespace
	 *
	 * @var string
	 */
	const NAMESPACE = 'ucp/v1';

	/**
	 * Register REST API routes
	 */
	public static function register_routes()
	{
		// Session endpoint - Create or retrieve session.
		register_rest_route(
			self::NAMESPACE,
			'/session',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array(__CLASS__, 'handle_session'),
				'permission_callback' => array(__CLASS__, 'check_api_permission'),
				'args'                => array(
					'platform'    => array(
						'required'          => false,
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_text_field',
						'description'       => __('Platform identifier', 'ucp-adapter-for-woocommerce'),
					),
					'user_data'   => array(
						'required'    => false,
						'type'        => 'object',
						'description' => __('User data for session initialization', 'ucp-adapter-for-woocommerce'),
					),
				),
			)
		);

		// Update endpoint - Update session data.
		register_rest_route(
			self::NAMESPACE,
			'/update/(?P<session_id>[a-zA-Z0-9_-]+)',
			array(
				'methods'             => WP_REST_Server::EDITABLE,
				'callback'            => array(__CLASS__, 'handle_update'),
				'permission_callback' => array(__CLASS__, 'check_api_permission'),
				'args'                => array(
					'session_id'  => array(
						'required'          => true,
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_text_field',
						'description'       => __('Session ID', 'ucp-adapter-for-woocommerce'),
					),
					'action'      => array(
						'required'          => true,
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_text_field',
						'description'       => __('Action to perform', 'ucp-adapter-for-woocommerce'),
					),
					'data'        => array(
						'required'    => false,
						'type'        => 'object',
						'description' => __('Data for the update', 'ucp-adapter-for-woocommerce'),
					),
				),
			)
		);

		// Complete endpoint - Finalize session.
		register_rest_route(
			self::NAMESPACE,
			'/complete/(?P<session_id>[a-zA-Z0-9_-]+)',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array(__CLASS__, 'handle_complete'),
				'permission_callback' => array(__CLASS__, 'check_api_permission'),
				'args'                => array(
					'session_id'  => array(
						'required'          => true,
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_text_field',
						'description'       => __('Session ID', 'ucp-adapter-for-woocommerce'),
					),
					'status'      => array(
						'required'          => false,
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_text_field',
						'default'           => 'completed',
						'description'       => __('Completion status', 'ucp-adapter-for-woocommerce'),
					),
					'metadata'    => array(
						'required'    => false,
						'type'        => 'object',
						'description' => __('Additional metadata', 'ucp-adapter-for-woocommerce'),
					),
				),
			)
		);

		// Status endpoint - Check session status.
		register_rest_route(
			self::NAMESPACE,
			'/status/(?P<session_id>[a-zA-Z0-9_-]+)',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array(__CLASS__, 'handle_status'),
				'permission_callback' => array(__CLASS__, 'check_api_permission'),
				'args'                => array(
					'session_id'  => array(
						'required'          => true,
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_text_field',
						'description'       => __('Session ID', 'ucp-adapter-for-woocommerce'),
					),
				),
			)
		);
	}

	/**
	 * Check API permission
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return bool|WP_Error
	 */
	public static function check_api_permission($request)
	{
		$api_key = $request->get_header('X-UCP-API-Key');

		if (empty($api_key)) {
			$api_key = $request->get_param('api_key');
		}

		$stored_key = get_option('ucp_adapter_api_key');

		if (empty($api_key) || ! hash_equals($stored_key, $api_key)) {
			return new WP_Error(
				'ucp_adapter_unauthorized',
				__('Invalid or missing API key.', 'ucp-adapter-for-woocommerce'),
				array('status' => 401)
			);
		}

		// Additional security check.
		if (! UCP_Adapter_Security::verify_request($request)) {
			return new WP_Error(
				'ucp_adapter_forbidden',
				__('Request verification failed.', 'ucp-adapter-for-woocommerce'),
				array('status' => 403)
			);
		}

		return true;
	}

	/**
	 * Handle session creation
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response|WP_Error
	 */
	public static function handle_session($request)
	{
		$platform  = $request->get_param('platform') ?? 'default';
		$user_data = $request->get_param('user_data') ?? array();

		$session_handler = UCP_Adapter_Session_Handler::get_instance();
		$session_id      = $session_handler->create_session($platform, $user_data);

		if (is_wp_error($session_id)) {
			return $session_id;
		}

		$response = array(
			'success'    => true,
			'session_id' => $session_id,
			'expires_at' => time() + (int) get_option('ucp_adapter_session_timeout', 3600),
			'message'    => __('Session created successfully.', 'ucp-adapter-for-woocommerce'),
		);

		do_action('ucp_adapter_session_created', $session_id, $platform, $user_data);

		return new WP_REST_Response($response, 201);
	}

	/**
	 * Handle session update
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response|WP_Error
	 */
	public static function handle_update($request)
	{
		$session_id = $request->get_param('session_id');
		$action     = $request->get_param('action');
		$data       = $request->get_param('data') ?? array();

		$session_handler = UCP_Adapter_Session_Handler::get_instance();
		$session         = $session_handler->get_session($session_id);

		if (is_wp_error($session)) {
			return $session;
		}

		// Process the update based on action.
		$result = self::process_update_action($session_id, $action, $data, $session);

		if (is_wp_error($result)) {
			return $result;
		}

		// Update session data.
		$updated = $session_handler->update_session($session_id, $result);

		if (is_wp_error($updated)) {
			return $updated;
		}

		$response = array(
			'success'    => true,
			'session_id' => $session_id,
			'action'     => $action,
			'data'       => $result,
			'message'    => __('Session updated successfully.', 'ucp-adapter-for-woocommerce'),
		);

		do_action('ucp_adapter_session_updated', $session_id, $action, $data);

		return new WP_REST_Response($response, 200);
	}

	/**
	 * Handle session completion
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response|WP_Error
	 */
	public static function handle_complete($request)
	{
		$session_id = $request->get_param('session_id');
		$status     = $request->get_param('status');
		$metadata   = $request->get_param('metadata') ?? array();

		$session_handler = UCP_Adapter_Session_Handler::get_instance();
		$session         = $session_handler->get_session($session_id);

		if (is_wp_error($session)) {
			return $session;
		}

		// Process completion.
		$completion_data = array(
			'status'       => $status,
			'completed_at' => current_time('mysql'),
			'metadata'     => $metadata,
		);

		$result = apply_filters('ucp_adapter_before_session_complete', $completion_data, $session_id, $session);

		// Mark session as complete.
		$completed = $session_handler->complete_session($session_id, $result);

		if (is_wp_error($completed)) {
			return $completed;
		}

		$response = array(
			'success'    => true,
			'session_id' => $session_id,
			'status'     => $status,
			'message'    => __('Session completed successfully.', 'ucp-adapter-for-woocommerce'),
		);

		do_action('ucp_adapter_session_completed', $session_id, $status, $metadata);

		return new WP_REST_Response($response, 200);
	}

	/**
	 * Handle session status check
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response|WP_Error
	 */
	public static function handle_status($request)
	{
		$session_id = $request->get_param('session_id');

		$session_handler = UCP_Adapter_Session_Handler::get_instance();
		$session         = $session_handler->get_session($session_id);

		if (is_wp_error($session)) {
			return $session;
		}

		$response = array(
			'success'    => true,
			'session_id' => $session_id,
			'status'     => $session['status'] ?? 'active',
			'created_at' => $session['created_at'] ?? null,
			'expires_at' => $session['expires'] ?? null,
			'data'       => $session['data'] ?? array(),
		);

		return new WP_REST_Response($response, 200);
	}

	/**
	 * Process update action
	 *
	 * @param string $session_id Session ID.
	 * @param string $action Action to perform.
	 * @param array  $data Data for the action.
	 * @param array  $session Current session data.
	 * @return array|WP_Error
	 */
	private static function process_update_action($session_id, $action, $data, $session)
	{
		$current_data = $session['data'] ?? array();

		switch ($action) {
			case 'add_item':
				if (! isset($current_data['items'])) {
					$current_data['items'] = array();
				}
				$current_data['items'][] = $data;
				break;

			case 'update_item':
				if (isset($data['index']) && isset($current_data['items'][$data['index']])) {
					$current_data['items'][$data['index']] = array_merge(
						$current_data['items'][$data['index']],
						$data
					);
				}
				break;

			case 'remove_item':
				if (isset($data['index']) && isset($current_data['items'][$data['index']])) {
					unset($current_data['items'][$data['index']]);
					$current_data['items'] = array_values($current_data['items']);
				}
				break;

			case 'set_data':
				$current_data = array_merge($current_data, $data);
				break;

			default:
				$current_data = apply_filters("ucp_adapter_update_action_{$action}", $current_data, $data, $session_id);
				break;
		}

		return $current_data;
	}
}
