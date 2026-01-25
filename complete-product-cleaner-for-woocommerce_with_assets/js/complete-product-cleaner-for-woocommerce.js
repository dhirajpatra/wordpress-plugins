jQuery(document).ready(function ($) {
    'use strict';

    const WCCleaner = {
        ajaxUrl: wcCleanerData.ajaxUrl,
        nonce: wcCleanerData.nonce,
        isProcessing: false,

        init: function () {
            this.bindEvents();
            this.initTabs();
            this.initProductCount();
        },

        bindEvents: function () {
            // Tab switching
            $('.wc-cleaner-tab').on('click', this.switchTab.bind(this));

            // Product deletion form
            $('#wc-cleaner-delete-form').on('submit', function (e) { e.preventDefault(); });

            // Image scan
            $('#wc-cleaner-scan-images').on('click', this.scanOrphanedImages.bind(this));

            // Image deletion
            $('#wc-cleaner-delete-images').on('click', this.deleteOrphanedImages.bind(this));

            // Confirmation for delete all
            $('.wc-cleaner-button-delete').on('click', this.handleDeleteSubmit.bind(this));

            // Option toggles
            $('.wc-cleaner-option input').on('change', this.updateDeleteSummary.bind(this));
        },

        initTabs: function () {
            const activeTab = window.location.hash.substring(1) || 'products';
            this.showTab(activeTab);
        },

        switchTab: function (e) {
            e.preventDefault();
            const tab = $(e.currentTarget).data('tab');
            this.showTab(tab);

            // Update URL hash
            window.location.hash = tab;
        },

        showTab: function (tab) {
            // Update active tab
            $('.wc-cleaner-tab').removeClass('nav-tab-active');
            $(`.wc-cleaner-tab[data-tab="${tab}"]`).addClass('nav-tab-active');

            // Show corresponding content
            $('.wc-cleaner-tab-content').removeClass('active');
            $(`#wc-cleaner-tab-${tab}`).addClass('active');
        },

        initProductCount: function () {
            $.ajax({
                url: this.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'wccc_get_product_count',
                    nonce: this.nonce
                },
                success: (response) => {
                    if (response.success) {
                        $('#wc-cleaner-product-count').text(response.data.count);
                    }
                }
            });
        },

        confirmDeleteAll: function (e) {
            const deleteAttached = $('#delete-attached-images').is(':checked');
            const deleteOrphaned = $('#delete-orphaned-images').is(':checked');
            const productCount = $('#wc-cleaner-product-count').text();

            let message = '🚨 CRITICAL WARNING 🚨\n\n';
            message += 'You are about to PERMANENTLY delete:\n\n';
            message += `• ${productCount} products and variations\n`;

            if (deleteAttached) {
                message += '• All images attached to products\n';
            }

            if (deleteOrphaned) {
                message += '• Any orphaned images found\n';
            }

            message += '\nThis action CANNOT be undone!\n\n';
            message += 'Type "DELETE" to confirm:';

            const userInput = prompt(message);

            if (userInput !== 'DELETE') {
                e.preventDefault();
                alert('Deletion cancelled. Nothing was deleted.');
                return false;
            }

            // Show processing indicator
            this.showProcessing(true);
            return true;
        },

        handleDeleteSubmit: function (e) {
            if (!this.confirmDeleteAll(e)) {
                return false;
            }

            // Add loading state to form
            const $button = $(e.currentTarget);
            const $form = $button.closest('form');

            $button.prop('disabled', true);
            $button.html('<span class="wc-cleaner-loader"></span> Processing...');

            // Show progress bar
            this.showProgressBar();

            // Process via AJAX for better feedback
            e.preventDefault();

            // Properly serialize form data including checkboxes
            const formData = new FormData($form[0]);
            formData.append('action', 'wccc_delete_products_ajax');
            formData.append('nonce', this.nonce);

            $.ajax({
                url: this.ajaxUrl,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: (response) => {
                    this.showProcessing(false);

                    if (response.success) {
                        this.showResults(response.data, 'delete');
                        this.initProductCount(); // Refresh count
                    } else {
                        this.showError(response.data.message || 'Deletion failed');
                    }
                },
                error: () => {
                    this.showProcessing(false);
                    this.showError('AJAX error occurred. Please try again or contact support.');
                },
                complete: () => {
                    $button.prop('disabled', false);
                    $button.text('🗑️ DELETE ALL PRODUCTS NOW');
                }
            });
        },

        scanOrphanedImages: function () {
            const $button = $('#wc-cleaner-scan-images');

            $button.prop('disabled', true);
            $button.html('<span class="wc-cleaner-loader"></span> Scanning...');

            $.ajax({
                url: this.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'wccc_scan_images',
                    nonce: this.nonce
                },
                success: (response) => {
                    if (response.success) {
                        this.displayScanResults(response.data);
                    } else {
                        this.showError('Scan failed: ' + (response.data.message || 'Unknown error'));
                    }
                },
                error: () => {
                    this.showError('AJAX error during scan. Please try again.');
                },
                complete: () => {
                    $button.prop('disabled', false);
                    $button.text('🔍 Scan for Orphaned Images');
                }
            });
        },

        deleteOrphanedImages: function () {
            if (!confirm('Delete all orphaned images? This cannot be undone.')) {
                return;
            }

            const $button = $('#wc-cleaner-delete-images');

            $button.prop('disabled', true);
            $button.html('<span class="wc-cleaner-loader"></span> Deleting...');

            $.ajax({
                url: this.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'wccc_delete_images',
                    nonce: this.nonce
                },
                success: (response) => {
                    if (response.success) {
                        this.showResults(response.data, 'images');
                        // Clear scan results display
                        $('#wc-cleaner-scan-results').html('');
                    } else {
                        this.showError('Deletion failed: ' + (response.data.message || 'Unknown error'));
                    }
                },
                error: () => {
                    this.showError('AJAX error during deletion. Please try again.');
                },
                complete: () => {
                    $button.prop('disabled', false);
                    $button.text('🗑️ Delete All Orphaned Images');
                }
            });
        },

        displayScanResults: function (data) {
            let html = `
                <div class="wc-cleaner-stats">
                    <h4>Scan Results:</h4>
                    <p>Found <strong>${data.count}</strong> orphaned images (${data.size})</p>
                </div>
            `;

            if (data.images && data.images.length > 0) {
                html += '<div class="wc-cleaner-image-grid">';

                data.images.slice(0, 20).forEach(image => {
                    const imageUrl = image.thumbnail || wcCleanerData.placeholder;
                    html += `
                        <div class="wc-cleaner-image-item">
                            <img src="${imageUrl}" alt="${image.title}" loading="lazy">
                            <div class="wc-cleaner-image-info">
                                ID: ${image.id}<br>
                                ${image.size}
                            </div>
                        </div>
                    `;
                });

                if (data.images.length > 20) {
                    html += `<div class="wc-cleaner-image-info">+ ${data.images.length - 20} more images</div>`;
                }

                html += '</div>';

                // Show delete button
                html += `
                    <div style="margin-top: 20px;">
                        <button type="button" id="wc-cleaner-delete-images" class="button button-primary wc-cleaner-button-delete">
                            🗑️ Delete All Orphaned Images (${data.count})
                        </button>
                    </div>
                `;
            } else {
                html += '<p>No orphaned images found. ✅</p>';
            }

            $('#wc-cleaner-scan-results').html(html);

            // Rebind delete button
            $('#wc-cleaner-delete-images').on('click', this.deleteOrphanedImages.bind(this));
        },

        showResults: function (data, type) {
            let html = '<div class="wc-cleaner-results">';

            if (type === 'delete') {
                html += '<h3>✅ Deletion Complete!</h3>';
                html += '<ul>';
                html += `<li><strong>Products Deleted:</strong> ${data.products_deleted}</li>`;
                html += `<li><strong>Variations Deleted:</strong> ${data.variations_deleted}</li>`;

                if (data.attached_images_deleted > 0) {
                    html += `<li><strong>Attached Images Deleted:</strong> ${data.attached_images_deleted}</li>`;
                }

                if (data.orphaned_images_deleted > 0) {
                    html += `<li><strong>Orphaned Images Deleted:</strong> ${data.orphaned_images_deleted}</li>`;
                }

                if (data.orders_cleaned > 0) {
                    html += `<li><strong>Order Items Cleaned:</strong> ${data.orders_cleaned}</li>`;
                }

                html += '</ul>';

                if (data.errors && data.errors.length > 0) {
                    html += '<div class="wc-cleaner-results error" style="margin-top: 15px;">';
                    html += '<h4>⚠️ Errors:</h4><ul>';
                    data.errors.forEach(error => {
                        html += `<li>${this.escapeHtml(error)}</li>`;
                    });
                    html += '</ul></div>';
                }

                html += '<p><em>Note: You may need to clear your cache plugins and regenerate thumbnails if needed.</em></p>';

            } else if (type === 'images') {
                html += '<h3>✅ Images Deleted!</h3>';
                html += `<p>Successfully deleted ${data.deleted_count} images and freed ${data.freed_space} of space.</p>`;
            }

            html += '</div>';

            // Insert at top of tab content
            const $tabContent = $('.wc-cleaner-tab-content.active');
            $tabContent.prepend(html);

            // Scroll to results
            $('html, body').animate({
                scrollTop: $tabContent.find('.wc-cleaner-results').first().offset().top - 100
            }, 500);
        },

        showError: function (message) {
            const html = `
                <div class="wc-cleaner-results error">
                    <h3>❌ Error</h3>
                    <p>${this.escapeHtml(message)}</p>
                </div>
            `;

            $('.wc-cleaner-tab-content.active').prepend(html);
        },

        showProcessing: function (show) {
            this.isProcessing = show;

            if (show) {
                $('body').append(`
                    <div id="wc-cleaner-overlay" style="
                        position: fixed;
                        top: 0;
                        left: 0;
                        right: 0;
                        bottom: 0;
                        background: rgba(255,255,255,0.8);
                        z-index: 999999;
                        display: flex;
                        align-items: center;
                        justify-content: center;
                    ">
                        <div style="
                            background: white;
                            padding: 40px;
                            border-radius: 8px;
                            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
                            text-align: center;
                        ">
                            <div class="wc-cleaner-loader" style="width: 40px; height: 40px; margin: 0 auto 20px;"></div>
                            <h3 style="margin: 0 0 10px 0; color: #1e293b;">Processing...</h3>
                            <p style="color: #64748b; margin: 0;">This may take a while for large stores. Please don't close this window.</p>
                        </div>
                    </div>
                `);
            } else {
                $('#wc-cleaner-overlay').remove();
            }
        },

        showProgressBar: function () {
            const html = `
                <div class="wc-cleaner-progress" style="margin: 20px 0;">
                    <div class="wc-cleaner-progress-bar" style="width: 0%;"></div>
                </div>
                <p style="text-align: center; color: #64748b;">Processing deletion...</p>
            `;

            $('#wc-cleaner-delete-form').append(html);

            // Animate progress bar (simulated)
            let width = 0;
            const interval = setInterval(() => {
                if (width >= 90) {
                    clearInterval(interval);
                } else {
                    width += 10;
                    $('.wc-cleaner-progress-bar').css('width', width + '%');
                }
            }, 500);
        },

        updateDeleteSummary: function () {
            const deleteAttached = $('#delete-attached-images').is(':checked');
            const deleteOrphaned = $('#delete-orphaned-images').is(':checked');

            let summary = 'This will delete all products and variations';

            if (deleteAttached && deleteOrphaned) {
                summary += ', along with all attached and orphaned images.';
            } else if (deleteAttached) {
                summary += ', along with attached product images.';
            } else if (deleteOrphaned) {
                summary += ', and scan for orphaned images to delete.';
            } else {
                summary += '. Product images will remain in the media library.';
            }

            $('#wc-cleaner-delete-summary').text(summary);
        },

        escapeHtml: function (text) {
            const map = {
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#039;'
            };
            return text.replace(/[&<>"']/g, m => map[m]);
        }
    };

    // Initialize
    WCCleaner.init();
});