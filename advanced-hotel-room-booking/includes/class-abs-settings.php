<?php

/**
 * Settings handler class
 *
 * @package AdvancedBookingSystem
 */

// Exit if accessed directly
if (! defined('ABSPATH')) {
    exit;
}

/**
 * Settings class
 */
class ABS_Settings
{

    /**
     * Get setting value
     *
     * @param string $key Setting key.
     * @param mixed  $default Default value.
     * @return mixed
     */
    public static function get($key, $default = null)
    {
        $cache_group = 'abs_settings';
        $cached_value = wp_cache_get($key, $cache_group);

        if (false !== $cached_value) {
            return $cached_value;
        }

        global $wpdb;
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery
        $value = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT setting_value FROM {$wpdb->prefix}abs_settings WHERE setting_key = %s",
                $key
            )
        );

        if (null === $value) {
            return $default;
        }

        // Unserialize if needed
        $unserialized = @unserialize($value);
        $final_value = (false !== $unserialized) ? $unserialized : $value;

        wp_cache_set($key, $final_value, $cache_group);

        return $final_value;
    }

    /**
     * Set setting value
     *
     * @param string $key Setting key.
     * @param mixed  $value Setting value.
     * @return bool
     */
    public static function set($key, $value)
    {
        global $wpdb;
        $table = $wpdb->prefix . 'abs_settings';

        // Serialize if array or object
        if (is_array($value) || is_object($value)) {
            $value = serialize($value);
        }

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
        $existing = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT id FROM {$wpdb->prefix}abs_settings WHERE setting_key = %s",
                $key
            )
        );

        if ($existing) {
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery
            $result = $wpdb->update(
                $table,
                array('setting_value' => $value),
                array('setting_key' => $key),
                array('%s'),
                array('%s')
            );
        } else {
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery
            $result = $wpdb->insert(
                $table,
                array(
                    'setting_key' => $key,
                    'setting_value' => $value,
                ),
                array('%s', '%s')
            );
        }

        if ($result) {
            wp_cache_delete($key, 'abs_settings');
        }

        return false !== $result;
    }

    /**
     * Delete setting
     *
     * @param string $key Setting key.
     * @return bool
     */
    public static function delete($key)
    {
        global $wpdb;
        $table = $wpdb->prefix . 'abs_settings';
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery
        $result = $wpdb->delete(
            $table,
            array('setting_key' => $key),
            array('%s')
        );

        if ($result) {
            wp_cache_delete($key, 'abs_settings');
        }

        return false !== $result;
    }

    /**
     * Set default settings on activation
     */
    public static function set_defaults()
    {
        $defaults = array(
            'start_day_of_week' => 0, // Sunday
            'closed_days' => array(), // No closed days by default
            'max_bookings_per_user' => 3,
            'admin_email' => get_option('admin_email'),
            'booking_title_default' => __('Room', 'advanced-hotel-room-booking-system'),
            'email_user_confirmation_subject' => __('Booking Confirmed - {site_name}', 'advanced-hotel-room-booking-system'),
            'email_user_confirmation_body' => self::get_default_user_confirmation_template(),
            'email_user_denial_subject' => __('Booking Request Declined - {site_name}', 'advanced-hotel-room-booking-system'),
            'email_user_denial_body' => self::get_default_user_denial_template(),
            'email_admin_notification_subject' => __('New Booking Request - {site_name}', 'advanced-hotel-room-booking-system'),
            'email_admin_notification_body' => self::get_default_admin_notification_template(),
        );

        foreach ($defaults as $key => $value) {
            if (null === self::get($key)) {
                self::set($key, $value);
            }
        }
    }

    /**
     * Get default user confirmation email template
     *
     * @return string
     */
    private static function get_default_user_confirmation_template()
    {
        return '<html><body><p>Dear {first_name} {last_name},</p><p>Your booking has been confirmed!</p><p><strong>Booking Details:</strong></p><ul><li>Booking ID: {booking_id}</li><li>Room: {room_name}</li><li>Date: {booking_date}</li><li>Time: {booking_time}</li></ul><p>Thank you for your booking!</p><p>Best regards,<br>{site_name}</p></body></html>';
    }

    /**
     * Get default user denial email template
     *
     * @return string
     */
    private static function get_default_user_denial_template()
    {
        return '<html><body><p>Dear {first_name} {last_name},</p><p>We regret to inform you that your booking request has been declined.</p><p><strong>Booking Details:</strong></p><ul><li>Booking ID: {booking_id}</li><li>Room: {room_name}</li><li>Date: {booking_date}</li></ul><p>Please contact us if you have any questions.</p><p>Best regards,<br>{site_name}</p></body></html>';
    }

    /**
     * Get default admin notification email template
     *
     * @return string
     */
    private static function get_default_admin_notification_template()
    {
        return '<html><body><p>A new booking request has been received.</p><p><strong>Booking Details:</strong></p><ul><li>Booking ID: {booking_id}</li><li>Customer: {first_name} {last_name}</li><li>Email: {email}</li><li>Phone: {phone}</li><li>Room: {room_name}</li><li>Date: {booking_date}</li><li>Time: {booking_time}</li><li>Status: {status}</li></ul><p><a href="{manage_url}">Manage this booking</a></p></body></html>';
    }
}
