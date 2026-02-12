<?php

/**
 * Booking handler class
 *
 * @package IntelligentRoomBookingSystemForHotel
 */

// Exit if accessed directly
if (! defined('ABSPATH')) {
    exit;
}

/**
 * Booking class
 */
class IRBSFH_Booking
{

    /**
     * Create a new booking
     *
     * @param array $data Booking data.
     * @return array
     */
    public static function create($data)
    {
        // Sanitize data
        $data = IRBSFH_Validation::sanitize_booking_data($data);

        // Validate data
        $validation = IRBSFH_Validation::validate_booking_form($data);
        if (! $validation['valid']) {
            return array(
                'success' => false,
                'message' => __('Please correct the errors in your form.', 'intelligent-room-booking-system-for-hotel'),
                'errors' => $validation['errors'],
            );
        }

        // Check if user can book
        $can_book = IRBSFH_Validation::can_user_book($data['user_id'], $data['room_id']);
        if (! $can_book['can_book']) {
            return array(
                'success' => false,
                'message' => $can_book['message'],
            );
        }

        // Check room availability
        $availability = IRBSFH_Validation::is_room_available($data['room_id'], $data['booking_date']);
        if (! $availability['available']) {
            return array(
                'success' => false,
                'message' => $availability['message'],
            );
        }

        // Create booking
        $booking_id = IRBSFH_Database::create_booking($data);

        if (! $booking_id) {
            return array(
                'success' => false,
                'message' => __('Failed to create booking. Please try again.', 'intelligent-room-booking-system-for-hotel'),
            );
        }

        // Send admin notification
        IRBSFH_Email::send_admin_notification($booking_id);

        // Fire action hook
        do_action('irbsfh_booking_created', $booking_id, $data);

        return array(
            'success' => true,
            'message' => __('Your booking request has been submitted successfully. You will receive an email once it is confirmed.', 'intelligent-room-booking-system-for-hotel'),
            'booking_id' => $booking_id,
        );
    }

    /**
     * Confirm a booking
     *
     * @param int $booking_id Booking ID.
     * @return array
     */
    public static function confirm($booking_id)
    {
        $booking = IRBSFH_Database::get_booking($booking_id);

        if (! $booking) {
            return array(
                'success' => false,
                'message' => __('Booking not found.', 'intelligent-room-booking-system-for-hotel'),
            );
        }

        // Update status
        $updated = IRBSFH_Database::update_booking(
            $booking_id,
            array('status' => 'confirmed')
        );

        if (! $updated) {
            return array(
                'success' => false,
                'message' => __('Failed to confirm booking.', 'intelligent-room-booking-system-for-hotel'),
            );
        }

        // Send confirmation email to user
        IRBSFH_Email::send_user_confirmation($booking_id);

        // Fire action hook
        do_action('irbsfh_booking_confirmed', $booking_id, $booking);

        return array(
            'success' => true,
            'message' => __('Booking confirmed successfully.', 'intelligent-room-booking-system-for-hotel'),
        );
    }

    /**
     * Deny a booking
     *
     * @param int $booking_id Booking ID.
     * @return array
     */
    public static function deny($booking_id)
    {
        $booking = IRBSFH_Database::get_booking($booking_id);

        if (! $booking) {
            return array(
                'success' => false,
                'message' => __('Booking not found.', 'intelligent-room-booking-system-for-hotel'),
            );
        }

        // Update status
        $updated = IRBSFH_Database::update_booking(
            $booking_id,
            array('status' => 'cancelled')
        );

        if (! $updated) {
            return array(
                'success' => false,
                'message' => __('Failed to deny booking.', 'intelligent-room-booking-system-for-hotel'),
            );
        }

        // Send denial email to user
        IRBSFH_Email::send_user_denial($booking_id);

        // Fire action hook
        do_action('irbsfh_booking_cancelled', $booking_id, $booking);

        return array(
            'success' => true,
            'message' => __('Booking denied successfully.', 'intelligent-room-booking-system-for-hotel'),
        );
    }

    /**
     * Cancel a booking
     *
     * @param int $booking_id Booking ID.
     * @param int $user_id User ID (for permission check).
     * @return array
     */
    public static function cancel($booking_id, $user_id = null)
    {
        $booking = IRBSFH_Database::get_booking($booking_id);

        if (! $booking) {
            return array(
                'success' => false,
                'message' => __('Booking not found.', 'intelligent-room-booking-system-for-hotel'),
            );
        }

        // Check permissions if user_id provided
        if ($user_id && intval($booking->user_id) !== intval($user_id)) {
            return array(
                'success' => false,
                'message' => __('You do not have permission to cancel this booking.', 'intelligent-room-booking-system-for-hotel'),
            );
        }

        // Update status
        $updated = IRBSFH_Database::update_booking(
            $booking_id,
            array('status' => 'cancelled')
        );

        if (! $updated) {
            return array(
                'success' => false,
                'message' => __('Failed to cancel booking.', 'intelligent-room-booking-system-for-hotel'),
            );
        }

        // Fire action hook
        do_action('irbsfh_booking_cancelled', $booking_id, $booking);

        return array(
            'success' => true,
            'message' => __('Booking cancelled successfully.', 'intelligent-room-booking-system-for-hotel'),
        );
    }

    /**
     * Delete a booking
     *
     * @param int $booking_id Booking ID.
     * @return array
     */
    public static function delete($booking_id)
    {
        $booking = IRBSFH_Database::get_booking($booking_id);

        if (! $booking) {
            return array(
                'success' => false,
                'message' => __('Booking not found.', 'intelligent-room-booking-system-for-hotel'),
            );
        }

        $deleted = IRBSFH_Database::delete_booking($booking_id);

        if (! $deleted) {
            return array(
                'success' => false,
                'message' => __('Failed to delete booking.', 'intelligent-room-booking-system-for-hotel'),
            );
        }

        // Fire action hook
        do_action('irbsfh_booking_deleted', $booking_id, $booking);

        return array(
            'success' => true,
            'message' => __('Booking deleted successfully.', 'intelligent-room-booking-system-for-hotel'),
        );
    }
}
