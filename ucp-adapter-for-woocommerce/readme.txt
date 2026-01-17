=== UCP Adapter For WooCommerce ===
Contributors: dhirajpatra
Tags: api, rest, commerce, ucp, integration
Requires at least: 6.9
Tested up to: 6.9
Requires PHP: 8.0
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Universal Commerce Platform REST API adapter providing Session, Update, and Complete endpoints for seamless e-commerce integration.

== Description ==

UCP Adapter For WooCommerce is a powerful WordPress plugin that provides REST API endpoints for Universal Commerce Platform (UCP) integration. It enables seamless communication between your WordPress site and external commerce platforms through secure, standardized API endpoints.

= Key Features =

* **Session Management**: Create and manage user sessions with automatic expiration
* **REST API Endpoints**: Three core endpoints (Session, Update, Complete) for UCP communication
* **Security First**: API key authentication, rate limiting, and IP whitelisting
* **Developer Friendly**: Clean, extensible code following WordPress coding standards
* **Extensible**: Hooks and filters throughout for custom implementations
* **Admin Interface**: Easy-to-use settings and session monitoring dashboard
* **Database Storage**: Efficient session storage with automatic cleanup

= REST API Endpoints =

1. **POST /wp-json/ucp/v1/session** - Create a new session
2. **PUT /wp-json/ucp/v1/update/{session_id}** - Update session data
3. **POST /wp-json/ucp/v1/complete/{session_id}** - Complete a session
4. **GET /wp-json/ucp/v1/status/{session_id}** - Check session status

= Use Cases =

* E-commerce platform integration
* Third-party checkout systems
* Payment gateway connections
* Order management systems
* Multi-platform commerce solutions

= Developer Resources =

Build custom UCP adapters for platforms like:
* WooCommerce
* Easy Digital Downloads
* MemberPress
* Custom e-commerce solutions

== Installation ==

= Automatic Installation =

1. Log in to your WordPress admin panel
2. Navigate to Plugins > Add New
3. Search for "UCP Adapter Core"
4. Click "Install Now" and then "Activate"

= Manual Installation =

1. Download the plugin zip file
2. Log in to your WordPress admin panel
3. Navigate to Plugins > Add New > Upload Plugin
4. Choose the downloaded zip file and click "Install Now"
5. Activate the plugin through the 'Plugins' menu

= Configuration =

1. Navigate to UCP Adapter in the admin menu
2. Copy your API key from the Settings page
3. Configure session timeout and rate limiting as needed
4. Use the API key in your UCP requests

== Frequently Asked Questions ==

= How do I get an API key? =

An API key is automatically generated when you activate the plugin. You can find it in the UCP Adapter > Settings page. You can also regenerate it at any time.

= How do I authenticate API requests? =

Include your API key in the request header:
`X-UCP-API-Key: your-api-key-here`

Or as a query parameter:
`?api_key=your-api-key-here`

= What happens to expired sessions? =

Expired sessions are automatically cleaned up on an hourly basis. Completed sessions are kept for 7 days before being removed.

= Can I customize the session timeout? =

Yes, you can configure the session timeout in the Settings page. The default is 3600 seconds (1 hour).

= Is this plugin compatible with WooCommerce? =

Yes! While this is the core adapter, it's designed to work with platform-specific extensions. You can build WooCommerce-specific functionality on top of this foundation.

= How do I extend the plugin? =

The plugin provides numerous hooks and filters:

* `ucp_adapter_init` - Runs after plugin initialization
* `ucp_session_created` - Fires when a session is created
* `ucp_session_updated` - Fires when a session is updated
* `ucp_session_completed` - Fires when a session is completed
* `ucp_before_session_complete` - Filter data before completion
* `ucp_update_action_{action}` - Handle custom update actions

= Where can I get support? =

For support questions, please use the WordPress.org support forums. For bugs and feature requests, visit our GitHub repository.

== Screenshots ==

1. Settings page with API key management
2. Active sessions dashboard
3. REST API endpoint documentation
4. Session status monitoring

== Changelog ==

= 1.0.0 =
* Initial release
* Session management system
* Three core REST API endpoints (Session, Update, Complete)
* API key authentication
* Rate limiting functionality
* IP whitelisting support
* Admin dashboard with settings
* Session monitoring interface
* Automatic session cleanup
* Comprehensive documentation

== Upgrade Notice ==

= 1.0.0 =
Initial release of UCP Adapter Core.

== API Documentation ==

= Create Session =

**Endpoint**: `POST /wp-json/ucp/v1/session`

**Headers**:
```
Content-Type: application/json
X-UCP-API-Key: your-api-key
```

**Body**:
```json
{
  "platform": "woocommerce",
  "user_data": {
    "user_id": 123,
    "email": "user@gmail.com"
  }
}
```

**Response**:
```json
{
  "success": true,
  "session_id": "ucp_abc123...",
  "expires_at": 1234567890,
  "message": "Session created successfully."
}
```

= Update Session =

**Endpoint**: `PUT /wp-json/ucp/v1/update/{session_id}`

**Body**:
```json
{
  "action": "add_item",
  "data": {
    "product_id": 456,
    "quantity": 2
  }
}
```

= Complete Session =

**Endpoint**: `POST /wp-json/ucp/v1/complete/{session_id}`

**Body**:
```json
{
  "status": "completed",
  "metadata": {
    "order_id": 789
  }
}
```

= Actions Available =

* `add_item` - Add an item to the session
* `update_item` - Update an existing item
* `remove_item` - Remove an item from the session
* `set_data` - Set custom session data
* Custom actions via filters

== Privacy Policy ==

UCP Adapter For WooCommerce stores session data temporarily in your WordPress database. This data is automatically cleaned up based on your configured retention settings. The plugin does not send any data to external services.

Session data may include:
* User identifiers
* Platform information
* Custom data provided via API calls

It is your responsibility to ensure compliance with privacy regulations (GDPR, CCPA, etc.) when using this plugin.

== Credits ==

Developed with WordPress coding standards and best practices in mind.

== Support ==

Support Forum: https://wordpress.org/support/plugin/ucp-adapter-for-woocommerce
