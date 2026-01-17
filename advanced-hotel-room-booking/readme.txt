=== Advanced Hotel Room Booking System ===
Contributors: dhirajpatra
Tags: booking, hotel, reservation, room, calendar
Requires at least: 5.8
Tested up to: 6.9
Stable tag: 1.0.0
Requires PHP: 7.4
License: GPL v2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Complete booking management system with calendar, user authentication, and email notifications.

== Description ==

Advanced Booking System is a comprehensive WordPress plugin that allows you to manage bookings for rooms, courts, chalets, sports fields, or any bookable resource. Perfect for hotels, sports facilities, co-working spaces, and rental businesses.

= Key Features =

**For Site Visitors:**
* Interactive calendar interface for date selection
* User registration and login required for bookings
* Real-time availability checking
* Email confirmation when booking is approved
* Email notification when booking is denied
* View and manage personal bookings
* Cancel bookings before the scheduled date

**For Administrators:**
* Comprehensive admin panel for booking management
* Approve or deny booking requests with one click
* Automated email notifications for new bookings
* Customizable email templates with template tags
* Unlimited room/resource management (tested up to 20 rooms)
* Set maximum bookings per user per room (default: 3)
* Configure closed days (e.g., closed every Sunday)
* Set week start day preference
* Customizable booking titles (Room, Chalet, Court, etc.)
* Filter bookings by status, room, or date range
* Bulk actions for efficient management
* Statistics dashboard

**Form Fields & Validation:**
* First Name (required, validated)
* Last Name (required, validated)
* Email Address (required, email format validation)
* Phone Number (required, phone format validation)
* Booking Date (required, validated against past dates and closed days)
* Notes/Comments (optional)

**Email Features:**
* Customizable email templates for:
  - User booking confirmation
  - User booking denial
  - Admin new booking notification
* Available template tags:
  - {first_name}, {last_name}
  - {email}, {phone}
  - {room_name}, {booking_title}
  - {booking_date}, {booking_time}
  - {booking_id}, {status}
  - {site_name}, {site_url}
  - {manage_url}

**Security Features:**
* SQL injection protection via prepared statements
* XSS protection via proper escaping and sanitization
* CSRF protection via nonce verification
* User authentication requirement
* Role-based access control
* Secure AJAX handling

== Installation ==

1. Upload the plugin files to the `/wp-content/plugins/advanced-hotel-room-booking` directory, or install the plugin through the WordPress plugins screen directly.
2. Activate the plugin through the 'Plugins' screen in WordPress.
3. Navigate to 'Bookings' in your WordPress admin menu.
4. Configure your settings under 'Bookings > Settings'.
5. Add rooms/resources under 'Bookings > Rooms'.
6. Add the booking calendar to any page using the shortcode `[abs_booking_calendar]`.

= Shortcodes =

* `[abs_booking_calendar]` - Display the booking calendar
* `[abs_booking_form]` - Display the booking form
* `[abs_room_list]` - Display list of available rooms
* `[abs_user_bookings]` - Display user's booking history (requires login)
* `[abs_login_widget]` - Display login form

= Widget =

The plugin includes a login widget that can be added to any widget area (sidebar, footer, etc.).

== Frequently Asked Questions ==

= Do users need to register before making a booking? =

Yes, users must be logged in to make bookings. This ensures accountability and allows users to manage their own bookings.

= How many rooms can I create? =

The plugin supports unlimited rooms, though it has been tested and optimized for up to 20 rooms.

= Can I limit how many bookings a user can make? =

Yes, you can set a maximum number of active bookings per user per room. The default is 3 bookings.

= How do I prevent bookings on specific days? =

In the Settings page, you can configure closed days (e.g., close every Sunday). Bookings will not be available on these days.

= Can I customize the email notifications? =

Yes, all email templates are fully customizable. You can edit the subject and body content, and use template tags to insert dynamic content.

= What happens when I approve a booking? =

When you approve a booking, the status changes to "confirmed" and an automated email is sent to the customer using your confirmation email template.

= What happens when I deny a booking? =

