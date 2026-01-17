# UCP Adapter For WooCommerce

A comprehensive WordPress plugin that provides REST endpoints for UCP (Universal Commerce Platform) integration, following WordPress coding standards and best practices. I've created a comprehensive WordPress plugin for UCP (Universal Commerce Platform) integration that follows all WordPress rules, regulations, and coding standards.

## Plugin Structure

**Core Components:**

1. **Main Plugin File** - Entry point with proper WordPress headers, activation/deactivation hooks, and initialization
2. **REST API Handler** - Three core endpoints (Session, Update, Complete) with authentication and validation
3. **Session Handler** - Database-backed session management with automatic cleanup
4. **Security Class** - API key authentication, rate limiting, and IP whitelisting
5. **Admin Interface** - Settings page, session monitoring, and documentation
6. **Frontend/Admin Assets** - CSS and JavaScript files following WordPress standards

## Key Features

✅ **WordPress Compliance:**
- Follows WordPress Coding Standards (WPCS)
- Proper text domain for internationalization
- Secure database operations using $wpdb
- Nonce verification and capability checks
- Sanitization and escaping of all data

✅ **REST API Endpoints:**
- `POST /wp-json/ucp/v1/session` - Create session
- `PUT /wp-json/ucp/v1/update/{session_id}` - Update session
- `POST /wp-json/ucp/v1/complete/{session_id}` - Complete session
- `GET /wp-json/ucp/v1/status/{session_id}` - Check status

✅ **Security Features:**
- API key authentication
- Rate limiting with configurable thresholds
- IP whitelisting support
- Request verification
- Secure session ID generation

✅ **Extensibility:**
- Hooks and filters throughout
- Custom action handlers
- Platform-specific adapters support
- Developer-friendly architecture

## Installation & Usage

1. Upload the plugin folder to `/wp-content/plugins/`
2. Activate through WordPress admin
3. Navigate to UCP Adapter menu
4. Copy your API key from Settings
5. Use the API key in your requests
