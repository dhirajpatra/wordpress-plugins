/**
 * UCP Adapter Frontend JavaScript
 *
 * @package UCP_Adapter
 */

(function($) {
	'use strict';

	/**
	 * UCP Adapter Client
	 */
	const UCPAdapter = {
		
		/**
		 * Initialize
		 */
		init: function() {
			this.bindEvents();
		},

		/**
		 * Bind events
		 */
		bindEvents: function() {
			// Add your custom event bindings here
			$(document).on('click', '.ucp-trigger-session', this.triggerSession.bind(this));
		},

		/**
		 * Create a new session
		 *
		 * @param {Object} data Session data
		 * @return {Promise}
		 */
		createSession: function(data) {
			return $.ajax({
				url: ucpAdapter.restUrl + '/session',
				method: 'POST',
				contentType: 'application/json',
				beforeSend: function(xhr) {
					xhr.setRequestHeader('X-WP-Nonce', ucpAdapter.nonce);
				},
				data: JSON.stringify(data)
			});
		},

		/**
		 * Update session
		 *
		 * @param {string} sessionId Session ID
		 * @param {string} action Action to perform
		 * @param {Object} data Update data
		 * @return {Promise}
		 */
		updateSession: function(sessionId, action, data) {
			return $.ajax({
				url: ucpAdapter.restUrl + '/update/' + sessionId,
				method: 'PUT',
				contentType: 'application/json',
				beforeSend: function(xhr) {
					xhr.setRequestHeader('X-WP-Nonce', ucpAdapter.nonce);
				},
				data: JSON.stringify({
					action: action,
					data: data
				})
			});
		},

		/**
		 * Complete session
		 *
		 * @param {string} sessionId Session ID
		 * @param {string} status Completion status
		 * @param {Object} metadata Additional metadata
		 * @return {Promise}
		 */
		completeSession: function(sessionId, status, metadata) {
			return $.ajax({
				url: ucpAdapter.restUrl + '/complete/' + sessionId,
				method: 'POST',
				contentType: 'application/json',
				beforeSend: function(xhr) {
					xhr.setRequestHeader('X-WP-Nonce', ucpAdapter.nonce);
				},
				data: JSON.stringify({
					status: status,
					metadata: metadata
				})
			});
		},

		/**
		 * Get session status
		 *
		 * @param {string} sessionId Session ID
		 * @return {Promise}
		 */
		getSessionStatus: function(sessionId) {
			return $.ajax({
				url: ucpAdapter.restUrl + '/status/' + sessionId,
				method: 'GET',
				beforeSend: function(xhr) {
					xhr.setRequestHeader('X-WP-Nonce', ucpAdapter.nonce);
				}
			});
		},

		/**
		 * Show loading state
		 *
		 * @param {jQuery} $element Element to show loading on
		 */
		showLoading: function($element) {
			$element.addClass('ucp-loading');
		},

		/**
		 * Hide loading state
		 *
		 * @param {jQuery} $element Element to hide loading from
		 */
		hideLoading: function($element) {
			$element.removeClass('ucp-loading');
		},

		/**
		 * Show error message
		 *
		 * @param {string} message Error message
		 * @param {jQuery} $container Container element
		 */
		showError: function(message, $container) {
			const $error = $('<div class="ucp-error"></div>').text(message);
			$container.prepend($error);
			
			setTimeout(function() {
				$error.fadeOut(function() {
					$(this).remove();
				});
			}, 5000);
		},

		/**
		 * Show success message
		 *
		 * @param {string} message Success message
		 * @param {jQuery} $container Container element
		 */
		showSuccess: function(message, $container) {
			const $success = $('<div class="ucp-success"></div>').text(message);
			$container.prepend($success);
			
			setTimeout(function() {
				$success.fadeOut(function() {
					$(this).remove();
				});
			}, 5000);
		},

		/**
		 * Example: Trigger session creation
		 *
		 * @param {Event} e Event object
		 */
		triggerSession: function(e) {
			e.preventDefault();
			
			const $button = $(e.currentTarget);
			const $container = $button.closest('.ucp-adapter-container');
			
			this.showLoading($button);
			
			this.createSession({
				platform: 'woocommerce',
				user_data: {
					// Add your user data here
				}
			})
			.done((response) => {
				this.hideLoading($button);
				this.showSuccess('Session created: ' + response.session_id, $container);
				console.log('Session created:', response);
			})
			.fail((xhr) => {
				this.hideLoading($button);
				const message = xhr.responseJSON?.message || 'Failed to create session';
				this.showError(message, $container);
				console.error('Session creation failed:', xhr);
			});
		}
	};

	/**
	 * Initialize on document ready
	 */
	$(document).ready(function() {
		UCPAdapter.init();
	});

	/**
	 * Expose to global scope
	 */
	window.UCPAdapter = UCPAdapter;

})(jQuery);
