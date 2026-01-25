jQuery(document).ready(function ($) {
    const data = wcCleanerData;

    // Tab Switching
    $('.wc-cleaner-tab').click(function () {
        const tab = $(this).data('tab');
        $('.wc-cleaner-tab').removeClass('nav-tab-active');
        $(this).addClass('nav-tab-active');

        $('.wc-cleaner-tab-content').removeClass('active');
        $('#wc-cleaner-tab-' + tab).addClass('active');
    });

    // Populate Counts
    function loadCounts() {
        $.post(data.ajaxUrl, {
            action: 'wccc_get_counts',
            nonce: data.nonce
        }, function (response) {
            if (response.success) {
                $.each(response.data, function (key, val) {
                    $(`[data-module="${key}"] .wccc-count-val`).text(val);
                });
            }
        });
    }
    loadCounts();

    // Start Cleanup
    $('.wc-cleaner-button-delete').click(function () {
        if (!confirm(data.confirmMessage)) {
            return;
        }

        const module = $(this).data('module');
        const container = $(this).closest('.wc-cleaner-card');

        // UI updates
        $(this).hide();
        container.find('.wc-cleaner-button-stop').show();
        container.find('.wccc-progress-bar').show();

        // Collect options
        const options = {};
        container.find('input[type="checkbox"]:checked').each(function () {
            options[$(this).attr('name')] = 1;
        });

        // Start AJAX
        $.post(data.ajaxUrl, {
            action: 'wccc_start_cleanup',
            nonce: data.nonce,
            type: module,
            options: options
        }, function (response) {
            if (response.success) {
                // Poll status
                pollStatus(module);
            } else {
                alert('Error: ' + response.data);
            }
        });
    });

    // Stop Cleanup
    $('.wc-cleaner-button-stop').click(function () {
        // Just reload page for now to stop visual polling, 
        // real stop logic would need an AJAX call to cancel Action Scheduler jobs.
        location.reload();
    });

    // Polling
    function pollStatus(module) {
        const container = $(`#wc-cleaner-tab-${module}`);

        const interval = setInterval(function () {
            $.post(data.ajaxUrl, {
                action: 'wccc_get_status',
                nonce: data.nonce
            }, function (response) {
                if (response.success) {
                    $('#wccc-global-status .status-text').text(response.data.status);

                    if (response.data.status !== 'running') {
                        clearInterval(interval);
                        alert('Cleanup completed!');
                        location.reload();
                    } else {
                        // Fake progress animation since we don't have total vs current in status yet
                        const bar = container.find('.wccc-progress-inner');
                        let width = parseFloat(bar.get(0).style.width) || 0;
                        if (width < 90) width += 1;
                        bar.css('width', width + '%');
                    }
                }
            });
        }, 2000);
    }
});