When you deny a booking, the status changes to "cancelled" and an automated email is sent to the customer using your denial email template.

= Can I change "Room" to something else like "Court" or "Chalet"? =

Yes, each room can have its own custom booking title. You can set it to anything like "Court," "Chalet," "Sports Field," "Conference Room," etc.

= Is the calendar mobile-responsive? =

Yes, the plugin is fully responsive and works seamlessly on mobile devices, tablets, and desktop computers.

= Does this plugin work with page builders? =

Yes, you can use the shortcodes with popular page builders like Elementor, Beaver Builder, Divi, and Visual Composer.

= Can users cancel their own bookings? =

Yes, users can cancel their bookings from their dashboard before the scheduled date.

== Screenshots ==

1. Interactive booking calendar with date selection
2. Admin bookings management panel
3. Booking form with validation
4. Email template editor with available tags
5. Room management interface
6. Settings page with configuration options
7. User dashboard showing booking history
8. Login widget for sidebars

== Changelog ==

= 1.0.0 =
* Initial release
* Interactive calendar with date selection
* User registration and authentication system
* Room/resource management (unlimited, tested up to 20)
* Booking approval/denial workflow
* Automated email notifications
* Customizable email templates with template tags
* Form validation (first name, last name, email, phone)
* Closed days configuration
* Maximum bookings per user constraint (default 3)
* Customizable booking titles per room
* Responsive design for all devices
* Login widget
* User booking dashboard
* Admin statistics dashboard
* Bulk actions for efficient management
* Security features (SQL injection, XSS, CSRF protection)

== Upgrade Notice ==

= 1.0.0 =
Initial release of Advanced Booking System.

== Additional Information ==

= Requirements =
* WordPress 5.8 or higher
* PHP 7.4 or higher
* MySQL 5.6 or higher

= Support =
For support, please visit the plugin support forum or contact us through our website.

= Development =
This plugin follows WordPress coding standards and best practices:
* Proper data sanitization and validation
* Prepared SQL statements for security
* Nonce verification for forms
* Translatable strings with text domain
* Action and filter hooks for extensibility
* Object-oriented architecture
* No PHP errors or warnings in debug mode

= Credits =
Developed with attention to WordPress coding standards and security best practices.

== Privacy Policy ==

This plugin stores the following user data:
* First name, last name
* Email address
* Phone number
* Booking dates and times
* User IP address (for security logs)

All data is stored in your WordPress database and is subject to your site's privacy policy. No data is sent to external services.

== Technical Details ==

= Database Tables =
* wp_abs_bookings - Stores all booking records
* wp_abs_rooms - Stores room/resource information
* wp_abs_settings - Stores plugin settings

= AJAX Actions =
* abs_load_calendar - Load calendar for specific month
* abs_check_availability - Check room availability
* abs_submit_booking - Submit new booking
* abs_cancel_booking - Cancel user booking
* abs_admin_confirm_booking - Admin approve booking
* abs_admin_deny_booking - Admin deny booking
* abs_admin_delete_booking - Admin delete booking
* abs_admin_bulk_action - Bulk booking actions

= Hooks for Developers =

**Actions:**
* abs_booking_created - Fires after booking is created
* abs_booking_confirmed - Fires after booking is confirmed
* abs_booking_cancelled - Fires after booking is cancelled
* abs_booking_deleted - Fires after booking is deleted
* abs_email_sent - Fires after email is sent

**Filters:**
* abs_booking_form_fields - Filter booking form fields
* abs_email_template_tags - Filter available email template tags
* abs_calendar_availability - Filter calendar availability data
* abs_max_bookings_per_user - Filter maximum bookings constraint

== Minimum Requirements ==

* WordPress 5.8 or greater
* PHP version 7.4 or greater
* MySQL version 5.6 or greater OR MariaDB version 10.1 or greater

== Recommended Requirements ==

* WordPress 6.0 or greater
* PHP version 8.0 or greater
* MySQL version 5.7 or greater OR MariaDB version 10.3 or greater
* HTTPS support