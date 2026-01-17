=== Complete Product Cleaner for WooCommerce ===
Contributors: dhirajpatra
Username: dhirajpatra
Display Name: Dhiraj Patra
Tags: woocommerce, cleanup, products, images, delete
Requires at least: 5.0
Tested up to: 6.9
Requires PHP: 8.0
Stable tag: 1.0.4
Requires Plugins: woocommerce
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Bulk delete all WooCommerce products and images with safety features.

== Description ==

WooCommerce Complete Product Cleaner is a powerful yet safe tool for store administrators who need to delete all WooCommerce products and related images. Perfect for resetting development sites, clearing demo data, or seasonal inventory changes.

**Features:**

*   **One-Click Product Deletion:** Delete all products and variations in a single click
*   **Smart Image Cleanup:** Options to delete attached images or scan for orphaned images
*   **Safe Operations:** Multiple confirmation prompts and nonce verification
*   **Progress Feedback:** Real-time feedback on what's being deleted
*   **Database Cleanup:** Removes orphaned metadata and term relationships
*   **Admin Only:** Restricted to users with manage_options capability
*   **Responsive Interface:** Clean, tabbed interface that works on all devices

== Installation ==

1.  Upload the plugin files to the `/wp-content/plugins/woocommerce-complete-product-cleaner` directory, or install the plugin through the WordPress plugins screen directly.
2.  Activate the plugin through the 'Plugins' screen in WordPress.
3.  Navigate to **WooCommerce → Complete Product Cleaner** in the admin menu.
4.  Always backup your database before using any deletion features.
5.  Select your options and proceed with caution.

== Frequently Asked Questions ==

= Is this plugin safe to use? =
The plugin includes multiple safety features including confirmation dialogs, nonce verification, and admin-only access. However, **ALWAYS BACKUP YOUR DATABASE** before using any deletion features.

= What does it delete exactly? =
- All products (including variations)
- Product metadata
- Optionally: Attached product images
- Optionally: Orphaned images (images not used elsewhere)
- WooCommerce transients and lookup tables

= Does it delete orders or customers? =
No, this plugin only deletes products and related data. Orders, customers, and settings remain intact.

= Can I recover deleted items? =
No, all deletions are permanent. That's why backups are essential.

= Does it work with large stores? =
Yes, but performance depends on server resources. For very large stores (10,000+ products), consider running during low-traffic periods.

== Changelog ==

= 1.0.0 =
*   Initial release with complete product and image cleanup
*   Tabbed interface for better organization
*   Smart orphaned image detection
*   Enhanced safety features

== Screenshots ==

1.  **Main Interface:** Tabbed interface showing product deletion and image cleanup options
2.  **Deletion Options:** Configuration panel with image deletion checkboxes
3.  **Results Display:** Success message showing deletion statistics
4.  **Orphaned Image Scanner:** Results of scanning for unused images
5.  **Mobile View:** Responsive design on mobile devices

== Upgrade Notice ==

This is the initial release. Always test on a staging site before using on production.