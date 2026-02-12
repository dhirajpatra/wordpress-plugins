# Single Click Posting Products to Social Networks

> Post WooCommerce products to Facebook, Pinterest, X (Twitter), LinkedIn, YouTube, and Instagram with a single click.

[![WordPress](https://img.shields.io/badge/WordPress-6.0%2B-blue.svg)](https://wordpress.org)
[![WooCommerce](https://img.shields.io/badge/WooCommerce-Required-violet.svg)](https://woocommerce.com)
[![License: GPL v3](https://img.shields.io/badge/License-GPLv3-green.svg)](https://www.gnu.org/licenses/gpl-3.0.html)

**Single Click Posting Products to Social Networks** allows you to effortlessly share your WooCommerce products across multiple social media platforms. Simplify your social media marketing by posting product details, images, and links directly from your product pages.

## Features

- **Multi-Network Support**: Post to 6 major social networks simultaneously.
- **One-Click Action**: Simple "Post Now" button on the product edit page.
    - *Note: This is a manual action. Use the checkboxes in the "Post to Social Networks" box to select specific networks, then click "Post Now".*
- **Activity Logging**: Keep track of which products have been posted and their status.
- **Simple Configuration**: Easy-to-use settings panel for API credentials.

### Supported Networks

1. **Facebook Pages**
2. **Pinterest Boards**
3. **X (Twitter)**
4. **LinkedIn**
5. **YouTube**
6. **Instagram Business Accounts**

## Requirements

- WordPress 6.0 or higher
- PHP 8.0 or higher
- WooCommerce installed and activated

## Installation

1. Upload the plugin files to the `/wp-content/plugins/single-click-posting-products-to-social-networks/` directory, or install the plugin through the WordPress plugins screen directly.
2. Activate the plugin through the **Plugins** screen in WordPress.
3. Navigate to **Social Networks > Settings** to configure your API credentials.

## Configuration

You will need to create applications/developer accounts for each social network you wish to use to obtain the necessary API keys and tokens.

1. Go to **Social Networks > Settings**.
2. Click on the tab for the network you want to configure (e.g., Facebook).
3. Check the **Enable** box.
4. Enter your credentials:

| Network | Field | Description |
|---------|-------|-------------|
| **Facebook** | Page Access Token | Long-lived token with `pages_manage_posts`. |
| | Page ID | Numeric ID of your Facebook Page. |
| **Pinterest** | Access Token | Token with `pins:write` scope. |
| | Board ID | Numeric ID of target board. |
| **X (Twitter)** | Bearer Token | App-only token with read/write access. |
| **LinkedIn** | Access Token | OAuth 2.0 token with `w_member_social`. |
| **Instagram** | Access Token | Graph API token with `instagram_content_publish`. |
| | Business Account ID | Linked Instagram Business ID. |

5. Save changes.

## Usage

1. Go to **Products** and edit the product you want to share.
2. Locate the **Post to Social Networks** meta box in the sidebar.
3. Select the networks you want to post to (networks must be enabled in settings).
4. Click **Post Now**.
5. A status message will appear confirming specific network success or failure.

## Frequently Asked Questions

**Does this require WooCommerce?**
Yes, WooCommerce must be installed and active for this plugin to function.

**Do I need API credentials?**
Yes, you are responsible for creating apps and generating access tokens for each social network you wish to post to.

## Changelog

### 1.0.0
- Initial release.

## License

This plugin is released under the [GPLv3 License](https://www.gnu.org/licenses/gpl-3.0.html).