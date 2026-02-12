<?php

/**
 * Room handler class
 *
 * @package IntelligentRoomBookingSystemForHotel
 */

// Exit if accessed directly
if (! defined('ABSPATH')) {
    exit;
}

/**
 * Room class
 */
class IRBSFH_Room
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
        $table = $wpdb->prefix . 'irbsfh_rooms';

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
            wp_cache_delete('irbsfh_rooms_all', 'irbsfh_rooms');
            wp_cache_delete('irbsfh_total_rooms', 'irbsfh_rooms');
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
        $table = $wpdb->prefix . 'irbsfh_rooms';

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
            wp_cache_delete('irbsfh_room_' . $room_id, 'irbsfh_rooms');
            wp_cache_delete('irbsfh_rooms_all', 'irbsfh_rooms');
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
        $table = $wpdb->prefix . 'irbsfh_rooms';

        // Check if room has bookings
        $bookings = IRBSFH_Database::get_room_bookings($room_id);
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
            wp_cache_delete('irbsfh_room_' . $room_id, 'irbsfh_rooms');
            wp_cache_delete('irbsfh_rooms_all', 'irbsfh_rooms');
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
        $cached = wp_cache_get('irbsfh_rooms_all', 'irbsfh_rooms');
        if (false !== $cached) {
            return $cached;
        }

        $rooms = IRBSFH_Database::get_rooms();
        wp_cache_set('irbsfh_rooms_all', $rooms, 'irbsfh_rooms');

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
        $cached = wp_cache_get('irbsfh_room_' . $room_id, 'irbsfh_rooms');
        if (false !== $cached) {
            return $cached;
        }

        $room = IRBSFH_Database::get_room($room_id);

        if ($room) {
            wp_cache_set('irbsfh_room_' . $room_id, $room, 'irbsfh_rooms');
        }

        return $room;
    }
}
