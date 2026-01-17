<?php

/**
 * Database handler class
 *
 * @package AdvancedBookingSystem
 */

// Exit if accessed directly
if (! defined('ABSPATH')) {
    exit;
}

/**
 * Database class
 */
class ABS_Database
{

    /**
     * Create database tables
     */
    public static function create_tables()
    {
        global $wpdb;

        $charset_collate = $wpdb->get_charset_collate();

        // Bookings table
        $bookings_table = $wpdb->prefix . 'abs_bookings';
        $sql_bookings = "CREATE TABLE IF NOT EXISTS $bookings_table (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            user_id bigint(20) UNSIGNED NOT NULL,
            room_id bigint(20) UNSIGNED NOT NULL,
            booking_date date NOT NULL,
            start_time time DEFAULT NULL,
            end_time time DEFAULT NULL,
            first_name varchar(100) NOT NULL,
            last_name varchar(100) NOT NULL,
            phone varchar(20) NOT NULL,
            email varchar(100) NOT NULL,
            status varchar(20) DEFAULT 'pending',
            notes text,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY user_id (user_id),
            KEY room_id (room_id),
            KEY booking_date (booking_date),
            KEY status (status)
        ) $charset_collate;";

        // Rooms table
        $rooms_table = $wpdb->prefix . 'abs_rooms';
        $sql_rooms = "CREATE TABLE IF NOT EXISTS $rooms_table (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            name varchar(200) NOT NULL,
            description text,
            capacity int DEFAULT 1,
            status varchar(20) DEFAULT 'active',
            max_bookings_per_user int DEFAULT 3,
            booking_title varchar(100) DEFAULT 'Room',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY status (status)
        ) $charset_collate;";

        // Settings table
        $settings_table = $wpdb->prefix . 'abs_settings';
        $sql_settings = "CREATE TABLE IF NOT EXISTS $settings_table (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            setting_key varchar(100) NOT NULL,
            setting_value longtext,
            PRIMARY KEY (id),
            UNIQUE KEY setting_key (setting_key)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql_bookings);
        dbDelta($sql_rooms);
        dbDelta($sql_settings);
    }

    /**
     * Get last changed timestamp for cache invalidation
     * 
     * @param string $group Cache group.
     * @return string
     */
    private static function get_last_changed($group)
    {
        $last_changed = wp_cache_get('last_changed', $group);
        if (! $last_changed) {
            $last_changed = microtime();
            wp_cache_set('last_changed', $last_changed, $group);
        }
        return $last_changed;
    }

    /**
     * Invalidate cache group
     * 
     * @param string $group Cache group.
     */
    private static function invalidate_cache_group($group)
    {
        wp_cache_set('last_changed', microtime(), $group);
    }

    /**
     * Get booking by ID
     *
     * @param int $booking_id Booking ID.
     * @return object|null
     */
    public static function get_booking($booking_id)
    {
        $cache_key = 'abs_booking_' . $booking_id;
        $cached = wp_cache_get($cache_key, 'abs_bookings');
        if (false !== $cached) {
            return $cached;
        }

        global $wpdb;
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery
        $booking = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}abs_bookings WHERE id = %d",
                $booking_id
            )
        );

        if ($booking) {
            wp_cache_set($cache_key, $booking, 'abs_bookings');
        }

        return $booking;
    }

    /**
     * Get bookings by user ID
     *
     * @param int $user_id User ID.
     * @return array
     */
    public static function get_user_bookings($user_id)
    {
        $last_changed = self::get_last_changed('abs_bookings');
        $cache_key = "user_bookings_{$user_id}:$last_changed";
        $cached = wp_cache_get($cache_key, 'abs_bookings');

        if (false !== $cached) {
            return $cached;
        }

        global $wpdb;
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery
        $bookings = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}abs_bookings WHERE user_id = %d ORDER BY booking_date DESC",
                $user_id
            )
        );

        wp_cache_set($cache_key, $bookings, 'abs_bookings');
        return $bookings;
    }

    /**
     * Get bookings by room and date
     *
     * @param int    $room_id Room ID.
     * @param string $date Date in Y-m-d format.
     * @return array
     */
    public static function get_room_bookings($room_id, $date = null)
    {
        $last_changed = self::get_last_changed('abs_bookings');
        $date_key = $date ? $date : 'all';
        $cache_key = "room_bookings_{$room_id}_{$date_key}:$last_changed";
        $cached = wp_cache_get($cache_key, 'abs_bookings');

        if (false !== $cached) {
            return $cached;
        }

        global $wpdb;
        if ($date) {
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery
            $bookings = $wpdb->get_results(
                $wpdb->prepare(
                    "SELECT * FROM {$wpdb->prefix}abs_bookings WHERE room_id = %d AND booking_date = %s AND status != 'cancelled'",
                    $room_id,
                    $date
                )
            );
        } else {
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery
            $bookings = $wpdb->get_results(
                $wpdb->prepare(
                    "SELECT * FROM {$wpdb->prefix}abs_bookings WHERE room_id = %d AND status != 'cancelled' ORDER BY booking_date ASC",
                    $room_id
                )
            );
        }

        wp_cache_set($cache_key, $bookings, 'abs_bookings');
        return $bookings;
    }

    /**
     * Count user active bookings
     *
     * @param int $user_id User ID.
     * @param int $room_id Room ID (optional).
     * @return int
     */
    public static function count_user_active_bookings($user_id, $room_id = null)
    {
        $last_changed = self::get_last_changed('abs_bookings');
        $room_key = $room_id ? $room_id : 'all';
        $cache_key = "count_user_{$user_id}_{$room_key}:$last_changed";
        $cached = wp_cache_get($cache_key, 'abs_bookings');

        if (false !== $cached) {
            return $cached;
        }

        global $wpdb;
        $sql = "SELECT COUNT(*) FROM {$wpdb->prefix}abs_bookings WHERE user_id = %d AND status IN ('pending', 'confirmed') AND booking_date >= CURDATE()";
        $params = array($user_id);

        if ($room_id) {
            $sql .= " AND room_id = %d";
            $params[] = $room_id;
        }

        // phpcs:ignore PluginCheck.Security.DirectDB.UnescapedDBParameter, WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.NotPrepared
        $count = (int) $wpdb->get_var($wpdb->prepare($sql, $params));
        wp_cache_set($cache_key, $count, 'abs_bookings');

        return $count;
    }

    /**
     * Get total number of rooms
     *
     * @return int
     */
    public static function get_total_rooms()
    {
        $cached = wp_cache_get('abs_total_rooms', 'abs_rooms');
        if (false !== $cached) {
            return $cached;
        }

        global $wpdb;
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery
        $count = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}abs_rooms");

        wp_cache_set('abs_total_rooms', $count, 'abs_rooms');
        return $count;
    }

    /**
     * Create booking
     *
     * @param array $data Booking data.
     * @return int|false
     */
    public static function create_booking($data)
    {
        global $wpdb;
        $table = $wpdb->prefix . 'abs_bookings';

        $defaults = array(
            'status' => 'pending',
            'created_at' => current_time('mysql'),
        );

        $data = wp_parse_args($data, $defaults);

        global $wpdb;
        $table = $wpdb->prefix . 'abs_bookings';

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery
        $result = $wpdb->insert(
            $table,
            $data,
            array(
                '%d', // user_id
                '%d', // room_id
                '%s', // booking_date
                '%s', // start_time
                '%s', // end_time
                '%s', // first_name
                '%s', // last_name
                '%s', // phone
                '%s', // email
                '%s', // status
                '%s', // notes
                '%s', // created_at
            )
        );

        if ($result) {
            self::invalidate_cache_group('abs_bookings');
            return $wpdb->insert_id;
        }

        return false;
    }

    /**
     * Update booking
     *
     * @param int   $booking_id Booking ID.
     * @param array $data Booking data.
     * @return bool
     */
    public static function update_booking($booking_id, $data)
    {
        global $wpdb;
        $table = $wpdb->prefix . 'abs_bookings';

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery
        $result = $wpdb->update(
            $table,
            $data,
            array('id' => $booking_id),
            null,
            array('%d')
        );

        if ($result !== false) {
            wp_cache_delete('abs_booking_' . $booking_id, 'abs_bookings');
            self::invalidate_cache_group('abs_bookings');
        }

        return false !== $result;
    }

    /**
     * Delete booking
     *
     * @param int $booking_id Booking ID.
     * @return bool
     */
    public static function delete_booking($booking_id)
    {
        global $wpdb;
        $table = $wpdb->prefix . 'abs_bookings';

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery
        $result = $wpdb->delete(
            $table,
            array('id' => $booking_id),
            array('%d')
        );

        if ($result) {
            wp_cache_delete('abs_booking_' . $booking_id, 'abs_bookings');
            self::invalidate_cache_group('abs_bookings');
        }

        return false !== $result;
    }


    /**
     * Get bookings with filters
     *
     * @param array $args Filter arguments.
     * @return array
     */
    public static function get_bookings($args = array())
    {
        $defaults = array(
            'status' => '',
            'room_id' => 0,
            'date_from' => '',
            'date_to' => '',
            'limit' => 0,
            'offset' => 0,
        );

        $args = wp_parse_args($args, $defaults);
        $last_changed = self::get_last_changed('abs_bookings');
        $cache_key = 'abs_bookings_filter_' . md5(serialize($args)) . ':' . $last_changed;
        $cached = wp_cache_get($cache_key, 'abs_bookings');

        if (false !== $cached) {
            return $cached;
        }

        global $wpdb;
        $sql = "SELECT * FROM {$wpdb->prefix}abs_bookings WHERE 1=1";
        $params = array();

        if (! empty($args['status'])) {
            $sql .= " AND status = %s";
            $params[] = $args['status'];
        }

        if (! empty($args['room_id'])) {
            $sql .= " AND room_id = %d";
            $params[] = $args['room_id'];
        }

        if (! empty($args['date_from'])) {
            $sql .= " AND booking_date >= %s";
            $params[] = $args['date_from'];
        }

        if (! empty($args['date_to'])) {
            $sql .= " AND booking_date <= %s";
            $params[] = $args['date_to'];
        }

        $sql .= " ORDER BY booking_date DESC, created_at DESC";

        if (! empty($args['limit'])) {
            $sql .= " LIMIT %d";
            $params[] = $args['limit'];

            if (! empty($args['offset'])) {
                $sql .= " OFFSET %d";
                $params[] = $args['offset'];
            }
        }

        if (! empty($params)) {
            // phpcs:ignore PluginCheck.Security.DirectDB.UnescapedDBParameter, WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.NotPrepared
            $bookings = $wpdb->get_results($wpdb->prepare($sql, $params));
        } else {
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.NotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter
            $bookings = $wpdb->get_results($sql);
        }

        wp_cache_set($cache_key, $bookings, 'abs_bookings');
        return $bookings;
    }

    /**
     * Get booking statistics
     *
     * @return object
     */
    public static function get_booking_stats()
    {
        $last_changed = self::get_last_changed('abs_bookings');
        $cache_key = 'abs_booking_stats:' . $last_changed;
        $cached = wp_cache_get($cache_key, 'abs_bookings');

        if (false !== $cached) {
            return $cached;
        }

        global $wpdb;
        $table = $wpdb->prefix . 'abs_bookings';

        $stats = new stdClass();
        $stats = new stdClass();
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery
        $stats->total = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}abs_bookings");
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery
        $stats->pending = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}abs_bookings WHERE status = 'pending'");
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery
        $stats->confirmed = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}abs_bookings WHERE status = 'confirmed'");
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery
        $stats->cancelled = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}abs_bookings WHERE status = 'cancelled'");

        wp_cache_set($cache_key, $stats, 'abs_bookings');
        return $stats;
    }

    /**
     * Get all rooms
     *
     * @return array
     */
    public static function get_rooms()
    {
        $cached = wp_cache_get('abs_rooms_all', 'abs_rooms');
        if (false !== $cached) {
            return $cached;
        }

        global $wpdb;
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery
        $rooms = $wpdb->get_results(
            "SELECT * FROM {$wpdb->prefix}abs_rooms WHERE status = 'active' ORDER BY name ASC"
        );

        wp_cache_set('abs_rooms_all', $rooms, 'abs_rooms');
        return $rooms;
    }

    /**
     * Get room by ID
     *
     * @param int $room_id Room ID.
     * @return object|null
     */
    public static function get_room($room_id)
    {
        $cached = wp_cache_get('abs_room_' . $room_id, 'abs_rooms');
        if (false !== $cached) {
            return $cached;
        }

        global $wpdb;
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery
        $room = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}abs_rooms WHERE id = %d",
                $room_id
            )
        );

        if ($room) {
            wp_cache_set('abs_room_' . $room_id, $room, 'abs_rooms');
        }

        return $room;
    }
}
