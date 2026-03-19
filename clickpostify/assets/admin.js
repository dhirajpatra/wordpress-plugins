jQuery(document).ready(function($) {
	$('.scppsn-post-btn').on('click', function(e) {
		e.preventDefault();
		
		var $btn = $(this);
		var $status = $('.scppsn-status');
		var productId = $btn.data('product');
		var networks = [];
		
		$('input[name="scppsn_networks[]"]:checked').each(function() {
			networks.push($(this).val());
		});
		
		if (networks.length === 0) {
			$status.css({background: '#f8d7da', color: '#721c24', padding: '8px', border: '1px solid #f5c6cb'})
				.text('Please select at least one network').show();
			return;
		}
		
		$btn.prop('disabled', true).text('Posting...');
		$status.css({background: '#d1ecf1', color: '#0c5460', padding: '8px', border: '1px solid #bee5eb'})
			.text('Posting to social networks...').show();
		
		$.ajax({
			url: scppsnData.ajax_url,
			type: 'POST',
			data: {
				action: 'scppsn_post',
				nonce: scppsnData.nonce,
				product_id: productId,
				networks: networks
			},
			success: function(response) {
				$btn.prop('disabled', false).text('Post Now');
				
				if (response.success) {
					$status.css({background: '#d4edda', color: '#155724', border: '1px solid #c3e6cb'})
						.text(response.data.message).show();
				} else {
					$status.css({background: '#f8d7da', color: '#721c24', border: '1px solid #f5c6cb'})
						.text(response.data.message).show();
				}
			},
			error: function() {
				$btn.prop('disabled', false).text('Post Now');
				$status.css({background: '#f8d7da', color: '#721c24', border: '1px solid #f5c6cb'})
					.text('Connection error').show();
			}
		});
	});
});
