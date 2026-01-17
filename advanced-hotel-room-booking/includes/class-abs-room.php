<?php

/**
 * Room handler class
 *
 * @package AdvancedBookingSystem
 */

// Exit if accessed directly
if (! defined('ABSPATH')) {
    exit;
}

/**
 * Room class
 */
class ABS_Room
{

    /**
     * Create a new room
     *
     * @param array $data Room data.
     * @return int|false
     */
    public static function create($data)
    {
        global $wpdb;
        $table = $wpdb->prefix . 'abs_rooms';

        $defaults = array(
            'status' => 'active',
            'max_bookings_per_user' => 3,
            'booking_title' => 'Room',
        );

        $data = wp_parse_args($data, $defaults);

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery
        $result = $wpdb->insert(
            $table,
            array(
                'name' => sanitize_text_field($data['name']),
                'description' => sanitize_textarea_field($data['description']),
                'capacity' => isset($data['capacity']) ? absint($data['capacity']) : 1,
                'status' => sanitize_text_field($data['status']),
                'max_bookings_per_user' => absint($data['max_bookings_per_user']),
                'booking_title' => sanitize_text_field($data['booking_title']),
            ),
            array('%s', '%s', '%d', '%s', '%d', '%s')
        );

        if ($result) {
            wp_cache_delete('abs_rooms_all', 'abs_rooms');
            wp_cache_delete('abs_total_rooms', 'abs_rooms');
            return $wpdb->insert_id;
        }

        return false;
    }

    /**
     * Update a room
     *
     * @param int   $room_id Room ID.
     * @param array $data Room data.
     * @return bool
     */
    public static function update($room_id, $data)
    {
        global $wpdb;
        $table = $wpdb->prefix . 'abs_rooms';

        $update_data = array();

        if (isset($data['name'])) {
            $update_data['name'] = sanitize_text_field($data['name']);
        }
        if (isset($data['description'])) {
            $update_data['description'] = sanitize_textarea_field($data['description']);
        }
        if (isset($data['capacity'])) {
            $update_data['capacity'] = absint($data['capacity']);
        }
        if (isset($data['status'])) {
            $update_data['status'] = sanitize_text_field($data['status']);
        }
        if (isset($data['max_bookings_per_user'])) {
            $update_data['max_bookings_per_user'] = absint($data['max_bookings_per_user']);
        }
        if (isset($data['booking_title'])) {
            $update_data['booking_title'] = sanitize_text_field($data['booking_title']);
        }

        if (empty($update_data)) {
            return false;
        }

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery
        $result = $wpdb->update(
            $table,
            $update_data,
            array('id' => $room_id),
            null,
            array('%d')
        );

        if ($result) {
            wp_cache_delete('abs_room_' . $room_id, 'abs_rooms');
            wp_cache_delete('abs_rooms_all', 'abs_rooms');
        }

        return false !== $result;
    }

    /**
     * Delete a room
     *
     * @param int $room_id Room ID.
     * @return bool
     */
    public static function delete($room_id)
    {
        global $wpdb;
        $table = $wpdb->prefix . 'abs_rooms';

        // Check if room has bookings
        $bookings = ABS_Database::get_room_bookings($room_id);
        if (! empty($bookings)) {
            return false;
        }

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery
        $result = $wpdb->delete(
            $table,
            array('id' => $room_id),
            array('%d')
        );

        if ($result) {
            wp_cache_delete('abs_room_' . $room_id, 'abs_rooms');
            wp_cache_delete('abs_rooms_all', 'abs_rooms');
        }

        return false !== $result;
    }

    /**
     * Get all active rooms
     *
     * @return array
     */
    public static function get_all()
    {
        $cached = wp_cache_get('abs_rooms_all', 'abs_rooms');
        if (false !== $cached) {
            return $cached;
        }

        $rooms = ABS_Database::get_rooms();
        wp_cache_set('abs_rooms_all', $rooms, 'abs_rooms');

        return $rooms;
    }

    /**
     * Get room by ID
     *
     * @param int $room_id Room ID.
     * @return object|null
     */
    public static function get($room_id)
    {
        $cached = wp_cache_get('abs_room_' . $room_id, 'abs_rooms');
        if (false !== $cached) {
            return $cached;
        }

        $room = ABS_Database::get_room($room_id);

        if ($room) {
            wp_cache_set('abs_room_' . $room_id, $room, 'abs_rooms');
        }

        return $room;
    }
}
