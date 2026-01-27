<?php
/**
 * Booking handler class
 *
 * @package AdvancedBookingSystem
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Booking class
 */
class ABS_Booking {

    /**
     * Create a new booking
     *
     * @param array $data Booking data.
     * @return array
     */
    public static function create( $data ) {
        // Sanitize data
        $data = ABS_Validation::sanitize_booking_data( $data );
        
        // Validate data
        $validation = ABS_Validation::validate_booking_form( $data );
        if ( ! $validation['valid'] ) {
            return array(
                'success' => false,
                'message' => __( 'Please correct the errors in your form.', 'advanced-hotel-room-booking-system' ),
                'errors' => $validation['errors'],
            );
        }
        
        // Check if user can book
        $can_book = ABS_Validation::can_user_book( $data['user_id'], $data['room_id'] );
        if ( ! $can_book['can_book'] ) {
            return array(
                'success' => false,
                'message' => $can_book['message'],
            );
        }
        
        // Check room availability
        $availability = ABS_Validation::is_room_available( $data['room_id'], $data['booking_date'] );
        if ( ! $availability['available'] ) {
            return array(
                'success' => false,
                'message' => $availability['message'],
            );
        }
        
        // Create booking
        $booking_id = ABS_Database::create_booking( $data );
        
        if ( ! $booking_id ) {
            return array(
                'success' => false,
                'message' => __( 'Failed to create booking. Please try again.', 'advanced-hotel-room-booking-system' ),
            );
        }
        
        // Send admin notification
        ABS_Email::send_admin_notification( $booking_id );
        
        // Fire action hook
        do_action( 'abs_booking_created', $booking_id, $data );
        
        return array(
            'success' => true,
            'message' => __( 'Your booking request has been submitted successfully. You will receive an email once it is confirmed.', 'advanced-hotel-room-booking-system' ),
            'booking_id' => $booking_id,
        );
    }

    /**
     * Confirm a booking
     *
     * @param int $booking_id Booking ID.
     * @return array
     */
    public static function confirm( $booking_id ) {
        $booking = ABS_Database::get_booking( $booking_id );
        
        if ( ! $booking ) {
            return array(
                'success' => false,
                'message' => __( 'Booking not found.', 'advanced-hotel-room-booking-system' ),
            );
        }
        
        // Update status
        $updated = ABS_Database::update_booking(
            $booking_id,
            array( 'status' => 'confirmed' )
        );
        
        if ( ! $updated ) {
            return array(
                'success' => false,
                'message' => __( 'Failed to confirm booking.', 'advanced-hotel-room-booking-system' ),
            );
        }
        
        // Send confirmation email to user
        ABS_Email::send_user_confirmation( $booking_id );
        
        // Fire action hook
        do_action( 'abs_booking_confirmed', $booking_id, $booking );
        
        return array(
            'success' => true,
            'message' => __( 'Booking confirmed successfully.', 'advanced-hotel-room-booking-system' ),
        );
    }

    /**
     * Deny a booking
     *
     * @param int $booking_id Booking ID.
     * @return array
     */
    public static function deny( $booking_id ) {
        $booking = ABS_Database::get_booking( $booking_id );
        
        if ( ! $booking ) {
            return array(
                'success' => false,
                'message' => __( 'Booking not found.', 'advanced-hotel-room-booking-system' ),
            );
        }
        
        // Update status
        $updated = ABS_Database::update_booking(
            $booking_id,
            array( 'status' => 'cancelled' )
        );
        
        if ( ! $updated ) {
            return array(
                'success' => false,
                'message' => __( 'Failed to deny booking.', 'advanced-hotel-room-booking-system' ),
            );
        }
        
        // Send denial email to user
        ABS_Email::send_user_denial( $booking_id );
        
        // Fire action hook
        do_action( 'abs_booking_cancelled', $booking_id, $booking );
        
        return array(
            'success' => true,
            'message' => __( 'Booking denied successfully.', 'advanced-hotel-room-booking-system' ),
        );
    }

    /**
     * Cancel a booking
     *
     * @param int $booking_id Booking ID.
     * @param int $user_id User ID (for permission check).
     * @return array
     */
    public static function cancel( $booking_id, $user_id = null ) {
        $booking = ABS_Database::get_booking( $booking_id );
        
        if ( ! $booking ) {
            return array(
                'success' => false,
                'message' => __( 'Booking not found.', 'advanced-hotel-room-booking-system' ),
            );
        }
        
        // Check permissions if user_id provided
        if ( $user_id && intval( $booking->user_id ) !== intval( $user_id ) ) {
            return array(
                'success' => false,
                'message' => __( 'You do not have permission to cancel this booking.', 'advanced-hotel-room-booking-system' ),
            );
        }
        
        // Update status
        $updated = ABS_Database::update_booking(
            $booking_id,
            array( 'status' => 'cancelled' )
        );
        
        if ( ! $updated ) {
            return array(
                'success' => false,
                'message' => __( 'Failed to cancel booking.', 'advanced-hotel-room-booking-system' ),
            );
        }
        
        // Fire action hook
        do_action( 'abs_booking_cancelled', $booking_id, $booking );
        
        return array(
            'success' => true,
            'message' => __( 'Booking cancelled successfully.', 'advanced-hotel-room-booking-system' ),
        );
    }

    /**
     * Delete a booking
     *
     * @param int $booking_id Booking ID.
     * @return array
     */
    public static function delete( $booking_id ) {
        $booking = ABS_Database::get_booking( $booking_id );
        
        if ( ! $booking ) {
            return array(
                'success' => false,
                'message' => __( 'Booking not found.', 'advanced-hotel-room-booking-system' ),
            );
        }
        
        $deleted = ABS_Database::delete_booking( $booking_id );
        
        if ( ! $deleted ) {
            return array(
                'success' => false,
                'message' => __( 'Failed to delete booking.', 'advanced-hotel-room-booking-system' ),
            );
        }
        
        // Fire action hook
        do_action( 'abs_booking_deleted', $booking_id, $booking );
        
        return array(
            'success' => true,
            'message' => __( 'Booking deleted successfully.', 'advanced-hotel-room-booking-system' ),
        );
    }
}