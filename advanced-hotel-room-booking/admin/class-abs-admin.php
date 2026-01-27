<?php

/**
 * Admin area handler
 *
 * @package AdvancedBookingSystem
 */

// Exit if accessed directly
if (! defined('ABSPATH')) {
    exit;
}

/**
 * Admin class
 */
class ABS_Admin
{

    /**
     * Constructor
     */
    public function __construct()
    {
        add_action('admin_menu', array($this, 'add_menu_pages'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('wp_ajax_abs_admin_confirm_booking', array($this, 'ajax_confirm_booking'));
        add_action('wp_ajax_abs_admin_deny_booking', array($this, 'ajax_deny_booking'));
        add_action('wp_ajax_abs_admin_delete_booking', array($this, 'ajax_delete_booking'));
        add_action('wp_ajax_abs_admin_bulk_action', array($this, 'ajax_bulk_action'));
        add_action('wp_ajax_abs_get_room', array($this, 'ajax_get_room'));
        add_action('wp_ajax_abs_save_room', array($this, 'ajax_save_room'));
        add_action('wp_ajax_abs_delete_room', array($this, 'ajax_delete_room'));
        add_action('admin_init', array($this, 'handle_settings_save'));
    }

    /**
     * Add admin menu pages
     */
    public function add_menu_pages()
    {
        add_menu_page(
            __('Bookings', 'advanced-hotel-room-booking-system'),
            __('Bookings', 'advanced-hotel-room-booking-system'),
            'manage_options',
            'abs-bookings',
            array($this, 'bookings_page'),
            'dashicons-calendar-alt',
            30
        );

        add_submenu_page(
            'abs-bookings',
            __('All Bookings', 'advanced-hotel-room-booking-system'),
            __('All Bookings', 'advanced-hotel-room-booking-system'),
            'manage_options',
            'abs-bookings',
            array($this, 'bookings_page')
        );

        add_submenu_page(
            'abs-bookings',
            __('Rooms', 'advanced-hotel-room-booking-system'),
            __('Rooms', 'advanced-hotel-room-booking-system'),
            'manage_options',
            'abs-rooms',
            array($this, 'rooms_page')
        );

        add_submenu_page(
            'abs-bookings',
            __('Settings', 'advanced-hotel-room-booking-system'),
            __('Settings', 'advanced-hotel-room-booking-system'),
            'manage_options',
            'abs-settings',
            array($this, 'settings_page')
        );
    }

    /**
     * Enqueue admin scripts and styles
     *
     * @param string $hook Current admin page hook.
     */
    public function enqueue_scripts($hook)
    {
        if (strpos($hook, 'abs-') === false) {
            return;
        }

        wp_enqueue_style(
            'abs-admin-css',
            ABS_PLUGIN_URL . 'assets/css/abs-admin.css',
            array(),
            ABS_VERSION
        );

        wp_enqueue_script(
            'abs-admin-js',
            ABS_PLUGIN_URL . 'assets/js/abs-admin.js',
            array('jquery'),
            ABS_VERSION,
            true
        );

        wp_localize_script(
            'abs-admin-js',
            'absAdmin',
            array(
                'ajaxUrl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('abs_admin_nonce'),
                'confirmDelete' => __('Are you sure you want to delete this booking?', 'advanced-hotel-room-booking-system'),
                'confirmDeny' => __('Are you sure you want to deny this booking?', 'advanced-hotel-room-booking-system'),
                'confirmBulkAction' => __('Are you sure you want to perform this bulk action?', 'advanced-hotel-room-booking-system'),
                'confirmBulkDelete' => __('Are you sure you want to delete the selected bookings?', 'advanced-hotel-room-booking-system'),
                'confirmDeleteRoom' => __('Are you sure you want to delete this room?', 'advanced-hotel-room-booking-system'),
                'noSelection' => __('Please select at least one booking.', 'advanced-hotel-room-booking-system'),
                'errorMessage' => __('An error occurred. Please try again.', 'advanced-hotel-room-booking-system'),
                'addRoom' => __('Add New Room', 'advanced-hotel-room-booking-system'),
                'editRoom' => __('Edit Room', 'advanced-hotel-room-booking-system'),
            )
        );
    }

    /**
     * Bookings page
     */
    public function bookings_page()
    {
        include ABS_PLUGIN_DIR . 'admin/views/bookings.php';
    }

    /**
     * Rooms page
     */
    public function rooms_page()
    {
        include ABS_PLUGIN_DIR . 'admin/views/rooms.php';
    }

    /**
     * Settings page
     */
    public function settings_page()
    {
        include ABS_PLUGIN_DIR . 'admin/views/settings.php';
    }

    /**
     * Handle settings save
     */
    public function handle_settings_save()
    {
        if (! isset($_POST['abs_settings_nonce'])) {
            return;
        }

        if (! wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['abs_settings_nonce'])), 'abs_save_settings')) {
            return;
        }

