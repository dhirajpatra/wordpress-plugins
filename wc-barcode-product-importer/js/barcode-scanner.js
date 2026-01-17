(function ($) {
    'use strict';

    let html5QrCode;
    let isScanning = false;
    const COOLDOWN_TIME = 2000; // 2 seconds between scans
    const scannedProducts = [];
    let lastScannedCode = null;
    let lastScannedTime = 0;

    /**
     * Initialize on page load
     */
    $(document).ready(function () {
        checkBrowserSupport();
        setupEventHandlers();

        // Initialize the scanner instance
        // "wc-bpi-video" is the ID of the container where we want the scanner
        // But html5-qrcode expects a container ID, not a video element ID if using the easy mode.
        // However, we want to use the video element we already have or let the library handle it.
        // The library works best given a container. Let's adjust the HTML structure slightly via JS if needed,
        // or just use the existing "wc-bpi-scanner-wrapper" as the container?
        // Actually, the library replaces the content of the container. 
        // Our PHP outputs: <div class="wc-bpi-scanner-wrapper"><video id="wc-bpi-video"...>...</div>
        // We will use "wc-bpi-scanner-wrapper" as the container, but we need to empty it first or let the lib handle it.
        // Better approach: Use the Html5Qrcode class (not scanner) and attach to the video ID.
        // But Html5QrcodeScanner is easier for UI.
        // Let's use Html5Qrcode class to control the video element "wc-bpi-video".

        // Note: html5-qrcode attaches to an element ID.
    });

    /**
     * Check browser support
     */
    function checkBrowserSupport() {
        if (location.protocol !== 'https:' && location.hostname !== 'localhost' && location.hostname !== '127.0.0.1') {
            updateStatus('⚠️ HTTPS is required for camera access.', 'warning');
        }
    }

    /**
     * Setup event handlers
     */
    function setupEventHandlers() {
        $('#wc-bpi-start').on('click', handleStartClick);
        $('#wc-bpi-stop').on('click', handleStopClick);
        $('#wc-bpi-clear-session').on('click', handleClearSession);
    }

    /**
     * Handle start button click
     */
    function handleStartClick(e) {
        e.preventDefault();
        $('.wc-bpi-empty-state').hide();
        startCamera();
    }

    /**
     * Handle stop button click
     */
    async function handleStopClick(e) {
        e.preventDefault();
        await stopCamera();
        updateStatus('Scanner stopped', 'info');
    }

    /**
     * Start camera using Html5Qrcode
     */
    async function startCamera() {
        // We need to use a container ID. The existing HTML has a video element inside a wrapper.
        // html5-qrcode works best when it controls the video element creation or usage.
        // Let's use the 'wc-bpi-scanner-wrapper' as the container.

        const containerId = 'wc-bpi-reader-container';

        // If the container doesn't exist yet (we might have only the wrapper), let's create a dedicated one inside the wrapper
        // or ensure our wrapper is ready.
        // The PHP template has: 
        // <div class="wc-bpi-scanner-wrapper">
        //    <video id="wc-bpi-video" ...></video>
        //    <div class="wc-bpi-scanner-overlay">...</div>
        // </div>
        //
        // We can hide the overlay or overlay it on top.
        // The html5-qrcode library will insert its own video element into the container.
        // So let's empty the wrapper first or hide the default video.

        $('#wc-bpi-video').hide(); // Hide the default empty video

        // Create a new container div for the library if not exists
        if ($('#' + containerId).length === 0) {
            $('.wc-bpi-scanner-wrapper').prepend('<div id="' + containerId + '" style="width:100%; height:100%;"></div>');
        }

        try {
            updateStatus('📸 Starting camera...', 'info');

            // Allow Permission
            const cameras = await Html5Qrcode.getCameras();
            if (cameras && cameras.length) {
                // Use back camera by default
                let cameraId = cameras[0].id;

                // Try to find back camera
                const backCamera = cameras.find(cam => cam.label.toLowerCase().includes('back') || cam.label.toLowerCase().includes('environment'));
                if (backCamera) {
                    cameraId = backCamera.id;
                }

                html5QrCode = new Html5Qrcode(containerId);

                updateUI('scanning');

                const config = {
                    fps: 10,
                    qrbox: { width: 250, height: 250 },
                    aspectRatio: 1.0
                };

                await html5QrCode.start(
                    cameraId,
                    config,
                    (decodedText, decodedResult) => {
                        // Success callback
                        handleScanSuccess(decodedText);
                    },
                    (errorMessage) => {
                        // Error callback (scanning failure, not camera failure)
                        // Ignore frequent errors
                    }
                );

                updateStatus(WCBPI.strings.cameraStarted, 'success');
                isScanning = true;

            } else {
                updateStatus('❌ No cameras found.', 'error');
            }
        } catch (err) {
            console.error('Camera start error:', err);
            let msg = '❌ Failed to start camera.';
            if (err.name === 'NotAllowedError') {
                msg += ' Permission denied.';
            }
            updateStatus(msg + ' ' + err, 'error');
            updateUI('stopped');
        }
    }

    /**
     * Handle successful scan
     */
    async function handleScanSuccess(decodedText) {
        if (!isScanning) return;

        const now = Date.now();
        // Prevent duplicate scans within cooldown
        if (decodedText === lastScannedCode && (now - lastScannedTime) < COOLDOWN_TIME) {
            return;
        }

        lastScannedCode = decodedText;
        lastScannedTime = now;

        // Play beep
        playBeep();

        // Process
        await processBarcode(decodedText);
    }

    /**
     * Stop camera
     */
    async function stopCamera() {
        if (html5QrCode && isScanning) {
            try {
                await html5QrCode.stop();
                html5QrCode.clear();
            } catch (err) {
                console.log('Failed to stop/clear', err);
            }
        }
        isScanning = false;
        updateUI('stopped');
        $('#wc-bpi-video').show(); // Show default video placeholder if needed, or leave hidden
    }

    /**
     * Handle clear session
     */
    async function handleClearSession(e) {
        e.preventDefault();
        if (!confirm('Clear all session statistics?')) return;

        try {
            const response = await fetch(WCBPI.ajaxUrl, {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: new URLSearchParams({
                    action: 'wc_bpi_clear_session',
                    nonce: WCBPI.nonce
                })
            });

            const result = await response.json();
            if (result.success) {
                updateSessionStats({ total_scans: 0, successful: 0, duplicates: 0, errors: 0 });
                $('#wc-bpi-scan-list').html('<p class="wc-bpi-empty-state">No products scanned yet. Start scanning to see results here.</p>');
                scannedProducts.length = 0;
            }
        } catch (err) {
            console.error('Clear session error:', err);
        }
    }

    /**
     * Play beep sound
     */
    function playBeep() {
        const audioContext = new (window.AudioContext || window.webkitAudioContext)();
        const oscillator = audioContext.createOscillator();
        const gainNode = audioContext.createGain();
        oscillator.connect(gainNode);
        gainNode.connect(audioContext.destination);
        oscillator.frequency.value = 800;
        oscillator.type = 'sine';
        gainNode.gain.value = 0.3;
        oscillator.start(audioContext.currentTime);
        oscillator.stop(audioContext.currentTime + 0.1);
    }

    /**
     * Process scanned barcode
     */
    async function processBarcode(barcode) {
        try {
            updateStatus(`${WCBPI.strings.detected}: ${barcode} - ${WCBPI.strings.fetching}`, 'info');

            const fetchResponse = await fetch(WCBPI.ajaxUrl, {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: new URLSearchParams({
                    action: 'wc_bpi_fetch_product_data',
                    nonce: WCBPI.nonce,
                    barcode: barcode
                })
            });

            const fetchResult = await fetchResponse.json();

            if (!fetchResult.success && fetchResult.data?.is_duplicate) {
                addToScanList({
                    barcode: barcode,
                    name: 'Duplicate Product',
                    status: 'duplicate',
                    product_id: fetchResult.data.product_id,
                    timestamp: Date.now()
                });
                updateStatus(`${WCBPI.strings.duplicate}: ${barcode}`, 'warning');
                await updateSessionStatsFromServer();
                return;
            }

            if (!fetchResult.success) {
                throw new Error(fetchResult.data?.message || 'Failed to fetch product data');
            }

            const productPayload = fetchResult.data;
            updateStatus(WCBPI.strings.creating, 'info');

            const createResponse = await fetch(WCBPI.ajaxUrl, {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: new URLSearchParams({
                    action: 'wc_bpi_create_product',
                    nonce: WCBPI.nonce,
                    product: JSON.stringify(productPayload)
                })
            });

            const createResult = await createResponse.json();

            if (!createResult.success) {
                if (createResult.data?.is_duplicate) {
                    addToScanList({
                        barcode: barcode,
                        name: productPayload.name,
                        status: 'duplicate',
                        product_id: createResult.data.product_id,
                        timestamp: Date.now()
                    });
                    updateStatus(`${WCBPI.strings.duplicate}: ${barcode}`, 'warning');
                } else {
                    throw new Error(createResult.data?.message || 'Failed to create product');
                }
                await updateSessionStatsFromServer();
                return;
            }

            const productId = createResult.data.product_id;
            addToScanList({
                barcode: barcode,
                name: productPayload.name,
                status: 'success',
                product_id: productId,
                image_url: productPayload.image_url,
                brand: productPayload.brand,
                edit_url: createResult.data.edit_url,
                timestamp: Date.now()
            });

            updateStatus(`${WCBPI.strings.success} ${productPayload.name}`, 'success');
            if (createResult.data.stats) {
                updateSessionStats(createResult.data.stats);
            }

        } catch (err) {
            console.error('Process error:', err);
            addToScanList({
                barcode: barcode,
                name: 'Error',
                status: 'error',
                error: err.message,
                timestamp: Date.now()
            });
            updateStatus(WCBPI.strings.error + ' ' + err.message, 'error');
            await updateSessionStatsFromServer();
        }
    }

    /**
     * Add product to scan list
     */
    function addToScanList(product) {
        scannedProducts.unshift(product);
        const listEl = $('#wc-bpi-scan-list');
        listEl.find('.wc-bpi-empty-state').remove();

        let statusIcon = '';
        let statusClass = '';

        switch (product.status) {
            case 'success': statusIcon = '✓'; statusClass = 'success'; break;
            case 'duplicate': statusIcon = '⚠'; statusClass = 'warning'; break;
            case 'error': statusIcon = '✗'; statusClass = 'error'; break;
        }

        // Use a generic placeholder if no image
        let imgHtml = '<div class="wc-bpi-scan-no-image">📦</div>';
        if (product.image_url) {
            imgHtml = `<img src="${escapeHtml(product.image_url)}" alt="${escapeHtml(product.name)}">`;
        }

        const itemHtml = `
            <div class="wc-bpi-scan-item ${statusClass}">
                <div class="wc-bpi-scan-icon">${statusIcon}</div>
                ${imgHtml}
                <div class="wc-bpi-scan-details">
                    <strong>${escapeHtml(product.name)}</strong>
                    <span class="wc-bpi-scan-barcode">Barcode: ${escapeHtml(product.barcode)}</span>
                    ${product.brand ? `<span class="wc-bpi-scan-brand">Brand: ${escapeHtml(product.brand)}</span>` : ''}
                    ${product.error ? `<span class="wc-bpi-scan-error">${escapeHtml(product.error)}</span>` : ''}
                </div>
                ${product.edit_url ? `<a href="${escapeHtml(product.edit_url)}" target="_blank" class="button button-small">Edit</a>` : ''}
            </div>
        `;

        listEl.prepend(itemHtml);
        listEl.find('.wc-bpi-scan-item').slice(20).remove();
    }

    /**
     * Update stats display
     */
    function updateSessionStats(stats) {
        $('#wc-bpi-scan-count').text(stats.total_scans);
        $('#wc-bpi-success-count').text(stats.successful);
        $('#wc-bpi-duplicate-count').text(stats.duplicates);
        $('#wc-bpi-error-count').text(stats.errors);
    }

    /**
     * Update stats from server
     */
    async function updateSessionStatsFromServer() {
        try {
            const response = await fetch(WCBPI.ajaxUrl, {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: new URLSearchParams({
                    action: 'wc_bpi_get_session_stats',
                    nonce: WCBPI.nonce
                })
            });
            const result = await response.json();
            if (result.success) {
                updateSessionStats(result.data);
            }
        } catch (err) {
            console.error('Stats update error:', err);
        }
    }

    /**
     * Update status message
     */
    function updateStatus(message, type = 'info') {
        const statusEl = $('#wc-bpi-status');
        statusEl.removeClass('info error success warning').addClass(type).html(message).show();
    }

    /**
     * Update UI
     */
    function updateUI(state) {
        if (state === 'scanning') {
            $('#wc-bpi-start').hide();
            $('#wc-bpi-stop').show();
            $('.wc-bpi-scanner-wrapper').addClass('active');
            $('.wc-bpi-scanner-overlay').show(); // Ensure overlay is visible if you want it
        } else {
            $('#wc-bpi-start').show();
            $('#wc-bpi-stop').hide();
            $('.wc-bpi-scanner-wrapper').removeClass('active');
        }
    }

    /**
     * Escape HTML
     */
    function escapeHtml(text) {
        if (!text) return '';
        const map = { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' };
        return String(text).replace(/[&<>"']/g, m => map[m]);
    }

})(jQuery);
