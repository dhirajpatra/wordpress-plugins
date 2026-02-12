<?php

/**
 * Admin area handler
 *
 * @package IntelligentRoomBookingSystemForHotel
 */

// Exit if accessed directly
if (! defined('ABSPATH')) {
    exit;
}

/**
 * Admin class
 */
class IRBSFH_Admin
{

    /**
     * Constructor
     */
    public function __construct()
    {
        add_action('admin_menu', array($this, 'add_menu_pages'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('wp_ajax_irbsfh_admin_confirm_booking', array($this, 'ajax_confirm_booking'));
        add_action('wp_ajax_irbsfh_admin_deny_booking', array($this, 'ajax_deny_booking'));
        add_action('wp_ajax_irbsfh_admin_delete_booking', array($this, 'ajax_delete_booking'));
        add_action('wp_ajax_irbsfh_admin_bulk_action', array($this, 'ajax_bulk_action'));
        add_action('wp_ajax_irbsfh_get_room', array($this, 'ajax_get_room'));
        add_action('wp_ajax_irbsfh_save_room', array($this, 'ajax_save_room'));
        add_action('wp_ajax_irbsfh_delete_room', array($this, 'ajax_delete_room'));
        add_action('admin_init', array($this, 'handle_settings_save'));
    }

    /**
     * Add admin menu pages
     */
    public function add_menu_pages()
    {
        add_menu_page(
            __('Bookings', 'intelligent-room-booking-system-for-hotel'),
            __('Bookings', 'intelligent-room-booking-system-for-hotel'),
            'manage_options',
            'irbsfh-bookings',
            array($this, 'bookings_page'),
            'dashicons-calendar-alt',
            30
        );

        add_submenu_page(
            'irbsfh-bookings',
            __('All Bookings', 'intelligent-room-booking-system-for-hotel'),
            __('All Bookings', 'intelligent-room-booking-system-for-hotel'),
            'manage_options',
            'irbsfh-bookings',
            array($this, 'bookings_page')
        );

        add_submenu_page(
            'irbsfh-bookings',
            __('Rooms', 'intelligent-room-booking-system-for-hotel'),
            __('Rooms', 'intelligent-room-booking-system-for-hotel'),
            'manage_options',
            'irbsfh-rooms',
            array($this, 'rooms_page')
        );

        add_submenu_page(
            'irbsfh-bookings',
            __('Settings', 'intelligent-room-booking-system-for-hotel'),
            __('Settings', 'intelligent-room-booking-system-for-hotel'),
            'manage_options',
            'irbsfh-settings',
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
        if (strpos($hook, 'irbsfh-') === false) {
            return;
        }

        wp_enqueue_style(
            'irbsfh-admin-css',
            IRBSFH_PLUGIN_URL . 'assets/css/irbsfh-admin.css',
            array(),
            IRBSFH_VERSION
        );

        wp_enqueue_script(
            'irbsfh-admin-js',
            IRBSFH_PLUGIN_URL . 'assets/js/irbsfh-admin.js',
            array('jquery'),
            IRBSFH_VERSION,
            true
        );

        wp_localize_script(
            'irbsfh-admin-js',
            'irbsfhAdmin',
            array(
                'ajaxUrl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('irbsfh_admin_nonce'),
                'confirmDelete' => __('Are you sure you want to delete this booking?', 'intelligent-room-booking-system-for-hotel'),
                'confirmDeny' => __('Are you sure you want to deny this booking?', 'intelligent-room-booking-system-for-hotel'),
                'confirmBulkAction' => __('Are you sure you want to perform this bulk action?', 'intelligent-room-booking-system-for-hotel'),
                'confirmBulkDelete' => __('Are you sure you want to delete the selected bookings?', 'intelligent-room-booking-system-for-hotel'),
                'confirmDeleteRoom' => __('Are you sure you want to delete this room?', 'intelligent-room-booking-system-for-hotel'),
                'noSelection' => __('Please select at least one booking.', 'intelligent-room-booking-system-for-hotel'),
                'errorMessage' => __('An error occurred. Please try again.', 'intelligent-room-booking-system-for-hotel'),
                'addRoom' => __('Add New Room', 'intelligent-room-booking-system-for-hotel'),
                'editRoom' => __('Edit Room', 'intelligent-room-booking-system-for-hotel'),
            )
        );
    }

    /**
     * Bookings page
     */
    public function bookings_page()
    {
        include IRBSFH_PLUGIN_DIR . 'admin/views/bookings.php';
    }

    /**
     * Rooms page
     */
    public function rooms_page()
    {
        include IRBSFH_PLUGIN_DIR . 'admin/views/rooms.php';
    }

    /**
     * Settings page
     */
    public function settings_page()
    {
        include IRBSFH_PLUGIN_DIR . 'admin/views/settings.php';
    }

    /**
     * Handle settings save
     */
    public function handle_settings_save()
    {
        if (! isset($_POST['irbsfh_settings_nonce'])) {
            return;
        }

        if (! wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['irbsfh_settings_nonce'])), 'irbsfh_save_settings')) {
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

                IRBSFH_Settings::set($setting, $value);
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
        check_ajax_referer('irbsfh_admin_nonce', 'nonce');

        if (! current_user_can('manage_options')) {
            wp_send_json_error(array(
                'message' => __('Permission denied.', 'intelligent-room-booking-system-for-hotel'),
            ));
        }

        $booking_id = isset($_POST['booking_id']) ? absint($_POST['booking_id']) : 0;
        $result = IRBSFH_Booking::confirm($booking_id);

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
        check_ajax_referer('irbsfh_admin_nonce', 'nonce');

        if (! current_user_can('manage_options')) {
            wp_send_json_error(array(
                'message' => __('Permission denied.', 'intelligent-room-booking-system-for-hotel'),
            ));
        }

        $booking_id = isset($_POST['booking_id']) ? absint($_POST['booking_id']) : 0;
        $result = IRBSFH_Booking::deny($booking_id);

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
        check_ajax_referer('irbsfh_admin_nonce', 'nonce');

        if (! current_user_can('manage_options')) {
            wp_send_json_error(array(
                'message' => __('Permission denied.', 'intelligent-room-booking-system-for-hotel'),
            ));
        }

        $booking_id = isset($_POST['booking_id']) ? absint($_POST['booking_id']) : 0;
        $result = IRBSFH_Booking::delete($booking_id);

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
        check_ajax_referer('irbsfh_admin_nonce', 'nonce');

        if (! current_user_can('manage_options')) {
            wp_send_json_error(array(
                'message' => __('Permission denied.', 'intelligent-room-booking-system-for-hotel'),
            ));
        }

        $action = isset($_POST['bulk_action']) ? sanitize_text_field(wp_unslash($_POST['bulk_action'])) : '';
        $booking_ids = isset($_POST['booking_ids']) ? array_map('absint', $_POST['booking_ids']) : array();

        if (empty($action) || empty($booking_ids)) {
            wp_send_json_error(array(
                'message' => __('Invalid request.', 'intelligent-room-booking-system-for-hotel'),
            ));
        }

        $success_count = 0;
        foreach ($booking_ids as $booking_id) {
            if ('confirm' === $action) {
                $result = IRBSFH_Booking::confirm($booking_id);
            } elseif ('deny' === $action) {
                $result = IRBSFH_Booking::deny($booking_id);
            } elseif ('delete' === $action) {
                $result = IRBSFH_Booking::delete($booking_id);
            }

            if (isset($result['success']) && $result['success']) {
                $success_count++;
            }
        }

        wp_send_json_success(array(
            'message' => sprintf(
                /* translators: %d: number of bookings */
                _n('%d booking updated successfully.', '%d bookings updated successfully.', $success_count, 'intelligent-room-booking-system-for-hotel'),
                $success_count
            ),
        ));
    }

    /**
     * AJAX: Get room
     */
    public function ajax_get_room()
    {
        check_ajax_referer('irbsfh_admin_nonce', 'nonce');

        if (! current_user_can('manage_options')) {
            wp_send_json_error(array(
                'message' => __('Permission denied.', 'intelligent-room-booking-system-for-hotel'),
            ));
        }

        $room_id = isset($_POST['room_id']) ? absint($_POST['room_id']) : 0;
        $room = IRBSFH_Room::get($room_id);

        if ($room) {
            wp_send_json_success(array('room' => $room));
        } else {
            wp_send_json_error(array(
                'message' => __('Room not found.', 'intelligent-room-booking-system-for-hotel'),
            ));
        }
    }

    /**
     * AJAX: Save room
     */
    public function ajax_save_room()
    {
        check_ajax_referer('irbsfh_admin_nonce', 'nonce');

        if (! current_user_can('manage_options')) {
            wp_send_json_error(array(
                'message' => __('Permission denied.', 'intelligent-room-booking-system-for-hotel'),
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
            $result = IRBSFH_Room::update($room_id, $data);
            $message = __('Room updated successfully.', 'intelligent-room-booking-system-for-hotel');
        } else {
            $result = IRBSFH_Room::create($data);
            $message = __('Room created successfully.', 'intelligent-room-booking-system-for-hotel');
        }

        if ($result) {
            wp_send_json_success(array('message' => $message));
        } else {
            wp_send_json_error(array(
                'message' => __('Failed to save room.', 'intelligent-room-booking-system-for-hotel'),
            ));
        }
    }

    /**
     * AJAX: Delete room
     */
    public function ajax_delete_room()
    {
        check_ajax_referer('irbsfh_admin_nonce', 'nonce');

        if (! current_user_can('manage_options')) {
            wp_send_json_error(array(
                'message' => __('Permission denied.', 'intelligent-room-booking-system-for-hotel'),
            ));
        }

        $room_id = isset($_POST['room_id']) ? absint($_POST['room_id']) : 0;
        $result = IRBSFH_Room::delete($room_id);

        if ($result) {
            wp_send_json_success(array(
                'message' => __('Room deleted successfully.', 'intelligent-room-booking-system-for-hotel'),
            ));
        } else {
            wp_send_json_error(array(
                'message' => __('Failed to delete room. Room may have existing bookings.', 'intelligent-room-booking-system-for-hotel'),
            ));
        }
    }
}
