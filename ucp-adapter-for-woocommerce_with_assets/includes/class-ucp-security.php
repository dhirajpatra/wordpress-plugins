<?php

/**
 * UCP Security Handler
 *
 * @package UCP_Adapter
 */

// Exit if accessed directly.
if (! defined('ABSPATH')) {
	exit;
}

/**
 * UCP Security Class
 */
class UCP_Adapter_Security
{

	/**
	 * Verify request authenticity
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return bool True if verified, false otherwise.
	 */
	public static function verify_request($request)
	{
		// Check if request is from allowed IP (if configured).
		if (! self::check_ip_whitelist($request)) {
			return false;
		}

		// Verify rate limiting.
		if (! self::check_rate_limit($request)) {
			return false;
		}

		// Additional custom verification can be added here.
		return apply_filters('ucp_adapter_verify_request', true, $request);
	}

	/**
	 * Check IP whitelist
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return bool True if allowed, false otherwise.
	 */
	private static function check_ip_whitelist($request)
	{
		$whitelist = get_option('ucp_adapter_ip_whitelist', array());

		if (empty($whitelist)) {
			return true;
		}

		$client_ip = self::get_client_ip();

		return in_array($client_ip, $whitelist, true);
	}

	/**
	 * Check rate limit
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return bool True if within limits, false otherwise.
	 */
	private static function check_rate_limit($request)
	{
		$rate_limit_enabled = get_option('ucp_adapter_rate_limit_enabled', false);

		if (! $rate_limit_enabled) {
			return true;
		}

		$client_ip = self::get_client_ip();
		$limit     = (int) get_option('ucp_adapter_rate_limit', 100);
		$window    = (int) get_option('ucp_adapter_rate_window', 60);

		$transient_key = 'ucp_adapter_rate_' . md5($client_ip);
		$requests      = get_transient($transient_key);

		if (false === $requests) {
			$requests = 0;
		}

		$requests++;

		if ($requests > $limit) {
			return false;
		}

		set_transient($transient_key, $requests, $window);

		return true;
	}

	/**
	 * Get client IP address
	 *
	 * @return string Client IP.
	 */
	private static function get_client_ip()
	{
		$ip = '';

		if (! empty($_SERVER['HTTP_CLIENT_IP'])) {
			$ip = sanitize_text_field(wp_unslash($_SERVER['HTTP_CLIENT_IP']));
		} elseif (! empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
			$ip = sanitize_text_field(wp_unslash($_SERVER['HTTP_X_FORWARDED_FOR']));
		} elseif (! empty($_SERVER['REMOTE_ADDR'])) {
			$ip = sanitize_text_field(wp_unslash($_SERVER['REMOTE_ADDR']));
		}

		return $ip;
	}

	/**
	 * Sanitize session data
	 *
	 * @param array $data Data to sanitize.
	 * @return array Sanitized data.
	 */
	public static function sanitize_data($data)
	{
		if (! is_array($data)) {
			return array();
		}

		$sanitized = array();

		foreach ($data as $key => $value) {
			$key = sanitize_key($key);

			if (is_array($value)) {
				$sanitized[$key] = self::sanitize_data($value);
			} elseif (is_string($value)) {
				$sanitized[$key] = sanitize_text_field($value);
			} else {
				$sanitized[$key] = $value;
			}
		}

		return $sanitized;
	}

	/**
	 * Generate secure API key
	 *
	 * @return string API key.
	 */
	public static function generate_api_key()
	{
		return wp_generate_password(32, false);
	}
}