        if (! current_user_can('manage_options')) {
            return;
        }

        // Save settings
        $settings = array(
            'start_day_of_week',
            'closed_days',
            'admin_email',
            'email_user_confirmation_subject',
            'email_user_confirmation_body',
            'email_user_denial_subject',
            'email_user_denial_body',
            'email_admin_notification_subject',
            'email_admin_notification_body',
        );

        foreach ($settings as $setting) {
            if (isset($_POST[$setting])) {
                // Unslash first
                // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized, WordPress.Security.ValidatedSanitizedInput.MissingUnslash -- Unslashed immediately
                $raw_value = wp_unslash($_POST[$setting]);

                if (is_array($raw_value)) {
                    $value = $raw_value;
                } else {
                    $value = $raw_value;
                }

                if ('closed_days' === $setting) {
                    $value = is_array($value) ? array_map('absint', $value) : array();
                } elseif ('admin_email' === $setting) {
                    $value = sanitize_email($value);
                } else {
                    $value = wp_kses_post($value);
                }

                ABS_Settings::set($setting, $value);
            }
        }

        wp_safe_redirect(add_query_arg('settings-updated', 'true', wp_get_referer()));
        exit;
    }

    /**
     * AJAX: Confirm booking
     */
    public function ajax_confirm_booking()
    {
        check_ajax_referer('abs_admin_nonce', 'nonce');

        if (! current_user_can('manage_options')) {
            wp_send_json_error(array(
                'message' => __('Permission denied.', 'advanced-hotel-room-booking-system'),
            ));
        }

        $booking_id = isset($_POST['booking_id']) ? absint($_POST['booking_id']) : 0;
        $result = ABS_Booking::confirm($booking_id);

        if ($result['success']) {
            wp_send_json_success($result);
        } else {
            wp_send_json_error($result);
        }
    }

    /**
     * AJAX: Deny booking
     */
    public function ajax_deny_booking()
    {
        check_ajax_referer('abs_admin_nonce', 'nonce');

        if (! current_user_can('manage_options')) {
            wp_send_json_error(array(
                'message' => __('Permission denied.', 'advanced-hotel-room-booking-system'),
            ));
        }

        $booking_id = isset($_POST['booking_id']) ? absint($_POST['booking_id']) : 0;
        $result = ABS_Booking::deny($booking_id);

        if ($result['success']) {
            wp_send_json_success($result);
        } else {
            wp_send_json_error($result);
        }
    }

    /**
     * AJAX: Delete booking
     */
    public function ajax_delete_booking()
    {
        check_ajax_referer('abs_admin_nonce', 'nonce');

        if (! current_user_can('manage_options')) {
            wp_send_json_error(array(
                'message' => __('Permission denied.', 'advanced-hotel-room-booking-system'),
            ));
        }

        $booking_id = isset($_POST['booking_id']) ? absint($_POST['booking_id']) : 0;
        $result = ABS_Booking::delete($booking_id);

        if ($result['success']) {
            wp_send_json_success($result);
        } else {
            wp_send_json_error($result);
        }
    }

    /**
     * AJAX: Bulk action
     */
    public function ajax_bulk_action()
    {
        check_ajax_referer('abs_admin_nonce', 'nonce');

        if (! current_user_can('manage_options')) {
            wp_send_json_error(array(
                'message' => __('Permission denied.', 'advanced-hotel-room-booking-system'),
            ));
        }

        $action = isset($_POST['bulk_action']) ? sanitize_text_field(wp_unslash($_POST['bulk_action'])) : '';
        $booking_ids = isset($_POST['booking_ids']) ? array_map('absint', $_POST['booking_ids']) : array();

        if (empty($action) || empty($booking_ids)) {
            wp_send_json_error(array(
                'message' => __('Invalid request.', 'advanced-hotel-room-booking-system'),
            ));
        }

        $success_count = 0;
        foreach ($booking_ids as $booking_id) {
            if ('confirm' === $action) {
                $result = ABS_Booking::confirm($booking_id);
            } elseif ('deny' === $action) {
                $result = ABS_Booking::deny($booking_id);
            } elseif ('delete' === $action) {
                $result = ABS_Booking::delete($booking_id);
            }

            if (isset($result['success']) && $result['success']) {
                $success_count++;
            }
        }

        wp_send_json_success(array(
            'message' => sprintf(
                /* translators: %d: number of bookings */
                _n('%d booking updated successfully.', '%d bookings updated successfully.', $success_count, 'advanced-hotel-room-booking-system'),
                $success_count
            ),
        ));
    }

    /**
     * AJAX: Get room
     */
    public function ajax_get_room()
    {
        check_ajax_referer('abs_admin_nonce', 'nonce');

        if (! current_user_can('manage_options')) {
            wp_send_json_error(array(
                'message' => __('Permission denied.', 'advanced-hotel-room-booking-system'),
            ));
        }

        $room_id = isset($_POST['room_id']) ? absint($_POST['room_id']) : 0;
        $room = ABS_Room::get($room_id);

        if ($room) {
            wp_send_json_success(array('room' => $room));
        } else {
            wp_send_json_error(array(
                'message' => __('Room not found.', 'advanced-hotel-room-booking-system'),
            ));
        }
    }

    /**
     * AJAX: Save room
     */
    public function ajax_save_room()
    {
        check_ajax_referer('abs_admin_nonce', 'nonce');

        if (! current_user_can('manage_options')) {
            wp_send_json_error(array(
                'message' => __('Permission denied.', 'advanced-hotel-room-booking-system'),
            ));
        }

        $room_id = isset($_POST['room_id']) ? absint($_POST['room_id']) : 0;
        $data = array(
            'name' => isset($_POST['room_name']) ? sanitize_text_field(wp_unslash($_POST['room_name'])) : '',
            'description' => isset($_POST['description']) ? sanitize_textarea_field(wp_unslash($_POST['description'])) : '',
            'booking_title' => isset($_POST['booking_title']) ? sanitize_text_field(wp_unslash($_POST['booking_title'])) : 'Room',
            'max_bookings_per_user' => isset($_POST['max_bookings']) ? absint($_POST['max_bookings']) : 3,
            'capacity' => isset($_POST['capacity']) ? absint($_POST['capacity']) : 1,
            'status' => isset($_POST['status']) ? sanitize_text_field(wp_unslash($_POST['status'])) : 'active',
        );

        if ($room_id) {
            $result = ABS_Room::update($room_id, $data);
            $message = __('Room updated successfully.', 'advanced-hotel-room-booking-system');
        } else {
            $result = ABS_Room::create($data);
            $message = __('Room created successfully.', 'advanced-hotel-room-booking-system');
        }

        if ($result) {
            wp_send_json_success(array('message' => $message));
        } else {
            wp_send_json_error(array(
                'message' => __('Failed to save room.', 'advanced-hotel-room-booking-system'),
            ));
        }
    }

    /**
     * AJAX: Delete room
     */
    public function ajax_delete_room()
    {
        check_ajax_referer('abs_admin_nonce', 'nonce');

        if (! current_user_can('manage_options')) {
            wp_send_json_error(array(
                'message' => __('Permission denied.', 'advanced-hotel-room-booking-system'),
            ));
        }

        $room_id = isset($_POST['room_id']) ? absint($_POST['room_id']) : 0;
        $result = ABS_Room::delete($room_id);

        if ($result) {
            wp_send_json_success(array(
                'message' => __('Room deleted successfully.', 'advanced-hotel-room-booking-system'),
            ));
        } else {
            wp_send_json_error(array(
                'message' => __('Failed to delete room. Room may have existing bookings.', 'advanced-hotel-room-booking-system'),
            ));
        }
    }
}
