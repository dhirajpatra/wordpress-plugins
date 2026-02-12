=== Single Click Posting Products to Social Networks ===
Contributors: dhirajpatra
Tags: woocommerce, social media, facebook, pinterest, instagram
Requires at least: 6.0
Tested up to: 6.9
Requires PHP: 8.0
Stable tag: 1.0.0
License: GPLv3 or later
License URI: https://www.gnu.org/licenses/gpl-3.0.html
Author: Dhiraj Patra
Author URI: https://github.com/dhirajpatra

Post WooCommerce products to Facebook, Pinterest, X, LinkedIn, YouTube, and Instagram with a single click.

== Description ==

Single Click Posting Products to Social Networks allows you to effortlessly share your WooCommerce products across multiple social media platforms with just one click.

Features:
* Post to 6 major social networks
* Simple configuration for each network
* One-click posting from product pages (Manual action required)
* Activity logging

> **Note**: This plugin does **not** automatically post products. On the product edit page, you will see a box with checkboxes for each enabled network. Select the networks you want to post to and click "Post Now".

Supported Networks:
* Facebook Pages
* Pinterest Boards
* X (Twitter)
* LinkedIn
* YouTube
* Instagram Business Accounts

== Installation ==

1. Upload plugin files to `/wp-content/plugins/`
2. Activate the plugin
3. Go to Social Networks > Settings
4. Configure your API credentials
5. Start posting products!

== Frequently Asked Questions ==

= Does this require WooCommerce? =
Yes, WooCommerce must be installed and activated.

= Do I need API credentials? =
Yes, you need to create apps and obtain credentials for each social network.

== Configuration Details ==

* **Facebook**:
    * Page Access Token: Long-lived token with `pages_manage_posts` permission.
    * Page ID: The numeric ID of your Facebook Page.

* **Pinterest**:
    * Access Token: App token with `pins:read` and `pins:write` scopes.
    * Board ID: The numeric ID of the board to pin to.

* **X (Twitter)**:
    * Bearer Token: App-only token from Developer Portal.

* **LinkedIn**:
    * Access Token: OAuth 2.0 token with `w_member_social` scope.

* **Instagram**:
    * Access Token: Graph API token with `instagram_content_publish`.
    * Business Account ID: Linked Instagram Business ID.

== Changelog ==

= 1.0.0 =
* Initial release
