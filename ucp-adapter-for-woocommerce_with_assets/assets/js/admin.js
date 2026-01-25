/**
 * UCP Adapter Admin JavaScript
 *
 * @package UCP_Adapter
 */

(function ($) {
	'use strict';

	/**
	 * Admin functionality
	 */
	const UCPAdapterAdminClient = {

		/**
		 * Initialize
		 */
		init: function () {
			this.bindEvents();
			this.initCopyButtons();
		},

		/**
		 * Bind events
		 */
		bindEvents: function () {
			$('#regenerate-api-key').on('click', this.regenerateAPIKey.bind(this));
		},

		/**
		 * Initialize copy to clipboard buttons
		 */
		initCopyButtons: function () {
			// Add copy button next to API key field
			const $apiKeyField = $('#ucp_adapter_api_key');
			if ($apiKeyField.length) {
				const $copyBtn = $('<button type="button" class="button button-secondary">Copy</button>');
				$apiKeyField.after($copyBtn);

				$copyBtn.on('click', function () {
					$apiKeyField.select();
					document.execCommand('copy');

					const originalText = $copyBtn.text();
					$copyBtn.text('Copied!');

					setTimeout(function () {
						$copyBtn.text(originalText);
					}, 2000);
				});
			}
		},

		/**
		 * Regenerate API key
		 *
		 * @param {Event} e Event object
		 */
		regenerateAPIKey: function (e) {
			e.preventDefault();

			if (!confirm('Are you sure you want to regenerate the API key? This will invalidate the current key.')) {
				return;
			}

			const $button = $(e.currentTarget);
			$button.prop('disabled', true).text('Regenerating...');

			$.ajax({
				url: ucpAdapterAdminData.ajaxUrl,
				method: 'POST',
				data: {
					action: 'ucp_adapter_regenerate_api_key',
					nonce: ucpAdapterAdminData.nonce
				}
			})
				.done((response) => {
					if (response.success) {
						$('#ucp_adapter_api_key').val(response.data.api_key);
						this.showNotice('API key regenerated successfully!', 'success');
					} else {
						this.showNotice(response.data.message || 'Failed to regenerate API key', 'error');
					}
				})
				.fail(() => {
					this.showNotice('Failed to regenerate API key', 'error');
				})
				.always(() => {
					$button.prop('disabled', false).text('Regenerate');
				});
		},

		/**
		 * Show admin notice
		 *
		 * @param {string} message Notice message
		 * @param {string} type Notice type (success, error, warning, info)
		 */
		showNotice: function (message, type) {
			const $notice = $('<div class="notice notice-' + type + ' is-dismissible"><p>' + message + '</p></div>');
			$('.wrap h1').after($notice);

			// Auto-dismiss after 5 seconds
			setTimeout(function () {
				$notice.fadeOut(function () {
					$(this).remove();
				});
			}, 5000);
		},

		/**
		 * Refresh sessions table
		 */
		refreshSessions: function () {
			location.reload();
		}
	};

	/**
	 * Initialize on document ready
	 */
	$(document).ready(function () {
		UCPAdapterAdminClient.init();
	});

	/**
	 * Add AJAX handler for API key regeneration
	 */
	if (typeof wp !== 'undefined' && wp.ajax) {
		wp.ajax.post('ucp_adapter_regenerate_api_key', {
			nonce: ucpAdapterAdminData.nonce
		});
	}

})(jQuery);

/**
 * PHP AJAX handler (to be added to the main plugin file)
 * 
 * add_action('wp_ajax_ucp_adapter_regenerate_api_key', 'ucp_ajax_regenerate_api_key');
 * 
 * function ucp_ajax_regenerate_api_key() {
 *     check_ajax_referer('ucp_adapter_admin', 'nonce');
 *     
 *     if (!current_user_can('manage_options')) {
 *         wp_send_json_error(array('message' => 'Unauthorized'));
 *     }
 *     
 *     $new_key = UCP_Adapter_Security::generate_api_key();
 *     update_option('ucp_adapter_api_key', $new_key);
 *     
 *     wp_send_json_success(array('api_key' => $new_key));
 * }
 */
