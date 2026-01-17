=== WC Barcode Product Importer ===
Contributors: dhirajpatra
Tags: barcode,woocommerce,import,products,scanner
Requires at least: 5.0
Tested up to: 6.9
Requires PHP: 8.0
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

== Description ==

WC Barcode Product Importer allows you to add products directly using a barcode scanner. This plugin integrates with WooCommerce to open your device's camera, scan a barcode, fetch product details from an external API, and automatically create a new product (including an image) via the WooCommerce REST API.

**Features:**

*   **Barcode Scanner:** Opens your device's camera to scan a barcode.
*   **Product Details:** Fetches product details from an external API based on the scanned barcode.
*   **WooCommerce Integration:** Creates a new product in WooCommerce with the fetched details and image.

== External Services ==

This plugin utilizes the following third-party services to provide barcode product import functionality. Data (user messages and site context) is sent to these services only when you configure an API key for them and users interact with the barcode scanner.

== Installation ==

1.  Upload the plugin files to the `/wp-content/plugins/wc-barcode-product-importer` directory, or install the plugin through the WordPress plugins screen directly.
2.  Activate the plugin through the 'Plugins' screen in WordPress.
3.  Navigate to **WC Barcode Product Importer** in the admin menu.

== Frequently Asked Questions ==

= Does it work with WooCommerce? =
Yes, it is designed to read your WooCommerce products and help customers find what they are looking for.

== Changelog ==

= 1.0.0 =
*   Initial release.

== Screenshots ==

1.  **Plugin Settings:** Configure barcode scanner widget.    
2.  **Frontend Mobile Camera Interface:** View of the barcode scanner widget on the site.    
3.  **Mobile View:** Fully responsive barcode scanner widget on a mobile device.
